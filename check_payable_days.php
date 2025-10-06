<?php

require_once 'IJCalculator.php';

$calc = new IJCalculator('taux.csv');
$mockData = json_decode(file_get_contents('mock7.json'), true);

$result = $calc->calculateAmount([
    'arrets' => $mockData,
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1959-10-07',
    'current_date' => '2024-09-30',
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 60,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0
]);

foreach ($result['payment_details'] as $idx => $pd) {
    echo "Arrêt #$idx:\n";
    echo "  arret-from: {$mockData[$idx]['arret-from-line']}\n";
    echo "  arret-to: {$mockData[$idx]['arret-to-line']}\n";
    echo "  arret_diff: {$mockData[$idx]['arret_diff']}\n";
    echo "  date_deb_droit: {$pd['date_debut_droits']}\n";
    echo "  payable_days: {$pd['payable_days']}\n\n";
}
