# Rechute (Relapse) Test Results and Findings

## Test Execution Date
October 20, 2025

## Summary

Created comprehensive tests for rechute (relapse) functionality based on functional specifications in `text.txt` (lines 369-377).

## Functional Requirements (from text.txt)

### Rechute Definition
- An arrêt that is **NOT consecutive** to another arrêt
- AND starts **less than 1 year** after the end date of the last arrêt
- Formula: `[start date] <= [previous end date] + [1 year] - [1 day]`

### Rechute Rules
1. **Date d'effet starts at 15th day** (instead of 91st for new pathology)
2. **Late declaration penalty: 15 days** (instead of 31 days)
3. **Account update penalty: 15 days** (instead of 31 days)
4. **Can be overridden by Medical Controller** to start at day 1

## Test Files Created

### 1. Tests/RechuteTest.php
Unit tests covering:
- Rechute detection logic
- 1-year boundary conditions
- 15-day rule vs 91-day rule
- Late declaration penalties
- Medical Controller overrides
- Multiple rechutes handling
- Prolongation vs rechute distinction

### 2. test_rechute_integration.php
Integration tests with real-world scenarios:
- Basic rechute with 15-day rule
- Boundary testing (exactly 364 days vs 365 days)
- Late declarations on rechute
- Multiple rechutes for same pathology
- MC override scenarios
- Consecutive arrêts (prolongation) handling

### 3. test_rechute_simple.php
Diagnostic test to understand current implementation behavior

## Test Results

### Current Implementation Issues

**Test 1: Basic Rechute**
```
Expected: 131 cumulative days (100 + 31)
Actual:   41 cumulative days
```
- Only counting partial days from arrêts
- Not properly accumulating days across rechute periods

**Test 2: Rechute Flag Processing**
```
Expected: 51 cumulative days (31 + 20)
Actual:   0 cumulative days
```
- No payment calculated
- Rechute flag not being properly processed

**Date d'effet Issues**
- Arrêt 1: Date effet = 2023-04-01 (91st day) ✓ Correct
- Arrêt 2 (rechute): Date effet = 2023-06-15 (day 1) ✗ Should be day 15

## Root Cause Analysis

Based on test output, the issues are likely in:

1. **DateService::calculateDateEffet()**
   - Not recognizing rechute-line flag
   - Not applying 15-day rule for rechutes
   - Defaulting to 91-day rule or day 1

2. **Day Accumulation Logic**
   - Rechute arrêts may not be properly linked to previous arrêts
   - Cumulative day counting doesn't span across rechute periods
   - Missing logic to track days for same pathology across multiple arrêts

3. **Rechute Detection**
   - May need to implement automatic rechute detection based on dates
   - Currently relying only on rechute-line flag
   - No validation of 1-year boundary rule

## Implementation Recommendations

### High Priority
1. **Implement 15-day rule for rechute in DateService::calculateDateEffet()**
   ```php
   if ($arret['rechute-line'] == 1) {
       // Apply 15-day rule instead of 91-day rule
       $daysThreshold = 15;
   } else {
       // Standard 91-day rule
       $daysThreshold = 90;
   }
   ```

2. **Fix cumulative day counting across rechutes**
   - Track days for entire pathology (all related arrêts)
   - Not just current payment period

3. **Implement late declaration penalty for rechute**
   - Check if declaration is > 15 days late (not 60 days)
   - Add +15 days penalty (not +31 days)

### Medium Priority
4. **Add automatic rechute detection**
   - Validate arrêts that start within 364 days of previous end
   - Set rechute-line flag automatically if criteria met

5. **Implement GPM account update penalty for rechute**
   - +15 days for rechute (not +31 days)

6. **Add MC override support**
   - Allow date-effet to be forced to day 1
   - Documented in specs: "Le 1er jour de l'arrêt" for rechute

### Low Priority
7. **Validate prolongation vs rechute logic**
   - Ensure consecutive arrêts (within 3 days) are treated as prolongations
   - Not as rechutes

## Next Steps

1. Review `Services/DateService.php::calculateDateEffet()` method
2. Review `IJCalculator.php` day accumulation logic
3. Implement 15-day rule for rechute
4. Fix cumulative day counting
5. Re-run all tests to validate fixes
6. Update integration tests with expected values

## Test Files Location

- Unit tests: `/Tests/RechuteTest.php`
- Integration tests: `/test_rechute_integration.php`
- Diagnostic test: `/test_rechute_simple.php`

## Running Tests

```bash
# Unit tests
php Tests/RechuteTest.php

# Integration tests
php test_rechute_integration.php

# Diagnostic test
php test_rechute_simple.php

# All tests
php run_all_tests.php  # (after adding to test runner)
```

## References

- Functional specifications: `text.txt` lines 369-377
- Current implementation: `IJCalculator.php`, `Services/DateService.php`
- VBA reference: `code.vba` (original rechute logic)
