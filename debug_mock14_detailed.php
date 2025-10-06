<?php

require_once 'IJCalculator.php';

$mock14 = json_decode(file_get_contents('mock14.json'), true);

echo "Mock14 Analysis\n";
echo "===============\n\n";

echo "Raw data from JSON:\n";
echo "  arret-from-line: {$mock14[0]['arret-from-line']}\n";
echo "  arret-to-line: {$mock14[0]['arret-to-line']}\n";
echo "  date_deb_droit: {$mock14[0]['date_deb_droit']}\n";
echo "  declaration-date-line: {$mock14[0]['declaration-date-line']}\n";
echo "  dt-line (DT à jour): {$mock14[0]['dt-line']}\n";
echo "  attestation-date-line: {$mock14[0]['attestation-date-line']}\n\n";

echo "Business logic:\n";
echo "  - Arrêt observé: 07/06/2023\n";
echo "  - Déclaré: 21/05/2024 (hors délai)\n";
echo "  - Commission n'a pas excusé la DT\n";
echo "  - Prise en charge au 31ème jour: 20/06/2024\n";
echo "  - Payé jusqu'au: 25/10/2024\n\n";

// The issue is likely that calculateDateEffet is recalculating the date_deb_droit
// instead of using the one from the JSON

$calc = new IJCalculator('taux.csv');

// Step 1: Check what calculateDateEffet does
$arrets = $mock14;
$result = $calc->calculateDateEffet($arrets, '1985-07-27', 0);

echo "After calculateDateEffet:\n";
foreach ($result as $i => $arret) {
    if (isset($arret['date-effet'])) {
        echo "  Arrêt $i date-effet: {$arret['date-effet']}\n";
    }
}
echo "\n";

echo "Expected date-effet: 2024-06-20 (from date_deb_droit)\n";
echo "But calculateDateEffet might be recalculating based on 90-day rule...\n\n";

// Check 90-day calculation
$start = new DateTime($mock14[0]['arret-from-line']);
$day90 = clone $start;
$day90->modify('+89 days'); // 90th day (inclusive)
echo "90th day from start: " . $day90->format('Y-m-d') . "\n";

$start90 = clone $start;
$start90->modify('+90 days'); // After 90 days
echo "After 90 days: " . $start90->format('Y-m-d') . "\n";
