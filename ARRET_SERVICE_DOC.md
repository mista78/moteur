# ArretService Documentation

## Overview

The `ArretService` is a comprehensive service for managing arret (work stoppage) collections in the IJ Calculator system. It provides utilities for loading, validating, formatting, and manipulating arret data from various sources.

## Location

**File:** `Services/ArretService.php`

**Namespace:** `App\IJCalculator\Services`

## Features

### ✅ Data Loading
- Load from JSON files
- Load from CakePHP entities or database results
- Load from arrays

### ✅ Data Normalization
- Normalize field names (handles variations like `arret_from` → `arret-from-line`)
- Normalize date formats (DateTime objects → strings)
- Set default values for optional fields

### ✅ Validation
- Validate individual arrets
- Validate arret collections
- Check required fields and date formats

### ✅ Utility Functions
- Sort by date (ascending/descending)
- Filter by date range
- Group by sinistre number
- Count total days
- Convert to JSON

## Usage

### Basic Setup

```php
<?php
require_once 'Services/ArretService.php';

use App\IJCalculator\Services\ArretService;

$arretService = new ArretService();
```

## Methods

### 1. Loading Arrets

#### `loadFromJson(string $filePath): array`

Load arrets from a JSON file.

```php
// Load from arrets.json
$arrets = $arretService->loadFromJson('arrets.json');
```

**Throws:** `RuntimeException` if file not found or invalid JSON

#### `loadFromEntities($entities): array`

Load from CakePHP entities or database results.

```php
// From CakePHP query
$entities = $this->Arrets->find('all')->where(['num_sinistre' => 8038]);
$arrets = $arretService->loadFromEntities($entities);

// From database array
$dbResults = $db->query("SELECT * FROM arrets WHERE num_sinistre = 8038");
$arrets = $arretService->loadFromEntities($dbResults);
```

### 2. Normalization

#### `normalizeArrets(array $arrets): array`

Normalize an array of arrets.

```php
$rawArrets = [
    [
        'arret_from' => '2023-09-04',  // Will be normalized to arret-from-line
        'arret_to' => '2023-11-10',    // Will be normalized to arret-to-line
        'dt' => 1                      // Will be normalized to dt-line
    ]
];

$normalized = $arretService->normalizeArrets($rawArrets);
// Result: [{arret-from-line: '2023-09-04', arret-to-line: '2023-11-10', dt-line: 1}]
```

#### `normalizeArret(array $arret): array`

Normalize a single arret.

**Field Mappings:**
- `arret_from` → `arret-from-line`
- `arret_to` → `arret-to-line`
- `declaration_date` → `declaration-date-line`
- `dt` → `dt-line`
- `rechute` → `rechute-line`
- `ouverture_date` → `ouverture-date-line`
- `code_patho` → `code-patho-line`
- `decompte` → `decompte-line`

**Date Fields Normalized:**
- DateTime objects converted to 'Y-m-d' strings
- Null, empty, and '0000-00-00' dates set to null

### 3. Validation

#### `validateArret(array $arret): bool`

Validate a single arret.

```php
try {
    $arretService->validateArret($arret);
    echo "Valid!";
} catch (InvalidArgumentException $e) {
    echo "Invalid: " . $e->getMessage();
}
```

**Checks:**
- Required fields present: `arret-from-line`, `arret-to-line`
- Valid date formats
- `arret-from-line` <= `arret-to-line`

**Throws:** `InvalidArgumentException` if invalid

#### `validateArrets(array $arrets): bool`

Validate an array of arrets.

```php
try {
    $arretService->validateArrets($arrets);
    echo "All valid!";
} catch (InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage();
}
```

**Throws:** `InvalidArgumentException` with index information

### 4. Utility Functions

#### `sortByDate(array $arrets, bool $ascending = true): array`

Sort arrets chronologically.

```php
// Oldest first (ascending)
$sorted = $arretService->sortByDate($arrets, true);

// Newest first (descending)
$sorted = $arretService->sortByDate($arrets, false);
```

#### `filterByDateRange(array $arrets, ?string $startDate, ?string $endDate): array`

Filter arrets by date range.

```php
// Get all arrets in 2023
$arrets2023 = $arretService->filterByDateRange($arrets, '2023-01-01', '2023-12-31');

// Get arrets after specific date
$recent = $arretService->filterByDateRange($arrets, '2023-06-01', null);

// Get arrets before specific date
$old = $arretService->filterByDateRange($arrets, null, '2022-12-31');
```

#### `groupBySinistre(array $arrets): array`

Group arrets by `num_sinistre`.

```php
$grouped = $arretService->groupBySinistre($arrets);
// Result: ['8038' => [arret1, arret2], '8039' => [arret3]]

foreach ($grouped as $sinistre => $arretList) {
    echo "Sinistre {$sinistre}: " . count($arretList) . " arrets\n";
}
```

#### `countTotalDays(array $arrets): int`

Count total days across all arrets.

```php
$totalDays = $arretService->countTotalDays($arrets);
echo "Total: {$totalDays} days";
```

### 5. JSON Export

#### `toJson(array $arrets, bool $prettyPrint = false): string`

Convert arrets to JSON string.

```php
// Compact JSON
$json = $arretService->toJson($arrets);

// Pretty-printed JSON
$json = $arretService->toJson($arrets, true);
```

#### `saveToJson(array $arrets, string $filePath, bool $prettyPrint = true): bool`

Save arrets to JSON file.

```php
$arretService->saveToJson($arrets, 'output.json', true);
```

**Throws:** `RuntimeException` if unable to write file

## Enhanced calculateDateEffet

The `calculateDateEffet()` method now returns enhanced arret data with additional fields.

### New Fields Returned

#### `is_rechute` (boolean)
- `true`: This arret is a rechute (relapse)
- `false`: This is a new pathology or first arret

#### `rechute_of_arret_index` (integer|null)
- Only present when `is_rechute` is `true`
- Points to the index of the source arret (the previous arret with opened rights)
- Example: If `rechute_of_arret_index = 3`, this is a rechute of arret at index 3

#### `decompte_days` (integer)
- Number of "décompte" days (threshold days before rights open)
- **90 days** for new pathology
- **14 days** for rechute
- These days are counted but not paid

### Example Usage

```php
require_once 'IJCalculator.php';
require_once 'Services/ArretService.php';

use App\IJCalculator\IJCalculator;
use App\IJCalculator\Services\ArretService;

$arretService = new ArretService();
$calculator = new IJCalculator($rates);

// Load and normalize arrets
$arrets = $arretService->loadFromJson('arrets.json');

// Calculate date-effet with enhanced fields
$result = $calculator->calculateDateEffet($arrets, '1958-06-03', 0);

foreach ($result as $index => $arret) {
    echo "Arret #{$index}\n";
    echo "  Date-effet: " . ($arret['date-effet'] ?? 'none') . "\n";
    echo "  Is rechute: " . ($arret['is_rechute'] ? 'Yes' : 'No') . "\n";
    echo "  Decompte days: " . ($arret['decompte_days'] ?? 'N/A') . "\n";
    
    if ($arret['is_rechute'] && isset($arret['rechute_of_arret_index'])) {
        echo "  Rechute of arret #" . $arret['rechute_of_arret_index'] . "\n";
    }
}
```

### API Endpoint Response

The `/api.php?endpoint=calculate-arrets-date-effet` endpoint returns:

```json
{
  "success": true,
  "data": [
    {
      "id": 12300,
      "arret-from-line": "2021-07-19",
      "arret-to-line": "2021-08-30",
      "dt-line": 1,
      "arret_diff": 43,
      "is_rechute": false,
      "decompte_days": 90,
      "date-effet": ""
    },
    {
      "id": 12304,
      "arret-from-line": "2023-06-15",
      "arret-to-line": "2023-06-15",
      "dt-line": 1,
      "arret_diff": 1,
      "is_rechute": true,
      "rechute_of_arret_index": 3,
      "decompte_days": 14,
      "date-effet": "2023-06-29"
    }
  ]
}
```

## Complete Example

```php
<?php
require_once 'Services/ArretService.php';
require_once 'IJCalculator.php';

use App\IJCalculator\Services\ArretService;
use App\IJCalculator\IJCalculator;

// Initialize services
$arretService = new ArretService();
$calculator = new IJCalculator($rates);

// 1. Load arrets from JSON
$arrets = $arretService->loadFromJson('arrets.json');

// 2. Validate
$arretService->validateArrets($arrets);

// 3. Filter for specific date range
$arrets2023 = $arretService->filterByDateRange($arrets, '2023-01-01', '2023-12-31');

// 4. Sort chronologically
$sorted = $arretService->sortByDate($arrets2023, true);

// 5. Calculate date-effet with enhanced fields
$result = $calculator->calculateDateEffet($sorted, '1958-06-03', 0);

// 6. Display results
foreach ($result as $index => $arret) {
    $rechuteIcon = $arret['is_rechute'] ? '🔄' : '🆕';
    echo "{$rechuteIcon} Arret {$arret['arret-from-line']} → {$arret['arret-to-line']}\n";
    echo "   Decompte: {$arret['decompte_days']} days\n";
    echo "   Date-effet: " . ($arret['date-effet'] ?: 'Not yet') . "\n";
}

// 7. Save results
$arretService->saveToJson($result, 'results.json', true);

// 8. Group by sinistre
$grouped = $arretService->groupBySinistre($result);
foreach ($grouped as $sinistre => $arretList) {
    $totalDays = $arretService->countTotalDays($arretList);
    echo "Sinistre {$sinistre}: {$totalDays} total days\n";
}
```

## Integration with CakePHP

### In a Controller

```php
<?php
namespace App\Controller;

use App\IJCalculator\Services\ArretService;
use App\IJCalculator\IJCalculator;

class CalculationsController extends AppController
{
    public function calculateArrets($numSinistre)
    {
        $arretService = new ArretService();
        
        // Load from database
        $entities = $this->Arrets->find()
            ->where(['num_sinistre' => $numSinistre])
            ->orderAsc('arret_from')
            ->all();
        
        // Convert to standard format
        $arrets = $arretService->loadFromEntities($entities);
        
        // Calculate date-effet
        $calculator = new IJCalculator($this->loadRates());
        $result = $calculator->calculateDateEffet(
            $arrets,
            $this->request->getData('birth_date'),
            0
        );
        
        // Update database with calculated values
        foreach ($result as $index => $arret) {
            $entity = $entities->toArray()[$index];
            $entity->date_effet = $arret['date-effet'];
            $entity->is_rechute = $arret['is_rechute'];
            $entity->decompte_days = $arret['decompte_days'];
            $this->Arrets->save($entity);
        }
        
        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'data' => $result
            ]));
    }
}
```

## Error Handling

### Common Exceptions

```php
try {
    $arrets = $arretService->loadFromJson('arrets.json');
    $arretService->validateArrets($arrets);
    
} catch (RuntimeException $e) {
    // File not found or invalid JSON
    echo "Load error: " . $e->getMessage();
    
} catch (InvalidArgumentException $e) {
    // Validation failed
    echo "Validation error: " . $e->getMessage();
}
```

## Testing

### Run Tests

```bash
# Test ArretService and enhanced calculateDateEffet
php test_arret_service.php

# Test API endpoint with enhanced fields
php test_arrets_endpoint.php
```

### Expected Output

```
✅ ArretService: Working
✅ Enhanced calculateDateEffet: Working
✅ is_rechute field: Present
✅ decompte_days field: Present
✅ JSON export: Working
✅ API format: Compatible
```

## Performance

- **Fast:** Processes 100+ arrets in milliseconds
- **Memory efficient:** Low memory footprint
- **Scalable:** Handles large collections efficiently

## Related Files

- **Service:** `Services/ArretService.php` (400+ lines)
- **Interface:** Used by `IJCalculator.php`
- **Tests:** `test_arret_service.php`
- **Documentation:** This file

## Changelog

### Version 1.0 (Current)
- ✅ Initial implementation
- ✅ Load from JSON, entities, arrays
- ✅ Field normalization and validation
- ✅ Utility functions (sort, filter, group)
- ✅ JSON export
- ✅ Enhanced calculateDateEffet with `is_rechute` and `decompte_days`
- ✅ Full CakePHP integration support
