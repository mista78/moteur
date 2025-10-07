<?php

require_once 'jest-php.php';
require_once 'IJCalculator.php';

// Test cases avec résultats attendus
$testCases = [
    'mock.json' => [
        'expected' => 750.6, // Pas de résultat attendu donné
        'statut' => 'M',
        'classe' => 'A',
        "payment_start" => ["2024-01-22", ""],
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
    ],
    'mock2.json' => [
        'expected' => 17318.92,
        "payment_start" => ["","","", "", "","2023-12-07"],
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
    ],
    'mock3.json' => [
        'expected' => 41832.6,
        'statut' => 'M',
        'classe' => 'B',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1961-12-01',
        'current_date' => '2024-12-27',
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 8,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock4.json' => [
        'expected' => 37875.88,
        'statut' => 'M',
        'classe' => 'C',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1958-12-21',
        'current_date' => '2024-09-29',
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 8,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock5.json' => [
        'expected' => 34276.56,
        'statut' => 'CCPL',
        'classe' => 'C',
        'option' => 25,
        'pass_value' => 47000,
        'birth_date' => '1984-01-08',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 8,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock6.json' => [
        'expected' => 31412.61,
        'statut' => 'M',
        'classe' => 'B',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1962-05-01',
        'current_date' => '2024-12-27',
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 50,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock7.json' => [
        'expected' => 74331.79,
        'statut' => 'M',
        'classe' => 'A',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1959-10-07',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock8.json' => [
        'expected' => 19291.28,
        'statut' => 'M',
        'classe' => 'B',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1950-10-07',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock9.json' => [
        'expected' => 53467.98,
        'statut' => 'M',
        'classe' => 'B',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1953-01-22',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock10.json' => [
        'expected' => 51744.25,
        'statut' => 'M',
        'classe' => 'A',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1961-10-14',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock11.json' => [
        'expected' => 10245.69,
        'statut' => 'M',
        'classe' => 'B',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1967-09-15',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock12.json' => [
        'expected' => 8330.25,
        'statut' => 'M',
        'classe' => 'B',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1953-12-31',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock13.json' => [
        'expected' => 4096.96,
        'statut' => 'rspm',
        'classe' => 'A',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1953-12-31',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock14.json' => [
        'expected' => 19215.36,
        'statut' => 'M',
        'classe' => 'C',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1985-07-27',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => '2017-07-01',
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock15.json' => [
        'expected' => 12497.49,
        'statut' => 'M',
        'classe' => 'B',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1990-04-15',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => '2017-07-01',
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock16.json' => [
        'expected' => 57099.15,
        'statut' => 'M',
        'classe' => 'A',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1960-01-29',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => '2017-07-01',
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock17.json' => [
        'expected' => 47296.39,
        'statut' => 'M',
        'classe' => 'C',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1960-01-05',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => '2017-07-01',
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock18.json' => [
        'expected' => 0,
        'statut' => 'M',
        'classe' => 'A',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1940-05-15',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => '2017-07-01',
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
];

// Tests avec JestPHP
describe('IJCalculator - Mock Tests', function() use ($testCases) {

    foreach ($testCases as $mockFile => $params) {
        $mockData = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $mockFile), true);
        $adherent = $mockData[0]["adherent_number"];
        test("should calculate correct amount for $adherent", function() use ($mockData,$mockFile, $params) {
            // Charger les données du mock
            

            if (!$mockData) {
                throw new Exception("Impossible de charger $mockFile");
            }

            // Créer le calculateur
            $calculator = new IJCalculator(__DIR__ . DIRECTORY_SEPARATOR . 'taux.csv');

            // Préparer les données au format attendu
            $requestData = [
                'arrets' => $mockData,
                'statut' => $params['statut'],
                'classe' => $params['classe'],
                'option' => $params['option'],
                'pass_value' => $params['pass_value'],
                'payment_start' => $params['payment_start'] ?? null,
                'birth_date' => $params['birth_date'],
                'current_date' => $params['current_date'],
                'attestation_date' => $params['attestation_date'],
                'last_payment_date' => $params['last_payment_date'],
                'affiliation_date' => $params['affiliation_date'],
                'nb_trimestres' => $params['nb_trimestres'],
                'previous_cumul_days' => $params['previous_cumul_days'],
                'prorata' => $params['prorata'],
                'patho_anterior' => $params['patho_anterior']
            ];

            $result = $calculator->calculateAmount($requestData);

            // Assertions
            expect($result['montant'])->toBeCloseTo($params['expected'], 0.01);

            // Test payment_start si défini dans les paramètres
            if (isset($params['payment_start'])) {
                foreach ($params['payment_start'] as $key => $expectedDate) {
                    if (isset($result['payment_details'][$key])) {
                        $actualDate = $result['payment_details'][$key]['payment_start'] ?? '';
                        expect($actualDate)->toBe($expectedDate);
                    }
                }
            }
        });
    }
});

// Lancer les tests
JestPHP::run();
