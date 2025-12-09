# IJCalculator PassRepository Injection

## Overview

The `IJCalculator` constructor has been updated to accept `PassRepository` as a dependency, enabling automatic loading of PASS (Plafond Annuel de la Sécurité Sociale) values from the database for class determination.

---

## Updated Constructor

### Signature

```php
public function __construct(
    array|string $csvPath = [],
    ?RateServiceInterface $rateService = null,
    ?DateCalculationInterface $dateService = null,
    ?TauxDeterminationInterface $tauxService = null,
    ?AmountCalculationInterface $amountService = null,
    ?PassRepository $passRepository = null  // ✅ NEW: 6th parameter
)
```

### Parameters

| Position | Parameter | Type | Description |
|----------|-----------|------|-------------|
| 1 | `$csvPath` | `array\|string` | Rates array or CSV path |
| 2 | `$rateService` | `?RateServiceInterface` | Optional rate service |
| 3 | `$dateService` | `?DateCalculationInterface` | Optional date service |
| 4 | `$tauxService` | `?TauxDeterminationInterface` | Optional taux service |
| 5 | `$amountService` | `?AmountCalculationInterface` | Optional amount service |
| 6 | `$passRepository` | `?PassRepository` | **NEW**: Optional PASS repository |

---

## How It Works

### With PassRepository (Recommended)

When `PassRepository` is injected:

```php
use App\IJCalculator;
use App\Repositories\PassRepository;
use App\Repositories\RateRepository;

$rateRepo = new RateRepository('data/taux.csv');
$passRepo = new PassRepository();

$calculator = new IJCalculator(
    $rateRepo->loadRates(),  // Rates
    null,                     // Use default RateService
    null,                     // Use default DateService
    null,                     // Use default TauxService
    null,                     // Use default AmountService
    $passRepo                 // ✅ Inject PassRepository
);

// PASS values automatically loaded from database!
// TauxService configured with [year => pass_value] array
```

**Behavior**:
1. `PassRepository` loads PASS values: `[2024 => 46368, 2023 => 43992, ...]`
2. Values injected into `TauxDeterminationService` via `setPassValuesByYear()`
3. Class determination uses correct PASS for each year automatically

### Without PassRepository (Fallback)

When `PassRepository` is NOT provided:

```php
$calculator = new IJCalculator($rateRepo->loadRates());

// Uses default PASS value: 47000
// Can be changed with: $calculator->setPassValue(46368)
```

**Behavior**:
1. Uses default PASS value: `47000`
2. Same PASS value for all years
3. Can be overridden manually with `setPassValue()`

---

## Dependency Injection Configuration

**File**: `config/dependencies.php`

```php
// IJ Calculator (with database PASS values)
IJCalculator::class => function (ContainerInterface $c) {
    $rateRepo = $c->get(RateRepository::class);
    $passRepo = $c->get(PassRepository::class);

    // Inject repositories: rates from RateRepo, PASS values from PassRepo
    return new IJCalculator(
        $rateRepo->loadRates(),  // $csvPath (rates array)
        null,                     // $rateService (use default)
        null,                     // $dateService (use default)
        null,                     // $tauxService (use default)
        null,                     // $amountService (use default)
        $passRepo                 // $passRepository (inject for PASS values)
    );
},
```

---

## Usage in Controllers

### Automatic Injection (Recommended)

```php
use App\IJCalculator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CalculationController
{
    // Constructor injection
    public function __construct(
        private IJCalculator $calculator  // ✅ Pre-configured with DB PASS values
    ) {}

    public function calculate(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $input = $request->getParsedBody();

        // Automatically uses correct PASS for the year
        $classe = $this->calculator->determineClasse(
            $revenuNMoins2 = 50000,
            $dateOuvertureDroits = '2024-01-01',
            $taxeOffice = false
        );
        // Returns: 'B' (using PASS=46368 from database)

        // ...
    }
}
```

### Method Injection

```php
public function calculate(
    ServerRequestInterface $request,
    ResponseInterface $response,
    IJCalculator $calculator  // ✅ Pre-configured with DB PASS values
): ResponseInterface {
    // Use directly - PASS values already loaded from database
    $classe = $calculator->determineClasse(50000, '2024-01-01', false);
    // Returns correct class based on database PASS values
}
```

---

## Class Determination Example

With PASS values from database:

```php
// Database contains:
// 2024 → 46368 €
// 2023 → 43992 €
// 2022 → 41136 €

$calculator = new IJCalculator($rates, null, null, null, null, $passRepo);

// Year 2024 (PASS = 46368)
$calculator->determineClasse(30000, '2024-01-01', false);  // → 'A' (< 46368)
$calculator->determineClasse(50000, '2024-01-01', false);  // → 'B' (between 46368 and 139104)
$calculator->determineClasse(150000, '2024-01-01', false); // → 'C' (> 139104)

// Year 2023 (PASS = 43992)
$calculator->determineClasse(30000, '2023-01-01', false);  // → 'A' (< 43992)
$calculator->determineClasse(50000, '2023-01-01', false);  // → 'B' (between 43992 and 131976)
$calculator->determineClasse(150000, '2023-01-01', false); // → 'C' (> 131976)
```

**Key Point**: The correct PASS is automatically selected based on the year!

---

## Testing

### Test Script

Run the test:

```bash
php test_ijcalculator_pass.php
```

**Tests performed**:
1. Create IJCalculator with PassRepository
2. Verify PASS values loaded from database
3. Test class determination with various revenues
4. Compare database vs manual PASS setting
5. Display PASS thresholds for class boundaries

### Expected Output

```
============================================
Testing IJCalculator with PassRepository
============================================

Test 1: Create IJCalculator with PassRepository
------------------------------------------------
✓ IJCalculator created successfully with PassRepository

Test 2: Verify PASS Values Loaded
----------------------------------
✓ Loaded 10 PASS value(s) from database

  Sample PASS values:
    2024: 46368 €
    2023: 43992 €
    2022: 41136 €

Test 3: Class Determination
----------------------------
Testing class determination with database PASS values:

  ✓ Year 2024, Revenue 30000 € → Class A (expected A, PASS=46368)
  ✓ Year 2024, Revenue 50000 € → Class B (expected B, PASS=46368)
  ✓ Year 2024, Revenue 150000 € → Class C (expected C, PASS=46368)
  ...

============================================
All tests completed! ✓
============================================
```

---

## Migration Guide

### Before (Manual PASS)

```php
// Old way - manual PASS setting
$calculator = new IJCalculator($rates);
$calculator->setPassValue(46368);  // Manual for all years
```

**Problem**: Same PASS for all years, requires manual updates

### After (Database PASS)

```php
// New way - automatic PASS from database
$calculator = new IJCalculator($rates, null, null, null, null, $passRepo);
// PASS values automatically loaded for each year!
```

**Benefits**:
- ✅ Correct PASS for each year automatically
- ✅ No manual updates needed
- ✅ Centralized management in database
- ✅ Historical accuracy maintained

---

## Backward Compatibility

The update is **100% backward compatible**:

### Old Code (Still Works)

```php
// Without PassRepository - uses default PASS
$calculator = new IJCalculator($rates);
$calculator->setPassValue(46368);
```

### New Code (Recommended)

```php
// With PassRepository - uses database PASS
$calculator = new IJCalculator($rates, null, null, null, null, $passRepo);
// No need for setPassValue()
```

---

## Benefits

✅ **Dynamic PASS Loading**: Automatically loads from database
✅ **Year-Specific Values**: Correct PASS for each year
✅ **No Manual Updates**: Centralized in database
✅ **Backward Compatible**: Old code still works
✅ **DI Integration**: Seamlessly integrated with container
✅ **Historical Accuracy**: Maintains PASS history for all years

---

## Constructor Logic Flow

```
1. Create services (RateService, DateService, TauxService)
   ↓
2. Check if PassRepository provided?
   ├─ YES → Load PASS by year from database
   │        [2024 => 46368, 2023 => 43992, ...]
   │        ↓
   │        Call: tauxService->setPassValuesByYear($passValues)
   │
   └─ NO → Use default single PASS value (47000)
            ↓
            Call: tauxService->setPassValue($this->passValue)
   ↓
3. Create AmountCalculationService with all dependencies
   ↓
4. IJCalculator ready to use!
```

---

## Related Files

**Modified**:
- `src/IJCalculator.php` - Updated constructor with PassRepository parameter
- `config/dependencies.php` - Injects PassRepository into IJCalculator

**Created**:
- `src/Models/PlafondSecuSociale.php` - Eloquent model for PASS table
- `src/Repositories/PassRepository.php` - Repository for loading PASS values
- `test_ijcalculator_pass.php` - Test script for verification

**Documentation**:
- `PASS_DB_INTEGRATION.md` - Complete PASS integration guide
- `IJCALCULATOR_PASS_INJECTION.md` - This document

---

## Summary

The `IJCalculator` constructor now accepts `PassRepository` as the **6th parameter**:

```php
new IJCalculator(
    $rates,      // 1. Rates array
    null,        // 2. RateService (optional)
    null,        // 3. DateService (optional)
    null,        // 4. TauxService (optional)
    null,        // 5. AmountService (optional)
    $passRepo    // 6. PassRepository (NEW!)
);
```

When provided, it automatically loads PASS values from database and configures `TauxDeterminationService` for accurate class determination based on revenue and year! 🚀
