<?php

require_once 'IJCalculator.php';
require_once __DIR__ . '/Services/DateCalculationInterface.php';
require_once __DIR__ . '/Services/DateService.php';

use IJCalculator\Services\DateService;

$dateService = new DateService();

// From mock20.json
$affiliationDate = '2019-01-01';
$currentDate = date("Y-m-d");
$pathologyStart = '2024-04-11'; // First arret date

echo "=== Mock20 Debug ===\n\n";
echo "Affiliation date: $affiliationDate\n";
echo "Current date: $currentDate\n";
echo "Pathology start: $pathologyStart\n\n";

// Calculate trimesters using OLD method (months / 3)
$affiliation = new DateTime($affiliationDate);
$current = new DateTime($currentDate);
$interval = $affiliation->diff($current);
$months = ($interval->y * 12) + $interval->m;
$oldTrimestres = (int) floor($months / 3);

echo "OLD calculation (months / 3):\n";
echo "  Months: $months\n";
echo "  Trimestres: $oldTrimestres\n\n";

// Calculate trimesters using NEW method (quarter completion)
$newTrimestres = $dateService->calculateTrimesters($affiliationDate, $currentDate);
echo "NEW calculation (quarter completion):\n";
echo "  Trimestres: $newTrimestres\n\n";

// Calculate from affiliation to pathology start (for nb_trimestres rule)
$trimestresAtPathologyStart = $dateService->calculateTrimesters($affiliationDate, $pathologyStart);
echo "Trimestres at pathology start ($pathologyStart):\n";
echo "  Trimestres: $trimestresAtPathologyStart\n\n";

// Test the mock
$calculator = new IJCalculator('taux.csv');
$mockData = json_decode(file_get_contents('mock20.json'), true);

$input = [
    'arrets' => $mockData,
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1981-03-15',
    'current_date' => date("Y-m-d"),
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => '2019-01-01',
    'nb_trimestres' => 23, // Manual override
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 1
];

$result = $calculator->calculateAmount($input);

echo "Calculation result:\n";
echo "  Expected: 8757€\n";
echo "  Got: {$result['montant']}€\n";
echo "  Payable days: {$result['nb_jours']}\n";
echo "  Age: {$result['age']}\n";
echo "  Nb trimestres used: " . ($input['nb_trimestres'] ?? 'auto') . "\n";
