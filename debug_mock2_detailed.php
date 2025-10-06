<?php

require_once 'IJCalculator.php';

echo "=== MOCK2 DETAILED ANALYSIS ===\n\n";

$mock2 = json_decode(file_get_contents('mock2.json'), true);

echo "Mock2 data:\n";
print_r($mock2);

$calculator = new IJCalculator('taux.csv');

$params = [
    'arrets' => $mock2,
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1989-09-26',
    'current_date' => '2024-09-09',
    'attestation_date' => '2023-03-14',  // GLOBAL attestation
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0
];

echo "\nArrêt details:\n";
echo "  From: 2021-07-19\n";
echo "  To: 2021-08-30\n";
echo "  Duration: 43 days\n";
echo "  Attestation (arrêt-specific): 2021-08-30\n";
echo "\nGlobal attestation: 2023-03-14\n";
echo "Current date: 2024-09-09\n\n";

echo "PROBLEM: Arrêt is only 43 days (< 90), so no date-effet\n";
echo "But test expects 17318.92€ which suggests ~230 days of payment (17318.92 / 75€)\n\n";

echo "HYPOTHESIS: Maybe the arrêt is a RECHUTE of a previous sinister?\n";
echo "And the global attestation (2023-03-14) extends payment beyond the arrêt end date?\n\n";

// Test with previous_cumul_days = 50 to trigger rights immediately
echo "=== TEST 1: With previous_cumul_days = 50 ===\n";
$params['previous_cumul_days'] = 50;
$result1 = $calculator->calculateAmount($params);
echo "Result: {$result1['montant']} € for {$result1['nb_jours']} days\n\n";

// Test with rechute flag
echo "=== TEST 2: Marking as rechute ===\n";
$mock2_rechute = $mock2;
$mock2_rechute[0]['rechute-line'] = 1;
$params['arrets'] = $mock2_rechute;
$params['previous_cumul_days'] = 100;  // Already past 90-day threshold
$result2 = $calculator->calculateAmount($params);
echo "Result: {$result2['montant']} € for {$result2['nb_jours']} days\n\n";

// Maybe the problem is the attestation extends payment beyond the arrêt?
echo "=== THEORY: Payment period calculation ===\n";
echo "If attestation_date (2023-03-14) is AFTER arrêt end (2021-08-30),\n";
echo "maybe the system should pay from arrêt start to attestation date?\n\n";

$start = new DateTime('2021-07-19');
$attest = new DateTime('2023-03-14');
$days_to_attest = $start->diff($attest)->days + 1;
echo "Days from arrêt start to attestation: $days_to_attest days\n";
echo "Amount at 75€/day: " . ($days_to_attest * 75) . " €\n\n";

// But that's way more than expected (17318.92)
// Let's check if it's from date-effet to attestation

$days_needed = ceil(17318.92 / 75);
echo "Days needed for 17318.92€ at 75€/day: $days_needed days\n\n";

// Try with custom date-effet-forced
echo "=== TEST 3: Force date-effet at arrêt start ===\n";
$mock2_forced = $mock2;
$mock2_forced[0]['date-effet-forced'] = '2021-07-19';  // Rights from day 1
$params['arrets'] = $mock2_forced;
$params['previous_cumul_days'] = 0;
$params['attestation_date'] = '2023-03-14';
$result3 = $calculator->calculateAmount($params);
echo "Result: {$result3['montant']} € for {$result3['nb_jours']} days\n";

if (isset($result3['payment_details']) && is_array($result3['payment_details'])) {
    foreach ($result3['payment_details'] as $pd) {
        if ($pd['payable_days'] > 0) {
            echo "  Payment: {$pd['payment_start']} to {$pd['payment_end']} = {$pd['payable_days']} days\n";
        }
    }
}
