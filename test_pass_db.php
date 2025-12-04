<?php
/**
 * Test script for PASS (Plafond Annuel Sécurité Sociale) database integration
 *
 * Tests the PlafondSecuSociale model and PassRepository functionality
 *
 * Usage: php test_pass_db.php
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\PlafondSecuSociale;
use App\Repositories\PassRepository;
use App\Services\TauxDeterminationService;
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
echo "Testing PASS Database Integration\n";
echo "===================================\n\n";

try {
    // Test 1: Check database connection
    echo "Test 1: Database Connection\n";
    echo "----------------------------\n";
    $connection = Capsule::connection();
    $connection->getPdo();
    echo "✓ Database connection successful\n";
    echo "  Database: " . (getenv('DB_NAME') ?: 'carmf_ij') . "\n\n";

    // Test 2: Check if plafond_secu_sociale table exists
    echo "Test 2: Table Existence\n";
    echo "-----------------------\n";
    $tableExists = Capsule::schema()->hasTable('plafond_secu_sociale');
    if ($tableExists) {
        echo "✓ Table 'plafond_secu_sociale' exists\n\n";
    } else {
        echo "✗ Table 'plafond_secu_sociale' does not exist\n";
        echo "  Please create the table first using the schema provided\n\n";
        exit(1);
    }

    // Test 3: Count PASS records
    echo "Test 3: PASS Record Count\n";
    echo "-------------------------\n";
    $count = PlafondSecuSociale::count();
    echo "✓ Found {$count} PASS record(s) in database\n";
    if ($count === 0) {
        echo "  Warning: No PASS records found. Please insert data.\n";
    }
    echo "\n";

    // Test 4: Get PASS values by year
    if ($count > 0) {
        echo "Test 4: Get PASS Values by Year\n";
        echo "--------------------------------\n";
        $passByYear = PlafondSecuSociale::getPassValuesByYear();
        echo "✓ Retrieved PASS values for " . count($passByYear) . " year(s)\n\n";

        echo "  Year → PASS Value:\n";
        foreach (array_slice($passByYear, 0, 5) as $year => $pass) {
            echo "  {$year} → {$pass} €\n";
        }
        echo "\n";

        // Test 5: Get PASS for specific year
        echo "Test 5: Get PASS for Specific Year\n";
        echo "-----------------------------------\n";
        $testYear = 2024;
        $pass2024 = PlafondSecuSociale::getPassForYear($testYear);
        if ($pass2024) {
            echo "✓ PASS for year {$testYear}: {$pass2024} €\n";
        } else {
            echo "✗ No PASS found for year {$testYear}\n";
        }
        echo "\n";

        // Test 6: Get PASS for specific date
        echo "Test 6: Get PASS for Specific Date\n";
        echo "-----------------------------------\n";
        $testDate = '2024-06-15';
        $passForDate = PlafondSecuSociale::getPassForDate($testDate);
        if ($passForDate) {
            echo "✓ PASS for date {$testDate}: {$passForDate} €\n";
        } else {
            echo "✗ No PASS found for date {$testDate}\n";
        }
        echo "\n";

        // Test 7: Get latest PASS
        echo "Test 7: Get Latest PASS\n";
        echo "-----------------------\n";
        $latestPass = PlafondSecuSociale::getLatestPass();
        if ($latestPass) {
            echo "✓ Latest PASS value: {$latestPass} €\n";
        } else {
            echo "✗ No PASS records found\n";
        }
        echo "\n";
    }

    // Test 8: PassRepository integration
    echo "Test 8: PassRepository\n";
    echo "----------------------\n";
    $passRepo = new PassRepository();
    $passValues = $passRepo->loadPassValuesByYear();
    echo "✓ PassRepository loaded " . count($passValues) . " PASS value(s)\n";
    if (count($passValues) > 0) {
        echo "  Sample: Year " . array_key_first($passValues) . " = " . reset($passValues) . " €\n";
    }
    echo "\n";

    // Test 9: TauxDeterminationService integration
    echo "Test 9: TauxDeterminationService Integration\n";
    echo "---------------------------------------------\n";
    $service = new TauxDeterminationService();
    $service->setPassValuesByYear($passValues);

    // Test classe determination with different revenues
    $testCases = [
        ['revenue' => 30000, 'expected' => 'A'],
        ['revenue' => 50000, 'expected' => 'B'],
        ['revenue' => 150000, 'expected' => 'C'],
    ];

    foreach ($testCases as $test) {
        $classe = $service->determineClasse(
            $test['revenue'],
            '2024-01-01',
            false,
            2024
        );
        $match = $classe === $test['expected'] ? '✓' : '✗';
        echo "  {$match} Revenue {$test['revenue']} € → Class {$classe} (expected {$test['expected']})\n";
    }
    echo "\n";

    // Test 10: Show all PASS records
    echo "Test 10: All PASS Records (ordered by date)\n";
    echo "--------------------------------------------\n";
    $allRecords = PlafondSecuSociale::orderBy('date_deb_effet', 'desc')->limit(10)->get();
    echo "✓ Showing latest " . min(10, $allRecords->count()) . " record(s):\n\n";

    foreach ($allRecords as $record) {
        $dateDebut = $record->date_deb_effet->format('Y-m-d');
        $dateFin = $record->date_fin_effet ? $record->date_fin_effet->format('Y-m-d') : 'N/A';
        echo "  ID: {$record->id_plafond_secu_sociale}\n";
        echo "    Period: {$dateDebut} to {$dateFin}\n";
        echo "    MT_PASS: {$record->MT_PASS} €\n";
        echo "\n";
    }

    echo "===================================\n";
    echo "All tests completed! ✓\n";
    echo "===================================\n";

} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
