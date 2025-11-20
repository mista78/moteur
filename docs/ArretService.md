# ArretService - Documentation Complète

## Vue d'ensemble

`ArretService` est le service de gestion des collections d'arrêts de travail. Il fournit des utilitaires pour charger, valider, formater et manipuler les données d'arrêts depuis différentes sources (JSON, base de données, tableaux).

**Emplacement** : `Services/ArretService.php`

**Namespace** : `App\IJCalculator\Services`

**Lignes de code** : 401 lignes

## Fonctionnalités

- ✅ Chargement depuis JSON, CakePHP, tableaux
- ✅ Normalisation des noms de champs
- ✅ Normalisation des formats de dates
- ✅ Validation des données
- ✅ Tri et filtrage
- ✅ Groupement et agrégation
- ✅ Conversion JSON

## Méthodes de Chargement

### 1. loadFromJson()

Charge les arrêts depuis un fichier JSON.

```php
public function loadFromJson(string $filePath): array
```

#### Exemple

```php
use App\IJCalculator\Services\ArretService;

$arretService = new ArretService();

// Charger depuis JSON
$arrets = $arretService->loadFromJson('arrets.json');

echo "Nombre d'arrêts : " . count($arrets) . "\n";
```

### 2. loadFromEntities()

Charge depuis des entités CakePHP ou résultats de base de données.

```php
public function loadFromEntities($entities): array
```

#### Exemple

```php
// Depuis CakePHP
$entities = $this->Arrets->find('all')
    ->where(['num_sinistre' => 8038])
    ->toArray();

$arrets = $arretService->loadFromEntities($entities);

// Depuis PDO
$stmt = $pdo->query("SELECT * FROM arrets WHERE num_sinistre = 8038");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$arrets = $arretService->loadFromEntities($results);
```

## Méthodes de Normalisation

### normalizeArrets()

Normalise un tableau d'arrêts.

```php
public function normalizeArrets(array $arrets): array
```

#### Normalisation des Champs

Le service accepte différentes variantes de noms de champs :

| Variantes acceptées | Champ normalisé |
|---------------------|-----------------|
| `arret_from`, `arret-from`, `date_debut` | `arret-from-line` |
| `arret_to`, `arret-to`, `date_fin` | `arret-to-line` |
| `dt`, `declaration_tardive` | `dt-line` |
| `rechute` | `rechute-line` |
| `gpm`, `gpm_member` | `gpm-member-line` |

#### Exemple

```php
$arretService = new ArretService();

// Données avec noms variés
$rawArrets = [
    [
        'arret_from' => '2023-09-01',  // Variante
        'arret_to' => '2023-11-10',    // Variante
        'dt' => 1,                     // Variante
        'rechute' => 0
    ]
];

$normalized = $arretService->normalizeArrets($rawArrets);

// Résultat avec noms normalisés
print_r($normalized);
// [
//     [
//         'arret-from-line' => '2023-09-01',
//         'arret-to-line' => '2023-11-10',
//         'dt-line' => 1,
//         'rechute-line' => 0
//     ]
// ]
```

## Méthodes de Validation

### validateArrets()

Valide un tableau d'arrêts.

```php
public function validateArrets(array $arrets): array
```

#### Valeur de Retour

```php
[
    'valid' => bool,
    'errors' => array
]
```

#### Exemple

```php
$arretService = new ArretService();

$arrets = [
    ['arret-from-line' => '2023-09-01', 'arret-to-line' => '2023-11-10'],
    ['arret-from-line' => 'date-invalide', 'arret-to-line' => '2023-11-10']
];

$validation = $arretService->validateArrets($arrets);

if (!$validation['valid']) {
    echo "Erreurs trouvées :\n";
    foreach ($validation['errors'] as $error) {
        echo "  - $error\n";
    }
}
```

## Méthodes Utilitaires

### 1. sortByDate()

Trie les arrêts par date de début.

```php
public function sortByDate(array $arrets, string $direction = 'asc'): array
```

#### Exemple

```php
$arrets = [
    ['arret-from-line' => '2023-11-01', 'arret-to-line' => '2023-11-30'],
    ['arret-from-line' => '2023-09-01', 'arret-to-line' => '2023-09-30']
];

// Tri croissant (défaut)
$sorted = $arretService->sortByDate($arrets);
// Premier arrêt : 2023-09-01

// Tri décroissant
$sorted = $arretService->sortByDate($arrets, 'desc');
// Premier arrêt : 2023-11-01
```

### 2. filterByDateRange()

Filtre les arrêts par plage de dates.

```php
public function filterByDateRange(
    array $arrets,
    string $startDate,
    string $endDate
): array
```

#### Exemple

```php
$arrets = [
    ['arret-from-line' => '2022-09-01', 'arret-to-line' => '2022-11-30'],
    ['arret-from-line' => '2023-09-01', 'arret-to-line' => '2023-11-30'],
    ['arret-from-line' => '2024-09-01', 'arret-to-line' => '2024-11-30']
];

// Filtrer année 2023
$filtered = $arretService->filterByDateRange(
    $arrets,
    '2023-01-01',
    '2023-12-31'
);

echo count($filtered); // 1 (seulement l'arrêt de 2023)
```

### 3. groupBySinistre()

Groupe les arrêts par numéro de sinistre.

```php
public function groupBySinistre(array $arrets): array
```

#### Exemple

```php
$arrets = [
    ['num_sinistre' => 8038, 'arret-from-line' => '2023-09-01'],
    ['num_sinistre' => 8038, 'arret-from-line' => '2023-11-01'],
    ['num_sinistre' => 8042, 'arret-from-line' => '2023-10-01']
];

$grouped = $arretService->groupBySinistre($arrets);

// Résultat :
// [
//     8038 => [2 arrêts],
//     8042 => [1 arrêt]
// ]

foreach ($grouped as $sinistre => $sinistreArrets) {
    echo "Sinistre $sinistre : " . count($sinistreArrets) . " arrêts\n";
}
```

### 4. countTotalDays()

Compte le nombre total de jours d'arrêts.

```php
public function countTotalDays(array $arrets): int
```

#### Exemple

```php
$arrets = [
    ['arret-from-line' => '2023-09-01', 'arret-to-line' => '2023-09-30'],  // 30 jours
    ['arret-from-line' => '2023-11-01', 'arret-to-line' => '2023-11-30']   // 30 jours
];

$totalDays = $arretService->countTotalDays($arrets);
echo "Total : $totalDays jours"; // 60 jours
```

### 5. toJson()

Convertit les arrêts en JSON.

```php
public function toJson(array $arrets, bool $pretty = false): string
```

#### Exemple

```php
$arrets = $arretService->loadFromJson('arrets.json');

// JSON compact
$json = $arretService->toJson($arrets);

// JSON formaté
$jsonPretty = $arretService->toJson($arrets, pretty: true);

file_put_contents('output.json', $jsonPretty);
```

## Exemples d'Utilisation Complets

### Exemple 1 : Pipeline Complet

```php
use App\IJCalculator\Services\ArretService;

$arretService = new ArretService();

// 1. Charger depuis JSON
$arrets = $arretService->loadFromJson('arrets.json');

// 2. Normaliser
$arrets = $arretService->normalizeArrets($arrets);

// 3. Valider
$validation = $arretService->validateArrets($arrets);
if (!$validation['valid']) {
    die("Erreurs : " . implode(", ", $validation['errors']));
}

// 4. Filtrer par année 2023
$arrets = $arretService->filterByDateRange($arrets, '2023-01-01', '2023-12-31');

// 5. Trier par date
$arrets = $arretService->sortByDate($arrets);

// 6. Compter les jours
$totalDays = $arretService->countTotalDays($arrets);

echo "Arrêts 2023 : " . count($arrets) . " arrêts, $totalDays jours\n";
```

### Exemple 2 : Statistiques par Sinistre

```php
$arrets = $arretService->loadFromJson('arrets.json');
$grouped = $arretService->groupBySinistre($arrets);

foreach ($grouped as $sinistre => $sinistreArrets) {
    $days = $arretService->countTotalDays($sinistreArrets);
    $sorted = $arretService->sortByDate($sinistreArrets);
    $first = $sorted[0]['arret-from-line'];
    $last = end($sorted)['arret-to-line'];

    echo "Sinistre $sinistre:\n";
    echo "  Nombre d'arrêts : " . count($sinistreArrets) . "\n";
    echo "  Total jours : $days\n";
    echo "  Premier arrêt : $first\n";
    echo "  Dernier arrêt : $last\n\n";
}
```

### Exemple 3 : Export Filtré

```php
$arretService = new ArretService();

// Charger tous les arrêts
$allArrets = $arretService->loadFromJson('arrets.json');

// Filtrer par année
$arrets2023 = $arretService->filterByDateRange(
    $allArrets,
    '2023-01-01',
    '2023-12-31'
);

// Exporter en JSON
$json = $arretService->toJson($arrets2023, pretty: true);
file_put_contents('arrets_2023.json', $json);

echo "Exporté : " . count($arrets2023) . " arrêts de 2023\n";
```

## Structure de Données

### Format d'Entrée (Flexible)

Accepte différentes variantes :

```json
{
    "arret_from": "2023-09-01",
    "arret_to": "2023-11-10",
    "dt": 1,
    "rechute": 0
}
```

### Format de Sortie (Normalisé)

Toujours normalisé :

```json
{
    "arret-from-line": "2023-09-01",
    "arret-to-line": "2023-11-10",
    "dt-line": 1,
    "rechute-line": 0,
    "gpm-member-line": 0
}
```

## Points Importants

1. **Normalisation automatique** : Accepte différents formats
2. **Validation robuste** : Vérifie dates et champs requis
3. **Utilitaires pratiques** : Tri, filtrage, groupement
4. **Multi-sources** : JSON, base de données, tableaux
5. **Immutable** : Les méthodes retournent de nouveaux tableaux

## Gestion des Erreurs

```php
try {
    $arrets = $arretService->loadFromJson('inexistant.json');
} catch (RuntimeException $e) {
    echo "Erreur : " . $e->getMessage();
}
```

## Voir Aussi

- [DateService](./DateService.md) - Calculs sur les arrêts
- [IJCalculator](./IJCalculator.md) - Utilisation des arrêts
- [DateNormalizer](./DateNormalizer.md) - Normalisation des dates
