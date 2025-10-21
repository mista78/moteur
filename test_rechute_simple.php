<?php

/**
 * Simple Rechute Test - to understand current behavior
 */

require_once 'IJCalculator.php';

echo "\n=== Simple Rechute Test ===\n\n";

$calculator = new IJCalculator('taux.csv');

// Test 1: Basic rechute scenario
echo "Test 1: Basic Rechute\n";
echo "---------------------\n";

$data = [
    'arrets' => [
        // First arrêt: 100 days
        [
            'arret-from-line' => '2023-01-01',
            'arret-to-line' => '2023-04-10',  // 100 days
            'rechute-line' => 0,
            'dt-line' => 0,
            'gpm-member-line' => 0,
            'declaration-date-line' => '2023-01-01'
        ],
        // Rechute after 2 months: should start at 15 days (not 91)
        [
            'arret-from-line' => '2023-06-15',
            'arret-to-line' => '2023-07-15',  // 31 days
            'rechute-line' => 1,
            'dt-line' => 0,
            'gpm-member-line' => 0,
            'declaration-date-line' => '2023-06-15'
        ]
    ],
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1980-05-15',
    'current_date' => '2024-01-15',
    'attestation_date' => '2024-01-15',
    'nb_trimestres' => 20,
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000
];

$result = $calculator->calculateAmount($data);

echo "Expected behavior:\n";
echo "  - First arrêt: 100 days total (payment starts after 90 days = 10 payable days)\n";
echo "  - Rechute: 31 days total (payment starts after 15 cumulative days from first arrêt)\n";
echo "  - Total cumulative: 131 days (100 + 31)\n";
echo "  - Total payable: ~121 days (131 - 10 days before first payment started)\n\n";

echo "Actual results:\n";
echo "  - Total cumulative days: " . $result['total_cumul_days'] . "\n";
echo "  - Payable days: " . $result['nb_jours'] . "\n";
echo "  - Amount: " . number_format($result['montant'], 2) . "€\n\n";

echo "Arrêts details:\n";
foreach ($result['arrets'] as $i => $arret) {
    echo "  Arrêt " . ($i+1) . ":\n";
    echo "    From: " . $arret['arret-from-line'] . " to " . $arret['arret-to-line'] . "\n";
    echo "    Days: " . $arret['arret_diff'] . "\n";
    echo "    Rechute: " . ($arret['rechute-line'] ? 'Yes' : 'No') . "\n";
    echo "    Payment start: " . ($arret['payment_start'] ?? 'N/A') . "\n";
    echo "    Date effet: " . ($arret['date-effet'] ?? 'N/A') . "\n";
    echo "\n";
}

// Test 2: Check if rechute flag is being processed
echo "\n\nTest 2: Check Rechute Flag Processing\n";
echo "---------------------------------------\n";

$data2 = [
    'arrets' => [
        [
            'arret-from-line' => '2023-01-01',
            'arret-to-line' => '2023-01-31',  // 31 days
            'rechute-line' => 0,
            'dt-line' => 0,
            'gpm-member-line' => 0,
            'declaration-date-line' => '2023-01-01'
        ],
        [
            'arret-from-line' => '2023-03-01',  // Not consecutive, < 1 year
            'arret-to-line' => '2023-03-20',  // 20 days
            'rechute-line' => 1,  // Marked as rechute
            'dt-line' => 0,
            'gpm-member-line' => 0,
            'declaration-date-line' => '2023-03-01'
        ]
    ],
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1980-01-01',
    'current_date' => '2024-01-15',
    'attestation_date' => '2024-01-15',
    'nb_trimestres' => 20,
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000
];

$result2 = $calculator->calculateAmount($data2);

echo "For rechute with rechute-line = 1:\n";
echo "  - Arrêt 1: 31 days\n";
echo "  - Arrêt 2 (rechute): 20 days\n";
echo "  - Expected cumulative: 51 days\n";
echo "  - Expected payment: Should start after 15 days for rechute\n\n";

echo "Actual results:\n";
echo "  - Total cumulative days: " . $result2['total_cumul_days'] . "\n";
echo "  - Payable days: " . $result2['nb_jours'] . "\n\n";

echo "Arrêts:\n";
foreach ($result2['arrets'] as $i => $arret) {
    echo "  Arrêt " . ($i+1) . ":\n";
    echo "    Days: " . $arret['arret_diff'] . "\n";
    echo "    Rechute flag: " . $arret['rechute-line'] . "\n";
    echo "    Payment start: " . ($arret['payment_start'] ?? 'N/A') . "\n";
    echo "\n";
}

echo "\n=== Analysis ===\n";
echo "If total_cumul_days doesn't match expected:\n";
echo "  - Check if rechute arrêts are being merged/consolidated incorrectly\n";
echo "  - Check if rechute-line flag is being honored\n";
echo "  - Look for logic in DateService::mergeProlongations() or calculateDateEffet()\n\n";
