<?php

echo "=== Testing payment up to ATTESTATION date ===\n\n";

$mock2 = json_decode(file_get_contents('mock2.json'), true);

echo "Rule: Payment is from date_deb_droit to min(arret-to-line, attestation-date-line)\n";
echo "When start == end, use exclusive start (0 days)\n\n";

// Arrêt 3
$a3_start = new DateTime($mock2[3]['date_deb_droit']); // 2022-12-06
$a3_end_arret = new DateTime($mock2[3]['arret-to-line']); // 2022-12-24
$a3_end_att = new DateTime($mock2[3]['attestation-date-line']); // 2022-12-06
$a3_end = min($a3_end_arret, $a3_end_att);
$a3_days = ($a3_start <= $a3_end) ? ($a3_start->diff($a3_end)->days + 1) : 0;
// But since start == end (both 2022-12-06), use exclusive rule
if ($a3_start == $a3_end) {
    $a3_days = 0; // Exclusive when same
}
echo "Arrêt 3:\n";
echo "  Payment: {$a3_start->format('Y-m-d')} to {$a3_end->format('Y-m-d')}\n";
echo "  Days: $a3_days (start == end, so 0)\n\n";

// Arrêt 4
$a4_start = new DateTime($mock2[4]['date_deb_droit']); // 2023-10-10
$a4_end_arret = new DateTime($mock2[4]['arret-to-line']); // 2023-10-10
$a4_end_att = new DateTime($mock2[4]['attestation-date-line']); // 2023-10-10
$a4_end = min($a4_end_arret, $a4_end_att);
$a4_days = ($a4_start <= $a4_end) ? ($a4_start->diff($a4_end)->days + 1) : 0;
if ($a4_start == $a4_end) {
    $a4_days = 0; // Exclusive when same
}
echo "Arrêt 4:\n";
echo "  Payment: {$a4_start->format('Y-m-d')} to {$a4_end->format('Y-m-d')}\n";
echo "  Days: $a4_days (start == end, so 0)\n\n";

// Arrêt 5
$a5_start = new DateTime($mock2[5]['date_deb_droit']); // 2023-12-07
$a5_end_arret = new DateTime($mock2[5]['arret-to-line']); // 2024-03-31
$a5_end_att = new DateTime($mock2[5]['attestation-date-line']); // 2024-06-12
$a5_end = min($a5_end_arret, $a5_end_att);
$year_end = new DateTime('2023-12-31');
$year_start = new DateTime('2024-01-01');

$a5_days_2023 = $a5_start->diff($year_end)->days + 1;
$a5_days_2024 = $year_start->diff($a5_end)->days + 1;
echo "Arrêt 5:\n";
echo "  Payment: {$a5_start->format('Y-m-d')} to {$a5_end->format('Y-m-d')}\n";
echo "  Days 2023: $a5_days_2023\n";
echo "  Days 2024: $a5_days_2024\n";
echo "  Total: " . ($a5_days_2023 + $a5_days_2024) . "\n\n";

$amt3 = $a3_days * 142.84;
$amt4 = $a4_days * 146.32;
$amt5_2023 = $a5_days_2023 * 146.32;
$amt5_2024 = $a5_days_2024 * 150.12;
$total = $amt3 + $amt4 + $amt5_2023 + $amt5_2024;

echo "Calculation:\n";
echo "Arrêt 3: $a3_days * 142.84 = $amt3\n";
echo "Arrêt 4: $a4_days * 146.32 = $amt4\n";
echo "Arrêt 5 (2023): $a5_days_2023 * 146.32 = $amt5_2023\n";
echo "Arrêt 5 (2024): $a5_days_2024 * 150.12 = $amt5_2024\n";
echo "Total: $total\n";
echo "Expected: 17318.92\n";
echo "Difference: " . abs($total - 17318.92) . "\n\n";

// Wait, still not matching. Let me check if attestation date >= 27 extends to end of month
echo "Checking if attestation date >= 27 extends payment:\n";
foreach ([3, 4, 5] as $i) {
    $att_day = (int)(new DateTime($mock2[$i]['attestation-date-line']))->format('d');
    echo "Arrêt $i: attestation day = $att_day\n";
    if ($att_day >= 27) {
        echo "  -> Extends to end of month\n";
    }
}

echo "\n\nLet me try with NO exclusive rule, but using attestation limit:\n";

// Try again without exclusive
$a3_days_v2 = 1; // 2022-12-06 to 2022-12-06 = 1 day inclusive
$a4_days_v2 = 1; // 2023-10-10 to 2023-10-10 = 1 day inclusive
$a5_days_2023_v2 = $a5_days_2023; // Same
$a5_days_2024_v2 = $a5_days_2024; // Same

$amt3_v2 = $a3_days_v2 * 142.84;
$amt4_v2 = $a4_days_v2 * 146.32;
$amt5_2023_v2 = $a5_days_2023_v2 * 146.32;
$amt5_2024_v2 = $a5_days_2024_v2 * 150.12;
$total_v2 = $amt3_v2 + $amt4_v2 + $amt5_2023_v2 + $amt5_2024_v2;

echo "With inclusive (no exclusive rule):\n";
echo "Arrêt 3: $a3_days_v2 * 142.84 = $amt3_v2\n";
echo "Arrêt 4: $a4_days_v2 * 146.32 = $amt4_v2\n";
echo "Arrêt 5 (2023): $a5_days_2023_v2 * 146.32 = $amt5_2023_v2\n";
echo "Arrêt 5 (2024): $a5_days_2024_v2 * 150.12 = $amt5_2024_v2\n";
echo "Total: $total_v2\n";
echo "Expected: 17318.92\n";
echo "Difference: " . abs($total_v2 - 17318.92) . "\n";
echo "This is exactly: 17608.08 (what the system calculated!)\n\n";

echo "So the issue is: system is paying 2 extra days (arrêts 3 and 4)\n";
echo "Expected behavior: when date_deb_droit == attestation-date-line, pay 0 days\n";
