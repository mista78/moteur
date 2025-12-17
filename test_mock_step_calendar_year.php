<?php
/**
 * Test Calendar Year Rates with mock_step.json
 *
 * Tests arrêt spanning 2024-2025:
 * - Start: 2024-11-23
 * - End: 2025-03-31
 * - Expected: Days in 2024 use 2024 DB rates, Days in 2025 use 2025 DB rates
 */

require __DIR__ . '/vendor/autoload.php';

use App\IJCalculator;
use App\Tools\Tools;

echo "════════════════════════════════════════════════════════════════\n";
echo "Test Calendar Year Rates - mock_step.json\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Load mock_step.json
$mockData = json_decode(file_get_contents(__DIR__ . '/public/mock_step.json'), true);

echo "Arrêt Information:\n";
echo "  From: " . $mockData['arrets'][0]['arret-from-line'] . "\n";
echo "  To:   " . $mockData['arrets'][0]['arret-to-line'] . "\n";
echo "  Statut: " . $mockData['statut'] . "\n";
echo "  Birth date: " . $mockData['birth_date'] . "\n";
echo "  Affiliation date: " . $mockData['affiliation_date'] . "\n";
echo "  Nb trimestres: " . $mockData['nb_trimestres'] . "\n\n";

// Rename keys using Tools
$postArray = Tools::renommerCles([$mockData], Tools::$correspondance)[0];

// Create calculator
$calculator = new IJCalculator(__DIR__ . '/data/taux.csv');

// Calculate
$result = $calculator->calculateAmount($postArray);

echo "════════════════════════════════════════════════════════════════\n";
echo "Calculation Results:\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "Total amount: " . number_format($result['montant'], 2) . " €\n";
echo "Total days: " . $result['nb_jours'] . "\n\n";

echo "════════════════════════════════════════════════════════════════\n";
echo "Payment Details (Calendar Year Breakdown):\n";
echo "════════════════════════════════════════════════════════════════\n\n";

if (isset($result['payment_details']) && is_array($result['payment_details'])) {
    foreach ($result['payment_details'] as $idx => $detail) {
        echo "Arrêt #" . ($idx + 1) . ":\n";
        echo "  Period: " . $detail['payment_start'] . " → " . $detail['payment_end'] . "\n";
        echo "  Payable days: " . $detail['payable_days'] . "\n";

        if (isset($detail['rate_breakdown']) && is_array($detail['rate_breakdown'])) {
            echo "  Rate breakdown by year:\n";

            $totalAmount = 0;
            $total2024Days = 0;
            $total2025Days = 0;
            $amount2024 = 0;
            $amount2025 = 0;

            foreach ($detail['rate_breakdown'] as $rateIdx => $rate) {
                $year = $rate['year'];
                $days = $rate['days'];
                $dailyRate = $rate['rate'];
                $montant = $days * $dailyRate;

                echo "    Year {$year}: {$days} days × {$dailyRate}€ = " . number_format($montant, 2) . " €\n";
                echo "      Period: {$rate['start']} → {$rate['end']}\n";
                echo "      Taux: {$rate['taux']}, Classe: {$rate['classe']}, Age: {$rate['age']}\n";

                $totalAmount += $montant;

                if ($year == 2024) {
                    $total2024Days += $days;
                    $amount2024 += $montant;
                } elseif ($year == 2025) {
                    $total2025Days += $days;
                    $amount2025 += $montant;
                }
            }

            echo "\n";
            echo "  Summary for this arrêt:\n";
            echo "    Days in 2024: {$total2024Days} days = " . number_format($amount2024, 2) . " €\n";
            echo "    Days in 2025: {$total2025Days} days = " . number_format($amount2025, 2) . " €\n";
            echo "    Total: " . ($total2024Days + $total2025Days) . " days = " . number_format($totalAmount, 2) . " €\n";
        }

        echo "\n";
    }
}

echo "════════════════════════════════════════════════════════════════\n";
echo "Expected Behavior:\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "✓ Arrêt starts 2024-11-23 (date_effet < 2025-01-01)\n";
echo "✓ Days from 2024-11-23 to 2024-12-31 → Should use 2024 DB rates\n";
echo "✓ Days from 2025-01-01 to 2025-03-31 → Should use 2025 DB rates\n";
echo "✓ Should NOT use PASS formula (only for date_effet >= 2025-01-01)\n\n";

echo "════════════════════════════════════════════════════════════════\n";
echo "Verification:\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Count days in each year
$daysIn2024 = 0;
$daysIn2025 = 0;

if (isset($result['payment_details']) && is_array($result['payment_details'])) {
    foreach ($result['payment_details'] as $detail) {
        if (isset($detail['rate_breakdown']) && is_array($detail['rate_breakdown'])) {
            foreach ($detail['rate_breakdown'] as $rate) {
                if ($rate['year'] == 2024) {
                    $daysIn2024 += $rate['days'];
                } elseif ($rate['year'] == 2025) {
                    $daysIn2025 += $rate['days'];
                }
            }
        }
    }
}

// Calculate expected days
$startDate = new DateTime('2024-11-23');
$endYearDate = new DateTime('2024-12-31');
$endDate = new DateTime('2025-03-31');

// Days in 2024 (need to account for date-effet)
$expectedDaysIn2024 = $startDate->diff($endYearDate)->days + 1;

// Days in 2025
$yearStartDate = new DateTime('2025-01-01');
$expectedDaysIn2025 = $yearStartDate->diff($endDate)->days + 1;

echo "Expected days breakdown:\n";
echo "  2024-11-23 to 2024-12-31: ~{$expectedDaysIn2024} days\n";
echo "  2025-01-01 to 2025-03-31: ~{$expectedDaysIn2025} days\n\n";

echo "Actual days breakdown:\n";
echo "  Days in 2024: {$daysIn2024}\n";
echo "  Days in 2025: {$daysIn2025}\n\n";

$success = ($daysIn2024 > 0 && $daysIn2025 > 0);

if ($success) {
    echo "✓✓✓ SUCCESS! Both 2024 and 2025 days are present.\n";
    echo "✓ The calendar year rate rule is working correctly!\n";
} else {
    echo "✗✗✗ ISSUE DETECTED!\n";
    if ($daysIn2024 == 0) {
        echo "✗ No days counted in 2024 - should have ~{$expectedDaysIn2024} days\n";
    }
    if ($daysIn2025 == 0) {
        echo "✗ No days counted in 2025 - should have ~{$expectedDaysIn2025} days\n";
    }
}

echo "\n════════════════════════════════════════════════════════════════\n";
