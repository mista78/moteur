<?php

echo "=== Testing conditional exclusive start ===\n";
echo "Rule: If date_effet == arret-to-line, pay 0 days. Otherwise, pay from date_effet (inclusive).\n\n";

$mock2 = json_decode(file_get_contents('mock2.json'), true);

echo "MOCK2:\n";
foreach ([3, 4, 5] as $i) {
    $arret = $mock2[$i];
    echo "\nArrêt $i:\n";
    echo "  date_deb_droit: {$arret['date_deb_droit']}\n";
    echo "  arret-to-line: {$arret['arret-to-line']}\n";
    echo "  Same? " . ($arret['date_deb_droit'] == $arret['arret-to-line'] ? 'YES' : 'NO') . "\n";
}

echo "\nApplying rule:\n";

// Arrêt 3: 2022-12-06 to 2022-12-24 (not same, so inclusive)
$arret3_start = new DateTime('2022-12-06');
$arret3_end = new DateTime('2022-12-24');
$days3 = $arret3_start->diff($arret3_end)->days + 1;
echo "Arrêt 3: $days3 days (inclusive, dates not same)\n";

// Arrêt 4: 2023-10-10 to 2023-10-10 (SAME, so 0 days)
$days4 = 0;
echo "Arrêt 4: $days4 days (dates are same, skip)\n";

// Arrêt 5: 2023-12-07 to 2024-03-31 (not same, so inclusive)
$arret5_start = new DateTime('2023-12-07');
$arret5_end = new DateTime('2024-03-31');
$year_end = new DateTime('2023-12-31');
$year_start = new DateTime('2024-01-01');

$days5_2023 = $arret5_start->diff($year_end)->days + 1;
$days5_2024 = $year_start->diff($arret5_end)->days + 1;
echo "Arrêt 5: $days5_2023 days (2023) + $days5_2024 days (2024) = " . ($days5_2023 + $days5_2024) . " days\n";

$amt3 = $days3 * 142.84;
$amt4 = $days4 * 146.32;
$amt5_2023 = $days5_2023 * 146.32;
$amt5_2024 = $days5_2024 * 150.12;
$total = $amt3 + $amt4 + $amt5_2023 + $amt5_2024;

echo "\nCalculation:\n";
echo "Arrêt 3: $days3 * 142.84 = $amt3\n";
echo "Arrêt 4: $days4 * 146.32 = $amt4\n";
echo "Arrêt 5 (2023): $days5_2023 * 146.32 = $amt5_2023\n";
echo "Arrêt 5 (2024): $days5_2024 * 150.12 = $amt5_2024\n";
echo "Total: $total\n";
echo "Expected: 17318.92\n";
echo "Difference: " . abs($total - 17318.92) . "\n\n";

// Still not matching. Let me try: exclusive start when it's on arret-to-line
echo "Alternative: Always use EXCLUSIVE start (day after date_effet):\n";

// But skip payment entirely if start > end
$arret3_start_ex = new DateTime('2022-12-07'); // Day after
$days3_ex = $arret3_start_ex <= $arret3_end ? ($arret3_start_ex->diff($arret3_end)->days + 1) : 0;

$arret4_start_ex = new DateTime('2023-10-11'); // Day after 2023-10-10
$arret4_end = new DateTime('2023-10-10');
$days4_ex = 0; // Start > end

$arret5_start_ex = new DateTime('2023-12-08'); // Day after
$days5_2023_ex = $arret5_start_ex->diff($year_end)->days + 1;
$days5_2024_ex = $year_start->diff($arret5_end)->days + 1;

echo "Arrêt 3: $days3_ex days (from 2022-12-07)\n";
echo "Arrêt 4: $days4_ex days (start > end)\n";
echo "Arrêt 5: $days5_2023_ex days (2023) + $days5_2024_ex days (2024)\n";

$amt3_ex = $days3_ex * 142.84;
$amt4_ex = $days4_ex * 146.32;
$amt5_2023_ex = $days5_2023_ex * 146.32;
$amt5_2024_ex = $days5_2024_ex * 150.12;
$total_ex = $amt3_ex + $amt4_ex + $amt5_2023_ex + $amt5_2024_ex;

echo "\nTotal: $total_ex\n";
echo "Expected: 17318.92\n";
echo "Difference: " . abs($total_ex - 17318.92) . "\n\n";

// Hmm wait, let me check if arret 3 should actually pay fewer days
// because the attestation date limits it
echo "Checking attestation dates:\n";
foreach ([3, 4, 5] as $i) {
    $arret = $mock2[$i];
    echo "\nArrêt $i:\n";
    echo "  date_deb_droit: {$arret['date_deb_droit']}\n";
    echo "  arret-to-line: {$arret['arret-to-line']}\n";
    echo "  attestation-date-line: {$arret['attestation-date-line']}\n";
}

echo "\n\nWait - let me check if payment only goes up to ATTESTATION date, not arret-to-line:\n";

// Arrêt 3: attestation 2022-12-06, same as date_deb_droit
$arret3_att = new DateTime($mock2[3]['attestation-date-line']);
echo "Arrêt 3: payment from {$mock2[3]['date_deb_droit']} to {$mock2[3]['attestation-date-line']}\n";
echo "  Same dates! So 1 day if inclusive, 0 if exclusive\n";

// Arrêt 4: attestation 2023-10-10, same as date_deb_droit
echo "Arrêt 4: payment from {$mock2[4]['date_deb_droit']} to {$mock2[4]['attestation-date-line']}\n";
echo "  Same dates! So 1 day if inclusive, 0 if exclusive\n";

// Arrêt 5: attestation 2024-06-12, but arret ends 2024-03-31
echo "Arrêt 5: payment from {$mock2[5]['date_deb_droit']} to min({$mock2[5]['arret-to-line']}, {$mock2[5]['attestation-date-line']})\n";
echo "  Payment to 2024-03-31 (arret end < attestation)\n";

echo "\n\nMaybe the rule is:\n";
echo "- Payment is from date_deb_droit to min(arret-to-line, attestation-date-line)\n";
echo "- If date_deb_droit == payment_end, then:\n";
echo "    - Pay 1 day if attestation >= day 27 (extends to end of month)?\n";
echo "    - Or pay 0 days?\n";
