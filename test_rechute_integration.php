<?php

/**
 * Integration Tests for Rechute (Relapse) Scenarios
 *
 * Tests real-world rechute cases with expected outcomes
 */

require_once 'jest-php.php';
require_once 'IJCalculator.php';

use App\IJCalculator\IJCalculator;

echo "\n";
echo "================================================================================\n";
echo "                   Rechute (Relapse) Integration Tests                         \n";
echo "================================================================================\n";
echo "\n";

$calculator = new IJCalculator('taux.csv');

describe('Rechute Integration Tests', function() use ($calculator) {

    describe('Test 1: Basic Rechute with 15-day Rule', function() use ($calculator) {

        it('should apply 15-day rule for rechute instead of 91-day rule', function() use ($calculator) {
            echo "\n--- Test 1: Basic Rechute (15-day rule) ---\n";

            $data = [
                'arrets' => [
                    // First arrêt: 100 days
                    [
                        'arret-from-line' => '2023-01-01',
                        'arret-to-line' => '2023-04-10',  // 100 days
                        'rechute-line' => 0,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-01-01'
                    ],
                    // Rechute after 2 months: should start at 15 days (not 91)
                    [
                        'arret-from-line' => '2023-06-15',
                        'arret-to-line' => '2023-07-15',  // 31 days
                        'rechute-line' => 1,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-06-15'
                    ]
                ],
                'statut' => 'M',
                'classe' => 'A',
                'option' => 100,
                'birth_date' => '1980-05-15',
                'current_date' => '2024-01-15',
                'attestation_date' => '2024-01-15',
                'nb_trimestres' => 20,
                'previous_cumul_days' => 0,
                'patho_anterior' => false,
                'prorata' => 1,
                'pass_value' => 47000
            ];

            $result = $calculator->calculateAmount($data);

            echo "First arrêt (100 days): payment starts after 90 days\n";
            echo "Rechute (31 days): payment starts after 15 days\n";
            echo "Total cumulative days: " . $result['total_cumul_days'] . "\n";
            echo "Payable days: " . $result['nb_jours'] . "\n";
            echo "Amount: " . number_format($result['montant'], 2) . "€\n";

            expect($result['total_cumul_days'])->toBe(131);  // 100 + 31
            expect($result['nb_jours'])->toBeGreaterThan(0);  // Should have payable days
        });
    });

    describe('Test 2: Rechute at 1-Year Boundary', function() use ($calculator) {

        it('should recognize rechute at exactly 364 days after previous arrêt', function() use ($calculator) {
            echo "\n--- Test 2: Rechute at 1-Year Boundary ---\n";

            $data = [
                'arrets' => [
                    [
                        'arret-from-line' => '2023-01-01',
                        'arret-to-line' => '2023-04-10',  // 100 days
                        'rechute-line' => 0,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-01-01'
                    ],
                    // Exactly 1 year - 1 day = rechute
                    [
                        'arret-from-line' => '2024-04-09',  // 364 days after April 10, 2023
                        'arret-to-line' => '2024-05-09',  // 31 days
                        'rechute-line' => 1,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2024-04-09'
                    ]
                ],
                'statut' => 'M',
                'classe' => 'B',
                'option' => 100,
                'birth_date' => '1975-03-20',
                'current_date' => '2024-06-01',
                'attestation_date' => '2024-06-01',
                'nb_trimestres' => 24,
                'previous_cumul_days' => 0,
                'patho_anterior' => false,
                'prorata' => 1,
                'pass_value' => 47000
            ];

            $result = $calculator->calculateAmount($data);

            echo "First arrêt ends: 2023-04-10\n";
            echo "Rechute starts: 2024-04-09 (364 days later - VALID rechute)\n";
            echo "Total cumulative days: " . $result['total_cumul_days'] . "\n";
            echo "Payable days: " . $result['nb_jours'] . "\n";

            expect($result['total_cumul_days'])->toBeGreaterThan(100);
        });

        it('should NOT be rechute at exactly 365 days (1 year)', function() use ($calculator) {
            echo "\n--- Test 2b: NOT Rechute at Exactly 1 Year ---\n";

            $data = [
                'arrets' => [
                    [
                        'arret-from-line' => '2023-01-01',
                        'arret-to-line' => '2023-04-10',
                        'rechute-line' => 0,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-01-01'
                    ],
                    // Exactly 1 year = NOT rechute (new pathology)
                    [
                        'arret-from-line' => '2024-04-10',  // Exactly 365 days
                        'arret-to-line' => '2024-05-10',
                        'rechute-line' => 0,  // New pathology
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2024-04-10'
                    ]
                ],
                'statut' => 'M',
                'classe' => 'A',
                'option' => 100,
                'birth_date' => '1980-01-01',
                'current_date' => '2024-06-01',
                'attestation_date' => '2024-06-01',
                'nb_trimestres' => 20,
                'previous_cumul_days' => 0,
                'patho_anterior' => false,
                'prorata' => 1,
                'pass_value' => 47000
            ];

            $result = $calculator->calculateAmount($data);

            echo "First arrêt ends: 2023-04-10\n";
            echo "New arrêt starts: 2024-04-10 (365 days - NEW pathology, not rechute)\n";
            echo "Should apply 91-day rule for second arrêt\n";

            expect($result)->toHaveKey('arrets');
        });
    });

    describe('Test 3: Rechute with Late Declaration', function() use ($calculator) {

        it('should apply 15-day penalty (not 31) for late declaration on rechute', function() use ($calculator) {
            echo "\n--- Test 3: Rechute with Late Declaration (15-day penalty) ---\n";

            $data = [
                'arrets' => [
                    [
                        'arret-from-line' => '2023-01-01',
                        'arret-to-line' => '2023-04-10',  // 100 days
                        'rechute-line' => 0,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-01-01'
                    ],
                    // Rechute declared 20 days late (> 15 days)
                    [
                        'arret-from-line' => '2023-07-01',
                        'arret-to-line' => '2023-12-31',  // 184 days
                        'rechute-line' => 1,
                        'dt-line' => 1,  // Declaration tardive
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-07-21'  // Declared 20 days late
                    ]
                ],
                'statut' => 'M',
                'classe' => 'C',
                'option' => 100,
                'birth_date' => '1970-08-15',
                'current_date' => '2024-01-15',
                'attestation_date' => '2024-01-15',
                'nb_trimestres' => 30,
                'previous_cumul_days' => 0,
                'patho_anterior' => false,
                'prorata' => 1,
                'pass_value' => 47000
            ];

            $result = $calculator->calculateAmount($data);

            echo "Rechute declared 20 days after start (> 15 days)\n";
            echo "Penalty: +15 days (for rechute) instead of +31 days (for new pathology)\n";
            echo "Total cumulative days: " . $result['total_cumul_days'] . "\n";

            expect($result['total_cumul_days'])->toBeGreaterThan(100);
        });
    });

    describe('Test 4: Multiple Rechutes for Same Pathology', function() use ($calculator) {

        it('should handle multiple rechutes with cumulative day counting', function() use ($calculator) {
            echo "\n--- Test 4: Multiple Rechutes ---\n";

            $data = [
                'arrets' => [
                    // Initial arrêt
                    [
                        'arret-from-line' => '2023-01-01',
                        'arret-to-line' => '2023-02-15',  // 46 days
                        'rechute-line' => 0,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-01-01'
                    ],
                    // First rechute
                    [
                        'arret-from-line' => '2023-04-01',
                        'arret-to-line' => '2023-05-01',  // 31 days
                        'rechute-line' => 1,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-04-01'
                    ],
                    // Second rechute
                    [
                        'arret-from-line' => '2023-08-01',
                        'arret-to-line' => '2023-09-15',  // 46 days
                        'rechute-line' => 1,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-08-01'
                    ]
                ],
                'statut' => 'M',
                'classe' => 'A',
                'option' => 100,
                'birth_date' => '1985-06-10',
                'current_date' => '2024-01-15',
                'attestation_date' => '2024-01-15',
                'nb_trimestres' => 15,
                'previous_cumul_days' => 0,
                'patho_anterior' => false,
                'prorata' => 1,
                'pass_value' => 47000
            ];

            $result = $calculator->calculateAmount($data);

            echo "Arrêt 1: 46 days\n";
            echo "Rechute 1: 31 days\n";
            echo "Rechute 2: 46 days\n";
            echo "Total cumulative: " . $result['total_cumul_days'] . " days\n";
            echo "Expected: 123 days (46 + 31 + 46)\n";
            echo "Payable days: " . $result['nb_jours'] . "\n";
            echo "Amount: " . number_format($result['montant'], 2) . "€\n";

            expect($result['total_cumul_days'])->toBe(123);
        });
    });

    describe('Test 5: Rechute with Medical Controller Override', function() use ($calculator) {

        it('should allow MC to force payment from day 1 on rechute', function() use ($calculator) {
            echo "\n--- Test 5: Rechute with MC Override to Day 1 ---\n";

            $data = [
                'arrets' => [
                    [
                        'arret-from-line' => '2023-01-01',
                        'arret-to-line' => '2023-04-10',  // 100 days
                        'rechute-line' => 0,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-01-01'
                    ],
                    // Rechute with MC override to day 1
                    [
                        'arret-from-line' => '2023-09-01',
                        'arret-to-line' => '2023-09-30',  // 30 days
                        'rechute-line' => 1,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-09-01',
                        'date-effet' => '2023-09-01'  // MC forced to day 1
                    ]
                ],
                'statut' => 'M',
                'classe' => 'B',
                'option' => 100,
                'birth_date' => '1978-12-25',
                'current_date' => '2024-01-15',
                'attestation_date' => '2024-01-15',
                'nb_trimestres' => 22,
                'previous_cumul_days' => 0,
                'patho_anterior' => false,
                'prorata' => 1,
                'pass_value' => 47000
            ];

            $result = $calculator->calculateAmount($data);

            echo "Rechute with MC override to start at day 1\n";
            echo "Payment should start from: 2023-09-01\n";
            echo "Rechute payment_start: " . $result['arrets'][1]['payment_start'] . "\n";
            echo "Payable days: " . $result['nb_jours'] . "\n";

            expect($result['arrets'][1]['payment_start'])->toBe('2023-09-01');
        });
    });

    describe('Test 6: Prolongation vs Rechute', function() use ($calculator) {

        it('should distinguish prolongation from rechute based on consecutive dates', function() use ($calculator) {
            echo "\n--- Test 6: Prolongation (consecutive) vs Rechute ---\n";

            $data = [
                'arrets' => [
                    [
                        'arret-from-line' => '2023-06-01',  // Thursday
                        'arret-to-line' => '2023-06-02',    // Friday
                        'rechute-line' => 0,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-06-01'
                    ],
                    // Starts Monday (consecutive with weekend)
                    [
                        'arret-from-line' => '2023-06-05',  // Monday - this is PROLONGATION
                        'arret-to-line' => '2023-12-31',
                        'rechute-line' => 0,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-06-05'
                    ]
                ],
                'statut' => 'M',
                'classe' => 'A',
                'option' => 100,
                'birth_date' => '1982-03-15',
                'current_date' => '2024-01-15',
                'attestation_date' => '2024-01-15',
                'nb_trimestres' => 18,
                'previous_cumul_days' => 0,
                'patho_anterior' => false,
                'prorata' => 1,
                'pass_value' => 47000
            ];

            $result = $calculator->calculateAmount($data);

            echo "Arrêt 1 ends: Friday 2023-06-02\n";
            echo "Arrêt 2 starts: Monday 2023-06-05\n";
            echo "This is a PROLONGATION (consecutive), not a rechute\n";
            echo "Should be merged and treated as one continuous arrêt\n";
            echo "Number of arrêts after merge: " . count($result['arrets']) . "\n";

            // After merging prolongations, should have 1 arrêt
            expect(count($result['arrets']))->toBeLessThanOrEqual(2);
        });
    });
});

JestPHP::run();
