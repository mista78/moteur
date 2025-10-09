<?php

require_once 'IJCalculator.php';

$mockData = json_decode(file_get_contents('mock9.json'), true);

$calculator = new IJCalculator('taux.csv');

$data = [
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
    'patho_anterior' => 0,
    'arrets' => $mockData
];

$calculator->setPassValue(47000);
$result = $calculator->calculateAmount($data);

echo "=== MOCK9 DEBUG ===\n";
echo "Birth date: {$data['birth_date']}\n";
echo "Arret: {$mockData[0]['arret-from-line']} to {$mockData[0]['arret-to-line']}\n";
echo "Arret diff: {$mockData[0]['arret_diff']} days\n\n";

if (isset($result['arrets'][0]['date-effet'])) {
    $dateEffet = $result['arrets'][0]['date-effet'];
    echo "Date effet: {$dateEffet}\n";

    $calculator2 = new IJCalculator('taux.csv');
    $ageAtStart = $calculator2->calculateAge($dateEffet, $data['birth_date']);
    $ageNow = $calculator2->calculateAge(date('Y-m-d'), $data['birth_date']);
    echo "Age at date effet: {$ageAtStart} years\n";
    echo "Age now: {$ageNow} years\n\n";
}

echo "Nombre de jours calculé: " . $result['nb_jours'] . "\n";
echo "Nombre de jours attendu: 730\n";
echo "Montant calculé: " . $result['montant'] . "\n";
echo "Montant attendu: 53467.98\n\n";

echo "Payment Details:\n";
echo "================\n";
if (isset($result['payment_details']) && is_array($result['payment_details'])) {
    foreach ($result['payment_details'] as $detailIndex => $detail) {
        echo "\nArrêt $detailIndex:\n";
        echo "  Payable days: " . ($detail['payable_days'] ?? 0) . "\n";
        echo "  Montant: " . ($detail['montant'] ?? 0) . " €\n";

        if (isset($detail['rate_breakdown']) && is_array($detail['rate_breakdown'])) {
            echo "  Rate breakdown (" . count($detail['rate_breakdown']) . " entries):\n";
            $totalDays = 0;
            foreach ($detail['rate_breakdown'] as $index => $breakdown) {
                $totalDays += ($breakdown['days'] ?? 0);
                echo sprintf(
                    "    [%2d] %s -> %s : %3d jours × %7.2f€ (Taux: %s, Period: %s)\n",
                    $index,
                    $breakdown['start'] ?? 'N/A',
                    $breakdown['end'] ?? 'N/A',
                    $breakdown['days'] ?? 0,
                    $breakdown['rate'] ?? 0,
                    $breakdown['taux'] ?? 'N/A',
                    $breakdown['period'] ?? 'N/A'
                );
            }
            echo "\n  Total days in breakdown: $totalDays\n";
        }
    }
}
