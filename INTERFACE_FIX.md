# Fix Interface Web - Mock2 Problem

## Problem Identified

The web interface was incorrectly interpreting `dt-line` values from mock JSON files:

### The Issue
In `app.js` line 708, when loading mocks:
```javascript
// OLD CODE (WRONG):
<input type="checkbox" id="dt_${arretCount}" ${arret['dt-line'] === '0' ? 'checked' : ''}>
```

When `mock2.json` has `"dt-line": "0"`:
- Checkbox was automatically **CHECKED**
- Sent **string "0"** to API
- **Completely blocked payment** instead of applying +31 day penalty

### Expected Behavior
According to specifications (`text.txt`):
- `dt-line = 0` (integer) = Late declaration NOT excused → **+31 days penalty** (but payment allowed)
- `dt-line = 1` (integer) = Account update needed → **+31 days penalty**
- String "0" = Special case to **BLOCK payment entirely**

## Fix Applied

**File:** `app.js` line 708

**Changed:**
```javascript
// NEW CODE (CORRECT):
<input type="checkbox" id="dt_${arretCount}">
<label>DT non excusée (bloquer paiement)</label>
```

Removed automatic `checked` attribute when loading mocks with `dt-line = 0`.

## Result

Now when loading `mock2.json`:
- ✅ Checkboxes start **unchecked**
- ✅ Sends integer `0` to API
- ✅ Applies +31 day penalty (not complete block)
- ✅ **Expected: 17,318.92€ with 116 days**

## To Test

1. **Clear browser cache** (very important!):
   - Windows/Linux: `Ctrl + Shift + R`
   - Mac: `Cmd + Shift + R`

2. Open web interface

3. Click "📋 Mock 2" button

4. Click "💰 Calculer Tout"

5. **Expected result:**
   - Montant: **17,318.92€** ✅
   - Jours: **116** ✅
   - All arrêts should show payments (not blocked)

## Cache Busting

To prevent future caching issues, consider adding version parameter:

```html
<!-- In index.html -->
<script src="app.js?v=2025102001"></script>
```

Update the version number whenever you modify JS/CSS files.

## Technical Details

### DT-Line Values

| Value | Type | Meaning | Behavior |
|-------|------|---------|----------|
| `0` | integer | Late declaration not excused | +31 days penalty (15 for rechute) |
| `1` | integer | Account update needed | +31 days penalty (15 for rechute) |
| `"0"` | string | Force block | No payment at all |

### Code Flow

1. **Mock loading** (app.js:708): Sets hidden field `dt_original_X`
2. **Form submission** (app.js:398-408):
   - If checkbox checked → send string `"0"` (block)
   - If checkbox unchecked → send integer from `dt_original_X`
3. **PHP processing** (DateService.php):
   - String `"0"` → blocks payment
   - Integer `0` → applies penalty
   - Integer `1` → applies penalty for account update

## Files Modified

- `app.js` line 708
- `INTERFACE_FIX.md` (this file)
