# First Day Logic Documentation

## Overview

The `first_day` field in the `ij_arret` table indicates whether the first day of the arrêt is **paid** (1) or **excused/décompte** (0).

## Business Rule

- `first_day = 1`: First day of arrêt is **PAID** (no décompte)
- `first_day = 0`: First day of arrêt is **EXCUSED** (décompte exists)

## Calculation Logic

The `first_day` value is determined from `payment_details` in the calculation result:

```php
$firstDay = 0;  // Default: first day is excused

// Check if payment starts on the same day as arrêt starts
if ($paymentStart === $arretFrom) {
    $firstDay = 1;  // First day is paid
}

// Or if there's no décompte days and payment exists
if ($decompteDays == 0 && !empty($paymentStart)) {
    $firstDay = 1;  // First day is paid
}
```

## Conditions

### first_day = 1 (Paid)

The first day is **paid** when:
1. `payment_start` equals `arret_from` (payment starts immediately)
2. OR `decompte_days = 0` and payment exists (no waiting period)

**Example:**
```json
{
  "arret_from": "2024-01-15",
  "arret_to": "2024-02-15",
  "payment_start": "2024-01-15",  // Same day!
  "decompte_days": 0,
  "payable_days": 32
}
→ first_day = 1
```

### first_day = 0 (Excused/Décompte)

The first day is **excused** when:
1. `payment_start` is **after** `arret_from` (décompte period exists)
2. OR no payment at all (arrêt still in décompte phase)

**Example 1 - Décompte exists:**
```json
{
  "arret_from": "2022-11-24",
  "arret_to": "2022-12-24",
  "payment_start": "2022-12-06",  // 12 days after start
  "decompte_days": 12,
  "payable_days": 19
}
→ first_day = 0 (12 days décompte)
```

**Example 2 - No payment yet:**
```json
{
  "arret_from": "2021-07-19",
  "arret_to": "2021-08-30",
  "payment_start": "",  // No payment
  "decompte_days": 43,
  "payable_days": 0
}
→ first_day = 0 (still accumulating toward 90-day threshold)
```

## Implementation

### ArretService

The logic is implemented in `Services/ArretService.php`:

```php
private function transformArretToDbFormat(..., ?array $paymentDetail = null) {
    // Determine first_day based on payment_details
    $firstDay = 0;

    if ($paymentDetail !== null) {
        $paymentStart = $paymentDetail['payment_start'] ?? null;
        $arretFrom = $arret['arret-from-line'] ?? $arret['arret_from'] ?? null;

        // If payment starts on same day as arrêt, first day is paid
        if ($paymentStart && $arretFrom && $paymentStart === $arretFrom) {
            $firstDay = 1;
        }

        // If no decompte days, first day is paid
        if (isset($paymentDetail['decompte_days']) &&
            $paymentDetail['decompte_days'] == 0) {
            $firstDay = 1;
        }
    }

    return [
        'first_day' => $firstDay,
        // ... other fields
    ];
}
```

### Using payment_details

The service now uses `payment_details` from calculation results:

```php
public function generateArretRecords(array $calculationResult, array $inputData) {
    $paymentDetails = $calculationResult['payment_details'] ?? [];

    foreach ($paymentDetails as $index => $paymentDetail) {
        $arret = $calculationResult['arrets'][$index] ?? [];
        $mergedData = array_merge($arret, $paymentDetail);

        $record = $this->transformArretToDbFormat(
            $mergedData,
            $adherentNumber,
            $numSinistre,
            $attestationDate,
            $index,
            $paymentDetail  // Pass payment_detail for first_day calculation
        );
    }
}
```

## Real Example (mock2.json)

### Arrêt #4
```
Arrêt dates: 2022-11-24 → 2022-12-24
Payment start: 2022-12-06
Décompte days: 12
Payable days: 19
```

**Analysis:**
- Arrêt starts: November 24
- Payment starts: December 6 (12 days later)
- First 12 days are décompte (not paid)
- **Result: first_day = 0**

### Arrêt #6
```
Arrêt dates: 2023-11-23 → 2024-03-31
Payment start: 2023-12-07
Décompte days: 14
Payable days: 116
```

**Analysis:**
- Arrêt starts: November 23
- Payment starts: December 7 (14 days later)
- First 14 days are décompte
- **Result: first_day = 0**

## Visual Timeline

### first_day = 0 (with décompte)
```
Arrêt:    |-----|-----|-----|-----|-----|-----|
          Nov24 Nov25 Nov26 ...   Dec05 Dec06
Status:   [DÉCOMPTE (12 days)]     [PAYMENT]
                                   ↑
                            payment_start
first_day = 0 (first day NOT paid)
```

### first_day = 1 (no décompte)
```
Arrêt:    |-----|-----|-----|-----|-----|
          Jan15 Jan16 Jan17 Jan18 Jan19
Status:   [PAYMENT immediately]
          ↑
   payment_start = arret_from
first_day = 1 (first day IS paid)
```

## Database Values

| Scenario | payment_start | arret_from | décompte | first_day | Meaning |
|----------|--------------|------------|----------|-----------|---------|
| Immediate payment | 2024-01-15 | 2024-01-15 | 0 | 1 | Paid from day 1 |
| With décompte | 2024-01-25 | 2024-01-15 | 10 | 0 | 10 days unpaid |
| No payment yet | NULL | 2024-01-15 | 43 | 0 | Still accumulating |
| Rechute (no threshold) | 2024-01-15 | 2024-01-15 | 0 | 1 | Paid from day 1 |
| 90-day threshold | 2024-04-14 | 2024-01-15 | 90 | 0 | 90 days unpaid |

## Testing

Run the first_day logic test:

```bash
php test_first_day_logic.php
```

Output shows:
- Payment details for each arrêt
- Décompte days calculation
- first_day determination logic
- Final ij_arret records with first_day values

## Summary

- ✅ `first_day` is calculated from `payment_details`
- ✅ Reflects whether first day of arrêt is paid or excused
- ✅ Handles décompte periods (90-day threshold, 15-day rechute)
- ✅ Automatically determined per arrêt
- ✅ No manual calculation needed
- ✅ Used in `ij_arret` table for accurate tracking

## Related Fields

| Field | Purpose | Relation to first_day |
|-------|---------|---------------------|
| `decompte_days` | Number of unpaid days before payment | If > 0, then first_day = 0 |
| `payment_start` | Date when payment begins | If = arret_from, then first_day = 1 |
| `date_deb_droit` | Rights opening date | Usually same as payment_start |
| `payable_days` | Number of paid days | If 0, then first_day = 0 |

## Business Impact

The `first_day` field allows:
1. **Accurate tracking** of which arrêts had immediate payment
2. **Compliance** with décompte rules (90-day, 15-day thresholds)
3. **Reporting** on payment patterns
4. **Audit trail** of when benefits began
5. **Front-end display** of payment status
