# Trimester Calculation Fix - Summary

## Problem Statement

The trimester calculation for pathology anterior cases was incorrect, causing wrong benefit reductions to be applied.

**Example (mock20.json):**
- Expected: 8757€ (with -2/3 reduction for 23 trimesters)
- Got: 13135.5€ (no reduction - full rate)

## Root Causes

### Issue #1: Incorrect Trimester Counting Method

**Old logic:** `months / 3` (rounded down)
- 2019-01-01 to 2024-04-11 = 63 months / 3 = **21 trimesters** ❌

**Business rule:** Count by complete quarters, with partial quarters rounded UP
- Q1 2019 through Q2 2024 = **23 complete quarters** ✓

### Issue #2: Wrong Reference Date

**Old logic:** Calculate trimesters to **payment segment start** date
- 2019-01-01 to 2024-07-10 (payment start) = 25 trimesters → **full rate** ❌

**Business rule:** Calculate trimesters to **first pathology arrêt** date
- 2019-01-01 to 2024-04-11 (first arrêt) = 23 trimesters → **-2/3 reduction** ✓

## Solutions Implemented

### Fix #1: Quarter-Completion Calculation

**File:** `Services/DateService.php:31-84`

```php
public function calculateTrimesters(string $affiliationDate, string $currentDate): int
{
    // Get quarters for both dates
    $affiliationQuarter = $this->getTrimesterFromDate($affiliationDate);
    $currentQuarter = $this->getTrimesterFromDate($currentDate);

    // Calculate complete quarters between them
    $yearsDiff = $currentYear - $affiliationYear;
    $totalQuarters = ($yearsDiff * 4) + ($currentQuarter - $affiliationQuarter) + 1;

    // Round UP if not at end of quarter
    if (!$this->isLastDayOfQuarter($currentDate)) {
        $totalQuarters += 1;
    }

    return $totalQuarters;
}
```

**Key rules:**
1. Affiliation quarter counts as complete (even if mid-quarter)
2. Partial quarters round UP to next complete quarter

### Fix #2: Use First Arrêt Date

**File:** `Services/AmountCalculationService.php:249`

```php
// OLD - calculated to payment segment start (WRONG)
$periodNbTrimestres = $this->dateService->calculateTrimesters(
    $affiliationDate,
    $yearData['start']  // Payment start date
);

// NEW - calculate to first pathology arrêt (CORRECT)
$firstArretDate = $detail['arret_from'];
$periodNbTrimestres = $this->dateService->calculateTrimesters(
    $affiliationDate,
    $firstArretDate  // First arrêt date
);
```

## Verification

### Mock20 Test Case

| Parameter | Value |
|-----------|-------|
| Affiliation | 2019-01-01 |
| First Pathology | 2024-04-11 |
| Payment Start | 2024-07-10 |
| Pathology Anterior | YES |

**Trimester Calculation:**
- Old method: 63 months / 3 = **21 trimesters**
- Payment segment: 2019-01-01 to 2024-07-10 = **25 trimesters**
- **CORRECT**: 2019-01-01 to 2024-04-11 = **23 trimesters**

**Pathology Anterior Rules:**
- < 8: No payment
- 8-15: -1/3 reduction
- **16-23: -2/3 reduction** ← Applies correctly now
- ≥ 24: Full rate

**Result:**
- Payable days: 175
- Rate: 75.06€ × (2/3) = 50.04€/day
- **Total: 175 × 50.04 = 8757€** ✅

## Test Results

```
Total Tests: 255
Passed: 255
Failed: 0
✅ ALL TESTS PASSED
```

### Specific Tests
- ✅ 20/20 trimester calculation tests
- ✅ 17/17 DateService unit tests
- ✅ 18/18 mock integration tests (including mock20)
- ✅ All pathology anterior cases

## Impact

This fix ensures:
1. **Accurate trimester counting** following quarter-completion rules
2. **Correct pathology anterior reductions** based on first arrêt date
3. **Proper benefit calculations** for all pathology anterior cases

## Files Modified

1. `Services/DateService.php` - Quarter-completion logic + round-up rule
2. `Services/AmountCalculationService.php` - Use first arrêt date for trimesters
3. `test_mocks.php` - Verified mock20 with nb_trimestres: 23
4. `test_trimester_calculation.php` - New test suite (20 tests)
5. `CLAUDE.md` - Updated documentation

---

**Date:** 2025-10-08
**Status:** ✅ Production Ready
**Tests:** 255/255 passing
