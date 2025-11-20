# DateNormalizer - Documentation Complète

## Vue d'ensemble

`DateNormalizer` est un service utilitaire pour normaliser les formats de dates à travers différentes sources (objets DateTime, chaînes de caractères, formats de base de données). Il assure une gestion cohérente des dates dans toute l'application.

**Emplacement** : `Services/DateNormalizer.php`

**Namespace** : `App\IJCalculator\Services`

**Lignes de code** : 266 lignes

## Fonctionnalités

- ✅ Normalisation DateTime → String
- ✅ Normalisation formats variés → Format standard
- ✅ Gestion des dates nulles/invalides
- ✅ Validation de format
- ✅ Conversion entre fuseaux horaires
- ✅ Support formats internationaux

## Formats Supportés

### Formats d'Entrée Acceptés

```php
// Objets DateTime
new DateTime('2023-09-01')
new DateTimeImmutable('2023-09-01')

// Chaînes ISO 8601
'2023-09-01'
'2023-09-01T00:00:00'
'2023-09-01T10:30:00+02:00'

// Formats européens
'01/09/2023'    // DD/MM/YYYY
'01-09-2023'    // DD-MM-YYYY

// Formats américains
'09/01/2023'    // MM/DD/YYYY
'09-01-2023'    // MM-DD-YYYY

// Formats base de données
'2023-09-01 00:00:00'  // MySQL DATETIME
'2023-09-01 10:30:00.000000'  // MySQL DATETIME avec microsecondes
```

### Format de Sortie Standard

Toujours normalisé en **'Y-m-d'** (ISO 8601 date) :
```
'2023-09-01'
```

## Méthodes Principales

### 1. normalize()

Normalise une date vers le format standard.

```php
public function normalize($date): ?string
```

#### Paramètres

- **`$date`** (mixed) : Date à normaliser (DateTime, string, ou null)

#### Valeur de Retour

- **`string`** : Date normalisée au format 'Y-m-d'
- **`null`** : Si la date est null ou invalide

#### Exemples

```php
use App\IJCalculator\Services\DateNormalizer;

$normalizer = new DateNormalizer();

// Depuis DateTime
$date = new DateTime('2023-09-01');
echo $normalizer->normalize($date); // "2023-09-01"

// Depuis chaîne ISO
echo $normalizer->normalize('2023-09-01'); // "2023-09-01"

// Depuis format européen
echo $normalizer->normalize('01/09/2023'); // "2023-09-01"

// Depuis format base de données
echo $normalizer->normalize('2023-09-01 10:30:00'); // "2023-09-01"

// Date null
echo $normalizer->normalize(null); // null

// Date invalide
echo $normalizer->normalize('date-invalide'); // null
```

### 2. normalizeArray()

Normalise un tableau de dates.

```php
public function normalizeArray(array $dates): array
```

#### Exemple

```php
$normalizer = new DateNormalizer();

$dates = [
    new DateTime('2023-09-01'),
    '2023-09-15',
    '15/09/2023',
    '2023-09-30 23:59:59'
];

$normalized = $normalizer->normalizeArray($dates);

print_r($normalized);
// [
//     '2023-09-01',
//     '2023-09-15',
//     '2023-09-15',
//     '2023-09-30'
// ]
```

### 3. normalizeRecord()

Normalise toutes les dates dans un enregistrement (tableau associatif).

```php
public function normalizeRecord(
    array $record,
    array $dateFields = ['created', 'modified', 'date']
): array
```

#### Exemple

```php
$normalizer = new DateNormalizer();

$record = [
    'id' => 1,
    'adherent_number' => '1234567',
    'created' => new DateTime('2023-09-01 10:30:00'),
    'arret_from' => '01/09/2023',
    'arret_to' => '2023-11-30',
    'amount' => 1500.00
];

$normalized = $normalizer->normalizeRecord(
    $record,
    dateFields: ['created', 'arret_from', 'arret_to']
);

print_r($normalized);
// [
//     'id' => 1,
//     'adherent_number' => '1234567',
//     'created' => '2023-09-01',
//     'arret_from' => '2023-09-01',
//     'arret_to' => '2023-11-30',
//     'amount' => 1500.00
// ]
```

### 4. isValid()

Vérifie si une date est valide.

```php
public function isValid($date): bool
```

#### Exemple

```php
$normalizer = new DateNormalizer();

echo $normalizer->isValid('2023-09-01') ? 'Valide' : 'Invalide'; // Valide
echo $normalizer->isValid('2023-13-01') ? 'Valide' : 'Invalide'; // Invalide (mois 13)
echo $normalizer->isValid('date-invalide') ? 'Valide' : 'Invalide'; // Invalide
echo $normalizer->isValid(new DateTime()) ? 'Valide' : 'Invalide'; // Valide
```

### 5. toDateTime()

Convertit une date normalisée en objet DateTime.

```php
public function toDateTime(string $date): ?\DateTime
```

#### Exemple

```php
$normalizer = new DateNormalizer();

$dateString = '2023-09-01';
$dateTime = $normalizer->toDateTime($dateString);

echo $dateTime->format('d/m/Y'); // "01/09/2023"
echo $dateTime->format('l, F j, Y'); // "Friday, September 1, 2023"
```

## Exemples d'Utilisation Avancés

### Exemple 1 : Normalisation depuis Base de Données

```php
use App\IJCalculator\Services\DateNormalizer;

$normalizer = new DateNormalizer();

// Résultats de requête SQL
$dbResults = [
    [
        'id' => 1,
        'arret_from' => '2023-09-01 00:00:00',
        'arret_to' => '2023-11-30 23:59:59',
        'created_at' => '2023-08-15 10:30:45.123456'
    ]
];

foreach ($dbResults as $row) {
    $normalized = $normalizer->normalizeRecord(
        $row,
        dateFields: ['arret_from', 'arret_to', 'created_at']
    );

    echo "De : " . $normalized['arret_from'] . "\n";
    echo "À  : " . $normalized['arret_to'] . "\n";
    echo "Créé : " . $normalized['created_at'] . "\n";
}
```

### Exemple 2 : Normalisation depuis Formulaire Web

```php
$normalizer = new DateNormalizer();

// Données POST d'un formulaire (format européen)
$formData = [
    'birth_date' => '15/01/1960',        // DD/MM/YYYY
    'affiliation_date' => '01/01/2019',
    'arret_from' => '04/09/2023',
    'arret_to' => '10/11/2023'
];

$normalized = $normalizer->normalizeRecord(
    $formData,
    dateFields: ['birth_date', 'affiliation_date', 'arret_from', 'arret_to']
);

// Utiliser avec IJCalculator
$data = [
    'birth_date' => $normalized['birth_date'],  // "1960-01-15"
    'affiliation_date' => $normalized['affiliation_date'],  // "2019-01-01"
    'arrets' => [
        [
            'arret-from-line' => $normalized['arret_from'],  // "2023-09-04"
            'arret-to-line' => $normalized['arret_to']  // "2023-11-10"
        ]
    ]
];
```

### Exemple 3 : Validation de Données

```php
$normalizer = new DateNormalizer();

$userInputs = [
    '2023-09-01',
    '2023-13-01',  // Invalide
    'invalide',
    new DateTime('2023-09-15'),
    null
];

$validDates = [];
$invalidDates = [];

foreach ($userInputs as $input) {
    if ($normalizer->isValid($input)) {
        $validDates[] = $normalizer->normalize($input);
    } else {
        $invalidDates[] = $input;
    }
}

echo "Dates valides : " . count($validDates) . "\n";
echo "Dates invalides : " . count($invalidDates) . "\n";
```

### Exemple 4 : Intégration avec ArretService

```php
use App\IJCalculator\Services\ArretService;
use App\IJCalculator\Services\DateNormalizer;

$arretService = new ArretService();
$normalizer = new DateNormalizer();

// Charger arrêts avec dates dans différents formats
$arrets = [
    [
        'arret_from' => new DateTime('2023-09-01'),
        'arret_to' => '30/09/2023',
        'created' => '2023-08-15 10:30:00'
    ]
];

// Normaliser toutes les dates
foreach ($arrets as &$arret) {
    $arret = $normalizer->normalizeRecord(
        $arret,
        dateFields: ['arret_from', 'arret_to', 'created']
    );
}

// Normaliser les noms de champs
$arrets = $arretService->normalizeArrets($arrets);

// Utiliser avec le calculateur
print_r($arrets);
```

### Exemple 5 : Conversion Batch

```php
$normalizer = new DateNormalizer();

// Fichier CSV avec dates européennes
$csv = [
    ['2023-09-01', '01/09/2023', '01-09-2023'],
    ['2023-09-15', '15/09/2023', '15-09-2023'],
    ['2023-09-30', '30/09/2023', '30-09-2023']
];

$normalized = [];
foreach ($csv as $row) {
    $normalized[] = $normalizer->normalizeArray($row);
}

// Toutes les dates en format ISO
print_r($normalized);
// [
//     ['2023-09-01', '2023-09-01', '2023-09-01'],
//     ['2023-09-15', '2023-09-15', '2023-09-15'],
//     ['2023-09-30', '2023-09-30', '2023-09-30']
// ]
```

## Détection Automatique de Format

Le service détecte automatiquement le format :

```php
$normalizer = new DateNormalizer();

// Tous donnent '2023-09-15'
echo $normalizer->normalize('2023-09-15');         // ISO direct
echo $normalizer->normalize('15/09/2023');         // Européen
echo $normalizer->normalize('15-09-2023');         // Européen avec tirets
echo $normalizer->normalize('2023-09-15T10:30:00'); // ISO avec heure
echo $normalizer->normalize(new DateTime('2023-09-15')); // DateTime
```

## Points Importants

1. **Format standard** : Toujours 'Y-m-d' en sortie
2. **Gestion des null** : Retourne null pour dates invalides
3. **Immutable** : Ne modifie pas les données sources
4. **Type-safe** : Accepte DateTime, string, null
5. **Robuste** : Gère les formats courants automatiquement

## Cas Limites

### Dates Ambiguës

```php
// Format américain vs européen
'01/02/2023'
// Peut être : 1er février (européen) ou 2 janvier (américain)

// Le service utilise le format EUROPÉEN par défaut
$normalizer->normalize('01/02/2023'); // "2023-02-01" (1er février)
```

### Dates avec Heures

```php
// Les heures sont ignorées
$normalizer->normalize('2023-09-01 23:59:59'); // "2023-09-01"
$normalizer->normalize('2023-09-01T23:59:59+02:00'); // "2023-09-01"
```

## Gestion des Erreurs

Le service ne lève pas d'exceptions, il retourne `null` :

```php
$normalizer = new DateNormalizer();

$result = $normalizer->normalize('date-invalide');
if ($result === null) {
    echo "Date invalide\n";
}
```

## Voir Aussi

- [ArretService](./ArretService.md) - Utilise la normalisation
- [DateService](./DateService.md) - Calculs sur dates
- [IJCalculator](./IJCalculator.md) - Utilisation des dates normalisées
