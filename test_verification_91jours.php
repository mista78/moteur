<?php

/**
 * Test de vérification: Le moteur calcule-t-il bien le 91ème jour?
 * Vérification des règles fonctionnelles de text.txt
 */

require_once __DIR__ . '/IJCalculator.php';

echo "\n";
echo "================================================================================\n";
echo "        VÉRIFICATION: Calcul de la date d'effet au 91ème jour                  \n";
echo "================================================================================\n";
echo "\n";

echo "RÈGLES FONCTIONNELLES (text.txt):\n";
echo "==================================\n\n";

echo "Ligne 367: «Le jour de début de droit est la date ultérieure entre\n";
echo "           [le 91ème jour d'arrêt pour un sinistre] OU\n";
echo "           [le 31ème jour suivant la mise à jour du compte cotisant] OU\n";
echo "           [le 31ème jour suivant la déclaration tardive non excusée]»\n\n";

echo "Ligne 410: «[Le 91ème jour d'arrêt de travail] correspond au dépassement\n";
echo "           de 90 du cumul du nombre de jours entre une date de début\n";
echo "           d'arrêt de travail et une date de fin d'arrêt de travail.»\n\n";

echo "TRADUCTION:\n";
echo "- Seuil: 90 jours de décompte (jours 1 à 90 non payés)\n";
echo "- Date d'effet: 91ème jour (premier jour payé)\n";
echo "- Paiement commence quand cumul > 90 jours\n\n";

echo "================================================================================\n\n";

$calculator = new IJCalculator(__DIR__ . '/taux.csv');

// Test 1: Arrêt de exactement 90 jours
echo "TEST 1: Arrêt de exactement 90 jours\n";
echo "======================================\n\n";

$test1 = [
    'arrets' => [
        [
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-03-30',  // 90 jours exactement
            'rechute-line' => 0,
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
    'current_date' => '2024-04-01',
    'attestation_date' => '2024-04-01',
    'last_payment_date' => null,
    'affiliation_date' => '2020-01-01',
    'nb_trimestres' => 16,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => false,
];

$result1 = $calculator->calculateAmount($test1);

echo "Période d'arrêt: 2024-01-01 → 2024-03-30\n";
echo "Durée: 90 jours\n";
echo "Date d'effet calculée: " . ($result1['payment_details'][0]['date_effet'] ?? 'null') . "\n";
echo "Jours payables: " . $result1['nb_jours'] . " jours\n\n";

if ($result1['nb_jours'] == 0) {
    echo "✅ CORRECT: Aucun jour payé car cumul = 90 jours (seuil non dépassé)\n";
    echo "   → Le paiement commence uniquement quand cumul > 90\n\n";
} else {
    echo "❌ ERREUR: Il y a des jours payés alors que cumul = 90\n\n";
}

echo "--------------------------------------------------------------------------------\n\n";

// Test 2: Arrêt de 91 jours (premier jour de paiement)
echo "TEST 2: Arrêt de 91 jours (devrait payer 1 jour)\n";
echo "==================================================\n\n";

$test2 = [
    'arrets' => [
        [
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-03-31',  // 91 jours
            'rechute-line' => 0,
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
    'current_date' => '2024-04-01',
    'attestation_date' => '2024-04-01',
    'last_payment_date' => null,
    'affiliation_date' => '2020-01-01',
    'nb_trimestres' => 16,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => false,
];

$result2 = $calculator->calculateAmount($test2);

echo "Période d'arrêt: 2024-01-01 → 2024-03-31\n";
echo "Durée: 91 jours\n";
echo "Date d'effet calculée: " . ($result2['payment_details'][0]['date_effet'] ?? 'null') . "\n";
echo "Décompte (non payé): " . ($result2['payment_details'][0]['decompte_days'] ?? 0) . " jours\n";
echo "Jours payables: " . $result2['nb_jours'] . " jours\n\n";

if ($result2['nb_jours'] == 1 && $result2['payment_details'][0]['date_effet'] == '2024-03-31') {
    echo "✅ CORRECT: 1 jour payé (le 91ème)\n";
    echo "   → Décompte: 90 jours (du 01/01 au 30/03)\n";
    echo "   → Date d'effet: 2024-03-31 (91ème jour)\n";
    echo "   → Premier jour payé: 2024-03-31\n\n";
} else {
    echo "❌ ERREUR: Le calcul n'est pas correct\n\n";
}

echo "--------------------------------------------------------------------------------\n\n";

// Test 3: Arrêt de 121 jours (cas réel)
echo "TEST 3: Arrêt de 121 jours (cas réel avec 31 jours payables)\n";
echo "=============================================================\n\n";

$test3 = [
    'arrets' => [
        [
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-04-30',  // 121 jours
            'rechute-line' => 0,
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
    'current_date' => '2024-05-01',
    'attestation_date' => '2024-05-01',
    'last_payment_date' => null,
    'affiliation_date' => '2020-01-01',
    'nb_trimestres' => 16,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => false,
];

$result3 = $calculator->calculateAmount($test3);

echo "Période d'arrêt: 2024-01-01 → 2024-04-30\n";
echo "Durée: 121 jours\n";
echo "Date d'effet calculée: " . ($result3['payment_details'][0]['date_effet'] ?? 'null') . "\n";
echo "Décompte (non payé): " . ($result3['payment_details'][0]['decompte_days'] ?? 0) . " jours\n";
echo "Jours payables: " . $result3['nb_jours'] . " jours\n\n";

if ($result3['nb_jours'] == 31 &&
    $result3['payment_details'][0]['date_effet'] == '2024-03-31' &&
    $result3['payment_details'][0]['decompte_days'] == 90) {
    echo "✅ CORRECT:\n";
    echo "   → Décompte: 90 jours (du 01/01 au 30/03) - NON PAYÉS\n";
    echo "   → Date d'effet: 2024-03-31 (91ème jour)\n";
    echo "   → Jours payés: 31 jours (du 31/03 au 30/04)\n";
    echo "   → Vérification: 90 + 31 = 121 jours ✓\n\n";
} else {
    echo "❌ ERREUR: Le calcul n'est pas correct\n\n";
}

echo "--------------------------------------------------------------------------------\n\n";

// Test 4: Cumul progressif sur plusieurs arrêts
echo "TEST 4: Cumul progressif (3 arrêts pour atteindre 90 jours)\n";
echo "============================================================\n\n";

$test4 = [
    'arrets' => [
        [
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-01-31',  // 31 jours
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2024-01-05',
            'valid_med_controleur' => 1,
            'cco_a_jour' => 1,
        ],
        [
            'arret-from-line' => '2024-02-01',
            'arret-to-line' => '2024-02-29',  // 29 jours (cumul: 60)
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2024-02-05',
            'valid_med_controleur' => 1,
            'cco_a_jour' => 1,
        ],
        [
            'arret-from-line' => '2024-03-01',
            'arret-to-line' => '2024-04-30',  // 61 jours (cumul: 121)
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

$result4 = $calculator->calculateAmount($test4);

echo "Arrêt 1: 01/01 → 31/01 (31 jours) - Cumul: 31j\n";
echo "Arrêt 2: 01/02 → 29/02 (29 jours) - Cumul: 60j\n";
echo "Arrêt 3: 01/03 → 30/04 (61 jours) - Cumul: 121j\n\n";

foreach ($result4['payment_details'] as $i => $detail) {
    echo "Arrêt #" . ($i+1) . ":\n";
    echo "  Durée: {$detail['arret_diff']}j\n";
    echo "  Décompte: {$detail['decompte_days']}j\n";
    echo "  Date d'effet: " . ($detail['date_effet'] ?? 'null') . "\n";
    echo "  Jours payés: {$detail['payable_days']}j\n\n";
}

echo "Total jours payés: {$result4['nb_jours']} jours\n\n";

if ($result4['nb_jours'] == 31) {
    echo "✅ CORRECT:\n";
    echo "   → Arrêts 1 & 2: décompte (31 + 29 = 60j) - NON PAYÉS\n";
    echo "   → Arrêt 3: 30j de décompte + 31j payés\n";
    echo "   → Total décompte: 90 jours (seuil atteint)\n";
    echo "   → Date d'effet: 2024-03-31 (91ème jour cumulé)\n";
    echo "   → Paiement: du 91ème au 121ème jour = 31 jours ✓\n\n";
} else {
    echo "❌ ERREUR: Le calcul n'est pas correct\n\n";
}

echo "================================================================================\n\n";

echo "CONCLUSION:\n";
echo "===========\n\n";

echo "Le moteur calcule-t-il bien le 91ème jour selon text.txt?\n\n";

// Vérification finale
if ($result1['nb_jours'] == 0 &&
    $result2['nb_jours'] == 1 &&
    $result3['nb_jours'] == 31 &&
    $result4['nb_jours'] == 31) {

    echo "✅ OUI - Le moteur est CONFORME aux règles fonctionnelles:\n\n";
    echo "1. ✓ Seuil de 90 jours respecté (décompte)\n";
    echo "2. ✓ Paiement commence au 91ème jour (date d'effet)\n";
    echo "3. ✓ Cumul progressif correctement implémenté\n";
    echo "4. ✓ Formule: cumul > 90 → paiement à partir du jour 91\n\n";

    echo "IMPLÉMENTATION TECHNIQUE:\n";
    echo "------------------------\n";
    echo "Services/DateService.php ligne 290:\n";
    echo "  \$lessDate = 90 - (\$newNbJours - \$arret_diff);\n";
    echo "  → Calcule le jour où cumul > 90\n\n";

    echo "Services/DateService.php ligne 313:\n";
    echo "  if (\$newNbJours > 90) {\n";
    echo "  → Date d'effet définie uniquement si cumul > 90\n\n";

    echo "Règle text.txt ligne 410 RESPECTÉE ✓\n";

} else {
    echo "❌ NON - Il y a des incohérences!\n\n";
    echo "Détails des résultats:\n";
    echo "- Test 90j: {$result1['nb_jours']} jours (attendu: 0)\n";
    echo "- Test 91j: {$result2['nb_jours']} jours (attendu: 1)\n";
    echo "- Test 121j: {$result3['nb_jours']} jours (attendu: 31)\n";
    echo "- Test cumul: {$result4['nb_jours']} jours (attendu: 31)\n";
}

echo "\n";
