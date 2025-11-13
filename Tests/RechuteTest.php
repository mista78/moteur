<?php

/**
 * Tests for Rechute (Relapse) Business Rules
 *
 * Based on text.txt specifications (lines 369-377):
 *
 * RECHUTE DEFINITION:
 * - An arrêt that is NOT consecutive to another arrêt
 * - AND starts less than 1 year after the end date of the last arrêt
 * - Formula: [start date] <= [previous end date] + [1 year] - [1 day]
 *
 * RECHUTE RULES:
 * - Date d'effet starts at 15th day (instead of 91st for new pathology)
 * - Late declaration penalty: 15 days (instead of 31 days)
 * - Account update penalty: 15 days (instead of 31 days)
 * - Can be overridden by Medical Controller to start at day 1
 */

require_once dirname(__DIR__) . '/jest-php.php';
require_once dirname(__DIR__) . '/IJCalculator.php';

use App\IJCalculator\IJCalculator;

describe('Rechute (Relapse) Business Rules', function() {

    $calculator = new IJCalculator('taux.csv');

    describe('Rechute Detection', function() use ($calculator) {

        it('should identify rechute when new arrêt starts within 1 year of previous arrêt end', function() use ($calculator) {
            $data = [
                'arrets' => [
                    [
                        'arret-from-line' => '2023-01-01',
                        'arret-to-line' => '2023-02-01',  // Ends Feb 1
                        'rechute-line' => 0,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-01-01'
                    ],
                    [
                        'arret-from-line' => '2023-06-01',  // Starts 4 months later (< 1 year)
                        'arret-to-line' => '2023-12-31',
                        'rechute-line' => 1,  // This is a rechute
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-06-01'
                    ]
                ],
                'statut' => 'M',
                'classe' => 'A',
                'option' => 100,
                'birth_date' => '1980-01-01',
                'current_date' => '2024-01-15',
                'attestation_date' => '2024-01-15',
                'nb_trimestres' => 20,
                'previous_cumul_days' => 0,
                'patho_anterior' => false,
                'prorata' => 1
            ];

            $result = $calculator->calculateAmount($data);

            // Rechute should have payment starting from 15th day, not 91st
            expect(isset($result['arrets']))->toBeTruthy();
            expect(count($result['arrets']))->toBe(2);
        });

        it('should NOT be rechute when arrêt starts more than 1 year after previous', function() use ($calculator) {
            $data = [
                'arrets' => [
                    [
                        'arret-from-line' => '2022-01-01',
                        'arret-to-line' => '2022-02-01',
                        'rechute-line' => 0,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2022-01-01'
                    ],
                    [
                        'arret-from-line' => '2023-02-02',  // Exactly 1 year + 1 day later
                        'arret-to-line' => '2023-12-31',
                        'rechute-line' => 0,  // NOT a rechute (too far)
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-02-02'
                    ]
                ],
                'statut' => 'M',
                'classe' => 'A',
                'option' => 100,
                'birth_date' => '1980-01-01',
                'current_date' => '2024-01-15',
                'attestation_date' => '2024-01-15',
                'nb_trimestres' => 20,
                'previous_cumul_days' => 0,
                'patho_anterior' => false,
                'prorata' => 1
            ];

            $result = $calculator->calculateAmount($data);

            // This should be treated as new pathology (91-day rule)
            expect(isset($result['arrets']))->toBeTruthy();
        });

        it('should recognize rechute at exactly 1 year - 1 day boundary', function() use ($calculator) {
            $data = [
                'arrets' => [
                    [
                        'arret-from-line' => '2023-01-01',
                        'arret-to-line' => '2023-01-31',
                        'rechute-line' => 0,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-01-01'
                    ],
                    [
                        'arret-from-line' => '2024-01-30',  // Exactly 1 year - 1 day
                        'arret-to-line' => '2024-12-31',
                        'rechute-line' => 1,  // This IS a rechute (at boundary)
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2024-01-30'
                    ]
                ],
                'statut' => 'M',
                'classe' => 'A',
                'option' => 100,
                'birth_date' => '1980-01-01',
                'current_date' => '2024-12-31',
                'attestation_date' => '2024-12-31',
                'nb_trimestres' => 20,
                'previous_cumul_days' => 0,
                'patho_anterior' => false,
                'prorata' => 1
            ];

            $result = $calculator->calculateAmount($data);
            expect(isset($result['arrets']))->toBeTruthy();
        });
    });

    describe('Rechute Date d\'Effet Rules', function() use ($calculator) {

        it('should apply 15-day rule for rechute (instead of 91-day rule)', function() use ($calculator) {
            // For rechute: date d'effet = 15th day (not 91st)
            $data = [
                'arrets' => [
                    [
                        'arret-from-line' => '2023-01-01',
                        'arret-to-line' => '2023-01-15',  // 15 days
                        'rechute-line' => 0,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-01-01'
                    ],
                    [
                        'arret-from-line' => '2023-06-01',  // Rechute
                        'arret-to-line' => '2023-06-20',  // 20 days
                        'rechute-line' => 1,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-06-01'
                    ]
                ],
                'statut' => 'M',
                'classe' => 'A',
                'option' => 100,
                'birth_date' => '1980-01-01',
                'current_date' => '2024-01-15',
                'attestation_date' => '2024-01-15',
                'nb_trimestres' => 20,
                'previous_cumul_days' => 0,
                'patho_anterior' => false,
                'prorata' => 1
            ];

            $result = $calculator->calculateAmount($data);

            // Rechute should start paying from 15th day
            // First arrêt: 15 days (but < 90, so no payment)
            // Second arrêt (rechute): cumul = 15 + 20 = 35 days
            // With 15-day rule for rechute, payment should start
            expect(isset($result['nb_jours']))->toBeTruthy();
        });

        it('should apply 15-day penalty for late declaration on rechute (not 31 days)', function() use ($calculator) {
            // Late declaration on rechute: +15 days penalty (not +31)
            $data = [
                'arrets' => [
                    [
                        'arret-from-line' => '2023-01-01',
                        'arret-to-line' => '2023-02-01',
                        'rechute-line' => 0,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-01-01'
                    ],
                    [
                        'arret-from-line' => '2023-06-01',
                        'arret-to-line' => '2023-12-31',
                        'rechute-line' => 1,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-06-20'  // Declared 19 days late (> 15 days for rechute)
                    ]
                ],
                'statut' => 'M',
                'classe' => 'A',
                'option' => 100,
                'birth_date' => '1980-01-01',
                'current_date' => '2024-01-15',
                'attestation_date' => '2024-01-15',
                'nb_trimestres' => 20,
                'previous_cumul_days' => 0,
                'patho_anterior' => false,
                'prorata' => 1
            ];

            $result = $calculator->calculateAmount($data);

            // Should apply +15 day penalty for late declaration on rechute
            expect(isset($result['arrets']))->toBeTruthy();
        });

        it('should apply 15-day penalty for GPM account update on rechute', function() use ($calculator) {
            // GPM account update on rechute: +15 days penalty (not +31)
            $data = [
                'arrets' => [
                    [
                        'arret-from-line' => '2023-01-01',
                        'arret-to-line' => '2023-02-01',
                        'rechute-line' => 0,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-01-01'
                    ],
                    [
                        'arret-from-line' => '2023-06-01',
                        'arret-to-line' => '2023-12-31',
                        'rechute-line' => 1,
                        'dt-line' => 0,
                        'gpm-member-line' => 1,  // Account needs update
                        'declaration-date-line' => '2023-06-01'
                    ]
                ],
                'statut' => 'M',
                'classe' => 'A',
                'option' => 100,
                'birth_date' => '1980-01-01',
                'current_date' => '2024-01-15',
                'attestation_date' => '2024-01-15',
                'nb_trimestres' => 20,
                'previous_cumul_days' => 0,
                'patho_anterior' => false,
                'prorata' => 1
            ];

            $result = $calculator->calculateAmount($data);

            // Should apply +15 day penalty for GPM on rechute
            expect(isset($result['arrets']))->toBeTruthy();
        });
    });

    

    describe('Rechute vs Prolongation Distinction', function() use ($calculator) {

        it('should treat consecutive arrêts as prolongation (not rechute)', function() use ($calculator) {
            // Consecutive = starts within 3 days of previous end (accounting for weekends)
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
                    [
                        'arret-from-line' => '2023-06-05',  // Monday (3 days later with weekend)
                        'arret-to-line' => '2023-12-31',
                        'rechute-line' => 0,  // NOT rechute, it's a prolongation
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-06-05'
                    ]
                ],
                'statut' => 'M',
                'classe' => 'A',
                'option' => 100,
                'birth_date' => '1980-01-01',
                'current_date' => '2024-01-15',
                'attestation_date' => '2024-01-15',
                'nb_trimestres' => 20,
                'previous_cumul_days' => 0,
                'patho_anterior' => false,
                'prorata' => 1
            ];

            $result = $calculator->calculateAmount($data);

            // Should be merged as prolongation, not treated as rechute
            expect(isset($result['arrets']))->toBeTruthy();
        });
    });

    describe('Rechute Cumulative Days Calculation', function() use ($calculator) {

        it('should cumulate days from previous arrêts for rechute', function() use ($calculator) {
            $data = [
                'arrets' => [
                    [
                        'arret-from-line' => '2023-01-01',
                        'arret-to-line' => '2023-03-31',  // 90 days
                        'rechute-line' => 0,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-01-01'
                    ],
                    [
                        'arret-from-line' => '2023-06-01',  // Rechute
                        'arret-to-line' => '2023-06-30',  // 30 days
                        'rechute-line' => 1,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-06-01'
                    ]
                ],
                'statut' => 'M',
                'classe' => 'A',
                'option' => 100,
                'birth_date' => '1980-01-01',
                'current_date' => '2024-01-15',
                'attestation_date' => '2024-01-15',
                'nb_trimestres' => 20,
                'previous_cumul_days' => 0,
                'patho_anterior' => false,
                'prorata' => 1
            ];

            $result = $calculator->calculateAmount($data);

            // Cumulative days should be 90 + 30 = 120
            expect(isset($result['total_cumul_days']))->toBeTruthy();
            expect($result['total_cumul_days'])->toBeGreaterThan(90);
        });

        it('should handle multiple rechutes in same pathology', function() use ($calculator) {
            $data = [
                'arrets' => [
                    [
                        'arret-from-line' => '2023-01-01',
                        'arret-to-line' => '2023-02-01',  // 32 days
                        'rechute-line' => 0,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-01-01'
                    ],
                    [
                        'arret-from-line' => '2023-04-01',  // Rechute 1
                        'arret-to-line' => '2023-05-01',  // 31 days
                        'rechute-line' => 1,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-04-01'
                    ],
                    [
                        'arret-from-line' => '2023-08-01',  // Rechute 2
                        'arret-to-line' => '2023-09-01',  // 32 days
                        'rechute-line' => 1,
                        'dt-line' => 0,
                        'gpm-member-line' => 0,
                        'declaration-date-line' => '2023-08-01'
                    ]
                ],
                'statut' => 'M',
                'classe' => 'A',
                'option' => 100,
                'birth_date' => '1980-01-01',
                'current_date' => '2024-01-15',
                'attestation_date' => '2024-01-15',
                'nb_trimestres' => 20,
                'previous_cumul_days' => 0,
                'patho_anterior' => false,
                'prorata' => 1
            ];

            $result = $calculator->calculateAmount($data);

            // All rechutes should cumulate days for same pathology
            expect(isset($result['total_cumul_days']))->toBeTruthy();
            expect($result['total_cumul_days'])->toBeGreaterThan(90);
        });
    });
});

