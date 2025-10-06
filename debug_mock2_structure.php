<?php

require_once 'IJCalculator.php';

$calc = new IJCalculator('taux.csv');

// Mock2 data
$mockData = json_decode(file_get_contents('mock2.json'), true);

$requestData = [
    'arrets' => $mockData,
    'statut' => 'M',
    'classe' => 'c',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1958-06-03',
    'current_date' => '2024-06-12',
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0
];

$result = $calc->calculateAmount($requestData);

echo "Result: " . $result['montant'] . "\n";
echo "Expected: 17318.92\n";
echo "Difference: " . ($result['montant'] - 17318.92) . "\n\n";

echo "Full result structure:\n";
print_r($result);
