<?php

require_once __DIR__ . '/IJCalculator.php';
require_once __DIR__ . '/Services/ArretService.php';
require_once __DIR__ . '/Tools/Tools.php';

use App\IJCalculator\IJCalculator;
use App\IJCalculator\Services\ArretService;
use App\Tools\Tools;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           IJ_ARRET TABLE RECORDS GENERATION                  ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Initialize calculator and service
$calculator = new IJCalculator(__DIR__ . '/taux.csv');
$arretService = new ArretService();

// Load mock data
$mockData = json_decode(file_get_contents(__DIR__ . '/mock2.json'), true);
$mockData = Tools::renommerCles($mockData, Tools::$correspondance);

// Input data with required fields for ij_arret table
$inputData = [
    'arrets' => $mockData,
    'adherent_number' => '1234567',  // Required: 7 characters
    'num_sinistre' => 12345,         // Required: integer
    'statut' => 'M',
    'classe' => 'C',
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
    'patho_anterior' => 0
];

// Calculate
$result = $calculator->calculateAmount($inputData);

echo "📊 CALCULATION RESULT:\n";
echo "   Montant: {$result['montant']}€\n";
echo "   Nb jours: {$result['nb_jours']}\n";
echo "   Arrêts (processed): " . count($result['arrets']) . "\n";
echo "   Arrêts (merged): " . count($result['arrets_merged']) . "\n\n";

// Generate ij_arret records
echo "═══════════════════════════════════════════════════════════════\n";
echo "📝 GENERATING IJ_ARRET RECORDS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$arretRecords = $arretService->generateArretRecords($result, $inputData);

echo "✅ Generated " . count($arretRecords) . " ij_arret records\n\n";

// Display records
foreach ($arretRecords as $i => $record) {
    echo "RECORD #" . ($i + 1) . ":\n";
    echo "  adherent_number: {$record['adherent_number']}\n";
    echo "  num_sinistre: {$record['num_sinistre']}\n";
    echo "  code_pathologie: " . ($record['code_pathologie'] ?? 'NULL') . "\n";
    echo "  date_start: " . ($record['date_start'] ?? 'NULL') . "\n";
    echo "  date_end: " . ($record['date_end'] ?? 'NULL') . "\n";
    echo "  date_prolongation: " . ($record['date_prolongation'] ?? 'NULL') . "\n";
    echo "  first_day: " . ($record['first_day']) . "\n";
    echo "  date_declaration: " . ($record['date_declaration'] ?? 'NULL') . "\n";
    echo "  DT_excused: " . ($record['DT_excused'] ?? 'NULL') . "\n";
    echo "  valid_med_controleur: " . ($record['valid_med_controleur'] ?? 'NULL') . "\n";
    echo "  date_deb_droit: " . ($record['date_deb_droit'] ?? 'NULL') . "\n";
    echo "  taux: " . ($record['taux'] ?? 'NULL') . "\n";
    echo "  version: {$record['version']}\n";
    echo "  actif: {$record['actif']}\n";
    echo "\n";
}

// Generate SQL INSERT statements
echo "═══════════════════════════════════════════════════════════════\n";
echo "📄 SQL INSERT STATEMENTS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "--- Single INSERT per record ---\n\n";
foreach ($arretRecords as $i => $record) {
    if ($i < 2) { // Show only first 2 to avoid clutter
        echo $arretService->generateInsertSQL($record) . "\n\n";
    }
}
if (count($arretRecords) > 2) {
    echo "... (" . (count($arretRecords) - 2) . " more records)\n\n";
}

echo "--- Batch INSERT (all records) ---\n\n";
$batchSQL = $arretService->generateBatchInsertSQL($arretRecords);
echo $batchSQL . "\n\n";

// Validate records
echo "═══════════════════════════════════════════════════════════════\n";
echo "✓ VALIDATION\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$allValid = true;
foreach ($arretRecords as $i => $record) {
    $valid = $arretService->validateRecord($record);
    if (!$valid) {
        echo "❌ Record #" . ($i + 1) . " is INVALID\n";
        $allValid = false;
    }
}

if ($allValid) {
    echo "✅ All records are VALID and ready for database insertion\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "📋 SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Generated: " . count($arretRecords) . " ij_arret records\n";
echo "All valid: " . ($allValid ? 'YES' : 'NO') . "\n";
echo "Ready for: Database insertion via SQL or ORM\n\n";

echo "💡 USAGE:\n";
echo "  1. Execute batch SQL to insert all records\n";
echo "  2. Or use ORM (CakePHP) to save records individually\n";
echo "  3. Records include all required foreign keys\n";
echo "  4. Merged arrêts are properly tracked with date_prolongation\n";
