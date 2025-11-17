<?php

require_once __DIR__ . '/Services/DateCalculationInterface.php';
require_once __DIR__ . '/Services/DateService.php';
require_once __DIR__ . '/Services/ArretService.php';

use App\IJCalculator\Services\ArretService;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║   TEST RECHUTE: Date-Effet Outside Arrêt Period = NULL      ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$arretService = new ArretService();

// Test case: Rechute with short arrêt (8 days)
// 15-day threshold would be AFTER arrêt ends, so date-effet should be NULL
$arrets = [
    [
        'arret-from-line' => '2024-01-01',
        'arret-to-line' => '2024-12-31',
        'valid_med_controleur' => 1,
        'rechute-line' => 0,
        'dt-line' => 0,
        'declaration-date-line' => '2024-01-01'
    ],
    [
        'arret-from-line' => '2024-09-02',
        'arret-to-line' => '2024-09-09',  // Only 8 days
        'valid_med_controleur' => 1,
        'rechute-line' => 1,  // This is a rechute
        'dt-line' => 0,
        'declaration-date-line' => '2024-09-02'
    ],
    [
        'arret-from-line' => '2024-10-01',
        'arret-to-line' => '2024-10-20',  // 20 days - enough for 15-day threshold
        'valid_med_controleur' => 1,
        'rechute-line' => 1,  // This is a rechute
        'dt-line' => 0,
        'declaration-date-line' => '2024-10-01'
    ]
];

$result = $arretService->calculateDateEffetForArrets($arrets, null, 0);

echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST CASES\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Arrêt #1 (initial - opens rights):\n";
echo "  Period: {$result[0]['arret-from-line']} → {$result[0]['arret-to-line']}\n";
echo "  Duration: {$result[0]['arret_diff']} days\n";
echo "  Is Rechute: " . ($result[0]['is_rechute'] ? 'YES' : 'NO') . "\n";
echo "  Date-effet: " . ($result[0]['date-effet'] ?: 'NULL') . "\n";
echo "  Décompte: {$result[0]['decompte_days']} days\n";
echo "\n";

echo "Arrêt #2 (rechute - TOO SHORT, 8 days < 15 days):\n";
echo "  Period: {$result[1]['arret-from-line']} → {$result[1]['arret-to-line']}\n";
echo "  Duration: {$result[1]['arret_diff']} days\n";
echo "  Is Rechute: " . ($result[1]['is_rechute'] ? 'YES' : 'NO') . "\n";
echo "  Date-effet: " . ($result[1]['date-effet'] ?: 'NULL') . "\n";
echo "  Décompte: {$result[1]['decompte_days']} days\n";
echo "  15th day would be: 2024-09-16 (AFTER arrêt ends 2024-09-09)\n";

if ($result[1]['date-effet'] === null) {
    echo "  ✅ CORRECT: Date-effet is NULL (outside arrêt period)\n";
} else {
    echo "  ❌ ERROR: Date-effet should be NULL (arrêt too short)\n";
}
echo "\n";

echo "Arrêt #3 (rechute - LONG ENOUGH, 20 days > 15 days):\n";
echo "  Period: {$result[2]['arret-from-line']} → {$result[2]['arret-to-line']}\n";
echo "  Duration: {$result[2]['arret_diff']} days\n";
echo "  Is Rechute: " . ($result[2]['is_rechute'] ? 'YES' : 'NO') . "\n";
echo "  Date-effet: " . ($result[2]['date-effet'] ?: 'NULL') . "\n";
echo "  Décompte: {$result[2]['decompte_days']} days\n";
echo "  15th day would be: 2024-10-15 (WITHIN arrêt period)\n";

if ($result[2]['date-effet'] !== null && $result[2]['date-effet'] === '2024-10-15') {
    echo "  ✅ CORRECT: Date-effet is 2024-10-15 (within arrêt period)\n";
} elseif ($result[2]['date-effet'] !== null) {
    echo "  ⚠️  Date-effet is {$result[2]['date-effet']} (expected 2024-10-15)\n";
} else {
    echo "  ❌ ERROR: Date-effet should be calculated (arrêt long enough)\n";
}
echo "\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$pass = 0;
$fail = 0;

// Check arrêt #2 (rechute too short)
if ($result[1]['is_rechute'] && $result[1]['date-effet'] === null && $result[1]['decompte_days'] === 0) {
    echo "✅ Arrêt #2: Rechute too short - date-effet NULL, decompte 0\n";
    $pass++;
} else {
    echo "❌ Arrêt #2: Failed\n";
    $fail++;
}

// Check arrêt #3 (rechute long enough)
if ($result[2]['is_rechute'] && $result[2]['date-effet'] !== null && $result[2]['decompte_days'] === 0) {
    echo "✅ Arrêt #3: Rechute long enough - date-effet calculated, decompte 0\n";
    $pass++;
} else {
    echo "❌ Arrêt #3: Failed\n";
    $fail++;
}

echo "\n";
echo "Passed: $pass / 2\n";
echo "Failed: $fail / 2\n\n";

if ($fail === 0) {
    echo "🎉 ALL CHECKS PASSED!\n";
}
