<?php
/**
 * Test RecapService Class Determination
 *
 * Tests that RecapService correctly determines class from revenu_n_moins_2
 */

require_once 'IJCalculator.php';
require_once 'Services/RecapService.php';

use App\IJCalculator\IJCalculator;
use App\IJCalculator\Services\RecapService;

echo "=== Test RecapService Class Determination ===\n\n";

$calculator = new IJCalculator('taux.csv');
$calculator->setPassValue(47000);

// Test scenarios
$tests = [
    [
        'description' => 'Class A from revenu (< 1 PASS)',
        'inputData' => [
            'adherent_number' => '301261U',
            'num_sinistre' => 48,
            'revenu_n_moins_2' => 35000,  // < 1 PASS
            'pass_value' => 47000,
            'statut' => 'M',
            'option' => 100,
            'birth_date' => '1960-01-15',
            'current_date' => '2024-01-15',
            'attestation_date' => '2024-01-15',
            'nb_trimestres' => 22,
            'arrets' => [
                [
                    'arret-from-line' => '2023-12-03',
                    'arret-to-line' => '2024-01-10',
                    'rechute-line' => 0,
                    'dt-line' => 0,
                    'gpm-member-line' => 0,
                    'declaration-date-line' => '2023-12-03',
                    'id' => 1
                ]
            ]
        ],
        'expected_classe' => 'A'
    ],
    [
        'description' => 'Class B from revenu (2 PASS)',
        'inputData' => [
            'adherent_number' => '191566V',
            'num_sinistre' => 50,
            'revenu_n_moins_2' => 94000,  // 2 PASS
            'pass_value' => 47000,
            'statut' => 'M',
            'option' => 100,
            'birth_date' => '1960-01-15',
            'current_date' => '2024-01-15',
            'attestation_date' => '2024-01-15',
            'nb_trimestres' => 22,
            'arrets' => [
                [
                    'arret-from-line' => '2023-12-03',
                    'arret-to-line' => '2024-01-10',
                    'rechute-line' => 0,
                    'dt-line' => 0,
                    'gpm-member-line' => 0,
                    'declaration-date-line' => '2023-12-03',
                    'id' => 1
                ]
            ]
        ],
        'expected_classe' => 'B'
    ],
    [
        'description' => 'Class C from revenu (> 3 PASS)',
        'inputData' => [
            'adherent_number' => '246007T',
            'num_sinistre' => 52,
            'revenu_n_moins_2' => 150000,  // > 3 PASS
            'pass_value' => 47000,
            'statut' => 'M',
            'option' => 100,
            'birth_date' => '1960-01-15',
            'current_date' => '2024-01-15',
            'attestation_date' => '2024-01-15',
            'nb_trimestres' => 22,
            'arrets' => [
                [
                    'arret-from-line' => '2023-12-03',
                    'arret-to-line' => '2024-01-10',
                    'rechute-line' => 0,
                    'dt-line' => 0,
                    'gpm-member-line' => 0,
                    'declaration-date-line' => '2023-12-03',
                    'id' => 1
                ]
            ]
        ],
        'expected_classe' => 'C'
    ],
    [
        'description' => 'Explicit class overrides revenu',
        'inputData' => [
            'adherent_number' => '301261U',
            'num_sinistre' => 48,
            'classe' => 'A',  // Explicit class
            'revenu_n_moins_2' => 150000,  // Would be C, but explicit A takes priority
            'pass_value' => 47000,
            'statut' => 'M',
            'option' => 100,
            'birth_date' => '1960-01-15',
            'current_date' => '2024-01-15',
            'attestation_date' => '2024-01-15',
            'nb_trimestres' => 22,
            'arrets' => [
                [
                    'arret-from-line' => '2023-12-03',
                    'arret-to-line' => '2024-01-10',
                    'rechute-line' => 0,
                    'dt-line' => 0,
                    'gpm-member-line' => 0,
                    'declaration-date-line' => '2023-12-03',
                    'id' => 1
                ]
            ]
        ],
        'expected_classe' => 'A'
    ],
    [
        'description' => 'Taxé d\'office returns A (revenu ignored)',
        'inputData' => [
            'adherent_number' => '301261U',
            'num_sinistre' => 48,
            'revenu_n_moins_2' => 150000,  // Would be C
            'taxe_office' => true,         // But taxé d'office
            'pass_value' => 47000,
            'statut' => 'M',
            'option' => 100,
            'birth_date' => '1960-01-15',
            'current_date' => '2024-01-15',
            'attestation_date' => '2024-01-15',
            'nb_trimestres' => 22,
            'arrets' => [
                [
                    'arret-from-line' => '2023-12-03',
                    'arret-to-line' => '2024-01-10',
                    'rechute-line' => 0,
                    'dt-line' => 0,
                    'gpm-member-line' => 0,
                    'declaration-date-line' => '2023-12-03',
                    'id' => 1
                ]
            ]
        ],
        'expected_classe' => 'A'
    ]
];

$passed = 0;
$failed = 0;

foreach ($tests as $i => $test) {
    $testNum = $i + 1;
    echo "Test #{$testNum}: {$test['description']}\n";

    // Calculate
    $result = $calculator->calculateAmount($test['inputData']);

    // Generate recap with calculator for class determination
    $recapService = new RecapService();
    $recapService->setCalculator($calculator);  // Inject calculator
    $recapRecords = $recapService->generateRecapRecords($result, $test['inputData']);

    if (empty($recapRecords)) {
        echo "  ✗ FAIL: No recap records generated\n\n";
        $failed++;
        continue;
    }

    $classeInRecap = $recapRecords[0]['classe'] ?? null;

    echo "  Expected classe: {$test['expected_classe']}\n";
    echo "  Got classe:      {$classeInRecap}\n";

    if ($classeInRecap === $test['expected_classe']) {
        echo "  ✓ PASS\n\n";
        $passed++;
    } else {
        echo "  ✗ FAIL\n\n";
        $failed++;
    }
}

// Test without calculator injection (backward compatibility)
echo "\n=== Test Backward Compatibility (No Calculator) ===\n\n";

$inputData = [
    'adherent_number' => '301261U',
    'num_sinistre' => 48,
    'classe' => 'B',  // Explicit class
    'statut' => 'M',
    'option' => 100,
    'birth_date' => '1960-01-15',
    'current_date' => '2024-01-15',
    'attestation_date' => '2024-01-15',
    'nb_trimestres' => 22,
    'arrets' => [
        [
            'arret-from-line' => '2023-12-03',
            'arret-to-line' => '2024-01-10',
            'rechute-line' => 0,
            'dt-line' => 0,
            'gpm-member-line' => 0,
            'declaration-date-line' => '2023-12-03',
            'id' => 1
        ]
    ]
];

$result = $calculator->calculateAmount($inputData);

// Generate recap WITHOUT calculator (old behavior)
$recapService = new RecapService();
// Don't call setCalculator()
$recapRecords = $recapService->generateRecapRecords($result, $inputData);

$classeInRecap = $recapRecords[0]['classe'] ?? null;

echo "Test: Without calculator injection, explicit classe should work\n";
echo "  Expected classe: B\n";
echo "  Got classe:      {$classeInRecap}\n";

if ($classeInRecap === 'B') {
    echo "  ✓ PASS (Backward compatibility maintained)\n\n";
    $passed++;
} else {
    echo "  ✗ FAIL\n\n";
    $failed++;
}

echo "=== Test Summary ===\n";
echo "Total:  " . (count($tests) + 1) . " tests\n";
echo "Passed: {$passed} tests\n";
echo "Failed: {$failed} tests\n";

if ($failed === 0) {
    echo "\n✓ All tests passed!\n";
} else {
    echo "\n✗ Some tests failed!\n";
    exit(1);
}
