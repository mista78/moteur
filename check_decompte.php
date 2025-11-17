<?php

require_once __DIR__ . '/IJCalculator.php';
use App\IJCalculator\IJCalculator;

$calculator = new IJCalculator(__DIR__ . '/taux.csv');

$inputData = json_decode(file_get_contents(__DIR__ . '/mock.json'), true);

// Build input matching API format
$input = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => $inputData[0]['date_naissance'],
    'current_date' => '2024-09-20',
    'attestation_date' => $inputData[0]['attestation-date-line'] ?? null,
    'last_payment_date' => null,
    'affiliation_date' => '2019-01-15',
    'nb_trimestres' => 22,
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000,
    'arrets' => $inputData
];

$result = $calculator->calculateAmount($input);

echo "Full Calculation Result:\n";
echo "nb_jours: " . $result['nb_jours'] . "\n";
echo "montant: " . $result['montant'] . "\n\n";

echo "Payment Details:\n";
foreach ($result['payment_details'] as $i => $detail) {
    echo "Arrêt #" . ($i + 1) . ":\n";
    echo "  date-effet: " . ($detail['date-effet'] ?? 'NOT SET') . "\n";
    echo "  decompte_days: " . ($detail['decompte_days'] ?? 'NOT SET') . "\n";
    echo "  payment_start: " . ($detail['payment_start'] ?? 'NOT SET') . "\n";
    echo "  nb_jours: " . ($detail['nb_jours'] ?? 'NOT SET') . "\n";
    echo "\n";
}
