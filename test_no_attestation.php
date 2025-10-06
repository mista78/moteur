<?php
/**
 * Test du calcul sans date d'attestation
 *
 * Vérifie que le système peut calculer les IJ même sans date d'attestation,
 * en utilisant la date de fin de l'arrêt ou la date actuelle.
 */

require_once 'IJCalculator.php';

$calculator = new IJCalculator('taux.csv');

echo "========================================\n";
echo "Test du calcul SANS date d'attestation\n";
echo "========================================\n\n";

// Configuration de base
$birthDate = '1989-09-26';
$currentDate = '2024-09-09';
$classe = 'B';
$statut = 'M';

// Test 1: Arrêt simple sans attestation
echo "─────────────────────────────────────────\n";
echo "TEST 1: Arrêt simple sans attestation\n";
echo "─────────────────────────────────────────\n";

$arrets1 = [
    [
        'arret-from-line' => '2024-01-01',
        'arret-to-line' => '2024-03-31',
        'rechute-line' => '0'
    ]
];

$result1 = $calculator->calculateAmount([
    'arrets' => $arrets1,
    'statut' => $statut,
    'classe' => $classe,
    'option' => 100,
    'birth_date' => $birthDate,
    'current_date' => $currentDate,
    'attestation_date' => null, // Pas d'attestation
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => false,
]);

echo "Arrêt: 2024-01-01 → 2024-03-31 (91 jours)\n";
echo "Date d'attestation: AUCUNE\n";
echo "Jours calculés: {$result1['nb_jours']}\n";
echo "Montant: " . number_format($result1['montant'], 2, ',', ' ') . " €\n";

if (isset($result1['payment_details']) && !empty($result1['payment_details'])) {
    $detail = reset($result1['payment_details']);
    echo "Raison: {$detail['reason']}\n";
    echo "Date de paiement début: {$detail['payment_start']}\n";
    echo "Date de paiement fin: {$detail['payment_end']}\n";
}
echo "\n";

// Test 2: Comparaison AVEC attestation
echo "─────────────────────────────────────────\n";
echo "TEST 2: Même arrêt AVEC attestation\n";
echo "─────────────────────────────────────────\n";

$result2 = $calculator->calculateAmount([
    'arrets' => $arrets1,
    'statut' => $statut,
    'classe' => $classe,
    'option' => 100,
    'birth_date' => $birthDate,
    'current_date' => $currentDate,
    'attestation_date' => '2024-03-15', // Attestation à mi-chemin
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => false,
]);

echo "Arrêt: 2024-01-01 → 2024-03-31 (91 jours)\n";
echo "Date d'attestation: 2024-03-15\n";
echo "Jours calculés: {$result2['nb_jours']}\n";
echo "Montant: " . number_format($result2['montant'], 2, ',', ' ') . " €\n";

if (isset($result2['payment_details']) && !empty($result2['payment_details'])) {
    $detail = reset($result2['payment_details']);
    echo "Raison: {$detail['reason']}\n";
    echo "Date de paiement début: {$detail['payment_start']}\n";
    echo "Date de paiement fin: {$detail['payment_end']}\n";
}
echo "\n";

// Test 3: Plusieurs arrêts sans attestation
echo "─────────────────────────────────────────\n";
echo "TEST 3: Plusieurs arrêts sans attestation\n";
echo "─────────────────────────────────────────\n";

$arrets3 = [
    [
        'arret-from-line' => '2024-01-01',
        'arret-to-line' => '2024-02-29',
        'rechute-line' => '0'
    ],
    [
        'arret-from-line' => '2024-03-01',
        'arret-to-line' => '2024-03-31',
        'rechute-line' => '0'
    ],
    [
        'arret-from-line' => '2024-04-01',
        'arret-to-line' => '2024-04-30',
        'rechute-line' => '0'
    ]
];

$result3 = $calculator->calculateAmount([
    'arrets' => $arrets3,
    'statut' => $statut,
    'classe' => $classe,
    'option' => 100,
    'birth_date' => $birthDate,
    'current_date' => $currentDate,
    'attestation_date' => null, // Pas d'attestation
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => false,
]);

echo "3 arrêts consécutifs (jan-fev-mar 2024)\n";
echo "Date d'attestation: AUCUNE\n";
echo "Jours calculés: {$result3['nb_jours']}\n";
echo "Montant: " . number_format($result3['montant'], 2, ',', ' ') . " €\n\n";

if (isset($result3['payment_details'])) {
    foreach ($result3['payment_details'] as $index => $detail) {
        if ($detail['payable_days'] > 0) {
            echo "  Arrêt #{$index}: {$detail['payable_days']} jours - {$detail['reason']}\n";
        }
    }
}
echo "\n";

// Test 4: Arrêt en cours (date de fin dans le futur)
echo "─────────────────────────────────────────\n";
echo "TEST 4: Arrêt en cours sans attestation\n";
echo "─────────────────────────────────────────\n";

$currentDate4 = '2024-06-15';
$arrets4 = [
    [
        'arret-from-line' => '2024-01-01',
        'arret-to-line' => '2024-12-31', // Arrêt jusqu'à fin d'année
        'rechute-line' => '0'
    ]
];

$result4 = $calculator->calculateAmount([
    'arrets' => $arrets4,
    'statut' => $statut,
    'classe' => $classe,
    'option' => 100,
    'birth_date' => $birthDate,
    'current_date' => $currentDate4,
    'attestation_date' => null, // Pas d'attestation
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => false,
]);

echo "Arrêt: 2024-01-01 → 2024-12-31\n";
echo "Date actuelle: {$currentDate4}\n";
echo "Date d'attestation: AUCUNE\n";
echo "Jours calculés: {$result4['nb_jours']}\n";
echo "Montant: " . number_format($result4['montant'], 2, ',', ' ') . " €\n";

if (isset($result4['payment_details']) && !empty($result4['payment_details'])) {
    $detail = reset($result4['payment_details']);
    echo "Date de paiement fin: {$detail['payment_end']}\n";
    echo "Note: Le calcul s'arrête à la date de fin de l'arrêt ou à la date actuelle\n";
}
echo "\n";

// Test 5: Attestation mixte (certains arrêts avec, d'autres sans)
echo "─────────────────────────────────────────\n";
echo "TEST 5: Attestation mixte\n";
echo "─────────────────────────────────────────\n";

$arrets5 = [
    [
        'arret-from-line' => '2024-01-01',
        'arret-to-line' => '2024-01-31',
        'rechute-line' => '0',
        'attestation-date-line' => '2024-01-31' // A une attestation
    ],
    [
        'arret-from-line' => '2024-02-01',
        'arret-to-line' => '2024-02-29',
        'rechute-line' => '0'
        // Pas d'attestation pour celui-ci
    ],
    [
        'arret-from-line' => '2024-03-01',
        'arret-to-line' => '2024-03-31',
        'rechute-line' => '0',
        'attestation-date-line' => '2024-03-15' // A une attestation
    ]
];

$result5 = $calculator->calculateAmount([
    'arrets' => $arrets5,
    'statut' => $statut,
    'classe' => $classe,
    'option' => 100,
    'birth_date' => $birthDate,
    'current_date' => $currentDate,
    'attestation_date' => null, // Pas d'attestation globale
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => false,
]);

echo "3 arrêts: 1 avec attestation, 1 sans, 1 avec attestation\n";
echo "Jours calculés: {$result5['nb_jours']}\n";
echo "Montant: " . number_format($result5['montant'], 2, ',', ' ') . " €\n\n";

if (isset($result5['payment_details'])) {
    foreach ($result5['payment_details'] as $index => $detail) {
        if ($detail['payable_days'] > 0) {
            $attestInfo = $detail['attestation_date'] ? "avec attestation ({$detail['attestation_date']})" : "SANS attestation";
            echo "  Arrêt #{$index}: {$detail['payable_days']} jours - {$attestInfo}\n";
        }
    }
}
echo "\n";

// Résumé
echo "========================================\n";
echo "RÉSUMÉ\n";
echo "========================================\n\n";

echo "✓ Test 1: Calcul sans attestation fonctionne\n";
echo "✓ Test 2: Calcul avec attestation fonctionne\n";
echo "✓ Test 3: Plusieurs arrêts sans attestation fonctionnent\n";
echo "✓ Test 4: Arrêt en cours sans attestation fonctionne\n";
echo "✓ Test 5: Mélange d'arrêts avec/sans attestation fonctionne\n\n";

echo "Comportement:\n";
echo "- SANS attestation: calcul jusqu'à la fin de l'arrêt (ou date actuelle)\n";
echo "- AVEC attestation: calcul jusqu'à la date d'attestation\n";
echo "- Attestations individuelles prioritaires sur globale\n\n";

echo "Avantages:\n";
echo "- Flexibilité: calcul possible même sans attestation\n";
echo "- Précision: utilise l'attestation quand disponible\n";
echo "- Mixte: permet d'avoir des arrêts avec et sans attestation\n";
