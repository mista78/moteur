<?php

declare(strict_types=1);

namespace Tests;

use App\Services\AmountCalculationService;
use App\Services\RateService;
use App\Services\DateService;
use App\Services\TauxDeterminationService;
use PHPUnit\Framework\TestCase;

class AmountCalculationServiceTest extends TestCase
{
    private AmountCalculationService $service;
    private RateService $rateService;
    private DateService $dateService;
    private TauxDeterminationService $tauxService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateService = new RateService(__DIR__ . '/../data/taux.csv');
        $this->dateService = new DateService();
        $this->tauxService = new TauxDeterminationService();
        $this->service = new AmountCalculationService(
            $this->dateService,
            $this->rateService,
            $this->tauxService
        );
    }

    public function testCalculateEndPaymentDatesForAge70Plus(): void
    {
        $arrets = [
            ['date-effet' => '2024-01-01']
        ];

        $result = $this->service->calculateEndPaymentDates($arrets, 0, '1950-01-01', '2024-01-01');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('end_period_1', $result);
        $this->assertArrayNotHasKey('end_period_2', $result); // No period 2 for 70+
    }

    public function testCalculateEndPaymentDatesForAge62To69(): void
    {
        $arrets = [
            ['date-effet' => '2024-01-01']
        ];

        $result = $this->service->calculateEndPaymentDates($arrets, 0, '1960-01-01', '2024-01-01');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('end_period_1', $result);
        $this->assertArrayHasKey('end_period_2', $result);
        $this->assertArrayHasKey('end_period_3', $result);
    }

    public function testCalculate3PeriodsForAge62To69WithPreviousCumulDays(): void
    {
        $arrets = [
            ['date-effet' => '2024-01-01']
        ];

        $result = $this->service->calculateEndPaymentDates($arrets, 100, '1960-01-01', '2024-01-01');

        $this->assertNotNull($result);
        // Dates should be adjusted by previous_cumul_days
        $this->assertArrayHasKey('end_period_1', $result);
        $this->assertArrayHasKey('end_period_2', $result);
        $this->assertArrayHasKey('end_period_3', $result);
    }

    public function testReturnNullIfNoDateEffetFound(): void
    {
        $arrets = [
            [
                'arret-from-line' => '2024-01-01',
                'arret-to-line' => '2024-01-31'
            ]
        ];

        $result = $this->service->calculateEndPaymentDates($arrets, 0, '1990-01-01', '2024-01-01');

        $this->assertNull($result);
    }

    public function testApplyProrataCorrectly(): void
    {
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

        $resultFull = $this->service->calculateAmount(array_merge($data, ['prorata' => 1]));
        $resultHalf = $this->service->calculateAmount(array_merge($data, ['prorata' => 0.5]));

        $this->assertEqualsWithDelta($resultFull['montant'] * 0.5, $resultHalf['montant'], 0.01);
    }

    public function testReturn0MontantIfNbTrimestresLessThan8(): void
    {
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

        $result = $this->service->calculateAmount($data);

        $this->assertEquals(0, $result['nb_jours']);
        $this->assertEquals(0, $result['montant']);
    }

    public function testLimitCumulativeDaysTo365ForAge70Plus(): void
    {
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

        $result = $this->service->calculateAmount($data);

        $this->assertLessThanOrEqual(365, $result['nb_jours']);
    }

    public function testCalculateCorrectAgeFromBirthDate(): void
    {
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

        $result = $this->service->calculateAmount($data);

        $this->assertEquals(34, $result['age']);
    }

    public function testHandleForcedRateOverride(): void
    {
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

        $result = $this->service->calculateAmount($data);

        // Expected: 29 jours × 25.02€ = 725.58€
        $this->assertEqualsWithDelta(725.58, $result['montant'], 0.01);
    }

    public function testRespect3YearMaximum1095Days(): void
    {
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

        $result = $this->service->calculateAmount($data);

        $this->assertEquals(0, $result['nb_jours']);
        $this->assertEquals(0, $result['montant']);
    }

    public function testAutoCalculateTrimestresFromAffiliationDate(): void
    {
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

        $result = $this->service->calculateAmount($data);

        $this->assertGreaterThan(0, $result['nb_trimestres']);
        $this->assertGreaterThanOrEqual(16, $result['nb_trimestres']);
    }
}
