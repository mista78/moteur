<?php
/**
 * Enhanced batch script to add missing return types and parameter types
 */

function addMissingTypes($file) {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $modified = false;

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];

        // Fix property types
        if (preg_match('/^\s+(private|public|protected)\s+\$rates\s*=/', $line)) {
            if ($i > 0 && !preg_match('/@var/', $lines[$i-1])) {
                array_splice($lines, $i, 0, ['	/** @var array<int, array<string, string>> */']);
                $modified = true;
                $i++;
            }
        }

        if (preg_match('/^\s+(private|public|protected)\s+\$passValue\s*=/', $line)) {
            if ($i > 0 && !preg_match('/@var/', $lines[$i-1])) {
                array_splice($lines, $i, 0, ['	/** @var float|int|null */']);
                $modified = true;
                $i++;
            }
        }

        // Add missing return types for methods
        $methodPatterns = [
            '/public function (calculateAge|getTrimesterFromDate)\([^)]*\)$/' => ': int',
            '/public function (getRateForYear|getRateForDate)\([^)]*\)$/' => ': ?array',
            '/public function (calculateTrimesters)\([^)]*\)$/' => ': int',
            '/public function (mergeProlongations|calculateDateEffet|calculatePayableDays|validateAndCorrectOption|calculateEndPaymentDates|generateDailyBreakdown|splitPaymentByYear)\([^)]*\)$/' => ': array',
            '/public function (calculateAmount|calculateMontantByAgeWithDetails)\([^)]*\)$/' => ': array',
            '/public function (getRate)\([^)]*\)$/' => ': float',
            '/public function (calculateRevenuAnnuel)\([^)]*\)$/' => ': float',
            '/public function (sortByDateStartDesc)\([^)]*\)$/' => ': array',
        ];

        foreach ($methodPatterns as $pattern => $returnType) {
            if (preg_match($pattern, $line)) {
                $lines[$i] = preg_replace('/\)$/', ')' . $returnType, $line);
                $modified = true;
                break;
            }
        }

        // Add parameter type hints
        $paramPatterns = [
            '/\$year(?!\w)/' => 'int ',
            '/\$age(?!\w)/' => 'int ',
            '/\$taux(?!\w)/' => 'int ',
            '/\$option(?!\w)/' => 'float|int ',
            '/\$previousCumulDays(?!\w)/' => 'int ',
            '/\$value\)/' => 'float|int $value)',
        ];

        foreach ($paramPatterns as $param => $type) {
            if (preg_match('/function [a-zA-Z]+\([^)]*' . preg_quote($param, '/') . '/', $line) &&
                !preg_match('/(int|float|string|array|bool) \\' . preg_quote($param, '/') . '/', $line)) {
                // Skip if already has type
                continue;
            }
        }
    }

    if ($modified) {
        file_put_contents($file, implode("\n", $lines));
        return true;
    }
    return false;
}

$files = [
    'src/IJCalculator.php',
    'src/Services/AmountCalculationService.php',
    'src/Services/DateService.php',
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "Skipping $file (not found)\n";
        continue;
    }

    if (addMissingTypes($file)) {
        echo "Updated $file\n";
    } else {
        echo "No changes for $file\n";
    }
}

echo "\nDone!\n";
