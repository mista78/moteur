<?php
/**
 * Test: Display which arret is a rechute of
 */

require_once 'IJCalculator.php';

echo "===========================================\n";
echo "Test: Rechute Display - Show Source Arret\n";
echo "===========================================\n\n";

$calculator = new IJCalculator('taux.csv');

// Scenario: Multiple arrets with different relationships
$data = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1980-01-01',
    'current_date' => '2024-12-31',
    'attestation_date' => '2024-12-31',
    'last_payment_date' => null,
    'affiliation_date' => '2010-01-01',
    'nb_trimestres' => 56,
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000,
    'arrets' => [
        // Arret 1: First arret, 101 days - will open rights
        [
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-04-11',
            'rechute-line' => null,
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2024-01-05'
        ],
        // Arret 2: 30 days after arret 1 - should be rechute of arret 1
        [
            'arret-from-line' => '2024-05-11',
            'arret-to-line' => '2024-06-10',
            'rechute-line' => null,
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2024-05-15'
        ],
        // Arret 3: 20 days after arret 2 - should also be rechute of arret 1
        [
            'arret-from-line' => '2024-06-30',
            'arret-to-line' => '2024-07-30',
            'rechute-line' => null,
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2024-07-05'
        ]
    ]
];

echo "Scenario: Multiple rechutes from same source arret\n";
echo "- Arret 1: 101 days (opens rights)\n";
echo "- Arret 2: 30 days after arret 1\n";
echo "- Arret 3: 20 days after arret 2\n\n";

$result = $calculator->calculateAmount($data);

echo "Results:\n";
echo "========\n\n";

foreach ($result['arrets'] as $index => $arret) {
    $arretNum = $index + 1;
    echo "Arret #{$arretNum}:\n";
    echo "  - Period: " . $arret['arret-from-line'] . " to " . $arret['arret-to-line'] . "\n";
    echo "  - Duration: " . (isset($arret['arret_diff']) ? $arret['arret_diff'] : 'N/A') . " days\n";
    echo "  - Date effet: " . ($arret['date-effet'] ?? 'N/A') . "\n";
    echo "  - Is rechute: " . (isset($arret['is_rechute']) ? ($arret['is_rechute'] ? 'YES' : 'NO') : 'Not set') . "\n";

    if (isset($arret['rechute_of_arret_index']) && $arret['rechute_of_arret_index'] !== null) {
        $sourceArretNum = $arret['rechute_of_arret_index'] + 1;
        echo "  - ⭐ Rechute of arret: #" . $sourceArretNum . "\n";
        echo "  - Frontend display: 🔄 Rechute de l'arrêt #{$sourceArretNum}\n";
    }

    echo "\n";
}

echo "===========================================\n";

// Second scenario: Accumulation then rechute
echo "\nScenario 2: Accumulation then rechute\n";
echo "======================================\n\n";

$data2 = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1980-01-01',
    'current_date' => '2024-12-31',
    'attestation_date' => '2024-12-31',
    'last_payment_date' => null,
    'affiliation_date' => '2010-01-01',
    'nb_trimestres' => 56,
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000,
    'arrets' => [
        // Arret 1: 46 days - not enough for date-effet
        [
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-02-16',
            'rechute-line' => null,
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2024-01-05'
        ],
        // Arret 2: 50 days - accumulates, reaches 90 days total
        [
            'arret-from-line' => '2024-03-01',
            'arret-to-line' => '2024-04-20',
            'rechute-line' => null,
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2024-03-05'
        ],
        // Arret 3: Should be rechute of arret 2 (the one that opened rights)
        [
            'arret-from-line' => '2024-05-15',
            'arret-to-line' => '2024-06-15',
            'rechute-line' => null,
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2024-05-20'
        ]
    ]
];

echo "- Arret 1: 46 days (no date-effet)\n";
echo "- Arret 2: 51 days (accumulates to 97 days, opens rights)\n";
echo "- Arret 3: 25 days after arret 2\n\n";

$result2 = $calculator->calculateAmount($data2);

echo "Results:\n";
echo "========\n\n";

foreach ($result2['arrets'] as $index => $arret) {
    $arretNum = $index + 1;
    echo "Arret #{$arretNum}:\n";
    echo "  - Period: " . $arret['arret-from-line'] . " to " . $arret['arret-to-line'] . "\n";
    echo "  - Duration: " . (isset($arret['arret_diff']) ? $arret['arret_diff'] : 'N/A') . " days\n";
    echo "  - Date effet: " . ($arret['date-effet'] ?? 'N/A') . "\n";
    echo "  - Is rechute: " . (isset($arret['is_rechute']) ? ($arret['is_rechute'] ? 'YES' : 'NO') : 'Not set') . "\n";

    if (isset($arret['rechute_of_arret_index']) && $arret['rechute_of_arret_index'] !== null) {
        $sourceArretNum = $arret['rechute_of_arret_index'] + 1;
        echo "  - ⭐ Rechute of arret: #" . $sourceArretNum . "\n";
        echo "  - Frontend display: 🔄 Rechute de l'arrêt #{$sourceArretNum}\n";
    } elseif (isset($arret['is_rechute']) && $arret['is_rechute'] === false && $index > 0) {
        echo "  - Frontend display: 🆕 Nouvelle pathologie\n";
    } elseif ($index === 0) {
        echo "  - Frontend display: 1ère pathologie\n";
    }

    echo "\n";
}

echo "===========================================\n";
echo "✓ Test Complete\n";
echo "===========================================\n";
