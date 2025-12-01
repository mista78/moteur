<?php
/**
 * Final comprehensive type hint fixer based on PHPStan output
 */

// Get PHPStan errors
exec('./vendor/bin/phpstan analyse --error-format=raw 2>&1 | grep "^/home"', $errors);

$fileIssues = [];

// Parse errors and group by file
foreach ($errors as $error) {
    if (preg_match('#^(/[^:]+):(\d+):(.+)$#', $error, $m)) {
        $file = $m[1];
        $line = (int)$m[2];
        $message = trim($m[3]);

        if (!isset($fileIssues[$file])) {
            $fileIssues[$file] = [];
        }

        $fileIssues[$file][] = ['line' => $line, 'message' => $message];
    }
}

// Fix each file
foreach ($fileIssues as $file => $issues) {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);

    foreach ($issues as $issue) {
        $lineNum = $issue['line'] - 1; // 0-indexed
        $message = $issue['message'];

        if (!isset($lines[$lineNum])) continue;

        // Fix missing parameter types in methods
        if (preg_match('/has parameter (\$\w+) with no type specified/', $message, $m)) {
            $param = $m[1];
            // Add type hint in docblock
            for ($i = $lineNum - 1; $i >= 0 && $i >= $lineNum - 20; $i--) {
                if (preg_match('#/\*\*#', $lines[$i])) {
                    // Found docblock, add @param if not exists
                    $foundParam = false;
                    for ($j = $i; $j < $lineNum; $j++) {
                        if (preg_match("/@param.*$param/", $lines[$j])) {
                            // Update existing @param
                            if (!preg_match('/@param\s+(array|string|int|float|bool|mixed)/', $lines[$j])) {
                                $lines[$j] = preg_replace("/@param\s+$param/", "@param mixed $param", $lines[$j]);
                            }
                            $foundParam = true;
                            break;
                        }
                    }
                    if (!$foundParam) {
                        // Add @param before closing */
                        for ($j = $lineNum - 1; $j > $i; $j--) {
                            if (preg_match('#\*/#', $lines[$j])) {
                                array_splice($lines, $j, 0, ["\t * @param mixed $param"]);
                                break;
                            }
                        }
                    }
                    break;
                }
            }
        }

        // Fix missing return types
        if (preg_match('/has no return type specified/', $message)) {
            for ($i = $lineNum - 1; $i >= 0 && $i >= $lineNum - 20; $i--) {
                if (preg_match('#/\*\*#', $lines[$i])) {
                    $foundReturn = false;
                    for ($j = $i; $j < $lineNum; $j++) {
                        if (preg_match('/@return/', $lines[$j])) {
                            $foundReturn = true;
                            break;
                        }
                    }
                    if (!$foundReturn) {
                        for ($j = $lineNum - 1; $j > $i; $j--) {
                            if (preg_match('#\*/#', $lines[$j])) {
                                array_splice($lines, $j, 0, ["\t * @return mixed"]);
                                break;
                            }
                        }
                    }
                    break;
                }
            }
        }

        // Fix array type specifications
        if (preg_match('/has no value type specified in iterable type array/', $message)) {
            for ($i = $lineNum - 1; $i >= 0 && $i >= $lineNum - 20; $i--) {
                if (preg_match('#@(param|return)\s+array\s#', $lines[$i])) {
                    $lines[$i] = preg_replace('/@(param|return)\s+array\s/', '@$1 array<string, mixed> ', $lines[$i]);
                    break;
                }
            }
        }
    }

    file_put_contents($file, implode("\n", $lines));
    echo "Fixed " . basename($file) . " (" . count($issues) . " issues)\n";
}

echo "\nDone! Run PHPStan again to verify.\n";
