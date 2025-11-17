<?php

require_once __DIR__ . '/Services/DateCalculationInterface.php';
require_once __DIR__ . '/Services/DateService.php';
require_once __DIR__ . '/Services/ArretService.php';

use App\IJCalculator\Services\ArretService;

$arretService = new ArretService();

$arrets = [
    [
        'arret-from-line' => '2024-01-01',
        'arret-to-line' => '2024-02-15',
        'valid_med_controleur' => 1,
        'rechute-line' => 0,
        'dt-line' => 1,
        'declaration-date-line' => '2024-01-01'
    ]
];

$result = $arretService->calculateDateEffetForArrets($arrets, null, 0);

echo "Result array:\n";
var_dump($result[0]);

echo "\n\nChecking date-effet key:\n";
echo "isset date-effet: " . (isset($result[0]['date-effet']) ? 'YES' : 'NO') . "\n";
echo "array_key_exists date-effet: " . (array_key_exists('date-effet', $result[0]) ? 'YES' : 'NO') . "\n";
echo "Value: ";
var_dump($result[0]['date-effet'] ?? 'KEY NOT EXISTS');
echo "\n";
