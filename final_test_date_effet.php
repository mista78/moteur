<?php

require_once __DIR__ . '/Services/DateCalculationInterface.php';
require_once __DIR__ . '/Services/DateService.php';
require_once __DIR__ . '/Services/ArretService.php';

use App\IJCalculator\Services\ArretService;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     FINAL TEST: calculateDateEffetForArrets WITH MOCKS      ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$arretService = new ArretService();

// Test with mock2.json
$arrets = json_decode(file_get_contents(__DIR__ . '/mock2.json'), true);

echo "Testing with mock2.json (6 arrêts from 2021-2024)\n";
echo "Birth date: {$arrets[0]['date_naissance']}\n\n";

$result = $arretService->calculateDateEffetForArrets(
    $arrets,
    $arrets[0]['date_naissance'],
    0
);

echo "═══════════════════════════════════════════════════════════════\n";
echo "RESULTS: Date-Effet Calculation\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$cumulativeDays = 0;
foreach ($result as $i => $arret) {
    $cumulativeDays += $arret['arret_diff'];

    echo "Arrêt #" . ($i + 1) . ":\n";
    echo "  Period: {$arret['arret-from-line']} → {$arret['arret-to-line']}\n";
    echo "  Duration: {$arret['arret_diff']} days\n";
    echo "  Cumulative: $cumulativeDays days\n";

    // Check if date-effet key exists
    if (array_key_exists('date-effet', $arret)) {
        if ($arret['date-effet'] === null) {
            echo "  ✅ Date-effet: NULL (rights not yet opened - need 90 cumulative days)\n";
        } else {
            echo "  ✅ Date-effet: {$arret['date-effet']} (rights opened!)\n";
        }
    } else {
        echo "  ❌ Date-effet: KEY MISSING\n";
    }

    echo "  Décompte: {$arret['decompte_days']} days\n";
    echo "  Is Rechute: " . ($arret['is_rechute'] ? 'YES' : 'NO') . "\n";
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "VERIFICATION\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Expected behavior:\n";
echo "  • Arrêt #1-3: date-effet = NULL (cumulative < 90 days)\n";
echo "  • Arrêt #4: date-effet calculated (cumulative > 90 days)\n";
echo "  • Arrêt #5-6: date-effet calculated based on rechute rules\n\n";

$passed = 0;
$failed = 0;

// Check arrêt #1-3 should have NULL
for ($i = 0; $i < 3; $i++) {
    if (array_key_exists('date-effet', $result[$i]) && $result[$i]['date-effet'] === null) {
        echo "✅ Arrêt #" . ($i + 1) . ": Correct (NULL before 90 days)\n";
        $passed++;
    } else {
        echo "❌ Arrêt #" . ($i + 1) . ": FAIL (should be NULL)\n";
        $failed++;
    }
}

// Check arrêt #4 should have date calculated
if (array_key_exists('date-effet', $result[3]) && $result[3]['date-effet'] !== null) {
    echo "✅ Arrêt #4: Correct (date-effet calculated = {$result[3]['date-effet']})\n";
    $passed++;
} else {
    echo "❌ Arrêt #4: FAIL (should have date-effet)\n";
    $failed++;
}

// Check decompte_days exists for all
$allHaveDecompte = true;
foreach ($result as $i => $arret) {
    if (!isset($arret['decompte_days'])) {
        echo "❌ Arrêt #" . ($i + 1) . ": Missing decompte_days\n";
        $allHaveDecompte = false;
        $failed++;
    }
}
if ($allHaveDecompte) {
    echo "✅ All arrêts have decompte_days calculated\n";
    $passed++;
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Tests Passed: $passed\n";
echo "Tests Failed: $failed\n\n";

if ($failed === 0) {
    echo "🎉 ✅ ALL CHECKS PASSED - calculateDateEffetForArrets WORKS CORRECTLY!\n";
} else {
    echo "❌ SOME CHECKS FAILED\n";
}
