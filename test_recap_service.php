<?php

/**
 * Test RecapService with real mock data
 *
 * This test loads actual mock files (mock.json, mock2.json, mock3.json, etc.)
 * and generates ij_recap records from the calculation results.
 *
 * To test a different mock:
 * 1. Change $mockFile below to 'mock.json', 'mock3.json', etc.
 * 2. Add corresponding configuration in $testCases array if not already present
 * 3. Run: php test_recap_service.php
 *
 * The test will:
 * - Load arrets from the mock JSON file
 * - Use configuration from test_mocks.php
 * - Calculate IJ amounts
 * - Generate ij_recap records
 * - Validate all records
 * - Generate SQL INSERT statements
 * - Create HTML preview (test_recap_preview.html)
 */

require_once 'IJCalculator.php';
require_once 'Services/RecapService.php';

use App\IJCalculator\IJCalculator;
use App\IJCalculator\Services\RecapService;

echo "=== Test RecapService ===\n\n";

// Select which mock to test (change this to test different scenarios)
$mockFile = 'mock6.json';

// Load mock JSON file (arrets data)
if (!file_exists($mockFile)) {
    die("Error: Mock file '$mockFile' not found!\n");
}

$arrets = json_decode(file_get_contents($mockFile), true);
if (!$arrets) {
    die("Error: Could not parse mock file '$mockFile'!\n");
}

echo "Loaded mock: $mockFile\n";
echo "Number of arrets: " . count($arrets) . "\n\n";

// Load common test case configurations
$testCases = require 'test_cases_config.php';

// Get test configuration for selected mock
if (!isset($testCases[$mockFile])) {
    die("Error: No test configuration found for '$mockFile'!\n");
}

$config = $testCases[$mockFile];

// Build complete test data
$testData = [
    'statut' => $config['statut'],
    'classe' => strtoupper($config['classe']),  // Normalize to uppercase
    'option' => $config['option'],
    'birth_date' => $config['birth_date'],
    'current_date' => $config['current_date'],
    'attestation_date' => $config['attestation_date'],
    'last_payment_date' => null,
    'affiliation_date' => $config['affiliation_date'],
    'nb_trimestres' => $config['nb_trimestres'],
    'previous_cumul_days' => 0,
    'patho_anterior' => $config['patho_anterior'],
    'prorata' => 1,
    'pass_value' => $config['pass_value'],
    'adherent_number' => $arrets[0]['adherent_number'] ?? 'UNKNOWN',
    'num_sinistre' => $arrets[0]['num_sinistre'] ?? 0,
    'arrets' => $arrets
];

// Calculate
$calculator = new IJCalculator('taux.csv');
$result = $calculator->calculateAmount($testData);

echo "Calcul effectué:\n";
echo "- Montant calculé: " . number_format($result['montant'], 2) . "€\n";
echo "- Montant attendu: " . number_format($config['expected'], 2) . "€\n";

// Check if calculation matches expected
$montantMatch = abs($result['montant'] - $config['expected']) < 0.01;
echo "- Match: " . ($montantMatch ? "✓ OK" : "✗ DIFFÉRENCE") . "\n";

echo "- Jours: " . $result['nb_jours'] . "\n";
echo "- Âge: " . $result['age'] . " ans\n\n";

// Generate recap records
$recapService = new RecapService();
$recapRecords = $recapService->generateRecapRecords($result, $testData);

echo "=== Enregistrements de Récapitulatif Générés ===\n\n";
echo "Nombre d'enregistrements: " . count($recapRecords) . "\n\n";

// Display each record
foreach ($recapRecords as $index => $record) {
    echo "Enregistrement #" . ($index + 1) . ":\n";
    echo "  - Adhérent: " . ($record['adherent_number'] ?? 'N/A') . "\n";
    echo "  - Exercice: " . ($record['exercice'] ?? 'N/A') . "\n";
    echo "  - Période: " . ($record['periode'] ?? 'N/A') . "\n";
    echo "  - Dates: " . ($record['date_start'] ?? 'N/A') . " → " . ($record['date_end'] ?? 'N/A') . "\n";
    echo "  - Taux: " . ($record['num_taux'] ?? 'N/A') . "\n";
    echo "  - MT journalier: " . ($record['MT_journalier'] ?? 0) / 100 . "€\n";
    echo "  - Jours: " . ($record['_nb_jours'] ?? 0) . "\n";
    echo "  - Classe: " . ($record['classe'] ?? 'N/A') . "\n";
    echo "  - Âge: " . ($record['personne_age'] ?? 'N/A') . "\n";
    echo "  - Trimestres: " . ($record['nb_trimestre'] ?? 'N/A') . "\n";

    // Validate record
    $validation = $recapService->validateRecord($record);
    echo "  - Validation: " . ($validation['valid'] ? '✓ OK' : '✗ ERREUR') . "\n";
    if (!$validation['valid']) {
        echo "    Erreurs: " . implode(', ', $validation['errors']) . "\n";
    }
    echo "\n";
}

// Generate SQL
echo "\n=== SQL INSERT Statements ===\n\n";
$sql = $recapService->generateBatchInsertSQL($recapRecords);
echo $sql;

// Generate HTML
echo "\n=== HTML Preview ===\n\n";
$html = $recapService->formatRecapHTML($recapRecords);
echo "HTML table generated (" . strlen($html) . " bytes)\n";

// Save HTML to file for preview
file_put_contents('test_recap_preview.html', "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Récapitulatif IJ Preview</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1 { color: #667eea; }
    </style>
</head>
<body>
    <h1>Récapitulatif IJ - Preview</h1>
    $html
</body>
</html>
");

echo "✓ HTML saved to test_recap_preview.html\n";

echo "\n=== Test Completed ===\n";
