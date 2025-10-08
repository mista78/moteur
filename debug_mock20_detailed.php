<?php

require_once 'IJCalculator.php';

$calculator = new IJCalculator('taux.csv');
$mockData = json_decode(file_get_contents('mock20.json'), true);

$input = [
    'arrets' => $mockData,
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1981-03-15',
    'current_date' => date("Y-m-d"),
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => '2019-01-01',
    'nb_trimestres' => 23,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 1
];

$result = $calculator->calculateAmount($input);

echo "===MOCK20 DETAILED DEBUG===\n\n";
echo "Input:\n";
echo "  Affiliation: 2019-01-01\n";
echo "  First pathology: 2024-04-11\n";
echo "  Pathology anterior: YES\n";
echo "  Nb trimestres: 23\n";
echo "  Age: " . $result['age'] . "\n\n";

echo "Pathology Anterior Rules:\n";
echo "  < 8 trimestres: No payment\n";
echo "  8-15 trimestres: -1/3 reduction\n";
echo "  16-23 trimestres: -2/3 reduction  <-- SHOULD APPLY\n";
echo "  >= 24 trimestres: Full rate\n\n";

echo "Result:\n";
echo "  Expected: 8757€\n";
echo "  Got: {$result['montant']}€\n";
echo "  Payable days: {$result['nb_jours']}\n\n";

echo "Rate calculation should be:\n";
echo "  Taux A1 = 75.06€ (2024 rate for class A, tier 1)\n";
echo "  With -2/3 reduction: 75.06 / 3 = 25.02€/day\n";
echo "  For 175 days: 25.02 × 175 = 4378.5€\n\n";

echo "If we're getting 13135.5€:\n";
echo "  13135.5 / 175 = 75.06€/day <-- FULL RATE, NO REDUCTION!\n\n";

echo "This means the pathology anterior -2/3 reduction is NOT being applied!\n";
