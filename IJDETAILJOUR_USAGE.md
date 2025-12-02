# IjDetailJour Model Usage Guide

The `IjDetailJour` model stores daily IJ calculation values in a **monthly format** (one row per month/period with 31 day columns: j1, j2, j3, ... j31).

## Table Structure

```
ij_detail_jour:
├── id (bigint)
├── adherent_number (varchar)
├── exercice (varchar) - Year, e.g., "2024"
├── periode (varchar) - Period/Month, e.g., "01", "02", etc.
├── num_sinistre (int)
├── j1 to j31 (int) - Daily values for each day of the month
└── created_at (datetime)
```

## Basic CRUD Operations

### Create a Monthly Record

```php
use App\Models\IjDetailJour;

// Create a detail record for January 2024
$detail = IjDetailJour::create([
    'adherent_number' => '1234567',
    'exercice' => '2024',
    'periode' => '01',  // January
    'num_sinistre' => 123,
    'j1' => 150,   // Day 1: 150€
    'j2' => 150,   // Day 2: 150€
    'j3' => 150,   // Day 3: 150€
    // ... days 4-30
    'j31' => 150,  // Day 31: 150€
]);
```

### Find and Read

```php
// Find by ID
$detail = IjDetailJour::find(1);

// Get value for specific day
$day5Value = $detail->j5;
$day15Value = $detail->getDayValue(15);

// Get all daily values as array
$allValues = $detail->getDailyValues();
// Returns: [1 => 150, 2 => 150, 3 => 150, ..., 31 => 150]

// Find by adherent and period
$details = IjDetailJour::where('adherent_number', '1234567')
    ->where('exercice', '2024')
    ->where('periode', '01')
    ->first();
```

### Update Daily Values

```php
$detail = IjDetailJour::find(1);

// Update single day
$detail->j10 = 200;
$detail->save();

// Or using helper method
$detail->setDayValue(10, 200);
$detail->save();

// Update multiple days
$detail->update([
    'j1' => 150,
    'j2' => 150,
    'j3' => 0,  // Day 3: no payment
    'j4' => 150,
]);

// Set all days at once
$values = array_fill(1, 31, 150); // All days = 150
$detail->setDailyValues($values);
$detail->save();
```

## Helper Methods

### Get Total Amount

```php
$detail = IjDetailJour::find(1);

// Sum all daily values (j1 + j2 + ... + j31)
$total = $detail->getTotalAmount();
// Example: 4650 (if all 31 days = 150€)
```

### Get Active Days Count

```php
// Count how many days have values (non-null)
$activeDays = $detail->getActiveDaysCount();
// Example: 31 (if all days have values)
```

### Get/Set Individual Day

```php
// Get day value
$value = $detail->getDayValue(15);  // Returns j15 value

// Set day value
$detail->setDayValue(15, 200);
$detail->save();
```

## Relationships

### With Sinistre (Claim)

```php
$detail = IjDetailJour::with('sinistre')->find(1);

echo $detail->sinistre->code_pathologie;
echo $detail->sinistre->date_debut;
```

### With Adherent (Member)

```php
$detail = IjDetailJour::with('adherent')->find(1);

echo $detail->adherent->nom;
echo $detail->adherent->prenom;
echo $detail->adherent->email;
```

### Query with Relationships

```php
// Get all detail records for a sinistre with adherent info
$details = IjDetailJour::with(['sinistre', 'adherent'])
    ->where('num_sinistre', 123)
    ->get();

foreach ($details as $detail) {
    echo "Adherent: {$detail->adherent->nom}\n";
    echo "Exercise: {$detail->exercice}\n";
    echo "Periode: {$detail->periode}\n";
    echo "Total: {$detail->getTotalAmount()}€\n";
}
```

## Complex Queries

### Get All Records for a Year

```php
$yearDetails = IjDetailJour::where('exercice', '2024')
    ->where('adherent_number', '1234567')
    ->orderBy('periode')
    ->get();

// Calculate annual total
$annualTotal = $yearDetails->sum(function ($detail) {
    return $detail->getTotalAmount();
});
```

### Find Records with Specific Day Values

```php
// Find all records where day 15 has a value > 100
$details = IjDetailJour::where('j15', '>', 100)->get();

// Find records with no payment on day 1
$details = IjDetailJour::whereNull('j1')->get();
```

### Aggregate by Period

```php
use Illuminate\Support\Facades\DB;

// Get monthly totals for a year
$monthlyTotals = IjDetailJour::where('exercice', '2024')
    ->where('adherent_number', '1234567')
    ->get()
    ->map(function ($detail) {
        return [
            'periode' => $detail->periode,
            'total' => $detail->getTotalAmount(),
            'active_days' => $detail->getActiveDaysCount(),
        ];
    });
```

## Practical Example: Monthly Breakdown

```php
use App\Models\IjDetailJour;

class DetailJourService
{
    /**
     * Create monthly breakdown for continuous payment
     */
    public function createMonthlyBreakdown(
        string $adherentNumber,
        int $numSinistre,
        string $exercice,
        string $periode,
        int $dailyAmount
    ): IjDetailJour {
        // Get number of days in month
        $year = (int) $exercice;
        $month = (int) $periode;
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        // Build data array
        $data = [
            'adherent_number' => $adherentNumber,
            'exercice' => $exercice,
            'periode' => $periode,
            'num_sinistre' => $numSinistre,
        ];

        // Set daily amounts
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $data["j$day"] = $dailyAmount;
        }

        // Set remaining days to null (for months with < 31 days)
        for ($day = $daysInMonth + 1; $day <= 31; $day++) {
            $data["j$day"] = null;
        }

        return IjDetailJour::create($data);
    }

    /**
     * Update specific days (e.g., after corrections)
     */
    public function updateDays(int $detailId, array $dayValues): bool
    {
        $detail = IjDetailJour::findOrFail($detailId);

        foreach ($dayValues as $day => $value) {
            $detail->setDayValue($day, $value);
        }

        return $detail->save();
    }

    /**
     * Get formatted monthly report
     */
    public function getMonthlyReport(int $detailId): array
    {
        $detail = IjDetailJour::with(['adherent', 'sinistre'])->findOrFail($detailId);

        return [
            'adherent' => $detail->adherent->nom . ' ' . $detail->adherent->prenom,
            'adherent_number' => $detail->adherent_number,
            'exercice' => $detail->exercice,
            'periode' => $detail->periode,
            'daily_values' => $detail->getDailyValues(),
            'total_amount' => $detail->getTotalAmount(),
            'active_days' => $detail->getActiveDaysCount(),
            'average_per_day' => $detail->getActiveDaysCount() > 0
                ? $detail->getTotalAmount() / $detail->getActiveDaysCount()
                : 0,
        ];
    }
}
```

## Usage with Different Databases

```php
// Default database
$detail = IjDetailJour::find(1);

// Secondary database
$detail = IjDetailJour::on('mysql_secondary')->find(1);

// Analytics database
IjDetailJour::on('mysql_analytics')->create([...]);
```

## Notes

- **One row = One month**: Each record represents one month/period for an adherent
- **31 columns for days**: j1 through j31 store daily values (use null for non-existent days in shorter months)
- **No `updated_at`**: The model uses `$timestamps = false` since the table only has `created_at`
- **Integer values**: All j1-j31 columns store integers (amounts in cents or smallest currency unit)
- **Exercice + Periode**: Together form the year-month identifier (e.g., "2024" + "01" = January 2024)

## Performance Tips

```php
// Use select to load only needed columns
$details = IjDetailJour::select(['id', 'adherent_number', 'j1', 'j2', 'j3'])
    ->where('exercice', '2024')
    ->get();

// Use chunk for large datasets
IjDetailJour::where('exercice', '2024')
    ->chunk(100, function ($details) {
        foreach ($details as $detail) {
            // Process each detail
        }
    });

// Eager load relationships to avoid N+1 queries
$details = IjDetailJour::with(['sinistre', 'adherent'])
    ->where('exercice', '2024')
    ->get();
```
