<?php

require_once __DIR__ . '/Services/DateCalculationInterface.php';
require_once __DIR__ . '/Services/DateService.php';
require_once __DIR__ . '/Services/ArretService.php';

use App\IJCalculator\Services\ArretService;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║              DEBUG DATE-EFFET CALCULATION                    ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$arretService = new ArretService();

// Test with mock2.json
$arrets = json_decode(file_get_contents(__DIR__ . '/mock2.json'), true);

echo "RAW INPUT from mock2.json:\n";
echo "Total arrêts: " . count($arrets) . "\n\n";

// Show first 3 arrêts raw data
for ($i = 0; $i < min(3, count($arrets)); $i++) {
    echo "Arrêt #" . ($i + 1) . " RAW DATA:\n";
    echo "  arret-from-line: " . ($arrets[$i]['arret-from-line'] ?? 'NOT SET') . "\n";
    echo "  arret-to-line: " . ($arrets[$i]['arret-to-line'] ?? 'NOT SET') . "\n";
    echo "  date_deb_droit: " . ($arrets[$i]['date_deb_droit'] ?? 'NOT SET') . "\n";
    echo "  valid_med_controleur: " . ($arrets[$i]['valid_med_controleur'] ?? 'NOT SET') . "\n";
    echo "  dt-line: " . ($arrets[$i]['dt-line'] ?? 'NOT SET') . "\n";
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "CALLING calculateDateEffetForArrets\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Calculate date-effet
$result = $arretService->calculateDateEffetForArrets(
    $arrets,
    $arrets[0]['date_naissance'],
    0
);

echo "RESULTS:\n\n";
for ($i = 0; $i < min(3, count($result)); $i++) {
    echo "Arrêt #" . ($i + 1) . " RESULT:\n";
    echo "  arret-from-line: " . ($result[$i]['arret-from-line'] ?? 'NOT SET') . "\n";
    echo "  arret-to-line: " . ($result[$i]['arret-to-line'] ?? 'NOT SET') . "\n";
    echo "  Original date_deb_droit: " . ($result[$i]['date_deb_droit'] ?? 'NOT SET') . "\n";
    echo "  Calculated date-effet: " . (isset($result[$i]['date-effet']) ? ($result[$i]['date-effet'] ?: 'NULL/EMPTY') : 'NOT SET') . "\n";
    echo "  decompte_days: " . ($result[$i]['decompte_days'] ?? 'NOT SET') . "\n";
    echo "  arret_diff: " . ($result[$i]['arret_diff'] ?? 'NOT SET') . "\n";
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "EXPECTED vs ACTUAL\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Arrêt #1: 43 days (2021-07-19 → 2021-08-30)\n";
echo "  Expected: NULL (only 43 days, < 90)\n";
echo "  Actual: " . ($result[0]['date-effet'] ?: 'NULL') . "\n\n";

echo "Arrêt #2: 17 days (2021-12-17 → 2022-01-02)\n";
echo "  Cumulative: 43 + 17 = 60 days (< 90)\n";
echo "  Expected: NULL (only 60 cumulative days)\n";
echo "  Actual: " . ($result[1]['date-effet'] ?: 'NULL') . "\n\n";

echo "Arrêt #3: 18 days (2022-10-27 → 2022-11-13)\n";
echo "  Cumulative: 43 + 17 + 18 = 78 days (< 90)\n";
echo "  Expected: NULL (only 78 cumulative days)\n";
echo "  Actual: " . ($result[2]['date-effet'] ?: 'NULL') . "\n\n";

echo "Arrêt #4: 31 days (2022-11-24 → 2022-12-24)\n";
echo "  Cumulative: 78 + 31 = 109 days (> 90)\n";
echo "  Expected: Should calculate date-effet (90th day from start)\n";
echo "  Actual: " . ($result[3]['date-effet'] ?? 'NOT SET') . "\n";
echo "  Original date_deb_droit: " . ($arrets[3]['date_deb_droit'] ?? 'NOT SET') . "\n\n";
