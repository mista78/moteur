<?php

require_once __DIR__ . '/../jest-php.php';
require_once __DIR__ . '/../Services/AmountCalculationInterface.php';
require_once __DIR__ . '/../Services/AmountCalculationService.php';
require_once __DIR__ . '/../Services/RateServiceInterface.php';
require_once __DIR__ . '/../Services/RateService.php';
require_once __DIR__ . '/../Services/DateCalculationInterface.php';
require_once __DIR__ . '/../Services/DateService.php';
require_once __DIR__ . '/../Services/TauxDeterminationInterface.php';
require_once __DIR__ . '/../Services/TauxDeterminationService.php';

use IJCalculator\Services\AmountCalculationService;
use IJCalculator\Services\RateService;
use IJCalculator\Services\DateService;
use IJCalculator\Services\TauxDeterminationService;

describe('AmountCalculationService', function() {

    // Helper to create service instance
    function createService() {
        $rateService = new RateService(__DIR__ . '/../taux.csv');
        $dateService = new DateService();
        $tauxService = new TauxDeterminationService();
        return new AmountCalculationService($dateService, $rateService, $tauxService);
    }

    test('should calculate end payment dates for age >= 70', function() {
        $service = createService();

        $arrets = [
            [
                'date-effet' => '2024-01-01'
            ]
        ];

        $result = $service->calculateEndPaymentDates($arrets, 0, '1950-01-01', '2024-01-01');

        expect($result !== null)->toBe(true);
        expect(isset($result['end_period_1']))->toBe(true);
        expect(isset($result['end_period_2']))->toBe(false); // No period 2 for 70+
    });

    test('should calculate end payment dates for age 62-69', function() {
        $service = createService();

        $arrets = [
            [
                'date-effet' => '2024-01-01'
            ]
        ];

        $result = $service->calculateEndPaymentDates($arrets, 0, '1960-01-01', '2024-01-01');

        expect($result !== null)->toBe(true);
        expect(isset($result['end_period_1']))->toBe(true);
        expect(isset($result['end_period_2']))->toBe(true);
        expect(isset($result['end_period_3']))->toBe(true);
    });

    test('should calculate 3 periods for age 62-69 with previous cumul days', function() {
        $service = createService();

        $arrets = [
            [
                'date-effet' => '2024-01-01'
            ]
        ];

        $result = $service->calculateEndPaymentDates($arrets, 100, '1960-01-01', '2024-01-01');

        expect($result !== null)->toBe(true);
        // Dates should be adjusted by previous_cumul_days
        expect(isset($result['end_period_1']))->toBe(true);
        expect(isset($result['end_period_2']))->toBe(true);
        expect(isset($result['end_period_3']))->toBe(true);
    });

    test('should return null if no date-effet found', function() {
        $service = createService();

        $arrets = [
            [
                'arret-from-line' => '2024-01-01',
                'arret-to-line' => '2024-01-31'
            ]
        ];

        $result = $service->calculateEndPaymentDates($arrets, 0, '1990-01-01', '2024-01-01');

        expect($result)->toBeNull();
    });

    test('should apply prorata correctly', function() {
        $service = createService();

        $data = [
            'arrets' => [
                [
                    'arret-from-line' => '2024-01-01',
                    'arret-to-line' => '2024-01-31',
                    'arret_diff' => 31,
                    'rechute-line' => 0,
                    'valid_med_controleur' => 1,
                    'date-effet' => '2024-01-01'
                ]
            ],
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1950-01-01',
            'current_date' => '2024-02-01',
            'previous_cumul_days' => 0,
            'nb_trimestres' => 60,
            'patho_anterior' => false,
            'attestation_date' => '2024-01-31',
            'last_payment_date' => null,
            'affiliation_date' => null,
            'prorata' => 0.5
        ];

        $resultFull = $service->calculateAmount(array_merge($data, ['prorata' => 1]));
        $resultHalf = $service->calculateAmount(array_merge($data, ['prorata' => 0.5]));

        expect($resultHalf['montant'])->toBeCloseTo($resultFull['montant'] * 0.5, 0.01);
    });

    test('should return 0 montant if nb_trimestres < 8', function() {
        $service = createService();

        $data = [
            'arrets' => [
                [
                    'arret-from-line' => '2024-01-01',
                    'arret-to-line' => '2024-01-31',
                    'arret_diff' => 31,
                    'rechute-line' => 0,
                    'valid_med_controleur' => 1,
                    'date-effet' => '2024-01-01'
                ]
            ],
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1990-01-01',
            'current_date' => '2024-02-01',
            'previous_cumul_days' => 0,
            'nb_trimestres' => 7,
            'patho_anterior' => false,
            'attestation_date' => '2024-01-31',
            'last_payment_date' => null,
            'affiliation_date' => null,
            'prorata' => 1
        ];

        $result = $service->calculateAmount($data);

        expect($result['nb_jours'])->toBe(0);
        expect($result['montant'])->toEqual(0);
    });

    test('should limit cumulative days to 365 for age >= 70', function() {
        $service = createService();

        $data = [
            'arrets' => [
                [
                    'arret-from-line' => '2024-01-01',
                    'arret-to-line' => '2024-12-31',
                    'arret_diff' => 366,
                    'rechute-line' => 0,
                    'valid_med_controleur' => 1,
                    'date-effet' => '2024-01-01'
                ]
            ],
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1950-01-01',
            'current_date' => '2025-01-01',
            'previous_cumul_days' => 0,
            'nb_trimestres' => 60,
            'patho_anterior' => false,
            'attestation_date' => '2024-12-31',
            'last_payment_date' => null,
            'affiliation_date' => null,
            'prorata' => 1
        ];

        $result = $service->calculateAmount($data);

        expect($result['nb_jours'] <= 365)->toBe(true);
    });

    test('should calculate correct age from birth date', function() {
        $service = createService();

        $data = [
            'arrets' => [
                [
                    'arret-from-line' => '2024-01-01',
                    'arret-to-line' => '2024-01-31',
                    'arret_diff' => 31,
                    'rechute-line' => 0,
                    'valid_med_controleur' => 1,
                    'date-effet' => '2024-01-01'
                ]
            ],
            'statut' => 'M',
            'classe' => 'B',
            'option' => 100,
            'birth_date' => '1990-06-15',
            'current_date' => '2024-07-01',
            'previous_cumul_days' => 0,
            'nb_trimestres' => 60,
            'patho_anterior' => false,
            'attestation_date' => '2024-01-31',
            'last_payment_date' => null,
            'affiliation_date' => null,
            'prorata' => 1
        ];

        $result = $service->calculateAmount($data);

        expect($result['age'])->toBe(34);
    });

    test('should handle forced rate override', function() {
        $service = createService();

        // Use mock21 data as base
        $mockData = json_decode(file_get_contents(__DIR__ . '/../mock21.json'), true);

        $data = [
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'pass_value' => 47000,
            'birth_date' => '1972-06-04',
            'current_date' => date('Y-m-d'),
            'attestation_date' => null,
            'last_payment_date' => null,
            'affiliation_date' => '2002-10-01',
            'nb_trimestres' => 23,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => 1,
            'forced_rate' => 25.02  // Taux journalier forcé (daily rate)
        ];

        $result = $service->calculateAmount($data);

        // Expected: 29 jours × 25.02€ = 725.58€
        expect($result['montant'])->toBeCloseTo(725.58, 0.01);
    });

    test('should respect 3-year maximum (1095 days)', function() {
        $service = createService();

        $data = [
            'arrets' => [
                [
                    'arret-from-line' => '2024-01-01',
                    'arret-to-line' => '2024-01-31',
                    'arret_diff' => 31,
                    'rechute-line' => 0,
                    'valid_med_controleur' => 1,
                    'date-effet' => '2024-01-01'
                ]
            ],
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1990-01-01',
            'current_date' => '2024-02-01',
            'previous_cumul_days' => 1100, // Already over 1095
            'nb_trimestres' => 60,
            'patho_anterior' => false,
            'attestation_date' => '2024-01-31',
            'last_payment_date' => null,
            'affiliation_date' => null,
            'prorata' => 1
        ];

        $result = $service->calculateAmount($data);

        expect($result['nb_jours'])->toBe(0);
        expect($result['montant'])->toEqual(0);
    });

    test('should auto-calculate trimestres from affiliation date', function() {
        $service = createService();

        $data = [
            'arrets' => [
                [
                    'arret-from-line' => '2024-01-01',
                    'arret-to-line' => '2024-01-31',
                    'arret_diff' => 31,
                    'rechute-line' => 0,
                    'valid_med_controleur' => 1,
                    'date-effet' => '2024-01-01'
                ]
            ],
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1990-01-01',
            'current_date' => '2024-02-01',
            'previous_cumul_days' => 0,
            'nb_trimestres' => 0, // Will be auto-calculated
            'patho_anterior' => false,
            'attestation_date' => '2024-01-31',
            'last_payment_date' => null,
            'affiliation_date' => '2020-01-01', // 4 years ago = 16 trimesters
            'prorata' => 1
        ];

        $result = $service->calculateAmount($data);

        expect($result['nb_trimestres'])->toBeGreaterThan(0);
        expect($result['nb_trimestres'] >= 16)->toBe(true);
    });
});

JestPHP::run();
