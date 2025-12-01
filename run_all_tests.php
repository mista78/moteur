<?php

/**
 * Comprehensive Test Runner
 * Runs all unit tests using PHPUnit
 */

echo "\n";
echo "================================================================================\n";
echo "                       IJCalculator Test Suite                                  \n";
echo "================================================================================\n";
echo "\n";

// Execute PHPUnit with the configuration file
$phpunitBin = __DIR__ . '/vendor/bin/phpunit';
$configFile = __DIR__ . '/phpunit.xml';

if (!file_exists($phpunitBin)) {
    echo "\033[31mError: PHPUnit not found. Run 'composer install' first.\033[0m\n";
    exit(1);
}

// Run PHPUnit
passthru("$phpunitBin --configuration $configFile --colors=always", $exitCode);

exit($exitCode);
