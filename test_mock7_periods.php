<?php

require_once 'IJCalculator.php';

$calculator = new IJCalculator('/home/mista/work/ij/taux.csv');

$mock7 = json_decode(file_get_contents('/home/mista/work/ij/mock7.json'), true);

$data7 = [
    'arrets' => $mock7,
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1960-01-29',
    'current_date' => '2024-09-30',
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0
];

$result = $calculator->calculateAmount($data7);

echo "=== MOCK7 DÉTAILS PÉRIODES ===\n";
echo "Calculé: " . number_format($result['montant'], 2) . "€\n";
echo "Attendu: 57099.15€\n";
echo "Différence: " . number_format($result['montant'] - 57099.15, 2) . "€\n\n";

echo "Date d'effet: " . $mock7[0]['date_deb_droit'] . "\n";
echo "Total jours: " . $result['nb_jours'] . "\n";
echo "Age: " . $result['age'] . "\n\n";

if (isset($result['payment_details'][0]['rate_breakdown'])) {
    echo "=== BREAKDOWN PAR PÉRIODE ===\n";

    $periodTotals = [1 => 0, 2 => 0, 3 => 0];
    $periodAmounts = [1 => 0, 2 => 0, 3 => 0];

    foreach ($result['payment_details'][0]['rate_breakdown'] as $rb) {
        $period = $rb['period'] ?? 0;
        if ($period > 0) {
            $periodTotals[$period] += $rb['days'];
            $periodAmounts[$period] += $rb['days'] * $rb['rate'];
        }
        echo "Year {$rb['year']}, Période {$period}, Taux {$rb['taux']}, Rate {$rb['rate']}€, {$rb['days']}j\n";
    }

    echo "\n=== TOTAUX PAR PÉRIODE ===\n";
    echo "Période 1 (taux 1): " . $periodTotals[1] . " jours, " . number_format($periodAmounts[1], 2) . "€\n";
    echo "Période 2 (taux 7): " . $periodTotals[2] . " jours, " . number_format($periodAmounts[2], 2) . "€\n";
    echo "Période 3 (taux 4): " . $periodTotals[3] . " jours, " . number_format($periodAmounts[3], 2) . "€\n";
    echo "Total: " . ($periodTotals[1] + $periodTotals[2] + $periodTotals[3]) . " jours\n\n";

    echo "Vous aviez dit:\n";
    echo "- Premiers 394 jours à taux 1\n";
    echo "- 365 jours suivants à taux 7\n";
    echo "- 246 jours à taux 4\n";
}
