# Date Effet Bug Fix - Rechute Detection

## Date: 2025-11-06

## Bug Description

**Issue**: When the first arret used `date_deb_droit` (pre-calculated date d'effet), subsequent rechute arrets calculated their date d'effet **before** the arret start date.

**Example (mock.json)**:
- Arret 1: Start 2023-10-24, has `date_deb_droit = 2024-01-22` ✓
- Arret 2: Start **2024-09-02**, has `rechute-line = 15`
  - **BEFORE FIX**: Date effet = **2024-07-15** ❌ (before arret start!)
  - **AFTER FIX**: Date effet = **2024-09-16** ✓ (arret start + 14 days)

## Root Cause

### Problem 1: Missing `$arretDroits` Increment

**File**: `Services/DateService.php`, line 274-282

When an arret uses `date_deb_droit`, the code set the date-effet but **never incremented** `$arretDroits`:

```php
// BEFORE (bug)
if (isset($currentData['date_deb_droit']) && !empty($currentData['date_deb_droit']) && $currentData['date_deb_droit'] !== '0000-00-00') {
    $currentData['date-effet'] = $currentData['date_deb_droit'];
    $nbJours = $newNbJours;
    // Missing: $arretDroits++;
    $increment++;
    continue;
}
```

**Impact**:
- `$arretDroits` stayed at 0
- Second arret entered "first arret" logic (`if ($arretDroits === 0)`)
- Calculated: `$lessDate = 90 - (147 - 8) = -49 days`
- Date effet: `2024-09-02 + (-49 days) = 2024-07-15` (incorrect!)

### Problem 2: Rechute-line Detection

**File**: `Services/DateService.php`, line 208

The code only recognized `rechute-line = 1` as rechute:

```php
// BEFORE (bug)
if (isset($currentArret['rechute-line']) && ...) {
    return (int)$currentArret['rechute-line'] === 1;  // Only 1 = rechute
}
```

**Impact**:
- Mock.json has `rechute-line = 15` (meaning 15-day delay)
- Was treated as NOT rechute
- Entered wrong calculation path

## Solution

### Fix 1: Increment `$arretDroits` When Using `date_deb_droit`

**File**: `Services/DateService.php`, lines 274-283

```php
// AFTER (fixed)
if (isset($currentData['date_deb_droit']) && !empty($currentData['date_deb_droit']) && $currentData['date_deb_droit'] !== '0000-00-00') {
    $currentData['date-effet'] = $currentData['date_deb_droit'];
    $nbJours = $newNbJours;
    $arretDroits++; // ✅ Mark that rights have been opened
    $increment++;
    continue;
}
```

**Also applied to forced date-effet** (line 289):
```php
if (isset($currentData['date-effet-forced'])) {
    $currentData['date-effet'] = $currentData['date-effet-forced'];
    $nbJours = $newNbJours;
    $arretDroits++; // ✅ Mark that rights have been opened
    $increment++;
    continue;
}
```

### Fix 2: Accept Any Positive `rechute-line` Value

**File**: `Services/DateService.php`, line 209

```php
// AFTER (fixed)
// Note: rechute-line peut être 1 (rechute) ou un nombre > 1 (ex: 15 pour délai de 15 jours)
if (isset($currentArret['rechute-line']) && ...) {
    return (int)$currentArret['rechute-line'] > 0;  // ✅ Any positive value = rechute
}
```

## Test Results

### Before Fix

```bash
$ php test_date_effet_fix.php

Arret 2 (rechute):
  Start: 2024-09-02
  Date effet: 2024-07-15
  ❌ FAIL: Date effet is before arret start!
```

### After Fix

```bash
$ php test_date_effet_fix.php

Arret 2 (rechute):
  Start: 2024-09-02
  Date effet: 2024-09-16
  ✅ PASS: Date effet (2024-09-16) is >= arret start (2024-09-02)

✅ Amount matches expected: 750.60€
```

## Verification

Created test file: `test_date_effet_fix.php`

**What it tests**:
1. ✅ First arret uses `date_deb_droit` correctly
2. ✅ Second arret (rechute) has date effet >= arret start
3. ✅ Total amount matches expected value (750.60€)

**Run test**:
```bash
php test_date_effet_fix.php
```

## Impact on Calculations

### Rechute Detection

With both fixes, rechute arrets are now properly:
1. **Detected** as rechute (when `rechute-line > 0`)
2. **Date effet calculated** as: `arret_start + 14 days` (15th day)
3. **Rights opened** counted correctly via `$arretDroits++`

### Date Effet Calculation Flow

```
Arret with date_deb_droit:
  ├─ Use date_deb_droit as date-effet
  ├─ Increment $arretDroits ✅ (NEW)
  └─ Continue to next arret

Next arret (rechute):
  ├─ $arretDroits > 0 → Enter rechute logic
  ├─ Detect rechute (rechute-line > 0) ✅ (FIXED)
  ├─ Calculate: arret_start + 14 days
  └─ Set date-effet >= arret_start ✅
```

## Related Business Rules

### `$arretDroits` Counter

**Purpose**: Track if rights have been opened (droits ouverts)

**When incremented**:
- ✅ Normal calculation: When `$newNbJours > 90` (line 329)
- ✅ **[NEW]** When using `date_deb_droit` (line 277)
- ✅ **[NEW]** When using `date-effet-forced` (line 289)

**Usage**:
- `$arretDroits === 0` → First pathology, need 90-day threshold
- `$arretDroits > 0` → Rights already opened, can be rechute

### Rechute-line Values

**Interpretation**:
- `0` = Not a rechute
- `1` = Rechute (standard)
- `15` = Rechute with 15-day delay indication
- Any `> 0` = Rechute ✅ (NEW)

## Files Modified

1. **Services/DateService.php**:
   - Line 277: Added `$arretDroits++` for `date_deb_droit`
   - Line 289: Added `$arretDroits++` for `date-effet-forced`
   - Line 209: Changed rechute detection to `> 0` instead of `=== 1`

## Test Coverage

**Integration test**: `test_date_effet_fix.php`
- ✅ Mock.json (2 arrets, rechute scenario)
- ✅ Verifies date effet >= arret start
- ✅ Verifies final amount (750.60€)

**Affected mocks**:
- `mock.json`: First arret with `date_deb_droit`, second with `rechute-line = 15`

## Backward Compatibility

✅ **Fully compatible**: All existing tests pass with same results

**Changes only affect**:
- Arrets using `date_deb_droit` or `date-effet-forced` followed by rechute
- Arrets with `rechute-line` values other than 0 or 1

**Does NOT affect**:
- Normal date effet calculation (90-day threshold)
- Arrets without `date_deb_droit`
- Standard rechute detection (within 1 year)

## Summary

**Problem**: Date effet calculated before arret start for rechute arrets
**Cause**: `$arretDroits` not incremented when using pre-calculated dates
**Solution**: Always increment `$arretDroits` when rights are opened
**Result**: ✅ Date effet now correctly >= arret start date

---

**Files**:
- Fix: `Services/DateService.php` (3 lines changed)
- Test: `test_date_effet_fix.php` (new)
- Docs: `DATE_EFFET_BUG_FIX.md` (this file)

**Author**: Claude Code
**Date**: 2025-11-06
**Status**: ✅ Fixed and Tested
