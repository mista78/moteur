<?php
/**
 * Test: Rechute should only apply AFTER rights are opened (date-effet exists)
 */

require_once 'IJCalculator.php';

echo "===========================================\n";
echo "Test: Rechute after Rights Opening\n";
echo "===========================================\n\n";

$calculator = new IJCalculator('taux.csv');

// Scenario: Two arrêts, but first one doesn't reach 90-day threshold
$data = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1980-01-01',
    'current_date' => '2024-01-15',
    'attestation_date' => '2024-12-31',
    'last_payment_date' => null,
    'affiliation_date' => '2010-01-01',
    'nb_trimestres' => 56,
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000,
    'arrets' => [
        [
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-02-15',  // 46 days - not enough for date-effet
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2024-01-05'
        ],
        [
            'arret-from-line' => '2024-03-01',  // 14 days after first arrêt
            'arret-to-line' => '2024-04-30',    // 61 days
            'rechute-line' => null,  // Let system determine
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2024-03-05'
        ]
    ]
];

echo "Scenario 1: Two arrêts, BEFORE 90-day threshold\n";
echo "- First arrêt: 46 days (no date-effet yet)\n";
echo "- Second arrêt: 14 days after first\n";
echo "- Expected: Second arrêt is NOT rechute (accumulating to 90 days)\n\n";

$result = $calculator->calculateAmount($data);

echo "First arrêt:\n";
echo "  - Days: " . (isset($result['arrets'][0]['arret_diff']) ? $result['arrets'][0]['arret_diff'] : 'N/A') . "\n";
echo "  - Date effet: " . (isset($result['arrets'][0]['date-effet']) && !empty($result['arrets'][0]['date-effet']) ? $result['arrets'][0]['date-effet'] : 'NONE (not enough days)') . "\n\n";

echo "Second arrêt:\n";
echo "  - Days: " . (isset($result['arrets'][1]['arret_diff']) ? $result['arrets'][1]['arret_diff'] : 'N/A') . "\n";
echo "  - Date effet: " . (isset($result['arrets'][1]['date-effet']) && !empty($result['arrets'][1]['date-effet']) ? $result['arrets'][1]['date-effet'] : 'NONE') . "\n";
echo "  - Is Rechute (backend): " . (isset($result['arrets'][1]['is_rechute']) ? ($result['arrets'][1]['is_rechute'] ? 'YES' : 'NO') : 'Not set') . "\n";
echo "  - Type: " . ($result['arrets'][1]['is_rechute'] === true ? 'Rechute' : ($result['arrets'][1]['is_rechute'] === false ? 'Nouvelle pathologie' : 'Unknown')) . "\n\n";

echo "Total cumul days: " . $result['total_cumul_days'] . " (should be 107)\n";
echo "Payment starts: " . (isset($result['arrets'][1]['date-effet']) && !empty($result['arrets'][1]['date-effet']) ? 'Yes - 90-day threshold reached' : 'No') . "\n\n";

echo "-------------------------------------------\n\n";

// Scenario 2: With rights already opened
$data2 = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1980-01-01',
    'current_date' => '2024-06-30',
    'attestation_date' => '2024-12-31',
    'last_payment_date' => null,
    'affiliation_date' => '2010-01-01',
    'nb_trimestres' => 56,
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000,
    'arrets' => [
        [
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-04-10',  // 101 days - enough for date-effet
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2024-01-05'
        ],
        [
            'arret-from-line' => '2024-05-01',  // 21 days after first arrêt
            'arret-to-line' => '2024-06-30',    // 61 days
            'rechute-line' => null,  // Let system determine
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2024-05-05'
        ]
    ]
];

echo "Scenario 2: Two arrêts, AFTER 90-day threshold\n";
echo "- First arrêt: 101 days (has date-effet)\n";
echo "- Second arrêt: 21 days after first\n";
echo "- Expected: Second arrêt IS rechute (within 1 year, rights were opened)\n\n";

$result2 = $calculator->calculateAmount($data2);

echo "First arrêt:\n";
echo "  - Days: " . (isset($result2['arrets'][0]['arret_diff']) ? $result2['arrets'][0]['arret_diff'] : 'N/A') . "\n";
echo "  - Date effet: " . (isset($result2['arrets'][0]['date-effet']) ? $result2['arrets'][0]['date-effet'] : 'NONE') . "\n\n";

echo "Second arrêt:\n";
echo "  - Days: " . (isset($result2['arrets'][1]['arret_diff']) ? $result2['arrets'][1]['arret_diff'] : 'N/A') . "\n";
echo "  - Date effet: " . (isset($result2['arrets'][1]['date-effet']) ? $result2['arrets'][1]['date-effet'] : 'NONE') . "\n";
echo "  - Is Rechute (backend): " . (isset($result2['arrets'][1]['is_rechute']) ? ($result2['arrets'][1]['is_rechute'] ? 'YES' : 'NO') : 'Not set') . "\n";
echo "  - Type: " . ($result2['arrets'][1]['is_rechute'] === true ? 'Rechute' : ($result2['arrets'][1]['is_rechute'] === false ? 'Nouvelle pathologie' : 'Unknown')) . "\n";

// Check if date-effet is at day 15 (rechute rule) or day 91 (new pathology rule)
if (isset($result2['arrets'][1]['date-effet']) && !empty($result2['arrets'][1]['date-effet'])) {
    $arretStart = new DateTime($result2['arrets'][1]['arret-from-line']);
    $dateEffet = new DateTime($result2['arrets'][1]['date-effet']);
    $daysDiff = $arretStart->diff($dateEffet)->days;
    echo "  - Days until payment: " . $daysDiff . " days (15 = rechute, 90+ = new pathology)\n";
}

echo "\n===========================================\n";
echo "✓ Test Complete\n";
echo "===========================================\n";
