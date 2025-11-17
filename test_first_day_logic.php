<?php

require_once __DIR__ . '/IJCalculator.php';
require_once __DIR__ . '/Services/ArretService.php';
require_once __DIR__ . '/Tools/Tools.php';

use App\IJCalculator\IJCalculator;
use App\IJCalculator\Services\ArretService;
use App\Tools\Tools;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           FIRST_DAY LOGIC TEST                               ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$calculator = new IJCalculator(__DIR__ . '/taux.csv');
$arretService = new ArretService();

// Load mock2 data
$mockData = json_decode(file_get_contents(__DIR__ . '/mock2.json'), true);
$mockData = Tools::renommerCles($mockData, Tools::$correspondance);

$inputData = [
    'arrets' => $mockData,
    'adherent_number' => '1234567',
    'num_sinistre' => 12345,
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

$result = $calculator->calculateAmount($inputData);

echo "📊 PAYMENT DETAILS ANALYSIS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

foreach ($result['payment_details'] as $i => $detail) {
    $arret = $result['arrets'][$i];

    echo "ARRÊT #" . ($i + 1) . ":\n";
    echo "  Dates: {$detail['arret_from']} → {$detail['arret_to']}\n";
    echo "  Payment start: " . ($detail['payment_start'] ?: 'NONE') . "\n";
    echo "  Payment end: " . ($detail['payment_end'] ?: 'NONE') . "\n";
    echo "  Décompte days: " . ($detail['decompte_days'] ?? 0) . "\n";
    echo "  Payable days: " . ($detail['payable_days'] ?? 0) . "\n";
    echo "  Date-effet: " . ($arret['date-effet'] ?? 'NULL') . "\n";

    // Calculate first_day logic
    $paymentStart = $detail['payment_start'] ?? null;
    $arretFrom = $detail['arret_from'];
    $decompteDays = $detail['decompte_days'] ?? 0;

    $firstDay = 0;
    if ($paymentStart && $arretFrom && $paymentStart === $arretFrom) {
        $firstDay = 1;
        echo "  ✅ first_day = 1 (payment starts on arrêt start date)\n";
    } elseif ($decompteDays == 0 && !empty($paymentStart)) {
        $firstDay = 1;
        echo "  ✅ first_day = 1 (no décompte days)\n";
    } else {
        echo "  ⚪ first_day = 0 (décompte exists: payment starts after arrêt start)\n";
    }

    echo "\n";
}

// Generate records
echo "═══════════════════════════════════════════════════════════════\n";
echo "📝 GENERATED IJ_ARRET RECORDS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$records = $arretService->generateArretRecords($result, $inputData);

foreach ($records as $i => $record) {
    echo "RECORD #" . ($i + 1) . ":\n";
    echo "  date_start: {$record['date_start']}\n";
    echo "  date_end: {$record['date_end']}\n";
    echo "  date_deb_droit: " . ($record['date_deb_droit'] ?? 'NULL') . "\n";
    echo "  first_day: {$record['first_day']} ";

    if ($record['first_day'] == 1) {
        echo "✅ (first day is PAID)\n";
    } else {
        echo "⚪ (first day is EXCUSED/décompte)\n";
    }

    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "📋 FIRST_DAY LOGIC SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "first_day = 1 when:\n";
echo "  • payment_start equals arret_from (no décompte)\n";
echo "  • OR decompte_days = 0 and payment exists\n\n";

echo "first_day = 0 when:\n";
echo "  • payment_start is after arret_from (décompte exists)\n";
echo "  • OR no payment at all (still in décompte period)\n\n";
