# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

French medical professional sick leave benefits calculator ("Indemnités Journalières" - IJ) for CARMF (Caisse Autonome de Retraite des Médecins de France). Built with **Slim Framework 4** using modern PHP architecture with SOLID principles.

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
php run_all_tests.php                       # Runs all unit tests (~56 tests)
./vendor/bin/phpunit                        # Direct PHPUnit execution
./vendor/bin/phpunit --testdox              # Test documentation format

# Run specific test suites
./vendor/bin/phpunit test/RateServiceTest.php              # 13 tests - rate lookups
./vendor/bin/phpunit test/DateServiceTest.php              # 17 tests - date calculations
./vendor/bin/phpunit test/TauxDeterminationServiceTest.php # 16 tests - taux logic
./vendor/bin/phpunit test/AmountCalculationServiceTest.php # 11 tests - amount calculations
./vendor/bin/phpunit test/RechuteTest.php                  # 9 tests - relapse detection

# Run with code coverage (requires Xdebug)
./vendor/bin/phpunit --coverage-html coverage/

# Integration tests (mock scenarios - uses legacy jest-php)
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
│   │   └── RateRepository.php          # CSV rate loading
│   │
│   ├── Middlewares/
│   │   ├── CorsMiddleware.php          # CORS headers
│   │   └── JsonBodyParserMiddleware.php # JSON parsing
│   │
│   └── Helpers/
│       └── ResponseFormatter.php       # Standardized responses
│
├── config/
│   ├── dependencies.php              # DI Container
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
└── composer.json                     # Dependencies (Slim 4, PHPUnit 10, PHPStan 2.1)

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

2. **Add controller method** in `src/Controllers/CalculationController.php`:
   ```php
   public function newMethod(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
   {
       // Implementation
       return ResponseFormatter::success($response, $data);
   }
   ```

3. **Test** with curl or frontend

### Modifying Business Logic

**IMPORTANT**: Business logic lives in Services, not Controllers

1. Update service in `src/Services/`
2. Update interface if method signature changes
3. Run unit tests: `php Tests/[Service]Test.php`
4. Run full suite: `php run_all_tests.php`
5. Test integration: `php Tests/test_mocks.php`

### Adding a New Test Scenario

1. Create `data/mocks/mockN.json`
2. Add to `Tests/test_mocks.php`:
   ```php
   'mockN.json' => [
       'expected' => 1234.56,
       'nbe_jours' => 100
   ]
   ```
3. Run: `php run_all_tests.php`

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
```

### Dependency Injection

Services are auto-wired via PHP-DI container defined in `config/dependencies.php`:
- IJCalculator receives rates from RateRepository
- Controllers receive IJCalculator and Logger
- All services use interface-based injection

## Testing Strategy

- **Unit tests** (~56 tests): Each service tested independently using PHPUnit
- **Integration tests** (20+ scenarios): Real-world mock data in data/mocks/
- **Debug scripts**: Specific edge cases (debug_mock2.php, debug_mock9.php, etc.)
- **Static analysis**: PHPStan level 8 for type safety
- **Test framework**: PHPUnit 10 with strict typing and comprehensive assertions

Run quality checks before committing:
```bash
php run_all_tests.php              # Run all tests with PHPUnit
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

- **PSR Standards**: PSR-7 (HTTP messages), PSR-11 (Container), PSR-15 (Middleware)
- **Autoloading**: PSR-4 autoloading via Composer (`App\` → `src/`)
- **No require_once**: All classes loaded via Composer autoloader
- **SOLID Principles**: Single responsibility, dependency injection throughout
- **100% Backward Compatible**: All original business logic preserved from VBA

## Documentation References

**Core Docs**:
- `SLIM_MIGRATION_PLAN.md` - Migration strategy and mapping
- `docs/README.md` - French technical documentation
- Service-specific docs in `docs/[Service].md`

**Business Rules**:
- See service files for inline documentation
- `IJCalculator.php` header (lines 24-100) - Complete 27-rate explanation

## Quick Start

```bash
# 1. Install dependencies
composer install

# 2. Start server
cd public && php -S localhost:8000

# 3. Test endpoint
curl http://localhost:8000/api/mocks

# 4. Run tests
php run_all_tests.php

# 5. Run static analysis
./vendor/bin/phpstan analyse

# 6. Access web interface
open http://localhost:8000/index.html
```

## Common Issues

**Port already in use**: Change port in server command
**Autoload errors**: Run `composer dump-autoload`
**Rate file not found**: Check `RATES_CSV_PATH` in `.env`
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

See `SLIM_MIGRATION_PLAN.md` for complete migration details.
