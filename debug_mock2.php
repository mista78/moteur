<?php

require_once 'IJCalculator.php';

echo "\n=== Debug Mock2 ===\n\n";

$calculator = new IJCalculator('taux.csv');

$mockData = json_decode(file_get_contents('mock2.json'), true);

$data = [
    'arrets' => $mockData,
    'statut' => 'M',
    'classe' => 'C',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1958-06-03',
    'current_date' => date("Y-m-d"),  // TODAY
    'attestation_date' => '2024-06-12',
    'last_payment_date' => null,
    'affiliation_date' => "1991-07-01",
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => false
];

echo "Current date (today): " . $data['current_date'] . "\n";
echo "Birth date: " . $data['birth_date'] . "\n";
echo "Attestation date: " . $data['attestation_date'] . "\n";
echo "Statut: " . $data['statut'] . ", Classe: " . $data['classe'] . "\n\n";

$result = $calculator->calculateAmount($data);

echo "=== RESULTS ===\n";
echo "Expected amount: 17318.92€\n";
echo "Actual amount: " . number_format($result['montant'], 2) . "€\n";
echo "Difference: " . number_format($result['montant'] - 17318.92, 2) . "€\n\n";

echo "Expected payable days: 116\n";
echo "Actual payable days: " . $result['nb_jours'] . "\n";
echo "Total cumulative days: " . $result['total_cumul_days'] . "\n";
echo "Age: " . $result['age'] . "\n\n";

echo "=== ARRÊTS DETAILS ===\n";
foreach ($result['arrets'] as $i => $arret) {
    echo "Arrêt " . ($i+1) . ":\n";
    echo "  Period: " . $arret['arret-from-line'] . " to " . $arret['arret-to-line'] . "\n";
    echo "  Days: " . $arret['arret_diff'] . "\n";
    echo "  Date effet: " . ($arret['date-effet'] ?? 'N/A') . "\n";
    echo "  Date deb droit: " . ($arret['date_deb_droit'] ?? 'N/A') . "\n";
    echo "  Declaration: " . ($arret['declaration-date-line'] ?? 'N/A') . "\n";
    echo "  DT line: " . ($arret['dt-line'] ?? 'N/A') . "\n";
    echo "  Rechute line: " . ($arret['rechute-line'] ?? 'N/A') . "\n";

    // Check if detected as rechute
    if ($i > 0) {
        $prevEnd = new DateTime($result['arrets'][$i-1]['arret-to-line']);
        $currStart = new DateTime($arret['arret-from-line']);
        $diff = $prevEnd->diff($currStart)->days;
        echo "  Gap from previous: " . $diff . " days\n";

        $oneYearLimit = clone $prevEnd;
        $oneYearLimit->modify('+1 year')->modify('-1 day');
        $isWithinYear = $currStart <= $oneYearLimit;
        echo "  Within 1 year? " . ($isWithinYear ? 'YES (potential rechute)' : 'NO (new pathology)') . "\n";
    }
    echo "\n";
}

echo "\n=== PAYMENT DETAILS ===\n";
if (isset($result['payment_details'])) {
    foreach ($result['payment_details'] as $i => $detail) {
        echo "Payment " . ($i+1) . ":\n";
        echo "  Arrêt index: " . ($detail['arret_index'] ?? 'N/A') . "\n";
        echo "  Payment days: " . ($detail['payable_days'] ?? 0) . "\n";
        echo "  Payment start: " . ($detail['payment_start'] ?? 'N/A') . "\n";
        echo "  Payment end: " . ($detail['payment_end'] ?? 'N/A') . "\n";
        echo "\n";
    }
}

echo "\n=== ANALYSIS ===\n";
echo "If amount is different:\n";
echo "1. Check if arrêts are being incorrectly identified as rechutes\n";
echo "2. Check if date effet calculations changed\n";
echo "3. Check if current_date affects age-based rate calculations\n";
echo "4. Mock2 age at current date: " . $result['age'] . " years (birth: 1958, should be 66-67)\n";
echo "5. Age 62-69 uses tiered rates (taux 1/7/4 depending on cumul days)\n";
