<?php

require_once 'IJCalculator.php';

$calc = new IJCalculator('taux.csv');
$mockData = json_decode(file_get_contents('mock10.json'), true);
$result = $calc->calculateAmount([
    'arrets' => $mockData,
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1961-10-14',
    'current_date' => '2024-09-30',
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 60,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0
]);

echo "Birth date: 1961-10-14\n";
echo "62nd birthday: 2023-10-14\n";
echo "Payment period: 2023-02-07 to 2025-01-31\n";
echo "Expected: 614j taux 1, 111j taux 7\n\n";

echo "Age: " . $result['age'] . "\n";
echo "Total jours: " . $result['nb_jours'] . "\n";
echo "Montant: " . number_format($result['montant'], 2, '.', '') . "€\n";
echo "Attendu: 51744.25€\n";
echo "Différence: " . number_format($result['montant'] - 51744.25, 2, '.', '') . "€\n\n";

$totalCalculated = 0;
foreach ($result['payment_details'] as $idx => $pd) {
    if ($pd['payable_days'] > 0) {
        echo "Arrêt #$idx: {$pd['payable_days']} jours (arret_diff: {$pd['arret_diff']})\n";
        if (isset($pd['rate_breakdown'])) {
            $periodCounts = [];
            foreach ($pd['rate_breakdown'] as $rb) {
                $period = $rb['period'] ?? 'N/A';
                if (!isset($periodCounts[$period])) {
                    $periodCounts[$period] = 0;
                }
                $periodCounts[$period] += $rb['days'];
            }
            echo "  Résumé par période:\n";
            foreach ($periodCounts as $period => $days) {
                echo "    Période $period: $days jours\n";
            }
            echo "\n  Détail:\n";
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
