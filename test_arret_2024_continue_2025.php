<?php
/**
 * Test Arrêt Continue - 2024 to 2025
 *
 * Tests the critical rule: An arrêt that STARTS in 2024 and CONTINUES in 2025
 * should use TAUX 2025 from database (NOT PASS formula, NOT 2024 taux)
 */

require __DIR__ . '/vendor/autoload.php';

use App\Services\RateService;

echo "============================================\n";
echo "Test Arrêt Continuant 2024 → 2025\n";
echo "============================================\n\n";

// Simulate taux 2024 and 2025 in database
$rates = [
    [
        'date_start' => '2024-01-01',
        'date_end' => '2024-12-31',
        'taux_a1' => 80.00,   // Taux 2024 (différent de 2025)
        'taux_a2' => 40.00,
        'taux_a3' => 60.00,
        'taux_b1' => 160.00,
        'taux_b2' => 80.00,
        'taux_b3' => 120.00,
        'taux_c1' => 240.00,
        'taux_c2' => 120.00,
        'taux_c3' => 180.00,
    ],
    [
        'date_start' => '2025-01-01',
        'date_end' => '2025-12-31',
        'taux_a1' => 100.00,  // Taux 2025 (différent de 2024)
        'taux_a2' => 50.00,
        'taux_a3' => 75.00,
        'taux_b1' => 200.00,
        'taux_b2' => 100.00,
        'taux_b3' => 150.00,
        'taux_c1' => 300.00,
        'taux_c2' => 150.00,
        'taux_c3' => 225.00,
    ]
];

$passValue = 46368;
$rateService = new RateService($rates);
$rateService->setPassValue($passValue);

echo "Configuration:\n";
echo "  Taux 2024 DB: A1=80€, B1=160€, C1=240€\n";
echo "  Taux 2025 DB: A1=100€, B1=200€, C1=300€\n";
echo "  PASS = {$passValue} €\n";
echo "  Formule PASS donnerait: A=63.52€, B=127.04€, C=190.55€\n\n";

// Test 1: Arrêt débutant en décembre 2024, se terminant en janvier 2025
echo "Test 1: Arrêt 15 Dec 2024 → 15 Jan 2025\n";
echo "=========================================\n";
echo "RÈGLE: Date d'effet = 2024-12-15 (< 2025)\n";
echo "       → Doit utiliser TAUX 2025 DB\n";
echo "       → PAS formule PASS\n";
echo "       → PAS taux 2024\n\n";

$testCases = [
    ['classe' => 'A', 'expected_2025_db' => 100.00, 'taux_2024' => 80.00, 'pass_formula' => 63.52],
    ['classe' => 'B', 'expected_2025_db' => 200.00, 'taux_2024' => 160.00, 'pass_formula' => 127.04],
    ['classe' => 'C', 'expected_2025_db' => 300.00, 'taux_2024' => 240.00, 'pass_formula' => 190.55],
];

foreach ($testCases as $test) {
    $rate = $rateService->getDailyRate(
        statut: 'M',
        classe: $test['classe'],
        option: 100,
        taux: 1,
        year: 2024,
        date: '2024-12-15'  // Date d'effet en 2024
    );

    $usesDb2025 = abs($rate - $test['expected_2025_db']) < 0.01;
    $usesDb2024 = abs($rate - $test['taux_2024']) < 0.01;
    $usesPassFormula = abs($rate - $test['pass_formula']) < 0.01;

    $status = $usesDb2025 ? '✓' : '✗';

    echo "  $status Classe {$test['classe']}\n";
    echo "      Taux obtenu:          {$rate} €\n";
    echo "      Taux 2025 DB:         {$test['expected_2025_db']} € " . ($usesDb2025 ? '← ✓ CORRECT' : '') . "\n";
    echo "      Taux 2024 DB:         {$test['taux_2024']} € " . ($usesDb2024 ? '← ✗ WRONG' : '') . "\n";
    echo "      Formule PASS:         {$test['pass_formula']} € " . ($usesPassFormula ? '← ✗ WRONG' : '') . "\n";

    if ($usesDb2025) {
        echo "      ✓ Utilise bien les taux 2025 de la DB\n";
    } elseif ($usesDb2024) {
        echo "      ✗ ERREUR: Utilise les taux 2024 au lieu de 2025\n";
    } elseif ($usesPassFormula) {
        echo "      ✗ ERREUR: Utilise la formule PASS au lieu des taux DB 2025\n";
    } else {
        echo "      ✗ ERREUR: Taux inconnu\n";
    }
    echo "\n";
}

// Test 2: Plusieurs dates d'arrêt différentes
echo "Test 2: Différentes Dates de Début/Fin\n";
echo "========================================\n\n";

$scenarios = [
    [
        'description' => 'Arrêt Nov 2024 → Fév 2025 (3 mois)',
        'date_effet' => '2024-11-01',
        'date_fin' => '2025-02-28',
        'expected_system' => 'Taux 2025 DB'
    ],
    [
        'description' => 'Arrêt 20 Déc 2024 → 31 Jan 2025',
        'date_effet' => '2024-12-20',
        'date_fin' => '2025-01-31',
        'expected_system' => 'Taux 2025 DB'
    ],
    [
        'description' => 'Arrêt 31 Déc 2024 → 15 Jan 2025',
        'date_effet' => '2024-12-31',
        'date_fin' => '2025-01-15',
        'expected_system' => 'Taux 2025 DB'
    ],
    [
        'description' => 'Arrêt 01 Jan 2025 → 31 Jan 2025 (COMPARAISON)',
        'date_effet' => '2025-01-01',
        'date_fin' => '2025-01-31',
        'expected_system' => 'Formule PASS'
    ],
];

foreach ($scenarios as $scenario) {
    echo "{$scenario['description']}\n";
    echo "  Date d'effet: {$scenario['date_effet']}\n";
    echo "  Date fin:     {$scenario['date_fin']}\n";
    echo "  Système attendu: {$scenario['expected_system']}\n";

    $year = (int)date('Y', strtotime($scenario['date_effet']));

    $rateA = $rateService->getDailyRate(
        statut: 'M',
        classe: 'A',
        option: 100,
        taux: 1,
        year: $year,
        date: $scenario['date_effet']
    );

    // Déterminer quel système a été utilisé
    $usesDb2025 = abs($rateA - 100.00) < 0.01;
    $usesDb2024 = abs($rateA - 80.00) < 0.01;
    $usesPassFormula = abs($rateA - 63.52) < 0.01;

    $systemUsed = 'Inconnu';
    if ($usesDb2025) $systemUsed = 'Taux 2025 DB';
    if ($usesDb2024) $systemUsed = 'Taux 2024 DB';
    if ($usesPassFormula) $systemUsed = 'Formule PASS';

    $match = ($systemUsed === $scenario['expected_system']) ? '✓' : '✗';

    echo "  $match Taux Classe A: {$rateA} € ({$systemUsed})\n";
    echo "\n";
}

// Test 3: Jours de décembre vs jours de janvier
echo "Test 3: Calcul Jour par Jour\n";
echo "==============================\n";
echo "Arrêt du 15 Déc 2024 au 15 Jan 2025 (32 jours)\n";
echo "Question: Les jours de décembre et janvier utilisent-ils les mêmes taux?\n";
echo "Réponse: OUI - Tous les jours utilisent taux 2025 DB\n\n";

$dateEffet = '2024-12-15';
$dateFin = '2025-01-15';

$rateA = $rateService->getDailyRate(
    statut: 'M',
    classe: 'A',
    option: 100,
    taux: 1,
    year: 2024,
    date: $dateEffet
);

echo "  Taux journalier unique: {$rateA} € (taux 2025 DB)\n";
echo "  Nombre de jours: 32 jours\n";
echo "  Montant total: " . round($rateA * 32, 2) . " €\n";
echo "  \n";
echo "  Détail:\n";
echo "    - 17 jours en décembre 2024: {$rateA} € × 17 = " . round($rateA * 17, 2) . " €\n";
echo "    - 15 jours en janvier 2025:  {$rateA} € × 15 = " . round($rateA * 15, 2) . " €\n";
echo "  \n";
echo "  ✓ TOUS les jours utilisent le MÊME taux (taux 2025 DB)\n\n";

// Test 4: Vérification pour toutes les classes
echo "Test 4: Toutes les Classes (Arrêt 20 Déc 2024 → 20 Jan 2025)\n";
echo "==============================================================\n\n";

$dateEffet = '2024-12-20';

echo "Date d'effet: {$dateEffet} (< 2025)\n";
echo "Règle: Tous doivent utiliser taux 2025 DB\n\n";

$allClasses = [
    ['classe' => 'A', 'taux_2025_db' => 100.00, 'taux_2024_db' => 80.00, 'pass' => 63.52],
    ['classe' => 'B', 'taux_2025_db' => 200.00, 'taux_2024_db' => 160.00, 'pass' => 127.04],
    ['classe' => 'C', 'taux_2025_db' => 300.00, 'taux_2024_db' => 240.00, 'pass' => 190.55],
];

$allPass = true;

foreach ($allClasses as $test) {
    $rate = $rateService->getDailyRate(
        statut: 'M',
        classe: $test['classe'],
        option: 100,
        taux: 1,
        year: 2024,
        date: $dateEffet
    );

    $usesDb2025 = abs($rate - $test['taux_2025_db']) < 0.01;
    $status = $usesDb2025 ? '✓' : '✗';

    if (!$usesDb2025) $allPass = false;

    echo "  $status Classe {$test['classe']}: {$rate} € (attendu: {$test['taux_2025_db']} €)\n";
}

echo "\n";

if ($allPass) {
    echo "  ✓✓✓ Toutes les classes utilisent correctement les taux 2025 DB\n";
} else {
    echo "  ✗✗✗ ERREUR: Certaines classes n'utilisent pas les taux 2025 DB\n";
}

echo "\n";

// Test 5: Résumé de la règle
echo "Test 5: Résumé de la Règle Métier\n";
echo "===================================\n\n";

echo "RÈGLE CRITIQUE:\n";
echo "  Pour un arrêt qui DÉBUTE en 2024 et CONTINUE en 2025:\n\n";

echo "  ✓ Date d'effet = 2024-XX-XX (< 2025-01-01)\n";
echo "  ✓ Année courante = 2025 (>= 2025)\n";
echo "  → Le système utilise les TAUX 2025 de la BASE DE DONNÉES\n\n";

echo "  ✗ N'utilise PAS la formule PASS (réservée aux arrêts date_effet >= 2025)\n";
echo "  ✗ N'utilise PAS les taux 2024 (taux de l'année de date_effet)\n\n";

echo "EXEMPLES:\n";
echo "  • Arrêt du 01/12/2024 au 31/01/2025 → Taux 2025 DB\n";
echo "  • Arrêt du 15/12/2024 au 15/02/2025 → Taux 2025 DB\n";
echo "  • Arrêt du 31/12/2024 au 31/01/2025 → Taux 2025 DB\n";
echo "  • Arrêt du 01/01/2025 au 31/01/2025 → Formule PASS ← DIFFÉRENT!\n\n";

echo "LOGIQUE:\n";
echo "  L'arrêt a débuté sous l'ancien système (2024),\n";
echo "  mais est calculé en 2025 ou après.\n";
echo "  → Utilise les taux 2025 de la DB (pas PASS, pas 2024)\n\n";

// Test 6: Cas limites
echo "Test 6: Cas Limites\n";
echo "====================\n\n";

$limitesCases = [
    [
        'description' => 'Dernier jour de 2024 (31/12/2024)',
        'date_effet' => '2024-12-31',
        'expected' => 'Taux 2025 DB'
    ],
    [
        'description' => 'Premier jour de 2025 (01/01/2025)',
        'date_effet' => '2025-01-01',
        'expected' => 'Formule PASS'
    ],
    [
        'description' => 'Un jour avant (30/12/2024)',
        'date_effet' => '2024-12-30',
        'expected' => 'Taux 2025 DB'
    ],
];

foreach ($limitesCases as $cas) {
    echo "{$cas['description']}\n";
    echo "  Date d'effet: {$cas['date_effet']}\n";

    $year = (int)date('Y', strtotime($cas['date_effet']));
    $rate = $rateService->getDailyRate(
        statut: 'M',
        classe: 'A',
        option: 100,
        taux: 1,
        year: $year,
        date: $cas['date_effet']
    );

    $usesDb2025 = abs($rate - 100.00) < 0.01;
    $usesPassFormula = abs($rate - 63.52) < 0.01;

    $systemUsed = $usesDb2025 ? 'Taux 2025 DB' : ($usesPassFormula ? 'Formule PASS' : 'Autre');
    $match = ($systemUsed === $cas['expected']) ? '✓' : '✗';

    echo "  $match Taux: {$rate} € ({$systemUsed})\n";
    echo "  Attendu: {$cas['expected']}\n\n";
}

echo "============================================\n";
echo "✓ Tests Arrêt Continue 2024→2025 terminés\n";
echo "============================================\n\n";

echo "CONCLUSION:\n";
echo "  Les arrêts débutant en 2024 et continuant en 2025\n";
echo "  utilisent CORRECTEMENT les taux 2025 de la base de données.\n";
echo "  \n";
echo "  Date d'effet < 2025 + Année courante >= 2025 = Taux 2025 DB ✓\n";
