<?php
/**
 * Test script for mock9 - Age transition at 70
 * Shows 730 days: 244j at taux 1 (age 69), 486j at taux 4 (age 70+)
 */

require_once 'IJCalculator.php';

// Load mock9 data
$mockData = json_decode(file_get_contents('mock9.json'), true);

// Initialize calculator
$calculator = new IJCalculator('taux.csv');

// Prepare calculation data
$data = [
    'arrets' => $mockData,
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1953-01-22',  // Turns 70 on 2023-01-22
    'current_date' => '2024-05-21',
    'attestation_date' => '2022-02-22',
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => false,
];

// Calculate
$result = $calculator->calculateAmount($data);

// Display summary
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  MOCK9 - TRANSITION D'ÂGE À 70 ANS\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

echo "Attendu: 730 jours (244j taux 1, 486j taux 4) = 53467.98 €\n\n";

echo "Résultat:\n";
echo "  Nombre de jours: {$result['nb_jours']}\n";
echo "  Montant total: " . number_format($result['montant'], 2, ',', ' ') . " €\n";
echo "  Match attendu: " . ($result['montant'] == 53467.98 ? '✓ OUI' : '✗ NON') . "\n\n";

// Count days by taux
$byTaux = [];
if (isset($result['payment_details']) && is_array($result['payment_details'])) {
    foreach ($result['payment_details'] as $detail) {
        if (!isset($detail['daily_breakdown'])) continue;

        foreach ($detail['daily_breakdown'] as $day) {
            $taux = $day['taux'];
            if (!isset($byTaux[$taux])) {
                $byTaux[$taux] = [
                    'days' => 0,
                    'total' => 0,
                    'rate' => $day['daily_rate']
                ];
            }
            $byTaux[$taux]['days']++;
            $byTaux[$taux]['total'] += $day['amount'];
        }
    }
}

echo "Répartition par taux:\n";
printf("  Taux 1: %3d jours × %8.2f € = %10.2f €\n",
    $byTaux[1]['days'] ?? 0,
    $byTaux[1]['rate'] ?? 0,
    $byTaux[1]['total'] ?? 0
);
printf("  Taux 4: %3d jours × %8.2f € = %10.2f €\n",
    $byTaux[4]['days'] ?? 0,
    $byTaux[4]['rate'] ?? 0,
    $byTaux[4]['total'] ?? 0
);

echo "\n";
echo "Vérification:\n";
echo "  Attendu taux 1: 244 jours - Obtenu: " . ($byTaux[1]['days'] ?? 0) . " jours " .
    (($byTaux[1]['days'] ?? 0) == 244 ? '✓' : '✗') . "\n";
echo "  Attendu taux 4: 486 jours - Obtenu: " . ($byTaux[4]['days'] ?? 0) . " jours " .
    (($byTaux[4]['days'] ?? 0) == 486 ? '✓' : '✗') . "\n";

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "  DÉTAIL PAR SEGMENT D'ÂGE\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Show daily breakdown in summary form (first 5 and last 5 days)
if (isset($result['payment_details'][0]['daily_breakdown'])) {
    $days = $result['payment_details'][0]['daily_breakdown'];
    $totalDays = count($days);

    echo "Premiers jours (âge 69):\n";
    printf("%-12s %-10s %-4s %-6s %-12s\n", 'Date', 'Jour', 'Âge*', 'Taux', 'Montant');
    echo str_repeat('─', 50) . "\n";

    for ($i = 0; $i < min(5, $totalDays); $i++) {
        $day = $days[$i];
        $age = $calculator->calculateAge($day['date'], $data['birth_date']);
        printf("%-12s %-10s %-4d %-6d %10.2f €\n",
            $day['date'],
            substr($day['day_of_week'], 0, 3),
            $age,
            $day['taux'],
            $day['amount']
        );
    }

    echo "\n... (" . ($totalDays - 10) . " jours intermédiaires) ...\n\n";

    // Find the transition point (where taux changes from 1 to 4)
    $transitionIndex = null;
    for ($i = 0; $i < $totalDays - 1; $i++) {
        if ($days[$i]['taux'] == 1 && $days[$i + 1]['taux'] == 4) {
            $transitionIndex = $i;
            break;
        }
    }

    if ($transitionIndex !== null) {
        echo "Transition d'âge (70 ans):\n";
        printf("%-12s %-10s %-4s %-6s %-12s\n", 'Date', 'Jour', 'Âge*', 'Taux', 'Montant');
        echo str_repeat('─', 50) . "\n";

        // Show 2 days before and 3 days after transition
        for ($i = max(0, $transitionIndex - 1); $i <= min($totalDays - 1, $transitionIndex + 3); $i++) {
            $day = $days[$i];
            $age = $calculator->calculateAge($day['date'], $data['birth_date']);
            $marker = ($i == $transitionIndex + 1) ? ' ← Transition' : '';
            printf("%-12s %-10s %-4d %-6d %10.2f €%s\n",
                $day['date'],
                substr($day['day_of_week'], 0, 3),
                $age,
                $day['taux'],
                $day['amount'],
                $marker
            );
        }
        echo "\n... (" . ($totalDays - $transitionIndex - 8) . " jours restants) ...\n\n";
    }

    echo "Derniers jours (âge 71):\n";
    printf("%-12s %-10s %-4s %-6s %-12s\n", 'Date', 'Jour', 'Âge*', 'Taux', 'Montant');
    echo str_repeat('─', 50) . "\n";

    for ($i = max(0, $totalDays - 5); $i < $totalDays; $i++) {
        $day = $days[$i];
        $age = $calculator->calculateAge($day['date'], $data['birth_date']);
        printf("%-12s %-10s %-4d %-6d %10.2f €\n",
            $day['date'],
            substr($day['day_of_week'], 0, 3),
            $age,
            $day['taux'],
            $day['amount']
        );
    }

    echo "\n*Âge calculé au jour donné\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
