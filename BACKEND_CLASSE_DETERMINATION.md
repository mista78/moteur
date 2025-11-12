# Backend Class Determination Implementation

## Date: 2025-11-12

## Overview

The system now supports **backend class determination** based on `revenu_n_moins_2`. Previously, class determination logic existed only in the frontend (app.js). Now it's also available in the backend for:
- API consumers who want server-side class determination
- Improved security (don't trust frontend calculations)
- Single source of truth for business logic

## Implementation

### 1. Added `IJCalculator::determineClasse()` Method

**File**: `IJCalculator.php` (lines 691-706)

```php
public function determineClasse(
    ?float $revenuNMoins2 = null,
    ?string $dateOuvertureDroits = null,
    bool $taxeOffice = false
): string {
    // Delegate to TauxDeterminationService
    return $this->tauxService->determineClasse($revenuNMoins2, $dateOuvertureDroits, $taxeOffice);
}
```

This is a wrapper method that delegates to `TauxDeterminationService::determineClasse()` which already existed but wasn't exposed through the calculator.

### 2. New API Endpoint: `determine-classe`

**File**: `api.php` (lines 131-158)

**Endpoint**: `POST /api.php?endpoint=determine-classe`

**Request Body**:
```json
{
  "revenu_n_moins_2": 94000,
  "pass_value": 47000,
  "taxe_office": false,
  "date_ouverture_droits": "2024-01-15"
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "classe": "B",
    "revenu_n_moins_2": 94000,
    "taxe_office": false,
    "pass_value": 47000
  }
}
```

### 3. Enhanced `calculate` Endpoint

**File**: `api.php` (lines 96-103)

The `calculate` endpoint now **auto-determines class** if:
- `revenu_n_moins_2` is provided
- `classe` is NOT provided

**Before**:
```json
{
  "classe": "B",  // Required
  "revenu_n_moins_2": 94000,
  "arrets": [...]
}
```

**After** (class optional if revenu provided):
```json
{
  "revenu_n_moins_2": 94000,  // Class auto-determined from this
  "arrets": [...]
}
```

## Business Rules

### Class Determination Logic

| Classe | Revenue N-2 | En PASS | Example (PASS=47000€) |
|--------|-------------|---------|----------------------|
| **A** | < 1 PASS | < 47 000€ | 35 000€ → Classe A |
| **B** | 1-3 PASS | 47 000€ - 141 000€ | 94 000€ → Classe B |
| **C** | > 3 PASS | > 141 000€ | 150 000€ → Classe C |

### Special Cases

1. **Taxé d'office** (`taxe_office: true`): Always returns Classe A
2. **Revenus non communiqués** (`revenu_n_moins_2: null`): Returns Classe A
3. **Boundary values**:
   - `revenu = 47000` (exactly 1 PASS) → Classe B
   - `revenu = 141000` (exactly 3 PASS) → Classe B
   - `revenu = 141001` (> 3 PASS) → Classe C

## Usage Examples

### Example 1: Standalone Class Determination

```bash
curl -X POST http://localhost:8000/api.php?endpoint=determine-classe \
  -H "Content-Type: application/json" \
  -d '{
    "revenu_n_moins_2": 94000,
    "pass_value": 47000
  }'
```

**Response**:
```json
{
  "success": true,
  "data": {
    "classe": "B",
    "revenu_n_moins_2": 94000,
    "taxe_office": false,
    "pass_value": 47000
  }
}
```

### Example 2: Auto-Determination in Calculate Endpoint

**Request** (without `classe`):
```bash
curl -X POST http://localhost:8000/api.php?endpoint=calculate \
  -H "Content-Type: application/json" \
  -d '{
    "statut": "M",
    "revenu_n_moins_2": 94000,
    "option": 100,
    "birth_date": "1960-01-15",
    "current_date": "2024-01-15",
    "attestation_date": "2024-01-31",
    "nb_trimestres": 22,
    "pass_value": 47000,
    "arrets": [...]
  }'
```

The backend will automatically determine `classe: "B"` before calculation.

### Example 3: PHP Direct Usage

```php
require_once 'IJCalculator.php';

$calculator = new IJCalculator('taux.csv');
$calculator->setPassValue(47000);

// Determine class
$classe = $calculator->determineClasse(
    revenuNMoins2: 94000,
    dateOuvertureDroits: '2024-01-15',
    taxeOffice: false
);

echo "Classe déterminée: {$classe}"; // Output: Classe déterminée: B
```

### Example 4: Taxé d'Office

```bash
curl -X POST http://localhost:8000/api.php?endpoint=determine-classe \
  -H "Content-Type: application/json" \
  -d '{
    "revenu_n_moins_2": 150000,
    "taxe_office": true,
    "pass_value": 47000
  }'
```

**Response** (always Classe A when taxé d'office):
```json
{
  "success": true,
  "data": {
    "classe": "A",
    "revenu_n_moins_2": 150000,
    "taxe_office": true,
    "pass_value": 47000
  }
}
```

## Testing

### Backend Unit Tests

**File**: `test_determine_classe.php`

```bash
php test_determine_classe.php
```

Tests 9 scenarios:
- ✅ Classe A: Revenu < 1 PASS
- ✅ Classe A: Revenu = 0.5 PASS
- ✅ Classe B: Revenu = 1 PASS (boundary)
- ✅ Classe B: Revenu = 2 PASS
- ✅ Classe B: Revenu = 3 PASS (boundary)
- ✅ Classe C: Revenu > 3 PASS
- ✅ Classe C: Revenu = 5 PASS
- ✅ Classe A: Taxé d'office
- ✅ Classe A: Revenus non communiqués

**Result**: All 9 tests pass

### API Endpoint Tests

**File**: `test_api_determine_classe.php`

```bash
# Start dev server first
php -S localhost:8000 &

# Run API tests
php test_api_determine_classe.php
```

Tests the same scenarios via HTTP API.

### Backward Compatibility

**All existing tests pass**: 114/114 tests ✅

```bash
php run_all_tests.php
```

The implementation maintains **100% backward compatibility**:
- Frontend class determination still works (app.js)
- API accepts explicit `classe` parameter
- No breaking changes to existing functionality

## Architecture

### Service Layer

```
IJCalculator
    ↓ delegates to
TauxDeterminationService::determineClasse()
    ↓ implements business logic
Returns: 'A' | 'B' | 'C'
```

The business logic lives in `TauxDeterminationService` (SOLID principle: Single Responsibility).

### Frontend vs Backend

| Aspect | Frontend (app.js) | Backend (IJCalculator) |
|--------|------------------|----------------------|
| Location | Lines 23-93 | IJCalculator.php:699-706 |
| Logic | Direct calculation | Delegates to service |
| Use Case | UI auto-fill | API consumers |
| Trust Level | User input | Server-validated |
| Dependency | JavaScript | PHP service layer |

**Best Practice**: Use backend determination for calculations, frontend for UX auto-fill.

## Benefits

### 1. Security ✅
- Server-side validation of class determination
- Don't trust frontend calculations
- Prevents class manipulation by users

### 2. Single Source of Truth ✅
- Business logic in one place (TauxDeterminationService)
- Frontend and backend use same rules
- Easier to maintain and update

### 3. API Flexibility ✅
- External systems can send revenue instead of class
- Backend determines class automatically
- Simpler integration for API consumers

### 4. Backward Compatible ✅
- Existing code continues to work
- Optional enhancement (can still send explicit class)
- No breaking changes

## Migration Guide

### For API Consumers

**Old approach** (still works):
```json
{
  "classe": "B",
  "arrets": [...]
}
```

**New approach** (recommended):
```json
{
  "revenu_n_moins_2": 94000,
  "pass_value": 47000,
  "arrets": [...]
}
```

### For Frontend Integration

Update app.js to call backend endpoint instead of calculating locally:

```javascript
async function determineClasseFromBackend(revenuNMoins2, passValue, taxeOffice) {
    const response = await fetch(`${API_URL}?endpoint=determine-classe`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            revenu_n_moins_2: revenuNMoins2,
            pass_value: passValue,
            taxe_office: taxeOffice
        })
    });

    const result = await response.json();
    return result.data.classe;
}
```

## Edge Cases Handled

### 1. Null Revenue ✅
```php
determineClasse(null) // Returns 'A'
```

### 2. Boundary Values ✅
```php
determineClasse(47000)  // Returns 'B' (exactly 1 PASS)
determineClasse(141000) // Returns 'B' (exactly 3 PASS)
determineClasse(141001) // Returns 'C' (> 3 PASS)
```

### 3. Taxé d'Office Priority ✅
```php
determineClasse(150000, null, true) // Returns 'A' (ignores high revenue)
```

### 4. Different PASS Values ✅
```php
// PASS = 46000 (2023)
determineClasse(92000, null, false) // Returns 'B' (2 PASS)

// PASS = 47000 (2024)
determineClasse(94000, null, false) // Returns 'B' (2 PASS)
```

## Future Enhancements

### Potential Improvements

1. **Historical PASS lookup**: Auto-fetch PASS value from year N-2
2. **Validation endpoint**: Verify classe matches revenue
3. **Batch determination**: Determine classe for multiple doctors
4. **Audit trail**: Log class determination decisions

### Not Implemented (Yet)

- Automatic N-2 year detection from `date_ouverture_droits`
- Integration with external PASS database
- Class change history tracking

## Summary

| Feature | Status |
|---------|--------|
| Backend method | ✅ `IJCalculator::determineClasse()` |
| API endpoint | ✅ `POST /api.php?endpoint=determine-classe` |
| Auto-determination | ✅ In `calculate` endpoint |
| Unit tests | ✅ 9/9 tests pass |
| API tests | ✅ Ready (needs dev server) |
| Backward compatibility | ✅ 114/114 tests pass |
| Documentation | ✅ This file |

---

**Files Modified**:
- `IJCalculator.php` (added determineClasse method)
- `api.php` (added determine-classe endpoint + auto-determination)

**Files Created**:
- `test_determine_classe.php` (backend unit tests)
- `test_api_determine_classe.php` (API endpoint tests)
- `BACKEND_CLASSE_DETERMINATION.md` (this documentation)

**Author**: Claude Code
**Date**: 2025-11-12
**Status**: ✅ Production Ready
