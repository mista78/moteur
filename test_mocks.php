<?php

require_once 'IJCalculator.php';

// Test cases avec résultats attendus
$testCases = [
    'mock.json' => [
        'expected' => 750.6, // Pas de résultat attendu donné
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
    ],
    'mock2.json' => [
        'expected' => 17318.92,
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
];

echo "=== TEST DES MOCKS ===\n\n";

foreach ($testCases as $mockFile => $params) {
    echo str_repeat("=", 80) . "\n";
    echo "TEST: $mockFile\n";
    echo str_repeat("=", 80) . "\n";

    // Charger les données du mock
    $mockData = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR ."$mockFile"), true);

    if (!$mockData) {
        echo "ERREUR: Impossible de charger $mockFile\n\n";
        continue;
    }

    // Créer le calculateur
    $calculator = new IJCalculator(__DIR__ . DIRECTORY_SEPARATOR .'taux.csv');

    // Afficher les paramètres
    echo "\nParamètres:\n";
    echo "  Statut: {$params['statut']}\n";
    echo "  Classe: {$params['classe']}\n";
    echo "  Option: {$params['option']}%\n";
    echo "  Date naissance: {$params['birth_date']}\n";
    echo "  Date actuelle: {$params['current_date']}\n";
    echo "  Nb trimestres: {$params['nb_trimestres']}\n";

    // Calculer
    try {
        // Préparer les données au format attendu
        $requestData = [
            'arrets' => $mockData,
            'statut' => $params['statut'],
            'classe' => $params['classe'],
            'option' => $params['option'],
            'pass_value' => $params['pass_value'],
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

        // Debug: afficher le résultat complet
        // echo "DEBUG result: " . print_r($result, true) . "\n";

        $calculated = $result['montant'] ?? 0;
        $expected = $params['expected'];

        echo "\nRésultat:\n";
        echo "  Calculé: " . number_format($calculated, 2, '.', '') . " €\n";

        if ($expected !== null) {
            echo "  Attendu: " . number_format($expected, 2, '.', '') . " €\n";
            $diff = $calculated - $expected;
            echo "  Différence: " . number_format($diff, 2, '.', '') . " €\n";

            if (abs($diff) < 0.01) {
                echo "  ✓ TEST RÉUSSI\n";
            } else {
                echo "  ✗ TEST ÉCHOUÉ\n";

                // Afficher les détails du calcul
                echo "\nDétails du calcul:\n";
                echo "  Total jours: " . ($result['nb_jours'] ?? 0) . "\n";
                echo "  Jours cumulés: " . ($result['total_cumul_days'] ?? 0) . "\n";
                echo "  Age: " . ($result['age'] ?? 'N/A') . "\n";

                if (isset($result['payment_details']) && is_array($result['payment_details'])) {
                    echo "\nDétails de paiement:\n";
                    foreach ($result['payment_details'] as $pd) {
                        if (isset($pd['payable_days']) && $pd['payable_days'] > 0) {
                            $arret_id = $pd['arret_id'] ?? $pd['arret_index'] ?? 'N/A';
                            echo "    - Arrêt #{$arret_id}: ";
                            echo "{$pd['payable_days']} jours payables";
                            if (isset($pd['rate'])) {
                                echo ", Taux: {$pd['taux']}, Rate: {$pd['rate']}€";
                            }
                            echo "\n";
                        }
                    }
                }
            }
        } else {
            echo "  (Pas de résultat attendu)\n";
        }

    } catch (Exception $e) {
        echo "ERREUR: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

echo str_repeat("=", 80) . "\n";
echo "FIN DES TESTS\n";
echo str_repeat("=", 80) . "\n";
