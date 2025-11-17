<?php

require_once __DIR__ . '/IJCalculator.php';
require_once __DIR__ . '/Tools/Tools.php';

use App\IJCalculator\IJCalculator;
use App\Tools\Tools;

$calculator = new IJCalculator(__DIR__ . '/taux.csv');

// Test data with multiple consecutive arrêts
$mockData = [
    [
        'arret-from-line' => '2024-01-02',
        'arret-to-line' => '2024-01-10',
        'valid_med_controleur' => 1,
        'rechute-line' => 0,
        'dt-line' => 1,
        'declaration-date-line' => '2024-01-02'
    ],
    [
        'arret-from-line' => '2024-01-11',  // Consecutive
        'arret-to-line' => '2024-01-20',
        'valid_med_controleur' => 1,
        'rechute-line' => 0,
        'dt-line' => 1,
        'declaration-date-line' => '2024-01-11'
    ],
    [
        'arret-from-line' => '2024-02-05',  // NOT consecutive (gap)
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

echo "=== ORIGINAL ARRETS (input) ===\n";
echo "Count: " . count($mockData) . "\n";
foreach ($mockData as $i => $arret) {
    echo "[$i] {$arret['arret-from-line']} to {$arret['arret-to-line']}\n";
}

echo "\n=== ARRETS IN RESPONSE (processed) ===\n";
echo "Count: " . count($result['arrets']) . "\n";
foreach ($result['arrets'] as $i => $arret) {
    $from = $arret['arret-from-line'] ?? 'N/A';
    $to = $arret['arret-to-line'] ?? 'N/A';
    echo "[$i] {$from} to {$to}\n";
}

echo "\n=== ARRETS_MERGED (new key for front-end) ===\n";
if (isset($result['arrets_merged'])) {
    echo "Count: " . count($result['arrets_merged']) . "\n";
    foreach ($result['arrets_merged'] as $i => $arret) {
        $from = $arret['arret-from-line'] ?? 'N/A';
        $to = $arret['arret-to-line'] ?? 'N/A';
        echo "[$i] {$from} to {$to}\n";
    }
} else {
    echo "ERROR: arrets_merged key not found in response!\n";
}

echo "\n=== RESULT ===\n";
echo "✅ Front-end now has access to:\n";
echo "  - 'arrets': Original/processed arrêts (with date-effet, etc.)\n";
echo "  - 'arrets_merged': Merged consecutive arrêts list\n";
echo "\nMontant: {$result['montant']}€\n";
echo "Nb jours: {$result['nb_jours']}\n";
