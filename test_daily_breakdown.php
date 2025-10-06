<?php
/**
 * Test script to display day-by-day payment breakdown
 */

require_once 'IJCalculator.php';

// Load mock data (using mock.json as example)
$mockData = json_decode(file_get_contents('mock.json'), true);

// Initialize calculator
$calculator = new IJCalculator('taux.csv');

// Prepare calculation data
$data = [
    'arrets' => $mockData,
    'statut' => 'M',
    'classe' => 'A',
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

// Calculate
$result = $calculator->calculateAmount($data);

// Display summary
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  CALCUL DES INDEMNITÉS JOURNALIÈRES - DÉTAIL PAR JOUR\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

echo "Paramètres:\n";
echo "  Statut: {$data['statut']}\n";
echo "  Classe: {$data['classe']}\n";
echo "  Option: {$data['option']}%\n";
echo "  Date de naissance: {$data['birth_date']}\n";
echo "  Âge: {$result['age']} ans\n";
echo "  Trimestres: {$data['nb_trimestres']}\n";
echo "  Pathologie antérieure: " . ($data['patho_anterior'] ? 'Oui' : 'Non') . "\n\n";

echo "Résultat:\n";
echo "  Nombre de jours: {$result['nb_jours']}\n";
echo "  Montant total: " . number_format($result['montant'], 2, ',', ' ') . " €\n";
echo "  Cumul total: {$result['total_cumul_days']} jours\n\n";

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  DÉTAIL JOUR PAR JOUR\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Display day-by-day breakdown
if (isset($result['payment_details']) && is_array($result['payment_details'])) {
    $totalDays = 0;
    $totalAmount = 0;

    foreach ($result['payment_details'] as $arretIndex => $detail) {
        if (!isset($detail['daily_breakdown']) || empty($detail['daily_breakdown'])) {
            continue;
        }

        echo "┌─────────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ Arrêt #" . ($arretIndex + 1) . "\n";
        echo "│ Période: {$detail['payment_start']} → {$detail['payment_end']}\n";
        echo "│ Jours payables: {$detail['payable_days']}\n";
        echo "│ Montant: " . number_format($detail['montant'], 2, ',', ' ') . " €\n";
        echo "└─────────────────────────────────────────────────────────────────────────────┘\n\n";

        // Table header
        printf("%-12s %-10s %-4s %-4s %-3s %-7s %-6s %-12s %-12s\n",
            'Date',
            'Jour',
            'Année',
            'Mois',
            'Tri',
            'Période',
            'Taux',
            'Taux jour.',
            'Montant'
        );
        echo str_repeat('─', 100) . "\n";

        $arretTotal = 0;
        foreach ($detail['daily_breakdown'] as $day) {
            printf("%-12s %-10s %-4d %-4d %-3s %-7s %-6d %10.2f € %10.2f €\n",
                $day['date'],
                substr($day['day_of_week'], 0, 3),
                $day['year'],
                $day['month'],
                "Q{$day['trimester']}",
                is_numeric($day['period']) ? "P{$day['period']}" : $day['period'],
                $day['taux'],
                $day['daily_rate'],
                $day['amount']
            );
            $arretTotal += $day['amount'];
            $totalDays++;
            $totalAmount += $day['amount'];
        }

        echo str_repeat('─', 100) . "\n";
        printf("%-70s %10s %10.2f €\n\n",
            "Sous-total arrêt #" . ($arretIndex + 1),
            "",
            $arretTotal
        );
    }

    echo "═══════════════════════════════════════════════════════════════════════════════\n";
    printf("%-70s %10s %10.2f €\n",
        "TOTAL GÉNÉRAL ({$totalDays} jours)",
        "",
        $totalAmount
    );
    echo "═══════════════════════════════════════════════════════════════════════════════\n\n";
} else {
    echo "Aucun détail jour par jour disponible.\n\n";
}

// Display rate breakdown summary
if (isset($result['payment_details']) && is_array($result['payment_details'])) {
    echo "═══════════════════════════════════════════════════════════════════════════════\n";
    echo "  RÉCAPITULATIF PAR TAUX\n";
    echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

    // Collect all days by taux
    $byTaux = [];
    foreach ($result['payment_details'] as $detail) {
        if (!isset($detail['daily_breakdown'])) continue;

        foreach ($detail['daily_breakdown'] as $day) {
            $taux = $day['taux'];
            if (!isset($byTaux[$taux])) {
                $byTaux[$taux] = [
                    'days' => 0,
                    'rate' => $day['daily_rate'],
                    'total' => 0,
                    'period' => $day['period']
                ];
            }
            $byTaux[$taux]['days']++;
            $byTaux[$taux]['total'] += $day['amount'];
        }
    }

    ksort($byTaux);

    printf("%-6s %-10s %-15s %-15s %-15s\n",
        'Taux',
        'Période',
        'Nombre jours',
        'Taux jour.',
        'Montant total'
    );
    echo str_repeat('─', 70) . "\n";

    foreach ($byTaux as $taux => $info) {
        $periodLabel = is_numeric($info['period']) ? "Période {$info['period']}" : ucfirst($info['period']);

        printf("%-6d %-10s %13d j %13.2f € %13.2f €\n",
            $taux,
            $periodLabel,
            $info['days'],
            $info['rate'],
            $info['total']
        );
    }

    echo str_repeat('─', 70) . "\n\n";
}

// Display monthly summary
if (isset($result['payment_details']) && is_array($result['payment_details'])) {
    echo "═══════════════════════════════════════════════════════════════════════════════\n";
    echo "  RÉCAPITULATIF PAR MOIS\n";
    echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

    // Collect all days by month
    $byMonth = [];
    foreach ($result['payment_details'] as $detail) {
        if (!isset($detail['daily_breakdown'])) continue;

        foreach ($detail['daily_breakdown'] as $day) {
            $monthKey = $day['year'] . '-' . str_pad($day['month'], 2, '0', STR_PAD_LEFT);
            if (!isset($byMonth[$monthKey])) {
                $byMonth[$monthKey] = [
                    'days' => 0,
                    'total' => 0,
                    'year' => $day['year'],
                    'month' => $day['month']
                ];
            }
            $byMonth[$monthKey]['days']++;
            $byMonth[$monthKey]['total'] += $day['amount'];
        }
    }

    ksort($byMonth);

    printf("%-10s %-15s %-15s\n",
        'Mois',
        'Nombre jours',
        'Montant total'
    );
    echo str_repeat('─', 45) . "\n";

    foreach ($byMonth as $monthKey => $info) {
        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        $monthLabel = $monthNames[$info['month']] . ' ' . $info['year'];

        printf("%-10s %13d j %13.2f €\n",
            $monthKey,
            $info['days'],
            $info['total']
        );
    }

    echo str_repeat('─', 45) . "\n\n";
}
