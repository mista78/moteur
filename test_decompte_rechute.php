<?php

/**
 * Test decompte for rechute (relapse) scenario
 * Shows 15-day decompte instead of 90-day for rechute
 */

require_once __DIR__ . '/IJCalculator.php';

echo "\n";
echo "================================================================================\n";
echo "           TEST DECOMPTE - RECHUTE (15-day threshold vs 90-day)                \n";
echo "================================================================================\n";
echo "\n";

$calculator = new IJCalculator(__DIR__ . '/taux.csv');

// Scenario: Previous arrêt + rechute within 1 year
$testData = [
    'arrets' => [
        // First arrêt (already completed)
        [
            'arret-from-line' => '2023-01-01',
            'arret-to-line' => '2023-05-31',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2023-01-05',
            'valid_med_controleur' => 1,
            'cco_a_jour' => 1,
            'date_deb_droit' => '2023-03-31', // Already calculated
        ],
        // Rechute - starts less than 1 year after previous ended
        [
            'arret-from-line' => '2024-01-01',  // Within 1 year of 2023-05-31
            'arret-to-line' => '2024-02-29',
            'rechute-line' => 1,  // Explicitly marked as rechute
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2024-01-05',
            'valid_med_controleur' => 1,
            'cco_a_jour' => 1,
        ]
    ],
    'statut' => 'M',
    'classe' => 'A',
    'option' => 1,
    'pass_value' => 47000,
    'birth_date' => '1970-01-01',
    'current_date' => '2024-03-01',
    'attestation_date' => '2024-03-01',
    'last_payment_date' => null,
    'affiliation_date' => '2020-01-01',
    'nb_trimestres' => 16,
    'previous_cumul_days' => 151,  // Days from first arrêt
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
    $arretNum = $index + 1;
    $isRechute = isset($testData['arrets'][$index]['rechute-line']) && $testData['arrets'][$index]['rechute-line'] == 1;

    echo "Arrêt #{$arretNum}" . ($isRechute ? " \033[36m(RECHUTE)\033[0m" : " (Initial)") . ":\n";
    echo "  Période: {$detail['arret_from']} → {$detail['arret_to']}\n";
    echo "  Durée totale: {$detail['arret_diff']} jours\n";
    echo "  Date d'effet: " . ($detail['date_effet'] ?? 'Non défini') . "\n";

    // Highlight decompte
    if ($isRechute) {
        echo "  \033[33mDécompte (RECHUTE - seuil 15 jours): {$detail['decompte_days']} jours\033[0m\n";
    } else {
        echo "  \033[33mDécompte (INITIAL - seuil 90 jours): {$detail['decompte_days']} jours\033[0m\n";
    }

    if ($detail['decompte_days'] > 0) {
        $startDate = new DateTime($detail['arret_from']);
        $dateEffet = new DateTime($detail['date_effet']);
        $dayBefore = clone $dateEffet;
        $dayBefore->modify('-1 day');
        echo "    → Du {$detail['arret_from']} au " . $dayBefore->format('Y-m-d') . " (accumulation)\n";
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
echo "DIFFÉRENCE RECHUTE vs NOUVELLE PATHOLOGIE:\n";
echo "==========================================\n";
echo "\n";
echo "NOUVELLE PATHOLOGIE:\n";
echo "  - Seuil: 90 jours de décompte avant paiement\n";
echo "  - Paiement commence au 91ème jour (date d'effet)\n";
echo "\n";
echo "RECHUTE (arrêt < 1 an après précédent):\n";
echo "  - Seuil: 15 jours de décompte avant paiement\n";
echo "  - Paiement commence au 15ème jour (date d'effet)\n";
echo "  - Les jours du précédent arrêt sont déjà comptabilisés\n";
echo "\n";
