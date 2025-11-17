<?php

require_once __DIR__ . '/Services/DateCalculationInterface.php';
require_once __DIR__ . '/Services/DateService.php';
require_once __DIR__ . '/Services/ArretService.php';

use App\IJCalculator\Services\ArretService;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║          USE ONLY ARRÊTS LIST - NO OTHER DATA               ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$arretService = new ArretService();

// ONLY arrêts list - all data is in the arrêts themselves
$arrets = [
    [
        'adherent_number' => '1234567',
        'num_sinistre' => 12345,
        'code_pathologie' => 'A',
        'arret-from-line' => '2024-01-01',
        'arret-to-line' => '2024-02-15',
        'valid_med_controleur' => 1,
        'rechute-line' => 0,
        'dt-line' => 1,
        'declaration-date-line' => '2024-01-01',
        'attestation_date' => '2024-06-12'
    ],
    [
        'adherent_number' => '1234567',
        'num_sinistre' => 12345,
        'code_pathologie' => 'A',
        'arret-from-line' => '2024-03-01',
        'arret-to-line' => '2024-12-31',
        'valid_med_controleur' => 1,
        'rechute-line' => 0,
        'dt-line' => 1,
        'declaration-date-line' => '2024-03-01',
        'attestation_date' => '2024-06-12'
    ]
];

echo "📋 INPUT: ONLY arrêts array (no separate inputData)\n";
echo "   - All required fields are IN the arrêts themselves\n";
echo "   - adherent_number: " . $arrets[0]['adherent_number'] . "\n";
echo "   - num_sinistre: " . $arrets[0]['num_sinistre'] . "\n";
echo "   - Number of arrêts: " . count($arrets) . "\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "🔧 METHOD 1: Calculate Date-Effet Only\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Just pass the arrêts - no other parameters needed!
$arretsWithDateEffet = $arretService->calculateDateEffetForArrets($arrets);

foreach ($arretsWithDateEffet as $i => $arret) {
    echo "Arrêt #" . ($i + 1) . ":\n";
    echo "  Dates: {$arret['arret-from-line']} → {$arret['arret-to-line']}\n";
    echo "  Date-effet: " . ($arret['date-effet'] ?: 'NULL') . "\n";
    echo "  Décompte days: " . ($arret['decompte_days'] ?? 0) . "\n";
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "📝 METHOD 2: Generate Database Records\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Just pass the arrêts - no inputData needed!
$records = $arretService->generateArretRecordsFromList($arrets);

echo "✅ Generated " . count($records) . " ij_arret records\n\n";

foreach ($records as $i => $record) {
    echo "RECORD #" . ($i + 1) . ":\n";
    echo "  adherent_number: {$record['adherent_number']}\n";
    echo "  num_sinistre: {$record['num_sinistre']}\n";
    echo "  code_pathologie: {$record['code_pathologie']}\n";
    echo "  date_start: {$record['date_start']}\n";
    echo "  date_end: {$record['date_end']}\n";
    echo "  date_deb_droit: " . ($record['date_deb_droit'] ?? 'NULL') . "\n";
    echo "  decompte_days: {$record['decompte_days']}\n";
    echo "  first_day: {$record['first_day']}\n";
    echo "  rechute: {$record['rechute']}\n";
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "📊 SQL GENERATION\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$sql = $arretService->generateBatchInsertSQL($records);
echo "Batch INSERT SQL:\n";
echo substr($sql, 0, 200) . "...\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ SIMPLE USAGE\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "1. Just pass arrêts array:\n";
echo "   \$records = \$arretService->generateArretRecordsFromList(\$arrets);\n\n";

echo "2. No need for separate inputData!\n";
echo "   - adherent_number is in each arrêt\n";
echo "   - num_sinistre is in each arrêt\n";
echo "   - All other fields extracted from arrêts\n\n";

echo "3. Insert into database:\n";
echo "   \$sql = \$arretService->generateBatchInsertSQL(\$records);\n";
echo "   \$pdo->exec(\$sql);\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "💡 FIELD MAPPING\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "The service automatically extracts:\n";
echo "  • adherent_number from arrêt['adherent_number'] or ['num_adherent']\n";
echo "  • num_sinistre from arrêt['num_sinistre'] or ['sinistre_id']\n";
echo "  • attestation_date from arrêt['attestation_date'] or ['date_dern_attestation']\n";
echo "  • birth_date from arrêt['birth_date'] (if present)\n\n";

echo "✅ RESULT: Clean, simple API - just pass the arrêts!\n";
