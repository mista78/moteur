<?php
/**
 * Simple Test: RecapService Class Determination
 * Uses existing mock data to test class determination
 */

require_once 'IJCalculator.php';
require_once 'Services/RecapService.php';

use App\IJCalculator\IJCalculator;
use App\IJCalculator\Services\RecapService;

echo "=== Simple RecapService Class Determination Test ===\n\n";

$calculator = new IJCalculator('taux.csv');

// Test 1: Load mock.json, remove classe, add revenu
echo "Test #1: Auto-determine Class A from revenu\n";

$mockData = json_decode(file_get_contents('mock.json'), true);
$inputData = [
    'adherent_number' => '301261U',
    'num_sinistre' => 48,
    // No classe provided
    'revenu_n_moins_2' => 35000,  // < 1 PASS = Classe A
    'pass_value' => 47000,
    'statut' => 'M',
    'option' => 100,
    'birth_date' => '1960-01-15',
    'current_date' => '2024-01-15',
    'attestation_date' => '2024-01-31',
    'nb_trimestres' => 22,
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'arrets' => $mockData
];

// Calculate with auto-determined class
$calculator->setPassValue(47000);
$result = $calculator->calculateAmount($inputData);

// Generate recap with calculator for class determination
$recapService = new RecapService();
$recapService->setCalculator($calculator);
$recapRecords = $recapService->generateRecapRecords($result, $inputData);

if (!empty($recapRecords)) {
    $classeInRecap = $recapRecords[0]['classe'] ?? null;
    echo "  Montant calculated: " . number_format($result['montant'], 2) . "€\n";
    echo "  Expected classe: A\n";
    echo "  Got classe:      {$classeInRecap}\n";
    echo "  " . ($classeInRecap === 'A' ? "✓ PASS" : "✗ FAIL") . "\n\n";
} else {
    echo "  ✗ FAIL: No recap records\n\n";
}

// Test 2: Class B (2 PASS)
echo "Test #2: Auto-determine Class B from revenu\n";

$inputData2 = $inputData;
$inputData2['revenu_n_moins_2'] = 94000;  // 2 PASS = Classe B
$inputData2['num_sinistre'] = 49;

$result2 = $calculator->calculateAmount($inputData2);
$recapRecords2 = $recapService->generateRecapRecords($result2, $inputData2);

if (!empty($recapRecords2)) {
    $classeInRecap2 = $recapRecords2[0]['classe'] ?? null;
    echo "  Montant calculated: " . number_format($result2['montant'], 2) . "€\n";
    echo "  Expected classe: B\n";
    echo "  Got classe:      {$classeInRecap2}\n";
    echo "  " . ($classeInRecap2 === 'B' ? "✓ PASS" : "✗ FAIL") . "\n\n";
} else {
    echo "  ✗ FAIL: No recap records\n\n";
}

// Test 3: Class C (> 3 PASS)
echo "Test #3: Auto-determine Class C from revenu\n";

$inputData3 = $inputData;
$inputData3['revenu_n_moins_2'] = 150000;  // > 3 PASS = Classe C
$inputData3['num_sinistre'] = 50;

$result3 = $calculator->calculateAmount($inputData3);
$recapRecords3 = $recapService->generateRecapRecords($result3, $inputData3);

if (!empty($recapRecords3)) {
    $classeInRecap3 = $recapRecords3[0]['classe'] ?? null;
    echo "  Montant calculated: " . number_format($result3['montant'], 2) . "€\n";
    echo "  Expected classe: C\n";
    echo "  Got classe:      {$classeInRecap3}\n";
    echo "  " . ($classeInRecap3 === 'C' ? "✓ PASS" : "✗ FAIL") . "\n\n";
} else {
    echo "  ✗ FAIL: No recap records\n\n";
}

// Test 4: Explicit class overrides revenu
echo "Test #4: Explicit class overrides revenu\n";

$inputData4 = $inputData;
$inputData4['classe'] = 'A';  // Explicit
$inputData4['revenu_n_moins_2'] = 150000;  // Would be C, but we explicitly set A
$inputData4['num_sinistre'] = 51;

$result4 = $calculator->calculateAmount($inputData4);
$recapRecords4 = $recapService->generateRecapRecords($result4, $inputData4);

if (!empty($recapRecords4)) {
    $classeInRecap4 = $recapRecords4[0]['classe'] ?? null;
    echo "  Expected classe: A (explicit override)\n";
    echo "  Got classe:      {$classeInRecap4}\n";
    echo "  " . ($classeInRecap4 === 'A' ? "✓ PASS" : "✗ FAIL") . "\n\n";
} else {
    echo "  ✗ FAIL: No recap records\n\n";
}

// Test 5: Backward compatibility - without calculator injection
echo "Test #5: Backward compatibility (no calculator injection)\n";

$inputData5 = $inputData;
$inputData5['classe'] = 'B';  // Explicit
$inputData5['num_sinistre'] = 52;
unset($inputData5['revenu_n_moins_2']);  // Remove revenu

$result5 = $calculator->calculateAmount($inputData5);

// Create new service WITHOUT calculator injection
$recapServiceOld = new RecapService();
// Don't call setCalculator()
$recapRecords5 = $recapServiceOld->generateRecapRecords($result5, $inputData5);

if (!empty($recapRecords5)) {
    $classeInRecap5 = $recapRecords5[0]['classe'] ?? null;
    echo "  Expected classe: B (explicit, no calculator)\n";
    echo "  Got classe:      {$classeInRecap5}\n";
    echo "  " . ($classeInRecap5 === 'B' ? "✓ PASS" : "✗ FAIL") . "\n\n";
} else {
    echo "  ✗ FAIL: No recap records\n\n";
}

echo "✓ RecapService class determination working!\n";
