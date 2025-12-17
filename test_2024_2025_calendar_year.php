<?php
/**
 * Test Calendar Year Rates: 2024-2025 Spanning Arrêt
 *
 * Critical test for the calendar year rate rule fix
 */

require __DIR__ . '/vendor/autoload.php';

use App\IJCalculator;
use App\Tools\Tools;

echo "════════════════════════════════════════════════════════════════\n";
echo "Test: Calendar Year Rates (2024-2025 Spanning Arrêt)\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Create test data matching the original mock_step scenario
$testData = [
    'adherent-number' => ['191566V'],
    'statut' => 'M',
    'classe' => 'C',  // Explicitly set class C
    'option' => 100,
    'birth_date' => '1958-06-03',
    'affiliation_date' => '1991-07-01',
    'nb_trimestres' => 50,
    'current_date' => '2025-03-31',
    'arrets' => [
        [
            'arret-from-line' => '2024-11-23',
            'arret-to-line' => '2025-03-31',
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'rechute-line' => 0,
            'valid_med_controleur' => 1,
            'cco_a_jour' => 1,
            'code-patho-line' => 'F',
            'ouverture-date-line' => '2024-11-23'
        ]
    ]
];

echo "Scenario:\n";
echo "  Adherent: 191566V, Age 66, Classe C\n";
echo "  Arrêt: 2024-11-23 → 2025-03-31\n";
echo "  Status: M (Médecin)\n";
echo "  Nb trimestres: 50\n\n";

// Rename keys using Tools
$postArray = Tools::renommerCles([$testData], Tools::$correspondance)[0];

// Create calculator
$calculator = new IJCalculator(__DIR__ . '/data/taux.csv');

// Calculate
$result = $calculator->calculateAmount($postArray);

echo "════════════════════════════════════════════════════════════════\n";
echo "Results:\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "Total amount: " . number_format($result['montant'], 2) . " €\n";
echo "Total days: " . $result['nb_jours'] . "\n";
echo "Nb trimestres: " . $result['nb_trimestres'] . "\n\n";

if (isset($result['payment_details'][0])) {
    $detail = $result['payment_details'][0];

    echo "Payment Period:\n";
    echo "  Start: " . $detail['payment_start'] . "\n";
    echo "  End: " . $detail['payment_end'] . "\n";
    echo "  Payable days: " . $detail['payable_days'] . "\n";
    echo "  Decompte days: " . $detail['decompte_days'] . "\n";
    echo "  Date effet: " . ($detail['date_effet'] ?? 'N/A') . "\n\n";

    if (isset($detail['rate_breakdown'])) {
        echo "Rate Breakdown by Calendar Year:\n";
        echo "────────────────────────────────────────────────────────────\n";

        $days2024 = 0;
        $amount2024 = 0;
        $rate2024 = null;
        $days2025 = 0;
        $amount2025 = 0;
        $rate2025 = null;

        foreach ($detail['rate_breakdown'] as $rate) {
            $year = $rate['year'];
            $days = $rate['days'];
            $dailyRate = $rate['rate'];
            $montant = $days * $dailyRate;

            echo "  Year {$year}: {$days} days × " . number_format($dailyRate, 2) . "€ = " . number_format($montant, 2) . " €\n";
            echo "    Period: {$rate['start']} → {$rate['end']}\n";
            echo "    Taux: {$rate['taux']}, Classe: {$rate['classe']}, Age: {$rate['age']}\n\n";

            if ($year == 2024) {
                $days2024 += $days;
                $amount2024 += $montant;
                $rate2024 = $dailyRate;
            } elseif ($year == 2025) {
                $days2025 += $days;
                $amount2025 += $montant;
                $rate2025 = $dailyRate;
            }
        }

        echo "────────────────────────────────────────────────────────────\n";
        echo "Summary by Year:\n";
        echo "  2024: {$days2024} days = " . number_format($amount2024, 2) . " €";
        if ($rate2024) echo " (rate: " . number_format($rate2024, 2) . "€)";
        echo "\n";
        echo "  2025: {$days2025} days = " . number_format($amount2025, 2) . " €";
        if ($rate2025) echo " (rate: " . number_format($rate2025, 2) . "€)";
        echo "\n\n";

        // Validation
        echo "════════════════════════════════════════════════════════════\n";
        echo "Validation:\n";
        echo "════════════════════════════════════════════════════════════\n\n";

        $expected2024Rate = 150.12;  // Class C, taux 1, 2024 DB
        $expected2025Rate = 152.81;  // Class C, taux 1, 2025 DB
        $wrongPASSRate = 190.55;     // Class C, PASS formula (WRONG)

        $success = true;

        // Check 2024 rate
        if ($days2024 > 0) {
            if (abs($rate2024 - $expected2024Rate) < 0.01) {
                echo "✓ 2024 rate CORRECT: " . number_format($rate2024, 2) . "€ (2024 DB rate)\n";
            } else {
                echo "✗ 2024 rate WRONG: " . number_format($rate2024, 2) . "€ (expected " . number_format($expected2024Rate, 2) . "€)\n";
                $success = false;
            }
        } else {
            echo "ℹ No days in 2024 (payment starts after date-effet in 2025)\n";
        }

        // Check 2025 rate
        if ($days2025 > 0) {
            if (abs($rate2025 - $expected2025Rate) < 0.01) {
                echo "✓ 2025 rate CORRECT: " . number_format($rate2025, 2) . "€ (2025 DB rate)\n";
            } elseif (abs($rate2025 - $wrongPASSRate) < 0.01) {
                echo "✗ 2025 rate WRONG: " . number_format($rate2025, 2) . "€ (using PASS formula instead of 2025 DB rate!)\n";
                echo "  Expected: " . number_format($expected2025Rate, 2) . "€ (2025 DB rate)\n";
                $success = false;
            } else {
                echo "✗ 2025 rate UNEXPECTED: " . number_format($rate2025, 2) . "€\n";
                echo "  Expected: " . number_format($expected2025Rate, 2) . "€ (2025 DB rate)\n";
                $success = false;
            }
        } else {
            echo "✗ No days in 2025 (ERROR: should have days in 2025)\n";
            $success = false;
        }

        echo "\n";

        if ($success) {
            echo "✓✓✓ TEST PASSED ✓✓✓\n";
            echo "Calendar year rate rule is working correctly!\n";
        } else {
            echo "✗✗✗ TEST FAILED ✗✗✗\n";
            echo "Calendar year rate rule is NOT working correctly.\n";
        }
    } else {
        echo "✗ No rate breakdown available\n";
    }
} else {
    echo "✗ No payment details available\n";
}

echo "\n════════════════════════════════════════════════════════════════\n";
