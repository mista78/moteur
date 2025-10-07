<?php

require_once 'jest-php.php';
require_once 'IJCalculator.php';

// Exemple 1: Tests de base
describe('IJCalculator', function() {

    test('should calculate correct amount for mock.json', function() {
        $mockData = json_decode(file_get_contents(__DIR__ . '/mock.json'), true);
        $calculator = new IJCalculator(__DIR__ . '/taux.csv');

        $result = $calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'pass_value' => 47000,
            'birth_date' => '1989-09-26',
            'current_date' => '2024-09-09',
            'attestation_date' => '2024-01-31',
            'last_payment_date' => null,
            'affiliation_date' => null,
            'nb_trimestres' => 8,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => 0
        ]);

        expect($result['montant'])->toBeCloseTo(750.60, 0.01);
    });

    test('should calculate correct amount for mock2.json', function() {
        $mockData = json_decode(file_get_contents(__DIR__ . '/mock2.json'), true);
        $calculator = new IJCalculator(__DIR__ . '/taux.csv');

        $result = $calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'c',
            'option' => 100,
            'pass_value' => 47000,
            'birth_date' => '1958-06-03',
            'current_date' => '2024-06-12',
            'attestation_date' => null,
            'last_payment_date' => null,
            'affiliation_date' => null,
            'nb_trimestres' => 8,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => 0
        ]);

        expect($result['montant'])->toBeCloseTo(17318.92, 0.01);
    });

    test('should return result with required properties', function() {
        $mockData = json_decode(file_get_contents(__DIR__ . '/mock.json'), true);
        $calculator = new IJCalculator(__DIR__ . '/taux.csv');

        $result = $calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'pass_value' => 47000,
            'birth_date' => '1989-09-26',
            'current_date' => '2024-09-09',
            'attestation_date' => null,
            'last_payment_date' => null,
            'affiliation_date' => null,
            'nb_trimestres' => 8,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => 0
        ]);

        expect($result)->toHaveProperty('montant');
        expect($result)->toHaveProperty('nb_jours');
        expect($result)->toHaveProperty('age');
    });
});

describe('Age calculation', function() {

    test('should calculate correct age', function() {
        $calculator = new IJCalculator(__DIR__ . '/taux.csv');
        $age = $calculator->calculateAge('2024-09-09', '1989-09-26');

        expect($age)->toBe(34);
    });

    test('should handle birthday not yet reached', function() {
        $calculator = new IJCalculator(__DIR__ . '/taux.csv');
        $age = $calculator->calculateAge('2024-09-25', '1989-09-26');

        expect($age)->toBe(34);
    });

    test('should handle birthday already passed', function() {
        $calculator = new IJCalculator(__DIR__ . '/taux.csv');
        $age = $calculator->calculateAge('2024-09-27', '1989-09-26');

        expect($age)->toBe(35);
    });
});

describe('Trimestres calculation', function() {

    test('should calculate correct number of trimestres', function() {
        $calculator = new IJCalculator(__DIR__ . '/taux.csv');
        $trimestres = $calculator->calculateTrimesters('2017-07-01', '2024-09-09');

        expect($trimestres)->toBeGreaterThan(0);
    });

    test('should return 0 for empty affiliation date', function() {
        $calculator = new IJCalculator(__DIR__ . '/taux.csv');
        $trimestres = $calculator->calculateTrimesters('', '2024-09-09');

        expect($trimestres)->toBe(0);
    });

    test('should return 0 for future affiliation date', function() {
        $calculator = new IJCalculator(__DIR__ . '/taux.csv');
        $trimestres = $calculator->calculateTrimesters('2025-01-01', '2024-09-09');

        expect($trimestres)->toBe(0);
    });
});

describe('Valid med controleur', function() {

    test('should not pay if valid_med_controleur is 0', function() {
        $mockData = [[
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-12-31',
            'valid_med_controleur' => 0,
            'rechute-line' => 0,
            'dt-line' => 1,
            'declaration-date-line' => '2024-01-01',
            'date_deb_droit' => '2024-01-01'
        ]];

        $calculator = new IJCalculator(__DIR__ . '/taux.csv');

        $result = $calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'pass_value' => 47000,
            'birth_date' => '1989-09-26',
            'current_date' => '2024-12-31',
            'attestation_date' => '2024-12-31',
            'last_payment_date' => null,
            'affiliation_date' => null,
            'nb_trimestres' => 8,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => 0
        ]);

        expect($result['montant'])->toBe(0);
        expect($result['nb_jours'])->toBe(0);
    });

    test('should pay if valid_med_controleur is 1', function() {
        $mockData = [[
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-12-31',
            'valid_med_controleur' => 1,
            'rechute-line' => 0,
            'dt-line' => 1,
            'declaration-date-line' => '2024-01-01',
            'date_deb_droit' => '2024-04-01',
            'attestation-date-line' => '2024-12-31'
        ]];

        $calculator = new IJCalculator(__DIR__ . '/taux.csv');

        $result = $calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'pass_value' => 47000,
            'birth_date' => '1989-09-26',
            'current_date' => '2024-12-31',
            'attestation_date' => null,
            'last_payment_date' => null,
            'affiliation_date' => null,
            'nb_trimestres' => 8,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => 0
        ]);

        expect($result['montant'])->toBeGreaterThan(0);
        expect($result['nb_jours'])->toBeGreaterThan(0);
    });
});

// Lancer tous les tests
JestPHP::run();
