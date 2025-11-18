<?php
/**
 * Test DateNormalizer with various input sources
 * - DateTime objects (from ORM)
 * - JSON strings (from API)
 * - Various date formats
 */

require_once 'Services/DateNormalizer.php';

use App\IJCalculator\Services\DateNormalizer;

echo "=== DateNormalizer Test Suite ===\n\n";

$tests = [
    'DateTime Object' => function() {
        $input = [
            'birth_date' => new DateTime('1960-01-15'),
            'current_date' => new DateTime('2024-01-15'),
            'arrets' => [
                [
                    'arret-from-line' => new DateTime('2023-09-04'),
                    'arret-to-line' => new DateTime('2023-11-10'),
                ]
            ]
        ];

        $result = DateNormalizer::normalize($input);

        return [
            'birth_date' => $result['birth_date'] === '1960-01-15',
            'current_date' => $result['current_date'] === '2024-01-15',
            'arret_from' => $result['arrets'][0]['arret-from-line'] === '2023-09-04',
            'arret_to' => $result['arrets'][0]['arret-to-line'] === '2023-11-10',
        ];
    },

    'String Dates (ISO format)' => function() {
        $input = [
            'birth_date' => '1960-01-15',
            'current_date' => '2024-01-15',
            'arrets' => [
                [
                    'arret-from-line' => '2023-09-04',
                    'arret-to-line' => '2023-11-10',
                ]
            ]
        ];

        $result = DateNormalizer::normalize($input);

        return [
            'birth_date' => $result['birth_date'] === '1960-01-15',
            'current_date' => $result['current_date'] === '2024-01-15',
            'arret_from' => $result['arrets'][0]['arret-from-line'] === '2023-09-04',
            'arret_to' => $result['arrets'][0]['arret-to-line'] === '2023-11-10',
        ];
    },

    'Null and Empty Dates' => function() {
        $input = [
            'birth_date' => null,
            'current_date' => '',
            'attestation_date' => '0000-00-00',
            'arrets' => [
                [
                    'arret-from-line' => '2023-09-04',
                    'date_deb_droit' => null,
                ]
            ]
        ];

        $result = DateNormalizer::normalize($input);

        return [
            'birth_date_null' => $result['birth_date'] === null,
            'current_date_null' => $result['current_date'] === null,
            'attestation_null' => $result['attestation_date'] === null,
            'date_deb_droit_null' => $result['arrets'][0]['date_deb_droit'] === null,
        ];
    },

    'Various Date Formats' => function() {
        $input = [
            'date1' => '2024-01-15',         // ISO format
            'date2' => '15/01/2024',         // European format
            'date3' => '2024/01/15',         // Slash format
            'date4' => '15-01-2024',         // European dash
        ];

        // Only normalize known date fields
        $testData = [
            'birth_date' => '15/01/2024',
            'affiliation_date' => '2024/01/15',
        ];

        $result = DateNormalizer::normalize($testData);

        return [
            'birth_date' => $result['birth_date'] === '2024-01-15',
            'affiliation_date' => $result['affiliation_date'] === '2024-01-15',
        ];
    },

    'Mixed Data Types' => function() {
        $input = [
            'birth_date' => new DateTime('1960-01-15'),
            'current_date' => '2024-01-15',
            'attestation_date' => null,
            'nb_trimestres' => 22,
            'statut' => 'M',
            'arrets' => [
                [
                    'arret-from-line' => new DateTime('2023-09-04'),
                    'arret-to-line' => '2023-11-10',
                    'rechute-line' => 0,
                    'dt-line' => 1,
                ]
            ]
        ];

        $result = DateNormalizer::normalize($input);

        return [
            'birth_date' => $result['birth_date'] === '1960-01-15',
            'current_date' => $result['current_date'] === '2024-01-15',
            'attestation_null' => $result['attestation_date'] === null,
            'nb_trimestres' => $result['nb_trimestres'] === 22,
            'statut' => $result['statut'] === 'M',
            'arret_from' => $result['arrets'][0]['arret-from-line'] === '2023-09-04',
            'arret_to' => $result['arrets'][0]['arret-to-line'] === '2023-11-10',
            'rechute' => $result['arrets'][0]['rechute-line'] === 0,
            'dt' => $result['arrets'][0]['dt-line'] === 1,
        ];
    },

    'Deep Nested Arrays' => function() {
        $input = [
            'arrets' => [
                [
                    'arret-from-line' => '2023-01-01',
                    'payment_details' => [
                        'payment_start' => new DateTime('2023-02-01'),
                        'payment_end' => '2023-03-01',
                    ]
                ],
                [
                    'arret-from-line' => new DateTime('2023-06-01'),
                    'date_deb_droit' => '2023-07-01',
                ]
            ]
        ];

        $result = DateNormalizer::normalize($input);

        return [
            'arret0_from' => $result['arrets'][0]['arret-from-line'] === '2023-01-01',
            'payment_start' => $result['arrets'][0]['payment_details']['payment_start'] === '2023-02-01',
            'payment_end' => $result['arrets'][0]['payment_details']['payment_end'] === '2023-03-01',
            'arret1_from' => $result['arrets'][1]['arret-from-line'] === '2023-06-01',
            'date_deb_droit' => $result['arrets'][1]['date_deb_droit'] === '2023-07-01',
        ];
    },

    'ORM-like Object (simulated)' => function() {
        // Simulate an ORM entity with private properties
        $entity = new class {
            private $birth_date;
            private $affiliation_date;
            private $statut = 'M';

            public function __construct() {
                $this->birth_date = new DateTime('1960-01-15');
                $this->affiliation_date = new DateTime('2019-01-15');
            }

            public function toArray() {
                return [
                    'birth_date' => $this->birth_date,
                    'affiliation_date' => $this->affiliation_date,
                    'statut' => $this->statut,
                ];
            }
        };

        $result = DateNormalizer::normalize($entity);

        return [
            'birth_date' => $result['birth_date'] === '1960-01-15',
            'affiliation_date' => $result['affiliation_date'] === '2019-01-15',
            'statut' => $result['statut'] === 'M',
        ];
    },
];

// Run tests
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

foreach ($tests as $testName => $testFunc) {
    echo "Testing: {$testName}\n";

    try {
        $results = $testFunc();
        $allPassed = true;

        foreach ($results as $assertion => $passed) {
            $totalTests++;
            if ($passed) {
                $passedTests++;
                echo "  ✓ {$assertion}\n";
            } else {
                $failedTests++;
                $allPassed = false;
                echo "  ✗ {$assertion}\n";
            }
        }

        if ($allPassed) {
            echo "  ✓ ALL PASSED\n";
        } else {
            echo "  ✗ SOME FAILED\n";
        }
    } catch (Exception $e) {
        $failedTests++;
        echo "  ✗ EXCEPTION: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

// Summary
echo "=== Test Summary ===\n";
echo "Total assertions: {$totalTests}\n";
echo "Passed: {$passedTests} (" . round($passedTests / $totalTests * 100, 2) . "%)\n";
echo "Failed: {$failedTests}\n";

if ($failedTests === 0) {
    echo "\n✓ ALL TESTS PASSED!\n";
} else {
    echo "\n✗ SOME TESTS FAILED!\n";
}
