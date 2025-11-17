<?php

require_once __DIR__ . '/Services/DateCalculationInterface.php';
require_once __DIR__ . '/Services/DateService.php';

use App\IJCalculator\Services\DateService;

$service = new DateService();

// Test array with consecutive and non-consecutive arrêts
// Note: Uses business days (Mon-Fri, skipping weekends and holidays)
$test_lstarray = [
    [
        'arret-from-line' => '2024-01-02',  // Tuesday
        'arret-to-line' => '2024-01-10',    // Wednesday
        'id' => 'Arret 1'
    ],
    [
        'arret-from-line' => '2024-01-11',  // Thursday - CONSECUTIVE (next business day)
        'arret-to-line' => '2024-01-17',    // Wednesday
        'id' => 'Arret 2'
    ],
    [
        'arret-from-line' => '2024-01-18',  // Thursday - CONSECUTIVE
        'arret-to-line' => '2024-01-25',    // Thursday
        'id' => 'Arret 3'
    ],
    [
        'arret-from-line' => '2024-02-05',  // Monday - NOT consecutive (gap)
        'arret-to-line' => '2024-02-14',    // Wednesday
        'id' => 'Arret 4'
    ],
    [
        'arret-from-line' => '2024-02-15',  // Thursday - CONSECUTIVE
        'arret-to-line' => '2024-02-22',    // Thursday
        'id' => 'Arret 5'
    ]
];

echo "=== BEFORE mergeProlongations ===\n";
echo "Total arrêts: " . count($test_lstarray) . "\n\n";
foreach ($test_lstarray as $i => $arret) {
    echo "[$i] {$arret['id']}: {$arret['arret-from-line']} to {$arret['arret-to-line']}\n";
}

$merged = $service->mergeProlongations($test_lstarray);

echo "\n=== AFTER mergeProlongations ===\n";
echo "Total arrêts: " . count($merged) . "\n\n";
foreach ($merged as $i => $arret) {
    $id = $arret['id'] ?? 'Merged';
    echo "[$i] {$id}: {$arret['arret-from-line']} to {$arret['arret-to-line']}\n";

    // Show merge flags
    if (isset($arret['has_prolongations']) && $arret['has_prolongations']) {
        echo "     ✅ has_prolongations: true\n";
        echo "     ✅ prolongation_count: {$arret['prolongation_count']}\n";

        if (isset($arret['merged_arrets'])) {
            echo "     ✅ merged_arrets:\n";
            foreach ($arret['merged_arrets'] as $merged_info) {
                echo "        - Index {$merged_info['original_index']}: {$merged_info['from']} to {$merged_info['to']}\n";
            }
        }
    } else {
        echo "     ⚪ No prolongations\n";
    }
}

echo "\n=== EXPLANATION ===\n";
echo "The function uses BUSINESS DAYS (Mon-Fri, skipping weekends and holidays)\n\n";
echo "Expected behavior:\n";
echo "- Arret 1, 2, 3 are CONSECUTIVE (by business days) → Should merge into ONE\n";
echo "  (2024-01-02 to 2024-01-25)\n";
echo "- Arret 2 and 3 are REMOVED from final array (used in merge)\n";
echo "- Arret 4 has a GAP → Kept as separate\n";
echo "- Arret 4, 5 are CONSECUTIVE → Should merge into ONE\n";
echo "  (2024-02-05 to 2024-02-22)\n";
echo "- Arret 5 is REMOVED from final array (used in merge)\n";
echo "\nExpected result: 5 arrêts → 2 merged arrêts\n";
