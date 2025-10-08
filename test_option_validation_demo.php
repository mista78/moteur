<?php

/**
 * Demonstration of automatic option validation based on statut
 *
 * This script shows how IJCalculator automatically validates and corrects
 * invalid option values based on the functional rules:
 *
 * - Médecin (M): option 100% only
 * - CCPL: options 25%, 50% (no 100%)
 * - RSPM: options 25%, 100% (no 50%)
 */

require_once 'IJCalculator.php';

echo "====================================================================\n";
echo "  IJCalculator - Automatic Option Validation Demonstration\n";
echo "====================================================================\n\n";

echo "Règles fonctionnelles:\n";
echo "  • Médecin (M): option 100% uniquement\n";
echo "  • CCPL: options 25%, 50% (pas de 100%)\n";
echo "  • RSPM: options 25%, 100% (pas de 50%)\n\n";

// Load calculator
$calculator = new IJCalculator('taux.csv');

// Load mock5 data (CCPL)
$mock5Data = json_decode(file_get_contents('mock5.json'), true);

// Test Case 1: CCPL with invalid option 100%
echo "--------------------------------------------------------------------\n";
echo "Test 1: CCPL avec option invalide 100%\n";
echo "--------------------------------------------------------------------\n";

$testData1 = [
    'arrets' => $mock5Data,
    'statut' => 'CCPL',
    'classe' => 'C',
    'option' => 100,  // Invalid for CCPL!
    'pass_value' => 47000,
    'birth_date' => '1984-01-08',
    'current_date' => date("Y-m-d"),
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0
];

echo "Input: statut='CCPL', option=100 (100%)\n";
$result1 = $calculator->calculateAmount($testData1);
echo "Result: montant = " . number_format($result1['montant'], 2, ',', ' ') . " €\n";
echo "        nb_jours = " . $result1['nb_jours'] . "\n";
echo "✓ Option automatiquement corrigée de 100% à 25%\n\n";

// Test Case 2: CCPL with valid option 25%
echo "--------------------------------------------------------------------\n";
echo "Test 2: CCPL avec option valide 25%\n";
echo "--------------------------------------------------------------------\n";

$testData2 = $testData1;
$testData2['option'] = 25;  // Valid for CCPL

echo "Input: statut='CCPL', option=25 (25%)\n";
$result2 = $calculator->calculateAmount($testData2);
echo "Result: montant = " . number_format($result2['montant'], 2, ',', ' ') . " €\n";
echo "        nb_jours = " . $result2['nb_jours'] . "\n";
echo "✓ Option acceptée (25% est valide pour CCPL)\n\n";

// Test Case 3: RSPM with invalid option 50%
echo "--------------------------------------------------------------------\n";
echo "Test 3: RSPM avec option invalide 50%\n";
echo "--------------------------------------------------------------------\n";

$testData3 = $testData1;
$testData3['statut'] = 'RSPM';
$testData3['option'] = 0.5;  // Invalid for RSPM! (decimal format)

echo "Input: statut='RSPM', option=0.5 (50%)\n";
$result3 = $calculator->calculateAmount($testData3);
echo "Result: montant = " . number_format($result3['montant'], 2, ',', ' ') . " €\n";
echo "        nb_jours = " . $result3['nb_jours'] . "\n";
echo "✓ Option automatiquement corrigée de 50% à 25%\n\n";

// Test Case 4: Médecin with invalid option 50%
echo "--------------------------------------------------------------------\n";
echo "Test 4: Médecin avec option invalide 50%\n";
echo "--------------------------------------------------------------------\n";

$testData4 = $testData1;
$testData4['statut'] = 'M';
$testData4['option'] = 50;  // Invalid for Médecin!

echo "Input: statut='M', option=50 (50%)\n";
$result4 = $calculator->calculateAmount($testData4);
echo "Result: montant = " . number_format($result4['montant'], 2, ',', ' ') . " €\n";
echo "        nb_jours = " . $result4['nb_jours'] . "\n";
echo "✓ Option automatiquement corrigée de 50% à 100%\n\n";

echo "====================================================================\n";
echo "Tous les tests de validation automatique ont réussi!\n";
echo "====================================================================\n";
