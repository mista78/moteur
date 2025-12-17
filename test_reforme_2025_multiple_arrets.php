<?php
/**
 * Test Réforme 2025 - Multiple Arrêts
 *
 * Comprehensive testing of 2025 reform with multiple work stoppages (arrêts)
 * Tests various scenarios:
 * - Multiple arrêts all in 2024
 * - Multiple arrêts all in 2025
 * - Mixed arrêts (some 2024, some 2025)
 * - Arrêts spanning 2024-2025 boundary
 * - Rechute cases
 * - All classes (A, B, C) together
 */

require __DIR__ . '/vendor/autoload.php';

use App\IJCalculator;
use App\Repositories\RateRepository;

// Simulate rates with realistic 2024/2025 values
$rates = [
    [
        'date_start' => '2024-01-01',
        'date_end' => '2024-12-31',
        'taux_a1' => 80.00,
        'taux_a2' => 53.33,
        'taux_a3' => 26.67,
        'taux_b1' => 160.00,
        'taux_b2' => 106.67,
        'taux_b3' => 53.33,
        'taux_c1' => 240.00,
        'taux_c2' => 160.00,
        'taux_c3' => 80.00,
    ],
    [
        'date_start' => '2025-01-01',
        'date_end' => '2025-12-31',
        'taux_a1' => 100.00,
        'taux_a2' => 66.67,
        'taux_a3' => 33.33,
        'taux_b1' => 200.00,
        'taux_b2' => 133.33,
        'taux_b3' => 66.67,
        'taux_c1' => 300.00,
        'taux_c2' => 200.00,
        'taux_c3' => 100.00,
    ]
];

$passValue = 46368;

echo "════════════════════════════════════════════════════════════════════════════\n";
echo "Test Réforme 2025 - Scénarios avec Multiples Arrêts\n";
echo "════════════════════════════════════════════════════════════════════════════\n\n";

echo "Configuration:\n";
echo "  PASS = {$passValue} €\n";
echo "  Taux 2024 DB: A1=80€, B1=160€, C1=240€\n";
echo "  Taux 2025 DB: A1=100€, B1=200€, C1=300€\n";
echo "  Formule PASS: A1=63.52€, B1=127.04€, C1=190.55€\n\n";

// ============================================================================
// Scenario 1: Three arrêts all in 2024
// ============================================================================
echo "════════════════════════════════════════════════════════════════════════════\n";
echo "Scénario 1: Trois arrêts tous en 2024\n";
echo "════════════════════════════════════════════════════════════════════════════\n\n";

$scenario1 = [
    'statut' => 'M',
    'classe' => 'B',
    'option' => 100,
    'birth_date' => '1980-05-15',
    'current_date' => '2024-12-31',
    'attestation_date' => '2024-12-31',
    'affiliation_date' => '2015-01-01',
    'nb_trimestres' => 40,
    'arrets' => [
        [
            'arret-from-line' => '2024-01-10',
            'arret-to-line' => '2024-02-10',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ],
        [
            'arret-from-line' => '2024-05-01',
            'arret-to-line' => '2024-06-15',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ],
        [
            'arret-from-line' => '2024-10-01',
            'arret-to-line' => '2024-11-30',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ]
    ]
];

echo "Description:\n";
echo "  - Arrêt 1: 10 Jan 2024 → 10 Fév 2024 (32 jours)\n";
echo "  - Arrêt 2: 01 Mai 2024 → 15 Juin 2024 (46 jours)\n";
echo "  - Arrêt 3: 01 Oct 2024 → 30 Nov 2024 (61 jours)\n";
echo "  - Classe B, tous en 2024\n\n";

$calculator = new IJCalculator($rates);
$calculator->setPassValue($passValue);
$result1 = $calculator->calculateAmount($scenario1);

echo "Résultat:\n";
echo "  Montant total: " . number_format($result1['montant'], 2) . " €\n";
echo "  Jours payés:   " . $result1['nb_jours'] . " jours\n";
echo "  Taux moyen:    " . number_format($result1['montant'] / $result1['nb_jours'], 2) . " €/jour\n\n";

echo "Attentes:\n";
echo "  → Tous les arrêts en 2024 devraient utiliser les taux 2024 DB\n";
echo "  → Taux attendu Classe B: 160.00 € ou dérivés (taux 2024 DB)\n\n";

// ============================================================================
// Scenario 2: Three arrêts all in 2025
// ============================================================================
echo "════════════════════════════════════════════════════════════════════════════\n";
echo "Scénario 2: Trois arrêts tous en 2025\n";
echo "════════════════════════════════════════════════════════════════════════════\n\n";

$scenario2 = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1985-03-20',
    'current_date' => '2025-12-31',
    'attestation_date' => '2025-12-31',
    'affiliation_date' => '2015-06-01',
    'nb_trimestres' => 42,
    'arrets' => [
        [
            'arret-from-line' => '2025-02-01',
            'arret-to-line' => '2025-03-15',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ],
        [
            'arret-from-line' => '2025-06-10',
            'arret-to-line' => '2025-07-20',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ],
        [
            'arret-from-line' => '2025-10-05',
            'arret-to-line' => '2025-11-25',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ]
    ]
];

echo "Description:\n";
echo "  - Arrêt 1: 01 Fév 2025 → 15 Mar 2025 (43 jours)\n";
echo "  - Arrêt 2: 10 Juin 2025 → 20 Juil 2025 (41 jours)\n";
echo "  - Arrêt 3: 05 Oct 2025 → 25 Nov 2025 (52 jours)\n";
echo "  - Classe A, tous en 2025\n\n";

$calculator2 = new IJCalculator($rates);
$calculator2->setPassValue($passValue);
$result2 = $calculator2->calculateAmount($scenario2);

echo "Résultat:\n";
echo "  Montant total: " . number_format($result2['montant'], 2) . " €\n";
echo "  Jours payés:   " . $result2['nb_jours'] . " jours\n";
echo "  Taux moyen:    " . number_format($result2['montant'] / $result2['nb_jours'], 2) . " €/jour\n\n";

echo "Attentes:\n";
echo "  → Tous les arrêts débutant en 2025 devraient utiliser la formule PASS\n";
echo "  → Taux attendu Classe A: ~63.52 € (PASS formula)\n\n";

// ============================================================================
// Scenario 3: Mixed - Some in 2024, some in 2025
// ============================================================================
echo "════════════════════════════════════════════════════════════════════════════\n";
echo "Scénario 3: Arrêts mixtes (2024 et 2025)\n";
echo "════════════════════════════════════════════════════════════════════════════\n\n";

$scenario3 = [
    'statut' => 'M',
    'classe' => 'C',
    'option' => 100,
    'birth_date' => '1978-11-10',
    'current_date' => '2025-12-31',
    'attestation_date' => '2025-12-31',
    'affiliation_date' => '2010-01-01',
    'nb_trimestres' => 64,
    'arrets' => [
        [
            'arret-from-line' => '2024-06-01',
            'arret-to-line' => '2024-07-15',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ],
        [
            'arret-from-line' => '2024-11-01',
            'arret-to-line' => '2024-12-20',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ],
        [
            'arret-from-line' => '2025-03-10',
            'arret-to-line' => '2025-04-30',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ],
        [
            'arret-from-line' => '2025-08-15',
            'arret-to-line' => '2025-09-30',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ]
    ]
];

echo "Description:\n";
echo "  - Arrêt 1: 01 Juin 2024 → 15 Juil 2024 (45 jours) [2024]\n";
echo "  - Arrêt 2: 01 Nov 2024 → 20 Déc 2024 (50 jours) [2024]\n";
echo "  - Arrêt 3: 10 Mar 2025 → 30 Avr 2025 (52 jours) [2025]\n";
echo "  - Arrêt 4: 15 Aoû 2025 → 30 Sep 2025 (47 jours) [2025]\n";
echo "  - Classe C, mélange 2024/2025\n\n";

$calculator3 = new IJCalculator($rates);
$calculator3->setPassValue($passValue);
$result3 = $calculator3->calculateAmount($scenario3);

echo "Résultat:\n";
echo "  Montant total: " . number_format($result3['montant'], 2) . " €\n";
echo "  Jours payés:   " . $result3['nb_jours'] . " jours\n";
echo "  Taux moyen:    " . number_format($result3['montant'] / $result3['nb_jours'], 2) . " €/jour\n\n";

echo "Attentes:\n";
echo "  → Arrêts 1-2 (2024): Taux 2024 DB (~240€ ou dérivés)\n";
echo "  → Arrêts 3-4 (2025): Formule PASS (~190.55€)\n";
echo "  → Taux moyen devrait être un mélange des deux systèmes\n\n";

// ============================================================================
// Scenario 4: Arrêts spanning 2024-2025 boundary
// ============================================================================
echo "════════════════════════════════════════════════════════════════════════════\n";
echo "Scénario 4: Arrêts traversant la frontière 2024-2025\n";
echo "════════════════════════════════════════════════════════════════════════════\n\n";

$scenario4 = [
    'statut' => 'M',
    'classe' => 'B',
    'option' => 100,
    'birth_date' => '1982-07-25',
    'current_date' => '2025-03-31',
    'attestation_date' => '2025-03-31',
    'affiliation_date' => '2015-01-01',
    'nb_trimestres' => 40,
    'arrets' => [
        [
            'arret-from-line' => '2024-11-15',
            'arret-to-line' => '2025-01-20',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ],
        [
            'arret-from-line' => '2024-12-10',
            'arret-to-line' => '2025-02-15',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ]
    ]
];

echo "Description:\n";
echo "  - Arrêt 1: 15 Nov 2024 → 20 Jan 2025 (traverse l'année)\n";
echo "  - Arrêt 2: 10 Déc 2024 → 15 Fév 2025 (traverse l'année)\n";
echo "  - Classe B\n\n";

$calculator4 = new IJCalculator($rates);
$calculator4->setPassValue($passValue);
$result4 = $calculator4->calculateAmount($scenario4);

echo "Résultat:\n";
echo "  Montant total: " . number_format($result4['montant'], 2) . " €\n";
echo "  Jours payés:   " . $result4['nb_jours'] . " jours\n";
echo "  Taux moyen:    " . number_format($result4['montant'] / $result4['nb_jours'], 2) . " €/jour\n\n";

echo "Attentes:\n";
echo "  → Arrêts avec date_effet < 2025 et jours en 2024: Taux 2024 DB (160€)\n";
echo "  → Arrêts avec date_effet < 2025 et jours en 2025: Taux 2025 DB (200€)\n";
echo "  → Le taux moyen reflétera le mix de jours en 2024 et 2025\n\n";

// ============================================================================
// Scenario 5: Rechute cases crossing years
// ============================================================================
echo "════════════════════════════════════════════════════════════════════════════\n";
echo "Scénario 5: Rechutes traversant les années\n";
echo "════════════════════════════════════════════════════════════════════════════\n\n";

$scenario5 = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1990-02-14',
    'current_date' => '2025-12-31',
    'attestation_date' => '2025-12-31',
    'affiliation_date' => '2018-01-01',
    'nb_trimestres' => 32,
    'arrets' => [
        // Initial arrêt in 2024
        [
            'arret-from-line' => '2024-03-01',
            'arret-to-line' => '2024-05-30',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ],
        // Rechute in 2024 (within 1 year)
        [
            'arret-from-line' => '2024-08-15',
            'arret-to-line' => '2024-10-10',
            'rechute-line' => 1,
            'dt-line' => 0,
            'gpm-member-line' => 0
        ],
        // Rechute spanning 2024-2025
        [
            'arret-from-line' => '2024-12-01',
            'arret-to-line' => '2025-02-15',
            'rechute-line' => 1,
            'dt-line' => 0,
            'gpm-member-line' => 0
        ],
        // New arrêt in 2025
        [
            'arret-from-line' => '2025-06-01',
            'arret-to-line' => '2025-07-31',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ]
    ]
];

echo "Description:\n";
echo "  - Arrêt initial: 01 Mar 2024 → 30 Mai 2024\n";
echo "  - Rechute 1:     15 Aoû 2024 → 10 Oct 2024 (rechute en 2024)\n";
echo "  - Rechute 2:     01 Déc 2024 → 15 Fév 2025 (rechute traverse années)\n";
echo "  - Nouvel arrêt:  01 Juin 2025 → 31 Juil 2025 (nouveau en 2025)\n";
echo "  - Classe A\n\n";

$calculator5 = new IJCalculator($rates);
$calculator5->setPassValue($passValue);
$result5 = $calculator5->calculateAmount($scenario5);

echo "Résultat:\n";
echo "  Montant total: " . number_format($result5['montant'], 2) . " €\n";
echo "  Jours payés:   " . $result5['nb_jours'] . " jours\n";
echo "  Taux moyen:    " . number_format($result5['montant'] / $result5['nb_jours'], 2) . " €/jour\n\n";

echo "Attentes:\n";
echo "  → Rechutes utilisent les règles de date-effet (15 jours au lieu de 90)\n";
echo "  → Rechute traversant 2024-2025: mélange taux 2024 DB (jours 2024) et 2025 DB (jours 2025)\n";
echo "  → Nouvel arrêt en 2025: formule PASS (~63.52€)\n\n";

// ============================================================================
// Scenario 6: All classes together (A, B, C)
// ============================================================================
echo "════════════════════════════════════════════════════════════════════════════\n";
echo "Scénario 6: Comparaison des trois classes avec mêmes arrêts\n";
echo "════════════════════════════════════════════════════════════════════════════\n\n";

echo "Configuration identique pour toutes les classes:\n";
echo "  - Arrêt 1: 20 Nov 2024 → 15 Jan 2025 (traverse années)\n";
echo "  - Arrêt 2: 01 Mar 2025 → 30 Avr 2025 (en 2025)\n\n";

$baseScenario = [
    'statut' => 'M',
    'option' => 100,
    'birth_date' => '1983-06-10',
    'current_date' => '2025-12-31',
    'attestation_date' => '2025-12-31',
    'affiliation_date' => '2015-01-01',
    'nb_trimestres' => 44,
    'arrets' => [
        [
            'arret-from-line' => '2024-11-20',
            'arret-to-line' => '2025-01-15',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ],
        [
            'arret-from-line' => '2025-03-01',
            'arret-to-line' => '2025-04-30',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ]
    ]
];

echo "┌─────────┬────────────────┬──────────────┬───────────────┐\n";
echo "│ Classe  │ Montant Total  │ Jours Payés  │  Taux Moyen   │\n";
echo "├─────────┼────────────────┼──────────────┼───────────────┤\n";

foreach (['A', 'B', 'C'] as $classe) {
    $scenario = $baseScenario;
    $scenario['classe'] = $classe;

    $calc = new IJCalculator($rates);
    $calc->setPassValue($passValue);
    $result = $calc->calculateAmount($scenario);

    $tauxMoyen = $result['nb_jours'] > 0 ? $result['montant'] / $result['nb_jours'] : 0;

    printf("│    %s    │ %11.2f €  │     %3d      │   %7.2f €   │\n",
        $classe,
        $result['montant'],
        $result['nb_jours'],
        $tauxMoyen
    );
}

echo "└─────────┴────────────────┴──────────────┴───────────────┘\n\n";

echo "Observations:\n";
echo "  → Classe C devrait avoir le montant le plus élevé\n";
echo "  → Classe B devrait être ~2x Classe A\n";
echo "  → Classe C devrait être ~3x Classe A\n";
echo "  → Tous utilisent le même nombre de jours payés\n\n";

// ============================================================================
// Scenario 7: Complex case - 6 arrêts over 2 years
// ============================================================================
echo "════════════════════════════════════════════════════════════════════════════\n";
echo "Scénario 7: Cas complexe - 6 arrêts sur 2 ans\n";
echo "════════════════════════════════════════════════════════════════════════════\n\n";

$scenario7 = [
    'statut' => 'M',
    'classe' => 'B',
    'option' => 100,
    'birth_date' => '1979-04-05',
    'current_date' => '2025-12-31',
    'attestation_date' => '2025-12-31',
    'affiliation_date' => '2012-01-01',
    'nb_trimestres' => 56,
    'arrets' => [
        // 2024 arrêts
        [
            'arret-from-line' => '2024-01-15',
            'arret-to-line' => '2024-02-28',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ],
        [
            'arret-from-line' => '2024-04-10',
            'arret-to-line' => '2024-05-20',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ],
        [
            'arret-from-line' => '2024-07-01',
            'arret-to-line' => '2024-08-15',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ],
        // Spanning arrêt
        [
            'arret-from-line' => '2024-12-15',
            'arret-to-line' => '2025-01-31',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ],
        // 2025 arrêts
        [
            'arret-from-line' => '2025-04-01',
            'arret-to-line' => '2025-05-31',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ],
        [
            'arret-from-line' => '2025-09-01',
            'arret-to-line' => '2025-10-31',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1
        ]
    ]
];

echo "Description:\n";
echo "  6 arrêts répartis sur 2024 et 2025:\n";
echo "  - 3 arrêts complets en 2024\n";
echo "  - 1 arrêt traversant 2024-2025\n";
echo "  - 2 arrêts complets en 2025\n";
echo "  - Classe B\n\n";

$calculator7 = new IJCalculator($rates);
$calculator7->setPassValue($passValue);
$result7 = $calculator7->calculateAmount($scenario7);

echo "Résultat:\n";
echo "  Montant total: " . number_format($result7['montant'], 2) . " €\n";
echo "  Jours payés:   " . $result7['nb_jours'] . " jours\n";
echo "  Taux moyen:    " . number_format($result7['montant'] / $result7['nb_jours'], 2) . " €/jour\n\n";

// Show breakdown if details available
if (isset($result7['details']) && is_array($result7['details'])) {
    echo "Détails par arrêt:\n";
    foreach ($result7['details'] as $idx => $detail) {
        $arretNum = $idx + 1;
        echo "  Arrêt {$arretNum}: " . number_format($detail['montant'] ?? 0, 2) . " € ";
        echo "(" . ($detail['jours'] ?? 0) . " jours)\n";
    }
    echo "\n";
}

echo "Attentes:\n";
echo "  → Mélange complexe de taux 2024 DB, 2025 DB et PASS formula\n";
echo "  → Le calcul doit gérer correctement les transitions entre systèmes\n\n";

// ============================================================================
// Final Summary
// ============================================================================
echo "════════════════════════════════════════════════════════════════════════════\n";
echo "✓ Tous les Scénarios Multi-Arrêts Testés\n";
echo "════════════════════════════════════════════════════════════════════════════\n\n";

echo "RÉSUMÉ DES RÈGLES:\n";
echo "==================\n\n";

echo "1️⃣  Arrêts avec date_effet en 2024:\n";
echo "    • Jours en 2024 → Taux 2024 DB\n";
echo "    • Jours en 2025 → Taux 2025 DB\n\n";

echo "2️⃣  Arrêts avec date_effet en 2025:\n";
echo "    • Tous les jours → Formule PASS\n\n";

echo "3️⃣  Rechutes:\n";
echo "    • Date-effet réduite (15 jours au lieu de 90)\n";
echo "    • Suivent les mêmes règles de taux selon l'année\n\n";

echo "4️⃣  Classes multiples:\n";
echo "    • Classe A: 1× multiplicateur\n";
echo "    • Classe B: 2× multiplicateur\n";
echo "    • Classe C: 3× multiplicateur\n\n";

echo "════════════════════════════════════════════════════════════════════════════\n";
