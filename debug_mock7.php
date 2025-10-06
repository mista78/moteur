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

echo "Age: " . $result['age'] . "\n";
echo "Total jours: " . $result['nb_jours'] . "\n";
echo "Montant: " . number_format($result['montant'], 2, '.', '') . "€\n";
echo "Attendu: 74331.79€\n\n";

$totalCalculated = 0;
foreach ($result['payment_details'] as $idx => $pd) {
    if ($pd['payable_days'] > 0) {
        echo "Arrêt #$idx: {$pd['payable_days']} jours\n";
        if (isset($pd['rate_breakdown'])) {
            foreach ($pd['rate_breakdown'] as $rb) {
                $amount = $rb['days'] * $rb['rate'];
                echo "  Période {$rb['period']}: {$rb['days']} jours x " . number_format($rb['rate'], 2, '.', '') . "€ = " . number_format($amount, 2, '.', '') . "€ (taux {$rb['taux']})\n";
            }
        }
        echo "  Total arrêt: " . number_format($pd['montant'], 2, '.', '') . "€\n\n";
        $totalCalculated += $pd['montant'];
    }
}

echo "Total recalculé: " . number_format($totalCalculated, 2, '.', '') . "€\n";
