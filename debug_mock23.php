<?php

require_once 'IJCalculator.php';

$mockData = json_decode(file_get_contents('mock23.json'), true);

$calculator = new IJCalculator('taux.csv');

$data = [
    'statut' => 'M',
    'classe' => 'C',
    'option' => 100,
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
    'arrets' => $mockData
];

$calculator->setPassValue(47000);
$result = $calculator->calculateAmount($data);

echo "Montant calculé: " . $result['montant'] . "\n";
echo "Montant attendu: 159033.47\n";
echo "Nombre de jours: " . $result['nb_jours'] . "\n\n";

echo "Payment Details:\n";
echo "================\n";
if (isset($result['payment_details']) && is_array($result['payment_details'])) {
    foreach ($result['payment_details'] as $detailIndex => $detail) {
        echo "\nArrêt $detailIndex:\n";
        echo "  Payable days: " . ($detail['payable_days'] ?? 0) . "\n";
        echo "  Montant: " . ($detail['montant'] ?? 0) . " €\n";

        if (isset($detail['rate_breakdown']) && is_array($detail['rate_breakdown'])) {
            echo "  Rate breakdown:\n";
            foreach ($detail['rate_breakdown'] as $index => $breakdown) {
                echo sprintf(
                    "    [%2d] %s -> %s : %3d jours × %7.2f€ = %9.2f€ (Taux: %s)\n",
                    $index,
                    $breakdown['start'] ?? 'N/A',
                    $breakdown['end'] ?? 'N/A',
                    $breakdown['days'] ?? 0,
                    $breakdown['rate'] ?? 0,
                    ($breakdown['days'] ?? 0) * ($breakdown['rate'] ?? 0),
                    $breakdown['taux'] ?? 'N/A'
                );
            }
        }
    }
}

echo "\n\nTotal amount: " . $result['montant'] . " €\n";
