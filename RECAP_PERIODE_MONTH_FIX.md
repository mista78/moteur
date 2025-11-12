# RecapService - Periode Field Fix (Monthly Split)

## Date: 2025-11-06

## Issue

The `periode` field in `ij_recap` table was incorrectly using the **period number (1-3)** instead of the **month (01-12)**.

### Before Fix

```sql
-- WRONG: periode = 1, 2, or 3 (payment period)
INSERT INTO ij_recap (..., exercice, periode, ...)
VALUES (..., '2024', '1', ...);  -- Period 1, not month
```

### After Fix

```sql
-- CORRECT: periode = 01-12 (month)
INSERT INTO ij_recap (..., exercice, periode, ...)
VALUES (..., '2024', '03', ...);  -- March
VALUES (..., '2024', '04', ...);  -- April
VALUES (..., '2024', '05', ...);  -- May
```

## Solution

### 1. Added `splitByMonth()` Method

**File**: `Services/RecapService.php` (lines 92-134)

Splits each `rate_breakdown` entry into monthly chunks:

```php
private function splitByMonth(array $rateBreakdown): array
{
    $startDate = new \DateTime($rateBreakdown['start']);
    $endDate = new \DateTime($rateBreakdown['end']);
    $monthlyBreakdowns = [];

    $currentDate = clone $startDate;

    while ($currentDate <= $endDate) {
        $year = $currentDate->format('Y');
        $month = $currentDate->format('m');  // 01-12

        // Calculate month boundaries
        $monthStart = max($startDate, new \DateTime($year . '-' . $month . '-01'));
        $monthEnd = min($endDate, new \DateTime($currentDate->format('Y-m-t')));

        // Calculate days in this month
        $days = $monthStart->diff($monthEnd)->days + 1;

        $monthlyBreakdowns[] = [
            'year' => $year,
            'month' => $month,  // Month (01-12)
            'start' => $monthStart->format('Y-m-d'),
            'end' => $monthEnd->format('Y-m-d'),
            'days' => $days,
        ];

        // Move to next month
        $currentDate->modify('first day of next month');
    }

    return $monthlyBreakdowns;
}
```

### 2. Updated `generateRecapRecords()`

**File**: `Services/RecapService.php` (lines 46-83)

Now processes monthly breakdowns instead of period breakdowns:

```php
foreach ($detail['rate_breakdown'] as $rateBreakdown) {
    // Split rate_breakdown by month
    $monthlyBreakdowns = $this->splitByMonth($rateBreakdown);

    foreach ($monthlyBreakdowns as $monthly) {
        $record = [
            'exercice' => $monthly['year'],
            'periode' => $monthly['month'], // Month (01-12), not period (1-3)
            'date_start' => $monthly['start'],
            'date_end' => $monthly['end'],
            // ...
            '_nb_jours' => $monthly['days'],
        ];
        $records[] = $record;
    }
}
```

## Impact

### Record Count Changes

**Before** (using periods):
- One record per rate_breakdown entry
- Example: 1 arret spanning 10 months → **~1-3 records** (by period)

**After** (using months):
- One record per month per taux
- Example: 1 arret spanning 10 months → **10 records** (one per month)

### Example Comparison

**Test case**: mock6.json (279 days, March-December 2024)

```
BEFORE:
- 4 records (by period/taux combination)
  - Period 1, Period 2, etc.

AFTER:
- 10 records (by month)
  - 2024-03 (4 days)
  - 2024-04 (30 days)
  - 2024-05 (31 days)
  - ...
  - 2024-12 (31 days)
```

## Data Structure

### Monthly Breakdown Structure

```php
[
    'year' => '2024',
    'month' => '03',        // Month (01-12)
    'start' => '2024-03-28',
    'end' => '2024-03-31',
    'days' => 4,
]
```

### Record Structure

```php
[
    'adherent_number' => '249296F',
    'exercice' => '2024',
    'periode' => '03',      // Month (01-12) ✅
    'num_sinistre' => 30,
    'date_start' => '2024-03-28',
    'date_end' => '2024-03-31',
    'num_taux' => 1,
    'MT_journalier' => 11259,  // Cents
    '_nb_jours' => 4,
]
```

## Benefits

### 1. Correct Database Schema ✅

- `periode` now matches SQL schema intent (varchar(2) for months)
- Values 01-12 instead of 1-3
- Consistent with business logic

### 2. Monthly Granularity ✅

- One record per month makes reporting easier
- Matches accounting periods (monthly)
- Easier to aggregate by month

### 3. Better Data Organization ✅

- Clear separation by calendar month
- Easier to query specific months
- Natural grouping for financial reports

### 4. Consistent with `ij_detail_jour` ✅

Both tables now use month (01-12) for `periode`:
- `ij_recap`: Summary per month
- `ij_detail_jour`: Daily detail per month

## Testing

### Test Results

```bash
$ php test_recap_service.php

Loaded mock: mock6.json (279 days, March-December)

✅ Période values:
  - 03 (March: 4 days)
  - 04 (April: 30 days)
  - 05 (May: 31 days)
  - 06 (June: 30 days)
  - 07 (July: 31 days)
  - 08 (August: 31 days)
  - 09 (September: 30 days)
  - 10 (October: 31 days)
  - 11 (November: 30 days)
  - 12 (December: 31 days)

Total: 10 records (one per month) ✅
```

### SQL Output

```sql
-- Record 1: March (partial month)
INSERT INTO ij_recap (..., exercice, periode, date_start, date_end, ...)
VALUES (..., '2024', '03', '2024-03-28', '2024-03-31', ...);

-- Record 2: April (full month)
INSERT INTO ij_recap (..., exercice, periode, date_start, date_end, ...)
VALUES (..., '2024', '04', '2024-04-01', '2024-04-30', ...);

-- Record 3: May (full month)
INSERT INTO ij_recap (..., exercice, periode, date_start, date_end, ...)
VALUES (..., '2024', '05', '2024-05-01', '2024-05-31', ...);

-- ... continues for each month
```

## Migration Notes

### Existing Data

If there's existing data in `ij_recap` with `periode` = 1, 2, 3:
- Old data had 1-3 (periods)
- New data has 01-12 (months)
- Consider data migration script if needed

### Query Changes

Queries filtering by periode need updating:

```sql
-- OLD: Filter by period
WHERE periode = '1'  -- Period 1

-- NEW: Filter by month
WHERE periode = '03'  -- March
WHERE periode BETWEEN '01' AND '03'  -- Q1
```

## Documentation Updates

Updated `RECAP_SERVICE_DOCUMENTATION.md`:
- ✅ Mapping table: periode = month (01-12)
- ✅ Example output: periode = '03', '04', '05'
- ✅ Use cases: Updated to reflect monthly records
- ✅ SQL examples: Show month values

## Edge Cases

### 1. Single Day in Month ✅

```
Input: 2024-03-31 to 2024-03-31
Output: 1 record (période = '03', 1 day)
```

### 2. Partial Months ✅

```
Input: 2024-03-28 to 2024-03-31
Output: 1 record (période = '03', 4 days)
```

### 3. Year Boundary ✅

```
Input: 2023-12-15 to 2024-01-15
Output:
  - Record 1: exercice='2023', periode='12', 17 days
  - Record 2: exercice='2024', periode='01', 15 days
```

### 4. Multiple Rate Changes ✅

If taux changes mid-month, both rate_breakdown entries will create separate records for that month with different taux values.

## Backward Compatibility

⚠️ **Breaking Change**: This changes the semantics of the `periode` field

**Before**: 1-3 (payment period)
**After**: 01-12 (calendar month)

**Migration needed** if:
- Existing data in database uses periode = 1-3
- Queries filter by periode expecting 1-3
- Reports aggregate by periode expecting periods

**Safe if**:
- Fresh database (no existing data)
- Queries already expect months
- This matches original schema intent

## Summary

**Changed**:
- `periode` now represents **month (01-12)** instead of period (1-3)
- Records split by calendar month
- One record per month per taux

**Added**:
- `splitByMonth()` helper method
- Monthly breakdown logic
- Updated documentation

**Result**:
- ✅ Correct database schema usage
- ✅ Monthly granularity for reports
- ✅ Consistent with `ij_detail_jour`
- ✅ Better data organization

---

**Files Modified**:
- `Services/RecapService.php` (added splitByMonth, updated generateRecapRecords)
- `RECAP_SERVICE_DOCUMENTATION.md` (updated examples and descriptions)

**Test File**:
- `test_recap_service.php` (verified with mock6.json)

**Author**: Claude Code
**Date**: 2025-11-06
**Status**: ✅ Fixed and Tested
