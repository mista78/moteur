# Test Failures Investigation Results

## Summary

Out of 13 test cases, **5 are failing**:
- **3 cases** with 0 payable days (mock2, mock4, mock6)
- **1 case** with incorrect rate for age 70+ (mock8)
- **1 case** with over-calculation due to current_date issue (mock14)

---

## Issue #1: Zero Payable Days (mock2, mock4, mock6)

### mock2.json - **CRITICAL ISSUE: No date-effet calculated**
**Status**: ❌ FAILED - Expected 17318.92€, got 0€

**Root Cause**: Arrêt duration is only **43 days**, which is below the 90-day threshold required for benefits to start.

**Details**:
- Arrêt: 2021-07-19 to 2021-08-30 (43 days)
- Age: 66 years
- The `calculateDateEffet()` function only assigns `date-effet` when cumulative days exceed 90 days
- Without `date-effet`, the `calculatePayableDays()` function returns 0 payable days

**Expected Behavior**: According to business rules in CLAUDE.md:
> "Benefits begin after 90 cumulative days of work stoppage"

This test appears to have an **incorrect expected value**. With only 43 days of work stoppage and no prior cumulative days, there should be NO benefits. The expected value of 17318.92€ is incorrect.

---

### mock4.json - **EDGE CASE: Payment start equals payment end**
**Status**: ❌ FAILED - Expected 37875.88€, got 0€

**Root Cause**: Date-effet is calculated as **2023-09-24** (the last day of the arrêt period), causing payment_start == payment_end.

**Details**:
- Arrêt: 2023-06-26 to 2023-09-24 (91 days)
- Date effet calculated: 2023-09-24 (91st day, just crossing the 90-day threshold)
- Attestation date: 2023-09-24 (same as arrêt end date)
- Payment start: 2023-09-24
- Payment end: 2023-09-24
- Code in IJCalculator.php:388-392:
  ```php
  // Si date de début == date de fin, payer 0 jours (règle exclusive VBA)
  if ($paymentStart->format('Y-m-d') === $paymentEnd->format('Y-m-d')) {
      $arretDays = 0;
  }
  ```

**Expected Behavior**: The 90-day rule should provide rights starting on day 91, but when the arrêt ends exactly on day 91, there's a boundary condition issue. The expected value suggests at least 1 day should be paid.

**Potential Fix**: Review whether the date-effet calculation should be inclusive or if there's an off-by-one error in the 90-day threshold logic.

---

### mock6.json - **CRITICAL ISSUE: No date-effet calculated**
**Status**: ❌ FAILED - Expected 31412.61€, got 0€

**Root Cause**: Arrêt duration is only **68 days**, below the 90-day threshold.

**Details**:
- Arrêt: 2023-09-04 to 2023-11-10 (68 days)
- Age: 62 years
- No date-effet assigned because cumulative days (68) < 90
- Attestation date is in the future: 2024-12-27 (but doesn't matter without date-effet)

**Expected Behavior**: Similar to mock2, this test has an **incorrect expected value**. With only 68 days and no previous cumulative days, there should be NO benefits.

---

## Issue #2: Incorrect Rate for Age 70+ (mock8)

### mock8.json - **RATE MAPPING ERROR**
**Status**: ❌ FAILED - Expected 19291.28€, got 8245.22€ (difference: -11046.06€)

**Root Cause**: Using wrong CSV column (taux_b2 = 51.66/52€) instead of the expected higher rate.

**Details**:
- Age: 74 years (>= 70)
- Arrêt: 2020-07-28 to 2021-04-02 (249 days)
- Date effet: 2020-10-26 (after 90 days)
- Payable days: 159 days ✓ (correct)
- Applied rate: 51.66€ and 52€ per day
- Expected total: 19291.28€ suggests rate should be ~121.33€/day (19291.28 / 159)

**Rate Calculation**:
- Current code uses `tier = 2` for age >= 70 → taux_b2
- Expected behavior might require `tier = 1` for age >= 70 → taux_b1 (higher rate)

**From IJCalculator.php:987-1010**:
```php
// Taux 4-6 (période 3 pour 62-69, OU ≥ 70 ans) → colonne 3 pour 62-69, colonne 2 pour 70+
if ($taux >= 4 && $taux <= 6) {
    if ($age !== null && $age >= 70) {
        $tier = 2; // Taux réduit senior pour 70+
    } else {
        $tier = 3; // Taux intermédiaire pour période 3 des 62-69 ans
    }
}
```

**Analysis**: The code is applying the "reduced senior rate" (tier 2) for age 70+, but the expected value suggests a higher rate should apply. This might indicate:
1. The CSV file has incorrect rate values, OR
2. The mapping logic is wrong (should use tier 1 or 3 instead of tier 2)

---

## Issue #3: Over-calculation (mock14)

### mock14.json - **PAYMENT BEYOND CURRENT_DATE**
**Status**: ❌ FAILED - Expected 19215.36€, got 62151.64€ (difference: +42936.28€)

**Root Cause**: Calculating payment until arrêt end date (2024-10-25) instead of respecting current_date limitation.

**Details**:
- Arrêt: 2023-06-07 to 2024-10-25 (507 days)
- Current date: 2024-10-25 (same as arrêt end)
- Date effet: 2023-09-05 (90 days from start)
- Attestation date: 2024-11-30 (future date)
- Calculated payable days: **417 days**
- Expected suggests: **128 days** (19215.36 / 150.12 ≈ 128)

**Expected Behavior**: The test comment says "Payé jusqu'au 25/10/2024" (Paid until 25/10/2024), but the calculated amount pays for 417 days when it should pay significantly fewer.

**Nb_trimestres Issue**:
- Test config: nb_trimestres = 60
- Calculated: nb_trimestres = 30
- Affiliation date: 2017-07-01
- The code auto-calculates trimestres from affiliation_date to current_date

**Analysis**: The discrepancy (417 vs 128 days) suggests either:
1. A last_payment_date should be set to reduce the payment window, OR
2. The attestation_date logic is not properly limiting the payment period, OR
3. There's a business rule about maximum payable days in the first period that's not being applied

---

## Recommendations

### Priority 1 - Fix mock4 (boundary condition)
- Review date-effet calculation for the exact 90-day threshold
- Consider if day 91 should be payable when arrêt ends on day 91

### Priority 2 - Fix mock8 (rate mapping)
- Verify CSV rate values for taux_b1, taux_b2, taux_b3
- Confirm the correct tier mapping for age >= 70
- Expected rate: ~121€/day vs actual ~52€/day

### Priority 3 - Fix mock14 (over-calculation)
- Investigate why 417 days are being paid instead of ~128 days
- Review attestation date handling for future dates
- Check if there's a missing payment limit rule

### Priority 4 - Invalid test data
- **mock2.json**: 43 days < 90 days threshold → Expected value should be 0€
- **mock6.json**: 68 days < 90 days threshold → Expected value should be 0€
- Consider updating these test cases with valid expected values

---

## Next Steps

1. Review the VBA reference code (code.vba) to understand the exact business rules
2. Check taux.csv for correct rate values
3. Fix the rate mapping logic in IJCalculator.php:972-1032
4. Add test cases for boundary conditions (exactly 90 days, 91 days)
5. Document the expected behavior for attestation dates in the future
