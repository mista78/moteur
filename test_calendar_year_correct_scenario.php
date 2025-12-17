<?php
/**
 * Test Calendar Year Rates - CORRECT Scenario
 *
 * Arrêt starting early enough so date_effet < 2025-01-01
 * but payment period extends into 2025
 */

require __DIR__ . '/vendor/autoload.php';

use App\IJCalculator;
use App\Tools\Tools;

echo "════════════════════════════════════════════════════════════════\n";
echo "Test: Calendar Year Rates - Correct Scenario\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Arrêt starting September 1, 2024
// With 90 days: date_effet ≈ November 30, 2024 (< 2025-01-01)
// Continues into January 2025
$testData = [
    'statut' => 'M',
    'classe' => 'C',
    'option' => 100,
    'birth_date' => '1958-06-03',
    'affiliation_date' => '1991-07-01',
    'nb_trimestres' => 50,
    'current_date' => '2025-01-31',
    'arrets' => [
        [
            'arret-from-line' => '2024-09-01',
            'arret-to-line' => '2025-01-31',
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'rechute-line' => 0,
            'valid_med_controleur' => 1,
            'cco_a_jour' => 1,
            'code-patho-line' => 'F',
            'ouverture-date-line' => '2024-09-01'
        ]
    ]
];

echo "Scenario:\n";
echo "  Arrêt: 2024-09-01 → 2025-01-31\n";
echo "  Expected date_effet: ~2024-11-30 (90 days after start)\n";
echo "  Classe: C, Age: 66, Statut: M\n\n";

$postArray = Tools::renommerCles([$testData], Tools::$correspondance)[0];
$calculator = new IJCalculator(__DIR__ . '/data/taux.csv');
$result = $calculator->calculateAmount($postArray);

echo "Results:\n";
echo "  Total: " . number_format($result['montant'], 2) . " €\n";
echo "  Days: " . $result['nb_jours'] . "\n\n";

if (isset($result['payment_details'][0])) {
    $detail = $result['payment_details'][0];

    echo "Payment Details:\n";
    echo "  Payment start: " . $detail['payment_start'] . "\n";
    echo "  Payment end: " . $detail['payment_end'] . "\n";
    echo "  Date effet: " . ($detail['date_effet'] ?? 'N/A') . "\n";
    echo "  Decompte days: " . $detail['decompte_days'] . "\n\n";

    if (isset($detail['rate_breakdown'])) {
        echo "Rate Breakdown:\n";
        echo "────────────────────────────────────────────────────────────\n";

        $days2024 = 0;
        $rate2024 = null;
        $days2025 = 0;
        $rate2025 = null;

        foreach ($detail['rate_breakdown'] as $rate) {
            echo "  Year {$rate['year']}: {$rate['days']} days × " . number_format($rate['rate'], 2) . "€\n";
            echo "    Period: {$rate['start']} → {$rate['end']}\n";

            if ($rate['year'] == 2024) {
                $days2024 += $rate['days'];
                $rate2024 = $rate['rate'];
            } elseif ($rate['year'] == 2025) {
                $days2025 += $rate['days'];
                $rate2025 = $rate['rate'];
            }
        }

        echo "\n════════════════════════════════════════════════════════════\n";
        echo "Validation:\n";
        echo "════════════════════════════════════════════════════════════\n\n";

        $dateEffet = $detail['date_effet'] ?? null;
        $dateEffetBefore2025 = $dateEffet && strtotime($dateEffet) < strtotime('2025-01-01');

        echo "Date effet: " . $dateEffet . "\n";
        echo "Is date_effet < 2025-01-01? " . ($dateEffetBefore2025 ? "YES" : "NO") . "\n\n";

        if ($dateEffetBefore2025) {
            echo "Expected behavior (date_effet < 2025-01-01):\n";
            echo "  - Days in 2024 → 2024 DB rate (150.12€)\n";
            echo "  - Days in 2025 → 2025 DB rate (152.81€)\n";
            echo "  - NOT PASS formula (190.55€)\n\n";

            $success = true;

            if ($days2024 > 0 && $rate2024) {
                if (abs($rate2024 - 150.12) < 0.01) {
                    echo "✓ 2024 rate CORRECT: " . number_format($rate2024, 2) . "€\n";
                } else {
                    echo "✗ 2024 rate WRONG: " . number_format($rate2024, 2) . "€ (expected 150.12€)\n";
                    $success = false;
                }
            } else {
                echo "ℹ No days in 2024\n";
            }

            if ($days2025 > 0 && $rate2025) {
                if (abs($rate2025 - 152.81) < 0.01) {
                    echo "✓ 2025 rate CORRECT: " . number_format($rate2025, 2) . "€ (2025 DB rate)\n";
                } elseif (abs($rate2025 - 190.55) < 0.01) {
                    echo "✗ 2025 rate WRONG: " . number_format($rate2025, 2) . "€ (PASS formula - should be 152.81€)\n";
                    $success = false;
                } else {
                    echo "✗ 2025 rate UNEXPECTED: " . number_format($rate2025, 2) . "€ (expected 152.81€)\n";
                    $success = false;
                }
            } else {
                echo "✗ No days in 2025 (should have days in 2025)\n";
                $success = false;
            }

            echo "\n";
            if ($success) {
                echo "✓✓✓ TEST PASSED ✓✓✓\n";
                echo "Calendar year rate rule is working correctly!\n";
            } else {
                echo "✗✗✗ TEST FAILED ✗✗✗\n";
            }
        } else {
            echo "Note: date_effet >= 2025-01-01, so PASS formula is CORRECT.\n";
            echo "This scenario doesn't test the calendar year rate rule.\n";
        }
    }
}

echo "\n════════════════════════════════════════════════════════════════\n";
