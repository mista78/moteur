<?php
/**
 * Test Taux 2025 Base de Données
 *
 * Vérifie que les arrêts avec date_effet < 2025 utilisent les taux 2025 de la DB
 * (pas la formule PASS, pas les taux de l'année de date_effet)
 */

require __DIR__ . '/vendor/autoload.php';

use App\Services\RateService;

echo "============================================\n";
echo "Test Taux 2025 Base de Données\n";
echo "============================================\n\n";

// Simuler des taux 2025 en base de données
$rates2025 = [
    [
        'date_start' => '2025-01-01',
        'date_end' => '2025-12-31',
        'taux_a1' => 100.00,  // Classe A, palier 1
        'taux_a2' => 50.00,   // Classe A, palier 2
        'taux_a3' => 75.00,   // Classe A, palier 3
        'taux_b1' => 200.00,  // Classe B, palier 1
        'taux_b2' => 100.00,  // Classe B, palier 2
        'taux_b3' => 150.00,  // Classe B, palier 3
        'taux_c1' => 300.00,  // Classe C, palier 1
        'taux_c2' => 150.00,  // Classe C, palier 2
        'taux_c3' => 225.00,  // Classe C, palier 3
    ]
];

$passValue = 46368;
$rateService = new RateService($rates2025);
$rateService->setPassValue($passValue);

echo "Configuration:\n";
echo "  Taux 2025 DB chargés (A1=100€, B1=200€, C1=300€)\n";
echo "  PASS = {$passValue} €\n";
echo "  Date actuelle simulée: 2025\n\n";

// Test 1: Arrêt avec date_effet AVANT 2025
echo "Test 1: Arrêt avec date_effet < 2025\n";
echo "=====================================\n";
echo "Règle: Doit utiliser les TAUX 2025 de la base de données\n";
echo "       PAS la formule PASS, PAS les taux 2024\n\n";

$testCases2024 = [
    ['classe' => 'A', 'taux' => 1, 'expected_db' => 100.00, 'expected_pass' => 63.52],
    ['classe' => 'B', 'taux' => 1, 'expected_db' => 200.00, 'expected_pass' => 127.04],
    ['classe' => 'C', 'taux' => 1, 'expected_db' => 300.00, 'expected_pass' => 190.55],
];

foreach ($testCases2024 as $test) {
    $rate = $rateService->getDailyRate(
        statut: 'M',
        classe: $test['classe'],
        option: 100,
        taux: $test['taux'],
        year: 2024,  // Année de date_effet
        date: '2024-12-15'  // Date d'effet < 2025
    );

    $usesDbRate = abs($rate - $test['expected_db']) < 0.01;
    $usesPassFormula = abs($rate - $test['expected_pass']) < 0.01;

    $status = $usesDbRate ? '✓' : '✗';
    $system = $usesDbRate ? 'Taux DB 2025' : ($usesPassFormula ? 'Formule PASS' : 'Autre');

    echo "  $status Classe {$test['classe']}, date_effet=2024-12-15\n";
    echo "      Taux obtenu: {$rate} €\n";
    echo "      Attendu (DB 2025): {$test['expected_db']} €\n";
    echo "      Formule PASS donnerait: {$test['expected_pass']} €\n";
    echo "      Système: {$system}\n";

    if ($usesDbRate) {
        echo "      ✓ Utilise bien les taux 2025 de la DB\n";
    } else {
        echo "      ✗ N'utilise pas les taux DB 2025\n";
    }
    echo "\n";
}

// Test 2: Arrêt avec date_effet EN 2025
echo "Test 2: Arrêt avec date_effet >= 2025\n";
echo "======================================\n";
echo "Règle: Doit utiliser la FORMULE PASS\n";
echo "       PAS les taux de la base de données\n\n";

$testCases2025 = [
    ['classe' => 'A', 'taux' => 1, 'expected_pass' => 63.52, 'db_rate' => 100.00],
    ['classe' => 'B', 'taux' => 1, 'expected_pass' => 127.04, 'db_rate' => 200.00],
    ['classe' => 'C', 'taux' => 1, 'expected_pass' => 190.55, 'db_rate' => 300.00],
];

foreach ($testCases2025 as $test) {
    $rate = $rateService->getDailyRate(
        statut: 'M',
        classe: $test['classe'],
        option: 100,
        taux: $test['taux'],
        year: 2025,
        date: '2025-01-15'  // Date d'effet >= 2025
    );

    $usesPassFormula = abs($rate - $test['expected_pass']) < 0.01;
    $usesDbRate = abs($rate - $test['db_rate']) < 0.01;

    $status = $usesPassFormula ? '✓' : '✗';
    $system = $usesPassFormula ? 'Formule PASS' : ($usesDbRate ? 'Taux DB' : 'Autre');

    echo "  $status Classe {$test['classe']}, date_effet=2025-01-15\n";
    echo "      Taux obtenu: {$rate} €\n";
    echo "      Attendu (PASS): {$test['expected_pass']} €\n";
    echo "      Taux DB 2025: {$test['db_rate']} €\n";
    echo "      Système: {$system}\n";

    if ($usesPassFormula) {
        echo "      ✓ Utilise bien la formule PASS\n";
    } else {
        echo "      ✗ N'utilise pas la formule PASS\n";
    }
    echo "\n";
}

// Test 3: Comparaison directe
echo "Test 3: Comparaison Directe\n";
echo "============================\n";

$comparisons = [
    [
        'description' => 'Classe A, Taux 1',
        'classe' => 'A',
        'date_effet_2024' => '2024-12-01',
        'date_effet_2025' => '2025-03-15',
        'expected_2024' => 100.00,  // Taux DB 2025
        'expected_2025' => 63.52    // Formule PASS
    ],
    [
        'description' => 'Classe C, Taux 1',
        'classe' => 'C',
        'date_effet_2024' => '2024-11-15',
        'date_effet_2025' => '2025-02-10',
        'expected_2024' => 300.00,  // Taux DB 2025
        'expected_2025' => 190.55   // Formule PASS
    ]
];

foreach ($comparisons as $comp) {
    echo "\n{$comp['description']}:\n";
    echo str_repeat("-", 50) . "\n";

    // Arrêt avec date_effet < 2025
    $rate2024 = $rateService->getDailyRate(
        statut: 'M',
        classe: $comp['classe'],
        option: 100,
        taux: 1,
        year: 2024,
        date: $comp['date_effet_2024']
    );

    // Arrêt avec date_effet >= 2025
    $rate2025 = $rateService->getDailyRate(
        statut: 'M',
        classe: $comp['classe'],
        option: 100,
        taux: 1,
        year: 2025,
        date: $comp['date_effet_2025']
    );

    echo "  Date effet {$comp['date_effet_2024']} (< 2025):\n";
    echo "    → {$rate2024} € (attendu: {$comp['expected_2024']} € - Taux DB 2025)\n";
    echo "    " . (abs($rate2024 - $comp['expected_2024']) < 0.01 ? '✓' : '✗') . "\n";

    echo "  Date effet {$comp['date_effet_2025']} (>= 2025):\n";
    echo "    → {$rate2025} € (attendu: {$comp['expected_2025']} € - Formule PASS)\n";
    echo "    " . (abs($rate2025 - $comp['expected_2025']) < 0.01 ? '✓' : '✗') . "\n";
}
echo "\n";

// Test 4: Résumé de la règle
echo "Test 4: Résumé de la Règle\n";
echo "===========================\n\n";

echo "RÈGLE IMPLÉMENTÉE:\n\n";

echo "1️⃣  Date d'effet >= 2025-01-01\n";
echo "    → Utilise la FORMULE PASS\n";
echo "    → Calcul: (Classe × PASS) / 730\n";
echo "    → Exemple Classe A: 1 × 46368 / 730 = 63.52 €\n\n";

echo "2️⃣  Date d'effet < 2025-01-01 (ancien arrêt)\n";
echo "    → Utilise les TAUX 2025 de la BASE DE DONNÉES\n";
echo "    → PAS la formule PASS\n";
echo "    → PAS les taux de l'année de date_effet\n";
echo "    → Exemple: Arrêt du 15/12/2024 → Taux DB 2025\n\n";

echo "============================================\n";
echo "✓ Tests Taux 2025 DB terminés\n";
echo "============================================\n\n";

echo "IMPORTANT:\n";
echo "  Pour que les arrêts avec date_effet < 2025 fonctionnent,\n";
echo "  il FAUT avoir les taux 2025 dans la base de données:\n";
echo "  - Table: ij_taux\n";
echo "  - Dates: 2025-01-01 à 2025-12-31\n";
echo "  - Taux: taux_a1, taux_a2, taux_a3, taux_b1, ..., taux_c3\n";
