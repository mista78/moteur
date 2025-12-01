<?php
/**
 * Batch script to fix common array type hints for PHPStan
 */

$files = [
    'src/Services/ArretService.php',
    'src/Services/DateService.php',
    'src/Services/AmountCalculationService.php',
    'src/Services/DateNormalizer.php',
    'src/IJCalculator.php',
];

$replacements = [
    // Common parameter patterns
    '/(@param\s+)array(\s+\$arrets)/m' => '$1array<int, array<string, mixed>>$2',
    '/(@param\s+)array(\s+\$data\b)/m' => '$1array<string, mixed>$2',
    '/(@param\s+)array(\s+\$rateInfo)/m' => '$1array<string, mixed>$2',
    '/(@param\s+)array(\s+\$arret\b)/m' => '$1array<string, mixed>$2',
    '/(@param\s+)array(\s+\$details)/m' => '$1array<string, mixed>$2',
    '/(@param\s+)array(\s+\$paymentDetails)/m' => '$1array<int, array<string, mixed>>$2',

    // Common return types
    '/(@return\s+)array(\s+[^\n]*arrets)/mi' => '$1array<int, array<string, mixed>>$2',
    '/(@return\s+)array(\s+[^\n]*records)/mi' => '$1array<int, array<string, mixed>>$2',
    '/(@return\s+)array(\s+[^\n]*breakdown)/mi' => '$1array<int, array<string, mixed>>$2',
    '/(@return\s+)array(\s+[^\n]*Result)/mi' => '$1array<string, mixed>$2',
    '/(@return\s+)array(\s+[^\n]*data)/mi' => '$1array<string, mixed>$2',
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "Skipping $file (not found)\n";
        continue;
    }

    $content = file_get_contents($file);
    $original = $content;

    foreach ($replacements as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }

    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Updated $file\n";
    } else {
        echo "No changes for $file\n";
    }
}

echo "\nDone! Run PHPStan to see remaining issues.\n";
