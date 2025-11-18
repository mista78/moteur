<?php
/**
 * Test the exact scenario from the error - Direct test without server
 */

require_once 'IJCalculator.php';
require_once 'Services/DateNormalizer.php';

use App\IJCalculator\IJCalculator;
use App\IJCalculator\Services\DateNormalizer;

echo "=== Testing Date Bug Fix ===\n\n";
echo "Simulating the exact data from your error message...\n\n";

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

// Exact data from error message
$inputData = [
    'statut' => 'M',
    'classe' => 'B',
    'option' => '1',
    'birth_date' => '1962-05-01',
    'current_date' => '2025-11-18',
    'attestation_date' => '2024-12-27',
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 50,
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'forced_rate' => null,
    'pass_value' => 47000,
    'arrets' => [
        [
            'arret-from-line' => '2023-09-04',
            'arret-to-line' => '2023-11-10',
            'rechute-line' => 0,
            'dt-line' => 0,
            'gpm-member-line' => 0,
        ],
        [
            'arret-from-line' => '2024-03-06',
            'arret-to-line' => '2025-01-03',
            'rechute-line' => 0,
            'dt-line' => 0,
            'gpm-member-line' => 0,
        ]
    ]
];

try {
    echo "Step 1: Normalizing dates...\n";
    $normalized = DateNormalizer::normalize($inputData);
    echo "  ✓ Dates normalized successfully\n\n";

    echo "Step 2: Creating calculator...\n";
    $calculator = new IJCalculator($rates);
    $calculator->setPassValue(47000);
    echo "  ✓ Calculator created\n\n";

    echo "Step 3: Running calculation (this previously failed with 'Call to format() on string')...\n";
    $result = $calculator->calculateAmount($normalized);
    echo "  ✓ Calculation completed successfully!\n\n";

    echo "=== RESULTS ===\n";
    echo "Montant total: " . $result['montant'] . "€\n";
    echo "Jours payables: " . $result['nb_jours'] . " jours\n";
    echo "Age: " . $result['age'] . " ans\n";
    echo "Trimestres: " . $result['nb_trimestres'] . "\n\n";

    if (isset($result['payment_details'])) {
        echo "Payment Details:\n";
        foreach ($result['payment_details'] as $i => $detail) {
            echo "\n  Arrêt #" . ($i + 1) . ":\n";
            echo "    Période: " . $detail['arret_from'] . " → " . $detail['arret_to'] . "\n";
            echo "    Date effet: " . ($detail['date_effet'] ?: 'N/A') . "\n";
            echo "    Jours payables: " . $detail['payable_days'] . "\n";
            echo "    Décompte: " . $detail['decompte_days'] . " jours\n";
            echo "    Is rechute: " . ($detail['is_rechute'] ? 'Oui' : 'Non') . "\n";
            echo "    Montant: " . ($detail['montant'] ?? 0) . "€\n";
        }
    }

    echo "\n";
    echo "========================================\n";
    echo "✅ THE BUG IS FIXED!\n";
    echo "========================================\n";
    echo "\n";
    echo "What was fixed:\n";
    echo "- DateNormalizer handles all date formats\n";
    echo "- RateService now works with both DateTime objects and strings\n";
    echo "- API automatically normalizes all incoming dates\n";
    echo "- Rechute indicator added to payment_details\n";
    echo "\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
