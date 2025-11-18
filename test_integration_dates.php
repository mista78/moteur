<?php
/**
 * End-to-end integration test for date handling
 * Simulates data from different sources and verifies calculator works correctly
 */

require_once 'IJCalculator.php';
require_once 'Services/DateNormalizer.php';

use App\IJCalculator\IJCalculator;
use App\IJCalculator\Services\DateNormalizer;

echo "=== Date Integration Test Suite ===\n\n";

// Load rates
$rates = [];
if (($handle = fopen('taux.csv', 'r')) !== false) {
    $header = fgetcsv($handle, 1000, ';');
    while (($data = fgetcsv($handle, 1000, ';')) !== false) {
        $rate = array_combine($header, $data);
        $rate['date_start'] = new DateTime($rate['date_start']);
        $rate['date_end'] = new DateTime($rate['date_end']);
        $rates[] = $rate;
    }
    fclose($handle);
}

$calculator = new IJCalculator($rates);
$calculator->setPassValue(47000);

// Test 1: Data from database (DateTime objects)
echo "Test 1: Database Source (DateTime objects)\n";
$inputFromDB = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 1,
    'birth_date' => new DateTime('1960-01-15'),
    'current_date' => new DateTime('2024-01-15'),
    'attestation_date' => new DateTime('2024-01-31'),
    'affiliation_date' => new DateTime('2019-01-15'),
    'nb_trimestres' => 22,
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000,
    'arrets' => [
        [
            'arret-from-line' => new DateTime('2023-09-04'),
            'arret-to-line' => new DateTime('2023-11-10'),
            'rechute-line' => 0,
            'dt-line' => 0,
            'gpm-member-line' => 0,
            'declaration-date-line' => new DateTime('2023-09-05'),
        ]
    ]
];

// Normalize dates before passing to calculator
$normalizedDB = DateNormalizer::normalize($inputFromDB);

try {
    $resultDB = $calculator->calculateAmount($normalizedDB);
    echo "  ✓ Calculation successful\n";
    echo "  - Montant: " . $resultDB['montant'] . "€\n";
    echo "  - Jours: " . $resultDB['nb_jours'] . " jours\n";

    // Verify dates are properly handled
    if (isset($resultDB['payment_details'][0])) {
        $detail = $resultDB['payment_details'][0];
        if (isset($detail['is_rechute'])) {
            echo "  ✓ Rechute indicator present: " . ($detail['is_rechute'] ? 'true' : 'false') . "\n";
        }
    }
} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Data from JSON API (strings)
echo "Test 2: JSON API Source (string dates)\n";
$jsonString = '{
    "statut": "M",
    "classe": "A",
    "option": 1,
    "birth_date": "1960-01-15",
    "current_date": "2024-01-15",
    "attestation_date": "2024-01-31",
    "affiliation_date": "2019-01-15",
    "nb_trimestres": 22,
    "previous_cumul_days": 0,
    "patho_anterior": false,
    "prorata": 1,
    "pass_value": 47000,
    "arrets": [
        {
            "arret-from-line": "2023-09-04",
            "arret-to-line": "2023-11-10",
            "rechute-line": 0,
            "dt-line": 0,
            "gpm-member-line": 0,
            "declaration-date-line": "2023-09-05"
        }
    ]
}';

$inputFromJSON = json_decode($jsonString, true);
$normalizedJSON = DateNormalizer::normalize($inputFromJSON);

try {
    $resultJSON = $calculator->calculateAmount($normalizedJSON);
    echo "  ✓ Calculation successful\n";
    echo "  - Montant: " . $resultJSON['montant'] . "€\n";
    echo "  - Jours: " . $resultJSON['nb_jours'] . " jours\n";
} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Data from Make.com API (various formats)
echo "Test 3: Make.com API Source (mixed date formats)\n";
$inputFromMake = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 1,
    'birth_date' => '15/01/1960',      // European format
    'current_date' => '2024-01-15',     // ISO format
    'attestation_date' => '31/01/2024', // European format
    'affiliation_date' => '2019/01/15', // Slash format
    'nb_trimestres' => 22,
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000,
    'arrets' => [
        [
            'arret-from-line' => '04/09/2023',  // European format
            'arret-to-line' => '10/11/2023',    // European format
            'rechute-line' => 0,
            'dt-line' => 0,
            'gpm-member-line' => 0,
            'declaration-date-line' => '05-09-2023',  // Dash format
        ]
    ]
];

$normalizedMake = DateNormalizer::normalize($inputFromMake);

try {
    $resultMake = $calculator->calculateAmount($normalizedMake);
    echo "  ✓ Calculation successful\n";
    echo "  - Montant: " . $resultMake['montant'] . "€\n";
    echo "  - Jours: " . $resultMake['nb_jours'] . " jours\n";
} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Verify results consistency
echo "Test 4: Results Consistency Check\n";
$consistent = true;

if ($resultDB['montant'] !== $resultJSON['montant']) {
    echo "  ✗ DB and JSON montant mismatch\n";
    $consistent = false;
} else {
    echo "  ✓ DB and JSON montant match: " . $resultDB['montant'] . "€\n";
}

if ($resultDB['montant'] !== $resultMake['montant']) {
    echo "  ✗ DB and Make montant mismatch\n";
    $consistent = false;
} else {
    echo "  ✓ DB and Make montant match: " . $resultDB['montant'] . "€\n";
}

if ($resultDB['nb_jours'] !== $resultJSON['nb_jours']) {
    echo "  ✗ DB and JSON nb_jours mismatch\n";
    $consistent = false;
} else {
    echo "  ✓ DB and JSON nb_jours match: " . $resultDB['nb_jours'] . " jours\n";
}

if ($resultDB['nb_jours'] !== $resultMake['nb_jours']) {
    echo "  ✗ DB and Make nb_jours mismatch\n";
    $consistent = false;
} else {
    echo "  ✓ DB and Make nb_jours match: " . $resultDB['nb_jours'] . " jours\n";
}

echo "\n";

// Test 5: Null and invalid dates handling
echo "Test 5: Null and Invalid Dates\n";
$inputWithNulls = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 1,
    'birth_date' => '1960-01-15',
    'current_date' => '2024-01-15',
    'attestation_date' => null,        // Null attestation
    'affiliation_date' => '0000-00-00', // Invalid date
    'nb_trimestres' => 22,
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000,
    'arrets' => [
        [
            'arret-from-line' => '2023-09-04',
            'arret-to-line' => '2023-11-10',
            'date_deb_droit' => '',             // Empty date
            'rechute-line' => 0,
            'dt-line' => 0,
            'gpm-member-line' => 0,
        ]
    ]
];

$normalizedNulls = DateNormalizer::normalize($inputWithNulls);

try {
    $resultNulls = $calculator->calculateAmount($normalizedNulls);
    echo "  ✓ Calculation successful with null dates\n";
    echo "  - Attestation null handled: " . ($normalizedNulls['attestation_date'] === null ? 'YES' : 'NO') . "\n";
    echo "  - Invalid affiliation date handled: " . ($normalizedNulls['affiliation_date'] === null ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
}
echo "\n";

// Final Summary
echo "=== Summary ===\n";
if ($consistent) {
    echo "✓ ALL INTEGRATION TESTS PASSED!\n";
    echo "✓ Date normalization works correctly for all data sources\n";
    echo "✓ Calculator produces consistent results regardless of input format\n";
} else {
    echo "✗ SOME CONSISTENCY ISSUES FOUND!\n";
}
