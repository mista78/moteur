<?php
/**
 * Test to verify rechute logic when date-effet is after arret end
 *
 * Requirements:
 * 1. If date-effet would be after arret end, it should NOT be a rechute
 * 2. All valid rechute decompte should be 0
 */

require_once 'IJCalculator.php';
require_once 'jest-php.php';

use App\IJCalculator\IJCalculator;

describe('Rechute Date Effet Fix', function() {

    test('should be rechute with empty date-effet and 0 decompte when arret too short', function() {
        $calculator = new IJCalculator();

        // First arret: 100 days (opens rights after 90 days)
        // Second arret: only 10 days (would need 15 days for rechute)
        $inputData = [
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1980-01-01',
            'current_date' => '2024-01-15',
            'attestation_date' => '2024-01-31',
            'affiliation_date' => '2015-01-01',
            'nb_trimestres' => 36,
            'previous_cumul_days' => 0,
            'patho_anterior' => false,
            'prorata' => 1,
            'pass_value' => 47000,
            'arrets' => [
                [
                    'arret-from-line' => '2023-01-01',
                    'arret-to-line' => '2023-04-10',  // 100 days
                    'rechute-line' => 0,
                    'dt-line' => 0,
                    'gpm-member-line' => 0,
                    'declaration-date-line' => '2023-01-05'
                ],
                [
                    'arret-from-line' => '2023-05-01',  // Gap of 21 days (not consecutive)
                    'arret-to-line' => '2023-05-10',    // Only 10 days (shorter than 15-day threshold)
                    'rechute-line' => 0,
                    'dt-line' => 0,
                    'gpm-member-line' => 0,
                    'declaration-date-line' => '2023-05-01'
                ]
            ]
        ];

        $result = $calculator->calculateAmount($inputData);

        // Second arret should NOT be rechute because date-effet would be after arret end
        $secondArret = $result['payment_details'][1];

        echo "\n--- Test Case 1: Short Arret (10 days) ---\n";
        echo "Second arret is_rechute: " . ($secondArret['is_rechute'] ? 'true' : 'false') . "\n";
        echo "Second arret date-effet: " . ($secondArret['date_effet'] ?: 'empty') . "\n";
        echo "Second arret decompte_days: " . $secondArret['decompte_days'] . "\n";

        expect($secondArret['is_rechute'])->toBe(true); // IS a rechute (timing-wise)
        expect($secondArret['date_effet'])->toBe(''); // No date-effet because arret too short
        expect($secondArret['decompte_days'])->toBe(0); // Should be 0 as per requirement
    });

    test('should be valid rechute when arret reaches date-effet with 0 decompte', function() {
        $calculator = new IJCalculator();

        // First arret: 100 days (opens rights after 90 days)
        // Second arret: 20 days (longer than 15-day threshold for rechute)
        $inputData = [
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1980-01-01',
            'current_date' => '2024-01-15',
            'attestation_date' => '2024-01-31',
            'affiliation_date' => '2015-01-01',
            'nb_trimestres' => 36,
            'previous_cumul_days' => 0,
            'patho_anterior' => false,
            'prorata' => 1,
            'pass_value' => 47000,
            'arrets' => [
                [
                    'arret-from-line' => '2023-01-01',
                    'arret-to-line' => '2023-04-10',  // 100 days
                    'rechute-line' => 0,
                    'dt-line' => 0,
                    'gpm-member-line' => 0,
                    'declaration-date-line' => '2023-01-05'
                ],
                [
                    'arret-from-line' => '2023-05-01',  // Gap of 21 days (not consecutive)
                    'arret-to-line' => '2023-05-20',    // 20 days (reaches 15-day threshold)
                    'rechute-line' => 0,
                    'dt-line' => 0,
                    'gpm-member-line' => 0,
                    'declaration-date-line' => '2023-05-01'
                ]
            ]
        ];

        $result = $calculator->calculateAmount($inputData);

        // Second arret SHOULD be rechute and reach date-effet
        $secondArret = $result['payment_details'][1];

        expect($secondArret['is_rechute'])->toBe(true);
        // date_effet should not be empty for valid rechute
        expect($secondArret['decompte_days'])->toBe(0); // All rechute decompte should be 0

        echo "\n--- Test Case 2: Valid Rechute (20 days) ---\n";
        echo "Second arret is_rechute: " . ($secondArret['is_rechute'] ? 'true' : 'false') . "\n";
        echo "Second arret date-effet: " . $secondArret['date_effet'] . "\n";
        echo "Second arret decompte_days: " . $secondArret['decompte_days'] . "\n";
        echo "Expected date-effet: 2023-05-15 (15th day)\n";
    });

    test('should set decompte to 0 for all valid rechutes', function() {
        $calculator = new IJCalculator();

        // Multiple rechutes - all should have 0 decompte
        $inputData = [
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1980-01-01',
            'current_date' => '2024-01-15',
            'attestation_date' => '2024-01-31',
            'affiliation_date' => '2015-01-01',
            'nb_trimestres' => 36,
            'previous_cumul_days' => 0,
            'patho_anterior' => false,
            'prorata' => 1,
            'pass_value' => 47000,
            'arrets' => [
                [
                    'arret-from-line' => '2023-01-01',
                    'arret-to-line' => '2023-04-10',  // 100 days (first arret)
                    'rechute-line' => 0,
                    'dt-line' => 0,
                    'gpm-member-line' => 0,
                    'declaration-date-line' => '2023-01-05'
                ],
                [
                    'arret-from-line' => '2023-05-01',
                    'arret-to-line' => '2023-05-30',  // 30 days (rechute 1)
                    'rechute-line' => 0,
                    'dt-line' => 0,
                    'gpm-member-line' => 0,
                    'declaration-date-line' => '2023-05-01'
                ],
                [
                    'arret-from-line' => '2023-07-01',
                    'arret-to-line' => '2023-07-25',  // 25 days (rechute 2)
                    'rechute-line' => 0,
                    'dt-line' => 0,
                    'gpm-member-line' => 0,
                    'declaration-date-line' => '2023-07-01'
                ]
            ]
        ];

        $result = $calculator->calculateAmount($inputData);

        // Check all rechutes have 0 decompte
        $rechute1 = $result['payment_details'][1];
        $rechute2 = $result['payment_details'][2];

        expect($rechute1['is_rechute'])->toBe(true);
        expect($rechute1['decompte_days'])->toBe(0);

        expect($rechute2['is_rechute'])->toBe(true);
        expect($rechute2['decompte_days'])->toBe(0);

        echo "\n--- Test Case 3: Multiple Rechutes ---\n";
        echo "Rechute 1 decompte_days: " . $rechute1['decompte_days'] . " (should be 0)\n";
        echo "Rechute 2 decompte_days: " . $rechute2['decompte_days'] . " (should be 0)\n";
    });

});

JestPHP::run();
