<?php

require_once __DIR__ . '/IJCalculator.php';

use App\IJCalculator\IJCalculator;

$calculator = new IJCalculator(__DIR__ . '/taux.csv');

// Create test data with consecutive arrêts that will be merged
$mockData = [
    [
        'arret-from-line' => '2024-01-02',  // Tuesday
        'arret-to-line' => '2024-01-10',    // Wednesday
        'valid_med_controleur' => 1,
        'rechute-line' => 0,
        'dt-line' => 1,
        'declaration-date-line' => '2024-01-02'
    ],
    [
        'arret-from-line' => '2024-01-11',  // Thursday - CONSECUTIVE
        'arret-to-line' => '2024-01-17',    // Wednesday
        'valid_med_controleur' => 1,
        'rechute-line' => 0,
        'dt-line' => 1,
        'declaration-date-line' => '2024-01-11'
    ],
    [
        'arret-from-line' => '2024-01-18',  // Thursday - CONSECUTIVE
        'arret-to-line' => '2024-01-25',    // Thursday
        'valid_med_controleur' => 1,
        'rechute-line' => 0,
        'dt-line' => 1,
        'declaration-date-line' => '2024-01-18'
    ],
    [
        'arret-from-line' => '2024-02-05',  // Monday - NOT consecutive (gap)
        'arret-to-line' => '2024-12-31',
        'valid_med_controleur' => 1,
        'rechute-line' => 0,
        'dt-line' => 1,
        'declaration-date-line' => '2024-02-05'
    ]
];

$requestData = [
    'arrets' => $mockData,
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1980-01-01',
    'current_date' => '2024-12-31',
    'attestation_date' => '2024-12-31',
    'last_payment_date' => null,
    'affiliation_date' => '2010-01-01',
    'nb_trimestres' => 60,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0
];

$result = $calculator->calculateAmount($requestData);

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           MERGE FLAGS IN API RESPONSE                        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "📋 ORIGINAL INPUT: " . count($mockData) . " arrêts\n";
echo "   [0] 2024-01-02 to 2024-01-10\n";
echo "   [1] 2024-01-11 to 2024-01-17 (consecutive)\n";
echo "   [2] 2024-01-18 to 2024-01-25 (consecutive)\n";
echo "   [3] 2024-02-05 to 2024-12-31 (gap)\n\n";

echo "📦 RESPONSE KEYS:\n";
foreach (array_keys($result) as $key) {
    echo "   - $key\n";
}

echo "\n🔗 ARRETS_MERGED: " . count($result['arrets_merged']) . " arrêts (merged)\n\n";

foreach ($result['arrets_merged'] as $i => $arret) {
    $from = $arret['arret-from-line'];
    $to = $arret['arret-to-line'];

    echo "[$i] $from → $to\n";

    // Check for prolongation flags
    if (isset($arret['has_prolongations']) && $arret['has_prolongations']) {
        echo "    ✅ has_prolongations: true\n";
        echo "    📊 prolongation_count: {$arret['prolongation_count']}\n";

        if (isset($arret['merged_arrets']) && !empty($arret['merged_arrets'])) {
            echo "    📝 merged_arrets (" . count($arret['merged_arrets']) . " items):\n";
            foreach ($arret['merged_arrets'] as $merged) {
                echo "       • Original index {$merged['original_index']}: {$merged['from']} → {$merged['to']}\n";
            }
        }
    } else {
        echo "    ⚪ Standalone arrêt (no prolongations)\n";
    }

    // Show calculation data if available
    if (isset($arret['date-effet'])) {
        echo "    💰 date-effet: {$arret['date-effet']}\n";
    }

    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "📊 CALCULATION RESULT:\n";
echo "   Montant: {$result['montant']}€\n";
echo "   Nb jours: {$result['nb_jours']}\n";
echo "   Age: {$result['age']}\n";
echo "   Total cumul days: {$result['total_cumul_days']}\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "✅ FRONT-END CAN NOW:\n";
echo "   1. Check 'has_prolongations' flag to know if arrêt was merged\n";
echo "   2. See 'prolongation_count' to know how many were merged\n";
echo "   3. Access 'merged_arrets' array for detailed merge info\n";
echo "   4. Display merged arrêts differently in UI (badges, tooltips, etc.)\n";
