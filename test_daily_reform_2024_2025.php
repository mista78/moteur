<?php
/**
 * Test Daily Rates - 2024 to 2025 Reform
 *
 * Generates daily rate calculations showing the reform application
 * with HTML table visualization
 */

require __DIR__ . '/vendor/autoload.php';

use App\Services\RateService;

// Simulate taux 2024 and 2025 in database
$rates = [
    [
        'date_start' => '2024-01-01',
        'date_end' => '2024-12-31',
        'taux_a1' => 80.00,
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
        'taux_a1' => 100.00,
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

/**
 * Generate daily calculations for an arrêt
 */
function generateDailyCalculations(RateService $rateService, string $dateDebut, string $dateFin, string $classe, int $year): array {
    $rate = $rateService->getDailyRate(
        statut: 'M',
        classe: $classe,
        option: 100,
        taux: 1,
        year: $year,
        date: $dateDebut
    );

    $days = [];
    $current = new DateTime($dateDebut);
    $end = new DateTime($dateFin);
    $dayNumber = 1;

    while ($current <= $end) {
        $days[] = [
            'day_number' => $dayNumber,
            'date' => $current->format('Y-m-d'),
            'day_name' => $current->format('l'),
            'month' => $current->format('F Y'),
            'rate' => $rate,
            'amount' => $rate
        ];
        $current->modify('+1 day');
        $dayNumber++;
    }

    return [
        'days' => $days,
        'total_days' => count($days),
        'daily_rate' => $rate,
        'total_amount' => $rate * count($days)
    ];
}

/**
 * Generate HTML table
 */
function generateHtmlTable(array $scenarios): string {
    $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réforme 2025 - Calculs Journaliers</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        .info-box {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px;
            border-radius: 10px;
            border-left: 5px solid #667eea;
        }
        .info-box h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .info-box ul {
            list-style: none;
            padding-left: 0;
        }
        .info-box li {
            padding: 5px 0;
            padding-left: 25px;
            position: relative;
        }
        .info-box li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
        .scenario {
            margin: 30px;
        }
        .scenario-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin-top: 30px;
        }
        .scenario-header h2 {
            font-size: 1.8em;
            margin-bottom: 10px;
        }
        .scenario-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .info-card {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        .info-card .label {
            font-size: 0.85em;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        .info-card .value {
            font-size: 1.3em;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .month-2024 {
            background-color: #fff3cd;
        }
        .month-2025 {
            background-color: #d1ecf1;
        }
        .total-row {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: bold;
            font-size: 1.1em;
        }
        .total-row td {
            padding: 20px 15px;
            border: none;
        }
        .rate-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.95em;
        }
        .rate-2024 {
            background-color: #ffc107;
            color: #000;
        }
        .rate-2025-db {
            background-color: #28a745;
            color: white;
        }
        .rate-pass {
            background-color: #007bff;
            color: white;
        }
        .summary {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px;
            border-radius: 10px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid #667eea;
        }
        .summary-card h4 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        .summary-card .amount {
            font-size: 2em;
            font-weight: bold;
            color: #333;
        }
        .comparison {
            margin: 30px;
            padding: 20px;
            background: #fff3cd;
            border-radius: 10px;
            border: 2px solid #ffc107;
        }
        .comparison h3 {
            color: #856404;
            margin-bottom: 15px;
        }
        .footer {
            background: #343a40;
            color: white;
            padding: 20px;
            text-align: center;
            margin-top: 30px;
        }
        .legend {
            margin: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .legend h3 {
            margin-bottom: 15px;
            color: #667eea;
        }
        .legend-items {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .legend-color {
            width: 30px;
            height: 30px;
            border-radius: 5px;
        }
        @media print {
            body {
                background: white;
            }
            .container {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">';

    $html .= '<div class="header">
        <h1>🏥 Réforme 2025 - Calculs Journaliers</h1>
        <p>Analyse détaillée de l\'application des taux jour par jour</p>
        <p style="font-size: 0.9em; margin-top: 10px;">Générée le ' . date('d/m/Y à H:i:s') . '</p>
    </div>';

    $html .= '<div class="info-box">
        <h3>📋 Règles de la Réforme 2025</h3>
        <ul>
            <li><strong>Date d\'effet &gt;= 2025-01-01</strong> : Utilise la formule PASS (Classe × 46368 / 730)</li>
            <li><strong>Date d\'effet &lt; 2025-01-01 ET Année courante &gt;= 2025</strong> : Utilise taux 2025 DB</li>
            <li><strong>Date d\'effet &lt; 2025-01-01 ET Année courante &lt; 2025</strong> : Utilise taux historiques</li>
            <li><strong>Critère déterminant</strong> : DATE D\'EFFET (pas date de paiement, pas date du jour)</li>
            <li><strong>Tous les jours</strong> d\'un même arrêt utilisent le même taux (basé sur date d\'effet)</li>
        </ul>
    </div>';

    $html .= '<div class="legend">
        <h3>🎨 Légende des Couleurs</h3>
        <div class="legend-items">
            <div class="legend-item">
                <div class="legend-color" style="background-color: #fff3cd;"></div>
                <span>Décembre 2024</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: #d1ecf1;"></div>
                <span>Janvier 2025</span>
            </div>
            <div class="legend-item">
                <span class="rate-badge rate-2024">Taux 2024 DB</span>
            </div>
            <div class="legend-item">
                <span class="rate-badge rate-2025-db">Taux 2025 DB</span>
            </div>
            <div class="legend-item">
                <span class="rate-badge rate-pass">Formule PASS</span>
            </div>
        </div>
    </div>';

    foreach ($scenarios as $scenario) {
        $html .= '<div class="scenario">';
        $html .= '<div class="scenario-header">';
        $html .= '<h2>' . htmlspecialchars($scenario['title']) . '</h2>';
        $html .= '<p>' . htmlspecialchars($scenario['description']) . '</p>';

        $html .= '<div class="scenario-info">';
        $html .= '<div class="info-card">
            <div class="label">Date d\'effet</div>
            <div class="value">' . $scenario['date_effet'] . '</div>
        </div>';
        $html .= '<div class="info-card">
            <div class="label">Classe</div>
            <div class="value">' . $scenario['classe'] . '</div>
        </div>';
        $html .= '<div class="info-card">
            <div class="label">Taux journalier</div>
            <div class="value">' . number_format($scenario['data']['daily_rate'], 2, ',', ' ') . ' €</div>
        </div>';
        $html .= '<div class="info-card">
            <div class="label">Système</div>
            <div class="value">' . $scenario['system'] . '</div>
        </div>';
        $html .= '<div class="info-card">
            <div class="label">Nombre de jours</div>
            <div class="value">' . $scenario['data']['total_days'] . ' jours</div>
        </div>';
        $html .= '<div class="info-card">
            <div class="label">Montant total</div>
            <div class="value">' . number_format($scenario['data']['total_amount'], 2, ',', ' ') . ' €</div>
        </div>';
        $html .= '</div>'; // scenario-info
        $html .= '</div>'; // scenario-header

        $html .= '<table>';
        $html .= '<thead><tr>
            <th>Jour #</th>
            <th>Date</th>
            <th>Jour</th>
            <th>Mois</th>
            <th>Taux journalier</th>
            <th>Montant</th>
        </tr></thead>';
        $html .= '<tbody>';

        foreach ($scenario['data']['days'] as $day) {
            $rowClass = (strpos($day['month'], '2024') !== false) ? 'month-2024' : 'month-2025';
            $html .= '<tr class="' . $rowClass . '">';
            $html .= '<td><strong>' . $day['day_number'] . '</strong></td>';
            $html .= '<td>' . date('d/m/Y', strtotime($day['date'])) . '</td>';
            $html .= '<td>' . $day['day_name'] . '</td>';
            $html .= '<td>' . $day['month'] . '</td>';
            $html .= '<td><strong>' . number_format($day['rate'], 2, ',', ' ') . ' €</strong></td>';
            $html .= '<td>' . number_format($day['amount'], 2, ',', ' ') . ' €</td>';
            $html .= '</tr>';
        }

        $html .= '<tr class="total-row">';
        $html .= '<td colspan="4"><strong>TOTAL</strong></td>';
        $html .= '<td><strong>' . $scenario['data']['total_days'] . ' jours × ' . number_format($scenario['data']['daily_rate'], 2, ',', ' ') . ' €</strong></td>';
        $html .= '<td><strong>' . number_format($scenario['data']['total_amount'], 2, ',', ' ') . ' €</strong></td>';
        $html .= '</tr>';

        $html .= '</tbody></table>';
        $html .= '</div>'; // scenario
    }

    $html .= '<div class="footer">
        <p><strong>CARMF IJ Calculator - Réforme 2025</strong></p>
        <p>Système de calcul des indemnités journalières avec application automatique de la réforme</p>
    </div>';

    $html .= '</div></body></html>';

    return $html;
}

// ============================================
// SCENARIO 1: Arrêt débutant en décembre 2024
// ============================================
echo "Génération des scénarios...\n\n";

$scenario1 = [
    'title' => 'Scénario 1 : Arrêt Débutant en Décembre 2024',
    'description' => 'Arrêt du 20 décembre 2024 au 10 janvier 2025 (date_effet = 2024-12-20)',
    'date_effet' => '2024-12-20',
    'date_fin' => '2025-01-10',
    'classe' => 'A',
    'year' => 2024,
    'system' => '🟢 Taux 2025 DB',
    'data' => generateDailyCalculations($rateService, '2024-12-20', '2025-01-10', 'A', 2024)
];

echo "✓ Scénario 1 généré : {$scenario1['data']['total_days']} jours\n";

// ============================================
// SCENARIO 2: Arrêt débutant en janvier 2025
// ============================================

$scenario2 = [
    'title' => 'Scénario 2 : Arrêt Débutant en Janvier 2025',
    'description' => 'Arrêt du 5 janvier 2025 au 25 janvier 2025 (date_effet = 2025-01-05)',
    'date_effet' => '2025-01-05',
    'date_fin' => '2025-01-25',
    'classe' => 'A',
    'year' => 2025,
    'system' => '🔵 Formule PASS',
    'data' => generateDailyCalculations($rateService, '2025-01-05', '2025-01-25', 'A', 2025)
];

echo "✓ Scénario 2 généré : {$scenario2['data']['total_days']} jours\n";

// ============================================
// SCENARIO 3: Classe B - Décembre 2024
// ============================================

$scenario3 = [
    'title' => 'Scénario 3 : Classe B - Arrêt Décembre 2024 à Janvier 2025',
    'description' => 'Arrêt du 15 décembre 2024 au 15 janvier 2025 (date_effet = 2024-12-15)',
    'date_effet' => '2024-12-15',
    'date_fin' => '2025-01-15',
    'classe' => 'B',
    'year' => 2024,
    'system' => '🟢 Taux 2025 DB',
    'data' => generateDailyCalculations($rateService, '2024-12-15', '2025-01-15', 'B', 2024)
];

echo "✓ Scénario 3 généré : {$scenario3['data']['total_days']} jours\n";

// ============================================
// SCENARIO 4: Classe C - Janvier 2025
// ============================================

$scenario4 = [
    'title' => 'Scénario 4 : Classe C - Arrêt Janvier 2025',
    'description' => 'Arrêt du 2 janvier 2025 au 31 janvier 2025 (date_effet = 2025-01-02)',
    'date_effet' => '2025-01-02',
    'date_fin' => '2025-01-31',
    'classe' => 'C',
    'year' => 2025,
    'system' => '🔵 Formule PASS',
    'data' => generateDailyCalculations($rateService, '2025-01-02', '2025-01-31', 'C', 2025)
];

echo "✓ Scénario 4 généré : {$scenario4['data']['total_days']} jours\n";

// ============================================
// SCENARIO 5: Edge case - 31 décembre 2024
// ============================================

$scenario5 = [
    'title' => 'Scénario 5 : Cas Limite - 31 Décembre 2024',
    'description' => 'Arrêt du 31 décembre 2024 au 15 janvier 2025 (date_effet = 2024-12-31)',
    'date_effet' => '2024-12-31',
    'date_fin' => '2025-01-15',
    'classe' => 'A',
    'year' => 2024,
    'system' => '🟢 Taux 2025 DB',
    'data' => generateDailyCalculations($rateService, '2024-12-31', '2025-01-15', 'A', 2024)
];

echo "✓ Scénario 5 généré : {$scenario5['data']['total_days']} jours\n";

// ============================================
// Generate HTML
// ============================================

$scenarios = [$scenario1, $scenario2, $scenario3, $scenario4, $scenario5];
$html = generateHtmlTable($scenarios);

$outputFile = __DIR__ . '/test_daily_reform_2024_2025_results.html';
file_put_contents($outputFile, $html);

echo "\n============================================\n";
echo "✓ Rapport HTML généré avec succès!\n";
echo "============================================\n\n";

echo "Fichier: {$outputFile}\n\n";

// Display summary in console
echo "Résumé des Scénarios:\n";
echo "=====================\n\n";

foreach ($scenarios as $i => $scenario) {
    $num = $i + 1;
    echo "Scénario {$num}: {$scenario['title']}\n";
    echo "  Date d'effet:   {$scenario['date_effet']}\n";
    echo "  Classe:         {$scenario['classe']}\n";
    echo "  Système:        {$scenario['system']}\n";
    echo "  Taux journalier: " . number_format($scenario['data']['daily_rate'], 2) . " €\n";
    echo "  Nombre de jours: {$scenario['data']['total_days']}\n";
    echo "  Montant total:   " . number_format($scenario['data']['total_amount'], 2) . " €\n";
    echo "\n";
}

echo "============================================\n";
echo "Ouvrez le fichier HTML dans votre navigateur:\n";
echo "  {$outputFile}\n";
echo "============================================\n";
