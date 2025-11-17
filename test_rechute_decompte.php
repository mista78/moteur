<?php

require_once __DIR__ . '/Services/DateCalculationInterface.php';
require_once __DIR__ . '/Services/DateService.php';
require_once __DIR__ . '/Services/ArretService.php';

use App\IJCalculator\Services\ArretService;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║       TEST DECOMPTE WITH RECHUTE (mock.json)                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$arretService = new ArretService();

// Test with mock.json (has rechute)
$arrets = json_decode(file_get_contents(__DIR__ . '/mock.json'), true);

echo "Testing with mock.json\n";
echo "Birth date: {$arrets[0]['date_naissance']}\n\n";

$result = $arretService->calculateDateEffetForArrets(
    $arrets,
    $arrets[0]['date_naissance'],
    0
);

echo "═══════════════════════════════════════════════════════════════\n";
echo "RESULTS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

foreach ($result as $i => $arret) {
    echo "Arrêt #" . ($i + 1) . ":\n";
    echo "  Period: {$arret['arret-from-line']} → {$arret['arret-to-line']}\n";
    echo "  Duration: {$arret['arret_diff']} days\n";
    echo "  Date-effet: " . ($arret['date-effet'] ?: 'NULL') . "\n";
    echo "  Is Rechute: " . ($arret['is_rechute'] ? 'YES' : 'NO') . "\n";
    echo "  rechute-line: " . ($arret['rechute-line'] ?? 'NOT SET') . "\n";
    echo "  Décompte days: {$arret['decompte_days']}\n";

    if ($arret['is_rechute'] && $arret['decompte_days'] == 0) {
        echo "  ✅ CORRECT: Rechute has décompte = 0\n";
    } elseif (!$arret['is_rechute'] && $arret['decompte_days'] > 0) {
        echo "  ✅ CORRECT: Non-rechute has décompte calculated\n";
    } elseif ($arret['is_rechute'] && $arret['decompte_days'] != 0) {
        echo "  ❌ ERROR: Rechute should have décompte = 0!\n";
    }

    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "VERIFICATION\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Expected:\n";
echo "  • Arrêt #1 (initial): decompte_days = days before date-effet\n";
echo "  • Arrêt #2 (rechute): decompte_days = 0\n\n";

if ($result[0]['is_rechute'] == false && $result[0]['decompte_days'] > 0) {
    echo "✅ Arrêt #1 (non-rechute): decompte_days = {$result[0]['decompte_days']} (correct)\n";
} else {
    echo "❌ Arrêt #1 failed\n";
}

if ($result[1]['is_rechute'] == true && $result[1]['decompte_days'] == 0) {
    echo "✅ Arrêt #2 (rechute): decompte_days = 0 (correct)\n";
} else {
    echo "❌ Arrêt #2 (rechute): decompte_days = {$result[1]['decompte_days']} (should be 0)\n";
}

echo "\n";
