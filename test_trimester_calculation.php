<?php

require_once __DIR__ . '/Services/DateCalculationInterface.php';
require_once __DIR__ . '/Services/DateService.php';

use App\IJCalculator\Services\DateService;

$service = new DateService();

echo "=== Trimester Calculation Tests ===\n\n";

// Test cases based on quarter-completion rule
$testCases = [
    // Affiliation at start of Q1
    ['2024-01-01', '2024-01-01', 1, 'Same day in Q1 (start of quarter)'],
    ['2024-01-01', '2024-03-31', 1, 'Full Q1'],
    ['2024-01-01', '2024-04-01', 2, 'Q1 + start of Q2'],
    ['2024-01-01', '2024-12-31', 4, 'Full year (4 quarters)'],

    // Affiliation in middle of Q1
    ['2024-01-15', '2024-01-15', 1, 'Same day mid-Q1 (counts as complete quarter)'],
    ['2024-01-15', '2024-03-31', 1, 'Mid-Q1 to end of Q1 (complete quarter)'],
    ['2024-01-15', '2024-04-01', 2, 'Mid-Q1 to start of Q2'],
    ['2024-01-15', '2024-12-31', 4, 'Mid-Q1 to end of year'],

    // Affiliation at end of Q1
    ['2024-03-31', '2024-03-31', 1, 'Last day of Q1 (counts as complete)'],
    ['2024-03-31', '2024-04-01', 2, 'Last day Q1 to first day Q2'],

    // Multi-year examples
    ['2023-01-01', '2024-12-31', 8, '2 full years (8 quarters)'],
    ['2023-07-15', '2024-09-15', 5, 'Q3 2023 to Q3 2024 (Q3+Q4 2023 + Q1+Q2+Q3 2024 = 5)'],

    // Real example from test: 2017-07-01 to 2024-09-09
    // Q3 2017 (1) + Q4 2017 (1) + 2018-2023 (6*4=24) + Q1+Q2+Q3 2024 (3) = 29 quarters
    ['2017-07-01', '2024-09-09', 29, 'Q3 2017 to Q3 2024'],

    // Edge cases
    ['2024-01-01', '2024-01-31', 1, 'Within same quarter Q1'],
    ['2024-02-15', '2024-05-20', 2, 'Q1 to Q2'],
    ['2024-04-01', '2024-06-30', 1, 'Full Q2'],
    ['2024-07-01', '2024-09-30', 1, 'Full Q3'],
    ['2024-10-01', '2024-12-31', 1, 'Full Q4'],

    // Cross-year boundary
    ['2023-12-31', '2024-01-01', 2, 'Q4 2023 to Q1 2024'],
    ['2023-11-01', '2024-02-28', 2, 'Q4 2023 + Q1 2024'],
];

$passed = 0;
$failed = 0;

foreach ($testCases as $test) {
    [$affiliation, $current, $expected, $description] = $test;

    $result = $service->calculateTrimesters($affiliation, $current);
    $status = $result === $expected ? '✓' : '✗';

    if ($result === $expected) {
        $passed++;
        echo "✓ PASS: $description\n";
        echo "   Affiliation: $affiliation | Current: $current | Expected: $expected | Got: $result\n\n";
    } else {
        $failed++;
        echo "✗ FAIL: $description\n";
        echo "   Affiliation: $affiliation | Current: $current | Expected: $expected | Got: $result\n\n";
    }
}

echo "=== Summary ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Total:  " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n✓ All tests passed!\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed!\n";
    exit(1);
}
