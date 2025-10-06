<?php

require_once 'IJCalculator.php';

$calculator = new IJCalculator('/home/mista/work/ij/taux.csv');

$mock7 = json_decode(file_get_contents('/home/mista/work/ij/mock7.json'), true);

// Test with FIXED limits (365, 730, 1095) instead of anniversary
$data7 = [
    'arrets' => $mock7,
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1960-01-29',
    'current_date' => '2024-09-30',
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0,
    'use_fixed_limits' => true  // Flag to use fixed limits
];

echo "=== MOCK7 WITH FIXED LIMITS (365/730/1095) ===\n\n";

// Manual calculation with fixed limits
$dateEffet = new DateTime('2022-01-02');
$arretEnd = new DateTime('2024-09-30');
$totalDays = $dateEffet->diff($arretEnd)->days + 1;

echo "Date effet: 2022-01-02\n";
echo "Arret end: 2024-09-30\n";
echo "Total days: $totalDays\n";
echo "Age at current date: 64\n\n";

// Fixed limits: 365, 730, 1095
echo "With FIXED limits:\n";
echo "Period 1 (days 1-365): 365 days at taux 1\n";
echo "Period 2 (days 366-730): 365 days at taux 7\n";
echo "Period 3 (days 731-1003): 273 days at taux 4\n\n";

// Get rates
$rate2022_1 = $calculator->getRateForYear(2022)['taux_a1'];  // 69.00
$rate2023_1 = $calculator->getRateForYear(2023)['taux_a1'];  // 73.16
$rate2023_3 = $calculator->getRateForYear(2023)['taux_a3'];  // 54.87
$rate2024_3 = $calculator->getRateForYear(2024)['taux_a3'];  // 56.30
$rate2024_2 = $calculator->getRateForYear(2024)['taux_a2'];  // 38.30

echo "Rates:\n";
echo "2022 taux_a1: {$rate2022_1}€\n";
echo "2023 taux_a1: {$rate2023_1}€, taux_a3: {$rate2023_3}€\n";
echo "2024 taux_a3: {$rate2024_3}€, taux_a2: {$rate2024_2}€\n\n";

// Period 1: days 1-365 (2022-01-02 to 2023-01-01)
// 2022: 01-02 to 12-31 = 364 days
// 2023: 01-01 = 1 day
$p1_2022 = 364;
$p1_2023 = 1;
$amount_p1 = ($p1_2022 * $rate2022_1) + ($p1_2023 * $rate2023_1);
echo "Period 1 calculation:\n";
echo "  2022: $p1_2022 days × {$rate2022_1}€ = " . number_format($p1_2022 * $rate2022_1, 2) . "€\n";
echo "  2023: $p1_2023 days × {$rate2023_1}€ = " . number_format($p1_2023 * $rate2023_1, 2) . "€\n";
echo "  Total P1: " . number_format($amount_p1, 2) . "€\n\n";

// Period 2: days 366-730 (2023-01-02 to 2024-01-01)
// 2023: 01-02 to 12-31 = 364 days
// 2024: 01-01 = 1 day
$p2_2023 = 364;
$p2_2024 = 1;
$amount_p2 = ($p2_2023 * $rate2023_3) + ($p2_2024 * $rate2024_3);
echo "Period 2 calculation:\n";
echo "  2023: $p2_2023 days × {$rate2023_3}€ = " . number_format($p2_2023 * $rate2023_3, 2) . "€\n";
echo "  2024: $p2_2024 days × {$rate2024_3}€ = " . number_format($p2_2024 * $rate2024_3, 2) . "€\n";
echo "  Total P2: " . number_format($amount_p2, 2) . "€\n\n";

// Period 3: days 731-1003 (2024-01-02 to 2024-09-30)
// 2024: 01-02 to 09-30 = 273 days
$p3_2024 = 273;
$amount_p3 = $p3_2024 * $rate2024_2;
echo "Period 3 calculation:\n";
echo "  2024: $p3_2024 days × {$rate2024_2}€ = " . number_format($amount_p3, 2) . "€\n";
echo "  Total P3: " . number_format($amount_p3, 2) . "€\n\n";

$total = $amount_p1 + $amount_p2 + $amount_p3;
echo "TOTAL: " . number_format($total, 2) . "€\n";
echo "Expected: 57099.15€\n";
echo "Difference: " . number_format($total - 57099.15, 2) . "€\n";
