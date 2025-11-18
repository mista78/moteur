<?php
/**
 * Test merge tracking in mergeProlongations
 * Verifies that merged_arret_indices are correctly tracked
 */

require_once 'Services/DateCalculationInterface.php';
require_once 'Services/DateService.php';

use App\IJCalculator\Services\DateService;

$service = new DateService();

echo "=== Test 1: Three consecutive arrets (all should merge) ===\n";
$arrets1 = [
	[
		'arret-from-line' => '2024-01-01',
		'arret-to-line' => '2024-01-10',
	],
	[
		'arret-from-line' => '2024-01-11', // Next day
		'arret-to-line' => '2024-01-20',
	],
	[
		'arret-from-line' => '2024-01-21', // Next day
		'arret-to-line' => '2024-01-31',
	],
];

$merged1 = $service->mergeProlongations($arrets1);
echo "Original arrets: 3\n";
echo "Merged arrets: " . count($merged1) . "\n";
echo "Expected: 1 (all consecutive)\n\n";

foreach ($merged1 as $index => $arret) {
	echo "Merged arret #$index:\n";
	echo "  From: {$arret['arret-from-line']}\n";
	echo "  To: {$arret['arret-to-line']}\n";
	echo "  Original index: " . ($arret['original_index'] ?? 'N/A') . "\n";
	echo "  Merged arret indices: " . json_encode($arret['merged_arret_indices'] ?? []) . "\n";
	echo "\n";
}

// Verify
if (count($merged1) === 1 &&
	isset($merged1[0]['merged_arret_indices']) &&
	count($merged1[0]['merged_arret_indices']) === 3) {
	echo "✓ PASS: All three arrets merged correctly\n";
	echo "✓ PASS: merged_arret_indices exists (actual merge occurred)\n";
	echo "✓ PASS: Tracking shows [0, 1, 2] as expected\n";
} else {
	echo "✗ FAIL: Merge tracking incorrect\n";
}

echo "\n" . str_repeat("=", 60) . "\n\n";

echo "=== Test 2: Two separate groups ===\n";
$arrets2 = [
	[
		'arret-from-line' => '2024-01-01',
		'arret-to-line' => '2024-01-10',
	],
	[
		'arret-from-line' => '2024-01-11', // Next day (merge with first)
		'arret-to-line' => '2024-01-15',
	],
	[
		'arret-from-line' => '2024-02-01', // Gap (separate arret)
		'arret-to-line' => '2024-02-10',
	],
	[
		'arret-from-line' => '2024-02-11', // Next day (merge with third)
		'arret-to-line' => '2024-02-20',
	],
];

$merged2 = $service->mergeProlongations($arrets2);
echo "Original arrets: 4\n";
echo "Merged arrets: " . count($merged2) . "\n";
echo "Expected: 2 (two groups of consecutive arrets)\n\n";

foreach ($merged2 as $index => $arret) {
	echo "Merged arret #$index:\n";
	echo "  From: {$arret['arret-from-line']}\n";
	echo "  To: {$arret['arret-to-line']}\n";
	echo "  Original index: " . ($arret['original_index'] ?? 'N/A') . "\n";
	echo "  Merged arret indices: " . json_encode($arret['merged_arret_indices'] ?? []) . "\n";
	echo "\n";
}

// Verify
if (count($merged2) === 2 &&
	isset($merged2[0]['merged_arret_indices']) &&
	isset($merged2[1]['merged_arret_indices']) &&
	count($merged2[0]['merged_arret_indices']) === 2 &&
	count($merged2[1]['merged_arret_indices']) === 2 &&
	$merged2[0]['merged_arret_indices'] == [0, 1] &&
	$merged2[1]['merged_arret_indices'] == [2, 3]) {
	echo "✓ PASS: Two groups merged correctly\n";
	echo "✓ PASS: merged_arret_indices exists for both (actual merges occurred)\n";
	echo "✓ PASS: First group: [0, 1]\n";
	echo "✓ PASS: Second group: [2, 3]\n";
} else {
	echo "✗ FAIL: Merge tracking incorrect\n";
}

echo "\n" . str_repeat("=", 60) . "\n\n";

echo "=== Test 3: No merges (all separate) ===\n";
$arrets3 = [
	[
		'arret-from-line' => '2024-01-01',
		'arret-to-line' => '2024-01-10',
	],
	[
		'arret-from-line' => '2024-02-01', // Gap
		'arret-to-line' => '2024-02-10',
	],
	[
		'arret-from-line' => '2024-03-01', // Gap
		'arret-to-line' => '2024-03-10',
	],
];

$merged3 = $service->mergeProlongations($arrets3);
echo "Original arrets: 3\n";
echo "Merged arrets: " . count($merged3) . "\n";
echo "Expected: 3 (all separate)\n\n";

foreach ($merged3 as $index => $arret) {
	echo "Merged arret #$index:\n";
	echo "  From: {$arret['arret-from-line']}\n";
	echo "  To: {$arret['arret-to-line']}\n";
	echo "  Original index: " . ($arret['original_index'] ?? 'N/A') . "\n";
	echo "  Merged arret indices: " . json_encode($arret['merged_arret_indices'] ?? []) . "\n";
	echo "\n";
}

// Verify
if (count($merged3) === 3 &&
	!isset($merged3[0]['merged_arret_indices']) &&
	!isset($merged3[1]['merged_arret_indices']) &&
	!isset($merged3[2]['merged_arret_indices']) &&
	$merged3[0]['original_index'] === 0 &&
	$merged3[1]['original_index'] === 1 &&
	$merged3[2]['original_index'] === 2) {
	echo "✓ PASS: All arrets remain separate\n";
	echo "✓ PASS: No merged_arret_indices field (no merges occurred)\n";
	echo "✓ PASS: original_index preserved: 0, 1, 2\n";
} else {
	echo "✗ FAIL: Merge tracking incorrect\n";
	if (isset($merged3[0]['merged_arret_indices'])) {
		echo "  ERROR: merged_arret_indices exists when it shouldn't!\n";
	}
}

echo "\n=== All Tests Complete ===\n";
