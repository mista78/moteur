# Decompte Days Calculation Fix

## Issue

The `decompte_days` field was initially set to a fixed value (90 for new pathology, 14 for rechute), but it should show the **remaining days** before date-effet opens, and **0** when date-effet is reached.

## Solution

### New Pathology (90-day threshold)

**Before:**
- `decompte_days = 90` (fixed value)

**After:**
- If threshold not reached: `decompte_days = 90 - cumulated_days` (remaining days)
- If threshold reached: `decompte_days = 0`

### Rechute (15-day threshold)

**Before:**
- `decompte_days = 14` (fixed value)

**After:**
- If arret ends before date-effet: `decompte_days = days_remaining_until_date_effet`
- If arret reaches date-effet: `decompte_days = 0`

### Forced Date-Effet

When `ouverture-date-line` or `date_deb_dr_force` is set:
- `decompte_days = 0` (rights already opened)

### Invalid Arret

When `valid_med_controleur != 1`:
- `decompte_days = 0` (no decompte applicable)

## Examples

### Example 1: New Pathology Accumulation

```
Arrêt #1: 43 days
  - Cumul: 43 days
  - Threshold: 90 days
  - Decompte: 90 - 43 = 47 days remaining
  - Date-effet: Not yet reached

Arrêt #2: 17 days (consecutive)
  - Cumul: 43 + 17 = 60 days
  - Threshold: 90 days
  - Decompte: 90 - 60 = 30 days remaining
  - Date-effet: Not yet reached

Arrêt #3: 18 days (consecutive)
  - Cumul: 60 + 18 = 78 days
  - Threshold: 90 days
  - Decompte: 90 - 78 = 12 days remaining
  - Date-effet: Not yet reached

Arrêt #4: 31 days (consecutive)
  - Cumul: 78 + 31 = 109 days
  - Threshold: 90 days (REACHED!)
  - Decompte: 0 days
  - Date-effet: 2022-12-06 ✅
```

### Example 2: Rechute (Short Arret)

```
Arrêt #5: 1 day, rechute
  - Start: 2023-06-15
  - End: 2023-06-15
  - Date-effet: 2023-06-29 (15 days from start)
  - Arret duration: 1 day
  - Arret ends BEFORE date-effet
  - Decompte: 14 - 1 = 13 days remaining
```

### Example 3: Rechute (Long Arret)

```
Arrêt #7: 12 days, rechute
  - Start: 2023-08-07
  - End: 2023-08-18
  - Date-effet: 2023-08-21 (15 days from start)
  - Arret duration: 12 days
  - Arret ends BEFORE date-effet (on day 12)
  - Decompte: 14 - 12 = 2 days remaining
```

### Example 4: Rechute (Reaches Threshold)

```
Arrêt #10: 15 days (new pathology, not rechute)
  - Start: 2023-09-26
  - End: 2023-10-10
  - Date-effet: 2023-10-10 (calculated)
  - Arret duration: 15 days
  - Arret reaches date-effet
  - Decompte: 0 days ✅
```

## Code Changes

### File: `Services/DateService.php`

#### 1. Forced Date-Effet (Lines 279-295)

```php
// Si date_deb_droit existe, l'utiliser comme date-effet
if (isset($currentData['ouverture-date-line']) && !empty($currentData['ouverture-date-line'])) {
    $currentData['date-effet'] = $currentData['ouverture-date-line'];
    $currentData['decompte_days'] = 0; // Rights already opened
    ...
}

// Si la date est forcée
if (isset($currentData['date_deb_dr_force'])) {
    $currentData['date-effet'] = $currentData['date_deb_dr_force'];
    $currentData['decompte_days'] = 0; // Rights forced open
    ...
}
```

#### 2. New Pathology (Lines 333-345)

```php
// Si on dépasse 90 jours, on définit la date d'effet
if ($newNbJours > 90) {
    $dates = date('Y-m-d', max([...]));
    $arretDroits++;
    $currentData['decompte_days'] = 0; // Rights opened
} else {
    // Remaining days before threshold
    $currentData['decompte_days'] = 90 - $newNbJours;
}
```

#### 3. Rechute (Lines 396-414)

```php
// Calculer le max des 3 dates (15ème jour arrêt, DT+15j, MAJ+15j)
$dates = date('Y-m-d', max([...]));

// Decompte: 14 days if arret shorter than date-effet, otherwise 0
$dateEffetTimestamp = strtotime($dates);
$arretEndTimestamp = strtotime($endDate->format('Y-m-d'));

if ($arretEndTimestamp < $dateEffetTimestamp) {
    // Arret ends before date-effet, calculate remaining days
    $remainingDays = ($dateEffetTimestamp - strtotime($startDate->format('Y-m-d'))) / 86400 - $arret_diff;
    $currentData['decompte_days'] = max(0, (int)$remainingDays);
} else {
    // Arret reaches date-effet
    $currentData['decompte_days'] = 0;
}
```

#### 4. New Pathology After Rechute Check Failed (Lines 436-447)

```php
// Si on dépasse 90 jours, on définit la date d'effet
if ($newNbJours > 90) {
    $dates = date('Y-m-d', max([...]));
    $arretDroits++;
    $currentData['decompte_days'] = 0; // Rights opened
} else {
    // Remaining days before threshold
    $currentData['decompte_days'] = 90 - $newNbJours;
}
```

#### 5. Invalid Arret (Lines 261-263)

```php
// Si valid_med_controleur != 1, pas de date d'effet
if (isset($currentData['valid_med_controleur']) && $currentData['valid_med_controleur'] != 1) {
    $currentData['date-effet'] = null;
    $currentData['decompte_days'] = 0; // Invalid, no decompte applicable
    ...
}
```

## Test Results

### Real-World Data (arrets.json - 11 arrets)

```
✅ Arrêt #1: 47 days remaining (43/90)
✅ Arrêt #2: 30 days remaining (60/90)
✅ Arrêt #3: 12 days remaining (78/90)
✅ Arrêt #4: 0 days (threshold reached, date-effet: 2022-12-06)
✅ Arrêt #5: 13 days remaining (1-day rechute, date-effet: 2023-06-29)
✅ Arrêt #6: 13 days remaining (1-day rechute, date-effet: 2023-07-31)
✅ Arrêt #7: 2 days remaining (12-day rechute, date-effet: 2023-08-21)
✅ Arrêt #8: 13 days remaining (1-day rechute, date-effet: 2023-10-02)
✅ Arrêt #9: 13 days remaining (1-day rechute, date-effet: 2023-10-05)
✅ Arrêt #10: 0 days (15-day threshold reached, date-effet: 2023-10-10)
✅ Arrêt #11: 0 days (threshold reached, date-effet: 2023-12-07)
```

### All Tests Pass

```
Total Tests: 114
Passed: 114 ✅
Failed: 0
Duration: 44.55ms
```

## API Response Example

### Before Fix

```json
{
  "arret-from-line": "2023-06-15",
  "arret-to-line": "2023-06-15",
  "arret_diff": 1,
  "is_rechute": true,
  "decompte_days": 14,  // ❌ Fixed value
  "date-effet": "2023-06-29"
}
```

### After Fix

```json
{
  "arret-from-line": "2023-06-15",
  "arret-to-line": "2023-06-15",
  "arret_diff": 1,
  "is_rechute": true,
  "decompte_days": 13,  // ✅ Remaining days (14 - 1)
  "date-effet": "2023-06-29"
}
```

## Benefits

### For Users
- ✅ Clear visibility of remaining days before rights open
- ✅ Shows 0 when rights are already opened
- ✅ Accurate tracking of threshold progress

### For Developers
- ✅ Dynamic calculation based on actual arret duration
- ✅ Correct handling of all edge cases
- ✅ Consistent behavior across all pathways

### For Business
- ✅ Transparent threshold tracking
- ✅ Accurate decompte period display
- ✅ Better user understanding of when rights will open

## Summary

The `decompte_days` field now correctly shows:
- **Remaining days** before date-effet opens (dynamic calculation)
- **0 days** when date-effet is reached or forced
- Proper handling for new pathologies, rechutes, and edge cases

All 114 tests pass. The fix is production-ready!
