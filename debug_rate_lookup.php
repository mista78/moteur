<?php
/**
 * Debug Rate Lookup - Check what rate is returned for 2025
 */

require __DIR__ . '/vendor/autoload.php';

use App\Services\RateService;

echo "════════════════════════════════════════════════════════════════\n";
echo "Debug: Rate Lookup for 2025\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Create RateService with CSV
$rateService = new RateService(__DIR__ . '/data/taux.csv');

// Set PASS value
$rateService->setPassValue(46368);

echo "Test 1: Get rate for year 2025\n";
echo "─────────────────────────────────\n";
$rate2025 = $rateService->getRateForYear(2025);
if ($rate2025) {
    echo "✓ Found 2025 rate in CSV:\n";
    echo "  taux_c1: " . $rate2025['taux_c1'] . "€\n";
    $dateStart = $rate2025['date_start'] instanceof DateTime ? $rate2025['date_start']->format('Y-m-d') : $rate2025['date_start'];
    $dateEnd = $rate2025['date_end'] instanceof DateTime ? $rate2025['date_end']->format('Y-m-d') : $rate2025['date_end'];
    echo "  date_start: " . $dateStart . "\n";
    echo "  date_end: " . $dateEnd . "\n";
} else {
    echo "✗ No 2025 rate found in CSV\n";
}
echo "\n";

echo "Test 2: Get daily rate for 2025 (date_effet = 2024-11-23)\n";
echo "───────────────────────────────────────────────────────────────\n";
echo "Parameters:\n";
echo "  statut: M\n";
echo "  classe: C\n";
echo "  option: 100\n";
echo "  taux: 1\n";
echo "  year: 2024\n";
echo "  date (date_effet): 2024-11-23\n";
echo "  calculationDate: 2025-01-15\n\n";

$dailyRate = $rateService->getDailyRate(
    statut: 'M',
    classe: 'C',
    option: 100,
    taux: 1,
    year: 2024,
    date: '2024-11-23',  // date_effet < 2025
    age: 66,
    usePeriode2: null,
    revenu: null,
    calculationDate: '2025-01-15'  // day in 2025
);

echo "Result: {$dailyRate}€\n\n";

echo "Expected: 152.81€ (2025 DB rate from CSV)\n";
echo "If showing 190.55€: Using PASS formula (WRONG)\n";
echo "If showing 152.81€: Using 2025 DB rate (CORRECT)\n\n";

// Calculate PASS formula for comparison
$passFormula = (3 * 46368) / 730;
echo "PASS formula would be: " . round($passFormula, 2) . "€\n\n";

echo "════════════════════════════════════════════════════════════════\n";
