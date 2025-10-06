<?php
require_once 'IJCalculator.php';

// Mock data
$mockData = [
    [
        "code-patho-line" => "2",
        "arret-from-line" => "2023-09-04",
        "arret-to-line" => "2023-11-10",
        "rechute-line" => "0",
        "dt-line" => "1",
        "gpm-member-line" => "1",
        "declaration-date-line" => "2023-09-19"
    ],
    [
        "code-patho-line" => "2",
        "arret-from-line" => "2024-03-06",
        "arret-to-line" => "2025-01-03",
        "rechute-line" => "0",
        "dt-line" => "1",
        "gpm-member-line" => "1",
        "declaration-date-line" => "2024-04-04"
    ]
];

echo "=== DEBUG: Step by step ===\n\n";

$cumulDays = 0;

// Arrêt 1
$start1 = new DateTime($mockData[0]['arret-from-line']);
$end1 = new DateTime($mockData[0]['arret-to-line']);
$duration1 = $start1->diff($end1)->days + 1;
$cumulDays += $duration1;

echo "Arrêt 1:\n";
echo "  Durée: $duration1 jours\n";
echo "  Cumul: $cumulDays jours\n";
echo "  90 jours atteints? " . ($cumulDays > 90 ? "OUI" : "NON") . "\n\n";

// Arrêt 2
$start2 = new DateTime($mockData[1]['arret-from-line']);
$end2 = new DateTime($mockData[1]['arret-to-line']);
$duration2 = $start2->diff($end2)->days + 1;
$cumulDays += $duration2;

echo "Arrêt 2:\n";
echo "  Durée: $duration2 jours\n";
echo "  Cumul: $cumulDays jours\n";
echo "  90 jours atteints? " . ($cumulDays > 90 ? "OUI" : "NON") . "\n\n";

$daysNeeded = 90 - ($cumulDays - $duration2);
echo "Jours nécessaires dans arrêt 2 pour atteindre 90: $daysNeeded\n";

$dateEffet = clone $start2;
$dateEffet->modify("+$daysNeeded days");
echo "Date d'effet calculée (sans ajustements): " . $dateEffet->format('Y-m-d') . "\n";
echo "Date d'effet attendue: 2024-03-28\n\n";

// Now test with calculator
$calculator = new IJCalculator('taux.csv');
$result = $calculator->calculateDateEffet($mockData, null, 0);

echo "=== Result from calculator ===\n";
echo "Arrêt 2 date d'effet: " . ($result[1]['date-effet'] ?? 'N/A') . "\n";
