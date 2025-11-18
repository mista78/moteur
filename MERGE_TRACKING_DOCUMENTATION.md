# Merge Tracking Feature

## Overview

The `mergeProlongations()` method in `DateService` now tracks which original arrets are merged together when consecutive work stoppages are combined.

## Feature Description

When multiple arrets are consecutive (one starts the day after another ends), they are merged into a single arret with an extended period. The merge tracking feature adds metadata to identify which original arrets were combined.

## New Fields Added

Each arret returned by `mergeProlongations()` now includes:

### `merged_arret_indices` (array) - **Only present when merge occurs**
- **Type**: Array of integers
- **Description**: Contains the original indices of all arrets that were merged into this arret
- **When present**: Only added when 2 or more consecutive arrets are merged
- **When absent**: If an arret stands alone (not merged), this field does not exist
- **Example**: `[0, 1, 2]` means original arrets at positions 0, 1, and 2 were merged

### `original_index` (integer) - **Always present**
- **Type**: Integer
- **Description**: The position of this arret in the original input array (before merging)
- **Always present**: This field exists for all arrets, whether merged or not
- **Example**: `0` means this was the first arret in the original input

## Examples

### Example 1: Three Consecutive Arrets (All Merged)

**Input:**
```php
$arrets = [
    ['arret-from-line' => '2024-01-01', 'arret-to-line' => '2024-01-10'],
    ['arret-from-line' => '2024-01-11', 'arret-to-line' => '2024-01-20'], // Next day
    ['arret-from-line' => '2024-01-21', 'arret-to-line' => '2024-01-31'], // Next day
];
```

**Output:**
```php
[
    [
        'arret-from-line' => '2024-01-01',
        'arret-to-line' => '2024-01-31',
        'original_index' => 0,
        'merged_arret_indices' => [0, 1, 2]
    ]
]
```

### Example 2: Two Separate Groups

**Input:**
```php
$arrets = [
    ['arret-from-line' => '2024-01-01', 'arret-to-line' => '2024-01-10'],
    ['arret-from-line' => '2024-01-11', 'arret-to-line' => '2024-01-15'], // Next day
    ['arret-from-line' => '2024-02-01', 'arret-to-line' => '2024-02-10'], // Gap
    ['arret-from-line' => '2024-02-11', 'arret-to-line' => '2024-02-20'], // Next day
];
```

**Output:**
```php
[
    [
        'arret-from-line' => '2024-01-01',
        'arret-to-line' => '2024-01-15',
        'original_index' => 0,
        'merged_arret_indices' => [0, 1]
    ],
    [
        'arret-from-line' => '2024-02-01',
        'arret-to-line' => '2024-02-20',
        'original_index' => 2,
        'merged_arret_indices' => [2, 3]
    ]
]
```

### Example 3: No Merges (All Separate)

**Input:**
```php
$arrets = [
    ['arret-from-line' => '2024-01-01', 'arret-to-line' => '2024-01-10'],
    ['arret-from-line' => '2024-02-01', 'arret-to-line' => '2024-02-10'], // Gap
    ['arret-from-line' => '2024-03-01', 'arret-to-line' => '2024-03-10'], // Gap
];
```

**Output:**
```php
[
    [
        'arret-from-line' => '2024-01-01',
        'arret-to-line' => '2024-01-10',
        'original_index' => 0
        // Note: merged_arret_indices NOT present (no merge occurred)
    ],
    [
        'arret-from-line' => '2024-02-01',
        'arret-to-line' => '2024-02-10',
        'original_index' => 1
        // Note: merged_arret_indices NOT present (no merge occurred)
    ],
    [
        'arret-from-line' => '2024-03-01',
        'arret-to-line' => '2024-03-10',
        'original_index' => 2
        // Note: merged_arret_indices NOT present (no merge occurred)
    ]
]
```

## Use Cases

1. **Audit Trail**: Track which original arrets contributed to a merged period
2. **UI Display**: Show users which separate work stoppages were combined
3. **Data Analysis**: Understand prolongation patterns in work stoppages
4. **Debugging**: Trace back from merged results to original input
5. **Conditional Logic**: Check `isset($arret['merged_arret_indices'])` to determine if merge occurred

## Implementation Details

### Location
- **Service**: `DateService.php` (line 172-219)
- **Interface**: `DateCalculationInterface.php` (line 35)
- **Test**: `test_merge_tracking.php`

### Merge Logic
Two arrets are merged if:
- They are consecutive (second starts exactly one day after first ends)
- This includes weekends (arrets can occur on weekends)

### Index Tracking
- Indices are assigned sequentially (0, 1, 2, ...) based on the original input order
- When arrets merge, all contributing indices are stored in `merged_arret_indices`
- The `original_index` preserves the index of the first arret in each merged group
- **Important**: `merged_arret_indices` is only added when an actual merge occurs (2+ arrets combined)

## Code Usage Example

```php
$dateService = new DateService();

$arrets = [
    ['arret-from-line' => '2024-01-01', 'arret-to-line' => '2024-01-10'],
    ['arret-from-line' => '2024-01-11', 'arret-to-line' => '2024-01-20'], // Consecutive
];

$merged = $dateService->mergeProlongations($arrets);

// Result when merge occurs:
// $merged[0]['arret-from-line'] = '2024-01-01'
// $merged[0]['arret-to-line'] = '2024-01-20'
// $merged[0]['original_index'] = 0
// $merged[0]['merged_arret_indices'] = [0, 1]  // Only present because merge occurred

// Check if merge occurred:
foreach ($merged as $arret) {
    if (isset($arret['merged_arret_indices'])) {
        echo "Merged arret combining: " . implode(', ', $arret['merged_arret_indices']) . "\n";
        echo "Total original arrets merged: " . count($arret['merged_arret_indices']) . "\n";
    } else {
        echo "Standalone arret (not merged)\n";
    }
}
```

## Testing

Run the merge tracking test:
```bash
php test_merge_tracking.php
```

All existing tests continue to pass with this feature:
```bash
php run_all_tests.php
```

## Backward Compatibility

- **Fully backward compatible**: Existing code continues to work
- **Non-breaking change**: New fields are added, no existing fields modified
- **All tests pass**: 114/114 tests passing after implementation

## Technical Notes

- The merge happens in `DateService::calculateDateEffet()` (line 244)
- Merge tracking is applied before any other date calculations
- Weekend handling: Consecutive means "next calendar day" including weekends
- Sorted input: Arrets are sorted by start date before merging
