<?php
/**
 * Migration script: CSV rates to database
 *
 * This script migrates rate data from data/taux.csv to the ij_taux database table
 *
 * Usage: php migrate_rates_to_db.php
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\IjTaux;
use Illuminate\Database\Capsule\Manager as Capsule;

// Load environment variables (same method as public/index.php)
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

// CSV file path
$csvPath = __DIR__ . '/data/taux.csv';

if (!file_exists($csvPath)) {
    echo "Error: CSV file not found at {$csvPath}\n";
    exit(1);
}

echo "Starting migration from CSV to database...\n\n";

// Open and read CSV
$file = fopen($csvPath, 'r');
if ($file === false) {
    echo "Error: Unable to open CSV file\n";
    exit(1);
}

// Read headers
$headers = fgetcsv($file, 0, ';');
if ($headers === false) {
    echo "Error: Invalid CSV file\n";
    fclose($file);
    exit(1);
}

$insertedCount = 0;
$updatedCount = 0;
$errorCount = 0;

// Process each row
while (($row = fgetcsv($file, 0, ';')) !== false) {
    try {
        $data = [];
        foreach ($headers as $index => $header) {
            $data[$header] = $row[$index] ?? '';
        }

        // Prepare rate data (excluding id from CSV)
        $rateData = [
            'date_start' => $data['date_start'],
            'date_end' => $data['date_end'],
            'taux_a1' => (float)$data['taux_a1'],
            'taux_a2' => (float)$data['taux_a2'],
            'taux_a3' => (float)$data['taux_a3'],
            'taux_b1' => (float)$data['taux_b1'],
            'taux_b2' => (float)$data['taux_b2'],
            'taux_b3' => (float)$data['taux_b3'],
            'taux_c1' => (float)$data['taux_c1'],
            'taux_c2' => (float)$data['taux_c2'],
            'taux_c3' => (float)$data['taux_c3'],
        ];

        // Check if rate already exists for this date range
        $existing = IjTaux::where('date_start', $rateData['date_start'])
                          ->where('date_end', $rateData['date_end'])
                          ->first();

        if ($existing) {
            // Update existing record
            $existing->update($rateData);
            $updatedCount++;
            echo "Updated: {$rateData['date_start']} to {$rateData['date_end']}\n";
        } else {
            // Create new record
            IjTaux::create($rateData);
            $insertedCount++;
            echo "Inserted: {$rateData['date_start']} to {$rateData['date_end']}\n";
        }

    } catch (Exception $e) {
        $errorCount++;
        echo "Error processing row: " . $e->getMessage() . "\n";
    }
}

fclose($file);

echo "\n";
echo "Migration completed!\n";
echo "-------------------------\n";
echo "Inserted: {$insertedCount}\n";
echo "Updated:  {$updatedCount}\n";
echo "Errors:   {$errorCount}\n";
echo "Total:    " . ($insertedCount + $updatedCount) . "\n";

// Verify the migration
echo "\n";
echo "Verifying data in database...\n";
$dbCount = IjTaux::count();
echo "Total rates in database: {$dbCount}\n";

// Show sample data
echo "\nSample rates:\n";
$samples = IjTaux::orderBy('date_start', 'desc')->limit(3)->get();
foreach ($samples as $sample) {
    echo "  {$sample->date_start->format('Y-m-d')} to {$sample->date_end->format('Y-m-d')}: ";
    echo "A1={$sample->taux_a1}, B1={$sample->taux_b1}, C1={$sample->taux_c1}\n";
}

echo "\nMigration complete! ✓\n";
