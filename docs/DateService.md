# DateService - Documentation Complète

## Vue d'ensemble

`DateService` est le service responsable de toutes les opérations liées aux dates dans le système IJ. Il gère les calculs d'âge, de trimestres, la fusion des prolongations, le calcul des dates d'effet (règle des 90 jours), et la détermination des jours payables.

**Emplacement** : `Services/DateService.php`

**Interface** : `Services/DateCalculationInterface.php`

**Namespace** : `App\IJCalculator\Services`

**Lignes de code** : 743 lignes

## Principe de Responsabilité Unique

Ce service suit le principe SOLID de **responsabilité unique** :
- ✅ Calculs d'âge et de trimestres
- ✅ Fusion des arrêts consécutifs (prolongations)
- ✅ Calcul des dates d'effet (règle 90 jours)
- ✅ Détection des rechutes
- ✅ Calcul des jours payables et décompte
- ❌ Ne gère PAS les taux ni les montants

## Méthodes Principales

### 1. calculateAge()

Calcule l'âge à une date donnée.

```php
public function calculateAge(string $currentDate, string $birthDate): int
```

#### Paramètres

- **`$currentDate`** (string) : Date de référence (format 'Y-m-d')
- **`$birthDate`** (string) : Date de naissance (format 'Y-m-d')

#### Valeur de Retour

`int` : Âge en années complètes

#### Exemples

```php
use App\IJCalculator\Services\DateService;

$dateService = new DateService();

// Exemple 1 : Calcul simple
$age = $dateService->calculateAge('2024-06-15', '1960-01-15');
echo $age; // 64

// Exemple 2 : Avant anniversaire
$age = $dateService->calculateAge('2024-01-10', '1960-01-15');
echo $age; // 63 (anniversaire pas encore passé)

// Exemple 3 : Jour de l'anniversaire
$age = $dateService->calculateAge('2024-01-15', '1960-01-15');
echo $age; // 64 (anniversaire = jour d'aujourd'hui)
```

### 2. calculateTrimesters()

Calcule le nombre de trimestres d'affiliation entre deux dates.

```php
public function calculateTrimesters(string $affiliationDate, string $currentDate): int
```

#### Règle Importante

**Les trimestres partiels comptent comme complets** (règle d'arrondi).

#### Trimestres (Quarters)

- **Q1** : Janvier à Mars (mois 1-3)
- **Q2** : Avril à Juin (mois 4-6)
- **Q3** : Juillet à Septembre (mois 7-9)
- **Q4** : Octobre à Décembre (mois 10-12)

#### Formule

```
Trimestres = (années × 4) + (quarter_actuel - quarter_affiliation) + 1
```

#### Paramètres

- **`$affiliationDate`** (string) : Date d'affiliation CARMF (format 'Y-m-d')
- **`$currentDate`** (string) : Date de référence (généralement date du premier arrêt)

#### Valeur de Retour

`int` : Nombre de trimestres (arrondi supérieur si partiel)

#### Exemples

```php
$dateService = new DateService();

// Exemple 1 : Calcul standard
$trimestres = $dateService->calculateTrimesters('2019-01-15', '2024-04-11');
echo $trimestres; // 22
// 2019 Q1 → 2024 Q2 = 5 ans × 4 + (2-1) + 1 = 22

// Exemple 2 : Même trimestre
$trimestres = $dateService->calculateTrimesters('2024-01-01', '2024-03-31');
echo $trimestres; // 1 (Q1 à Q1 = 1 trimestre)

// Exemple 3 : Trimestre partiel compte comme complet
$trimestres = $dateService->calculateTrimesters('2024-01-31', '2024-04-01');
echo $trimestres; // 2 (Q1 + Q2, même si Q1 n'a qu'un jour)

// Exemple 4 : Plusieurs années
$trimestres = $dateService->calculateTrimesters('2010-06-15', '2024-09-20');
echo $trimestres; // 58
// 2010 Q2 → 2024 Q3 = 14 ans × 4 + (3-2) + 1 = 58
```

### 3. mergeProlongations()

Fusionne les arrêts consécutifs (prolongations) en périodes uniques.

```php
public function mergeProlongations(array $arrets): array
```

#### Définition : Prolongation

Deux arrêts sont considérés comme **consécutifs** (prolongation) si :
- La date de fin du premier arrêt + 1 jour = date de début du deuxième arrêt
- OU les dates se chevauchent

#### Paramètres

- **`$arrets`** (array) : Tableau d'arrêts à fusionner

#### Valeur de Retour

`array` : Tableau contenant :
- **`merged`** : Arrêts fusionnés
- **`merged_indices`** : Indices des arrêts fusionnés (pour traçabilité)

#### Exemples

```php
$dateService = new DateService();

// Exemple 1 : Arrêts consécutifs (prolongation)
$arrets = [
    [
        'arret-from-line' => '2023-09-01',
        'arret-to-line' => '2023-09-30'
    ],
    [
        'arret-from-line' => '2023-10-01',  // Jour suivant
        'arret-to-line' => '2023-10-31'
    ]
];

$result = $dateService->mergeProlongations($arrets);
// Résultat : Un seul arrêt du 2023-09-01 au 2023-10-31
echo count($result['merged']); // 1
print_r($result['merged_indices']); // [[0, 1]] (indices fusionnés)

// Exemple 2 : Arrêts NON consécutifs (rechute potentielle)
$arrets = [
    [
        'arret-from-line' => '2023-09-01',
        'arret-to-line' => '2023-09-30'
    ],
    [
        'arret-from-line' => '2023-11-01',  // Gap de 1 mois
        'arret-to-line' => '2023-11-30'
    ]
];

$result = $dateService->mergeProlongations($arrets);
// Résultat : Deux arrêts distincts
echo count($result['merged']); // 2

// Exemple 3 : Arrêts chevauchants
$arrets = [
    [
        'arret-from-line' => '2023-09-01',
        'arret-to-line' => '2023-09-30'
    ],
    [
        'arret-from-line' => '2023-09-25',  // Chevauchement
        'arret-to-line' => '2023-10-15'
    ]
];

$result = $dateService->mergeProlongations($arrets);
// Résultat : Un seul arrêt du 2023-09-01 au 2023-10-15
echo count($result['merged']); // 1

// Exemple 4 : Trois arrêts consécutifs
$arrets = [
    ['arret-from-line' => '2023-09-01', 'arret-to-line' => '2023-09-30'],
    ['arret-from-line' => '2023-10-01', 'arret-to-line' => '2023-10-31'],
    ['arret-from-line' => '2023-11-01', 'arret-to-line' => '2023-11-30']
];

$result = $dateService->mergeProlongations($arrets);
// Résultat : Un seul arrêt du 2023-09-01 au 2023-11-30
echo count($result['merged']); // 1
print_r($result['merged_indices']); // [[0, 1, 2]]
```

### 4. calculateDateEffet()

Calcule les dates d'effet (ouverture des droits) selon la règle des 90 jours.

```php
public function calculateDateEffet(
    array $arrets,
    ?string $birthDate = null,
    int $previousCumulDays = 0
): array
```

#### Règle des 90 Jours

**Nouvelle pathologie** : Les droits s'ouvrent après **90 jours cumulés** d'arrêt.

**Rechute** : Les droits s'ouvrent après **15 jours cumulés**.

#### Pénalités

| Type | Nouvelle Pathologie | Rechute |
|------|---------------------|---------|
| Déclaration tardive (DT) | +31 jours | +15 jours |
| Mise à jour GPM | +31 jours | +15 jours |

#### Critères de Rechute

Un arrêt est une **rechute** si **TOUS** ces critères sont remplis :
1. ✅ Un arrêt précédent a déjà ouvert des droits (date-effet existe)
2. ✅ L'arrêt n'est PAS consécutif (gap entre les arrêts)
3. ✅ L'arrêt commence dans l'année suivant la fin de l'arrêt précédent

#### Paramètres

- **`$arrets`** (array) : Tableau des arrêts
- **`$birthDate`** (string, optionnel) : Date de naissance (pour calcul âge)
- **`$previousCumulDays`** (int) : Jours cumulés antérieurs (défaut : 0)

#### Valeur de Retour

`array` : Arrêts avec champs ajoutés :
- **`date-effet`** : Date d'ouverture des droits (ou vide si seuil non atteint)
- **`payment_start`** : Date de début de paiement
- **`is_rechute`** : Boolean indiquant si c'est une rechute
- **`rechute_of_arret_index`** : Index de l'arrêt source (si rechute)

#### Exemples

**Exemple 1 : Premier Arrêt - Nouvelle Pathologie**

```php
$dateService = new DateService();

$arrets = [
    [
        'arret-from-line' => '2023-09-01',
        'arret-to-line' => '2023-12-31',  // 122 jours
        'dt-line' => 0,
        'gpm-member-line' => 0
    ]
];

$result = $dateService->calculateDateEffet($arrets, '1960-01-15', 0);

// Jours d'arrêt : 122 jours
// Seuil : 90 jours (nouvelle pathologie)
// Date-effet : 2023-09-01 + 90 jours = 2023-11-30
// Paiement commence : 2023-12-01 (jour suivant)

echo $result[0]['date-effet']; // "2023-11-30"
echo $result[0]['payment_start']; // "2023-12-01"
echo $result[0]['is_rechute']; // false
```

**Exemple 2 : Arrêt Court - Seuil Non Atteint**

```php
$arrets = [
    [
        'arret-from-line' => '2023-09-01',
        'arret-to-line' => '2023-10-15',  // 45 jours seulement
        'dt-line' => 0,
        'gpm-member-line' => 0
    ]
];

$result = $dateService->calculateDateEffet($arrets, '1960-01-15', 0);

// 45 jours < 90 jours : seuil non atteint
echo $result[0]['date-effet']; // "" (vide)
echo $result[0]['payment_start']; // "" (vide)
```

**Exemple 3 : Déclaration Tardive avec Pénalité**

```php
$arrets = [
    [
        'arret-from-line' => '2023-09-01',
        'arret-to-line' => '2024-01-31',  // 153 jours
        'dt-line' => 1,  // Déclaration tardive
        'gpm-member-line' => 1  // GPM à mettre à jour
    ]
];

$result = $dateService->calculateDateEffet($arrets, '1960-01-15', 0);

// Seuil : 90 + 31 (DT) + 31 (GPM) = 152 jours
// Date-effet : 2023-09-01 + 152 jours = 2024-01-31
// Paiement commence : 2024-02-01

echo $result[0]['date-effet']; // "2024-01-31"
```

**Exemple 4 : Rechute (Droits Déjà Ouverts)**

```php
$arrets = [
    // Premier arrêt : ouvre les droits
    [
        'arret-from-line' => '2023-01-01',
        'arret-to-line' => '2023-05-31',  // 151 jours
        'dt-line' => 0,
        'gpm-member-line' => 0
    ],
    // Deuxième arrêt : rechute dans l'année
    [
        'arret-from-line' => '2023-09-01',  // Gap de 3 mois
        'arret-to-line' => '2023-10-15',  // 45 jours
        'dt-line' => 0,
        'gpm-member-line' => 0
    ]
];

$result = $dateService->calculateDateEffet($arrets, '1960-01-15', 0);

// Premier arrêt : 90 jours atteints → date-effet au 2023-04-01
echo $result[0]['date-effet']; // "2023-04-01"
echo $result[0]['is_rechute']; // false

// Deuxième arrêt : rechute (seuil 15 jours)
// Date-effet : 2023-09-01 + 15 jours = 2023-09-16
echo $result[1]['date-effet']; // "2023-09-16"
echo $result[1]['is_rechute']; // true
echo $result[1]['rechute_of_arret_index']; // 0
```

**Exemple 5 : Multiples Arrêts Courts Cumulés**

```php
$arrets = [
    [
        'arret-from-line' => '2023-09-01',
        'arret-to-line' => '2023-09-30',  // 30 jours
        'dt-line' => 0,
        'gpm-member-line' => 0
    ],
    [
        'arret-from-line' => '2023-11-01',  // Non consécutif
        'arret-to-line' => '2023-11-30',  // 30 jours
        'dt-line' => 0,
        'gpm-member-line' => 0
    ],
    [
        'arret-from-line' => '2024-01-01',  // Non consécutif
        'arret-to-line' => '2024-02-29',  // 60 jours
        'dt-line' => 0,
        'gpm-member-line' => 0
    ]
];

$result = $dateService->calculateDateEffet($arrets, '1960-01-15', 0);

// Arrêt 1 : 30 jours < 90 → pas de date-effet
echo $result[0]['date-effet']; // ""

// Arrêt 2 : 30 + 30 = 60 jours < 90 → pas de date-effet
echo $result[1]['date-effet']; // ""

// Arrêt 3 : 30 + 30 + 60 = 120 jours > 90 → date-effet !
// Date-effet : cumul 90 jours atteints le jour X
echo $result[2]['date-effet']; // Date calculée
```

### 5. calculatePayableDays()

Calcule les jours payables pour chaque arrêt avec détail quotidien.

```php
public function calculatePayableDays(
    array $arrets,
    ?string $attestationDate = null,
    ?string $lastPaymentDate = null,
    ?string $currentDate = null
): array
```

#### Paramètres

- **`$arrets`** (array) : Arrêts avec date-effet calculée
- **`$attestationDate`** (string, optionnel) : Date de dernière attestation médicale
- **`$lastPaymentDate`** (string, optionnel) : Date de dernier paiement
- **`$currentDate`** (string, optionnel) : Date actuelle

#### Valeur de Retour

```php
[
    'total_days' => int,  // Nombre total de jours payables
    'payment_details' => [
        [
            'arret_index' => 0,
            'nb_jours' => 59,
            'daily_breakdown' => [
                ['date' => '2023-12-01', 'payable' => true],
                ['date' => '2023-12-02', 'payable' => true],
                // ...
            ]
        ]
    ]
]
```

#### Exemples

**Exemple 1 : Calcul Simple**

```php
$dateService = new DateService();

// Préparer l'arrêt avec date-effet
$arrets = [
    [
        'arret-from-line' => '2023-09-01',
        'arret-to-line' => '2023-12-31',
        'date-effet' => '2023-11-30',
        'payment_start' => '2023-12-01'
    ]
];

$result = $dateService->calculatePayableDays(
    $arrets,
    attestationDate: '2023-12-31',
    lastPaymentDate: null,
    currentDate: '2024-01-15'
);

// Jours payables : du 2023-12-01 au 2023-12-31 = 31 jours
echo $result['total_days']; // 31
```

**Exemple 2 : Avec Attestation Limitante**

```php
$arrets = [
    [
        'arret-from-line' => '2023-09-01',
        'arret-to-line' => '2024-03-31',  // 6 mois
        'date-effet' => '2023-11-30',
        'payment_start' => '2023-12-01'
    ]
];

$result = $dateService->calculatePayableDays(
    $arrets,
    attestationDate: '2023-12-31',  // Attestation jusqu'au 31 déc
    lastPaymentDate: null,
    currentDate: '2024-01-15'
);

// Jours payables limités par attestation : 31 jours
echo $result['total_days']; // 31 (pas 122)
```

**Exemple 3 : Détail Quotidien**

```php
$arrets = [
    [
        'arret-from-line' => '2023-12-01',
        'arret-to-line' => '2023-12-10',
        'date-effet' => '2023-11-30',
        'payment_start' => '2023-12-01'
    ]
];

$result = $dateService->calculatePayableDays(
    $arrets,
    attestationDate: '2023-12-31',
    lastPaymentDate: null,
    currentDate: '2024-01-15'
);

// Détail jour par jour
foreach ($result['payment_details'][0]['daily_breakdown'] as $day) {
    echo $day['date'] . " : " . ($day['payable'] ? "Payable" : "Non payable") . "\n";
}
```

### 6. calculateDecompteDays()

Calcule les jours de décompte (non payés avant l'ouverture des droits).

```php
public function calculateDecompteDays(
    string $arretFrom,
    string $dateEffet,
    bool $isRechute
): int
```

#### Définition : Décompte

Les jours de **décompte** sont les jours comptés vers le seuil mais **non payés** :
- Nouvelle pathologie : 90 jours de décompte
- Rechute : 14 jours de décompte (15 - 1)

#### Paramètres

- **`$arretFrom`** (string) : Date de début d'arrêt
- **`$dateEffet`** (string) : Date d'effet (ouverture droits)
- **`$isRechute`** (bool) : Indique si c'est une rechute

#### Valeur de Retour

`int` : Nombre de jours de décompte

#### Exemples

```php
$dateService = new DateService();

// Nouvelle pathologie
$decompte = $dateService->calculateDecompteDays(
    arretFrom: '2023-09-01',
    dateEffet: '2023-11-30',
    isRechute: false
);
echo $decompte; // 90

// Rechute
$decompte = $dateService->calculateDecompteDays(
    arretFrom: '2023-09-01',
    dateEffet: '2023-09-16',
    isRechute: true
);
echo $decompte; // 14
```

### 7. isRechute()

Détermine si un arrêt est une rechute.

```php
public function isRechute(
    array $currentArret,
    array $previousArrets
): bool
```

#### Critères

Un arrêt est une rechute si **TOUS** ces critères sont vrais :
1. Un arrêt précédent a une date-effet (droits ouverts)
2. L'arrêt actuel n'est PAS consécutif au précédent
3. L'arrêt commence ≤ 365 jours après la fin du précédent

#### Exemples

```php
$dateService = new DateService();

// Exemple 1 : Rechute valide
$current = [
    'arret-from-line' => '2023-09-01',
    'arret-to-line' => '2023-10-31'
];

$previous = [
    [
        'arret-from-line' => '2023-01-01',
        'arret-to-line' => '2023-05-31',
        'date-effet' => '2023-04-01',  // Droits ouverts
        'payment_start' => '2023-04-02'
    ]
];

$isRechute = $dateService->isRechute($current, $previous);
echo $isRechute; // true (gap de 3 mois, dans l'année)

// Exemple 2 : Pas de rechute (consécutif = prolongation)
$current = ['arret-from-line' => '2023-06-01', 'arret-to-line' => '2023-06-30'];
$previous = [
    ['arret-from-line' => '2023-05-01', 'arret-to-line' => '2023-05-31']
];

$isRechute = $dateService->isRechute($current, $previous);
echo $isRechute; // false (consécutif)

// Exemple 3 : Pas de rechute (> 1 an)
$current = ['arret-from-line' => '2024-06-01', 'arret-to-line' => '2024-06-30'];
$previous = [
    [
        'arret-from-line' => '2023-01-01',
        'arret-to-line' => '2023-05-31',
        'date-effet' => '2023-04-01'
    ]
];

$isRechute = $dateService->isRechute($current, $previous);
echo $isRechute; // false (> 365 jours)
```

### 8. getTrimesterFromDate()

Retourne le numéro de trimestre (1-4) pour une date.

```php
public function getTrimesterFromDate(string $date): int
```

#### Valeur de Retour

- **1** : Janvier - Mars (Q1)
- **2** : Avril - Juin (Q2)
- **3** : Juillet - Septembre (Q3)
- **4** : Octobre - Décembre (Q4)

#### Exemples

```php
$dateService = new DateService();

echo $dateService->getTrimesterFromDate('2024-01-15'); // 1
echo $dateService->getTrimesterFromDate('2024-04-20'); // 2
echo $dateService->getTrimesterFromDate('2024-07-10'); // 3
echo $dateService->getTrimesterFromDate('2024-12-31'); // 4
```

## Exemples d'Utilisation Complète

### Exemple Complet : Flux de Calcul

```php
use App\IJCalculator\Services\DateService;

$dateService = new DateService();

// 1. Calculer l'âge
$age = $dateService->calculateAge('2024-01-15', '1960-01-15');
echo "Âge : $age ans\n";

// 2. Calculer les trimestres
$trimestres = $dateService->calculateTrimesters('2019-01-15', '2024-01-15');
echo "Trimestres : $trimestres\n";

// 3. Préparer les arrêts
$arrets = [
    [
        'arret-from-line' => '2023-09-01',
        'arret-to-line' => '2023-12-31',
        'dt-line' => 0,
        'gpm-member-line' => 0
    ]
];

// 4. Fusionner les prolongations
$merged = $dateService->mergeProlongations($arrets);
echo "Arrêts fusionnés : " . count($merged['merged']) . "\n";

// 5. Calculer les dates d'effet
$arretsWithDateEffet = $dateService->calculateDateEffet(
    $merged['merged'],
    '1960-01-15',
    0
);

// 6. Calculer les jours payables
$payableResult = $dateService->calculatePayableDays(
    $arretsWithDateEffet,
    attestationDate: '2024-01-31',
    lastPaymentDate: null,
    currentDate: '2024-02-01'
);

echo "Jours payables : " . $payableResult['total_days'] . "\n";
```

## Points Importants

1. **Fusion avant calcul** : Toujours fusionner les prolongations avant de calculer les dates d'effet

2. **Rechute intelligente** : La détection est automatique selon 3 critères précis

3. **Décompte** : Les jours avant date-effet sont comptabilisés séparément

4. **Trimestres arrondis** : Les trimestres partiels comptent toujours comme complets

5. **Attestation limite** : La date d'attestation limite les jours payables

## Gestion des Erreurs

```php
try {
    $age = $dateService->calculateAge('2024-01-15', 'date-invalide');
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
```

## Voir Aussi

- [IJCalculator](./IJCalculator.md) - Classe principale
- [TauxDeterminationService](./TauxDeterminationService.md) - Utilise calculateTrimesters()
- [AmountCalculationService](./AmountCalculationService.md) - Utilise calculatePayableDays()
