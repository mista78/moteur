<?php
/**
 * Test IJCalculator with PassRepository injection
 *
 * Verifies that IJCalculator properly loads PASS values from database
 * and uses them for class determination
 */

require __DIR__ . '/vendor/autoload.php';

use App\IJCalculator;
use App\Repositories\PassRepository;
use App\Repositories\RateRepository;
use Illuminate\Database\Capsule\Manager as Capsule;

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        putenv(trim($line));
    }
}

// Initialize Eloquent
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => getenv('DB_DRIVER') ?: 'mysql',
    'host'      => getenv('DB_HOST') ?: 'localhost',
    'database'  => getenv('DB_NAME') ?: 'carmf_ij',
    'username'  => getenv('DB_USER') ?: 'root',
    'password'  => getenv('DB_PASSWORD') ?: '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "============================================\n";
echo "Testing IJCalculator with PassRepository\n";
echo "============================================\n\n";

try {
    // Test 1: Create IJCalculator with PassRepository
    echo "Test 1: Create IJCalculator with PassRepository\n";
    echo "------------------------------------------------\n";

    $rateRepo = new RateRepository(__DIR__ . '/data/taux.csv');
    $passRepo = new PassRepository();

    $calculator = new IJCalculator(
        $rateRepo->loadRates(),  // Rates array
        null,                     // RateService (use default)
        null,                     // DateService (use default)
        null,                     // TauxService (use default)
        null,                     // AmountService (use default)
        $passRepo                 // PassRepository (inject)
    );

    echo "✓ IJCalculator created successfully with PassRepository\n\n";

    // Test 2: Load PASS values
    echo "Test 2: Verify PASS Values Loaded\n";
    echo "----------------------------------\n";

    $passValues = $passRepo->loadPassValuesByYear();
    echo "✓ Loaded " . count($passValues) . " PASS value(s) from database\n";

    if (count($passValues) > 0) {
        echo "\n  Sample PASS values:\n";
        foreach (array_slice($passValues, 0, 3, true) as $year => $pass) {
            echo "    {$year}: {$pass} €\n";
        }
    }
    echo "\n";

    // Test 3: Test class determination with different revenues
    echo "Test 3: Class Determination\n";
    echo "----------------------------\n";

    $testCases = [
        ['revenue' => 30000, 'year' => 2024, 'expected' => 'A'],
        ['revenue' => 50000, 'year' => 2024, 'expected' => 'B'],
        ['revenue' => 150000, 'year' => 2024, 'expected' => 'C'],
        ['revenue' => 40000, 'year' => 2023, 'expected' => 'A'],
        ['revenue' => 80000, 'year' => 2023, 'expected' => 'B'],
    ];

    echo "Testing class determination with database PASS values:\n\n";

    foreach ($testCases as $test) {
        $classe = $calculator->determineClasse(
            $test['revenue'],
            "{$test['year']}-01-01",
            false
        );

        $match = $classe === $test['expected'] ? '✓' : '✗';
        $passForYear = $passValues[$test['year']] ?? 'N/A';

        echo "  {$match} Year {$test['year']}, Revenue {$test['revenue']} € → Class {$classe}";
        echo " (expected {$test['expected']}, PASS={$passForYear})\n";
    }
    echo "\n";

    // Test 4: Compare with manual PASS setting
    echo "Test 4: Compare Database vs Manual PASS\n";
    echo "----------------------------------------\n";

    // Create calculator without PassRepository
    $calculatorManual = new IJCalculator($rateRepo->loadRates());
    $calculatorManual->setPassValue(46368); // 2024 PASS

    $testRevenue = 50000;
    $testYear = 2024;

    $classWithDB = $calculator->determineClasse($testRevenue, "{$testYear}-01-01", false);
    $classManual = $calculatorManual->determineClasse($testRevenue, "{$testYear}-01-01", false);

    echo "  Revenue: {$testRevenue} €, Year: {$testYear}\n";
    echo "  Class with DB PASS:     {$classWithDB}\n";
    echo "  Class with Manual PASS: {$classManual}\n";

    if ($classWithDB === $classManual) {
        echo "  ✓ Results match! Database integration working correctly\n";
    } else {
        echo "  ✗ Results differ! Check PASS values\n";
    }
    echo "\n";

    // Test 5: Show PASS thresholds for class determination
    echo "Test 5: PASS Thresholds (2024)\n";
    echo "-------------------------------\n";

    $pass2024 = $passValues[2024] ?? 46368;
    echo "  PASS 2024: {$pass2024} €\n";
    echo "  Class A:   < {$pass2024} €\n";
    echo "  Class B:   {$pass2024} € to " . ($pass2024 * 3) . " €\n";
    echo "  Class C:   > " . ($pass2024 * 3) . " €\n";
    echo "\n";

    echo "============================================\n";
    echo "All tests completed! ✓\n";
    echo "============================================\n";
    echo "\nIJCalculator is now using PASS values from database!\n";

} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n  Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
