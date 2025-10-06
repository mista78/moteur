<?php

require_once 'IJCalculator.php';

$calc = new IJCalculator('taux.csv');

echo "=== MOCK2 - Testing if expected uses year-average rates ===\n\n";

// The issue is that for 2022, there are TWO rate periods:
// 2022-01-01 to 2022-06-30: taux_c1=138.00
// 2022-07-01 to 2022-12-31: taux_c1=142.84

$rate2022H1 = $calc->getRateForDate('2022-01-01');
$rate2022H2 = $calc->getRateForDate('2022-12-06');

echo "2022 rates:\n";
echo "H1 (Jan-Jun): taux_c1={$rate2022H1['taux_c1']}\n";
echo "H2 (Jul-Dec): taux_c1={$rate2022H2['taux_c1']}\n";

// If the VBA was using year-based lookup (not date-based), it would use
// whichever rate period it found first for 2022
// Or it might use the H1 rate for all of 2022

echo "\nTest using H1 rate for arrêt 3:\n";
$alt1 = 1 * floatval($rate2022H1['taux_c1']) +
        1 * 146.32 +
        25 * 146.32 +
        91 * 150.12;
echo "1 * {$rate2022H1['taux_c1']} + 1 * 146.32 + 25 * 146.32 + 91 * 150.12 = $alt1\n";
echo "Difference from expected: " . abs($alt1 - 17318.92) . "\n\n";

// Or maybe the VBA uses a different date for arrêt 5?
echo "Checking arrêt 5 breakdown:\n";
echo "Date effet: 2023-12-07\n";
echo "Arrêt end: 2024-03-31\n";
echo "Current date (attestation): 2024-06-12\n\n";

// Maybe the VBA counts days differently - let's try
$days2023 = (new DateTime('2023-12-31'))->diff(new DateTime('2023-12-07'))->days + 1;
$days2024 = (new DateTime('2024-03-31'))->diff(new DateTime('2024-01-01'))->days + 1;
echo "Days in 2023: $days2023 (Dec 7-31)\n";
echo "Days in 2024: $days2024 (Jan 1 - Mar 31)\n";
echo "Total: " . ($days2023 + $days2024) . "\n\n";

// What if arrêt 5 doesn't pay until some later date?
// date_deb_droit is 2023-12-07 but maybe it's not fully paid out?
echo "Testing if arrêt 5 stops at a different date:\n";
$arret5_days_paid = 17318.92 - 142.84 - 146.32;
echo "Amount left for arrêt 5: $arret5_days_paid\n";

// Try different combinations
for ($days2023_test = 0; $days2023_test <= 30; $days2023_test++) {
    $remaining = $arret5_days_paid - ($days2023_test * 146.32);
    $days2024_test = $remaining / 150.12;
    $total_days = $days2023_test + $days2024_test;

    if (abs($total_days - round($total_days)) < 0.01 && $total_days > 110 && $total_days < 120) {
        $calc_total = 142.84 + 146.32 + ($days2023_test * 146.32) + ($days2024_test * 150.12);
        if (abs($calc_total - 17318.92) < 1) {
            echo "  $days2023_test days in 2023, " . round($days2024_test) . " days in 2024: " . round($total_days) . " total days\n";
            echo "  Calculation: 142.84 + 146.32 + ($days2023_test * 146.32) + (" . round($days2024_test) . " * 150.12) = $calc_total\n";
        }
    }
}

echo "\n\n=== MOCK4 - Similar analysis ===\n\n";

$mock4 = json_decode(file_get_contents('mock4.json'), true);
echo "Birth date: 1958-12-21\n";
echo "Current date: 2024-09-29\n";
echo "Age: 65\n\n";

echo "Arrêts:\n";
foreach ($mock4 as $i => $arret) {
    echo "$i: {$arret['arret-from-line']} to {$arret['arret-to-line']} ";
    echo "({$arret['arret_diff']} days) ";
    echo "date_deb_droit: {$arret['date_deb_droit']} ";
    echo "rechute: {$arret['rechute-line']}\n";
}

echo "\nSystem calculates: 38022.20\n";
echo "Expected: 37875.88\n";
echo "Difference: 146.32 (exactly 1 day at 2023 rate!)\n\n";

// This suggests arrêt 0 is being paid 1 day instead of 0 days
// Or arrêt 1 is 1 day longer
// Or the date split is wrong

echo "Testing: What if there's an off-by-one in year transition?\n";
$a0 = 1 * 146.32; // Arrêt 0: 1 day at 2023
$a1_2023 = (new DateTime('2023-12-31'))->diff(new DateTime('2023-10-26'))->days + 1;
$a1_2024 = (new DateTime('2024-06-16'))->diff(new DateTime('2024-01-01'))->days + 1;
echo "Arrêt 1: $a1_2023 days in 2023, $a1_2024 days in 2024\n";
$amt_a1 = $a1_2023 * 146.32 + $a1_2024 * 150.12;
echo "  Amount: ($a1_2023 * 146.32) + ($a1_2024 * 150.12) = $amt_a1\n";

$a2_days = 19;
$amt_a2 = $a2_days * 150.12;
echo "Arrêt 2: $a2_days days at 2024 rate = $amt_a2\n";

$total = $a0 + $amt_a1 + $amt_a2;
echo "\nTotal: $a0 + $amt_a1 + $amt_a2 = $total\n";
echo "Expected: 37875.88\n";
echo "Difference: " . ($total - 37875.88) . "\n\n";

// Try without the first day of arrêt 0
echo "Testing without arrêt 0 first day:\n";
$a0_alt = 0;
$total_alt = $a0_alt + $amt_a1 + $amt_a2;
echo "Total: $total_alt\n";
echo "Expected: 37875.88\n";
echo "Difference: " . ($total_alt - 37875.88) . "\n\n";

// Maybe year counting is different?
echo "Testing if 2023 days are counted differently:\n";
$a1_2023_alt = $a1_2023 - 1; // One less day in 2023
$amt_a1_alt = $a1_2023_alt * 146.32 + $a1_2024 * 150.12;
$total_alt2 = $a0 + $amt_a1_alt + $amt_a2;
echo "If arrêt 1 has " . $a1_2023_alt . " days in 2023: $total_alt2\n";
echo "Difference: " . ($total_alt2 - 37875.88) . "\n";
