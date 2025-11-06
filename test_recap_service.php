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

use IJCalculator\Services\RecapService;

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

// Test case configurations from test_mocks.php
$testCases = [
    'mock.json' => [
        'expected' => 750.6, // Pas de résultat attendu donné
        'statut' => 'M',
        'classe' => 'A',
        "payment_start" => ["2024-01-22", ""],
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1989-09-26',
        'current_date' => '2024-09-09',
        'attestation_date' => '2024-01-31',
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 8,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0,
        'nbe_jours' => 10
    ],
    'mock2.json' => [
        'expected' => 17318.92,
        "payment_start" => ["", "", "", "", "", "2023-12-07"],
        'statut' => 'M',
        'classe' => 'c',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1958-06-03',
        'current_date' => date("Y-m-d"),
        'attestation_date' => '2024-06-12',
        'last_payment_date' => null,
        'affiliation_date' => "1991-07-01",
        'nb_trimestres' => 8,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'nbe_jours' => 116,
        'patho_anterior' => 0
    ],
    'mock3.json' => [
        'expected' => 41832.6,
        'statut' => 'M',
        'classe' => 'B',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1961-12-01',
        'current_date' => date("Y-m-d"),
        'attestation_date' => '2024-12-27',
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 8,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'nbe_jours' => 374,
        'patho_anterior' => 0
    ],
    'mock4.json' => [
        'expected' => 37875.88,
        'nbe_jours' => 254,
        'statut' => 'M',
        'classe' => 'C',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1958-12-21',
        'current_date' => '2024-09-29',
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 8,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock5.json' => [
        'expected' => 34276.56,
        'statut' => 'CCPL',
        'classe' => 'C',
        'option' => 25,
        'nbe_jours' => 941,
        'pass_value' => 47000,
        'birth_date' => '1984-01-08',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 8,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock6.json' => [
        'expected' => 31412.61,
        "payment_start" => ["", "2024-03-28"],
        'nbe_jours' => 279,
        'statut' => 'M',
        'classe' => 'B',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1962-05-01',
        'current_date' => date("Y-m-d"),
        'attestation_date' => '2024-12-27',
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 50,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock7.json' => [
        'expected' => 74331.79,
        'statut' => 'M',
        'nbe_jours' => 1095,
        'classe' => 'A',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1959-10-07',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => '2008-10-01',
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock8.json' => [
        'expected' => 19291.28,
        'nbe_jours' => 365,
        'statut' => 'M',
        'classe' => 'B',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1950-10-07',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock9.json' => [
        'expected' => 53467.98,
        'nbe_jours' => 730,
        'statut' => 'M',
        'classe' => 'B',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1953-01-22',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock10.json' => [
        'expected' => 51744.25,
        'nbe_jours' => 725,
        'statut' => 'M',
        'classe' => 'A',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1961-10-14',
        'current_date' => date("Y-m-d"),
        'attestation_date' => '2025-01-28',
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock11.json' => [
        'expected' => 10245.69,
        'nbe_jours' => 91,
        'statut' => 'M',
        'classe' => 'B',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1967-09-15',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock12.json' => [
        'expected' => 8330.25,
        'nbe_jours' => 145,
        'statut' => 'M',
        'classe' => 'B',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1953-12-31',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => null,
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock13.json' => [
        'expected' => 4096.96,
        'statut' => 'RSPM',
        'classe' => 'A',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1992-06-24',
        'current_date' => date("Y-m-d"),
        'attestation_date' => "2023-07-31",
        'last_payment_date' => null,
        'affiliation_date' => "2021-10-01",
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock14.json' => [
        'expected' => 19215.36,
        'statut' => 'M',
        'classe' => 'C',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1985-07-27',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => '2017-07-01',
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock15.json' => [
        'expected' => 12497.49,
        'statut' => 'M',
        'classe' => 'B',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1990-04-15',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => '2017-07-01',
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock16.json' => [
        'expected' => 57099.15,
        'statut' => 'M',
        'classe' => 'A',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1960-01-29',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => '2017-07-01',
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock17.json' => [
        'expected' => 47296.39,
        'statut' => 'M',
        'classe' => 'C',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1960-01-05',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => '2017-07-01',
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock18.json' => [
        'expected' => 0,
        'statut' => 'M',
        'classe' => 'A',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1940-05-15',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => '2017-07-01',
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock19.json' => [
        'expected' => 3377.7,
        'statut' => 'M',
        'classe' => 'B',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1989-06-16',
        'current_date' => date("Y-m-d"),
        'attestation_date' => '2024-11-30',
        'last_payment_date' => null,
        'affiliation_date' => '2017-07-01',
        'nb_trimestres' => 60,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 0
    ],
    'mock20.json' => [
        'expected' => 8757,
        'statut' => 'M',
        'classe' => 'A',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1981-03-15',
        'current_date' => date("Y-m-d"),
        'attestation_date' => '2024-12-29',
        'last_payment_date' => null,
        'affiliation_date' => '2019-01-01',
        'nb_trimestres' => 23,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 1
    ],
    'mock21.json' => [
        'expected' => 725.58,  // 29 jours × 25.02€ = 725.58€
        'statut' => 'M',
        'classe' => 'A',
        'option' => 100,
        'pass_value' => 47000,
        'birth_date' => '1972-06-04',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => '2002-10-01',
        'nb_trimestres' => 23,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 1,
        "forced_rate" => 25.02  // Taux journalier forcé
    ],
    'mock22.json' => [
        'expected' => 37.54,
        'statut' => 'RSPM',
        'classe' => 'A',
        'option' => 25,  // 25% option (not 100%)
        'pass_value' => 47000,
        'birth_date' => '1984-07-24',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => '2017-01-01',
        'nb_trimestres' => 23,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 1,
    ],
    'mock23.json' => [
        'expected' => 159033.47,
        'statut' => 'M',
        'classe' => 'C',
        'option' => 100,  // 25% option (not 100%)
        'pass_value' => 47000,
        'birth_date' => '1961-12-10',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => '1990-04-01',
        'nb_trimestres' => 23,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 1,
    ],
    'mock28.json' => [
        'expected' => 4369.62,
        'statut' => 'M',
        'classe' => 'C',
        'option' => 100,  // 25% option (not 100%)
        'pass_value' => 47000,
        'birth_date' => '1958-12-07',
        'current_date' => date("Y-m-d"),
        'attestation_date' => null,
        'last_payment_date' => null,
        'affiliation_date' => '2013-01-01',
        'nb_trimestres' => 23,
        'previous_cumul_days' => 0,
        'prorata' => 1,
        'patho_anterior' => 1,
    ],
];

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
