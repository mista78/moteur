<?php

echo "=== Date Counting Analysis ===\n\n";

// MOCK2 Arrêt 5
echo "MOCK2 Arrêt 5:\n";
echo "Date effet: 2023-12-07\n";
echo "Arrêt end: 2024-03-31\n\n";

$start = new DateTime('2023-12-07');
$end2023 = new DateTime('2023-12-31');
$start2024 = new DateTime('2024-01-01');
$end2024 = new DateTime('2024-03-31');

echo "Method 1: diff()->days + 1\n";
$days2023_m1 = $start->diff($end2023)->days + 1;
$days2024_m1 = $start2024->diff($end2024)->days + 1;
echo "  2023-12-07 to 2023-12-31: $days2023_m1 days\n";
echo "  2024-01-01 to 2024-03-31: $days2024_m1 days\n";
echo "  Total: " . ($days2023_m1 + $days2024_m1) . " days\n\n";

// The PHP diff counts
echo "Detailed count 2023:\n";
$current = new DateTime('2023-12-07');
$count = 0;
while ($current <= $end2023) {
    $count++;
    $current->modify('+1 day');
}
echo "  Counting from 2023-12-07 to 2023-12-31: $count days\n";

echo "Detailed count 2024:\n";
$current = new DateTime('2024-01-01');
$count = 0;
while ($current <= $end2024) {
    $count++;
    $current->modify('+1 day');
}
echo "  Counting from 2024-01-01 to 2024-03-31: $count days\n\n";

// What if the VBA code splits differently?
echo "Alternative: What if payment starts 2023-12-10 (3 days later)?\n";
$alt_start = new DateTime('2023-12-10');
$alt_days2023 = $alt_start->diff($end2023)->days + 1;
echo "  2023-12-10 to 2023-12-31: $alt_days2023 days\n";
echo "  Total with 2024: " . ($alt_days2023 + $days2024_m1) . " days\n\n";

echo "Alternative: What if payment ends 2024-03-28 (3 days earlier)?\n";
$alt_end = new DateTime('2024-03-28');
$alt_days2024 = $start2024->diff($alt_end)->days + 1;
echo "  2024-01-01 to 2024-03-28: $alt_days2024 days\n";
echo "  Total with 2023: " . ($days2023_m1 + $alt_days2024) . " days\n\n";

// Check the exact dates in the mock data
echo "Checking mock data date_deb_droit vs arret dates:\n";
$mock2 = json_decode(file_get_contents('mock2.json'), true);
$arret5 = $mock2[5];
echo "date_deb_droit: {$arret5['date_deb_droit']}\n";
echo "arret-from-line: {$arret5['arret-from-line']}\n";
echo "arret-to-line: {$arret5['arret-to-line']}\n";
echo "attestation-date-line: {$arret5['attestation-date-line']}\n\n";

// The payment starts from date_deb_droit (2023-12-07), not arret-from-line (2023-11-23)
// So the actual payment period is 2023-12-07 to 2024-03-31

// But wait - maybe the attestation date matters?
echo "Maybe the payment end is limited by something?\n";
echo "If arrêt ends 2024-03-31 but we're only paying up to some earlier date...\n\n";

// MOCK4
echo "\n=== MOCK4 Arrêt 0 ===\n";
echo "Date effet: 2023-09-24\n";
echo "Arrêt start: 2023-06-26\n";
echo "Arrêt end: 2023-09-24\n\n";

$mock4 = json_decode(file_get_contents('mock4.json'), true);
$arret0 = $mock4[0];
echo "date_deb_droit: {$arret0['date_deb_droit']}\n";
echo "arret-from-line: {$arret0['arret-from-line']}\n";
echo "arret-to-line: {$arret0['arret-to-line']}\n";
echo "rechute-line: {$arret0['rechute-line']}\n\n";

// date_deb_droit = arret-to-line = 2023-09-24
// So payment is from 2023-09-24 to 2023-09-24 = 1 day
echo "Payment from 2023-09-24 to 2023-09-24 = 1 day\n";
echo "But expected suggests 0 days?\n\n";

echo "Checking if it's a rechute payment rule:\n";
echo "rechute-line: {$arret0['rechute-line']}\n";
echo "Maybe when date_deb_droit = arret-to-line, it means no payment?\n";
echo "Or payment starts the day AFTER date_deb_droit?\n";
