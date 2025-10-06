<?php

require_once 'IJCalculator.php';

echo "=== HYPOTHESIS: Tests need previous_cumul_days >= 90 ===\n\n";

$calculator = new IJCalculator('taux.csv');

// Test mock6 with previous_cumul_days = 100 (so this arrêt has immediate rights)
echo "TEST: mock6 with previous_cumul_days = 100\n";
echo str_repeat("-", 60) . "\n";

$mock6 = json_decode(file_get_contents('mock6.json'), true);

$result = $calculator->calculateAmount([
    'arrets' => $mock6,
    'statut' => 'M',
    'classe' => 'B',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1962-05-01',
    'current_date' => '2024-12-27',
    'attestation_date' => null,  // Use arrêt-specific attestation
    'last_payment_date' => '2023-11-10',  // Last payment was at end of this arrêt
    'affiliation_date' => null,
    'nb_trimestres' => 50,
    'previous_cumul_days' => 100,  // Already paid 100 days before
    'prorata' => 1,
    'patho_anterior' => 0
]);

echo "Expected: 31412.61 €\n";
echo "Got: " . $result['montant'] . " €\n";
echo "Days: " . $result['nb_jours'] . "\n\n";

// The hypothesis: maybe the attestation date is for the NEXT payment period
// not for this arrêt
echo "TEST: mock6 - payment from last_payment_date to attestation date\n";
echo str_repeat("-", 60) . "\n";

$result2 = $calculator->calculateAmount([
    'arrets' => $mock6,
    'statut' => 'M',
    'classe' => 'B',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1962-05-01',
    'current_date' => '2024-12-27',
    'attestation_date' => '2024-12-27',  // Global attestation for payment period
    'last_payment_date' => '2023-11-10',  // Already paid until end of arrêt
    'affiliation_date' => null,
    'nb_trimestres' => 50,
    'previous_cumul_days' => 100,
    'prorata' => 1,
    'patho_anterior' => 0
]);

echo "Days calculated: " . $result2['nb_jours'] . "\n";
echo "Amount: " . $result2['montant'] . " €\n\n";

// Calculate how many days from 2023-11-10 to 2024-12-31
$start = new DateTime('2023-11-11'); // Day after last payment
$end = new DateTime('2024-12-31');   // End of attestation month
$days_expected = $start->diff($end)->days + 1;
echo "Days from 2023-11-11 to 2024-12-31: $days_expected days\n";
echo "Expected amount at ~112€/day: " . ($days_expected * 112) . " €\n\n";

// Now test mock2
echo "\n" . str_repeat("=", 60) . "\n";
echo "TEST: mock2 with previous_cumul_days\n";
echo str_repeat("-", 60) . "\n";

$mock2 = json_decode(file_get_contents('mock2.json'), true);

// mock2 arrêt: 2021-07-19 to 2021-08-30 (43 days)
// attestation: 2021-08-30
// If previous_cumul_days = 50, then rights start immediately
$result3 = $calculator->calculateAmount([
    'arrets' => $mock2,
    'statut' => 'M',
    'classe' => 'C',  // Class C in test_mocks.php for mock2
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1958-06-03',
    'current_date' => '2024-06-12',
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 8,
    'previous_cumul_days' => 50,  // Try with 50 previous days
    'prorata' => 1,
    'patho_anterior' => 0
]);

echo "Expected: 17318.92 €\n";
echo "Got: " . $result3['montant'] . " €\n";
echo "Days: " . $result3['nb_jours'] . "\n";
echo "Age: " . $result3['age'] . "\n\n";

if (isset($result3['payment_details']) && is_array($result3['payment_details'])) {
    foreach ($result3['payment_details'] as $pd) {
        if ($pd['payable_days'] > 0) {
            echo "  Payment: {$pd['payment_start']} to {$pd['payment_end']} = {$pd['payable_days']} days\n";
        }
    }
}
