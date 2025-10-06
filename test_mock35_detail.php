<?php

require_once 'IJCalculator.php';

$calculator = new IJCalculator('/home/mista/work/ij/taux.csv');

echo "=== MOCK3 DÉTAIL ===\n";
$mock3 = json_decode(file_get_contents('/home/mista/work/ij/mock3.json'), true);
$data3 = [
    'arrets' => $mock3,
    'statut' => 'M',
    'classe' => 'B',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1961-12-01',
    'current_date' => '2024-12-27',
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0
];

$result3 = $calculator->calculateAmount($data3);
echo "Calculé: " . number_format($result3['montant'], 2) . "€\n";
echo "Attendu: 41832.60€\n";
echo "Différence: " . number_format($result3['montant'] - 41832.60, 2) . "€\n";
echo "Jours: " . $result3['nb_jours'] . "\n";
echo "Age: " . $result3['age'] . "\n\n";

echo "=== MOCK5 DÉTAIL ===\n";
$mock5 = json_decode(file_get_contents('/home/mista/work/ij/mock5.json'), true);
$data5 = [
    'arrets' => $mock5,
    'statut' => 'CCPL',
    'classe' => 'C',
    'option' => 25,
    'pass_value' => 47000,
    'birth_date' => '1984-01-08',
    'current_date' => '2024-09-30',
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0
];

$result5 = $calculator->calculateAmount($data5);
echo "Calculé: " . number_format($result5['montant'], 2) . "€\n";
echo "Attendu: 34276.56€\n";
echo "Différence: " . number_format($result5['montant'] - 34276.56, 2) . "€\n";
echo "Jours: " . $result5['nb_jours'] . "\n";
echo "Age: " . $result5['age'] . "\n\n";

// Analyser les taux utilisés
if (isset($result5['payment_details'][0]['rate_breakdown'])) {
    echo "=== MOCK5 BREAKDOWN ===\n";
    $total = 0;
    foreach ($result5['payment_details'][0]['rate_breakdown'] as $rb) {
        $montant = $rb['days'] * $rb['rate'];
        echo "Year {$rb['year']}, Period {$rb['period']}, Taux {$rb['taux']}, Rate {$rb['rate']}€, {$rb['days']}j = " . number_format($montant, 2) . "€\n";
        $total += $montant;
    }
    echo "Total breakdown: " . number_format($total, 2) . "€\n";
}

echo "\n=== ANALYSE ÉCARTS ===\n";
echo "Mock3: écart de " . number_format($result3['montant'] - 41832.60, 2) . "€\n";
echo "Mock5: écart de " . number_format($result5['montant'] - 34276.56, 2) . "€\n";
echo "\nLes deux écarts sont similaires (~220-240€)\n";
echo "Cela suggère un problème systématique avec le découpage ou l'arrondi\n";
