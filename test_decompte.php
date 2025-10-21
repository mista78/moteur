<?php

/**
 * Test to demonstrate decompte_days feature
 * Shows unpaid accumulation days before date d'effet for each arrêt
 */

require_once __DIR__ . '/IJCalculator.php';

echo "\n";
echo "================================================================================\n";
echo "                   TEST DECOMPTE DAYS (Unpaid Accumulation)                    \n";
echo "================================================================================\n";
echo "\n";

// Create calculator
$calculator = new IJCalculator(__DIR__ . '/taux.csv');

// Test data: Multiple arrêts showing decompte accumulation
$testData = [
    'arrets' => [
        [
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-01-31',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2024-01-05',
            'valid_med_controleur' => 1,
            'cco_a_jour' => 1,
        ],
        [
            'arret-from-line' => '2024-02-01',
            'arret-to-line' => '2024-02-29',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2024-02-05',
            'valid_med_controleur' => 1,
            'cco_a_jour' => 1,
        ],
        [
            'arret-from-line' => '2024-03-01',
            'arret-to-line' => '2024-04-30',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2024-03-05',
            'valid_med_controleur' => 1,
            'cco_a_jour' => 1,
        ]
    ],
    'statut' => 'M',
    'classe' => 'A',
    'option' => 1,
    'pass_value' => 47000,
    'birth_date' => '1970-01-01',
    'current_date' => '2024-05-01',
    'attestation_date' => '2024-05-01',
    'last_payment_date' => null,
    'affiliation_date' => '2020-01-01',
    'nb_trimestres' => 16,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => false,
];

$result = $calculator->calculateAmount($testData);

echo "RÉSULTAT DU CALCUL:\n";
echo "==================\n\n";
echo "Montant total: " . number_format($result['montant'], 2, ',', ' ') . " €\n";
echo "Jours payables: {$result['nb_jours']} jours\n";
echo "Cumul total: {$result['total_cumul_days']} jours\n\n";

echo "DÉTAILS PAR ARRÊT:\n";
echo "==================\n\n";

foreach ($result['payment_details'] as $index => $detail) {
    echo "Arrêt #" . ($index + 1) . ":\n";
    echo "  Période: {$detail['arret_from']} → {$detail['arret_to']}\n";
    echo "  Durée totale: {$detail['arret_diff']} jours\n";
    echo "  Date d'effet: " . ($detail['date_effet'] ?? 'Non défini') . "\n";

    // NOUVELLE FONCTIONNALITÉ: Afficher le décompte
    echo "  \033[33mDécompte (jours non payés avant date d'effet): {$detail['decompte_days']} jours\033[0m\n";

    if ($detail['decompte_days'] > 0) {
        $startDate = new DateTime($detail['arret_from']);
        $dateEffet = new DateTime($detail['date_effet']);
        $dayBefore = clone $dateEffet;
        $dayBefore->modify('-1 day');
        echo "    → Du {$detail['arret_from']} au " . $dayBefore->format('Y-m-d') . " (accumulation vers le seuil)\n";
    }

    echo "  Jours payables: {$detail['payable_days']} jours\n";

    if ($detail['payable_days'] > 0) {
        echo "  Période de paiement: {$detail['payment_start']} → {$detail['payment_end']}\n";
        echo "  Montant: " . number_format($detail['montant'], 2, ',', ' ') . " €\n";
    }

    echo "  Statut: {$detail['reason']}\n";
    echo "\n";
}

echo "================================================================================\n";
echo "\n";
echo "EXPLICATION:\n";
echo "============\n";
echo "Le 'décompte' représente les jours qui:\n";
echo "  - Comptent vers le seuil de 90 jours (nouvelle pathologie) ou 15 jours (rechute)\n";
echo "  - Ne sont PAS payés (avant la date d'effet)\n";
echo "  - S'accumulent pour déterminer quand le paiement commence\n";
echo "\n";
echo "Exemple:\n";
echo "  - Arrêt 1: 31 jours de décompte (accumulation: 0→31 jours)\n";
echo "  - Arrêt 2: 29 jours de décompte (accumulation: 31→60 jours)\n";
echo "  - Arrêt 3: 30 jours de décompte (accumulation: 60→90 jours)\n";
echo "  - À partir du 91ème jour: paiement commence (date d'effet)\n";
echo "\n";
