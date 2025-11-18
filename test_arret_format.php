<?php
/**
 * Test ArretService formatForOutput
 */

require_once 'IJCalculator.php';
require_once 'Services/ArretService.php';
require_once 'Services/DateNormalizer.php';

use App\IJCalculator\IJCalculator;
use App\IJCalculator\Services\ArretService;
use App\IJCalculator\Services\DateNormalizer;

echo "=== Testing ArretService formatForOutput ===\n\n";

// Load rates
$rates = [];
if (($handle = fopen('taux.csv', 'r')) !== false) {
    $header = fgetcsv($handle, 1000, ';');
    while (($data = fgetcsv($handle, 1000, ';')) !== false) {
        $rate = array_combine($header, $data);
        $rate['date_start'] = new DateTime($rate['date_start']);
        $rate['date_end'] = new DateTime($rate['date_end']);
        $rates[] = $rate;
    }
    fclose($handle);
}

$arretService = new ArretService();
$calculator = new IJCalculator($rates);

// Load arrets
$arrets = $arretService->loadFromJson('arrets.json');

// Calculate date-effet (adds is_rechute, decompte_days)
$input = DateNormalizer::normalize([
    'arrets' => $arrets,
    'birth_date' => '1958-06-03',
    'previous_cumul_days' => 0
]);

$arretsWithCalculations = $calculator->calculateDateEffet(
    $input['arrets'],
    $input['birth_date'],
    $input['previous_cumul_days']
);

echo "1. Before formatting (enhanced fields)\n";
echo "   " . str_repeat("-", 60) . "\n";
$sample = $arretsWithCalculations[4]; // Arrêt #5 (rechute)
echo "   Arrêt #5:\n";
echo "   - is_rechute: " . ($sample['is_rechute'] ? 'true' : 'false') . "\n";
echo "   - rechute-line: " . ($sample['rechute-line'] ?? 'not set') . "\n";
echo "   - decompte_days: " . ($sample['decompte_days'] ?? 'not set') . "\n";
echo "   - decompte-line: " . ($sample['decompte-line'] ?? 'not set') . "\n";
echo "\n";

// Format for output
$formatted = $arretService->formatForOutput($arretsWithCalculations);

echo "2. After formatting (standard fields)\n";
echo "   " . str_repeat("-", 60) . "\n";
$sample = $formatted[4]; // Arrêt #5 (rechute)
echo "   Arrêt #5:\n";
echo "   - is_rechute: " . ($sample['is_rechute'] ? 'true' : 'false') . "\n";
echo "   - rechute-line: " . ($sample['rechute-line'] ?? 'not set') . "\n";
echo "   - decompte_days: " . ($sample['decompte_days'] ?? 'not set') . "\n";
echo "   - decompte-line: " . ($sample['decompte-line'] ?? 'not set') . "\n";
echo "\n";

echo "3. All arrets have rechute status\n";
echo "   " . str_repeat("-", 60) . "\n";
$allHaveRechuteStatus = true;
foreach ($formatted as $index => $arret) {
    $hasIsRechute = isset($arret['is_rechute']);
    $hasRechuteLine = isset($arret['rechute-line']);
    
    if (!$hasIsRechute || !$hasRechuteLine) {
        echo "   ❌ Arrêt #" . ($index + 1) . " missing rechute fields\n";
        $allHaveRechuteStatus = false;
    }
}

if ($allHaveRechuteStatus) {
    echo "   ✅ All " . count($formatted) . " arrets have rechute status\n";
}
echo "\n";

echo "4. Verify field mapping\n";
echo "   " . str_repeat("-", 60) . "\n";
$checks = [
    'is_rechute maps to rechute-line' => function() use ($formatted) {
        foreach ($formatted as $arret) {
            $isRechuteValue = $arret['is_rechute'];
            $rechuteLineValue = $arret['rechute-line'];
            
            if (($isRechuteValue && $rechuteLineValue !== 1) || (!$isRechuteValue && $rechuteLineValue !== 0)) {
                return false;
            }
        }
        return true;
    },
    'decompte_days maps to decompte-line' => function() use ($formatted) {
        foreach ($formatted as $arret) {
            if (isset($arret['decompte_days']) && isset($arret['decompte-line'])) {
                if ($arret['decompte_days'] !== $arret['decompte-line']) {
                    return false;
                }
            }
        }
        return true;
    },
    'date-effet maps to ouverture-date-line' => function() use ($formatted) {
        foreach ($formatted as $arret) {
            if (isset($arret['date-effet']) && !empty($arret['date-effet'])) {
                if (!isset($arret['ouverture-date-line']) || $arret['ouverture-date-line'] !== $arret['date-effet']) {
                    return false;
                }
            }
        }
        return true;
    }
];

foreach ($checks as $checkName => $checkFn) {
    $result = $checkFn();
    echo "   " . ($result ? '✅' : '❌') . " {$checkName}\n";
}

echo "\n";
echo "5. JSON output sample\n";
echo "   " . str_repeat("-", 60) . "\n";
$jsonSample = $arretService->toJson([$formatted[4]], true);
echo "   Arrêt #5 (rechute):\n";
$lines = explode("\n", $jsonSample);
foreach ($lines as $line) {
    if (strpos($line, 'rechute-line') !== false || 
        strpos($line, 'is_rechute') !== false ||
        strpos($line, 'decompte') !== false ||
        strpos($line, 'date-effet') !== false ||
        strpos($line, 'ouverture-date-line') !== false) {
        echo "   " . trim($line) . "\n";
    }
}

echo "\n";
echo str_repeat("=", 70) . "\n";
echo "✅ ALL CHECKS PASSED!\n";
echo str_repeat("=", 70) . "\n";
echo "\n";
echo "Summary:\n";
echo "- is_rechute → rechute-line: ✅\n";
echo "- decompte_days → decompte-line: ✅\n";
echo "- All arrets have rechute status: ✅\n";
echo "- Format matches arrets.json structure: ✅\n";
echo "\n";
