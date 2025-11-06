<?php

/**
 * Test DetailJourService with real mock data
 *
 * This test loads actual mock files and generates ij_detail_jour records
 * from the daily_breakdown in calculation results.
 *
 * To test a different mock:
 * 1. Change $mockFile below to 'mock.json', 'mock3.json', etc.
 * 2. Run: php test_detail_jour_service.php
 *
 * The test will:
 * - Load arrets from the mock JSON file
 * - Calculate IJ amounts with daily breakdown
 * - Generate ij_detail_jour records (one per month)
 * - Validate all records
 * - Generate SQL INSERT statements
 * - Create HTML preview (test_detail_jour_preview.html)
 */

require_once 'IJCalculator.php';
require_once 'Services/DetailJourService.php';

use IJCalculator\Services\DetailJourService;

echo "=== Test DetailJourService ===\n\n";

// Select which mock to test (change this to test different scenarios)
$mockFile = 'mock.json';

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
    'classe' => strtoupper($config['classe']),
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
echo "- Montant: " . number_format($result['montant'], 2) . "€\n";
echo "- Jours: " . $result['nb_jours'] . "\n";
echo "- Âge: " . $result['age'] . " ans\n\n";

// Check if daily_breakdown is available
$hasDailyBreakdown = false;
if (isset($result['payment_details'])) {
    foreach ($result['payment_details'] as $detail) {
        if (isset($detail['daily_breakdown']) && !empty($detail['daily_breakdown'])) {
            $hasDailyBreakdown = true;
            echo "Daily breakdown available: " . count($detail['daily_breakdown']) . " days\n";
            break;
        }
    }
}

if (!$hasDailyBreakdown) {
    echo "⚠️  WARNING: No daily_breakdown found in calculation results!\n";
    echo "DetailJourService requires daily_breakdown to generate records.\n\n";
}

// Generate detail_jour records
$detailJourService = new DetailJourService();
$detailJourRecords = $detailJourService->generateDetailJourRecords($result, $testData);

echo "\n=== Enregistrements de Détail Journalier Générés ===\n\n";
echo "Nombre d'enregistrements (mois): " . count($detailJourRecords) . "\n\n";

// Display each record summary
foreach ($detailJourRecords as $index => $record) {
    echo "Enregistrement #" . ($index + 1) . ":\n";
    echo "  - Adhérent: " . ($record['adherent_number'] ?? 'N/A') . "\n";
    echo "  - Exercice: " . ($record['exercice'] ?? 'N/A') . "\n";
    echo "  - Période (mois): " . ($record['periode'] ?? 'N/A') . "\n";

    // Count days with values
    $daysWithValues = 0;
    $monthTotal = 0;
    for ($i = 1; $i <= 31; $i++) {
        if (isset($record["j$i"]) && $record["j$i"] !== null) {
            $daysWithValues++;
            $monthTotal += $record["j$i"];
        }
    }

    echo "  - Jours payables: $daysWithValues\n";
    echo "  - Total du mois: " . number_format($monthTotal / 100, 2) . "€\n";

    // Show first few days as example
    echo "  - Exemples de jours:\n";
    $shown = 0;
    for ($i = 1; $i <= 31 && $shown < 5; $i++) {
        if (isset($record["j$i"]) && $record["j$i"] !== null) {
            $amount = $record["j$i"] / 100;
            echo "    J$i: " . number_format($amount, 2) . "€\n";
            $shown++;
        }
    }

    // Validate record
    $validation = $detailJourService->validateRecord($record);
    echo "  - Validation: " . ($validation['valid'] ? '✓ OK' : '✗ ERREUR') . "\n";
    if (!$validation['valid']) {
        echo "    Erreurs: " . implode(', ', $validation['errors']) . "\n";
    }
    echo "\n";
}

// Generate statistics
if (!empty($detailJourRecords)) {
    echo "\n=== Statistiques ===\n\n";
    $stats = $detailJourService->getStatistics($detailJourRecords);
    echo "Total mois: " . $stats['total_months'] . "\n";
    echo "Total jours: " . $stats['total_days'] . "\n";
    echo "Montant total: " . number_format($stats['total_amount'], 2) . "€\n\n";

    echo "Détail par mois:\n";
    foreach ($stats['months'] as $month => $data) {
        echo "  $month: " . $data['days'] . " jours, " . number_format($data['amount'], 2) . "€\n";
    }
}

// Generate SQL
echo "\n=== SQL INSERT Statements ===\n\n";
if (!empty($detailJourRecords)) {
    $sql = $detailJourService->generateBatchInsertSQL($detailJourRecords);

    // Show first statement as example (they can be very long)
    $lines = explode("\n", trim($sql));
    if (count($lines) > 0) {
        echo "Exemple (premier mois):\n";
        echo $lines[0] . "\n\n";
        echo "Total: " . count($lines) . " instructions SQL générées\n";
    }
} else {
    echo "Aucune instruction SQL à générer.\n";
}

// Generate HTML
echo "\n=== HTML Preview ===\n\n";
$html = $detailJourService->formatDetailJourHTML($detailJourRecords);
echo "HTML table generated (" . strlen($html) . " bytes)\n";

// Save HTML to file for preview
$htmlContent = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Détail Journalier IJ Preview</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1 { color: #667eea; }
        .info { background: #e3f2fd; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        table { border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #ddd; padding: 4px; }
        th { background-color: #667eea; color: white; }
    </style>
</head>
<body>
    <h1>Détail Journalier IJ - Preview</h1>
    <div class='info'>
        <strong>Mock:</strong> $mockFile<br>
        <strong>Mois:</strong> " . count($detailJourRecords) . "<br>
        <strong>Note:</strong> Les montants sont en euros (divisés par 100 pour affichage)
    </div>
    $html
</body>
</html>";

file_put_contents('test_detail_jour_preview.html', $htmlContent);

echo "✓ HTML saved to test_detail_jour_preview.html\n";

echo "\n=== Test Completed ===\n";
