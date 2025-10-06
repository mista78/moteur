<?php

require_once 'IJCalculator.php';
$calc = new IJCalculator('taux.csv');

// Test mock2 - réussi - médecin 66 ans
$mockData = json_decode(file_get_contents('mock2.json'), true);
$result = $calc->calculateAmount([
    'arrets' => $mockData,
    'statut' => 'M',
    'classe' => 'C',
    'option' => 100,
    'birth_date' => '1958-06-03',
    'current_date' => '2024-06-12',
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0
]);

echo "Mock2 (66 ans - 62-69 ans):\n";
echo "  Age: {$result['age']}\n";
echo "  Jours: {$result['nb_jours']}\n";
echo "  Montant: {$result['montant']}€\n";
echo "  Attendu: 17318.92€\n";
echo "  " . ($result['montant'] == 17318.92 ? '✓' : '✗') . " TEST\n\n";

// Regardons le détail
foreach ($result['payment_details'] as $idx => $pd) {
    if ($pd['payable_days'] > 0) {
        echo "Arrêt #$idx: {$pd['payable_days']} jours\n";
        if (isset($pd['rate_breakdown'])) {
            foreach ($pd['rate_breakdown'] as $rb) {
                echo "  Période {$rb['period']}: {$rb['days']} jours x {$rb['rate']}€ (taux {$rb['taux']})\n";
            }
        }
        echo "  Total arrêt: {$pd['montant']}€\n";
    }
}
