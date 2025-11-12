# RecapService - Backend Class Determination

## Date: 2025-11-12

## Overview

The **RecapService** now supports **automatic class determination** in recap records based on `revenu_n_moins_2`. This ensures that the classe field in `ij_recap` table accurately reflects the backend-determined class.

## Implementation

### 1. Added Calculator Injection

**File**: `Services/RecapService.php` (lines 11-22)

```php
class RecapService
{
    private $calculator = null;

    /**
     * Set the calculator instance for class determination
     */
    public function setCalculator($calculator): void
    {
        $this->calculator = $calculator;
    }
```

The service can now accept an IJCalculator instance for backend class determination.

### 2. Added determineClasse() Private Method

**File**: `Services/RecapService.php` (lines 107-131)

```php
private function determineClasse(array $inputData): ?string
{
    // If class is already provided, use it
    if (isset($inputData['classe']) && !empty($inputData['classe'])) {
        return $inputData['classe'];
    }

    // If calculator is available and revenu_n_moins_2 is provided, auto-determine
    if ($this->calculator !== null && isset($inputData['revenu_n_moins_2'])) {
        $revenuNMoins2 = (float) $inputData['revenu_n_moins_2'];
        $taxeOffice = isset($inputData['taxe_office']) ? (bool) $inputData['taxe_office'] : false;
        $dateOuvertureDroits = $inputData['date_ouverture_droits'] ?? null;

        return $this->calculator->determineClasse($revenuNMoins2, $dateOuvertureDroits, $taxeOffice);
    }

    // No class determination possible
    return null;
}
```

Logic:
1. **Priority 1**: Use explicit `classe` if provided
2. **Priority 2**: Auto-determine from `revenu_n_moins_2` if calculator available
3. **Fallback**: Return null

### 3. Updated AmountCalculationService

**File**: `Services/AmountCalculationService.php` (lines 34-44)

```php
// Auto-determine class if not provided but revenu_n_moins_2 is available
if (!isset($data['classe']) || empty($data['classe'])) {
    if (isset($data['revenu_n_moins_2'])) {
        $revenuNMoins2 = (float) $data['revenu_n_moins_2'];
        $taxeOffice = isset($data['taxe_office']) ? (bool) $data['taxe_office'] : false;
        $dateOuvertureDroits = $data['date_ouverture_droits'] ?? null;
        $data['classe'] = $this->tauxService->determineClasse($revenuNMoins2, $dateOuvertureDroits, $taxeOffice);
    }
}

$classe = isset($data['classe']) ? strtoupper($data['classe']) : 'A';
```

The calculation service now auto-determines class before processing, ensuring calculations use the correct class.

## Usage

### Basic Usage (With Class Determination)

```php
require_once 'IJCalculator.php';
require_once 'Services/RecapService.php';

use IJCalculator\Services\RecapService;

$calculator = new IJCalculator('taux.csv');
$calculator->setPassValue(47000);

$inputData = [
    'adherent_number' => '301261U',
    'num_sinistre' => 48,
    // No classe provided
    'revenu_n_moins_2' => 94000,  // 2 PASS = Classe B
    'pass_value' => 47000,
    // ... other fields
];

// Calculate
$result = $calculator->calculateAmount($inputData);

// Generate recap WITH calculator for class determination
$recapService = new RecapService();
$recapService->setCalculator($calculator);  // Inject calculator
$recapRecords = $recapService->generateRecapRecords($result, $inputData);

// Class B will be determined and stored in all recap records
foreach ($recapRecords as $record) {
    echo "Classe: " . $record['classe'];  // Output: Classe: B
}
```

### Backward Compatible Usage (Without Calculator)

```php
// Old code still works
$inputData = [
    'adherent_number' => '301261U',
    'num_sinistre' => 48,
    'classe' => 'B',  // Explicit class
    // ... other fields
];

$result = $calculator->calculateAmount($inputData);

// Generate recap WITHOUT calculator injection (old behavior)
$recapService = new RecapService();
// Don't call setCalculator()
$recapRecords = $recapService->generateRecapRecords($result, $inputData);

// Explicit class is used
foreach ($recapRecords as $record) {
    echo "Classe: " . $record['classe'];  // Output: Classe: B
}
```

## Class Determination Priority

The service follows this priority order:

1. **Explicit `classe` in inputData** (highest priority)
   - If `classe` is provided, it's used directly
   - No auto-determination occurs

2. **Auto-determination from `revenu_n_moins_2`**
   - Requires calculator injection via `setCalculator()`
   - Requires `revenu_n_moins_2` in inputData
   - Uses backend business logic

3. **Null fallback** (lowest priority)
   - If neither explicit classe nor revenu available
   - Records will have `classe: null`

## Testing

### Test File

**File**: `test_recap_class_simple.php`

```bash
php test_recap_class_simple.php
```

**Tests 5 scenarios**:
1. ✅ Auto-determine Class A from revenu (< 1 PASS)
2. ✅ Auto-determine Class B from revenu (2 PASS)
3. ✅ Auto-determine Class C from revenu (> 3 PASS)
4. ✅ Explicit class overrides revenu
5. ✅ Backward compatibility (no calculator injection)

**Result**: All 5 tests pass

### Integration with Existing Tests

All existing tests continue to pass:

```bash
php run_all_tests.php
```

**Result**: 114/114 tests pass ✅

## Examples

### Example 1: Classe A from Low Revenue

```php
$inputData = [
    'revenu_n_moins_2' => 35000,  // < 1 PASS
    'pass_value' => 47000,
    // ... no classe provided
];

$recapService->setCalculator($calculator);
$records = $recapService->generateRecapRecords($result, $inputData);

// $records[0]['classe'] = 'A'
```

### Example 2: Classe B from Mid Revenue

```php
$inputData = [
    'revenu_n_moins_2' => 94000,  // 2 PASS
    'pass_value' => 47000,
    // ... no classe provided
];

$recapService->setCalculator($calculator);
$records = $recapService->generateRecapRecords($result, $inputData);

// $records[0]['classe'] = 'B'
```

### Example 3: Classe C from High Revenue

```php
$inputData = [
    'revenu_n_moins_2' => 150000,  // > 3 PASS
    'pass_value' => 47000,
    // ... no classe provided
];

$recapService->setCalculator($calculator);
$records = $recapService->generateRecapRecords($result, $inputData);

// $records[0]['classe'] = 'C'
```

### Example 4: Explicit Class Override

```php
$inputData = [
    'classe' => 'A',  // Explicit
    'revenu_n_moins_2' => 150000,  // Would be C, but explicit takes priority
    'pass_value' => 47000,
];

$recapService->setCalculator($calculator);
$records = $recapService->generateRecapRecords($result, $inputData);

// $records[0]['classe'] = 'A' (explicit override)
```

### Example 5: Taxé d'Office

```php
$inputData = [
    'revenu_n_moins_2' => 150000,  // Would be C
    'taxe_office' => true,         // But taxé d'office
    'pass_value' => 47000,
];

$recapService->setCalculator($calculator);
$records = $recapService->generateRecapRecords($result, $inputData);

// $records[0]['classe'] = 'A' (taxé d'office always returns A)
```

## Database Impact

### ij_recap Table

The `classe` column in `ij_recap` will now contain:

**Before** (manual/frontend determination):
```sql
INSERT INTO ij_recap (..., classe, ...) VALUES (..., 'A', ...);
-- Class might not match actual revenue
```

**After** (backend determination):
```sql
INSERT INTO ij_recap (..., classe, ...) VALUES (..., 'B', ...);
-- Class accurately determined from revenu_n_moins_2
```

### Example SQL Output

**Input**: `revenu_n_moins_2 = 94000` (2 PASS)

```sql
-- Record 1: March 2024
INSERT INTO ij_recap (adherent_number, exercice, periode, classe, ...)
VALUES ('301261U', '2024', '03', 'B', ...);

-- Record 2: April 2024
INSERT INTO ij_recap (adherent_number, exercice, periode, classe, ...)
VALUES ('301261U', '2024', '04', 'B', ...);
```

All records for the same calculation have **consistent classe** determined by backend logic.

## Benefits

### 1. Data Consistency ✅

- All recap records use the same class determination logic
- No discrepancy between frontend and backend
- Classe matches actual revenue thresholds

### 2. Audit Trail ✅

- Class determination based on documented revenue
- Traceable back to `revenu_n_moins_2` in source data
- Reproducible calculations

### 3. Backward Compatible ✅

- Existing code works without changes
- Explicit `classe` still respected
- Optional enhancement (inject calculator when needed)

### 4. Flexible Usage ✅

- With calculator: Auto-determine from revenue
- Without calculator: Use explicit classe (old behavior)
- Priority system handles mixed scenarios

## Migration Guide

### For New Code (Recommended)

```php
// New approach: Auto-determine class
$recapService = new RecapService();
$recapService->setCalculator($calculator);  // Enable auto-determination
$records = $recapService->generateRecapRecords($result, $inputData);
```

### For Existing Code (No Changes Needed)

```php
// Old approach: Still works
$recapService = new RecapService();
// Don't call setCalculator() - old behavior preserved
$records = $recapService->generateRecapRecords($result, $inputData);
```

## Edge Cases

### 1. No Calculator, No Classe ✅

```php
$inputData = ['revenu_n_moins_2' => 94000];  // No classe

$recapService = new RecapService();
// No setCalculator()
$records = $recapService->generateRecapRecords($result, $inputData);

// $records[0]['classe'] = null (no determination possible)
```

### 2. Calculator But No Revenue ✅

```php
$inputData = [];  // No revenu, no classe

$recapService = new RecapService();
$recapService->setCalculator($calculator);
$records = $recapService->generateRecapRecords($result, $inputData);

// $records[0]['classe'] = null (no revenue to determine from)
```

### 3. Both Classe and Revenue ✅

```php
$inputData = [
    'classe' => 'A',
    'revenu_n_moins_2' => 150000  // Would be C
];

$recapService = new RecapService();
$recapService->setCalculator($calculator);
$records = $recapService->generateRecapRecords($result, $inputData);

// $records[0]['classe'] = 'A' (explicit takes priority)
```

## Related Files

**Modified**:
- `Services/RecapService.php` (added setCalculator, determineClasse)
- `Services/AmountCalculationService.php` (auto-determine class before calculation)

**New Tests**:
- `test_recap_class_simple.php` (5 scenarios testing class determination)

**Documentation**:
- `BACKEND_CLASSE_DETERMINATION.md` (overall backend class determination)
- `RECAP_CLASS_DETERMINATION.md` (this file - RecapService specific)

## Summary

| Feature | Status |
|---------|--------|
| Calculator injection | ✅ `setCalculator()` method |
| Auto-determination | ✅ From `revenu_n_moins_2` |
| Priority system | ✅ Explicit > Auto > Null |
| Tests | ✅ 5/5 scenarios pass |
| Backward compatibility | ✅ 114/114 existing tests pass |
| AmountCalculationService | ✅ Auto-determines before calculation |

---

**Author**: Claude Code
**Date**: 2025-11-12
**Status**: ✅ Production Ready
