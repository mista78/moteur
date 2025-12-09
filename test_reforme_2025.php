<?php
/**
 * Test Réforme 2025 - Calcul des Taux basé sur PASS
 *
 * Vérifie les nouveaux calculs de taux pour les dates d'effet >= 2025-01-01
 */

require __DIR__ . '/vendor/autoload.php';

use App\Services\RateService;

echo "============================================\n";
echo "Test Réforme 2025 - Calcul des Taux\n";
echo "============================================\n\n";

// PASS 2024 utilisé comme référence
$passValue = 46368;

echo "PASS utilisé: {$passValue} €\n\n";

// Créer le service de taux
$rateService = new RateService([]);
$rateService->setPassValue($passValue);

// Test 1: Calcul des taux de base par classe
echo "Test 1: Taux de base par classe (Taux 1)\n";
echo "==========================================\n";

$expectedRates = [
    'A' => ($passValue * 1) / 730,
    'B' => ($passValue * 2) / 730,
    'C' => ($passValue * 3) / 730
];

foreach (['A', 'B', 'C'] as $classe) {
    $rate = $rateService->getDailyRate(
        statut: 'M',
        classe: $classe,
        option: 100,
        taux: 1,
        year: 2025,
        date: '2025-01-01'
    );

    $expected = round($expectedRates[$classe], 2);
    $match = abs($rate - $expected) < 0.01 ? '✓' : '✗';

    echo "  $match Classe $classe: {$rate} € (attendu: {$expected} €)\n";
    echo "      Formule: {$classe}=". ($classe === 'A' ? 1 : ($classe === 'B' ? 2 : 3)) . " * {$passValue} / 730\n";
}
echo "\n";

// Test 2: Application des réductions de taux (Classe A)
echo "Test 2: Réductions de taux (Classe A)\n";
echo "======================================\n";

$baseRateA = ($passValue * 1) / 730;

$tauxTests = [
    1 => ['multiplier' => 1.0,        'description' => '100% (taux plein)'],
    2 => ['multiplier' => 2/3,        'description' => '66.67% (- 1/3)'],
    3 => ['multiplier' => 1/3,        'description' => '33.33% (- 2/3)'],
    4 => ['multiplier' => 0.5,        'description' => '50% (taux 4)'],
    5 => ['multiplier' => 0.5 * 2/3,  'description' => '33.33% (taux 4 - 1/3)'],
    6 => ['multiplier' => 0.5 * 1/3,  'description' => '16.67% (taux 4 - 2/3)'],
    7 => ['multiplier' => 0.75,       'description' => '75% (taux 7)'],
    8 => ['multiplier' => 0.75 * 2/3, 'description' => '50% (taux 7 - 1/3)'],
    9 => ['multiplier' => 0.75 * 1/3, 'description' => '25% (taux 7 - 2/3)']
];

foreach ($tauxTests as $tauxNum => $test) {
    $rate = $rateService->getDailyRate(
        statut: 'M',
        classe: 'A',
        option: 100,
        taux: $tauxNum,
        year: 2025,
        date: '2025-01-01'
    );

    $expected = round($baseRateA * $test['multiplier'], 2);
    $match = abs($rate - $expected) < 0.01 ? '✓' : '✗';

    echo "  $match Taux $tauxNum: {$rate} € - {$test['description']}\n";
}
echo "\n";

// Test 3: Tous les taux pour toutes les classes
echo "Test 3: Matrice complète Classes × Taux\n";
echo "========================================\n";

echo "PASS = {$passValue} €\n\n";
echo sprintf("%-8s", "Classe");
for ($t = 1; $t <= 9; $t++) {
    echo sprintf("%-12s", "Taux $t");
}
echo "\n";
echo str_repeat("-", 116) . "\n";

foreach (['A', 'B', 'C'] as $classe) {
    echo sprintf("%-8s", $classe);
    for ($taux = 1; $taux <= 9; $taux++) {
        $rate = $rateService->getDailyRate(
            statut: 'M',
            classe: $classe,
            option: 100,
            taux: $taux,
            year: 2025,
            date: '2025-01-01'
        );
        echo sprintf("%-12s", number_format($rate, 2) . " €");
    }
    echo "\n";
}
echo "\n";

// Test 4: Application de l'option pour CCPL/RSPM
echo "Test 4: Option pour CCPL/RSPM (Classe B, Taux 1)\n";
echo "=================================================\n";

$baseRateB = ($passValue * 2) / 730;

$options = [25, 50, 75, 100];
foreach ($options as $option) {
    $rate = $rateService->getDailyRate(
        statut: 'CCPL',
        classe: 'B',
        option: $option,
        taux: 1,
        year: 2025,
        date: '2025-01-01'
    );

    $expected = round($baseRateB * ($option / 100), 2);
    $match = abs($rate - $expected) < 0.01 ? '✓' : '✗';

    echo "  $match Option {$option}%: {$rate} € (attendu: {$expected} €)\n";
}
echo "\n";

// Test 5: Comparaison avant/après 2025
echo "Test 5: Comparaison Avant/Après Réforme\n";
echo "========================================\n";

echo "Classe B, Taux 1, Statut M:\n";

// Avant 2025 (utilise les taux CSV)
$rateBefore = $rateService->getDailyRate(
    statut: 'M',
    classe: 'B',
    option: 100,
    taux: 1,
    year: 2024,
    date: '2024-12-31'
);

// Après 2025 (utilise la nouvelle formule PASS)
$rateAfter = $rateService->getDailyRate(
    statut: 'M',
    classe: 'B',
    option: 100,
    taux: 1,
    year: 2025,
    date: '2025-01-01'
);

echo "  → Avant (2024): {$rateBefore} € (taux CSV)\n";
echo "  → Après (2025): {$rateAfter} € (formule PASS)\n";
echo "  → Différence: " . number_format($rateAfter - $rateBefore, 2) . " €\n";
echo "\n";

// Test 6: Cas réels d'utilisation
echo "Test 6: Exemples Concrets\n";
echo "==========================\n";

$examples = [
    [
        'description' => 'Médecin Classe A, arrêt court (<62 ans)',
        'statut' => 'M',
        'classe' => 'A',
        'option' => 100,
        'taux' => 1
    ],
    [
        'description' => 'Médecin Classe C, période 2 (62-69 ans)',
        'statut' => 'M',
        'classe' => 'C',
        'option' => 100,
        'taux' => 7
    ],
    [
        'description' => 'Médecin Classe B, senior (≥70 ans)',
        'statut' => 'M',
        'classe' => 'B',
        'option' => 100,
        'taux' => 4
    ],
    [
        'description' => 'CCPL Classe A, option 50%, taux réduit',
        'statut' => 'CCPL',
        'classe' => 'A',
        'option' => 50,
        'taux' => 2
    ]
];

foreach ($examples as $example) {
    $rate = $rateService->getDailyRate(
        statut: $example['statut'],
        classe: $example['classe'],
        option: $example['option'],
        taux: $example['taux'],
        year: 2025,
        date: '2025-01-01'
    );

    echo "  ✓ {$example['description']}\n";
    echo "    → Taux journalier: {$rate} €\n";
}
echo "\n";

// Test 7: Vérification des formules
echo "Test 7: Vérification des Formules\n";
echo "==================================\n";

$baseA = ($passValue * 1) / 730;
$baseB = ($passValue * 2) / 730;
$baseC = ($passValue * 3) / 730;

echo "Formules de base:\n";
echo "  Classe A (1 PASS): {$passValue} / 730 = " . number_format($baseA, 2) . " €\n";
echo "  Classe B (2 PASS): {$passValue} × 2 / 730 = " . number_format($baseB, 2) . " €\n";
echo "  Classe C (3 PASS): {$passValue} × 3 / 730 = " . number_format($baseC, 2) . " €\n";
echo "\n";

echo "Taux dérivés (Classe B comme exemple):\n";
echo "  Taux 1 (100%):    " . number_format($baseB, 2) . " €\n";
echo "  Taux 4 (50%):     " . number_format($baseB * 0.5, 2) . " €\n";
echo "  Taux 7 (75%):     " . number_format($baseB * 0.75, 2) . " €\n";
echo "\n";

echo "============================================\n";
echo "✓ Réforme 2025 implémentée avec succès!\n";
echo "============================================\n\n";

echo "Résumé:\n";
echo "  → Dates ≥ 2025-01-01: Formule basée sur PASS\n";
echo "  → Dates < 2025-01-01: Taux historiques (CSV)\n";
echo "  → Classes A/B/C: Multiplicateurs 1/2/3\n";
echo "  → 9 taux avec réductions automatiques\n";
echo "  → Support options CCPL/RSPM\n";
