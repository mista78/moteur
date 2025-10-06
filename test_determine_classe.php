<?php
/**
 * Test de la détermination automatique de la classe du médecin
 * basée sur les revenus N-2
 */

require_once 'IJCalculator.php';

// Initialize calculator
$calculator = new IJCalculator('taux.csv');

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  DÉTERMINATION AUTOMATIQUE DE LA CLASSE DU MÉDECIN\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

echo "RÈGLES:\n";
echo "  • Classe A: Revenus < 1 PASS (< 47 000 €)\n";
echo "  • Classe B: Revenus entre 1 PASS et 3 PASS (47 000 € - 141 000 €)\n";
echo "  • Classe C: Revenus > 3 PASS (> 141 000 €)\n";
echo "  • Si revenus non communiqués: Classe A d'office\n";
echo "  • La classe est basée sur les revenus de l'année N-2\n\n";

// Date d'ouverture des droits: 2024-01-15
// Donc année N = 2024, année N-2 = 2022
$dateOuvertureDroits = '2024-01-15';
$anneeN = (int)date('Y', strtotime($dateOuvertureDroits));
$anneeNMoins2 = $calculator->getAnneeNMoins2($dateOuvertureDroits);

echo "Date d'ouverture des droits: {$dateOuvertureDroits}\n";
echo "Année N: {$anneeN}\n";
echo "Année N-2 (revenus à considérer): {$anneeNMoins2}\n\n";

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  SCÉNARIOS DE TEST\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Test cases
$testCases = [
    [
        'description' => 'Médecin débutant',
        'revenus' => 30000,
        'taxe_office' => false,
    ],
    [
        'description' => 'Médecin établi (revenus moyens)',
        'revenus' => 80000,
        'taxe_office' => false,
    ],
    [
        'description' => 'Médecin très établi (revenus élevés)',
        'revenus' => 200000,
        'taxe_office' => false,
    ],
    [
        'description' => 'Juste en dessous du seuil classe A/B',
        'revenus' => 46500,
        'taxe_office' => false,
    ],
    [
        'description' => 'Exactement 1 PASS',
        'revenus' => 47000,
        'taxe_office' => false,
    ],
    [
        'description' => 'Juste en dessous du seuil classe B/C',
        'revenus' => 140000,
        'taxe_office' => false,
    ],
    [
        'description' => 'Exactement 3 PASS',
        'revenus' => 141000,
        'taxe_office' => false,
    ],
    [
        'description' => 'Juste au-dessus de 3 PASS',
        'revenus' => 142000,
        'taxe_office' => false,
    ],
    [
        'description' => 'Revenus non communiqués (taxé d\'office)',
        'revenus' => null,
        'taxe_office' => true,
    ],
    [
        'description' => 'Revenus non disponibles',
        'revenus' => null,
        'taxe_office' => false,
    ],
];

$testNumber = 1;
foreach ($testCases as $test) {
    echo "┌─────────────────────────────────────────────────────────────────────────────┐\n";
    echo "│ Test #{$testNumber}: {$test['description']}\n";
    echo "└─────────────────────────────────────────────────────────────────────────────┘\n";

    $revenus = $test['revenus'];
    $taxeOffice = $test['taxe_office'];

    echo "  Revenus N-2 ({$anneeNMoins2}): ";
    if ($revenus === null) {
        echo "Non communiqués\n";
    } else {
        echo number_format($revenus, 2, ',', ' ') . " €\n";
    }

    echo "  Taxé d'office: " . ($taxeOffice ? 'Oui' : 'Non') . "\n";

    $classe = $calculator->determineClasse($revenus, $dateOuvertureDroits, $taxeOffice);

    echo "  ➜ Classe déterminée: {$classe}\n";

    // Afficher le détail de la détermination
    if ($taxeOffice || $revenus === null) {
        echo "  Explication: Revenus non communiqués → Classe A d'office\n";
    } else {
        $passValue = 47000;
        $nbPass = $revenus / $passValue;

        if ($revenus < $passValue) {
            echo "  Explication: {$revenus} € < 1 PASS (47 000 €) → Classe A\n";
        } elseif ($revenus <= (3 * $passValue)) {
            echo sprintf("  Explication: 1 PASS < %s € ≤ 3 PASS (47 000 € - 141 000 €) → Classe B\n",
                number_format($revenus, 0, ',', ' '));
            echo sprintf("  Correspond à %.2f PASS\n", $nbPass);
        } else {
            echo sprintf("  Explication: %s € > 3 PASS (141 000 €) → Classe C\n",
                number_format($revenus, 0, ',', ' '));
            echo sprintf("  Correspond à %.2f PASS\n", $nbPass);
        }
    }

    echo "\n";
    $testNumber++;
}

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  EXEMPLE D'UTILISATION DANS UN CALCUL IJ\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Exemple concret
$revenusMedecin = 85000; // Revenus de l'année N-2
$classeDeterminee = $calculator->determineClasse($revenusMedecin, $dateOuvertureDroits);

echo "Médecin avec revenus {$anneeNMoins2}: " . number_format($revenusMedecin, 2, ',', ' ') . " €\n";
echo "Classe déterminée: {$classeDeterminee}\n\n";

// Charger des données d'arrêt
$mockData = json_decode(file_get_contents('mock.json'), true);

echo "Calcul avec classe automatiquement déterminée:\n\n";

$data = [
    'arrets' => $mockData,
    'statut' => 'M',
    'classe' => $classeDeterminee,  // Utilisation de la classe déterminée automatiquement
    'option' => 100,
    'birth_date' => '1989-09-26',
    'current_date' => '2024-09-09',
    'attestation_date' => '2024-01-31',
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => false,
];

$result = $calculator->calculateAmount($data);

echo "  Classe utilisée: {$classeDeterminee}\n";
echo "  Nombre de jours: {$result['nb_jours']}\n";
echo "  Montant calculé: " . number_format($result['montant'], 2, ',', ' ') . " €\n\n";

// Comparer avec une autre classe
echo "Comparaison avec les autres classes:\n\n";

foreach (['A', 'B', 'C'] as $classe) {
    $dataComparison = $data;
    $dataComparison['classe'] = $classe;
    $resultComparison = $calculator->calculateAmount($dataComparison);

    $marker = ($classe === $classeDeterminee) ? ' ← Classe déterminée' : '';
    echo sprintf("  Classe %s: %10s €%s\n",
        $classe,
        number_format($resultComparison['montant'], 2, ',', ' '),
        $marker
    );
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "  TABLEAU RÉCAPITULATIF DES SEUILS\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

echo "┌──────────────┬────────────────────────────┬─────────────────────────────────┐\n";
echo "│    Classe    │     Revenus N-2            │          Correspond à           │\n";
echo "├──────────────┼────────────────────────────┼─────────────────────────────────┤\n";
echo "│      A       │  < 47 000 €                │  < 1 PASS                       │\n";
echo "│      B       │  47 000 € - 141 000 €      │  1 PASS à 3 PASS                │\n";
echo "│      C       │  > 141 000 €               │  > 3 PASS                       │\n";
echo "├──────────────┼────────────────────────────┼─────────────────────────────────┤\n";
echo "│  Non comm.   │  Revenus non déclarés      │  Classe A d'office              │\n";
echo "└──────────────┴────────────────────────────┴─────────────────────────────────┘\n";

echo "\nNote: La valeur du PASS utilisée est 47 000 € (configurable)\n";
echo "      La classe est déterminée à la date d'ouverture des droits\n";
echo "      Les revenus considérés sont ceux de l'année N-2\n\n";
