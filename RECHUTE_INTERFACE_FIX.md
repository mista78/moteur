# Rechute Interface Fix

## Problem

The web interface was automatically marking ALL arrêts after the first one as "rechute" (relapse) and disabling the checkbox. However, according to the correct business rules, an arrêt should only be considered a rechute if the **previous arrêt has rights opened** (date-effet exists), meaning the 90-day threshold was already reached.

### Previous Behavior
- ❌ Arret 1: Normal
- ❌ Arret 2: **Automatically marked as rechute** (incorrect if 90-day threshold not reached)
- ❌ Arret 3: **Automatically marked as rechute** (incorrect if 90-day threshold not reached)

### Correct Behavior
- ✓ Arret 1: Normal (accumulating toward 90 days)
- ✓ Arret 2: Manual checkbox, auto-determined by backend based on whether arret 1 has date-effet
- ✓ Arret 3: Manual checkbox, auto-determined by backend based on whether previous arrets have date-effet

## Solution

### Backend Fix (Services/DateService.php)

Added critical check in `isRechute()` method at line 216-220:

```php
// CRITICAL: Si l'arrêt précédent n'a pas de date-effet (droits pas ouverts),
// alors ce n'est pas une rechute, juste une accumulation vers le seuil de 90 jours
if (!isset($previousArret['date-effet']) || empty($previousArret['date-effet'])) {
    return false;
}
```

**Rechute criteria (all must be met):**
1. Previous arret has **date-effet** (rights were opened - 90-day threshold reached)
2. Not consecutive (not a prolongation)
3. Starts within 1 year after previous arret ended

### Frontend Fix (app.js)

**1. Updated `addArret()` function (line 286-319):**
- ❌ Removed: `const isRechute = arretCount > 1;`
- ❌ Removed: Automatic "(Rechute)" label in header
- ❌ Removed: `checked disabled` attributes on rechute checkbox
- ✓ Added: Clear label "Rechute (si droits déjà ouverts)"
- ✓ Changed: Rechute checkbox is now always enabled and manual

**2. Updated `collectArrets()` function (line 413-419):**
- Changed `'rechute-line': document.getElementById('rechute_${id}').checked ? 1 : 0`
- To: `'rechute-line': rechute.checked ? 1 : null`
- ✓ When unchecked, sends `null` to allow backend auto-determination

**3. Updated `loadMockData()` function (line 679-706):**
- ❌ Removed: `const isRechute = index > 0;`
- ❌ Removed: Automatic "(Rechute)" label in header
- ❌ Removed: `checked disabled` attributes on rechute checkbox
- ✓ Added: Clear label "Rechute (si droits déjà ouverts)"
- ✓ Changed: Only checks the checkbox if mock data has `rechute-line == '1'`

## Testing

### Test Scenarios

**Scenario 1: Before 90-day threshold**
```
Arrêt 1: 46 days (no date-effet)
Arrêt 2: 61 days (starts 14 days after arrêt 1)
Result: Arrêt 2 is NOT rechute → accumulating to 107 total days
Payment starts: At day 91 (after reaching 90-day threshold)
```

**Scenario 2: After 90-day threshold**
```
Arrêt 1: 101 days (has date-effet)
Arrêt 2: 61 days (starts 21 days after arrêt 1)
Result: Arrêt 2 IS rechute → rights were already opened
Payment starts: At day 15 (rechute rule, not day 91)
```

### Test Results
✓ **114/114 tests passed**
✓ Test file created: `test_rechute_after_droits.php`
✓ All integration tests (mock.json through mock28.json) pass
✓ Unit tests for rechute detection pass

## User Experience Changes

### Before Fix
1. User adds Arrêt 1: 30 days
2. User adds Arrêt 2: **Automatically marked as "Rechute (automatique)"** with disabled checkbox
3. Calculation treats it as rechute (incorrect - no rights opened yet)

### After Fix
1. User adds Arrêt 1: 30 days
2. User adds Arrêt 2: Checkbox available to manually mark as rechute if desired
3. If checkbox unchecked, backend auto-determines:
   - If Arrêt 1 has no date-effet → NOT rechute (accumulating)
   - If Arrêt 1 has date-effet → Check other criteria (consecutive, < 1 year)
4. Calculation is now correct!

## Files Modified

1. **Services/DateService.php** (line 216-220)
   - Added check for previous arret date-effet in `isRechute()` method

2. **app.js** (lines 286-319, 413-419, 679-706)
   - Removed automatic rechute marking in `addArret()`
   - Changed rechute-line to send `null` when unchecked in `collectArrets()`
   - Removed automatic rechute marking in `loadMockData()`

3. **CLAUDE.md** (lines 132-141)
   - Updated documentation with correct rechute business rules

## Documentation

Updated CLAUDE.md with clarified rechute rules:
```markdown
**Rechute (Relapse) handling**:
- **Critical rule**: An arret is only a rechute if rights have been opened (date-effet exists for previous arret)
- If the 90-day threshold hasn't been reached yet, subsequent arrets accumulate days, they are NOT relapses
- Rechute criteria (all must be met):
  1. Previous arret has date-effet (rights were opened)
  2. Not consecutive (not a prolongation)
  3. Starts within 1 year after previous arret ended
- Implementation: DateService::isRechute() (Services/DateService.php:204)
```

## Impact

✓ Correct business logic implementation
✓ Better user experience - no forced rechute marking
✓ Backend now properly determines rechute based on rights opening
✓ All existing tests continue to pass
✓ Interface is now consistent with business rules

## Frontend Visual Display

### Added in Services/DateService.php

The backend now adds an `is_rechute` flag to each arret:
- Line 298: `$currentData['is_rechute'] = false;` for first arret/new pathology
- Line 336: `$currentData['is_rechute'] = $siRechute;` for subsequent arrets

### Added in app.js (lines 1071-1114)

**Visual indicators in results table:**
- 🔄 **Rechute** (yellow background): Rights already opened + < 1 year
- 🆕 **Nouvelle pathologie** (green background): Rights not opened OR > 1 year
- **1ère pathologie** (gray text): First work stoppage

**Explanation box:**
Added informational box explaining each type with business rules.

### Benefits
- ✅ User can see backend determination visually
- ✅ Color-coded for quick understanding
- ✅ Explanation helps understand complex business rules
- ✅ Easier debugging and verification

See **FRONTEND_RECHUTE_DISPLAY.md** for detailed documentation.

## Date: 2024-10-31
