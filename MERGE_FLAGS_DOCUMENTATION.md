# Merge Flags Documentation

## Overview

The `arrets_merged` response now includes flags to track which arrêts were merged due to consecutive prolongations. This allows the front-end to display merged arrêts with proper visual indicators.

## Response Structure

The API response includes a new key `arrets_merged` with merge tracking flags:

```json
{
  "nb_jours": 265,
  "montant": 19890.9,
  "arrets": [...],
  "arrets_merged": [
    {
      "arret-from-line": "2024-01-02",
      "arret-to-line": "2024-01-25",
      "has_prolongations": true,
      "prolongation_count": 2,
      "is_prolongation": false,
      "prolongation_of": null,
      "merged_arrets": [
        {
          "original_index": 1,
          "from": "2024-01-11",
          "to": "2024-01-17"
        },
        {
          "original_index": 2,
          "from": "2024-01-18",
          "to": "2024-01-25"
        }
      ],
      "date-effet": "2024-04-01",
      ...
    },
    {
      "arret-from-line": "2024-02-05",
      "arret-to-line": "2024-12-31",
      "has_prolongations": false,
      "prolongation_count": 0,
      "is_prolongation": false,
      "prolongation_of": null,
      "date-effet": "2024-05-05",
      ...
    }
  ],
  "payment_details": [...],
  ...
}
```

## Merge Flags Reference

### Core Flags

| Flag | Type | Description | Example Values |
|------|------|-------------|----------------|
| `has_prolongations` | boolean | `true` if this arrêt has other arrêts merged into it | `true`, `false`, or not set |
| `prolongation_count` | integer | Number of arrêts merged into this one | `0`, `1`, `2`, `3`, etc. |
| `is_prolongation` | boolean | Always `false` in merged array (prolongations are removed) | `false` |
| `prolongation_of` | null/integer | Always `null` in merged array | `null` |

### Detailed Merge Info

| Field | Type | Description |
|-------|------|-------------|
| `merged_arrets` | array | List of arrêts that were merged into this one |
| `merged_arrets[].original_index` | integer | Original index of the merged arrêt in input array |
| `merged_arrets[].from` | string (date) | Start date of the merged arrêt |
| `merged_arrets[].to` | string (date) | End date of the merged arrêt |

## Example Scenarios

### Scenario 1: Arrêts with Prolongations

**Input**: 3 consecutive arrêts
```json
[
  {"arret-from-line": "2024-01-02", "arret-to-line": "2024-01-10"},
  {"arret-from-line": "2024-01-11", "arret-to-line": "2024-01-17"},
  {"arret-from-line": "2024-01-18", "arret-to-line": "2024-01-25"}
]
```

**Output in `arrets_merged`**: 1 merged arrêt
```json
[
  {
    "arret-from-line": "2024-01-02",
    "arret-to-line": "2024-01-25",
    "has_prolongations": true,
    "prolongation_count": 2,
    "merged_arrets": [
      {"original_index": 1, "from": "2024-01-11", "to": "2024-01-17"},
      {"original_index": 2, "from": "2024-01-18", "to": "2024-01-25"}
    ]
  }
]
```

### Scenario 2: Standalone Arrêt (No Merge)

**Input**: Non-consecutive arrêt
```json
[
  {"arret-from-line": "2024-02-05", "arret-to-line": "2024-12-31"}
]
```

**Output in `arrets_merged`**: Same arrêt, no merge flags
```json
[
  {
    "arret-from-line": "2024-02-05",
    "arret-to-line": "2024-12-31",
    "has_prolongations": false,
    "prolongation_count": 0,
    "is_prolongation": false,
    "prolongation_of": null
  }
]
```

### Scenario 3: Mixed Consecutive and Non-Consecutive

**Input**: 5 arrêts with 2 groups of consecutive + 1 standalone
```json
[
  {"arret-from-line": "2024-01-02", "arret-to-line": "2024-01-10"},
  {"arret-from-line": "2024-01-11", "arret-to-line": "2024-01-17"}, // Consecutive with [0]
  {"arret-from-line": "2024-02-05", "arret-to-line": "2024-02-14"}, // Gap - standalone
  {"arret-from-line": "2024-02-15", "arret-to-line": "2024-02-22"}, // Consecutive with [2]
  {"arret-from-line": "2024-03-01", "arret-to-line": "2024-03-10"}  // Consecutive with [3]
]
```

**Output in `arrets_merged`**: 2 merged arrêts
```json
[
  {
    "arret-from-line": "2024-01-02",
    "arret-to-line": "2024-01-17",
    "has_prolongations": true,
    "prolongation_count": 1,
    "merged_arrets": [
      {"original_index": 1, "from": "2024-01-11", "to": "2024-01-17"}
    ]
  },
  {
    "arret-from-line": "2024-02-05",
    "arret-to-line": "2024-03-10",
    "has_prolongations": true,
    "prolongation_count": 2,
    "merged_arrets": [
      {"original_index": 3, "from": "2024-02-15", "to": "2024-02-22"},
      {"original_index": 4, "from": "2024-03-01", "to": "2024-03-10"}
    ]
  }
]
```

## Front-End Usage

### Display Merge Badges

```javascript
// Check if arrêt has prolongations
if (arret.has_prolongations) {
  displayBadge(`Merged ${arret.prolongation_count} prolongation(s)`);
}
```

### Show Merge Details in Tooltip

```javascript
if (arret.merged_arrets && arret.merged_arrets.length > 0) {
  const tooltip = arret.merged_arrets.map(m =>
    `Merged: ${m.from} → ${m.to}`
  ).join('\n');

  showTooltip(tooltip);
}
```

### Visual Indicators

```javascript
// Different styling for merged arrêts
if (arret.has_prolongations) {
  element.classList.add('arrêt-merged');
  element.setAttribute('data-merge-count', arret.prolongation_count);
}
```

### Expandable Merge Details

```html
<!-- Show expandable list of merged arrêts -->
<div class="arrêt-item">
  <span>2024-01-02 → 2024-01-25</span>

  <button class="merge-toggle" v-if="arret.has_prolongations">
    📋 Show {{ arret.prolongation_count }} merged arrêts
  </button>

  <ul class="merged-details" v-if="expanded">
    <li v-for="merged in arret.merged_arrets">
      ↳ {{ merged.from }} → {{ merged.to }}
    </li>
  </ul>
</div>
```

## Business Rules

### What Makes Arrêts Consecutive?

Arrêts are considered consecutive (and merged) when:
1. **Next business day matches**: The next business day after arrêt A ends = arrêt B starts
2. **Business days only**: Weekends (Sat, Sun) and French public holidays are skipped
3. **Sequential order**: Arrêts are sorted by start date before merging

Example:
- Arrêt A: Jan 10 (Wednesday) to Jan 17 (Wednesday)
- Arrêt B: Jan 18 (Thursday) - **CONSECUTIVE** (next business day)
- Arrêt C: Jan 20 (Saturday) - **NOT consecutive** (gap on Friday Jan 19)

### French Public Holidays Considered

- New Year's Day (Jan 1)
- Easter Monday (mobile)
- Labor Day (May 1)
- WWII Victory Day (May 8)
- Ascension (mobile)
- Whit Monday (mobile)
- Bastille Day (July 14)
- Assumption (Aug 15)
- All Saints (Nov 1)
- Armistice (Nov 11)
- Christmas (Dec 25)

## Implementation Details

### File: `Services/DateService.php`

**Method**: `mergeProlongations(array $arrets): array`

The method:
1. Sorts arrêts by start date
2. Iterates through each arrêt
3. Checks if consecutive using `addOneBusinessDay()`
4. Merges consecutive arrêts and adds flags:
   - `has_prolongations = true`
   - `prolongation_count` incremented for each merge
   - `merged_arrets[]` array stores merge details
5. Returns merged array with all flags

**Flags are stored in**: `$mergedArrets` property and exposed via `getMergedArrets()`

**Added to response in**: `Services/AmountCalculationService.php` - `calculateTotalAmount()` method

## Testing

### Test Files

- `test_lstarray.php` - Basic merge demonstration
- `test_merge_flags.php` - Comprehensive flag display
- `test_mock2_merged.php` - Real scenario with mock data
- `test_arrets_merged.php` - New key verification

### Running Tests

```bash
# Unit tests (all pass)
php run_all_tests.php

# Merge flags demonstration
php test_merge_flags.php

# Mock data test
php test_mock2_merged.php
```

## Backward Compatibility

✅ **Fully backward compatible**
- Existing `arrets` key unchanged
- New `arrets_merged` key added alongside
- All existing tests pass (114/114)
- Front-end can ignore new flags if not needed

## Benefits for Front-End

1. **Visual clarity**: Show which arrêts were merged
2. **Detailed tracking**: Access original dates of merged arrêts
3. **Better UX**: Display merge count badges
4. **Expandable details**: Show/hide merged arrêt list
5. **Audit trail**: Full transparency of merge operations
6. **Flexible display**: Choose between merged or original views

## API Endpoint

All endpoints that return calculation results include the flags:

```bash
POST /api.php?endpoint=calculate
```

Response includes `arrets_merged` with all merge flags.
