<?php

/**
 * Test API with mock2 data
 */

require_once 'IJCalculator.php';

echo "\n=== Test API Calculation (simulating web interface) ===\n\n";

// Simulate what the web interface sends to api.php
$mockData = json_decode(file_get_contents('mock2.json'), true);

// This is what the web interface sends
$postData = [
    'statut' => 'M',
    'classe' => 'C',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1958-06-03',
    'current_date' => date('Y-m-d'),  // Today
    'attestation_date' => '2024-06-12',
    'last_payment_date' => '',
    'affiliation_date' => '1991-07-01',
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0,
    'arrets' => $mockData
];

$calculator = new IJCalculator('taux.csv');
$result = $calculator->calculateAmount($postData);

echo "Status: SUCCESS\n\n";
echo "Expected: 17,318.92€ (116 jours)\n";
echo "Actual:   " . number_format($result['montant'], 2) . "€ (" . $result['nb_jours'] . " jours)\n";
echo "Match:    " . ($result['montant'] == 17318.92 ? "✅ YES" : "❌ NO") . "\n\n";

echo "If web interface shows different values:\n";
echo "1. Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)\n";
echo "2. Disable browser cache in DevTools (F12 > Network > Disable cache)\n";
echo "3. Check browser console for errors (F12 > Console)\n";
echo "4. Verify API URL is correct in app.js\n";
echo "5. Make sure PHP server is running with latest code\n";
