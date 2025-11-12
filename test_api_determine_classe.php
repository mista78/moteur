<?php
/**
 * Test API Class Determination Endpoint
 *
 * Tests the new determine-classe API endpoint
 */

echo "=== Test API determine-classe Endpoint ===\n\n";

$apiUrl = 'http://localhost:8000/api.php?endpoint=determine-classe';

// Test scenarios
$tests = [
    [
        'description' => 'Classe A: Revenu < 1 PASS',
        'data' => [
            'revenu_n_moins_2' => 35000,
            'pass_value' => 47000
        ],
        'expected' => 'A'
    ],
    [
        'description' => 'Classe B: Revenu = 2 PASS',
        'data' => [
            'revenu_n_moins_2' => 94000,
            'pass_value' => 47000
        ],
        'expected' => 'B'
    ],
    [
        'description' => 'Classe C: Revenu > 3 PASS',
        'data' => [
            'revenu_n_moins_2' => 150000,
            'pass_value' => 47000
        ],
        'expected' => 'C'
    ],
    [
        'description' => 'Classe A: Taxé d\'office',
        'data' => [
            'revenu_n_moins_2' => 150000,
            'taxe_office' => true,
            'pass_value' => 47000
        ],
        'expected' => 'A'
    ],
    [
        'description' => 'Classe A: Revenus non communiqués',
        'data' => [
            'pass_value' => 47000
        ],
        'expected' => 'A'
    ],
];

// Check if server is running
$ch = curl_init('http://localhost:8000/api.php?endpoint=load-mock');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 0) {
    echo "⚠️  Warning: Development server not running at localhost:8000\n";
    echo "   Start with: php -S localhost:8000\n";
    echo "   Skipping API tests...\n\n";
    exit(0);
}

$passed = 0;
$failed = 0;

foreach ($tests as $i => $test) {
    $testNum = $i + 1;
    echo "Test #{$testNum}: {$test['description']}\n";

    // Make API request
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test['data']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "  ✗ HTTP Error: {$httpCode}\n";
        echo "  Response: {$response}\n\n";
        $failed++;
        continue;
    }

    $result = json_decode($response, true);

    if (!$result['success']) {
        echo "  ✗ API Error: " . ($result['error'] ?? 'Unknown error') . "\n\n";
        $failed++;
        continue;
    }

    $classe = $result['data']['classe'];
    echo "  Expected: {$test['expected']}\n";
    echo "  Got:      {$classe}\n";

    if ($classe === $test['expected']) {
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
    echo "\n✓ All API tests passed!\n";
} else {
    echo "\n✗ Some tests failed!\n";
    exit(1);
}
