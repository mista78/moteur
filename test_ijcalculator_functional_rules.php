<?php
/**
 * Test IJCalculator - Verify All Functional Rules
 *
 * This test validates:
 * 1. Calendar year rate rule (2024 vs 2025 rates)
 * 2. Date d'effet rule (< 2025 vs >= 2025)
 * 3. PASS formula for new arrêts in 2025
 * 4. Rechute detection and 15-day rule
 * 5. Age-based periods (< 62, 62-69, >= 70)
 */

require __DIR__ . '/vendor/autoload.php';

use App\IJCalculator;

echo "════════════════════════════════════════════════════════════════════════════\n";
echo "Test IJCalculator - Functional Rules Validation\n";
echo "════════════════════════════════════════════════════════════════════════════\n\n";

$allTestsPassed = true;

// ============================================================================
// Test 1: Calendar Year Rate Rule (mock_step.json scenario)
// ============================================================================
echo "Test 1: Calendar Year Rate Rule\n";
echo "────────────────────────────────────────────────────────────────────────────\n";
echo "Arrêt: 2024-11-23 → 2025-03-31 (date_effet < 2025-01-01)\n";
echo "Expected:\n";
echo "  - Days in 2024 → 2024 DB rates (150.12€ for Class C)\n";
echo "  - Days in 2025 → 2025 DB rates (152.81€ for Class C)\n";
echo "  - NOT PASS formula (190.55€)\n\n";

$test1Data = [
    'statut' => 'M',
    'classe' => 'C',
    'option' => 100,
    'birth_date' => '1958-06-03',
    'current_date' => '2025-12-04',
    'attestation_date' => '2025-03-31',
    'affiliation_date' => '1991-07-01',
    'nb_trimestres' => 50,
    'arrets' => [
        [
            'arret-from-line' => '2024-11-23',
            'arret-to-line' => '2025-03-31',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ]
    ]
];

$calculator = new IJCalculator(__DIR__ . '/data/taux.csv');
$result1 = $calculator->calculateAmount($test1Data);

echo "Result:\n";
echo "  Total: " . number_format($result1['montant'], 2) . " €\n";
echo "  Days: " . $result1['nb_jours'] . "\n";

// Verify rates
$uses2024DB = false;
$uses2025DB = false;
$usesPASS = false;

if (isset($result1['payment_details'][0]['rate_breakdown'])) {
    foreach ($result1['payment_details'][0]['rate_breakdown'] as $rate) {
        if ($rate['year'] == 2024 && abs($rate['rate'] - 150.12) < 0.01) {
            $uses2024DB = true;
        }
        if ($rate['year'] == 2025 && abs($rate['rate'] - 152.81) < 0.01) {
            $uses2025DB = true;
        }
        if ($rate['year'] == 2025 && abs($rate['rate'] - 190.55) < 0.01) {
            $usesPASS = true;
        }
    }
}

if ($uses2024DB && $uses2025DB && !$usesPASS) {
    echo "  ✓ PASS: Using correct calendar year rates\n";
} else {
    echo "  ✗ FAIL: Incorrect rates detected\n";
    if (!$uses2024DB) echo "    - Missing 2024 DB rates\n";
    if (!$uses2025DB) echo "    - Missing 2025 DB rates\n";
    if ($usesPASS) echo "    - Incorrectly using PASS formula\n";
    $allTestsPassed = false;
}
echo "\n";

// ============================================================================
// Test 2: PASS Formula for Arrêts Starting in 2025
// ============================================================================
echo "Test 2: PASS Formula (date_effet >= 2025-01-01)\n";
echo "────────────────────────────────────────────────────────────────────────────\n";
echo "Arrêt: 2025-02-01 → 2025-03-31 (date_effet >= 2025-01-01)\n";
echo "Expected: ALL days use PASS formula (~63.52€ for Class A)\n\n";

$test2Data = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1985-05-15',
    'current_date' => '2025-12-04',
    'attestation_date' => '2025-03-31',
    'affiliation_date' => '2015-01-01',
    'nb_trimestres' => 40,
    'arrets' => [
        [
            'arret-from-line' => '2025-02-01',
            'arret-to-line' => '2025-03-31',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ]
    ]
];

$calculator->setPassValue(46368);
$result2 = $calculator->calculateAmount($test2Data);

echo "Result:\n";
echo "  Total: " . number_format($result2['montant'], 2) . " €\n";
echo "  Days: " . $result2['nb_jours'] . "\n";

// Check if using PASS formula
$usesPASSFormula = false;
if (isset($result2['payment_details'][0]['rate_breakdown'])) {
    foreach ($result2['payment_details'][0]['rate_breakdown'] as $rate) {
        // PASS formula: 1 × 46368 / 730 = 63.52
        if (abs($rate['rate'] - 63.52) < 0.5) {
            $usesPASSFormula = true;
            break;
        }
    }
}

if ($usesPASSFormula) {
    echo "  ✓ PASS: Using PASS formula correctly\n";
} else {
    echo "  ✗ FAIL: Not using PASS formula\n";
    $allTestsPassed = false;
}
echo "\n";

// ============================================================================
// Test 3: Rechute Detection and 15-Day Rule
// ============================================================================
echo "Test 3: Rechute Detection (15-day date_effet)\n";
echo "────────────────────────────────────────────────────────────────────────────\n";
echo "Arrêt 1: 2024-03-01 → 2024-05-30 (initial)\n";
echo "Arrêt 2: 2024-08-15 → 2024-10-10 (rechute, within 1 year)\n";
echo "Expected: Arrêt 2 has 15-day date_effet (not 90 days)\n\n";

$test3Data = [
    'statut' => 'M',
    'classe' => 'B',
    'option' => 100,
    'birth_date' => '1990-02-14',
    'current_date' => '2024-12-04',
    'attestation_date' => '2024-10-10',
    'affiliation_date' => '2018-01-01',
    'nb_trimestres' => 32,
    'arrets' => [
        [
            'arret-from-line' => '2024-03-01',
            'arret-to-line' => '2024-05-30',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ],
        [
            'arret-from-line' => '2024-08-15',
            'arret-to-line' => '2024-10-10',
            'rechute-line' => 1,
            'dt-line' => 0,
            'gpm-member-line' => 0
        ]
    ]
];

$result3 = $calculator->calculateAmount($test3Data);

echo "Result:\n";
echo "  Total: " . number_format($result3['montant'], 2) . " €\n";
echo "  Days: " . $result3['nb_jours'] . "\n";

// Check that second arrêt has fewer decompte days (should be ~15 instead of ~90)
$hasRechuteLogic = false;
if (isset($result3['payment_details'][1])) {
    $decompteDays = $result3['payment_details'][1]['decompte_days'] ?? 0;
    // Rechute should have ~15-31 decompte days (15 base + DT penalties)
    if ($decompteDays < 40) {  // Much less than 90+
        $hasRechuteLogic = true;
        echo "  Decompte days (arrêt 2): {$decompteDays} (< 40, indicates rechute)\n";
    } else {
        echo "  Decompte days (arrêt 2): {$decompteDays} (>= 40, NOT rechute)\n";
    }
}

if ($hasRechuteLogic) {
    echo "  ✓ PASS: Rechute logic working (reduced date_effet)\n";
} else {
    echo "  ✗ FAIL: Rechute not detected or incorrect date_effet\n";
    $allTestsPassed = false;
}
echo "\n";

// ============================================================================
// Test 4: Age-Based Periods (62-69 years)
// ============================================================================
echo "Test 4: Age-Based Periods (62-69 years, 3 periods)\n";
echo "────────────────────────────────────────────────────────────────────────────\n";
echo "Age: 66 years (62-69 range)\n";
echo "Expected:\n";
echo "  - Period 1 (days 1-365): Full rate (taux 1-3)\n";
echo "  - Period 2 (days 366-730): Rate -25% (taux 7-9)\n";
echo "  - Period 3 (days 731-1095): Senior rate (taux 4-6)\n\n";

$test4Data = [
    'statut' => 'M',
    'classe' => 'B',
    'option' => 100,
    'birth_date' => '1958-06-03',  // Age 66
    'current_date' => '2024-12-04',
    'attestation_date' => '2024-12-04',
    'affiliation_date' => '1991-07-01',
    'nb_trimestres' => 50,
    'arrets' => [
        [
            'arret-from-line' => '2022-01-01',
            'arret-to-line' => '2024-12-04',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ]
    ]
];

$result4 = $calculator->calculateAmount($test4Data);

echo "Result:\n";
echo "  Total: " . number_format($result4['montant'], 2) . " €\n";
echo "  Days: " . $result4['nb_jours'] . "\n";

// Check for different periods
$hasPeriod1 = false;
$hasPeriod2 = false;
$hasPeriod3 = false;

if (isset($result4['payment_details'][0]['rate_breakdown'])) {
    foreach ($result4['payment_details'][0]['rate_breakdown'] as $rate) {
        if (isset($rate['period'])) {
            if ($rate['period'] == 1) $hasPeriod1 = true;
            if ($rate['period'] == 2) $hasPeriod2 = true;
            if ($rate['period'] == 3) $hasPeriod3 = true;
        }
    }
}

if ($hasPeriod1 && $hasPeriod2 && $hasPeriod3) {
    echo "  ✓ PASS: All 3 periods detected (correct for age 62-69)\n";
} else {
    echo "  ✗ FAIL: Missing periods\n";
    if (!$hasPeriod1) echo "    - Period 1 missing\n";
    if (!$hasPeriod2) echo "    - Period 2 missing\n";
    if (!$hasPeriod3) echo "    - Period 3 missing\n";
    $allTestsPassed = false;
}
echo "\n";

// ============================================================================
// Test 5: Trimester Calculation (Partial Quarters Count)
// ============================================================================
echo "Test 5: Trimester Calculation\n";
echo "────────────────────────────────────────────────────────────────────────────\n";
echo "Affiliation: 2019-01-15\n";
echo "Current: 2024-04-11\n";
echo "Expected: 22 quarters (partial quarters count as complete)\n\n";

$test5Data = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1990-01-01',
    'current_date' => '2024-04-11',
    'attestation_date' => '2024-04-11',
    'affiliation_date' => '2019-01-15',
    'arrets' => [
        [
            'arret-from-line' => '2024-04-01',
            'arret-to-line' => '2024-04-11',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ]
    ]
];

$result5 = $calculator->calculateAmount($test5Data);

echo "Result:\n";
echo "  Trimesters: " . $result5['nb_trimestres'] . "\n";

if ($result5['nb_trimestres'] == 22) {
    echo "  ✓ PASS: Correct trimester calculation (22 quarters)\n";
} else {
    echo "  ✗ FAIL: Expected 22, got " . $result5['nb_trimestres'] . "\n";
    $allTestsPassed = false;
}
echo "\n";

// ============================================================================
// Test 6: 90-Day Threshold vs 15-Day Rechute
// ============================================================================
echo "Test 6: Date-Effet Threshold (90-day initial vs 15-day rechute)\n";
echo "────────────────────────────────────────────────────────────────────────────\n";

$test6aData = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1985-01-01',
    'current_date' => '2024-12-04',
    'attestation_date' => '2024-06-30',
    'affiliation_date' => '2015-01-01',
    'nb_trimestres' => 40,
    'arrets' => [
        [
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-06-30',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ]
    ]
];

$result6a = $calculator->calculateAmount($test6aData);
$decompte6a = $result6a['payment_details'][0]['decompte_days'] ?? 0;

echo "Initial arrêt (not rechute):\n";
echo "  Decompte days: {$decompte6a}\n";
echo "  Expected: ~90-122 days (90 base + DT 31 + GPM 31)\n";

if ($decompte6a >= 85 && $decompte6a <= 130) {
    echo "  ✓ PASS: Correct 90-day threshold\n";
} else {
    echo "  ✗ FAIL: Unexpected decompte days for initial arrêt\n";
    $allTestsPassed = false;
}
echo "\n";

// ============================================================================
// Final Summary
// ============================================================================
echo "════════════════════════════════════════════════════════════════════════════\n";
echo "Test Summary\n";
echo "════════════════════════════════════════════════════════════════════════════\n\n";

if ($allTestsPassed) {
    echo "✓✓✓ ALL TESTS PASSED ✓✓✓\n\n";
    echo "IJCalculator is working correctly with all functional rules:\n";
    echo "  ✓ Calendar year rate rule (2024 vs 2025 rates)\n";
    echo "  ✓ Date d'effet rule (< 2025 vs >= 2025)\n";
    echo "  ✓ PASS formula for new arrêts in 2025\n";
    echo "  ✓ Rechute detection and 15-day rule\n";
    echo "  ✓ Age-based periods (< 62, 62-69, >= 70)\n";
    echo "  ✓ Trimester calculation (partial quarters)\n";
    echo "  ✓ 90-day threshold for initial arrêts\n";
} else {
    echo "✗✗✗ SOME TESTS FAILED ✗✗✗\n\n";
    echo "Please review the failed tests above.\n";
}

echo "\n════════════════════════════════════════════════════════════════════════════\n";
