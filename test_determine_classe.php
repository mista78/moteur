<?php
/**
 * Test Class Determination (Backend)
 *
 * Tests the backend class determination logic
 */

require_once 'IJCalculator.php';

echo "=== Test Backend Class Determination ===\n\n";

$calculator = new IJCalculator('taux.csv');
$calculator->setPassValue(47000);

// Test scenarios
$tests = [
    [
        'description' => 'Classe A: Revenu < 1 PASS',
        'revenu' => 35000,
        'expected' => 'A'
    ],
    [
        'description' => 'Classe A: Revenu = 0.5 PASS',
        'revenu' => 23500,
        'expected' => 'A'
    ],
    [
        'description' => 'Classe B: Revenu = 1 PASS (seuil)',
        'revenu' => 47000,
        'expected' => 'B'
    ],
    [
        'description' => 'Classe B: Revenu = 2 PASS',
        'revenu' => 94000,
        'expected' => 'B'
    ],
    [
        'description' => 'Classe B: Revenu = 3 PASS (seuil)',
        'revenu' => 141000,
        'expected' => 'B'
    ],
    [
        'description' => 'Classe C: Revenu > 3 PASS',
        'revenu' => 150000,
        'expected' => 'C'
    ],
    [
        'description' => 'Classe C: Revenu = 5 PASS',
        'revenu' => 235000,
        'expected' => 'C'
    ],
    [
        'description' => 'Classe A: Taxé d\'office (revenu ignoré)',
        'revenu' => 150000,
        'taxe_office' => true,
        'expected' => 'A'
    ],
    [
        'description' => 'Classe A: Revenus non communiqués',
        'revenu' => null,
        'expected' => 'A'
    ],
];

$passed = 0;
$failed = 0;

foreach ($tests as $i => $test) {
    $testNum = $i + 1;
    echo "Test #{$testNum}: {$test['description']}\n";
    echo "  Revenu N-2: " . ($test['revenu'] ? number_format($test['revenu'], 0, ',', ' ') . '€' : 'null') . "\n";

    if (isset($test['taxe_office']) && $test['taxe_office']) {
        echo "  Taxé d'office: OUI\n";
    }

    $taxeOffice = isset($test['taxe_office']) ? $test['taxe_office'] : false;
    $result = $calculator->determineClasse($test['revenu'], null, $taxeOffice);

    echo "  Expected: {$test['expected']}\n";
    echo "  Got:      {$result}\n";

    if ($result === $test['expected']) {
        echo "  ✓ PASS\n\n";
        $passed++;
    } else {
        echo "  ✗ FAIL\n\n";
        $failed++;
    }
}

echo "=== Test Summary ===\n";
echo "Total:  " . count($tests) . " tests\n";
echo "Passed: {$passed} tests\n";
echo "Failed: {$failed} tests\n";

if ($failed === 0) {
    echo "\n✓ All tests passed!\n";
} else {
    echo "\n✗ Some tests failed!\n";
    exit(1);
}
