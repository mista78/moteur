<?php

require_once 'IJCalculator.php';

// Mock2: Check dates and cumulative day counting
echo "=== MOCK2 Analysis ===\n\n";

$mock2 = json_decode(file_get_contents('mock2.json'), true);

echo "Arrêts:\n";
$totalDays = 0;
foreach ($mock2 as $i => $arret) {
    echo "$i: {$arret['arret-from-line']} to {$arret['arret-to-line']} ";
    echo "({$arret['arret_diff']} days) ";
    echo "date_deb_droit: {$arret['date_deb_droit']} ";
    echo "rechute: {$arret['rechute-line']}\n";
    $totalDays += $arret['arret_diff'];
}
echo "\nTotal days in arrêts: $totalDays\n";
echo "Payable according to test: 118 days (arrêts 3,4,5 have date_deb_droit)\n\n";

// Let's manually calculate what should be paid
$arret3Days = 1; // 2022-12-06 to 2022-12-24, but only 1 day is paid
$arret4Days = 1; // 2023-10-10 to 2023-10-10
$arret5Days = 116; // 2023-12-07 to 2024-03-31

echo "Manual calculation:\n";
echo "Arrêt 3: $arret3Days day (starting 2022-12-06) - 2022 H2 rate\n";
echo "Arrêt 4: $arret4Days day (2023-10-10) - 2023 rate\n";
echo "Arrêt 5: $arret5Days days (2023-12-07 to 2024-03-31)\n";
echo "  - Days in 2023: " . (new DateTime('2023-12-31'))->diff(new DateTime('2023-12-07'))->days + 1 . " days\n";
echo "  - Days in 2024: " . (new DateTime('2024-03-31'))->diff(new DateTime('2024-01-01'))->days + 1 . " days\n\n";

// Get rates from CSV
$calc = new IJCalculator('taux.csv');
$rate2022H2 = $calc->getRateForDate('2022-12-06');
$rate2023 = $calc->getRateForDate('2023-12-07');
$rate2024 = $calc->getRateForDate('2024-01-01');

echo "Rates for class C:\n";
echo "2022-H2: taux_c1={$rate2022H2['taux_c1']}, taux_c2={$rate2022H2['taux_c2']}, taux_c3={$rate2022H2['taux_c3']}\n";
echo "2023: taux_c1={$rate2023['taux_c1']}, taux_c2={$rate2023['taux_c2']}, taux_c3={$rate2023['taux_c3']}\n";
echo "2024: taux_c1={$rate2024['taux_c1']}, taux_c2={$rate2024['taux_c2']}, taux_c3={$rate2024['taux_c3']}\n\n";

// Person is 66 years old (born 1958-06-03, current date 2024-06-12)
// Age determines which rate column to use
$birthDate = new DateTime('1958-06-03');
$ageAt2022Dec = (new DateTime('2022-12-06'))->diff($birthDate)->y;
$ageAt2023Oct = (new DateTime('2023-10-10'))->diff($birthDate)->y;
$ageAt2023Dec = (new DateTime('2023-12-07'))->diff($birthDate)->y;
$ageAt2024Jan = (new DateTime('2024-01-01'))->diff($birthDate)->y;

echo "Ages:\n";
echo "At 2022-12-06: $ageAt2022Dec\n";
echo "At 2023-10-10: $ageAt2023Oct\n";
echo "At 2023-12-07: $ageAt2023Dec\n";
echo "At 2024-01-01: $ageAt2024Jan\n\n";

// 62-69 years old -> uses taux 7 (rate 1 for first year)
// After first year (365 days) -> taux 8 (rate 2)
// After second year (730 days) -> taux 9 (rate 3)

echo "According to the system, for 62-69 years old:\n";
echo "- Days 1-365: Use taux_c1 (full rate)\n";
echo "- Days 366-730: Use taux_c2 (reduced rate)\n";
echo "- Days 731-1095: Use taux_c3 (more reduced rate)\n\n";

// Calculate cumul days for each arrêt
echo "Cumulative days at each payment:\n";
$cumul = 0;
echo "Arrêt 3 (1 day): cumul = $cumul to " . ($cumul + 1) . " -> tier 1\n";
$cumul += 1;
echo "Arrêt 4 (1 day): cumul = $cumul to " . ($cumul + 1) . " -> tier 1\n";
$cumul += 1;
$daysIn2023 = (new DateTime('2023-12-31'))->diff(new DateTime('2023-12-07'))->days + 1;
$daysIn2024 = (new DateTime('2024-03-31'))->diff(new DateTime('2024-01-01'))->days + 1;
echo "Arrêt 5 part 1 ($daysIn2023 days in 2023): cumul = $cumul to " . ($cumul + $daysIn2023) . "\n";
$cumul += $daysIn2023;
echo "Arrêt 5 part 2 ($daysIn2024 days in 2024): cumul = " . ($cumul) . " to " . ($cumul + $daysIn2024) . "\n\n";

// Expected calculation
echo "Expected calculation:\n";
$expectedTotal = 0;

// Arrêt 3: 1 day at taux_c1 for 2022-H2
$amt3 = 1 * floatval($rate2022H2['taux_c1']);
echo "Arrêt 3: 1 * {$rate2022H2['taux_c1']} = $amt3\n";
$expectedTotal += $amt3;

// Arrêt 4: 1 day at taux_c1 for 2023
$amt4 = 1 * floatval($rate2023['taux_c1']);
echo "Arrêt 4: 1 * {$rate2023['taux_c1']} = $amt4\n";
$expectedTotal += $amt4;

// Arrêt 5: 25 days in 2023, then 91 days in 2024
// Days 3-27 (25 days) at 2023 rate taux_c1
$amt5a = 25 * floatval($rate2023['taux_c1']);
echo "Arrêt 5 (2023): 25 * {$rate2023['taux_c1']} = $amt5a\n";
$expectedTotal += $amt5a;

// Days 28-118 (91 days) at 2024 rate taux_c1
$amt5b = 91 * floatval($rate2024['taux_c1']);
echo "Arrêt 5 (2024): 91 * {$rate2024['taux_c1']} = $amt5b\n";
$expectedTotal += $amt5b;

echo "\nExpected total: $expectedTotal\n";
echo "Expected from test: 17318.92\n";
echo "Calculated by system: 17608.08\n";
echo "Difference: " . (17608.08 - $expectedTotal) . " (system vs manual)\n";
echo "Difference: " . (17608.08 - 17318.92) . " (system vs test)\n";
