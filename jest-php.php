<?php

/**
 * Jest-style testing framework for PHP
 */

class JestPHP {
    private static $suites = [];
    private static $currentSuite = null;
    private static $stats = [
        'passed' => 0,
        'failed' => 0,
        'total' => 0
    ];
    private static $failedTests = [];
    private static $startTime;

    public static function describe($description, $callback) {
        self::$currentSuite = [
            'description' => $description,
            'tests' => []
        ];

        $callback();

        self::$suites[] = self::$currentSuite;
        self::$currentSuite = null;
    }

    public static function it($description, $callback) {
        if (self::$currentSuite === null) {
            throw new Exception("'it' must be called inside 'describe'");
        }

        self::$currentSuite['tests'][] = [
            'description' => $description,
            'callback' => $callback
        ];
    }

    public static function expect($actual) {
        return new Expectation($actual);
    }

    public static function run() {
        self::$startTime = microtime(true);
        echo "\n";

        foreach (self::$suites as $suite) {
            // Check if suite has tests array
            if (!isset($suite['tests']) || !is_array($suite['tests'])) {
                continue;
            }

            foreach ($suite['tests'] as $test) {
                self::$stats['total']++;
                $testName = "{$suite['description']} › {$test['description']}";

                try {
                    $test['callback']();
                    echo " \033[32m✓\033[0m $testName\n";
                    self::$stats['passed']++;
                } catch (AssertionError $e) {
                    echo " \033[31m✗\033[0m $testName\n";
                    self::$stats['failed']++;
                    self::$failedTests[] = [
                        'name' => $testName,
                        'error' => $e->getMessage()
                    ];
                } catch (Exception $e) {
                    echo " \033[31m✗\033[0m $testName\n";
                    self::$stats['failed']++;
                    self::$failedTests[] = [
                        'name' => $testName,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        self::printSummary();
    }

    private static function printSummary() {
        $endTime = microtime(true);
        $duration = round(($endTime - self::$startTime) * 1000, 2);

        echo "\n";

        // Afficher les tests échoués
        if (!empty(self::$failedTests)) {
            echo "\033[1mFailed Tests:\033[0m\n";
            foreach (self::$failedTests as $test) {
                echo "  \033[31m●\033[0m {$test['name']}\n";
                echo "    {$test['error']}\n\n";
            }
        }

        // Résumé
        $suiteCount = count(self::$suites);
        $suitesStatus = self::$stats['failed'] > 0 ? "\033[31m1 failed\033[0m" : "\033[32m{$suiteCount} passed\033[0m";

        echo "\033[1mTest Suites:\033[0m $suitesStatus, $suiteCount total\n";
        echo "\033[1mTests:\033[0m       ";

        if (self::$stats['failed'] > 0) {
            echo "\033[31m" . self::$stats['failed'] . " failed\033[0m, ";
        }

        echo "\033[32m" . self::$stats['passed'] . " passed\033[0m, " . self::$stats['total'] . " total\n";
        echo "\033[1mTime:\033[0m        {$duration}ms\n";
    }

    public static function reset() {
        self::$suites = [];
        self::$currentSuite = null;
        self::$stats = [
            'passed' => 0,
            'failed' => 0,
            'total' => 0
        ];
        self::$failedTests = [];
    }
}

class Expectation {
    private $actual;

    public function __construct($actual) {
        $this->actual = $actual;
    }

    public function toBe($expected) {
        if ($this->actual !== $expected) {
            throw new AssertionError("Expected {$expected}, but got {$this->actual}");
        }
        return $this;
    }

    public function toEqual($expected) {
        if ($this->actual != $expected) {
            throw new AssertionError("Expected " . print_r($expected, true) . ", but got " . print_r($this->actual, true));
        }
        return $this;
    }

    public function toBeCloseTo($expected, $precision = 0.01) {
        if (abs($this->actual - $expected) >= $precision) {
            throw new AssertionError("Expected {$expected} (±{$precision}), but got {$this->actual}");
        }
        return $this;
    }

    public function toBeGreaterThan($expected) {
        if ($this->actual <= $expected) {
            throw new AssertionError("Expected value to be greater than {$expected}, but got {$this->actual}");
        }
        return $this;
    }

    public function toBeLessThan($expected) {
        if ($this->actual >= $expected) {
            throw new AssertionError("Expected value to be less than {$expected}, but got {$this->actual}");
        }
        return $this;
    }

    public function toBeNull() {
        if ($this->actual !== null) {
            throw new AssertionError("Expected null, but got " . print_r($this->actual, true));
        }
        return $this;
    }

    public function toBeTruthy() {
        if (!$this->actual) {
            throw new AssertionError("Expected truthy value, but got " . print_r($this->actual, true));
        }
        return $this;
    }

    public function toBeFalsy() {
        if ($this->actual) {
            throw new AssertionError("Expected falsy value, but got " . print_r($this->actual, true));
        }
        return $this;
    }

    public function toContain($needle) {
        if (is_array($this->actual)) {
            if (!in_array($needle, $this->actual)) {
                throw new AssertionError("Expected array to contain " . print_r($needle, true));
            }
        } elseif (is_string($this->actual)) {
            if (strpos($this->actual, $needle) === false) {
                throw new AssertionError("Expected string to contain '{$needle}'");
            }
        } else {
            throw new AssertionError("toContain() only works with arrays and strings");
        }
        return $this;
    }

    public function toHaveProperty($property) {
        if (!is_object($this->actual) && !is_array($this->actual)) {
            throw new AssertionError("Expected object or array");
        }

        if (is_array($this->actual)) {
            if (!array_key_exists($property, $this->actual)) {
                throw new AssertionError("Expected array to have property '{$property}'");
            }
        } else {
            if (!property_exists($this->actual, $property)) {
                throw new AssertionError("Expected object to have property '{$property}'");
            }
        }
        return $this;
    }

    public function toMatchArray($expected) {
        if (!is_array($this->actual) || !is_array($expected)) {
            throw new AssertionError("Both values must be arrays");
        }

        if (count($this->actual) !== count($expected)) {
            throw new AssertionError("Arrays have different lengths");
        }

        foreach ($expected as $key => $value) {
            if (!array_key_exists($key, $this->actual)) {
                throw new AssertionError("Missing key '{$key}' in actual array");
            }
            if ($this->actual[$key] != $value) {
                throw new AssertionError("Value mismatch at key '{$key}': expected {$value}, got {$this->actual[$key]}");
            }
        }

        return $this;
    }

    public function toContainItemWith($key, $value) {
        if (!is_array($this->actual)) {
            throw new AssertionError("Expected array");
        }

        foreach ($this->actual as $item) {
            if (is_array($item) && isset($item[$key]) && $item[$key] == $value) {
                return $this;
            }
        }

        throw new AssertionError("Expected array to contain item with {$key} = {$value}");
    }

    public function toHavePaymentDetailWith($property, $value) {
        if (!is_array($this->actual)) {
            throw new AssertionError("Expected array of payment details");
        }

        foreach ($this->actual as $detail) {
            if (is_array($detail) && isset($detail[$property]) && $detail[$property] == $value) {
                return $this;
            }
        }

        $actualValues = array_map(function($d) use ($property) {
            return isset($d[$property]) ? $d[$property] : 'null';
        }, $this->actual);

        throw new AssertionError(
            "Expected payment_details to contain item with {$property} = {$value}\n" .
            "Actual values: " . implode(', ', $actualValues)
        );
    }
}

// Fonctions globales pour un usage plus simple
function describe($description, $callback) {
    JestPHP::describe($description, $callback);
}

function it($description, $callback) {
    JestPHP::it($description, $callback);
}

function test($description, $callback) {
    JestPHP::it($description, $callback);
}

function expect($actual) {
    return JestPHP::expect($actual);
}
