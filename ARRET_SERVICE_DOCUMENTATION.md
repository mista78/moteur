# ArretService Documentation

## Overview

`ArretService` generates `ij_arret` table records from IJCalculator results, matching your database schema perfectly. It handles field mapping, data transformation, validation, and SQL generation.

## Database Schema

```sql
CREATE TABLE `ij_arret` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adherent_number` varchar(7) NOT NULL,
  `code_pathologie` varchar(2) NOT NULL,
  `num_sinistre` int(11) NOT NULL,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `date_prolongation` date DEFAULT NULL,
  `first_day` tinyint(4) DEFAULT NULL,
  `date_declaration` date DEFAULT NULL,
  `DT_excused` tinyint(4) DEFAULT NULL,
  `valid_med_controleur` tinyint(4) DEFAULT NULL,
  `cco_a_jour` tinyint(4) DEFAULT NULL,
  `date_dern_attestation` date DEFAULT NULL,
  `date_deb_droit` date DEFAULT NULL,
  `date_deb_dr_force` date DEFAULT NULL,
  `taux` float DEFAULT NULL,
  `NOARRET` int(4) DEFAULT NULL,
  `version` tinyint(4) NOT NULL DEFAULT 1,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`adherent_number`) REFERENCES `adherent_infos`,
  FOREIGN KEY (`code_pathologie`) REFERENCES `pathologie`,
  FOREIGN KEY (`num_sinistre`) REFERENCES `ij_sinistre`
);
```

## Field Mapping

| Database Field | Source | Notes |
|----------------|--------|-------|
| `adherent_number` | Input (required) | Must be exactly 7 characters |
| `code_pathologie` | `code-patho-line` or `code_pathologie` | Required, 2 chars |
| `num_sinistre` | Input (required) | Integer FK to ij_sinistre |
| `date_start` | `arret-from-line` | Arrêt start date |
| `date_end` | `arret-to-line` | Arrêt end date |
| `date_prolongation` | Calculated from `merged_arrets` | Last merged arrêt's end date |
| `first_day` | Calculated from `payment_details` | 1 = paid, 0 = excused |
| `decompte_days` | From `payment_details` | Number of non-paid days |
| `date_declaration` | `declaration-date-line` | Declaration date |
| `DT_excused` | Inverted from `dt-line` | 1 = excused, 0 = not excused |
| `valid_med_controleur` | `valid_med_controleur` | Medical controller validation |
| `cco_a_jour` | `cco_a_jour` | Account up to date flag |
| `date_dern_attestation` | Input `attestation_date` | Last attestation date |
| `date_deb_droit` | `date-effet` or `ouverture-date-line` | Rights opening date |
| `date_deb_dr_force` | `date_deb_dr_force` | Forced rights opening date |
| `taux` | `taux` or `taux_number` | Rate number or value |
| `NOARRET` | `NOARRET` | Mainframe arrêt number |
| `version` | Default: 1 | Record version |
| `actif` | Default: 1 | Active flag (0 for merged) |

## Usage

### Basic Usage

```php
require_once 'Services/ArretService.php';
require_once 'IJCalculator.php';

use App\IJCalculator\IJCalculator;
use App\IJCalculator\Services\ArretService;

$calculator = new IJCalculator('taux.csv');
$arretService = new ArretService();

// Input data with required fields
$inputData = [
    'adherent_number' => '1234567',  // Required: 7 chars
    'num_sinistre' => 12345,         // Required: integer
    'arrets' => [...],               // Arrêts data
    'attestation_date' => '2024-06-12',
    // ... other calculation params
];

// Calculate
$result = $calculator->calculateAmount($inputData);

// Generate ij_arret records
$arretRecords = $arretService->generateArretRecords($result, $inputData);

// Validate records
foreach ($arretRecords as $record) {
    if (!$arretService->validateRecord($record)) {
        throw new Exception('Invalid record');
    }
}
```

### Generate SQL

```php
// Single INSERT per record
foreach ($arretRecords as $record) {
    $sql = $arretService->generateInsertSQL($record);
    echo $sql . "\n";
}

// Batch INSERT (all records in one statement)
$batchSQL = $arretService->generateBatchInsertSQL($arretRecords);
echo $batchSQL;
```

### Execute in Database

```php
// Using PDO
$pdo = new PDO($dsn, $user, $password);

$batchSQL = $arretService->generateBatchInsertSQL($arretRecords);
$pdo->exec($batchSQL);
```

### Using CakePHP ORM

```php
// In a CakePHP controller or service
use Cake\ORM\TableRegistry;

$ArretTable = TableRegistry::getTableLocator()->get('IjArret');

foreach ($arretRecords as $recordData) {
    $entity = $ArretTable->newEntity($recordData);
    if (!$ArretTable->save($entity)) {
        // Handle errors
        debug($entity->getErrors());
    }
}
```

## Methods Reference

### `generateArretRecords(array $calculationResult, array $inputData): array`

Generates ij_arret records from calculation results.

**Parameters:**
- `$calculationResult`: Result from `IJCalculator->calculateAmount()`
- `$inputData`: Original input with `adherent_number`, `num_sinistre`, `attestation_date`

**Returns:** Array of records ready for database insertion

**Example:**
```php
$records = $arretService->generateArretRecords($result, $inputData);
// Returns: [
//   ['adherent_number' => '1234567', 'num_sinistre' => 12345, ...],
//   ['adherent_number' => '1234567', 'num_sinistre' => 12345, ...],
// ]
```

### `generateInsertSQL(array $record): string`

Generates SQL INSERT statement for a single record.

**Example:**
```php
$sql = $arretService->generateInsertSQL($record);
// Returns: "INSERT INTO `ij_arret` (...) VALUES (...);"
```

### `generateBatchInsertSQL(array $records): string`

Generates batch SQL INSERT for multiple records.

**Example:**
```php
$sql = $arretService->generateBatchInsertSQL($records);
// Returns: "INSERT INTO `ij_arret` (...) VALUES (...), (...);"
```

### `validateRecord(array $record): bool`

Validates a record before insertion.

**Validation Rules:**
- `adherent_number`: Required, exactly 7 characters
- `code_pathologie`: Required
- `num_sinistre`: Required
- Date fields: Must be valid Y-m-d format or NULL

**Example:**
```php
if (!$arretService->validateRecord($record)) {
    throw new Exception('Invalid record');
}
```

### `generateDetailedArretRecords(array $result, array $inputData, bool $includeOriginals): array`

Generates records including original arrêts from merged prolongations.

**Parameters:**
- `$result`: Calculation result
- `$inputData`: Input data
- `$includeOriginals`: If true, creates separate records for merged arrêts (marked as `actif = 0`)

**Example:**
```php
// Generate main records + original merged arrêts
$allRecords = $arretService->generateDetailedArretRecords(
    $result,
    $inputData,
    true  // Include originals
);
```

## Data Transformations

### DT_excused Logic

The `dt-line` field from calculator is **inverted** for database:

| Calculator `dt-line` | Database `DT_excused` | Meaning |
|---------------------|----------------------|---------|
| 1 | 0 | NOT excused (penalty applies) |
| 0 | 1 | Excused (no penalty) |

**Code:**
```php
$dtExcused = ($arret['dt-line'] == 1) ? 0 : 1;
```

### Date Normalization

All dates are normalized to handle invalid values:
- `'0000-00-00'` → `NULL`
- Empty string `''` → `NULL`
- Valid dates remain unchanged

### First Day Calculation

```php
$firstDay = ($index === 0) ? 1 : 0;
```

First arrêt in list gets `first_day = 1`, others get `0`.

### Date Prolongation

If arrêt has merged prolongations:
```php
if ($arret['has_prolongations'] && !empty($arret['merged_arrets'])) {
    $lastMerged = end($arret['merged_arrets']);
    $dateProlongation = $lastMerged['to'];
}
```

## Required Input Fields

```php
$inputData = [
    'adherent_number' => '1234567',  // REQUIRED: 7 chars
    'num_sinistre' => 12345,         // REQUIRED: integer
    'attestation_date' => '2024-06-12',  // Optional but recommended
    'arrets' => [...],               // Arrêts array
    // ... other fields for calculation
];
```

### Validation Errors

```php
// Missing adherent_number
throw new InvalidArgumentException("Missing required field: adherent_number");

// Wrong length
throw new InvalidArgumentException("adherent_number must be exactly 7 characters");

// Missing num_sinistre
throw new InvalidArgumentException("Missing required field: num_sinistre");

// Non-numeric num_sinistre
throw new InvalidArgumentException("num_sinistre must be an integer");

// Missing code_pathologie in arrêt
throw new InvalidArgumentException("code_pathologie is required for ij_arret record");
```

## Output Examples

### Single Record

```php
[
    'adherent_number' => '1234567',
    'code_pathologie' => 'A',
    'num_sinistre' => 12345,
    'date_start' => '2024-01-02',
    'date_end' => '2024-01-25',
    'date_prolongation' => '2024-01-25',  // From merged_arrets
    'first_day' => 1,
    'date_declaration' => '2024-01-02',
    'DT_excused' => 1,
    'valid_med_controleur' => 1,
    'cco_a_jour' => 1,
    'date_dern_attestation' => '2024-06-12',
    'date_deb_droit' => '2024-04-01',
    'date_deb_dr_force' => null,
    'taux' => 1,
    'NOARRET' => null,
    'version' => 1,
    'actif' => 1
]
```

### SQL INSERT Statement

```sql
INSERT INTO `ij_arret` (
    `adherent_number`, `code_pathologie`, `num_sinistre`,
    `date_start`, `date_end`, `date_prolongation`,
    `first_day`, `date_declaration`, `DT_excused`,
    `valid_med_controleur`, `cco_a_jour`,
    `date_dern_attestation`, `date_deb_droit`,
    `date_deb_dr_force`, `taux`, `NOARRET`,
    `version`, `actif`
) VALUES (
    '1234567', 'A', 12345,
    '2024-01-02', '2024-01-25', '2024-01-25',
    1, '2024-01-02', 1,
    1, 1,
    '2024-06-12', '2024-04-01',
    NULL, 1, NULL,
    1, 1
);
```

## Integration with Merged Arrêts

The service automatically handles merged arrêts:

```php
// If arrêt has prolongations:
[
    'arret-from-line' => '2024-01-02',
    'arret-to-line' => '2024-01-25',      // Extended end date
    'has_prolongations' => true,
    'prolongation_count' => 2,
    'merged_arrets' => [
        ['from' => '2024-01-11', 'to' => '2024-01-17'],
        ['from' => '2024-01-18', 'to' => '2024-01-25']
    ]
]

// Transforms to:
[
    'date_start' => '2024-01-02',
    'date_end' => '2024-01-25',
    'date_prolongation' => '2024-01-25',  // Last merged arrêt's end
]
```

## Testing

### Test File

```bash
php test_arret_service.php
```

### Test Coverage

- ✅ Record generation from calculation results
- ✅ Field mapping and transformation
- ✅ DT_excused inversion logic
- ✅ Date normalization ('0000-00-00' → NULL)
- ✅ First day calculation
- ✅ Date prolongation from merged arrêts
- ✅ SQL generation (single and batch)
- ✅ Record validation
- ✅ Required field validation
- ✅ Foreign key validation

## Error Handling

```php
try {
    $records = $arretService->generateArretRecords($result, $inputData);

    foreach ($records as $record) {
        if (!$arretService->validateRecord($record)) {
            throw new Exception('Record validation failed');
        }
    }

    $sql = $arretService->generateBatchInsertSQL($records);
    $pdo->exec($sql);

} catch (InvalidArgumentException $e) {
    // Handle missing or invalid input
    error_log('Input error: ' . $e->getMessage());

} catch (PDOException $e) {
    // Handle database errors
    error_log('Database error: ' . $e->getMessage());
}
```

## Foreign Key Considerations

Before inserting `ij_arret` records, ensure:

1. **`adherent_number`** exists in `adherent_infos` table
2. **`code_pathologie`** exists in `pathologie` table
3. **`num_sinistre`** exists in `ij_sinistre` table

```php
// Example: Validate foreign keys before insert
$stmt = $pdo->prepare("SELECT id FROM ij_sinistre WHERE id = ?");
$stmt->execute([$inputData['num_sinistre']]);
if (!$stmt->fetch()) {
    throw new Exception('num_sinistre does not exist');
}

// Then proceed with arrêt insertion
$sql = $arretService->generateBatchInsertSQL($records);
$pdo->exec($sql);
```

## Complete Workflow Example

```php
// 1. Load data and calculate
$calculator = new IJCalculator('taux.csv');
$arretService = new ArretService();

$inputData = [
    'adherent_number' => '1234567',
    'num_sinistre' => 12345,
    'arrets' => $mockData,
    'attestation_date' => '2024-06-12',
    // ... other params
];

$result = $calculator->calculateAmount($inputData);

// 2. Generate ij_arret records
$arretRecords = $arretService->generateArretRecords($result, $inputData);

// 3. Validate all records
$allValid = true;
foreach ($arretRecords as $record) {
    if (!$arretService->validateRecord($record)) {
        $allValid = false;
        break;
    }
}

if (!$allValid) {
    throw new Exception('Validation failed');
}

// 4. Generate and execute SQL
$sql = $arretService->generateBatchInsertSQL($arretRecords);

$pdo = new PDO($dsn, $user, $password);
$pdo->beginTransaction();

try {
    $pdo->exec($sql);
    $pdo->commit();
    echo "✅ Inserted " . count($arretRecords) . " arrêt records\n";
} catch (PDOException $e) {
    $pdo->rollBack();
    throw $e;
}
```

## Benefits

1. ✅ **Perfect schema match** - All fields mapped correctly
2. ✅ **Automatic validation** - Required fields, date formats, FK references
3. ✅ **SQL generation** - Single or batch INSERT statements
4. ✅ **Merged arrêt support** - Handles prolongations automatically
5. ✅ **Date normalization** - Converts invalid dates to NULL
6. ✅ **DT logic** - Proper inversion of dt-line to DT_excused
7. ✅ **Foreign key ready** - Includes all FK fields
8. ✅ **CakePHP compatible** - Works with ORM or raw SQL
9. ✅ **Transaction safe** - Batch operations for atomicity
10. ✅ **Error handling** - Clear validation messages

## File Location

```
/Services/ArretService.php
```

## Dependencies

- PHP 7.4+
- DateTime class
- IJCalculator (for input data structure)
