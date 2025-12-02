# Multi-Database Configuration with Eloquent ORM

This document explains how to use multiple databases in the IJ Calculator project.

## Configuration

### 1. Database Connections

The project supports multiple database connections defined in `config/database.php`:

- **mysql** (default) - Primary database for IJ calculations
- **mysql_secondary** - Secondary database (e.g., legacy system, external data)
- **mysql_analytics** - Analytics database (e.g., logs, metrics, reporting)
- **pgsql** - PostgreSQL database (optional)
- **sqlite** - SQLite database (optional, for testing)

### 2. Environment Variables

Configure each database in your `.env` file:

```bash
# Set default connection
DB_DEFAULT_CONNECTION=mysql

# Primary Database
DB_HOST=localhost
DB_NAME=carmf_ij
DB_USER=root
DB_PASSWORD=

# Secondary Database
DB_SECONDARY_HOST=localhost
DB_SECONDARY_NAME=carmf_legacy
DB_SECONDARY_USER=root
DB_SECONDARY_PASSWORD=

# Analytics Database
DB_ANALYTICS_HOST=analytics.example.com
DB_ANALYTICS_NAME=carmf_analytics
DB_ANALYTICS_USER=analytics_user
DB_ANALYTICS_PASSWORD=secret
```

## Usage

### Method 1: Set Connection in Model

Define the connection directly in your model class:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyAdherent extends Model
{
    protected $connection = 'mysql_secondary';
    protected $table = 'adherent_infos';
    // ...
}

// Usage
$adherent = LegacyAdherent::find(1);
```

### Method 2: Switch Connection at Runtime

Switch connections dynamically when querying:

```php
use App\Models\IjRecap;

// Use default connection
$recaps = IjRecap::all();

// Use specific connection
$legacyRecaps = IjRecap::on('mysql_secondary')->get();

// Query Builder with specific connection
$results = DB::connection('mysql_analytics')
    ->table('calculation_logs')
    ->where('success', true)
    ->get();
```

### Method 3: Multiple Connections in Same Query

```php
use Illuminate\Support\Facades\DB;

// Get data from primary database
$ijData = DB::connection('mysql')
    ->table('ij_recap')
    ->where('adherent_number', '1234567')
    ->first();

// Get data from secondary database
$adherentInfo = DB::connection('mysql_secondary')
    ->table('adherent_infos')
    ->where('adherent_number', '1234567')
    ->first();

// Log to analytics database
DB::connection('mysql_analytics')
    ->table('calculation_logs')
    ->insert([
        'adherent_number' => '1234567',
        'calculation_type' => 'ij_calculation',
        'execution_time' => 0.523,
        'success' => true,
    ]);
```

### Method 4: Transactions Across Multiple Databases

```php
use Illuminate\Support\Facades\DB;

try {
    // Start transaction on primary database
    DB::connection('mysql')->beginTransaction();

    // Start transaction on analytics database
    DB::connection('mysql_analytics')->beginTransaction();

    // Save to primary database
    $recap = IjRecap::create([...]);

    // Log to analytics database
    DB::connection('mysql_analytics')
        ->table('calculation_logs')
        ->insert([...]);

    // Commit both transactions
    DB::connection('mysql')->commit();
    DB::connection('mysql_analytics')->commit();

} catch (\Exception $e) {
    // Rollback both transactions
    DB::connection('mysql')->rollBack();
    DB::connection('mysql_analytics')->rollBack();

    throw $e;
}
```

## Real-World Examples

### Example 1: Fetching from Legacy System

```php
use App\Models\LegacyAdherent;
use App\Models\IjRecap;

class RecapService
{
    public function createRecapWithAdherentInfo(array $data): array
    {
        // Get adherent info from legacy database
        $adherent = LegacyAdherent::where('adherent_number', $data['adherent_number'])
            ->first();

        // Create recap in primary database
        $recap = IjRecap::create([
            'adherent_number' => $adherent->adherent_number,
            'montant_total' => $data['montant_total'],
            'nbe_jours' => $data['nbe_jours'],
        ]);

        return [
            'recap' => $recap,
            'adherent' => $adherent,
        ];
    }
}
```

### Example 2: Logging Calculations to Analytics

```php
use App\Models\AnalyticsLog;
use App\Models\IjRecap;

class CalculationService
{
    public function performCalculation(array $input): array
    {
        $startTime = microtime(true);

        try {
            // Perform calculation
            $result = $this->calculate($input);

            // Save to primary database
            $recap = IjRecap::create($result);

            // Log to analytics database
            AnalyticsLog::create([
                'adherent_number' => $input['adherent_number'],
                'calculation_type' => 'ij_calculation',
                'input_data' => $input,
                'output_data' => $result,
                'execution_time' => microtime(true) - $startTime,
                'success' => true,
            ]);

            return $result;

        } catch (\Exception $e) {
            // Log error to analytics
            AnalyticsLog::create([
                'adherent_number' => $input['adherent_number'] ?? null,
                'calculation_type' => 'ij_calculation',
                'input_data' => $input,
                'execution_time' => microtime(true) - $startTime,
                'success' => false,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

### Example 3: Read-Write Splitting

```php
use Illuminate\Support\Facades\DB;

// Configure read-write splitting in config/database.php
'mysql' => [
    'read' => [
        'host' => ['192.168.1.1', '192.168.1.2'],
    ],
    'write' => [
        'host' => ['192.168.1.3'],
    ],
    'driver'    => 'mysql',
    'database'  => 'carmf_ij',
    'username'  => 'root',
    'password'  => '',
    // ...
],
```

## Testing Multiple Connections

Test all connections:

```bash
php test_eloquent.php
```

This will test all configured database connections and report their status.

## Best Practices

1. **Use Models**: Define `$connection` in model classes for clarity
2. **Document Connections**: Comment which database each model uses
3. **Handle Failures**: Wrap multi-database operations in try-catch
4. **Connection Pooling**: Reuse connections when possible
5. **Security**: Store credentials in `.env`, never commit them
6. **Monitoring**: Log cross-database operations for debugging

## Troubleshooting

**Connection Refused:**
```bash
# Check if database server is running
sudo service mysql status

# Check credentials in .env file
cat .env | grep DB_
```

**Wrong Database:**
```php
// Verify active connection
echo DB::connection()->getDatabaseName();

// List all connections
var_dump(DB::getConnections());
```

**Performance Issues:**
- Use eager loading: `Model::with('relation')->get()`
- Index foreign keys in all databases
- Monitor slow queries with `DB::enableQueryLog()`

## Additional Resources

- [Laravel Eloquent Documentation](https://laravel.com/docs/11.x/eloquent)
- [Multiple Database Connections](https://laravel.com/docs/11.x/database#configuration)
- [Database Transactions](https://laravel.com/docs/11.x/database#database-transactions)
