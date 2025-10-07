<?php

/**
 * Comprehensive Test Runner
 * Runs all unit tests and integration tests
 */

echo "\n";
echo "================================================================================\n";
echo "                       IJCalculator Test Suite                                  \n";
echo "================================================================================\n";
echo "\n";

$testFiles = [
    'Tests/RateServiceTest.php' => 'Rate Service Unit Tests',
    'Tests/DateServiceTest.php' => 'Date Service Unit Tests',
    'Tests/TauxDeterminationServiceTest.php' => 'Taux Determination Service Unit Tests',
    'Tests/AmountCalculationServiceTest.php' => 'Amount Calculation Service Unit Tests',
    'test_mocks.php' => 'Integration Tests (IJCalculator with real mocks)'
];

$totalPassed = 0;
$totalFailed = 0;
$startTime = microtime(true);

foreach ($testFiles as $file => $description) {
    echo "\n";
    echo "--------------------------------------------------------------------------------\n";
    echo "Running: $description\n";
    echo "File: $file\n";
    echo "--------------------------------------------------------------------------------\n";

    // Capture output
    ob_start();
    include $file;
    $output = ob_get_clean();

    echo $output;

    // Parse results (simple regex to extract counts)
    if (preg_match('/(\d+) passed/', $output, $matches)) {
        $totalPassed += (int)$matches[1];
    }
    if (preg_match('/(\d+) failed/', $output, $matches)) {
        $totalFailed += (int)$matches[1];
    }
}

$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

echo "\n";
echo "================================================================================\n";
echo "                            FINAL SUMMARY                                       \n";
echo "================================================================================\n";
echo "\n";
echo "Total Tests: " . ($totalPassed + $totalFailed) . "\n";
echo "\033[32mPassed: $totalPassed\033[0m\n";
if ($totalFailed > 0) {
    echo "\033[31mFailed: $totalFailed\033[0m\n";
} else {
    echo "Failed: 0\n";
}
echo "Duration: {$duration}ms\n";
echo "\n";

if ($totalFailed === 0) {
    echo "\033[42m\033[30m ✓ ALL TESTS PASSED \033[0m\n";
} else {
    echo "\033[41m\033[37m ✗ SOME TESTS FAILED \033[0m\n";
}

echo "\n";

exit($totalFailed > 0 ? 1 : 0);
