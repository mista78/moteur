# RateService - Documentation Complète

## Vue d'ensemble

`RateService` est le service responsable de toutes les recherches et calculs de taux journaliers dans le système IJ. Il gère le chargement des tables historiques de taux, la détermination des périodes (tier), et l'application des multiplicateurs selon le statut.

**Emplacement** : `Services/RateService.php`

**Interface** : `Services/RateServiceInterface.php`

**Namespace** : `App\IJCalculator\Services`

## Principe de Responsabilité Unique

Ce service suit le principe SOLID de **responsabilité unique** :
- ✅ Recherche de taux dans les tables historiques
- ✅ Calcul du taux journalier selon les paramètres
- ✅ Détermination du tier (période 1/2/3)
- ✅ Application des multiplicateurs d'option
- ❌ Ne gère PAS les dates, l'âge, ou les règles métier complexes

## Structure des Données de Taux

### Format CSV (taux.csv)

```csv
id;date_start;date_end;taux_a1;taux_a2;taux_a3;taux_b1;taux_b2;taux_b3;taux_c1;taux_c2;taux_c3
1;2024-01-01;2024-12-31;75.06;38.3;56.3;112.59;57.45;84.45;150.12;76.6;112.59
2;2023-01-01;2023-12-31;72.50;37.00;54.00;108.75;55.50;81.00;145.00;74.00;108.00
```

### Colonnes

- **`id`** : Identifiant unique de la ligne
- **`date_start`** : Date de début de validité
- **`date_end`** : Date de fin de validité
- **`taux_X1`** : Taux période 1 (jours 1-365)
- **`taux_X2`** : Taux période 2 (jours 366-730, pour âge 62-69)
- **`taux_X3`** : Taux période 3 (jours 731-1095, pour âge 62-69)
- **X** : a/b/c pour les classes A/B/C

### Tiers (Périodes)

Le **tier** détermine quelle colonne utiliser :

| Tier | Jours | Colonne | Usage |
|------|-------|---------|-------|
| 1 | 1-365 | taux_X1 | Tous âges, période initiale |
| 2 | 366-730 | taux_X2 | Âge 62-69, période 2 |
| 3 | 731-1095 | taux_X3 | Âge 62-69, période 3 |

## Constructeur

```php
public function __construct($csvPathOrRates = [])
```

### Paramètres

- **`$csvPathOrRates`** (string|array) :
  - **String** : Chemin vers le fichier CSV (format legacy)
  - **Array** : Tableau de taux préchargé (format moderne)

### Exemples

```php
// Méthode 1 : Charger depuis CSV
$rateService = new RateService('taux.csv');

// Méthode 2 : Utiliser un tableau de taux
$rates = [
    [
        'id' => 1,
        'date_start' => new DateTime('2024-01-01'),
        'date_end' => new DateTime('2024-12-31'),
        'taux_a1' => 75.06,
        'taux_a2' => 38.30,
        'taux_a3' => 56.30,
        'taux_b1' => 112.59,
        'taux_b2' => 57.45,
        'taux_b3' => 84.45,
        'taux_c1' => 150.12,
        'taux_c2' => 76.60,
        'taux_c3' => 112.59
    ]
];
$rateService = new RateService($rates);
```

## Méthodes Principales

### 1. getDailyRate()

Calcule le taux journalier selon tous les paramètres.

```php
public function getDailyRate(
    string $statut,
    string $classe,
    string|int|float $option,
    int $taux,
    int $year,
    ?string $date = null,
    ?int $age = null,
    ?bool $usePeriode2 = null
): float
```

#### Paramètres

- **`$statut`** (string) : Statut professionnel
  - `'M'` : Médecin
  - `'RSPM'` : Remplaçant Salarié Permanent Médecin
  - `'CCPL'` : Collaborateur libéral

- **`$classe`** (string) : Classe de cotisation
  - `'A'` : 1 PASS
  - `'B'` : 2 PASS
  - `'C'` : 3 PASS

- **`$option`** (string|int|float) : Pourcentage d'option
  - Formats acceptés : `25`, `"0,25"`, `0.25` (tous = 25%)
  - Valeurs courantes : `25`, `50`, `75`, `100`

- **`$taux`** (int) : Numéro de taux (1-9)

- **`$year`** (int) : Année pour la recherche de taux

- **`$date`** (string, optionnel) : Date spécifique (format 'Y-m-d')
  - Si fournie, prime sur `$year`

- **`$age`** (int, optionnel) : Âge du médecin
  - Utilisé pour déterminer le tier si ≥ 70 ans

- **`$usePeriode2`** (bool, optionnel) : Indique l'utilisation de la période 2
  - `true` : Utilise tier 2 (taux_X2)
  - `false` : Utilise tier 1 (taux_X1)
  - `null` : Déterminé automatiquement

#### Valeur de Retour

`float` : Taux journalier en euros

#### Exemples

**Exemple 1 : Médecin Classe A, Taux 1, Option 100%**

```php
$rateService = new RateService('taux.csv');

$dailyRate = $rateService->getDailyRate(
    statut: 'M',
    classe: 'A',
    option: 100,
    taux: 1,
    year: 2024
);

echo $dailyRate; // 75.06
```

**Exemple 2 : CCPL Classe B avec Option 50%**

```php
$dailyRate = $rateService->getDailyRate(
    statut: 'CCPL',
    classe: 'B',
    option: 50,
    taux: 1,
    year: 2024
);

// Calcul : taux_b1 (112.59) × 0.5 × multiplicateur CCPL
echo $dailyRate;
```

**Exemple 3 : Période 2 (62-69 ans, jours 366-730)**

```php
$dailyRate = $rateService->getDailyRate(
    statut: 'M',
    classe: 'C',
    option: 100,
    taux: 7,      // Taux période 2
    year: 2024,
    date: null,
    age: 65,
    usePeriode2: true  // Force l'utilisation du tier 2
);

// Utilise taux_c2 (76.60) au lieu de taux_c1
echo $dailyRate;
```

**Exemple 4 : Avec Date Spécifique**

```php
$dailyRate = $rateService->getDailyRate(
    statut: 'M',
    classe: 'A',
    option: 100,
    taux: 1,
    year: 2024,
    date: '2024-06-15'  // Date spécifique
);

echo $dailyRate;
```

**Exemple 5 : Formats d'Option Variés**

```php
// Tous ces appels donnent le même résultat (25%)
$rate1 = $rateService->getDailyRate('M', 'A', 25, 1, 2024);
$rate2 = $rateService->getDailyRate('M', 'A', '0,25', 1, 2024);
$rate3 = $rateService->getDailyRate('M', 'A', 0.25, 1, 2024);

echo $rate1 === $rate2 && $rate2 === $rate3; // true
```

### 2. getRateForYear()

Récupère les données de taux pour une année spécifique.

```php
public function getRateForYear(int $year): ?array
```

#### Paramètres

- **`$year`** (int) : Année recherchée

#### Valeur de Retour

- **`array`** : Tableau contenant toutes les colonnes de taux
- **`null`** : Si aucun taux n'est trouvé pour l'année

#### Exemple

```php
$rateService = new RateService('taux.csv');

$rate2024 = $rateService->getRateForYear(2024);

if ($rate2024) {
    echo "Taux A1 pour 2024 : " . $rate2024['taux_a1'] . "€\n";
    echo "Taux B1 pour 2024 : " . $rate2024['taux_b1'] . "€\n";
    echo "Taux C1 pour 2024 : " . $rate2024['taux_c1'] . "€\n";
} else {
    echo "Aucun taux trouvé pour 2024\n";
}
```

### 3. getRateForDate()

Récupère les données de taux pour une date spécifique.

```php
public function getRateForDate(string $date): ?array
```

#### Paramètres

- **`$date`** (string) : Date au format 'Y-m-d'

#### Valeur de Retour

- **`array`** : Tableau contenant toutes les colonnes de taux
- **`null`** : Si aucun taux n'est trouvé pour la date

#### Exemple

```php
$rateService = new RateService('taux.csv');

$rateJune2024 = $rateService->getRateForDate('2024-06-15');

if ($rateJune2024) {
    echo "Période : " . $rateJune2024['date_start']->format('Y-m-d') .
         " à " . $rateJune2024['date_end']->format('Y-m-d') . "\n";
    echo "Taux A1 : " . $rateJune2024['taux_a1'] . "€\n";
}
```

### 4. setPassValue()

Définit la valeur du PASS (Plafond Annuel de la Sécurité Sociale).

```php
public function setPassValue(float $value): void
```

#### Paramètres

- **`$value`** (float) : Valeur du PASS en euros

#### Exemple

```php
$rateService = new RateService('taux.csv');

// Définir le PASS pour 2024
$rateService->setPassValue(47000);

// Définir le PASS pour 2025
$rateService->setPassValue(48000);
```

## Logique de Détermination du Tier

Le tier (période) est déterminé selon ces règles :

```php
private function determineTier(
    int $taux,
    ?int $age,
    ?bool $usePeriode2
): int
```

### Règles

1. **Si `$usePeriode2` est spécifié** :
   - `true` → tier 2
   - `false` → tier 1

2. **Si `$age >= 70`** et `$taux` entre 4-6 :
   - tier 3 (taux senior réduit)

3. **Si `$taux` entre 7-9** :
   - tier 2 (période 2 pour 62-69 ans)

4. **Si `$taux` entre 4-6** (et age < 70) :
   - tier 3 (période 3)

5. **Par défaut** :
   - tier 1

### Schéma

```
Taux 1-3  → Tier 1 (taux_X1)
Taux 4-6  → Tier 3 (taux_X3) [sauf si age ≥ 70]
           → Tier 3 si age ≥ 70 (cas spécial)
Taux 7-9  → Tier 2 (taux_X2)

usePeriode2 = true  → Force Tier 2
usePeriode2 = false → Force Tier 1
```

## Multiplicateurs de Statut

Chaque statut applique un multiplicateur différent :

| Statut | Multiplicateur | Description |
|--------|----------------|-------------|
| M (Médecin) | 1.0 | Taux plein |
| RSPM | Variable | Selon règles RSPM |
| CCPL | Variable | Selon règles CCPL |

## Format d'Option

Le service accepte plusieurs formats pour l'option :

```php
// Ces formats sont équivalents pour 25% :
25          // Integer
"25"        // String number
"0,25"      // String with comma
"0.25"      // String with dot
0.25        // Float

// Conversion interne :
// → Normalise en pourcentage (0-100)
// → Divise par 100 pour obtenir le multiplicateur
```

## Exemples d'Utilisation Avancés

### Exemple 1 : Calcul Multi-Années

```php
$rateService = new RateService('taux.csv');

$years = [2022, 2023, 2024];
$rates = [];

foreach ($years as $year) {
    $rate = $rateService->getDailyRate(
        statut: 'M',
        classe: 'B',
        option: 100,
        taux: 1,
        year: $year
    );
    $rates[$year] = $rate;
}

// Comparer l'évolution
foreach ($rates as $year => $rate) {
    echo "$year: " . number_format($rate, 2, ',', ' ') . " €\n";
}
```

### Exemple 2 : Comparaison Classes

```php
$rateService = new RateService('taux.csv');

$classes = ['A', 'B', 'C'];
$comparison = [];

foreach ($classes as $classe) {
    $comparison[$classe] = $rateService->getDailyRate(
        statut: 'M',
        classe: $classe,
        option: 100,
        taux: 1,
        year: 2024
    );
}

echo "Classe A : {$comparison['A']} €\n";
echo "Classe B : {$comparison['B']} €\n";
echo "Classe C : {$comparison['C']} €\n";
echo "Ratio B/A : " . round($comparison['B'] / $comparison['A'], 2) . "\n";
echo "Ratio C/A : " . round($comparison['C'] / $comparison['A'], 2) . "\n";
```

### Exemple 3 : Impact de l'Option

```php
$rateService = new RateService('taux.csv');

$options = [25, 50, 75, 100];
$impacts = [];

foreach ($options as $option) {
    $impacts[$option] = $rateService->getDailyRate(
        statut: 'M',
        classe: 'C',
        option: $option,
        taux: 1,
        year: 2024
    );
}

echo "Impact de l'option sur le taux journalier :\n";
foreach ($impacts as $option => $rate) {
    $percentage = $option;
    echo "$percentage% : " . number_format($rate, 2, ',', ' ') . " €\n";
}
```

### Exemple 4 : Service avec Mock (Tests)

```php
// Pour les tests unitaires, injecter des taux mockés
$mockRates = [
    [
        'id' => 1,
        'date_start' => new DateTime('2024-01-01'),
        'date_end' => new DateTime('2024-12-31'),
        'taux_a1' => 100.00,
        'taux_b1' => 200.00,
        'taux_c1' => 300.00,
        // ... autres colonnes
    ]
];

$rateService = new RateService($mockRates);

// Test
$rate = $rateService->getDailyRate('M', 'A', 100, 1, 2024);
assert($rate === 100.00, "Le taux devrait être 100€");
```

### Exemple 5 : Gestion des Périodes pour 62-69 ans

```php
$rateService = new RateService('taux.csv');

// Médecin 65 ans
$age = 65;

// Période 1 (jours 1-365) : Taux plein
$period1 = $rateService->getDailyRate(
    statut: 'M',
    classe: 'B',
    option: 100,
    taux: 1,
    year: 2024,
    date: null,
    age: $age,
    usePeriode2: false
);

// Période 2 (jours 366-730) : Taux -25%
$period2 = $rateService->getDailyRate(
    statut: 'M',
    classe: 'B',
    option: 100,
    taux: 7,
    year: 2024,
    date: null,
    age: $age,
    usePeriode2: true
);

// Période 3 (jours 731-1095) : Taux senior
$period3 = $rateService->getDailyRate(
    statut: 'M',
    classe: 'B',
    option: 100,
    taux: 4,
    year: 2024,
    date: null,
    age: $age,
    usePeriode2: false
);

echo "Période 1 : " . number_format($period1, 2) . " €/jour\n";
echo "Période 2 : " . number_format($period2, 2) . " €/jour\n";
echo "Période 3 : " . number_format($period3, 2) . " €/jour\n";
```

## Gestion des Erreurs

Le service lève des exceptions en cas de problème :

```php
try {
    $rateService = new RateService('fichier_inexistant.csv');
} catch (RuntimeException $e) {
    echo "Erreur : " . $e->getMessage();
    // "CSV file not found: fichier_inexistant.csv"
}
```

**Erreurs possibles** :
- Fichier CSV introuvable
- Fichier CSV vide ou invalide
- Format de taux incorrect
- Aucun taux trouvé pour l'année/date demandée

## Points Importants

1. **Historique des taux** : Le service supporte plusieurs lignes dans le CSV pour gérer l'historique

2. **Format flexible** : Accepte DateTime objects ou strings pour les dates

3. **Option normalisée** : Tous les formats d'option sont convertis automatiquement

4. **Tier automatique** : La détermination du tier est intelligente selon le taux et l'âge

5. **Thread-safe** : Le service est immutable après construction (sauf setPassValue)

## Performance

- **Complexité temporelle** : O(n) pour la recherche dans les taux (n = nombre de lignes)
- **Optimisation possible** : Indexer les taux par année pour O(1)
- **Mémoire** : Tous les taux sont chargés en mémoire

## Voir Aussi

- [IJCalculator](./IJCalculator.md) - Classe principale
- [TauxDeterminationService](./TauxDeterminationService.md) - Détermination du numéro de taux
- [AmountCalculationService](./AmountCalculationService.md) - Utilisation dans les calculs
