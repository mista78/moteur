<?php

/**
 * Test to verify the date effet bug fix
 *
 * Bug: When first arret has date_deb_droit, it didn't increment $arretDroits,
 * causing subsequent rechute arrets to calculate date effet incorrectly.
 *
 * Expected behavior:
 * - Arret 1: Use date_deb_droit = 2024-01-22
 * - Arret 2 (rechute=15): Date effet should be >= arret start (2024-09-02)
 */

require_once 'IJCalculator.php';

echo "=== Test Date Effet Fix ===\n\n";

$arrets = json_decode(file_get_contents('mock.json'), true);

$testData = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1989-09-26',
    'current_date' => '2024-09-09',
    'attestation_date' => '2024-01-31',
    'affiliation_date' => null,
    'nb_trimestres' => 8,
    'patho_anterior' => 0,
    'prorata' => 1,
    'pass_value' => 47000,
    'adherent_number' => $arrets[0]['adherent_number'],
    'num_sinistre' => $arrets[0]['num_sinistre'],
    'arrets' => $arrets
];

$calculator = new IJCalculator('taux.csv');
$result = $calculator->calculateAmount($testData);

echo "Arret 1:\n";
echo "  Start: " . $arrets[0]['arret-from-line'] . "\n";
echo "  Date effet: " . ($result['payment_details'][0]['date_effet'] ?? 'EMPTY') . "\n";
echo "  ✓ Should use date_deb_droit from data\n\n";

echo "Arret 2 (rechute):\n";
echo "  Start: " . $arrets[1]['arret-from-line'] . "\n";
echo "  Date effet: " . ($result['payment_details'][1]['date_effet'] ?? 'EMPTY') . "\n";

$arret2_start = new DateTime($arrets[1]['arret-from-line']);
$arret2_effet = isset($result['payment_details'][1]['date_effet']) && $result['payment_details'][1]['date_effet']
    ? new DateTime($result['payment_details'][1]['date_effet'])
    : null;

if ($arret2_effet && $arret2_effet >= $arret2_start) {
    echo "  ✅ PASS: Date effet (" . $arret2_effet->format('Y-m-d') . ") is >= arret start (" . $arret2_start->format('Y-m-d') . ")\n";
} else {
    echo "  ❌ FAIL: Date effet is before arret start!\n";
    if ($arret2_effet) {
        echo "     Date effet: " . $arret2_effet->format('Y-m-d') . "\n";
        echo "     Arret start: " . $arret2_start->format('Y-m-d') . "\n";
    }
}

echo "\n";
echo "Total calculation:\n";
echo "  Montant: " . number_format($result['montant'], 2) . "€\n";
echo "  Jours: " . $result['nb_jours'] . "\n";
echo "\n";

// Expected: 750.60€ according to test_mocks.php
$expected = 750.6;
$actual = $result['montant'];
$match = abs($actual - $expected) < 0.01;

if ($match) {
    echo "✅ Amount matches expected: " . number_format($expected, 2) . "€\n";
} else {
    echo "⚠️  Amount difference: Expected " . number_format($expected, 2) . "€, got " . number_format($actual, 2) . "€\n";
}

echo "\n=== Test Completed ===\n";
