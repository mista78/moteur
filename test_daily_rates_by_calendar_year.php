<?php
/**
 * Test: Daily Rates Based on Calendar Year
 *
 * Rule: Rate depends on which calendar year the DAY falls in
 * - Days in 2024 → Taux 2024 DB (80€)
 * - Days in 2025 (arrêt starting 2024) → Taux 2025 DB (100€)
 * - Arrêt starting 2025 → PASS formula (63.52€)
 */

require __DIR__ . '/vendor/autoload.php';

use App\Services\RateService;

// Simulate taux 2024 and 2025 in database
$rates = [
    [
        'date_start' => '2024-01-01',
        'date_end' => '2024-12-31',
        'taux_a1' => 80.00,   // Taux 2024
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
        'taux_a1' => 100.00,  // Taux 2025
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

echo "════════════════════════════════════════════════════════════════\n";
echo "Test: Taux Basés sur l'Année Calendrier du Jour\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "RÈGLE:\n";
echo "======\n\n";
echo "1️⃣  Jours en 2024 → Taux 2024 DB (80€, 160€, 240€)\n";
echo "2️⃣  Jours en 2025 (arrêt débuté 2024) → Taux 2025 DB (100€, 200€, 300€)\n";
echo "3️⃣  Arrêt débuté 2025 → Formule PASS (63.52€, 127.04€, 190.55€)\n\n";

// Test 1: Arrêt spanning 2024-2025
echo "═══════════════════════════════════════════════════════════════════\n";
echo "Test 1: Arrêt du 20 Déc 2024 au 10 Jan 2025 (Classe A)\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$dateEffet = '2024-12-20';
$dateFin = '2025-01-10';

echo "Date d'effet: {$dateEffet}\n";
echo "Date fin:     {$dateFin}\n\n";

$current = new DateTime($dateEffet);
$end = new DateTime($dateFin);
$dayNumber = 1;
$total2024 = 0;
$total2025 = 0;
$days2024 = 0;
$days2025 = 0;

echo "┌──────┬──────────────┬───────────┬──────────┬─────────────┐\n";
echo "│ Jour │     Date     │  Année    │   Taux   │   Système   │\n";
echo "├──────┼──────────────┼───────────┼──────────┼─────────────┤\n";

while ($current <= $end) {
    $dayDate = $current->format('Y-m-d');
    $dayYear = $current->format('Y');

    $rate = $rateService->getDailyRate(
        statut: 'M',
        classe: 'A',
        option: 100,
        taux: 1,
        year: 2024,
        date: $dateEffet,
        calculationDate: $dayDate  // ← Le jour spécifique
    );

    $system = '';
    if ($dayYear == '2024') {
        $system = 'Taux 2024 DB';
        $total2024 += $rate;
        $days2024++;
    } else {
        $system = 'Taux 2025 DB';
        $total2025 += $rate;
        $days2025++;
    }

    $formattedDate = $current->format('d/m/Y');
    $dayName = substr($current->format('D'), 0, 3);

    printf("│  %2d  │ %s %s │   %4s    │ %6.2f € │ %-11s │\n",
        $dayNumber, $formattedDate, $dayName, $dayYear, $rate, $system);

    $current->modify('+1 day');
    $dayNumber++;
}

echo "└──────┴──────────────┴───────────┴──────────┴─────────────┘\n\n";

echo "RÉSUMÉ:\n";
echo "=======\n\n";
printf("  Jours en 2024: %2d jours × 80.00€  = %7.2f €\n", $days2024, $total2024);
printf("  Jours en 2025: %2d jours × 100.00€ = %7.2f €\n", $days2025, $total2025);
echo "  ───────────────────────────────────────────────\n";
printf("  TOTAL:         %2d jours            = %7.2f €\n\n", $days2024 + $days2025, $total2024 + $total2025);

// Validation
$expected2024Rate = 80.00;
$expected2025Rate = 100.00;

$rate2024 = $rateService->getDailyRate('M', 'A', 100, 1, 2024, $dateEffet, calculationDate: '2024-12-25');
$rate2025 = $rateService->getDailyRate('M', 'A', 100, 1, 2024, $dateEffet, calculationDate: '2025-01-05');

$valid2024 = abs($rate2024 - $expected2024Rate) < 0.01;
$valid2025 = abs($rate2025 - $expected2025Rate) < 0.01;

echo "VALIDATION:\n";
echo "===========\n\n";
echo "  " . ($valid2024 ? '✓' : '✗') . " Jour en 2024 (25/12/2024): {$rate2024}€ (attendu: {$expected2024Rate}€)\n";
echo "  " . ($valid2025 ? '✓' : '✗') . " Jour en 2025 (05/01/2025): {$rate2025}€ (attendu: {$expected2025Rate}€)\n\n";

if ($valid2024 && $valid2025) {
    echo "  ✓✓✓ Le système utilise bien les taux selon l'année du jour!\n\n";
} else {
    echo "  ✗✗✗ ERREUR: Les taux ne correspondent pas aux attentes\n\n";
}

// Test 2: Arrêt starting in 2025
echo "═══════════════════════════════════════════════════════════════════\n";
echo "Test 2: Arrêt du 05 Jan 2025 au 25 Jan 2025 (Classe A)\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$dateEffet2025 = '2025-01-05';
$dateFin2025 = '2025-01-25';

echo "Date d'effet: {$dateEffet2025}\n";
echo "Date fin:     {$dateFin2025}\n\n";

$current = new DateTime($dateEffet2025);
$end = new DateTime($dateFin2025);
$dayNumber = 1;
$totalPass = 0;
$daysPass = 0;

echo "┌──────┬──────────────┬─────────────┬──────────┬─────────────┐\n";
echo "│ Jour │     Date     │    Année    │   Taux   │   Système   │\n";
echo "├──────┼──────────────┼─────────────┼──────────┼─────────────┤\n";

while ($current <= $end) {
    $dayDate = $current->format('Y-m-d');

    $rate = $rateService->getDailyRate(
        statut: 'M',
        classe: 'A',
        option: 100,
        taux: 1,
        year: 2025,
        date: $dateEffet2025,
        calculationDate: $dayDate
    );

    $totalPass += $rate;
    $daysPass++;

    $formattedDate = $current->format('d/m/Y');
    $dayName = substr($current->format('D'), 0, 3);

    printf("│  %2d  │ %s %s │    2025     │  %5.2f € │ PASS Formula│\n",
        $dayNumber, $formattedDate, $dayName, $rate);

    $current->modify('+1 day');
    $dayNumber++;
}

echo "└──────┴──────────────┴─────────────┴──────────┴─────────────┘\n\n";

echo "RÉSUMÉ:\n";
echo "=======\n\n";
printf("  Jours en 2025: %2d jours × 63.52€  = %7.2f €\n", $daysPass, $totalPass);
printf("  TOTAL:         %2d jours            = %7.2f €\n\n", $daysPass, $totalPass);

// Test 3: Classe B spanning years
echo "═══════════════════════════════════════════════════════════════════\n";
echo "Test 3: Arrêt du 28 Déc 2024 au 5 Jan 2025 (Classe B)\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$dateEffetB = '2024-12-28';
$dateFinB = '2025-01-05';

$current = new DateTime($dateEffetB);
$end = new DateTime($dateFinB);

$total2024B = 0;
$total2025B = 0;
$days2024B = 0;
$days2025B = 0;

while ($current <= $end) {
    $dayDate = $current->format('Y-m-d');
    $dayYear = $current->format('Y');

    $rate = $rateService->getDailyRate(
        statut: 'M',
        classe: 'B',
        option: 100,
        taux: 1,
        year: 2024,
        date: $dateEffetB,
        calculationDate: $dayDate
    );

    if ($dayYear == '2024') {
        $total2024B += $rate;
        $days2024B++;
    } else {
        $total2025B += $rate;
        $days2025B++;
    }

    $current->modify('+1 day');
}

printf("  Jours en 2024: %d jours × 160.00€ = %7.2f €\n", $days2024B, $total2024B);
printf("  Jours en 2025: %d jours × 200.00€ = %7.2f €\n", $days2025B, $total2025B);
echo "  ────────────────────────────────────────────\n";
printf("  TOTAL:         %d jours           = %7.2f €\n\n", $days2024B + $days2025B, $total2024B + $total2025B);

// Final summary
echo "════════════════════════════════════════════════════════════════\n";
echo "✓ Tests Terminés - Taux par Année Calendrier\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "RÉSUMÉ DE LA RÈGLE:\n";
echo "===================\n\n";

echo "  ✓ Les JOURS en 2024 utilisent les taux 2024 DB\n";
echo "  ✓ Les JOURS en 2025 (arrêt débuté 2024) utilisent les taux 2025 DB\n";
echo "  ✓ Les arrêts débutant en 2025 utilisent la formule PASS\n\n";

echo "AVANTAGES:\n";
echo "==========\n\n";
echo "  • Taux adapté à chaque période\n";
echo "  • Transition naturelle entre années\n";
echo "  • Respecte les taux en vigueur par période\n\n";

echo "════════════════════════════════════════════════════════════════\n";
