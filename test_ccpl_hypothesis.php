<?php

require_once 'IJCalculator.php';

$calculator = new IJCalculator('/home/mista/work/ij/taux.csv');

echo "=== TEST HYPOTHÈSE CCPL ===\n\n";

$mock5 = json_decode(file_get_contents('/home/mista/work/ij/mock5.json'), true);

// Hypothèse: Pour CCPL option 25, utiliser classe A sans multiplicateur
echo "Hypothèse 1: CCPL option 25 = classe A × 1.0\n";
$data1 = [
    'arrets' => $mock5,
    'statut' => 'CCPL',
    'classe' => 'A',
    'option' => 100,  // 100% de classe A
    'pass_value' => 47000,
    'birth_date' => '1984-01-08',
    'current_date' => '2024-09-30',
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0
];

$result1 = $calculator->calculateAmount($data1);
echo "Calculé avec classe A option 100: " . number_format($result1['montant'], 2) . "€\n";
echo "Attendu: 34276.56€\n";
echo "Différence: " . number_format($result1['montant'] - 34276.56, 2) . "€\n\n";

// Vérifier les taux utilisés
$rateData2022 = $calculator->getRateForYear(2022);
$rateData2023 = $calculator->getRateForYear(2023);
$rateData2024 = $calculator->getRateForYear(2024);

echo "=== TAUX CLASSE A (pour vérification) ===\n";
echo "2022: taux_a1 = {$rateData2022['taux_a1']}€\n";
echo "2023: taux_a1 = {$rateData2023['taux_a1']}€\n";
echo "2024: taux_a1 = {$rateData2024['taux_a1']}€\n\n";

// Calcul manuel avec classe A
echo "Calcul manuel classe A:\n";
// 2022: du 05/03 au 31/12 = 302 jours
$days2022 = 302;
$amount2022 = $days2022 * $rateData2022['taux_a1'];
echo "2022: {$days2022}j × {$rateData2022['taux_a1']}€ = " . number_format($amount2022, 2) . "€\n";

// 2023: toute l'année = 365 jours
$days2023 = 365;
$amount2023 = $days2023 * $rateData2023['taux_a1'];
echo "2023: {$days2023}j × {$rateData2023['taux_a1']}€ = " . number_format($amount2023, 2) . "€\n";

// 2024: du 01/01 au 30/09 = 274 jours
$days2024 = 274;
$amount2024 = $days2024 * $rateData2024['taux_a1'];
echo "2024: {$days2024}j × {$rateData2024['taux_a1']}€ = " . number_format($amount2024, 2) . "€\n";

$totalManual = $amount2022 + $amount2023 + $amount2024;
echo "Total manuel: " . number_format($totalManual, 2) . "€\n";
echo "Attendu: 34276.56€\n";
echo "Différence: " . number_format($totalManual - 34276.56, 2) . "€\n\n";

// Test avec multiplicateur 0.5 sur classe C
echo "=== Autre hypothèse: Classe C × 0.5 ===\n";
$data2 = [
    'arrets' => $mock5,
    'statut' => 'CCPL',
    'classe' => 'C',
    'option' => 50,
    'pass_value' => 47000,
    'birth_date' => '1984-01-08',
    'current_date' => '2024-09-30',
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0
];

$result2 = $calculator->calculateAmount($data2);
echo "Calculé avec classe C option 50: " . number_format($result2['montant'], 2) . "€\n";
echo "Attendu: 34276.56€\n";
echo "Différence: " . number_format($result2['montant'] - 34276.56, 2) . "€\n";
