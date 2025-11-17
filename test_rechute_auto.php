<?php

require_once __DIR__ . '/Services/DateCalculationInterface.php';
require_once __DIR__ . '/Services/DateService.php';
require_once __DIR__ . '/Services/ArretService.php';

use App\IJCalculator\Services\ArretService;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     TEST: Rechute Auto-Calculation (Ignore Input Data)      ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$arretService = new ArretService();

echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 1: rechute-line = 0 but should be auto-detected as rechute\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$arrets1 = [
    [
        'arret-from-line' => '2024-01-01',
        'arret-to-line' => '2024-12-31',
        'valid_med_controleur' => 1,
        'rechute-line' => 0,  // Input says NOT rechute
        'dt-line' => 0,
        'declaration-date-line' => '2024-01-01'
    ],
    [
        'arret-from-line' => '2024-09-02',
        'arret-to-line' => '2024-10-20',
        'valid_med_controleur' => 1,
        'rechute-line' => 0,  // Input says NOT rechute, but should auto-detect as rechute
        'dt-line' => 0,
        'declaration-date-line' => '2024-09-02'
    ]
];

$result1 = $arretService->calculateDateEffetForArrets($arrets1, null, 0);

echo "Arrêt #1:\n";
echo "  rechute-line from input: 0 (NOT rechute)\n";
echo "  Auto-calculated is_rechute: " . ($result1[0]['is_rechute'] ? 'YES' : 'NO') . "\n";
echo "  Date-effet: " . ($result1[0]['date-effet'] ?: 'NULL') . "\n";
echo "  Décompte: {$result1[0]['decompte_days']} days\n";

if ($result1[0]['is_rechute'] == false) {
    echo "  ✅ CORRECT: First arrêt is NOT rechute\n";
} else {
    echo "  ❌ ERROR: First arrêt should NOT be rechute\n";
}
echo "\n";

echo "Arrêt #2:\n";
echo "  rechute-line from input: 0 (NOT rechute)\n";
echo "  Auto-calculated is_rechute: " . ($result1[1]['is_rechute'] ? 'YES' : 'NO') . "\n";
echo "  Date-effet: " . ($result1[1]['date-effet'] ?: 'NULL') . "\n";
echo "  Décompte: {$result1[1]['decompte_days']} days\n";
echo "  Rule: Within 1 year after previous arrêt + rights opened = RECHUTE\n";

if ($result1[1]['is_rechute'] == true) {
    echo "  ✅ CORRECT: Auto-detected as RECHUTE (ignoring rechute-line=0)\n";
} else {
    echo "  ❌ ERROR: Should be auto-detected as rechute\n";
}
echo "\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 2: rechute-line = 1 but should NOT be rechute (> 1 year)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$arrets2 = [
    [
        'arret-from-line' => '2024-01-01',
        'arret-to-line' => '2024-12-31',
        'valid_med_controleur' => 1,
        'rechute-line' => 0,
        'dt-line' => 0,
        'declaration-date-line' => '2024-01-01'
    ],
    [
        'arret-from-line' => '2026-01-15',  // More than 1 year later
        'arret-to-line' => '2026-02-20',
        'valid_med_controleur' => 1,
        'rechute-line' => 1,  // Input says rechute, but it's > 1 year
        'dt-line' => 0,
        'declaration-date-line' => '2026-01-15'
    ]
];

$result2 = $arretService->calculateDateEffetForArrets($arrets2, null, 0);

echo "Arrêt #2 (2026-01-15, more than 1 year after 2024-12-31):\n";
echo "  rechute-line from input: 1 (IS rechute)\n";
echo "  Auto-calculated is_rechute: " . ($result2[1]['is_rechute'] ? 'YES' : 'NO') . "\n";
echo "  Date-effet: " . ($result2[1]['date-effet'] ?: 'NULL') . "\n";
echo "  Décompte: {$result2[1]['decompte_days']} days\n";
echo "  Rule: > 1 year after previous = NEW PATHOLOGY (not rechute)\n";

if ($result2[1]['is_rechute'] == false) {
    echo "  ✅ CORRECT: Auto-detected as NEW PATHOLOGY (ignoring rechute-line=1)\n";
} else {
    echo "  ❌ ERROR: Should NOT be rechute (> 1 year gap)\n";
}
echo "\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$pass = 0;
$fail = 0;

// Test 1: Should auto-detect rechute despite rechute-line=0
if ($result1[1]['is_rechute'] == true && $result1[1]['decompte_days'] == 0) {
    echo "✅ Test 1: Auto-detected rechute (ignored rechute-line=0)\n";
    $pass++;
} else {
    echo "❌ Test 1: Failed to auto-detect rechute\n";
    $fail++;
}

// Test 2: Should NOT be rechute despite rechute-line=1
if ($result2[1]['is_rechute'] == false && $result2[1]['decompte_days'] > 0) {
    echo "✅ Test 2: Auto-detected new pathology (ignored rechute-line=1)\n";
    $pass++;
} else {
    echo "❌ Test 2: Failed - still treating as rechute\n";
    $fail++;
}

echo "\nPassed: $pass / 2\n";
echo "Failed: $fail / 2\n\n";

if ($fail === 0) {
    echo "🎉 SUCCESS: Rechute is auto-calculated, input data ignored!\n";
} else {
    echo "❌ FAILED: Still using rechute-line from input\n";
}
