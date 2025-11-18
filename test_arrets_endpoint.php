<?php
/**
 * Test the calculate-arrets-date-effet endpoint
 */

require_once 'IJCalculator.php';
require_once 'Services/DateNormalizer.php';

use App\IJCalculator\IJCalculator;
use App\IJCalculator\Services\DateNormalizer;

echo "=== Testing Arrets Date-Effet Endpoint ===\n\n";

// Load arrets from JSON file
$arretsJson = file_get_contents('arrets.json');
$arrets = json_decode($arretsJson, true);

echo "Loaded " . count($arrets) . " arrets from arrets.json\n\n";

// Prepare request data
$requestData = [
    'arrets' => $arrets,
    'birth_date' => '1958-06-03',  // Birth date from the data
    'previous_cumul_days' => 0
];

// Load rates for calculator
$rates = [];
if (($handle = fopen('taux.csv', 'r')) !== false) {
    $header = fgetcsv($handle, 1000, ';');
    while (($data = fgetcsv($handle, 1000, ';')) !== false) {
        $rate = array_combine($header, $data);
        $rate['date_start'] = new DateTime($rate['date_start']);
        $rate['date_end'] = new DateTime($rate['date_end']);
        $rates[] = $rate;
    }
    fclose($handle);
}

// Simulate the endpoint logic
echo "Calculating date-effet for all arrets...\n";

// Normalize dates
$input = DateNormalizer::normalize($requestData);

// Calculate date-effet
$calculator = new IJCalculator($rates);
$arretsWithDateEffet = $calculator->calculateDateEffet(
    $input['arrets'],
    $input['birth_date'],
    $input['previous_cumul_days']
);

echo "\n=== Results ===\n\n";

// Display results
foreach ($arretsWithDateEffet as $index => $arret) {
    echo "Arrêt #" . ($index + 1) . " (ID: {$arret['id']}):\n";
    echo "  Période: {$arret['arret-from-line']} → {$arret['arret-to-line']}\n";
    echo "  Durée: {$arret['arret_diff']} jours\n";

    if (isset($arret['date-effet']) && !empty($arret['date-effet'])) {
        echo "  ✅ Date-effet: {$arret['date-effet']}\n";
    } else {
        echo "  ⚠️  Date-effet: Pas encore calculée (seuil non atteint)\n";
    }

    if (isset($arret['is_rechute'])) {
        echo "  Rechute: " . ($arret['is_rechute'] ? 'Oui' : 'Non') . "\n";

        if ($arret['is_rechute'] && isset($arret['rechute_of_arret_index'])) {
            echo "  Source rechute: Arrêt #" . ($arret['rechute_of_arret_index'] + 1) . "\n";
        }
    }

    echo "\n";
}

// Show JSON output example
echo "=== Example API Response ===\n\n";
echo "POST to: /api.php?endpoint=calculate-arrets-date-effet\n\n";
echo "Request Body:\n";
echo json_encode([
    'arrets' => array_slice($arrets, 0, 2), // Show first 2 for brevity
    'birth_date' => '1958-06-03',
    'previous_cumul_days' => 0
], JSON_PRETTY_PRINT) . "\n\n";

echo "Response (first 2 arrets):\n";
echo json_encode([
    'success' => true,
    'data' => array_slice($arretsWithDateEffet, 0, 2)
], JSON_PRETTY_PRINT) . "\n";

echo "\n✅ Endpoint ready to use!\n";
