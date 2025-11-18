<?php
/**
 * Test script to verify rechute indicator in payment_details
 */

require_once 'IJCalculator.php';

use App\IJCalculator\IJCalculator;

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

// Load mock2.json (has multiple arrets with rechute)
$mockData = json_decode(file_get_contents('mock2.json'), true);

// Prepare input data
$inputData = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 1,
    'birth_date' => '1958-06-03',
    'current_date' => '2024-01-15',
    'attestation_date' => '2024-01-31',
    'affiliation_date' => '2019-01-15',
    'nb_trimestres' => 22,
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000,
    'arrets' => $mockData
];

// Create calculator and run calculation
$calculator = new IJCalculator($rates);
$calculator->setPassValue(47000);

$result = $calculator->calculateAmount($inputData);

echo "=== Test Rechute Indicator in Payment Details ===\n\n";

// Check if payment_details have is_rechute field
if (isset($result['payment_details'])) {
    echo "Number of payment details: " . count($result['payment_details']) . "\n\n";

    foreach ($result['payment_details'] as $index => $detail) {
        echo "Payment Detail #" . ($index + 1) . ":\n";
        echo "  Arret: " . $detail['arret_from'] . " to " . $detail['arret_to'] . "\n";
        echo "  Date effet: " . ($detail['date_effet'] ?? 'N/A') . "\n";
        echo "  Payable days: " . $detail['payable_days'] . "\n";
        echo "  Decompte days: " . ($detail['decompte_days'] ?? 0) . "\n";

        // Check for is_rechute field
        if (isset($detail['is_rechute'])) {
            echo "  ✓ is_rechute: " . ($detail['is_rechute'] ? 'TRUE' : 'FALSE') . "\n";

            if ($detail['is_rechute'] && isset($detail['rechute_of_arret_index'])) {
                echo "  ✓ rechute_of_arret_index: " . $detail['rechute_of_arret_index'] . "\n";
            }
        } else {
            echo "  ✗ is_rechute field MISSING!\n";
        }

        echo "\n";
    }

    echo "=== Test Summary ===\n";
    $hasIsRechute = true;
    foreach ($result['payment_details'] as $detail) {
        if (!isset($detail['is_rechute'])) {
            $hasIsRechute = false;
            break;
        }
    }

    if ($hasIsRechute) {
        echo "✓ SUCCESS: All payment_details have is_rechute field\n";
    } else {
        echo "✗ FAILED: Some payment_details are missing is_rechute field\n";
    }
} else {
    echo "ERROR: No payment_details in result\n";
}

echo "\n";
