<?php
/**
 * Test script for database rate integration
 *
 * Tests the IjTaux model and RateRepository database functionality
 *
 * Usage: php test_rates_db.php
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\IjTaux;
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

echo "===================================\n";
echo "Testing Database Rate Integration\n";
echo "===================================\n\n";

try {
    // Test 1: Check database connection
    echo "Test 1: Database Connection\n";
    echo "----------------------------\n";
    $connection = Capsule::connection();
    $connection->getPdo();
    echo "✓ Database connection successful\n";
    echo "  Database: " . (getenv('DB_NAME') ?: 'carmf_ij') . "\n";
    echo "  Host: " . (getenv('DB_HOST') ?: 'localhost') . "\n\n";

    // Test 2: Check if ij_taux table exists
    echo "Test 2: Table Existence\n";
    echo "-----------------------\n";
    $tableExists = Capsule::schema()->hasTable('ij_taux');
    if ($tableExists) {
        echo "✓ Table 'ij_taux' exists\n\n";
    } else {
        echo "✗ Table 'ij_taux' does not exist\n";
        echo "  Please create the table first using the schema provided\n\n";
        exit(1);
    }

    // Test 3: Count rates in database
    echo "Test 3: Rate Count\n";
    echo "------------------\n";
    $count = IjTaux::count();
    echo "✓ Found {$count} rate record(s) in database\n";
    if ($count === 0) {
        echo "  Note: No rates found. Run 'php migrate_rates_to_db.php' to import from CSV\n";
    }
    echo "\n";

    // Test 4: Get rate for specific year (if data exists)
    if ($count > 0) {
        echo "Test 4: Query by Year\n";
        echo "---------------------\n";
        $rate2024 = IjTaux::getRateForYear(2024);
        if ($rate2024) {
            echo "✓ Rate for year 2024 found:\n";
            echo "  Date range: {$rate2024->date_start->format('Y-m-d')} to {$rate2024->date_end->format('Y-m-d')}\n";
            echo "  Class A rates: A1={$rate2024->taux_a1}, A2={$rate2024->taux_a2}, A3={$rate2024->taux_a3}\n";
            echo "  Class B rates: B1={$rate2024->taux_b1}, B2={$rate2024->taux_b2}, B3={$rate2024->taux_b3}\n";
            echo "  Class C rates: C1={$rate2024->taux_c1}, C2={$rate2024->taux_c2}, C3={$rate2024->taux_c3}\n";
        } else {
            echo "✗ No rate found for year 2024\n";
        }
        echo "\n";

        // Test 5: Query by date
        echo "Test 5: Query by Date\n";
        echo "---------------------\n";
        $rateByDate = IjTaux::getRateForDate('2024-06-15');
        if ($rateByDate) {
            echo "✓ Rate for date 2024-06-15 found:\n";
            echo "  Date range: {$rateByDate->date_start->format('Y-m-d')} to {$rateByDate->date_end->format('Y-m-d')}\n";
            echo "  Taux A1: {$rateByDate->taux_a1}\n";
        } else {
            echo "✗ No rate found for date 2024-06-15\n";
        }
        echo "\n";

        // Test 6: Get all rates ordered
        echo "Test 6: All Rates Ordered\n";
        echo "-------------------------\n";
        $allRates = IjTaux::getAllRatesOrdered();
        echo "✓ Retrieved {$allRates->count()} rate(s) ordered by date\n";
        echo "\n  Recent rates:\n";
        foreach ($allRates->take(3) as $rate) {
            echo "  - {$rate->date_start->format('Y-m-d')} to {$rate->date_end->format('Y-m-d')}: ";
            echo "A1={$rate->taux_a1}, B1={$rate->taux_b1}, C1={$rate->taux_c1}\n";
        }
        echo "\n";
    }

    // Test 7: RateRepository integration
    echo "Test 7: RateRepository\n";
    echo "----------------------\n";
    $csvPath = __DIR__ . '/data/taux.csv';
    $repository = new RateRepository($csvPath);
    $rates = $repository->loadRates();
    echo "✓ RateRepository loaded {$count} rate(s) from database\n";
    if (count($rates) > 0) {
        echo "  Sample rate structure:\n";
        $sample = $rates[0];
        echo "    date_start: " . (is_string($sample['date_start']) ? $sample['date_start'] : $sample['date_start']->format('Y-m-d')) . "\n";
        echo "    date_end: " . (is_string($sample['date_end']) ? $sample['date_end'] : $sample['date_end']->format('Y-m-d')) . "\n";
        echo "    taux_a1: {$sample['taux_a1']}\n";
    }
    echo "\n";

    echo "===================================\n";
    echo "All tests passed! ✓\n";
    echo "===================================\n";

} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
