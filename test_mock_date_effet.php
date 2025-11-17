<?php

require_once __DIR__ . '/Services/DateCalculationInterface.php';
require_once __DIR__ . '/Services/DateService.php';
require_once __DIR__ . '/Services/ArretService.php';

use App\IJCalculator\Services\ArretService;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║       TEST calculateDateEffetForArrets WITH MOCK FILES       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$arretService = new ArretService();

// Test with mock2.json
echo "═══════════════════════════════════════════════════════════════\n";
echo "📋 TESTING WITH mock2.json\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$arrets = json_decode(file_get_contents(__DIR__ . '/mock2.json'), true);

echo "INPUT: " . count($arrets) . " arrêts\n";
echo "Birth date: " . $arrets[0]['date_naissance'] . "\n\n";

// Calculate date-effet
$result = $arretService->calculateDateEffetForArrets(
    $arrets,
    $arrets[0]['date_naissance'],
    0
);

echo "RESULTS:\n\n";
foreach ($result as $i => $arret) {
    echo "Arrêt #" . ($i + 1) . ":\n";
    echo "  Dates: {$arret['arret-from-line']} → {$arret['arret-to-line']}\n";
    echo "  Original date_deb_droit: " . ($arret['date_deb_droit'] ?? 'NOT SET') . "\n";
    echo "  Calculated date-effet: " . ($arret['date-effet'] ?? 'NOT SET') . "\n";
    echo "  Décompte days: " . ($arret['decompte_days'] ?? 'NOT SET') . "\n";
    echo "  Is rechute: " . (isset($arret['is_rechute']) ? ($arret['is_rechute'] ? 'YES' : 'NO') : 'NOT SET') . "\n";
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "📋 TESTING WITH mock.json\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$arrets = json_decode(file_get_contents(__DIR__ . '/mock.json'), true);

echo "INPUT: " . count($arrets) . " arrêts\n";
echo "Birth date: " . $arrets[0]['date_naissance'] . "\n\n";

// Calculate date-effet
$result = $arretService->calculateDateEffetForArrets(
    $arrets,
    $arrets[0]['date_naissance'],
    0
);

echo "RESULTS:\n\n";
foreach ($result as $i => $arret) {
    echo "Arrêt #" . ($i + 1) . ":\n";
    echo "  Dates: {$arret['arret-from-line']} → {$arret['arret-to-line']}\n";
    echo "  Original date_deb_droit: " . ($arret['date_deb_droit'] ?? 'NOT SET') . "\n";
    echo "  Calculated date-effet: " . ($arret['date-effet'] ?? 'NOT SET') . "\n";
    echo "  Décompte days: " . ($arret['decompte_days'] ?? 'NOT SET') . "\n";
    echo "  Is rechute: " . (isset($arret['is_rechute']) ? ($arret['is_rechute'] ? 'YES' : 'NO') : 'NOT SET') . "\n";
    echo "\n";
}
