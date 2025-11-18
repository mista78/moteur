# Rechute Logic Update

## Changes Made

### 1. ✅ isRechute() - Always Calculated (Never Forced)

**Before:**
- Could be forced by `rechute-line` field
- Used business day logic (skipping public holidays)

**After:**
- ALWAYS calculated automatically
- Never uses `rechute-line` field (removed forced rechute logic)
- Simple next-day check (no business day calculation)

### 2. ✅ Prolongation Check - Next Day Only (Including Weekends)

**Before:**
- Used `addOneBusinessDay()` method
- Skipped weekends AND public holidays

**After:**
- Checks if next arret starts exactly the next calendar day
- Includes weekends (arrets can be set on Saturday/Sunday)
- Simple: `lastEnd + 1 day == currentStart`

## Code Changes

### File: `Services/DateService.php`

#### 1. mergeProlongations() - Lines 172-201

**Before:**
```php
// Calculer le prochain jour ouvré après la fin du dernier arrêt
$nextBusinessDay = $this->addOneBusinessDay($lastEnd);

if ($nextBusinessDay->format('Y-m-d') == $currentStart->format('Y-m-d')) {
    $last['arret-to-line'] = $arret['arret-to-line'];
} else {
    $merged[] = $arret;
}
```

**After:**
```php
// Calculer le prochain jour (including weekends - arrets can be on weekends)
$nextDay = clone $lastEnd;
$nextDay->modify('+1 day');

if ($nextDay->format('Y-m-d') == $currentStart->format('Y-m-d')) {
    // C'est le jour suivant (prolongation)
    $last['arret-to-line'] = $arret['arret-to-line'];
} else {
    $merged[] = $arret;
}
```

#### 2. isRechute() - Lines 204-241

**Removed (Lines 210-214):**
```php
// Si rechute-line est explicitement défini (forcé par commission), le respecter
if (isset($currentArret['rechute-line']) && $currentArret['rechute-line'] !== null && $currentArret['rechute-line'] !== '') {
    return (int)$currentArret['rechute-line'] > 0;
}
```

**Changed Prolongation Check:**

**Before:**
```php
// Vérifier si consécutif (prolongation)
$nextBusinessDay = $this->addOneBusinessDay($lastEnd);
if ($nextBusinessDay->format('Y-m-d') == $currentStart->format('Y-m-d')) {
    // C'est une prolongation, pas une rechute
    return false;
}
```

**After:**
```php
// Vérifier si c'est une prolongation (exactly next day, including weekends)
$nextDay = clone $lastEnd;
$nextDay->modify('+1 day');

if ($nextDay->format('Y-m-d') == $currentStart->format('Y-m-d')) {
    // C'est une prolongation, pas une rechute
    return false;
}
```

## Business Rules

### Rechute Detection (Always Automatic)

An arret is a **rechute** if ALL conditions are met:

1. ✅ **Previous arret exists**
2. ✅ **Previous arret has date-effet** (rights already opened)
3. ✅ **NOT a prolongation** (gap > 1 day)
4. ✅ **Within 1 year** of previous arret end date

### Prolongation Detection

An arret is a **prolongation** (consecutive) if:
- It starts **exactly the next calendar day** after the previous arret ends
- Includes **weekends** (Saturday, Sunday)
- No business day calculation

### Examples

#### Example 1: Prolongation

```
Arret A: 2023-06-15 → 2023-06-20
Arret B: 2023-06-21 → 2023-06-25

Gap: 0 days (next day)
Result: PROLONGATION ✅ (merged into one arret)
```

#### Example 2: Prolongation Over Weekend

```
Arret A: 2023-06-16 (Friday) → 2023-06-16
Arret B: 2023-06-17 (Saturday) → 2023-06-18

Gap: 0 days (next day, Saturday)
Result: PROLONGATION ✅ (arrets can be on weekends)
```

#### Example 3: NOT Prolongation (Rechute)

```
Arret A: 2023-06-15 → 2023-06-20 (date-effet: 2023-07-05)
Arret B: 2023-08-01 → 2023-08-05

Gap: 41 days
Previous has date-effet: Yes
Within 1 year: Yes
Result: RECHUTE 🔄
```

#### Example 4: NOT Rechute (No Date-Effet)

```
Arret A: 2023-06-15 → 2023-06-20 (no date-effet)
Arret B: 2023-08-01 → 2023-08-05

Previous has date-effet: No
Result: NEW PATHOLOGY 🆕 (accumulating toward 90-day threshold)
```

## Test Results

### Real Data (arrets.json)

```
✅ Arrêt #4: Date-effet reached (2022-12-06)
✅ Arrêt #5: Rechute of #4 (gap: 173 days, within 1 year)
✅ Arrêt #6: Rechute of #5 (gap: 32 days, within 1 year)
✅ Arrêt #7: Rechute of #6 (gap: 21 days, within 1 year)
✅ Arrêt #8: Rechute of #7 (gap: 31 days, within 1 year)
✅ Arrêt #9: Rechute of #8 (gap: 3 days, within 1 year)
✅ Arrêt #10: NOT rechute (no previous date-effet or new pathology)
```

### All Tests Pass

```
Total Tests: 114
Passed: 114 ✅
Failed: 0
Duration: 44.19ms
```

## Breaking Changes

### ⚠️ Removed Feature: Forced Rechute

**What was removed:**
- The ability to force rechute via `rechute-line` field
- Example: `rechute-line: 1` would force rechute detection

**Why removed:**
- User requirement: "rechute is always calculated never forced"
- Ensures consistent, automatic calculation
- Prevents manual override conflicts

**Migration:**
- Remove any code that sets `rechute-line` field
- Rechute will be automatically detected based on business rules
- No action needed if you weren't using forced rechute

## Benefits

### For Users
- ✅ Consistent rechute detection (no manual overrides)
- ✅ Clear prolongation rules (next day only)
- ✅ Weekends properly handled (arrets can occur on weekends)

### For Developers
- ✅ Simpler logic (no business day calculation needed)
- ✅ Automatic calculation (no forced values)
- ✅ Easy to understand (next day = prolongation)

### For Business
- ✅ Accurate rechute tracking
- ✅ No manual intervention needed
- ✅ Transparent automatic rules

## API Response

The API response remains the same, but `is_rechute` is now always calculated:

```json
{
  "arret-from-line": "2023-06-15",
  "arret-to-line": "2023-06-15",
  "is_rechute": true,
  "rechute_of_arret_index": 3,
  "decompte_days": 13,
  "date-effet": "2023-06-29"
}
```

## Summary

### What Changed
- ✅ Rechute always calculated (never forced)
- ✅ Prolongation = exactly next day (including weekends)
- ✅ Removed `rechute-line` forcing logic
- ✅ Simplified business day logic (no more addOneBusinessDay)

### What Stayed
- ✅ Rechute requires previous date-effet
- ✅ Rechute must be within 1 year
- ✅ 15-day threshold for rechute
- ✅ 90-day threshold for new pathology

All 114 tests pass. The changes are production-ready! 🎉
