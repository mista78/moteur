<?php

require_once __DIR__ . '/IJCalculator.php';
require_once __DIR__ . '/Tools/Tools.php';

use App\IJCalculator\IJCalculator;
use App\Tools\Tools;

$calculator = new IJCalculator(__DIR__ . '/taux.csv');

// Load mock2.json
$mockData = json_decode(file_get_contents(__DIR__ . '/mock2.json'), true);

echo "=== BEFORE TRANSFORMATION ===\n";
echo "Count: " . count($mockData) . "\n";
foreach ($mockData as $i => $arret) {
    $from = $arret['date_start'] ?? $arret['debutArret'] ?? 'N/A';
    $to = $arret['date_end'] ?? $arret['finArret'] ?? 'N/A';
    echo "[$i] {$from} to {$to}\n";
}

// Transform keys using Tools
$mockData = Tools::renommerCles($mockData, Tools::$correspondance);

echo "\n=== AFTER KEY TRANSFORMATION ===\n";
echo "Count: " . count($mockData) . "\n";
foreach ($mockData as $i => $arret) {
    echo "[$i] {$arret['arret-from-line']} to {$arret['arret-to-line']}\n";
}

$requestData = [
    'arrets' => $mockData,
    'statut' => 'M',
    'classe' => 'C',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1958-06-03',
    'current_date' => date("Y-m-d"),
    'attestation_date' => '2024-06-12',
    'last_payment_date' => null,
    'affiliation_date' => "1991-07-01",
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0
];

$result = $calculator->calculateAmount($requestData);

echo "\n=== RESPONSE STRUCTURE ===\n";
echo "Keys in response:\n";
foreach (array_keys($result) as $key) {
    if ($key === 'arrets' || $key === 'arrets_merged') {
        echo "  ✅ $key: " . count($result[$key]) . " items\n";
    } else {
        echo "  - $key\n";
    }
}

echo "\n=== ARRETS_MERGED (for front-end display) ===\n";
echo "Count: " . count($result['arrets_merged']) . "\n";
foreach ($result['arrets_merged'] as $i => $arret) {
    echo "[$i] {$arret['arret-from-line']} to {$arret['arret-to-line']}\n";

    // Show merge flags
    if (isset($arret['has_prolongations']) && $arret['has_prolongations']) {
        echo "     ✅ Has {$arret['prolongation_count']} prolongation(s) merged\n";
        if (isset($arret['merged_arrets'])) {
            foreach ($arret['merged_arrets'] as $merged) {
                echo "        - Merged: {$merged['from']} to {$merged['to']}\n";
            }
        }
    }
}

echo "\n=== CALCULATION RESULT ===\n";
echo "Montant: {$result['montant']}€ (expected: 20032.88€)\n";
echo "Nb jours: {$result['nb_jours']} (expected: 135)\n";
echo "Age: {$result['age']}\n";
echo "Total cumul days: {$result['total_cumul_days']}\n";

echo "\n✅ SUCCESS: Front-end can now access both:\n";
echo "  - 'arrets': Processed arrêts with all calculation data\n";
echo "  - 'arrets_merged': Simplified merged list for display\n";
