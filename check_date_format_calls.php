<?php
/**
 * Scan all PHP files for potential date format() calls on non-DateTime objects
 */

echo "=== Checking for Potential Date Format Issues ===\n\n";

$files = array_merge(
    glob('Services/*.php'),
    glob('*.php')
);

$issues = [];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);

    foreach ($lines as $lineNum => $line) {
        // Look for patterns like $variable['date_xxx']->format
        // or $variable->date_xxx->format
        if (preg_match('/\$\w+\[[\'"](date_\w+|arret-\w+-line)[\'\"]\]\s*->\s*format/', $line)) {
            $issues[] = [
                'file' => $file,
                'line' => $lineNum + 1,
                'code' => trim($line),
                'type' => 'Array key with format() call'
            ];
        }

        // Look for $xxx->date_something->format
        if (preg_match('/\$\w+->(date_\w+)\s*->\s*format/', $line)) {
            $issues[] = [
                'file' => $file,
                'line' => $lineNum + 1,
                'code' => trim($line),
                'type' => 'Object property with format() call'
            ];
        }
    }
}

if (empty($issues)) {
    echo "✅ No potential issues found!\n";
    echo "All date format() calls appear to be safe.\n";
} else {
    echo "⚠️  Found " . count($issues) . " potential issue(s):\n\n";

    foreach ($issues as $issue) {
        echo "File: {$issue['file']}\n";
        echo "Line: {$issue['line']}\n";
        echo "Type: {$issue['type']}\n";
        echo "Code: {$issue['code']}\n";
        echo str_repeat('-', 80) . "\n";
    }

    echo "\nNote: These may be false positives. Review each case manually.\n";
}

echo "\n=== Check Complete ===\n";
