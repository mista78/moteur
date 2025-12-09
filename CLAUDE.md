# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

French medical professional sick leave benefits calculator ("Indemnités Journalières" - IJ) for CARMF (Caisse Autonome de Retraite des Médecins de France). Built with **Slim Framework 4** and **Laravel Eloquent ORM** using modern PHP architecture with SOLID principles.

Calculates daily benefits based on:
- Contribution classes (A/B/C) and professional status (M/RSPM/CCPL)
- Age brackets (<62, 62-69, 70+) with different rate periods
- Affiliation quarters and pathology anterior status
- Complex 27-rate system with historical rate tables
- 90-day threshold for initial benefits, 15-day for relapses (rechute)

## Development Commands

### Running the Server

```bash
# Start PHP built-in server (recommended)
cd public && php -S localhost:8000

# Access at: http://localhost:8000
```

### Testing

```bash
# Run all tests with PHPUnit
php run_all_tests.php                       # Recommended: runs all unit tests with formatted output
./vendor/bin/phpunit                        # Direct PHPUnit execution
./vendor/bin/phpunit --testdox              # Test documentation format

# Run specific test suites
./vendor/bin/phpunit test/RateServiceTest.php              # Rate lookups
./vendor/bin/phpunit test/DateServiceTest.php              # Date calculations
./vendor/bin/phpunit test/TauxDeterminationServiceTest.php # Taux logic
./vendor/bin/phpunit test/AmountCalculationServiceTest.php # Amount calculations
./vendor/bin/phpunit test/RechuteTest.php                  # Relapse detection

# Run with code coverage (requires Xdebug)
./vendor/bin/phpunit --coverage-html coverage/

# Integration tests (mock scenarios)
php test/test_mocks.php                    # 20+ real-world scenarios
```

### Debug Specific Scenarios

```bash
php debug_mock2.php   # Multiple stoppages with rechute
php debug_mock9.php   # Age 70 transition case
php debug_mock20.php  # Period 2 calculations (62-69 age)
```

### Static Analysis

```bash
# Run PHPStan analysis (level 8)
./vendor/bin/phpstan analyse

# Run with memory limit increase
./vendor/bin/phpstan analyse --memory-limit=256M

# Configuration: phpstan.neon
# - Level: 8 (high strictness)
# - Paths: src/, config/
# - PHP Version: 8.2
# - Error format: raw (default, prevents table formatting crashes)
```

## Architecture

### Slim Framework Structure

```
project/
├── public/
│   ├── index.php                 # Slim entry point
│   ├── index.html                # Frontend
│   ├── app.js                    # Frontend logic
│   └── calendar_functions.js    # Calendar view
│
├── src/
│   ├── IJCalculator.php                # Main calculator/orchestrator (~700 lines)
│   │
│   ├── Controllers/
│   │   ├── CalculationController.php  # Calculation endpoints
│   │   └── MockController.php         # Mock data endpoints
│   │
│   ├── Models/                         # Eloquent ORM Models
│   │   ├── IjArret.php                 # Work stoppage model
│   │   ├── IjRecap.php                 # Calculation summary model
│   │   ├── IjDetailJour.php            # Daily detail model
│   │   ├── IjSinistre.php              # Claim model
│   │   └── IjTaux.php                  # Rate model (27-rate system)
│   │
│   ├── Services/                       # Business logic (SOLID)
│   │   ├── RateService.php             # Rate lookups (~220 lines)
│   │   ├── DateService.php             # Date calculations (~740 lines)
│   │   ├── TauxDeterminationService.php # Rate determination (~150 lines)
│   │   ├── AmountCalculationService.php # Pipeline orchestration (~740 lines)
│   │   ├── RecapService.php            # Database records (ij_recap)
│   │   ├── DetailJourService.php       # Database records (ij_detail_jour)
│   │   ├── ArretService.php            # Arret management
│   │   └── DateNormalizer.php          # Date normalization
│   │
│   ├── Repositories/
│   │   ├── RateRepository.php          # Rate loading (DB with CSV fallback)
│   │   └── PassRepository.php          # PASS values loading
│   │
│   ├── Middlewares/
│   │   ├── CorsMiddleware.php          # CORS headers
│   │   └── JsonBodyParserMiddleware.php # JSON parsing
│   │
│   └── Helpers/
│       └── ResponseFormatter.php       # Standardized responses
│
├── config/
│   ├── dependencies.php              # DI Container (includes Eloquent setup)
│   ├── database.php                  # Database configuration (Eloquent)
│   ├── settings.php                  # App settings
│   └── routes.php                    # Route definitions
│
├── data/
│   ├── taux.csv                      # Historical rates (2022-2025)
│   └── mocks/                        # Test scenarios (mock*.json)
│
├── test/                             # PHPUnit tests
├── logs/                             # Application logs
├── phpstan.neon                      # PHPStan configuration (level 8)
├── phpunit.xml                       # PHPUnit configuration
├── run_all_tests.php                 # Test runner (wraps PHPUnit)
├── .env                              # Environment configuration
└── composer.json                     # Dependencies (Slim 4, Eloquent 11, PHPUnit 10, PHPStan 2.1)

```

### Service Layer (SOLID Principles)

All services follow single responsibility principle and use dependency injection:

**RateService** - CSV rate lookups, tier determination
**DateService** - Age, trimesters, date-effet, payable days, rechute detection
**TauxDeterminationService** - Rate number (1-9) from age/quarters/pathology
**AmountCalculationService** - Pipeline orchestration, multi-period handling
**RecapService** - Database records for ij_recap table
**DetailJourService** - Database records for ij_detail_jour table
**ArretService** - Loading, validation, normalization of work stoppages

### Data Flow

1. **Request** → public/index.php (Slim entry point)
2. **Middleware** → CORS, JSON parsing
3. **Router** → config/routes.php matches endpoint
4. **Controller** → CalculationController or MockController
5. **Service Layer** → Business logic execution
6. **Response** → Standardized JSON via ResponseFormatter

## API Endpoints

### API Documentation (Swagger/OpenAPI)

**Interactive Documentation**: http://localhost:8000/api-docs

The API is fully documented with OpenAPI 3.0 (Swagger) using dynamic generation from PHP 8 attributes:

```bash
# Swagger UI (Interactive Documentation)
GET /api-docs                             # Full interactive API documentation

# OpenAPI Specification
GET /api/docs                             # OpenAPI JSON format
GET /api/docs/yaml                        # OpenAPI YAML format
```

**Features**:
- ✅ Auto-generated from controller PHP 8 attributes
- ✅ Try-it-out functionality for all endpoints
- ✅ Request/response schemas with examples
- ✅ cURL command generation
- ✅ Always up-to-date with code changes

### RESTful Endpoints (New)

```bash
# Calculations
POST /api/calculations                    # Main calculation (most used)
POST /api/calculations/date-effet         # Calculate date-effet (90-day rule)
POST /api/calculations/end-payment        # Period end dates
POST /api/calculations/revenu             # Revenue from class/PASS
POST /api/calculations/classe             # Auto-determine class from revenue
POST /api/calculations/arrets-date-effet  # Batch date-effet calculation

# Mock Data
GET /api/mocks                            # List all mock files
GET /api/mocks/{file}                     # Load specific mock (e.g., /api/mocks/mock or /api/mocks/mock.json)
```

### Example Request

```bash
curl -X POST http://localhost:8000/api/calculations \
  -H "Content-Type: application/json" \
  -d '{
    "statut": "M",
    "classe": "A",
    "option": 100,
    "birth_date": "1989-09-26",
    "current_date": "2024-09-09",
    "attestation_date": "2024-01-31",
    "affiliation_date": "2019-01-15",
    "nb_trimestres": 22,
    "arrets": [{
      "arret-from-line": "2023-10-24",
      "arret-to-line": "2024-01-31",
      "rechute-line": 0,
      "dt-line": 1,
      "gpm-member-line": 1
    }]
  }'
```

## Critical Business Rules

### Rechute Detection - DateService::isRechute()

**IMPORTANT**: A stoppage is only a rechute if ALL criteria are met:
1. **Previous arret has date-effet** (rights already opened)
2. NOT consecutive (gap between stoppages)
3. Starts within 1 year after previous arret ended

### 90-Day Threshold Rule

- **New pathology**: 90 days + penalties (DT: 31, GPM: 31)
- **Rechute**: 15 days + penalties (DT: 15, GPM: 15)
- Days before date-effet are "décompte" (counted but not paid)

### Trimester Calculation

**Partial quarters always count as complete**:
- Example: 2019-01-15 to 2024-04-11 = 22 quarters (NOT 21)
- Formula: `(years × 4) + (currentQ - affiliationQ) + 1`

### Age-Based Period System

**< 62 years**: Single rate (taux 1-3)
**62-69 years**: Three periods
  - Period 1 (days 1-365): Full rate (taux 1-3)
  - Period 2 (days 366-730): Rate -25% (taux 7-9)
  - Period 3 (days 731-1095): Senior rate (taux 4-6)
**≥ 70 years**: Max 365 days, senior rates (taux 4-6)

### 27-Rate System

9 taux numbers × 3 classes (A/B/C) = 27 rates:
- **Taux 1-3**: <62 years (full, -1/3, -2/3)
- **Taux 4-6**: ≥70 years (senior reduced)
- **Taux 7-9**: 62-69 years Period 2 (full-25%, -1/3, -2/3)

## Development Workflows

### Adding a New Endpoint

1. **Define route** in `config/routes.php`:
   ```php
   $group->post('/calculations/new-endpoint', [CalculationController::class, 'newMethod']);
   ```

2. **Add controller method** with OpenAPI attributes in `src/Controllers/CalculationController.php`:
   ```php
   #[OA\Post(
       path: "/api/calculations/new-endpoint",
       summary: "Brief description",
       description: "Detailed description of what this endpoint does",
       tags: ["calculations"],
       requestBody: new OA\RequestBody(
           required: true,
           content: new OA\JsonContent(
               properties: [
                   new OA\Property(property: "param1", type: "string", example: "value1"),
                   new OA\Property(property: "param2", type: "integer", example: 123)
               ]
           )
       ),
       responses: [
           new OA\Response(
               response: 200,
               description: "Success response",
               content: new OA\JsonContent(
                   properties: [
                       new OA\Property(property: "success", type: "boolean", example: true),
                       new OA\Property(property: "data", type: "object")
                   ]
               )
           )
       ]
   )]
   public function newMethod(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
   {
       // Implementation
       return ResponseFormatter::success($response, $data);
   }
   ```

3. **Test** with curl, Swagger UI (http://localhost:8000/api-docs), or frontend

**Note**: OpenAPI documentation is automatically updated when you add PHP 8 attributes to controller methods. No manual JSON/YAML editing required!

### Modifying Business Logic

**IMPORTANT**: Business logic lives in Services, not Controllers

1. Update service in `src/Services/`
2. Update interface if method signature changes (interfaces: `*Interface.php`)
3. Run specific test: `./vendor/bin/phpunit test/[Service]Test.php`
4. Run full suite: `php run_all_tests.php`
5. Test integration: `php test/test_mocks.php`
6. Run static analysis: `./vendor/bin/phpstan analyse`

### Adding a New Test Scenario

1. Create `data/mocks/mockN.json`
2. Add to `test/test_mocks.php`:
   ```php
   'mockN.json' => [
       'expected' => 1234.56,
       'nbe_jours' => 100
   ]
   ```
3. Run: `php test/test_mocks.php`

### Debugging

1. Check logs: `tail -f logs/app.log`
2. Enable debug mode in `.env`: `APP_DEBUG=true`
3. Use debug scripts: `php debug_mock2.php`
4. Check server log: `tail -f logs/server.log`

## Configuration

### Environment Variables (.env)

```bash
APP_ENV=development
APP_DEBUG=true

RATES_CSV_PATH=data/taux.csv
MOCKS_PATH=data/mocks

LOG_PATH=logs/app.log
LOG_LEVEL=debug

# Database Configuration (Eloquent ORM - Multi-Database)
DB_DEFAULT_CONNECTION=mysql

# Primary Database
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=carmf_ij
DB_USER=root
DB_PASSWORD=

# Secondary Database (optional)
DB_SECONDARY_HOST=localhost
DB_SECONDARY_NAME=carmf_legacy
DB_SECONDARY_USER=root
DB_SECONDARY_PASSWORD=

# Analytics Database (optional)
DB_ANALYTICS_HOST=localhost
DB_ANALYTICS_NAME=carmf_analytics
DB_ANALYTICS_USER=root
DB_ANALYTICS_PASSWORD=
```

### Dependency Injection

Services are auto-wired via PHP-DI container defined in `config/dependencies.php`:
- Eloquent ORM is initialized via Capsule Manager
- IJCalculator receives rates from RateRepository and PASS values from PassRepository
- Controllers receive IJCalculator and Logger
- All services use interface-based injection

**IJCalculator Constructor** (6 parameters):
```php
new IJCalculator(
    $rates,      // 1. Rates array (from RateRepository)
    null,        // 2. RateService (optional, uses default)
    null,        // 3. DateService (optional, uses default)
    null,        // 4. TauxService (optional, uses default)
    null,        // 5. AmountService (optional, uses default)
    $passRepo    // 6. PassRepository (optional, loads PASS values from DB)
);
```

When `PassRepository` is injected, PASS values are automatically loaded from database for year-specific class determination.

### Database (Eloquent ORM)

The project uses **Laravel Eloquent ORM** with **multi-database support**:

**Model Classes** (`src/Models/`):
- `IjArret` - Work stoppages (arrêts de travail) - Primary DB
- `IjRecap` - Calculation summaries - Primary DB
- `IjDetailJour` - Daily calculation details - Primary DB
- `IjSinistre` - Claims - Primary DB
- `IjTaux` - Rate records (27-rate system) - Primary DB
- `PlafondSecuSociale` - PASS values by year (class determination) - Primary DB
- `LegacyAdherent` - Legacy system data - Secondary DB
- `AnalyticsLog` - Analytics/logging - Analytics DB

**Available Database Connections**:
- `mysql` (default) - Primary IJ calculation database
- `mysql_secondary` - Secondary/legacy database
- `mysql_analytics` - Analytics and logging database
- `pgsql` - PostgreSQL (optional)
- `sqlite` - SQLite for testing (optional)

**Basic Usage**:
```php
use App\Models\IjRecap;

// Default connection (primary database)
$recap = IjRecap::create([
    'montant_total' => 1500.00,
    'nbe_jours' => 30,
    'date_debut' => '2024-01-01',
    'date_fin' => '2024-01-31',
]);

// Query with relationships
$recap = IjRecap::with('details')->find($id);

// Update
$recap->update(['montant_total' => 1600.00]);
```

**Multi-Database Usage**:
```php
use App\Models\IjRecap;
use App\Models\LegacyAdherent;
use Illuminate\Support\Facades\DB;

// Model with specific connection (defined in model class)
$adherent = LegacyAdherent::find(1);

// Switch connection at runtime
$legacyData = IjRecap::on('mysql_secondary')->get();

// Query Builder with specific connection
$logs = DB::connection('mysql_analytics')
    ->table('calculation_logs')
    ->where('success', true)
    ->get();
```

**See `MULTI_DATABASE_USAGE.md` for complete documentation and examples.**

### Rate Management (Database)

The rate system has been migrated from CSV to **database storage** (`ij_taux` table):

**Database Table Schema**:
```sql
CREATE TABLE `ij_taux` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `taux_a1` float NOT NULL,
  `taux_a2` float NOT NULL,
  `taux_a3` float NOT NULL,
  `taux_b1` float NOT NULL,
  `taux_b2` float NOT NULL,
  `taux_b3` float NOT NULL,
  `taux_c1` float NOT NULL,
  `taux_c2` float NOT NULL,
  `taux_c3` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
```

**Migration Commands**:
```bash
# 1. Create the ij_taux table (use schema above)

# 2. Migrate CSV data to database
php migrate_rates_to_db.php

# 3. Test database integration
php test_rates_db.php

# 4. Verify rates loaded correctly
# The test script will show count and sample data
```

**Using the IjTaux Model**:
```php
use App\Models\IjTaux;

// Get rate for specific year
$rate = IjTaux::getRateForYear(2024);

// Get rate for specific date
$rate = IjTaux::getRateForDate('2024-06-15');

// Get all rates ordered
$rates = IjTaux::getAllRatesOrdered();

// Access rate values
echo $rate->taux_a1;  // Class A, tier 1
echo $rate->taux_b2;  // Class B, tier 2
echo $rate->taux_c3;  // Class C, tier 3
```

**RateRepository Behavior**:
- **Primary**: Loads rates from database (`ij_taux` table)
- **Fallback**: Falls back to CSV if database connection fails
- **Compatibility**: Returns same data structure as legacy CSV format

## Testing Strategy

- **Unit tests**: Each service tested independently using PHPUnit 10
  - `RateServiceTest.php` - Rate lookups and tier determination
  - `DateServiceTest.php` - Date calculations and age brackets
  - `TauxDeterminationServiceTest.php` - Rate number determination
  - `AmountCalculationServiceTest.php` - Payment calculations
  - `RechuteTest.php` - Relapse detection logic
- **Integration tests** (20+ scenarios): Real-world mock data in `data/mocks/`
- **Debug scripts**: Specific edge cases (debug_mock2.php, debug_mock9.php, debug_mock20.php)
- **Static analysis**: PHPStan level 8 for type safety

Run quality checks before committing:
```bash
php run_all_tests.php              # Run all unit tests
./vendor/bin/phpstan analyse       # Run static analysis
```

## Frontend Integration

### Web Interface Files

- `public/index.html` - Main interface
- `public/app.js` - API calls and results display
- `public/calendar_functions.js` - Interactive calendar view

### API Integration

Frontend calls RESTful endpoints:
```javascript
// Example from app.js
fetch('/api/calculations', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(calculationData)
})
```

## Important Notes

- **ORM**: Laravel Eloquent 11 for database operations
- **API Documentation**: OpenAPI 3.0 (Swagger) with zircote/swagger-php 5.x using PHP 8 attributes
- **PSR Standards**: PSR-7 (HTTP messages), PSR-11 (Container), PSR-15 (Middleware)
- **Autoloading**: PSR-4 autoloading via Composer (`App\` → `src/`)
- **No require_once**: All classes loaded via Composer autoloader
- **SOLID Principles**: Single responsibility, dependency injection throughout
- **100% Backward Compatible**: All original business logic preserved from VBA

## Documentation References

**Core Docs**:
- `SLIM_MIGRATION_PLAN.md` - Migration strategy and mapping
- `SWAGGER_API_DOCUMENTATION.md` - OpenAPI/Swagger documentation guide (dynamic generation with PHP 8 attributes)
- `MULTI_DATABASE_USAGE.md` - Multi-database configuration and usage
- `DEPENDENCY_INJECTION_GUIDE.md` - How to use DI with IJCalculator in controllers
- `METHOD_INJECTION_GUIDE.md` - How to inject dependencies into methods (not just constructors)
- `RATE_DB_MIGRATION.md` - Rate system migration from CSV to database
- `PASS_DB_INTEGRATION.md` - PASS values integration with database (class determination)
- `IJCALCULATOR_PASS_INJECTION.md` - PassRepository injection into IJCalculator constructor
- `docs/README.md` - French technical documentation
- Service-specific docs in `docs/[Service].md`

**Business Rules**:
- See service files for inline documentation
- `IJCalculator.php` header (lines 24-100) - Complete 27-rate explanation

## Quick Start

```bash
# 1. Install dependencies
composer install

# 2. Configure databases in .env
# Edit DB_HOST, DB_NAME, DB_USER, DB_PASSWORD

# 3. Create ij_taux table
# Execute the CREATE TABLE statement from "Rate Management" section

# 4. Migrate rate data from CSV to database
php migrate_rates_to_db.php

# 5. Test database connections and rate data
php test_rates_db.php

# 6. Start server
cd public && php -S localhost:8000

# 7. Test endpoint
curl http://localhost:8000/api/mocks

# 8. Run tests
php run_all_tests.php

# 9. Run static analysis
./vendor/bin/phpstan analyse

# 10. Access web interface
open http://localhost:8000/index.html

# 11. View API documentation
open http://localhost:8000/api-docs
```

## Common Issues

**Port already in use**: Change port in server command
**Autoload errors**: Run `composer dump-autoload`
**Rate data not found**: Ensure `ij_taux` table exists and has data (run `php migrate_rates_to_db.php`)
**Database connection failed**: System automatically falls back to CSV (`data/taux.csv`)
**CORS errors**: CorsMiddleware handles all origins (*)
**PHPStan memory errors**: Use `--memory-limit=256M` flag
**PHPStan errors**: Default format is `raw` in phpstan.neon to prevent crashes

## Migration Notes

This project was migrated from standalone PHP to Slim Framework 4. Key changes:

- **Old**: `api.php?endpoint=calculate` → **New**: `POST /api/calculations`
- **Old**: Procedural with global state → **New**: MVC with DI
- **Old**: Manual routing → **New**: Slim routing + middleware
- **Services**: No changes (moved from `Services/` to `src/Services/`)
- **IJCalculator**: Moved to `src/` (namespace `App\IJCalculator`)
- **Tests**: Compatible with both old and new structure
- **Rates**: CSV (`data/taux.csv`) → Database (`ij_taux` table) with CSV fallback

See `SLIM_MIGRATION_PLAN.md` for complete migration details.
