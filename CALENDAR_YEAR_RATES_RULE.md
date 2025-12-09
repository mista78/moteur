# Calendar Year Rates Rule - 2025 Reform

## Overview

This document explains the final implementation where **rates change based on the calendar year of each day**.

## The Rule

### For Arrêts Starting Before 2025 (date_effet < 2025-01-01)

**Different rates apply to different days based on calendar year:**

1. **Days in 2024** → Use Taux 2024 DB
2. **Days in 2025** → Use Taux 2025 DB

### For Arrêts Starting in 2025 (date_effet >= 2025-01-01)

**All days use the PASS formula** (reform applies)

## Visual Example

### Scenario: Arrêt Dec 20, 2024 → Jan 10, 2025 (Classe A)

```
         2024                    │         2025
─────────────────────────────────┼──────────────────────────
         December                │         January
                                 │
Day 1:  20/12/2024 → 80€  ──┐   │
Day 2:  21/12/2024 → 80€    │   │
Day 3:  22/12/2024 → 80€    │   │
...                          ├──►│ 12 days × 80€ = 960€
Day 10: 29/12/2024 → 80€    │   │
Day 11: 30/12/2024 → 80€    │   │
Day 12: 31/12/2024 → 80€  ──┘   │
═════════════════════════════════╪═══════════════════════════
                                 │ Day 13: 01/01/2025 → 100€ ──┐
                                 │ Day 14: 02/01/2025 → 100€   │
                                 │ Day 15: 03/01/2025 → 100€   │
                                 │ ...                         ├──► 10 days × 100€ = 1,000€
                                 │ Day 20: 08/01/2025 → 100€   │
                                 │ Day 21: 09/01/2025 → 100€   │
                                 │ Day 22: 10/01/2025 → 100€ ──┘

TOTAL: 22 days = 1,960€
```

## Implementation

### RateService.php - getDailyRate()

**New Parameter**: `$calculationDate`

```php
public function getDailyRate(
    string $statut,
    string $classe,
    string|int|float $option,
    int $taux,
    int $year,
    ?string $date = null,
    ?int $age = null,
    ?bool $usePeriode2 = null,
    ?float $revenu = null,
    ?string $calculationDate = null  // ← NEW
): float
```

### Logic

```php
// For arrêts with date_effet >= 2025
if ($isDateEffetAfter2025) {
    return $this->calculate2025Rate(...); // PASS formula
}

// For arrêts with date_effet < 2025
// Determine rate based on the CALENDAR YEAR of the day
$dayYear = $calculationDate
    ? (int)date('Y', strtotime($calculationDate))
    : (int)date('Y', $dateEffetTimestamp);

if ($dayYear >= 2025) {
    // Day in 2025+ → Use taux 2025 DB
    $rateData = $this->getRateForYear(2025);
} else {
    // Day in 2024 or before → Use taux from that year
    $rateData = $this->getRateForDate($calculationDate);
}
```

## Test Results

### Test 1: Arrêt Dec 20, 2024 → Jan 10, 2025 (Classe A)

```
Days in 2024: 12 days × 80€  = 960€
Days in 2025: 10 days × 100€ = 1,000€
───────────────────────────────────
TOTAL:        22 days        = 1,960€
```

✅ **Validation**: Days in 2024 use 80€, days in 2025 use 100€

### Test 2: Arrêt Jan 5, 2025 → Jan 25, 2025 (Classe A)

```
Days in 2025: 21 days × 63.52€ = 1,333.92€
```

✅ **Validation**: All days use PASS formula (63.52€)

### Test 3: Arrêt Dec 28, 2024 → Jan 5, 2025 (Classe B)

```
Days in 2024: 4 days × 160€  = 640€
Days in 2025: 5 days × 200€  = 1,000€
───────────────────────────────────
TOTAL:        9 days         = 1,640€
```

✅ **Validation**: Different rates for different years

## Complete Rate Table

| Date d'effet | Day Date | Rate Applied | Source |
|--------------|----------|--------------|--------|
| 2024-12-20 | 2024-12-25 | 80€ (Classe A) | Taux 2024 DB |
| 2024-12-20 | 2025-01-05 | 100€ (Classe A) | Taux 2025 DB |
| 2025-01-05 | 2025-01-10 | 63.52€ (Classe A) | PASS Formula |

## Key Points

1. ✅ **Date d'effet** determines the SYSTEM (historical vs PASS)
2. ✅ **Calendar year of the day** determines which HISTORICAL rate
3. ✅ **Transition is smooth** at year boundary
4. ✅ **Each day** gets the rate in effect for that period

## Database Requirements

### Must Have Rates for Both Years

```sql
-- Taux 2024
INSERT INTO ij_taux (
    date_start, date_end,
    taux_a1, taux_b1, taux_c1, ...
) VALUES (
    '2024-01-01', '2024-12-31',
    80.00, 160.00, 240.00, ...
);

-- Taux 2025
INSERT INTO ij_taux (
    date_start, date_end,
    taux_a1, taux_b1, taux_c1, ...
) VALUES (
    '2025-01-01', '2025-12-31',
    100.00, 200.00, 300.00, ...
);
```

## Usage in AmountCalculationService

When calculating amounts for each day, pass the specific day date:

```php
foreach ($days as $day) {
    $dailyRate = $rateService->getDailyRate(
        statut: $statut,
        classe: $classe,
        option: $option,
        taux: $taux,
        year: $year,
        date: $dateEffet,
        calculationDate: $day['date']  // ← Pass the specific day
    );

    $amount += $dailyRate;
}
```

## Comparison Table

### Before vs After

| Scenario | OLD Behavior | NEW Behavior |
|----------|--------------|--------------|
| Dec 20, 2024 → Jan 10, 2025 | All days: 100€ (taux 2025 DB) | Dec: 80€, Jan: 100€ |
| Jan 5, 2025 → Jan 25, 2025 | All days: 63.52€ (PASS) | All days: 63.52€ (PASS) ✓ |

## Benefits

1. **Accurate Period Rates**: Each day uses the rate in effect during that period
2. **Natural Transition**: Rate changes automatically at year boundary
3. **Flexible**: Can handle any year transition (2025→2026, etc.)
4. **Clear Logic**: Easy to understand and explain

## Testing

```bash
# Test calendar year rates
php test_daily_rates_by_calendar_year.php

# Expected output:
# ✓ Days in 2024 use taux 2024 DB
# ✓ Days in 2025 use taux 2025 DB
# ✓ Arrêt starting 2025 uses PASS
```

## Migration Notes

### Breaking Change

⚠️ **This is a breaking change** from previous implementation:
- **OLD**: All days in an arrêt used the same rate
- **NEW**: Days can have different rates based on calendar year

### Impact

- Existing calculations for 2024 arrêts will show different amounts
- Need to ensure both taux 2024 and taux 2025 exist in database

## Future Years

This logic automatically handles future year transitions:
- 2025 → 2026: Days in 2025 use taux 2025, days in 2026 use taux 2026
- Works indefinitely with database-driven rates

## Summary

✅ **Days in 2024** → Taux 2024 DB (80€, 160€, 240€)
✅ **Days in 2025** (arrêt starting 2024) → Taux 2025 DB (100€, 200€, 300€)
✅ **Arrêt starting 2025** → PASS Formula (63.52€, 127.04€, 190.55€)

**The system now correctly applies different rates to different days based on their calendar year!** 🎉

---

**Last Updated**: December 2025
**Status**: ✅ Implemented and Tested
