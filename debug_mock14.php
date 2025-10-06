<?php

require_once 'IJCalculator.php';

$mock14 = json_decode(file_get_contents('mock14.json'), true);

$requestData = [
    'arrets' => $mock14,
    'statut' => 'M',
    'classe' => 'C',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1985-07-27',
    'current_date' => '2024-10-25',
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => '2017-07-01',
    'nb_trimestres' => 60,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0
];

$calc = new IJCalculator('taux.csv');
$result = $calc->calculateAmount($requestData);

echo "Mock14 Debug\n";
echo "============\n\n";

echo "Arrêt data:\n";
echo "  arret-from-line: {$mock14[0]['arret-from-line']}\n";
echo "  arret-to-line: {$mock14[0]['arret-to-line']}\n";
echo "  attestation-date-line: {$mock14[0]['attestation-date-line']}\n";
echo "  date_deb_droit: {$mock14[0]['date_deb_droit']}\n";
echo "  declaration-date-line: {$mock14[0]['declaration-date-line']}\n";
echo "  dt-line (DT à jour): {$mock14[0]['dt-line']}\n\n";

echo "Test parameters:\n";
echo "  current_date: {$requestData['current_date']}\n";
echo "  birth_date: {$requestData['birth_date']}\n\n";

echo "Calculated result:\n";
echo "  Total days: {$result['nb_jours']}\n";
echo "  Amount: {$result['montant']}\n";
echo "  Expected: 19215.36\n\n";

echo "Payment details:\n";
foreach ($result['payment_details'] as $detail) {
    if ($detail['payable_days'] > 0) {
        echo "  Arrêt {$detail['arret_index']}:\n";
        echo "    Payment period: {$detail['payment_start']} to {$detail['payment_end']}\n";
        echo "    Payable days: {$detail['payable_days']}\n";
        echo "    Attestation extended to: {$detail['attestation_date_extended']}\n";
    }
}

echo "\n\nManual calculation:\n";
$start = new DateTime('2024-06-20');
$end = new DateTime('2024-10-25');
$days = $start->diff($end)->days + 1;
echo "2024-06-20 to 2024-10-25 = $days days\n";

// But check if attestation extends beyond
$att = new DateTime($mock14[0]['attestation-date-line']);
$att_day = (int)$att->format('d');
echo "Attestation date: {$mock14[0]['attestation-date-line']} (day $att_day)\n";
if ($att_day >= 27) {
    $att->modify('last day of this month');
    echo "  -> Extended to end of month: " . $att->format('Y-m-d') . "\n";
}

$arret_end = new DateTime($mock14[0]['arret-to-line']);
$payment_end = min($arret_end, $att);
echo "Payment end: " . $payment_end->format('Y-m-d') . "\n";

$manual_days = $start->diff($payment_end)->days + 1;
echo "Manual days: $manual_days\n";

// Expected calculation
$rate2024 = 150.12; // taux_c1 for 2024
$expected_days = 19215.36 / $rate2024;
echo "\nExpected days (19215.36 / 150.12): " . number_format($expected_days, 2) . " days\n";
