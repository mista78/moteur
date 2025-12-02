<?php

/**
 * Test Eloquent ORM Multi-Database Setup
 *
 * This script tests all configured database connections
 */

require __DIR__ . '/vendor/autoload.php';

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

echo "==========================================================\n";
echo "   Eloquent ORM Multi-Database Connection Test\n";
echo "==========================================================\n\n";

// Load database configuration
$config = require __DIR__ . '/config/database.php';

echo "Default Connection: {$config['default']}\n";
echo "Available Connections: " . implode(', ', array_keys($config['connections'])) . "\n\n";

// Initialize Eloquent
$capsule = new Capsule;

// Register all database connections
foreach ($config['connections'] as $name => $connection) {
    $capsule->addConnection($connection, $name);
}

// Set default connection
$capsule->getDatabaseManager()->setDefaultConnection($config['default']);

$capsule->setAsGlobal();
$capsule->bootEloquent();

// Test each connection
$results = [];
$successCount = 0;
$failureCount = 0;

foreach ($config['connections'] as $name => $connection) {
    echo "----------------------------------------------------------\n";
    echo "Testing connection: $name\n";
    echo "----------------------------------------------------------\n";

    // Skip SQLite if file doesn't exist
    if ($connection['driver'] === 'sqlite' && !file_exists($connection['database'])) {
        echo "⊘ Skipped (database file not found)\n";
        echo "  Path: {$connection['database']}\n\n";
        continue;
    }

    try {
        $conn = Capsule::connection($name);
        $pdo = $conn->getPdo();

        echo "✓ Connection successful!\n";

        // Show connection details
        if ($connection['driver'] !== 'sqlite') {
            echo "  Driver: {$connection['driver']}\n";
            echo "  Host: {$connection['host']}\n";
            echo "  Database: {$connection['database']}\n";
            echo "  Username: {$connection['username']}\n";
        } else {
            echo "  Driver: sqlite\n";
            echo "  Database: {$connection['database']}\n";
        }

        // Test basic query
        if ($connection['driver'] === 'mysql') {
            $tables = $conn->select('SHOW TABLES');
            echo "  Tables: " . count($tables) . "\n";

            if (count($tables) > 0 && count($tables) <= 10) {
                foreach ($tables as $table) {
                    $tableName = current((array)$table);
                    echo "    - $tableName\n";
                }
            }
        } elseif ($connection['driver'] === 'pgsql') {
            $tables = $conn->select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'");
            echo "  Tables: " . count($tables) . "\n";
        } elseif ($connection['driver'] === 'sqlite') {
            $tables = $conn->select("SELECT name FROM sqlite_master WHERE type='table'");
            echo "  Tables: " . count($tables) . "\n";
        }

        $results[$name] = 'success';
        $successCount++;

    } catch (Exception $e) {
        echo "✗ Connection failed!\n";
        echo "  Error: " . $e->getMessage() . "\n";

        if ($connection['driver'] !== 'sqlite') {
            echo "  Host: {$connection['host']}\n";
            echo "  Database: {$connection['database']}\n";
        }

        $results[$name] = 'failed';
        $failureCount++;
    }

    echo "\n";
}

// Summary
echo "==========================================================\n";
echo "   Test Summary\n";
echo "==========================================================\n\n";

foreach ($results as $name => $status) {
    $icon = $status === 'success' ? '✓' : '✗';
    $statusText = $status === 'success' ? 'SUCCESS' : 'FAILED';
    echo "$icon $name: $statusText\n";
}

echo "\n";
echo "Total: " . count($results) . " connections tested\n";
echo "Success: $successCount\n";
echo "Failed: $failureCount\n";
echo "\n";

if ($failureCount === 0 && $successCount > 0) {
    echo "==========================================================\n";
    echo "   ✓ All configured connections working!\n";
    echo "==========================================================\n";
    echo "\nYou can now use Models with multiple databases:\n\n";
    echo "// Default connection (primary database)\n";
    echo "use App\\Models\\IjRecap;\n";
    echo "\$recap = IjRecap::find(1);\n\n";
    echo "// Secondary connection\n";
    echo "use App\\Models\\LegacyAdherent;\n";
    echo "\$adherent = LegacyAdherent::find(1);\n\n";
    echo "// Switch connection at runtime\n";
    echo "\$data = IjRecap::on('mysql_secondary')->get();\n\n";
    echo "See MULTI_DATABASE_USAGE.md for more examples.\n\n";
    exit(0);
} else {
    echo "==========================================================\n";
    if ($successCount > 0) {
        echo "   ⚠ Some connections failed\n";
    } else {
        echo "   ✗ All connections failed\n";
    }
    echo "==========================================================\n";
    echo "\nPlease check:\n";
    echo "  1. Database credentials in .env file\n";
    echo "  2. Database servers are running\n";
    echo "  3. Databases exist\n";
    echo "  4. Network connectivity\n\n";
    exit(1);
}
