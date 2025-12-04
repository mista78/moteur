<?php

/**
 * Multi-Database Configuration for Eloquent ORM
 *
 * This file contains multiple database connection settings.
 * You can define as many connections as needed and switch between them.
 *
 * Usage:
 * - Default connection: Model::query() or DB::table('table')
 * - Specific connection: Model::on('connection_name')->get()
 */

return [
    // Default connection name
    'default' => $_ENV['DB_DEFAULT_CONNECTION'] ?? 'mysql',

    // Database connections
    'connections' => [
        // Primary MySQL database (default)
        'mysql' => [
            'driver'    => $_ENV['DB_DRIVER'] ?? 'mysql',
            'host'      => $_ENV['DB_HOST'] ?? 'localhost',
            'port'      => $_ENV['DB_PORT'] ?? '3306',
            'database'  => $_ENV['DB_NAME'] ?? 'moteurij',
            'username'  => $_ENV['DB_USER'] ?? 'root',
            'password'  => $_ENV['DB_PASSWORD'] ?? 'kmaoulida',
            'charset'   => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
            'prefix'    => $_ENV['DB_PREFIX'] ?? '',
            'strict'    => false,
            'engine'    => null,
        ],

        // Secondary database (e.g., legacy system, reporting, etc.)
        'mysql_secondary' => [
            'driver'    => $_ENV['DB_SECONDARY_DRIVER'] ?? 'mysql',
            'host'      => $_ENV['DB_SECONDARY_HOST'] ?? 'localhost',
            'port'      => $_ENV['DB_SECONDARY_PORT'] ?? '3306',
            'database'  => $_ENV['DB_SECONDARY_NAME'] ?? 'carmf_legacy',
            'username'  => $_ENV['DB_SECONDARY_USER'] ?? 'root',
            'password'  => $_ENV['DB_SECONDARY_PASSWORD'] ?? '',
            'charset'   => $_ENV['DB_SECONDARY_CHARSET'] ?? 'utf8mb4',
            'collation' => $_ENV['DB_SECONDARY_COLLATION'] ?? 'utf8mb4_unicode_ci',
            'prefix'    => $_ENV['DB_SECONDARY_PREFIX'] ?? '',
            'strict'    => true,
            'engine'    => null,
        ],

        // Third database (optional - e.g., analytics, logs, etc.)
        'mysql_analytics' => [
            'driver'    => $_ENV['DB_ANALYTICS_DRIVER'] ?? 'mysql',
            'host'      => $_ENV['DB_ANALYTICS_HOST'] ?? 'localhost',
            'port'      => $_ENV['DB_ANALYTICS_PORT'] ?? '3306',
            'database'  => $_ENV['DB_ANALYTICS_NAME'] ?? 'carmf_analytics',
            'username'  => $_ENV['DB_ANALYTICS_USER'] ?? 'root',
            'password'  => $_ENV['DB_ANALYTICS_PASSWORD'] ?? '',
            'charset'   => $_ENV['DB_ANALYTICS_CHARSET'] ?? 'utf8mb4',
            'collation' => $_ENV['DB_ANALYTICS_COLLATION'] ?? 'utf8mb4_unicode_ci',
            'prefix'    => $_ENV['DB_ANALYTICS_PREFIX'] ?? '',
            'strict'    => true,
            'engine'    => null,
        ],

        // PostgreSQL example (if you need a different database type)
        'pgsql' => [
            'driver'   => 'pgsql',
            'host'     => $_ENV['DB_PGSQL_HOST'] ?? 'localhost',
            'port'     => $_ENV['DB_PGSQL_PORT'] ?? '5432',
            'database' => $_ENV['DB_PGSQL_NAME'] ?? 'carmf_pgsql',
            'username' => $_ENV['DB_PGSQL_USER'] ?? 'postgres',
            'password' => $_ENV['DB_PGSQL_PASSWORD'] ?? '',
            'charset'  => $_ENV['DB_PGSQL_CHARSET'] ?? 'utf8',
            'prefix'   => $_ENV['DB_PGSQL_PREFIX'] ?? '',
            'schema'   => 'public',
            'sslmode'  => 'prefer',
        ],

        // SQLite example (for testing or local development)
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => $_ENV['DB_SQLITE_PATH'] ?? database_path('database.sqlite'),
            'prefix'   => '',
            'foreign_key_constraints' => true,
        ],
    ],
];

/**
 * Helper function to get database path
 */
function database_path(string $path = ''): string
{
    $basePath = dirname(__DIR__) . '/data/database';
    return $path ? $basePath . '/' . $path : $basePath;
}
