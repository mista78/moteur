# Test Réforme 2025 - Multiple Arrêts

## Overview

Comprehensive test suite for the 2025 reform with multiple work stoppages (arrêts). This test validates the correct application of rate calculation rules across various scenarios involving multiple arrêts spanning 2024 and 2025.

## Running the Test

```bash
php test_reforme_2025_multiple_arrets.php
```

## Test Scenarios

### Scenario 1: Three arrêts all in 2024
**Purpose**: Verify that arrêts entirely in 2024 use 2024 DB rates

**Configuration**:
- Arrêt 1: 10 Jan 2024 → 10 Fév 2024 (32 days)
- Arrêt 2: 01 Mai 2024 → 15 Juin 2024 (46 days)
- Arrêt 3: 01 Oct 2024 → 30 Nov 2024 (61 days)
- Class B

**Expected behavior**:
- All days should use 2024 DB rates (160€/day for Class B)
- No PASS formula applied
- No 2025 DB rates applied

---

### Scenario 2: Three arrêts all in 2025
**Purpose**: Verify that arrêts starting in 2025 use PASS formula

**Configuration**:
- Arrêt 1: 01 Fév 2025 → 15 Mar 2025 (43 days)
- Arrêt 2: 10 Juin 2025 → 20 Juil 2025 (41 days)
- Arrêt 3: 05 Oct 2025 → 25 Nov 2025 (52 days)
- Class A

**Expected behavior**:
- All days should use PASS formula (63.52€/day for Class A)
- No DB rates applied

---

### Scenario 3: Mixed arrêts (some 2024, some 2025)
**Purpose**: Verify correct handling of mixed arrêts across different years

**Configuration**:
- Arrêt 1: 01 Juin 2024 → 15 Juil 2024 (45 days) [2024]
- Arrêt 2: 01 Nov 2024 → 20 Déc 2024 (50 days) [2024]
- Arrêt 3: 10 Mar 2025 → 30 Avr 2025 (52 days) [2025]
- Arrêt 4: 15 Aoû 2025 → 30 Sep 2025 (47 days) [2025]
- Class C

**Expected behavior**:
- Arrêts 1-2: Use 2024 DB rates (240€/day or derivatives)
- Arrêts 3-4: Use PASS formula (190.55€/day)
- Average rate should reflect the mix

---

### Scenario 4: Arrêts spanning 2024-2025 boundary
**Purpose**: Verify correct calendar year rate application

**Configuration**:
- Arrêt 1: 15 Nov 2024 → 20 Jan 2025 (crosses year boundary)
- Arrêt 2: 10 Déc 2024 → 15 Fév 2025 (crosses year boundary)
- Class B

**Expected behavior**:
- Days in 2024: Use 2024 DB rates (160€/day)
- Days in 2025: Use 2025 DB rates (200€/day)
- **NOT PASS formula** (because date_effet < 2025-01-01)

**Critical Rule**: For arrêts with `date_effet < 2025-01-01`:
- Days falling in calendar year 2024 → 2024 DB rates
- Days falling in calendar year 2025 → 2025 DB rates
- Does NOT use PASS formula

---

### Scenario 5: Rechute cases crossing years
**Purpose**: Verify rechute detection and rate application across years

**Configuration**:
- Initial arrêt: 01 Mar 2024 → 30 Mai 2024
- Rechute 1: 15 Aoû 2024 → 10 Oct 2024 (within 1 year)
- Rechute 2: 01 Déc 2024 → 15 Fév 2025 (crosses year, still within 1 year)
- New arrêt: 01 Juin 2025 → 31 Juil 2025 (new pathology)
- Class A

**Expected behavior**:
- Rechutes use reduced date-effet (15 days instead of 90)
- Rechute 2 follows calendar year rate rules (2024 DB for Dec days, 2025 DB for Jan-Feb days)
- New arrêt in 2025 uses PASS formula

---

### Scenario 6: All classes comparison
**Purpose**: Verify correct class multipliers across all scenarios

**Configuration**:
- Same arrêts for all classes (A, B, C)
- Arrêt 1: 20 Nov 2024 → 15 Jan 2025
- Arrêt 2: 01 Mar 2025 → 30 Avr 2025

**Expected behavior**:
- Class B ≈ 2× Class A
- Class C ≈ 3× Class A
- All classes have same number of paid days

**Verification**:
- Class A: ~63.52€/day
- Class B: ~127.04€/day (2× Class A)
- Class C: ~190.55€/day (3× Class A)

---

### Scenario 7: Complex case - 6 arrêts over 2 years
**Purpose**: Stress test with maximum complexity

**Configuration**:
- 3 complete arrêts in 2024 (Jan-Feb, Apr-May, Jul-Aug)
- 1 spanning arrêt (Dec 2024 - Jan 2025)
- 2 complete arrêts in 2025 (Apr-May, Sep-Oct)
- Class B

**Expected behavior**:
- Correct mix of 2024 DB, 2025 DB, and PASS formula rates
- Proper handling of all date-effet calculations
- No errors or inconsistencies

---

## Key Rules Validated

### 1. Date d'effet Rule
- `date_effet < 2025-01-01` → Use DB rates (calendar year dependent)
- `date_effet >= 2025-01-01` → Use PASS formula

### 2. Calendar Year Rate Rule (Critical!)
For arrêts with `date_effet < 2025-01-01` that span into 2025:
- **Days in 2024** → 2024 DB rates
- **Days in 2025** → 2025 DB rates
- **NOT PASS formula**

### 3. Rechute Rules
- Must have previous arrêt with date-effet
- Must NOT be consecutive (gap required)
- Must start within 1 year of previous arrêt end
- Date-effet reduced to 15 days (instead of 90)

### 4. Class Multipliers
- Class A: 1× base rate
- Class B: 2× base rate
- Class C: 3× base rate

## Expected Output

The test produces a comprehensive report showing:
- Each scenario's description
- Calculated totals (amount, days, average rate)
- Expected behavior
- Visual tables for comparisons
- Rule summaries

## Success Criteria

✅ All arrêts in 2024 use correct 2024 DB rates
✅ All arrêts starting in 2025 use PASS formula
✅ Mixed arrêts apply correct rates per year
✅ Spanning arrêts use calendar year rates correctly
✅ Rechutes properly detected and calculated
✅ Class multipliers accurate (2× and 3×)
✅ No calculation errors or exceptions

## Related Documentation

- `REFORME_2025_TAUX.md` - 2025 reform details
- `REGLE_DATE_EFFET_2025.md` - Date d'effet rules
- `CALENDAR_YEAR_RATES_RULE.md` - Calendar year rate logic
- `test_reforme_2025.php` - Basic 2025 reform tests
- `test_daily_rates_by_calendar_year.php` - Daily rate breakdown

## Notes

This test uses **simulated rates** for clarity:
- 2024 DB: A1=80€, B1=160€, C1=240€
- 2025 DB: A1=100€, B1=200€, C1=300€
- PASS formula (46368€): A1=63.52€, B1=127.04€, C1=190.55€

These values make it easy to visually verify which rate system is being applied.
