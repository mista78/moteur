<?php

echo "=== Testing if VBA uses exclusive start date ===\n\n";

// MOCK2 - Test if date_effet is exclusive (payment starts day AFTER)
echo "MOCK2 with exclusive start:\n";
$arret3_start = new DateTime('2022-12-06');
$arret3_start->modify('+1 day'); // Start day AFTER date_effet
$arret3_end = new DateTime('2022-12-24');
$days3 = $arret3_start->diff($arret3_end)->days + 1;
echo "Arrêt 3: " . $arret3_start->format('Y-m-d') . " to " . $arret3_end->format('Y-m-d') . " = $days3 days (was 1, now " . ($days3 - 1) . " less)\n";

$arret4_start = new DateTime('2023-10-10');
$arret4_start->modify('+1 day');
$arret4_end = new DateTime('2023-10-10');
$days4 = 0; // Start after end, so 0 days
echo "Arrêt 4: " . $arret4_start->format('Y-m-d') . " to " . $arret4_end->format('Y-m-d') . " = $days4 days (was 1, now 0)\n";

$arret5_start = new DateTime('2023-12-07');
$arret5_start->modify('+1 day'); // 2023-12-08
$arret5_end = new DateTime('2024-03-31');
$year_end = new DateTime('2023-12-31');
$year_start = new DateTime('2024-01-01');

$days5_2023 = $arret5_start->diff($year_end)->days + 1;
$days5_2024 = $year_start->diff($arret5_end)->days + 1;
echo "Arrêt 5: " . $arret5_start->format('Y-m-d') . " to 2023-12-31 = $days5_2023 days (was 25)\n";
echo "       : 2024-01-01 to " . $arret5_end->format('Y-m-d') . " = $days5_2024 days (was 91)\n";
echo "       Total: " . ($days5_2023 + $days5_2024) . " days (was 116)\n\n";

// Calculate with exclusive start
$amt3 = 0 * 142.84;
$amt4 = 0 * 146.32;
$amt5_2023 = $days5_2023 * 146.32;
$amt5_2024 = $days5_2024 * 150.12;
$total = $amt3 + $amt4 + $amt5_2023 + $amt5_2024;

echo "Calculation with exclusive start:\n";
echo "Arrêt 3: $amt3\n";
echo "Arrêt 4: $amt4\n";
echo "Arrêt 5 (2023): $amt5_2023\n";
echo "Arrêt 5 (2024): $amt5_2024\n";
echo "Total: $total\n";
echo "Expected: 17318.92\n";
echo "Difference: " . abs($total - 17318.92) . "\n\n";

// Hmm, that's way off. Let me try a different approach.
// What if the issue is the end date, not the start?

echo "Testing with inclusive start, exclusive end:\n";
$arret3_days = 0; // 2022-12-06 to 2022-12-06 (exclusive end = same day = 0)
$arret4_days = 0; // 2023-10-10 to 2023-10-10 (exclusive end = 0)
$arret5_start = new DateTime('2023-12-07');
$arret5_end = new DateTime('2024-03-31');
$arret5_end->modify('-1 day'); // Exclusive end = 2024-03-30

$year_end = new DateTime('2023-12-31');
$year_start = new DateTime('2024-01-01');

$days5_2023 = $arret5_start->diff($year_end)->days + 1;
$days5_2024 = $year_start->diff($arret5_end)->days + 1;

echo "Arrêt 3: $arret3_days days\n";
echo "Arrêt 4: $arret4_days days\n";
echo "Arrêt 5 (2023): $days5_2023 days\n";
echo "Arrêt 5 (2024): $days5_2024 days (to 2024-03-30)\n";

$amt3 = $arret3_days * 142.84;
$amt4 = $arret4_days * 146.32;
$amt5_2023 = $days5_2023 * 146.32;
$amt5_2024 = $days5_2024 * 150.12;
$total = $amt3 + $amt4 + $amt5_2023 + $amt5_2024;

echo "\nTotal: $total\n";
echo "Expected: 17318.92\n";
echo "Difference: " . abs($total - 17318.92) . "\n\n";

// MOCK4 - Test similar
echo "\n=== MOCK4 with exclusive start ===\n";
$arret0_start = new DateTime('2023-09-24');
$arret0_start->modify('+1 day');
$arret0_end = new DateTime('2023-09-24');
echo "Arrêt 0: Start after end, 0 days (was 1)\n\n";

// This would make arrêt 0 = 0 days, which matches!
echo "If arrêt 0 pays 0 days (exclusive start), then:\n";
echo "  Mock4 total = 38022.20 - 146.32 = 37875.88 ✓\n";
