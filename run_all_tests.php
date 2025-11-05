<?php

/**
 * Comprehensive Test Runner
 * Runs all unit tests and integration tests
 */

require_once 'jest-php.php';

echo "\n";
echo "================================================================================\n";
echo "                       IJCalculator Test Suite                                  \n";
echo "================================================================================\n";
echo "\n";
define('ROOT_PATH', __DIR__ . '/');
$testFiles = glob(ROOT_PATH . 'Tests/*.php');

$totalPassed = 0;
$totalFailed = 0;
$startTime = microtime(true);

foreach ($testFiles as $file) {
    include $file;
}



// Run all tests once
ob_start();
JestPHP::run();
$output = ob_get_clean();

echo $output;

// Parse results - match "Tests:" line, accounting for ANSI color codes
// Strip ANSI codes for easier parsing
$cleanOutput = preg_replace('/\033\[[0-9;]+m/', '', $output);

if (preg_match('/Tests:\s+(?:\d+ failed,\s+)?(\d+) passed/', $cleanOutput, $matches)) {
    $totalPassed = (int)$matches[1];
}
if (preg_match('/Tests:\s+(\d+) failed/', $cleanOutput, $matches)) {
    $totalFailed = (int)$matches[1];
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
