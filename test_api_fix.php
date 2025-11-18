<?php
/**
 * Test the exact scenario from the error
 */

// Simulate API POST data (like from Make.com)
$jsonInput = json_encode([
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
]);

// Simulate API call
$ch = curl_init('http://localhost:8000/api.php?endpoint=calculate');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonInput);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

echo "Testing API with the exact data from your error...\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $result = json_decode($response, true);

    if ($result['success']) {
        echo "✅ SUCCESS! API call completed without errors\n\n";
        echo "Results:\n";
        echo "- Montant: " . $result['data']['montant'] . "€\n";
        echo "- Jours payables: " . $result['data']['nb_jours'] . " jours\n";
        echo "- Age: " . $result['data']['age'] . " ans\n";

        if (isset($result['data']['payment_details'])) {
            echo "\nPayment Details:\n";
            foreach ($result['data']['payment_details'] as $i => $detail) {
                echo "  Arret " . ($i + 1) . ":\n";
                echo "    - From: " . $detail['arret_from'] . " to " . $detail['arret_to'] . "\n";
                echo "    - Payable days: " . $detail['payable_days'] . "\n";
                echo "    - Is rechute: " . ($detail['is_rechute'] ? 'Yes' : 'No') . "\n";
            }
        }

        echo "\n✅ The date parsing bug is FIXED!\n";
    } else {
        echo "❌ API returned error: " . $result['error'] . "\n";
    }
} else {
    echo "❌ HTTP Error: " . $httpCode . "\n";
    echo "Response: " . $response . "\n";
}
