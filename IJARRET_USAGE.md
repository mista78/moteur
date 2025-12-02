# IjArret Model Usage Guide

The `IjArret` model represents work stoppages (arrêts de travail) for medical professionals with comprehensive tracking of dates, status, and medical validations.

## Table Structure

```
ij_arret:
├── id (int unsigned)
├── adherent_number (varchar)
├── code_pathologie (varchar)
├── num_sinistre (int)
├── date_start (date) - Start date of work stoppage
├── date_end (date) - End date of work stoppage
├── date_reprise_activite (date) - Activity resumption date
├── date_end_init (date) - Initial end date (before prolongation)
├── date_prolongation (date) - Prolongation date
├── first_day (tinyint) - First day payment flag
├── is_rechute (tinyint) - Rechute/relapse flag
├── is_prolongation (tinyint) - Prolongation flag
├── date_declaration (date) - Declaration date
├── DT_excused (tinyint) - DT excused flag
├── valid_med_controleur (tinyint) - Medical controller validation
├── cco_a_jour (tinyint) - CCO up to date flag
├── date_dern_attestation (date) - Last attestation date
├── date_deb_droit (date) - Rights start date (date-effet)
├── date_deb_dr_force (date) - Forced rights start date
├── taux (float) - Rate/percentage
├── NOARRET (int) - Mainframe arret number
├── source (varchar) - Source system (default: 'OPEN')
├── version (tinyint) - Version number (default: 1)
├── actif (tinyint) - Active status (default: 1)
├── created_at (timestamp)
└── updated_at (timestamp)
```

## Basic CRUD Operations

### Create a New Arret

```php
use App\Models\IjArret;

// Simple arret
$arret = IjArret::create([
    'adherent_number' => '1234567',
    'code_pathologie' => 'MP',
    'num_sinistre' => 123,
    'date_start' => '2024-01-15',
    'date_end' => '2024-02-15',
    'date_declaration' => '2024-01-16',
    // Optional fields with defaults:
    // 'first_day' => 0,
    // 'is_rechute' => 0,
    // 'is_prolongation' => 0,
    // 'source' => 'OPEN',
    // 'version' => 1,
    // 'actif' => 1,
]);

// Arret with all details
$arret = IjArret::create([
    'adherent_number' => '1234567',
    'code_pathologie' => 'MP',
    'num_sinistre' => 123,
    'date_start' => '2024-01-15',
    'date_end' => '2024-02-15',
    'date_end_init' => '2024-01-31',
    'date_prolongation' => '2024-02-01',
    'date_reprise_activite' => '2024-02-16',
    'first_day' => 1,
    'is_rechute' => 0,
    'is_prolongation' => 1,
    'date_declaration' => '2024-01-16',
    'DT_excused' => 1,
    'valid_med_controleur' => 1,
    'cco_a_jour' => 1,
    'date_dern_attestation' => '2024-02-14',
    'date_deb_droit' => '2024-01-20',
    'taux' => 100.0,
    'NOARRET' => 98765,
    'source' => 'OPEN',
]);
```

### Read/Query Arrets

```php
// Find by ID
$arret = IjArret::find(1);

// Find with relationships
$arret = IjArret::with(['sinistre', 'adherent', 'pathologie'])->find(1);

// Query by adherent
$arrets = IjArret::where('adherent_number', '1234567')->get();

// Query by sinistre
$arrets = IjArret::where('num_sinistre', 123)
    ->orderBy('date_start')
    ->get();

// Query by date range
$arrets = IjArret::whereBetween('date_start', ['2024-01-01', '2024-12-31'])
    ->get();
```

### Update Arret

```php
$arret = IjArret::find(1);

// Update single field
$arret->date_end = '2024-03-15';
$arret->save();

// Update multiple fields
$arret->update([
    'date_end' => '2024-03-15',
    'is_prolongation' => 1,
    'date_prolongation' => '2024-02-15',
]);

// Mark as resumed
$arret->update([
    'date_reprise_activite' => '2024-02-16',
]);
```

### Delete/Deactivate Arret

```php
$arret = IjArret::find(1);

// Soft delete (deactivate)
$arret->update(['actif' => 0]);

// Hard delete
$arret->delete();
```

## Using Scopes

```php
// Get only active arrets
$activeArrets = IjArret::active()->get();

// Get only rechutes
$rechutes = IjArret::rechute()->get();

// Get only prolongations
$prolongations = IjArret::prolongation()->get();

// Combine scopes
$activeRechutes = IjArret::active()
    ->rechute()
    ->where('adherent_number', '1234567')
    ->get();
```

## Helper Methods

### Check Status

```php
$arret = IjArret::find(1);

// Check if rechute
if ($arret->isRechute()) {
    echo "This is a rechute";
}

// Check if prolongation
if ($arret->isProlongation()) {
    echo "This arret has been prolonged";
}

// Check if active
if ($arret->isActive()) {
    echo "This arret is active";
}

// Check if has prolongation date
if ($arret->hasProlongation()) {
    echo "Prolongation date: " . $arret->date_prolongation->format('Y-m-d');
}

// Check if activity resumed
if ($arret->hasResumedActivity()) {
    echo "Resumed on: " . $arret->date_reprise_activite->format('Y-m-d');
}
```

### Calculate Duration

```php
$arret = IjArret::find(1);

// Get duration in days
$days = $arret->getDurationInDays();
echo "Duration: $days days";

// Get date difference manually
$duration = $arret->date_start->diffInDays($arret->date_end) + 1;
```

## Relationships

### With Sinistre

```php
$arret = IjArret::with('sinistre')->find(1);

echo $arret->sinistre->code_pathologie;
echo $arret->sinistre->date_debut;
```

### With Adherent

```php
$arret = IjArret::with('adherent')->find(1);

echo $arret->adherent->nom;
echo $arret->adherent->prenom;
echo $arret->adherent->email;
```

### With Pathologie

```php
$arret = IjArret::with('pathologie')->find(1);

echo $arret->pathologie->libelle;
echo $arret->pathologie->description;
```

### With All Relationships

```php
$arret = IjArret::with(['sinistre', 'adherent', 'pathologie'])->find(1);

// Access all related data
echo "Adherent: {$arret->adherent->nom}\n";
echo "Pathologie: {$arret->pathologie->libelle}\n";
echo "Sinistre: {$arret->sinistre->id}\n";
```

## Complex Queries

### Find Active Arrets with Pending Validation

```php
$pendingArrets = IjArret::active()
    ->whereNull('valid_med_controleur')
    ->orWhere('valid_med_controleur', 0)
    ->with('adherent')
    ->get();
```

### Find Prolonged Arrets

```php
$prolongedArrets = IjArret::prolongation()
    ->whereNotNull('date_prolongation')
    ->with('adherent')
    ->get();

foreach ($prolongedArrets as $arret) {
    $initialDuration = $arret->date_start->diffInDays($arret->date_end_init) + 1;
    $totalDuration = $arret->getDurationInDays();
    $prolongationDays = $totalDuration - $initialDuration;

    echo "Arret #{$arret->id}: prolonged by {$prolongationDays} days\n";
}
```

### Find Rechutes Within Time Period

```php
$recentRechutes = IjArret::rechute()
    ->where('date_start', '>=', now()->subMonths(6))
    ->with(['adherent', 'pathologie'])
    ->get();
```

### Find Arrets Awaiting Medical Controller Validation

```php
$awaitingValidation = IjArret::active()
    ->where(function ($query) {
        $query->whereNull('valid_med_controleur')
              ->orWhere('valid_med_controleur', 0);
    })
    ->with('adherent')
    ->orderBy('date_declaration')
    ->get();
```

## Practical Service Example

```php
use App\Models\IjArret;
use Carbon\Carbon;

class ArretService
{
    /**
     * Create a new arret
     */
    public function createArret(array $data): IjArret
    {
        return IjArret::create([
            'adherent_number' => $data['adherent_number'],
            'code_pathologie' => $data['code_pathologie'],
            'num_sinistre' => $data['num_sinistre'],
            'date_start' => $data['date_start'],
            'date_end' => $data['date_end'],
            'date_declaration' => $data['date_declaration'] ?? now(),
            'first_day' => $data['first_day'] ?? 0,
            'is_rechute' => $data['is_rechute'] ?? 0,
            'source' => $data['source'] ?? 'OPEN',
        ]);
    }

    /**
     * Prolong an existing arret
     */
    public function prolongArret(int $arretId, string $newEndDate): IjArret
    {
        $arret = IjArret::findOrFail($arretId);

        // Save original end date if not already saved
        if (!$arret->date_end_init) {
            $arret->date_end_init = $arret->date_end;
        }

        $arret->update([
            'date_end' => $newEndDate,
            'date_prolongation' => now(),
            'is_prolongation' => 1,
        ]);

        return $arret->fresh();
    }

    /**
     * Mark arret as resumed
     */
    public function markAsResumed(int $arretId, ?string $resumptionDate = null): IjArret
    {
        $arret = IjArret::findOrFail($arretId);

        $arret->update([
            'date_reprise_activite' => $resumptionDate ?? now(),
        ]);

        return $arret->fresh();
    }

    /**
     * Get arret statistics for an adherent
     */
    public function getAdherentStatistics(string $adherentNumber): array
    {
        $arrets = IjArret::active()
            ->where('adherent_number', $adherentNumber)
            ->get();

        return [
            'total_arrets' => $arrets->count(),
            'total_rechutes' => $arrets->where('is_rechute', 1)->count(),
            'total_prolongations' => $arrets->where('is_prolongation', 1)->count(),
            'total_days' => $arrets->sum(function ($arret) {
                return $arret->getDurationInDays();
            }),
            'active_arrets' => $arrets->filter(function ($arret) {
                return !$arret->hasResumedActivity();
            })->count(),
        ];
    }

    /**
     * Check if arret overlaps with existing arrets
     */
    public function checkOverlap(
        string $adherentNumber,
        string $startDate,
        string $endDate,
        ?int $excludeId = null
    ): bool {
        $query = IjArret::active()
            ->where('adherent_number', $adherentNumber)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date_start', [$startDate, $endDate])
                  ->orWhereBetween('date_end', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('date_start', '<=', $startDate)
                         ->where('date_end', '>=', $endDate);
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
```

## Working with Different Databases

```php
// Default database
$arret = IjArret::find(1);

// Secondary database
$arret = IjArret::on('mysql_secondary')->find(1);

// Query from legacy database
$legacyArrets = IjArret::on('mysql_secondary')
    ->where('source', 'LEGACY')
    ->get();
```

## Important Notes

- **Default Values**: `first_day`, `is_rechute`, `is_prolongation` default to 0
- **Source**: Defaults to 'OPEN' (can be used to track data origin)
- **Version**: Defaults to 1 (can be used for versioning)
- **Actif**: Defaults to 1 (use for soft deletes)
- **Date Fields**: All date fields are automatically cast to Carbon instances
- **Boolean Fields**: `first_day`, `is_rechute`, `is_prolongation`, `DT_excused`, etc. are cast to boolean

## Performance Tips

```php
// Eager load relationships to avoid N+1 queries
$arrets = IjArret::with(['sinistre', 'adherent', 'pathologie'])
    ->where('adherent_number', '1234567')
    ->get();

// Select only needed columns
$arrets = IjArret::select(['id', 'adherent_number', 'date_start', 'date_end'])
    ->where('adherent_number', '1234567')
    ->get();

// Use chunk for large datasets
IjArret::active()
    ->chunk(100, function ($arrets) {
        foreach ($arrets as $arret) {
            // Process each arret
        }
    });
```
