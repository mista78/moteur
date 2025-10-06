<?php

require_once 'IJCalculator.php';

function debugMock($mockFile, $params) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "DEBUG: $mockFile\n";
    echo str_repeat("=", 80) . "\n";

    $mockData = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $mockFile), true);

    if (!$mockData) {
        echo "ERROR: Cannot load $mockFile\n";
        return;
    }

    echo "\n=== RAW MOCK DATA ===\n";
    print_r($mockData);

    $calculator = new IJCalculator(__DIR__ . DIRECTORY_SEPARATOR . 'taux.csv');

    echo "\n=== CALCULATION PARAMETERS ===\n";
    echo "Statut: {$params['statut']}\n";
    echo "Classe: {$params['classe']}\n";
    echo "Option: {$params['option']}%\n";
    echo "Birth date: {$params['birth_date']}\n";
    echo "Current date: {$params['current_date']}\n";
    echo "Attestation date: " . ($params['attestation_date'] ?? 'NULL') . "\n";
    echo "Last payment date: " . ($params['last_payment_date'] ?? 'NULL') . "\n";
    echo "Nb trimestres: {$params['nb_trimestres']}\n";
    echo "Previous cumul days: {$params['previous_cumul_days']}\n";

    // Calculate age
    $age = $calculator->calculateAge($params['current_date'], $params['birth_date']);
    echo "Age at current_date: $age\n";

    // Prepare request data
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

    // Step 1: Calculate date effet
    echo "\n=== STEP 1: DATE EFFET CALCULATION ===\n";
    $arretsWithDateEffet = $calculator->calculateDateEffet($mockData, $params['birth_date'], $params['previous_cumul_days']);
    foreach ($arretsWithDateEffet as $index => $arret) {
        echo "Arret #$index:\n";
        echo "  From: {$arret['arret-from-line']}\n";
        echo "  To: {$arret['arret-to-line']}\n";
        echo "  Date effet: " . ($arret['date-effet'] ?? 'NONE') . "\n";
        echo "  Duration: {$arret['arret_diff']} days\n";
    }

    // Step 2: Calculate payable days
    echo "\n=== STEP 2: PAYABLE DAYS CALCULATION ===\n";
    $paymentResult = $calculator->calculatePayableDays(
        $arretsWithDateEffet,
        $params['attestation_date'],
        $params['last_payment_date'],
        $params['current_date']
    );

    echo "Total payable days: {$paymentResult['total_days']}\n\n";
    echo "Payment details:\n";
    foreach ($paymentResult['payment_details'] as $index => $detail) {
        echo "  Arret #$index:\n";
        echo "    From: {$detail['arret_from']} To: {$detail['arret_to']}\n";
        echo "    Date effet: " . ($detail['date_effet'] ?? 'NULL') . "\n";
        echo "    Attestation date: " . ($detail['attestation_date'] ?? 'NULL') . "\n";
        echo "    Attestation extended: " . ($detail['attestation_date_extended'] ?? 'NULL') . "\n";
        echo "    Payment start: " . ($detail['payment_start'] ?? 'NULL') . "\n";
        echo "    Payment end: " . ($detail['payment_end'] ?? 'NULL') . "\n";
        echo "    Payable days: {$detail['payable_days']}\n";
        echo "    Reason: {$detail['reason']}\n";
    }

    // Step 3: Full calculation
    echo "\n=== STEP 3: FULL CALCULATION ===\n";
    try {
        $result = $calculator->calculateAmount($requestData);

        echo "Result:\n";
        echo "  Nb jours: {$result['nb_jours']}\n";
        echo "  Montant: " . number_format($result['montant'], 2, '.', '') . " €\n";
        echo "  Total cumul days: {$result['total_cumul_days']}\n";
        echo "  Age: {$result['age']}\n";
        echo "  Nb trimestres: {$result['nb_trimestres']}\n";

        if (isset($result['payment_details']) && is_array($result['payment_details'])) {
            echo "\n  Payment details:\n";
            foreach ($result['payment_details'] as $pd) {
                if (isset($pd['payable_days']) && $pd['payable_days'] > 0) {
                    $arret_id = $pd['arret_id'] ?? $pd['arret_index'] ?? 'N/A';
                    echo "    Arret #$arret_id: {$pd['payable_days']} days\n";
                    if (isset($pd['rate_breakdown'])) {
                        foreach ($pd['rate_breakdown'] as $rb) {
                            echo "      Period {$rb['period']}: {$rb['days']} days × {$rb['rate']}€ (taux {$rb['taux']})\n";
                        }
                    }
                }
            }
        }

    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

// Debug failing tests
$testCases = [
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
    'mock14.json' => [
        'expected' => 19215.36,
        'statut' => 'M',
        'classe' => 'C',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1985-07-27',
        'current_date' => '2024-10-25',
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => '2017-07-01',
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
];

foreach ($testCases as $mockFile => $params) {
    debugMock($mockFile, $params);
}
