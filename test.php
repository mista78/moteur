<?php
require_once 'IJCalculator.php';

$calculator = new IJCalculator('taux.csv');

// Load mock data
$mockData = json_decode(file_get_contents('mock.json'), true);

echo "=== TEST: Date Effet Calculation ===\n\n";

// Test with mock data
$result = $calculator->calculateDateEffet($mockData, null, 0);

foreach ($result as $index => $arret) {
    echo "Arrêt " . ($index + 1) . ":\n";
    echo "  Début: {$arret['arret-from-line']}\n";
    echo "  Fin: {$arret['arret-to-line']}\n";

    $start = new DateTime($arret['arret-from-line']);
    $end = new DateTime($arret['arret-to-line']);
    $duration = $start->diff($end)->days + 1;
    echo "  Durée: $duration jours\n";

    if (isset($arret['date-effet'])) {
        echo "  ✓ Date d'effet: {$arret['date-effet']}\n";
    } else {
        echo "  ✗ Date d'effet: Non calculée\n";
    }
    echo "\n";
}

echo "\n=== Expected for Arrêt 2 ===\n";
echo "Date d'effet attendue: 2024-03-28\n";
echo "Date d'effet calculée: " . ($result[1]['date-effet'] ?? 'N/A') . "\n";

if (isset($result[1]['date-effet']) && $result[1]['date-effet'] === '2024-03-28') {
    echo "✓✓✓ TEST PASSED ✓✓✓\n";
} else {
    echo "✗✗✗ TEST FAILED ✗✗✗\n";
}
