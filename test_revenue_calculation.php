<?php
/**
 * Test des formules de calcul du revenu par classe
 *
 * NOUVELLES RÈGLES:
 * - Classe A: montant_1_pass / 730
 * - Classe B: revenu / 730
 * - Classe C: (montant_1_pass * 3) / 730
 */

require_once 'IJCalculator.php';

$calculator = new IJCalculator('taux.csv');

// PASS value = 47 000 €
$passValue = 47000;
$calculator->setPassValue($passValue);

echo "========================================\n";
echo "Test des formules de calcul du revenu\n";
echo "========================================\n\n";

echo "PASS utilisé: " . number_format($passValue, 0, ',', ' ') . " €\n\n";

// Test Classe A
echo "─────────────────────────────────────────\n";
echo "CLASSE A\n";
echo "─────────────────────────────────────────\n";
echo "Formule: montant_1_pass / 730\n";
$resultA = $calculator->calculateRevenuAnnuel('A');
echo "Revenu annuel: " . number_format($resultA['revenu_annuel'], 2, ',', ' ') . " €\n";
echo "Revenu par jour: " . number_format($resultA['revenu_per_day'], 2, ',', ' ') . " €\n";
echo "Nb PASS: " . $resultA['nb_pass'] . "\n";
echo "Calcul: " . number_format($passValue, 0, ',', ' ') . " / 730 = " . number_format($passValue / 730, 2, ',', ' ') . " €/jour\n\n";

// Test Classe B - sans revenu spécifié (utilise 2 PASS par défaut)
echo "─────────────────────────────────────────\n";
echo "CLASSE B (sans revenu spécifié)\n";
echo "─────────────────────────────────────────\n";
echo "Formule: revenu / 730 (défaut: 2 PASS)\n";
$resultB_default = $calculator->calculateRevenuAnnuel('B');
echo "Revenu annuel (défaut): " . number_format($resultB_default['revenu_annuel'], 2, ',', ' ') . " €\n";
echo "Revenu par jour: " . number_format($resultB_default['revenu_per_day'], 2, ',', ' ') . " €\n";
echo "Nb PASS: " . number_format($resultB_default['nb_pass'], 2, ',', ' ') . "\n";
echo "Calcul: " . number_format($resultB_default['revenu_annuel'], 0, ',', ' ') . " / 730 = " . number_format($resultB_default['revenu_annuel'] / 730, 2, ',', ' ') . " €/jour\n\n";

// Test Classe B - avec revenu de 85 000 €
echo "─────────────────────────────────────────\n";
echo "CLASSE B (revenu: 85 000 €)\n";
echo "─────────────────────────────────────────\n";
echo "Formule: revenu / 730\n";
$revenuB = 85000;
$resultB = $calculator->calculateRevenuAnnuel('B', $revenuB);
echo "Revenu annuel: " . number_format($resultB['revenu_annuel'], 2, ',', ' ') . " €\n";
echo "Revenu par jour: " . number_format($resultB['revenu_per_day'], 2, ',', ' ') . " €\n";
echo "Nb PASS: " . number_format($resultB['nb_pass'], 2, ',', ' ') . "\n";
echo "Calcul: " . number_format($revenuB, 0, ',', ' ') . " / 730 = " . number_format($revenuB / 730, 2, ',', ' ') . " €/jour\n\n";

// Test Classe B - avec revenu de 100 000 €
echo "─────────────────────────────────────────\n";
echo "CLASSE B (revenu: 100 000 €)\n";
echo "─────────────────────────────────────────\n";
echo "Formule: revenu / 730\n";
$revenuB2 = 100000;
$resultB2 = $calculator->calculateRevenuAnnuel('B', $revenuB2);
echo "Revenu annuel: " . number_format($resultB2['revenu_annuel'], 2, ',', ' ') . " €\n";
echo "Revenu par jour: " . number_format($resultB2['revenu_per_day'], 2, ',', ' ') . " €\n";
echo "Nb PASS: " . number_format($resultB2['nb_pass'], 2, ',', ' ') . "\n";
echo "Calcul: " . number_format($revenuB2, 0, ',', ' ') . " / 730 = " . number_format($revenuB2 / 730, 2, ',', ' ') . " €/jour\n\n";

// Test Classe C
echo "─────────────────────────────────────────\n";
echo "CLASSE C\n";
echo "─────────────────────────────────────────\n";
echo "Formule: (montant_1_pass * 3) / 730\n";
$resultC = $calculator->calculateRevenuAnnuel('C');
echo "Revenu annuel: " . number_format($resultC['revenu_annuel'], 2, ',', ' ') . " €\n";
echo "Revenu par jour: " . number_format($resultC['revenu_per_day'], 2, ',', ' ') . " €\n";
echo "Nb PASS: " . $resultC['nb_pass'] . "\n";
echo "Calcul: (" . number_format($passValue, 0, ',', ' ') . " * 3) / 730 = " . number_format(($passValue * 3) / 730, 2, ',', ' ') . " €/jour\n\n";

// Comparaison des revenus journaliers
echo "========================================\n";
echo "COMPARAISON DES REVENUS JOURNALIERS\n";
echo "========================================\n\n";

echo "Classe A: " . number_format($resultA['revenu_per_day'], 2, ',', ' ') . " €/jour\n";
echo "Classe B (85 000 €): " . number_format($resultB['revenu_per_day'], 2, ',', ' ') . " €/jour\n";
echo "Classe B (100 000 €): " . number_format($resultB2['revenu_per_day'], 2, ',', ' ') . " €/jour\n";
echo "Classe C: " . number_format($resultC['revenu_per_day'], 2, ',', ' ') . " €/jour\n\n";

// Ratios
echo "Ratio B(85k)/A: " . number_format($resultB['revenu_per_day'] / $resultA['revenu_per_day'], 2, ',', ' ') . "x\n";
echo "Ratio B(100k)/A: " . number_format($resultB2['revenu_per_day'] / $resultA['revenu_per_day'], 2, ',', ' ') . "x\n";
echo "Ratio C/A: " . number_format($resultC['revenu_per_day'] / $resultA['revenu_per_day'], 2, ',', ' ') . "x\n";
echo "Ratio C/B(85k): " . number_format($resultC['revenu_per_day'] / $resultB['revenu_per_day'], 2, ',', ' ') . "x\n\n";

// Test avec différentes valeurs de PASS
echo "========================================\n";
echo "TEST AVEC DIFFÉRENTES VALEURS DE PASS\n";
echo "========================================\n\n";

foreach ([43992, 47000, 50000] as $testPass) {
    echo "─── PASS = " . number_format($testPass, 0, ',', ' ') . " € ───\n";
    $calculator->setPassValue($testPass);

    $rA = $calculator->calculateRevenuAnnuel('A');
    $rB = $calculator->calculateRevenuAnnuel('B', 85000);
    $rC = $calculator->calculateRevenuAnnuel('C');

    echo "Classe A: " . number_format($rA['revenu_per_day'], 2, ',', ' ') . " €/jour\n";
    echo "Classe B (85 000 €): " . number_format($rB['revenu_per_day'], 2, ',', ' ') . " €/jour\n";
    echo "Classe C: " . number_format($rC['revenu_per_day'], 2, ',', ' ') . " €/jour\n\n";
}

// Test des cas limites
echo "========================================\n";
echo "CAS LIMITES\n";
echo "========================================\n\n";

$calculator->setPassValue(47000);

echo "Classe B avec revenu = 1 PASS (47 000 €):\n";
$rB_min = $calculator->calculateRevenuAnnuel('B', 47000);
echo "  Revenu/jour: " . number_format($rB_min['revenu_per_day'], 2, ',', ' ') . " €\n";
echo "  Nb PASS: " . number_format($rB_min['nb_pass'], 2, ',', ' ') . "\n\n";

echo "Classe B avec revenu = 3 PASS (141 000 €):\n";
$rB_max = $calculator->calculateRevenuAnnuel('B', 141000);
echo "  Revenu/jour: " . number_format($rB_max['revenu_per_day'], 2, ',', ' ') . " €\n";
echo "  Nb PASS: " . number_format($rB_max['nb_pass'], 2, ',', ' ') . "\n\n";

echo "Comparaison:\n";
echo "  Classe A vs B(1 PASS): ";
echo ($resultA['revenu_per_day'] == $rB_min['revenu_per_day']) ? "✓ IDENTIQUES" : "✗ DIFFÉRENTS";
echo " (" . number_format($resultA['revenu_per_day'], 2, ',', ' ') . " vs " . number_format($rB_min['revenu_per_day'], 2, ',', ' ') . ")\n";

echo "  Classe C vs B(3 PASS): ";
echo ($resultC['revenu_per_day'] == $rB_max['revenu_per_day']) ? "✓ IDENTIQUES" : "✗ DIFFÉRENTS";
echo " (" . number_format($resultC['revenu_per_day'], 2, ',', ' ') . " vs " . number_format($rB_max['revenu_per_day'], 2, ',', ' ') . ")\n\n";

echo "========================================\n";
echo "VALIDATION DES FORMULES\n";
echo "========================================\n\n";

$allValid = true;

// Validation Classe A
$expectedA = $passValue / 730;
$actualA = $resultA['revenu_per_day'];
if (abs($expectedA - $actualA) < 0.01) {
    echo "✓ Classe A: formule correcte\n";
} else {
    echo "✗ Classe A: ERREUR (attendu: " . number_format($expectedA, 2) . ", obtenu: " . number_format($actualA, 2) . ")\n";
    $allValid = false;
}

// Validation Classe B
$expectedB = 85000 / 730;
$actualB = $resultB['revenu_per_day'];
if (abs($expectedB - $actualB) < 0.01) {
    echo "✓ Classe B: formule correcte\n";
} else {
    echo "✗ Classe B: ERREUR (attendu: " . number_format($expectedB, 2) . ", obtenu: " . number_format($actualB, 2) . ")\n";
    $allValid = false;
}

// Validation Classe C
$expectedC = ($passValue * 3) / 730;
$actualC = $resultC['revenu_per_day'];
if (abs($expectedC - $actualC) < 0.01) {
    echo "✓ Classe C: formule correcte\n";
} else {
    echo "✗ Classe C: ERREUR (attendu: " . number_format($expectedC, 2) . ", obtenu: " . number_format($actualC, 2) . ")\n";
    $allValid = false;
}

echo "\n";
if ($allValid) {
    echo "✓ TOUTES LES FORMULES SONT CORRECTES\n";
} else {
    echo "✗ CERTAINES FORMULES SONT INCORRECTES\n";
}

echo "\n";
