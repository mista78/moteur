# PASS Database Integration

## Overview

PASS (Plafond Annuel de la Sécurité Sociale) values are now loaded from the **`plafond_secu_sociale`** database table instead of being hardcoded. This enables dynamic management of PASS values for determining contribution classes (A/B/C) based on revenue.

---

## Database Schema

```sql
CREATE TABLE `plafond_secu_sociale` (
  `id_plafond_secu_sociale` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date_deb_effet` date DEFAULT NULL,
  `date_fin_effet` date DEFAULT NULL,
  `MT_PASS` int(11) DEFAULT NULL,        -- ✅ Main field used
  `MT_PTSS` int(11) DEFAULT NULL,
  `MT_PMSS` int(11) DEFAULT NULL,
  `MT_PHSS` int(11) DEFAULT NULL,
  `MT_PJSS` int(11) DEFAULT NULL,
  `MT_PHRSS` int(11) DEFAULT NULL,
  `date_de_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_de_dern_maj` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_plafond_secu_sociale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
```

**Key Fields**:
- `date_deb_effet` - Start date (used to extract year)
- `MT_PASS` - Annual ceiling value in euros

---

## Architecture

### Components Created

1. **`PlafondSecuSociale`** Model (`src/Models/PlafondSecuSociale.php`)
   - Eloquent ORM model for `plafond_secu_sociale` table
   - Methods for querying PASS values by year/date

2. **`PassRepository`** (`src/Repositories/PassRepository.php`)
   - Loads PASS values from database
   - Provides fallback to default values if database unavailable

3. **DI Configuration** (`config/dependencies.php`)
   - `PassRepository` registered in container
   - `TauxDeterminationService` auto-configured with database PASS values

---

## Usage

### 1. Using the Model Directly

```php
use App\Models\PlafondSecuSociale;

// Get all PASS values indexed by year
$passByYear = PlafondSecuSociale::getPassValuesByYear();
// Returns: [2024 => 46368, 2023 => 43992, 2022 => 41136, ...]

// Get PASS for specific year
$pass2024 = PlafondSecuSociale::getPassForYear(2024);
// Returns: 46368

// Get PASS for specific date
$pass = PlafondSecuSociale::getPassForDate('2024-06-15');
// Returns: 46368

// Get latest PASS
$latest = PlafondSecuSociale::getLatestPass();
// Returns: 46368
```

### 2. Using PassRepository

```php
use App\Repositories\PassRepository;

$passRepo = new PassRepository();

// Load all PASS values by year
$passValues = $passRepo->loadPassValuesByYear();
// Returns: [2024 => 46368, 2023 => 43992, ...]

// Get PASS for specific year
$pass = $passRepo->getPassForYear(2024);
// Returns: 46368

// Get PASS for specific date
$pass = $passRepo->getPassForDate('2024-06-15');
// Returns: 46368

// Get latest PASS
$latest = $passRepo->getLatestPass();
// Returns: 46368
```

### 3. Using TauxDeterminationService (Automatic)

The service is automatically configured with database PASS values via DI:

```php
use App\Services\TauxDeterminationService;

// Injected automatically in controllers
public function calculate(
    ServerRequestInterface $request,
    ResponseInterface $response,
    TauxDeterminationService $tauxService  // ✅ Pre-loaded with PASS values
): ResponseInterface {
    // Determine class based on revenue
    $classe = $tauxService->determineClasse(
        $revenuNMoins2 = 50000,
        $dateOuvertureDroits = '2024-01-01',
        $taxeOffice = false,
        $year = 2024
    );
    // Returns: 'B' (because 50000 is between PASS and 3*PASS)
}
```

---

## Class Determination Logic

Based on revenue (N-2) and PASS value:

| Revenue Range | Class |
|---------------|-------|
| `< PASS` | **A** |
| `>= PASS AND <= 3×PASS` | **B** |
| `> 3×PASS` | **C** |

**Example with PASS = 46368 € (2024)**:
- Revenue 30,000 € → Class **A** (< 46,368)
- Revenue 50,000 € → Class **B** (between 46,368 and 139,104)
- Revenue 150,000 € → Class **C** (> 139,104)

---

## Testing

### Test Script

Run the comprehensive test:

```bash
php test_pass_db.php
```

**Tests performed**:
1. Database connection
2. Table existence
3. Record count
4. Get PASS by year
5. Get PASS by date
6. Get latest PASS
7. PassRepository integration
8. TauxDeterminationService integration
9. Class determination with sample revenues
10. Display all PASS records

### Expected Output

```
===================================
Testing PASS Database Integration
===================================

Test 1: Database Connection
----------------------------
✓ Database connection successful
  Database: carmf_ij

Test 2: Table Existence
-----------------------
✓ Table 'plafond_secu_sociale' exists

Test 3: PASS Record Count
-------------------------
✓ Found 10 PASS record(s) in database

Test 4: Get PASS Values by Year
--------------------------------
✓ Retrieved PASS values for 10 year(s)

  Year → PASS Value:
  2024 → 46368 €
  2023 → 43992 €
  2022 → 41136 €
  ...

===================================
All tests completed! ✓
===================================
```

---

## Fallback Mechanism

If database is unavailable, `PassRepository` falls back to default values:

```php
private function getDefaultPassValues(): array
{
    return [
        2024 => 46368,
        2023 => 43992,
        2022 => 41136,
        2021 => 41136,
        2020 => 41136,
        // ... more years
    ];
}
```

This ensures the application continues working even if database connection fails.

---

## Sample Data

Insert sample PASS data:

```sql
INSERT INTO `plafond_secu_sociale`
  (`date_deb_effet`, `date_fin_effet`, `MT_PASS`)
VALUES
  ('2024-01-01', '2024-12-31', 46368),
  ('2023-01-01', '2023-12-31', 43992),
  ('2022-01-01', '2022-12-31', 41136),
  ('2021-01-01', '2021-12-31', 41136),
  ('2020-01-01', '2020-12-31', 41136),
  ('2019-01-01', '2019-12-31', 40524),
  ('2018-01-01', '2018-12-31', 39732),
  ('2017-01-01', '2017-12-31', 39228),
  ('2016-01-01', '2016-12-31', 38616),
  ('2015-01-01', '2015-12-31', 38040);
```

---

## Integration with IJCalculator

The `TauxDeterminationService` is used by `IJCalculator` for class determination:

```php
// In IJCalculator::determineClasse()
$tauxService = new TauxDeterminationService();
$tauxService->setPassValuesByYear($this->passValuesByYear);

$classe = $tauxService->determineClasse(
    $revenuNMoins2,
    $dateOuvertureDroits,
    $taxeOffice,
    $year
);

return $classe; // 'A', 'B', or 'C'
```

---

## Configuration in Dependencies

**File**: `config/dependencies.php`

```php
// PASS Repository
PassRepository::class => function (ContainerInterface $c) {
    return new PassRepository();
},

// Taux Determination Service (with PASS values from database)
TauxDeterminationService::class => function (ContainerInterface $c) {
    $passRepo = $c->get(PassRepository::class);
    $service = new TauxDeterminationService();

    // Load PASS values from database
    $passValues = $passRepo->loadPassValuesByYear();
    $service->setPassValuesByYear($passValues);

    return $service;
},
```

---

## Benefits

✅ **Dynamic Management**: Update PASS values in database without code changes
✅ **Historical Tracking**: Maintain PASS history for all years
✅ **Automatic Loading**: DI container loads values on application startup
✅ **Fallback Support**: Works even if database unavailable
✅ **Type Safety**: Eloquent ORM provides type casting
✅ **Easy Testing**: Test script verifies integration

---

## API Example

**Endpoint**: `POST /api/calculations/classe`

**Request**:
```json
{
  "revenu_n_moins_2": 50000,
  "date_ouverture_droits": "2024-01-01",
  "taxe_office": false
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "classe": "B",
    "revenu": 50000,
    "pass_value": 46368,
    "year": 2024
  }
}
```

The PASS value (46368) is automatically loaded from the database for year 2024!

---

## Troubleshooting

### No PASS records found

**Solution**: Insert data using the SQL above

### Wrong PASS value returned

**Check**: Ensure `date_deb_effet` has correct year
```sql
SELECT YEAR(date_deb_effet) as year, MT_PASS
FROM plafond_secu_sociale
ORDER BY date_deb_effet DESC;
```

### Database connection error

**Fallback**: System uses default PASS values from `PassRepository::getDefaultPassValues()`

---

## Files Created/Modified

**New Files**:
- `src/Models/PlafondSecuSociale.php` - Eloquent model
- `src/Repositories/PassRepository.php` - Repository
- `test_pass_db.php` - Test script
- `PASS_DB_INTEGRATION.md` - This documentation

**Modified Files**:
- `config/dependencies.php` - Added PassRepository and TauxDeterminationService

**Unchanged Files**:
- `src/Services/TauxDeterminationService.php` - Already had support for `setPassValuesByYear()`
- All other services and controllers

---

## Related Documentation

- `RATE_DB_MIGRATION.md` - Similar pattern for rate data migration
- `MULTI_DATABASE_USAGE.md` - Multi-database configuration
- `CLAUDE.md` - Complete project documentation

---

## Summary

PASS values are now loaded from `plafond_secu_sociale` table:
- **Model**: `PlafondSecuSociale`
- **Repository**: `PassRepository`
- **Service**: `TauxDeterminationService` (auto-configured)
- **Format**: `[year => pass_value, ...]`

The system extracts the year from `date_deb_effet` and uses `MT_PASS` to build an associative array for class determination! 🚀
