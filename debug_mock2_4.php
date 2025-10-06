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

echo "=== MOCK2 DEBUG ===\n\n";

$result = $calc->calculateAmount($requestData);

echo "Result: " . $result['montant'] . "\n";
echo "Expected: 17318.92\n";
echo "Difference: " . ($result['montant'] - 17318.92) . "\n\n";

// Check which arrêts have payments
echo "Payment details:\n";
foreach ($result['payment_details'] as $detail) {
    if ($detail['payable_days'] > 0) {
        echo "\nArrêt {$detail['arret_index']}: {$detail['payable_days']} days\n";
        echo "  From: {$detail['date_effet']} to {$detail['arret_to']}\n";
        echo "  Amount: {$detail['amount']}\n";
        if (isset($detail['daily_breakdown'])) {
            echo "  Daily breakdown:\n";
            foreach ($detail['daily_breakdown'] as $day) {
                echo "    {$day['date']}: {$day['rate']}€ (taux {$day['taux']}, tier {$day['tier']})\n";
            }
        }
    }
}

echo "\n\n=== MOCK4 DEBUG ===\n\n";

// Mock4 data
$mockData4 = json_decode(file_get_contents('mock4.json'), true);

$requestData4 = [
    'arrets' => $mockData4,
    'statut' => 'M',
    'classe' => 'C',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1958-12-21',
    'current_date' => '2024-09-29',
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0
];

$result4 = $calc->calculateAmount($requestData4);

echo "Result: " . $result4['montant'] . "\n";
echo "Expected: 37875.88\n";
echo "Difference: " . ($result4['montant'] - 37875.88) . "\n\n";

// Check which arrêts have payments
echo "Payment details:\n";
foreach ($result4['payment_details'] as $detail) {
    if ($detail['payable_days'] > 0) {
        echo "\nArrêt {$detail['arret_index']}: {$detail['payable_days']} days\n";
        echo "  From: {$detail['date_effet']} to {$detail['arret_to']}\n";
        echo "  Amount: {$detail['amount']}\n";
        if (isset($detail['daily_breakdown'])) {
            echo "  Daily breakdown (first 10):\n";
            $count = 0;
            foreach ($detail['daily_breakdown'] as $day) {
                echo "    {$day['date']}: {$day['rate']}€ (taux {$day['taux']}, tier {$day['tier']})\n";
                if (++$count >= 10) break;
            }
            if (count($detail['daily_breakdown']) > 10) {
                echo "    ... (" . (count($detail['daily_breakdown']) - 10) . " more days)\n";
            }
        }
    }
}
