<?php

require_once 'IJCalculator.php';

$calc = new IJCalculator('taux.csv');
$mockData = json_decode(file_get_contents('mock9.json'), true);
$result = $calc->calculateAmount([
    'arrets' => $mockData,
    'statut' => 'M',
    'classe' => 'B',
    'option' => 100,
    'birth_date' => '1953-01-22',
    'current_date' => '2024-09-30',
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 60,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0
]);

echo "Birth date: 1953-01-22\n";
echo "70th birthday: 2023-01-22\n";
echo "Payment period: 2022-05-23 to 2024-05-21\n";
echo "Days before 70: 244 (2022-05-23 to 2023-01-21)\n";
echo "Days after 70: 486 (2023-01-22 to 2024-05-21)\n\n";

echo "Age: " . $result['age'] . "\n";
echo "Total jours: " . $result['nb_jours'] . "\n";
echo "Montant: " . number_format($result['montant'], 2, '.', '') . "€\n";
echo "Attendu: 53467.98€\n";
echo "Différence: " . number_format($result['montant'] - 53467.98, 2, '.', '') . "€\n\n";

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
