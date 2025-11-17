<?php

require_once __DIR__ . '/Services/DateCalculationInterface.php';
require_once __DIR__ . '/Services/DateService.php';
require_once __DIR__ . '/Services/ArretService.php';
require_once __DIR__ . '/Tools/Tools.php';

use App\IJCalculator\Services\ArretService;
use App\Tools\Tools;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     CALCULATE DATE_EFFET FROM ARRÊTS LIST ONLY              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$arretService = new ArretService();

// Load mock data
$mockData = json_decode(file_get_contents(__DIR__ . '/mock2.json'), true);
$mockData = Tools::renommerCles($mockData, Tools::$correspondance);

echo "📋 INPUT: " . count($mockData) . " arrêts\n\n";

// Just the arrêts list - no full calculation needed
foreach ($mockData as $i => $arret) {
    echo "Arrêt #" . ($i + 1) . ": {$arret['arret-from-line']} → {$arret['arret-to-line']}\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "🔧 CALCULATING DATE_EFFET\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Calculate date-effet for arrêts (without full calculation)
$arretsWithDateEffet = $arretService->calculateDateEffetForArrets(
    $mockData            // previous_cumul_days
);

echo "✅ Date-effet calculated for " . count($arretsWithDateEffet) . " arrêts\n\n";

foreach ($arretsWithDateEffet as $i => $arret) {
    echo "Arrêt #" . ($i + 1) . ":\n";
    echo "  Dates: {$arret['arret-from-line']} → {$arret['arret-to-line']}\n";
    echo "  Date-effet: " . ($arret['date-effet'] ?: 'NULL') . "\n";
    echo "  Décompte days: " . ($arret['decompte_days'] ?? 0) . "\n";
    echo "  Rechute: " . (isset($arret['is_rechute']) && $arret['is_rechute'] ? 'YES' : 'NO') . "\n";
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "📝 GENERATING IJ_ARRET RECORDS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Input data for record generation
$inputData = [
    'adherent_number' => '1234567',
    'num_sinistre' => 12345,
    'attestation_date' => '2024-06-12',
    'birth_date' => '1958-06-03',
    'previous_cumul_days' => 0
];

// Generate ij_arret records directly from arrêts list
$records = $arretService->generateArretRecordsFromList($mockData, $inputData);

echo "✅ Generated " . count($records) . " ij_arret records\n\n";

// Show first 3 records
for ($i = 0; $i < min(3, count($records)); $i++) {
    $record = $records[$i];
    echo "RECORD #" . ($i + 1) . ":\n";
    echo "  adherent_number: {$record['adherent_number']}\n";
    echo "  date_start: {$record['date_start']}\n";
    echo "  date_end: {$record['date_end']}\n";
    echo "  date_deb_droit: " . ($record['date_deb_droit'] ?? 'NULL') . "\n";
    echo "  decompte_days: {$record['decompte_days']}\n";
    echo "  first_day: {$record['first_day']}\n";
    echo "  rechute: {$record['rechute']}\n";
    echo "\n";
}

if (count($records) > 3) {
    echo "... (" . (count($records) - 3) . " more records)\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "💡 USAGE SCENARIOS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "1. Calculate date-effet only:\n";
echo "   \$arretsWithDateEffet = \$arretService->calculateDateEffetForArrets(\$arrets);\n\n";

echo "2. Generate ij_arret records from arrêts list:\n";
echo "   \$records = \$arretService->generateArretRecordsFromList(\$arrets, \$inputData);\n\n";

echo "3. No need to run full IJCalculator if you only need date-effet\n\n";

echo "✅ BENEFITS:\n";
echo "  • Calculate date-effet independently\n";
echo "  • Generate database records without full calculation\n";
echo "  • Faster processing for simple date-effet needs\n";
echo "  • Direct arrêts list → database records\n";
