# IJCalculator - Documentation Complète

## Vue d'ensemble

`IJCalculator` est la classe principale du système de calcul des Indemnités Journalières (IJ) pour les médecins selon les règles de la CARMF (Caisse Autonome de Retraite des Médecins de France).

**Emplacement** : `IJCalculator.php`

**Namespace** : `App\IJCalculator`

## Architecture

Cette classe utilise une architecture basée sur les principes SOLID avec injection de dépendances. Elle orchestre quatre services spécialisés :

- **RateService** : Gestion des taux et recherches dans les tables historiques
- **DateService** : Opérations liées aux dates (âge, trimestres, date d'effet)
- **TauxDeterminationService** : Détermination des numéros de taux (1-9) et classes
- **AmountCalculationService** : Calcul des montants et orchestration complète

## Système de 27 Taux

Le système utilise 27 taux différents organisés en 9 numéros × 3 classes (A/B/C) :

### Numéros de Taux par Âge

**Taux 1-3** : Médecins < 62 ans
- Taux 1 : Taux plein
- Taux 2 : Réduction 1/3
- Taux 3 : Réduction 2/3

**Taux 4-6** : Médecins ≥ 70 ans (ou après 1 an au taux 7-9)
- Taux 4 : Taux réduit senior
- Taux 5 : Réduction 1/3
- Taux 6 : Réduction 2/3

**Taux 7-9** : Médecins 62-69 ans (période 2, jours 366-730)
- Taux 7 : Taux -25%
- Taux 8 : Réduction 1/3
- Taux 9 : Réduction 2/3

### Classes de Cotisation

- **Classe A** : 1 PASS (47 000€ en 2024)
- **Classe B** : 2 PASS (94 000€ en 2024)
- **Classe C** : 3 PASS (141 000€ en 2024)

## Règles Métier

### Règle des 90 Jours

Les droits aux IJ s'ouvrent après **90 jours cumulés** d'arrêt de travail pour une nouvelle pathologie.

**Pénalités** :
- Déclaration tardive (DT) : +31 jours
- Mise à jour compte GPM : +31 jours

### Rechute

Une rechute est détectée si **TOUS** les critères suivants sont remplis :
1. Droits déjà ouverts (date-effet existante pour un arrêt précédent)
2. NON consécutif (écart entre les arrêts)
3. Début dans l'année suivant la fin de l'arrêt précédent

**Pour une rechute** :
- Seuil : 15 jours (au lieu de 90)
- Pénalités réduites : 15 jours (au lieu de 31)

### Pathologie Antérieure

Si la pathologie existait avant l'affiliation CARMF :

| Trimestres | Taux applicable |
|-----------|----------------|
| < 8 | Pas d'indemnisation |
| 8-15 | Taux +1 (réduction 1/3) |
| 16-23 | Taux +2 (réduction 2/3) |
| ≥ 24 | Taux plein |

### Périodes de Paiement selon l'Âge

**< 62 ans** : Un seul taux pour toute la durée

**62-69 ans** : Trois périodes distinctes
- Période 1 (jours 1-365) : Taux plein (1-3)
- Période 2 (jours 366-730) : Taux -25% (7-9)
- Période 3 (jours 731-1095) : Taux réduit senior (4-6)

**≥ 70 ans** : Maximum 365 jours avec taux réduit (4-6)

## Constructeur

```php
public function __construct(
    $csvPath = [],
    ?RateServiceInterface $rateService = null,
    ?DateCalculationInterface $dateService = null,
    ?TauxDeterminationInterface $tauxService = null,
    ?AmountCalculationInterface $amountService = null
)
```

### Paramètres

- **`$csvPath`** (array) : Tableau des taux CSV ou chemin vers le fichier
- **`$rateService`** (RateServiceInterface, optionnel) : Service de taux (pour tests/mocks)
- **`$dateService`** (DateCalculationInterface, optionnel) : Service de dates
- **`$tauxService`** (TauxDeterminationInterface, optionnel) : Service de détermination
- **`$amountService`** (AmountCalculationInterface, optionnel) : Service de calcul

## Méthodes Principales

### 1. calculateTotalAmount()

Calcule le montant total des IJ avec tous les détails.

```php
public function calculateTotalAmount(array $data): array
```

#### Paramètres `$data`

```php
[
    'statut' => 'M',              // M, RSPM, CCPL
    'classe' => 'A',              // A, B, C
    'option' => 100,              // 25, 50, 75, 100
    'birth_date' => '1960-01-15',
    'current_date' => '2024-01-15',
    'attestation_date' => '2024-01-31',
    'affiliation_date' => '2019-01-15',
    'nb_trimestres' => 22,
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000,
    'revenu_n_moins_2' => 50000,  // Optionnel, pour auto-détermination classe
    'arrets' => [
        [
            'arret-from-line' => '2023-09-04',
            'arret-to-line' => '2023-11-10',
            'rechute-line' => 0,
            'dt-line' => 1,
            'gpm-member-line' => 1,
            'declaration-date-line' => '2023-09-19'
        ]
    ]
]
```

#### Valeur de Retour

```php
[
    'nb_jours' => 59,
    'montant' => 4428.54,
    'age' => 64,
    'total_cumul_days' => 59,
    'end_payment_dates' => [
        'end_period_1' => '2024-12-02',
        'end_period_2' => '2025-11-02',
        'end_period_3' => '2026-10-02'
    ],
    'payment_details' => [
        [
            'arret_index' => 0,
            'arret_from' => '2023-09-04',
            'arret_to' => '2023-11-10',
            'date-effet' => '2023-12-03',
            'nb_jours' => 59,
            'decompte_days' => 90,
            'rate_breakdown' => [              // Résumé agrégé par période
                [
                    'year' => 2023,
                    'month' => 12,
                    'trimester' => 4,          // Q1-Q4
                    'nb_trimestres' => 22,
                    'period' => 1,             // Période 1/2/3 ou 'senior'
                    'start' => '2023-12-03',
                    'end' => '2023-12-31',
                    'days' => 29,
                    'rate' => 75.06,
                    'taux' => 1
                ],
                [
                    'year' => 2024,
                    'month' => 1,
                    'trimester' => 1,
                    'nb_trimestres' => 22,
                    'period' => 1,
                    'start' => '2024-01-01',
                    'end' => '2024-01-30',
                    'days' => 30,
                    'rate' => 75.06,
                    'taux' => 1
                ]
            ],
            'daily_breakdown' => [             // Détail jour par jour
                [
                    'date' => '2023-12-03',
                    'day_of_week' => 'Sunday',
                    'year' => 2023,
                    'month' => 12,
                    'trimester' => 4,
                    'period' => 1,
                    'taux' => 1,
                    'daily_rate' => 75.06,
                    'amount' => 75.06,
                    'nb_trimestres' => 22
                ]
            ]
        ]
    ]
]
```

### 2. calculateDateEffet()

Calcule les dates d'effet (ouverture des droits) pour chaque arrêt.

```php
public function calculateDateEffet(array $arrets, $birthDate, $previousCumulDays = 0): array
```

#### Paramètres

- **`$arrets`** (array) : Tableau des arrêts de travail
- **`$birthDate`** (string) : Date de naissance (format 'Y-m-d')
- **`$previousCumulDays`** (int) : Jours cumulés antérieurs

#### Exemple

```php
$calculator = new IJCalculator(['taux.csv']);

$arrets = [
    [
        'arret-from-line' => '2023-09-04',
        'arret-to-line' => '2023-11-10',
        'dt-line' => 1,
        'gpm-member-line' => 1,
        'rechute-line' => 0
    ]
];

$result = $calculator->calculateDateEffet($arrets, '1960-01-15', 0);
```

### 3. calculateEndPaymentDate()

Calcule les dates de fin de paiement par période selon l'âge.

```php
public function calculateEndPaymentDate(
    array $arrets,
    $birthDate,
    $currentDate,
    $previousCumulDays = 0
): array
```

### 4. calculateRevenu()

Calcule le revenu annuel selon la classe et le PASS.

```php
public function calculateRevenu(string $classe, ?float $nbPass, float $passValue): array
```

### 5. determineClasseFromRevenu()

Détermine automatiquement la classe (A/B/C) selon le revenu N-2.

```php
public function determineClasseFromRevenu(float $revenuNMoins2, float $passValue): ?string
```

## Exemples d'Utilisation

### Exemple 1 : Calcul Simple avec un Arrêt

```php
<?php
require_once 'IJCalculator.php';

use App\IJCalculator\IJCalculator;

// Initialisation
$calculator = new IJCalculator(['taux.csv']);

// Données d'entrée
$data = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1960-01-15',
    'current_date' => '2024-01-15',
    'attestation_date' => '2024-01-31',
    'affiliation_date' => '2019-01-15',
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000,
    'arrets' => [
        [
            'arret-from-line' => '2023-09-04',
            'arret-to-line' => '2023-12-31',
            'rechute-line' => 0,
            'dt-line' => 0,
            'gpm-member-line' => 0,
            'declaration-date-line' => '2023-09-04'
        ]
    ]
];

// Calcul
$result = $calculator->calculateTotalAmount($data);

// Affichage
echo "Nombre de jours payables : " . $result['nb_jours'] . "\n";
echo "Montant total : " . number_format($result['montant'], 2, ',', ' ') . " €\n";
echo "Âge : " . $result['age'] . " ans\n";
```

### Exemple 2 : Calcul avec Rechute

```php
<?php
require_once 'IJCalculator.php';

use App\IJCalculator\IJCalculator;

$calculator = new IJCalculator(['taux.csv']);

$data = [
    'statut' => 'M',
    'classe' => 'B',
    'option' => 100,
    'birth_date' => '1958-06-03',
    'current_date' => '2024-01-15',
    'attestation_date' => '2024-01-31',
    'affiliation_date' => '2010-01-01',
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000,
    'arrets' => [
        // Premier arrêt (ouvre les droits)
        [
            'arret-from-line' => '2023-01-01',
            'arret-to-line' => '2023-05-31',
            'rechute-line' => 0,
            'dt-line' => 0,
            'gpm-member-line' => 0
        ],
        // Deuxième arrêt (rechute - dans l'année)
        [
            'arret-from-line' => '2023-09-01',
            'arret-to-line' => '2023-11-30',
            'rechute-line' => 1,
            'dt-line' => 0,
            'gpm-member-line' => 0
        ]
    ]
];

$result = $calculator->calculateTotalAmount($data);

foreach ($result['payment_details'] as $detail) {
    echo "Arrêt du " . $detail['arret_from'] . " au " . $detail['arret_to'] . "\n";
    echo "Date-effet : " . $detail['date-effet'] . "\n";
    echo "Jours payables : " . $detail['nb_jours'] . "\n";
    echo "Jours décompte : " . $detail['decompte_days'] . "\n\n";
}
```

### Exemple 3 : Auto-détermination de la Classe

```php
<?php
require_once 'IJCalculator.php';

use App\IJCalculator\IJCalculator;

$calculator = new IJCalculator(['taux.csv']);

// Sans spécifier la classe, elle sera déterminée automatiquement
$data = [
    'statut' => 'M',
    // 'classe' => 'B',  // Omis volontairement
    'option' => 100,
    'birth_date' => '1965-03-20',
    'current_date' => '2024-01-15',
    'affiliation_date' => '2015-01-01',
    'revenu_n_moins_2' => 85000,  // Revenu N-2 pour détermination
    'pass_value' => 47000,
    'arrets' => [
        [
            'arret-from-line' => '2023-10-01',
            'arret-to-line' => '2024-01-15'
        ]
    ]
];

$result = $calculator->calculateTotalAmount($data);

// La classe sera déterminée automatiquement :
// 85000€ / 47000€ = 1.8 PASS → Classe B (entre 1 et 3 PASS)
```

### Exemple 4 : Pathologie Antérieure

```php
<?php
require_once 'IJCalculator.php';

use App\IJCalculator\IJCalculator;

$calculator = new IJCalculator(['taux.csv']);

$data = [
    'statut' => 'M',
    'classe' => 'C',
    'option' => 100,
    'birth_date' => '1970-05-15',
    'current_date' => '2024-01-15',
    'affiliation_date' => '2021-01-01',  // 3 ans = 12 trimestres
    'nb_trimestres' => 12,
    'patho_anterior' => true,  // Pathologie existait avant affiliation
    'previous_cumul_days' => 0,
    'pass_value' => 47000,
    'arrets' => [
        [
            'arret-from-line' => '2023-11-01',
            'arret-to-line' => '2024-02-29'
        ]
    ]
];

$result = $calculator->calculateTotalAmount($data);

// Avec 12 trimestres et pathologie antérieure :
// Taux sera augmenté de +1 (réduction 1/3)
// Si âge < 62 : taux 2 au lieu de 1
```

### Exemple 5 : Médecin 62-69 ans (Multi-périodes)

```php
<?php
require_once 'IJCalculator.php';

use App\IJCalculator\IJCalculator;

$calculator = new IJCalculator(['taux.csv']);

$data = [
    'statut' => 'M',
    'classe' => 'B',
    'option' => 100,
    'birth_date' => '1960-01-01',  // 64 ans en 2024
    'current_date' => '2024-01-15',
    'attestation_date' => '2026-01-15',  // 2 ans d'attestation
    'affiliation_date' => '2000-01-01',
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'pass_value' => 47000,
    'arrets' => [
        [
            'arret-from-line' => '2023-09-01',
            'arret-to-line' => '2026-01-15'  // Long arrêt
        ]
    ]
];

$result = $calculator->calculateTotalAmount($data);

// Résultat avec 3 périodes :
// - Période 1 (jours 1-365) : Taux plein (taux 1)
// - Période 2 (jours 366-730) : Taux -25% (taux 7)
// - Période 3 (jours 731-1095) : Taux senior (taux 4)

echo "Fin période 1 : " . $result['end_payment_dates']['end_period_1'] . "\n";
echo "Fin période 2 : " . $result['end_payment_dates']['end_period_2'] . "\n";
echo "Fin période 3 : " . $result['end_payment_dates']['end_period_3'] . "\n";
```

### Exemple 6 : Statut CCPL avec Option Réduite

```php
<?php
require_once 'IJCalculator.php';

use App\IJCalculator\IJCalculator;

$calculator = new IJCalculator(['taux.csv']);

$data = [
    'statut' => 'CCPL',  // Statut CCPL
    'classe' => 'A',
    'option' => 50,  // Option à 50% (multiplicateur 0.5)
    'birth_date' => '1975-08-10',
    'current_date' => '2024-01-15',
    'affiliation_date' => '2018-01-01',
    'pass_value' => 47000,
    'arrets' => [
        [
            'arret-from-line' => '2023-10-01',
            'arret-to-line' => '2024-01-15'
        ]
    ]
];

$result = $calculator->calculateTotalAmount($data);

// Le montant sera multiplié par 0.5 (option 50%)
// Taux CCPL appliqué selon les règles spécifiques
```

## Gestion des Erreurs

La classe lève des exceptions en cas d'erreur :

```php
try {
    $result = $calculator->calculateTotalAmount($data);
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
```

**Erreurs courantes** :
- Données manquantes (statut, classe, dates)
- Format de date invalide
- Classe invalide (doit être A, B ou C)
- Statut invalide (doit être M, RSPM ou CCPL)
- Arrêts sans dates valides

## Méthodes Utilitaires

### setPassValue()

Définit la valeur du PASS pour l'année.

```php
public function setPassValue(float $value): void

// Exemple
$calculator->setPassValue(48000); // PASS 2025
```

### getServices()

Récupère les services injectés (utile pour tests).

```php
public function getServices(): array

// Retourne
[
    'rateService' => RateServiceInterface,
    'dateService' => DateCalculationInterface,
    'tauxService' => TauxDeterminationInterface,
    'amountService' => AmountCalculationInterface
]
```

## Points Importants

1. **Trimestres** : Si `nb_trimestres` n'est pas fourni mais `affiliation_date` l'est, le calcul est automatique

2. **Classe** : Peut être omise si `revenu_n_moins_2` est fourni (auto-détermination)

3. **Rechute** : La détection est automatique même si `rechute-line` est à 0

4. **Décompte** : Les jours avant l'ouverture des droits sont comptabilisés séparément

5. **Dates-effet** : Peuvent être vides si le seuil de 90/15 jours n'est pas atteint

6. **Attestation** : Si absente, les calculs vont jusqu'à la fin de l'arrêt

## Différence rate_breakdown vs daily_breakdown

### rate_breakdown (Résumé Agrégé)

Le `rate_breakdown` fournit un **résumé agrégé** des paiements groupés par période de taux :
- Groupé par mois/année/trimestre/période
- Un enregistrement par segment de taux identique
- Utile pour comprendre la structure tarifaire
- Optimisé pour l'analyse et les rapports

**Exemple** : Un mois entier au même taux = 1 entrée

```php
[
    'year' => 2023,
    'month' => 12,
    'trimester' => 4,
    'period' => 1,
    'days' => 31,              // 31 jours groupés
    'rate' => 75.06,
    'taux' => 1
]
```

### daily_breakdown (Détail Quotidien)

Le `daily_breakdown` fournit un **détail jour par jour** :
- Une entrée par jour de paiement
- Inclut le jour de la semaine
- Utile pour les calendriers et affichages détaillés
- Utilisé par l'interface web pour le calendrier

**Exemple** : Un mois entier = 31 entrées

```php
[
    'date' => '2023-12-01',
    'day_of_week' => 'Friday',
    'year' => 2023,
    'month' => 12,
    'period' => 1,
    'daily_rate' => 75.06,
    'amount' => 75.06
],
[
    'date' => '2023-12-02',
    'day_of_week' => 'Saturday',
    // ...
]
// ... 29 autres entrées
```

### Quand Utiliser Chaque Format

| Utilisation | Format Recommandé |
|-------------|-------------------|
| Rapports mensuels | `rate_breakdown` |
| Statistiques par période | `rate_breakdown` |
| Calendrier visuel | `daily_breakdown` |
| Export détaillé | `daily_breakdown` |
| Base de données (ij_recap) | `rate_breakdown` |
| Base de données (ij_detail_jour) | `daily_breakdown` |
| Validation calculs | Les deux |

### Exemple d'Utilisation

```php
$result = $calculator->calculateTotalAmount($data);

foreach ($result['payment_details'] as $detail) {
    // Analyse par période (rate_breakdown)
    echo "=== Résumé par période ===\n";
    foreach ($detail['rate_breakdown'] as $segment) {
        echo "{$segment['month']}/{$segment['year']} : ";
        echo "{$segment['days']} jours à {$segment['rate']}€ ";
        echo "(taux {$segment['taux']}, période {$segment['period']})\n";
    }

    // Détail quotidien (daily_breakdown)
    echo "\n=== Détail jour par jour ===\n";
    foreach ($detail['daily_breakdown'] as $day) {
        echo "{$day['date']} ({$day['day_of_week']}) : {$day['amount']}€\n";
    }
}
```

## Voir Aussi

- [RateService](./RateService.md) - Service de gestion des taux
- [DateService](./DateService.md) - Service de calcul des dates
- [TauxDeterminationService](./TauxDeterminationService.md) - Détermination des taux
- [AmountCalculationService](./AmountCalculationService.md) - Calcul des montants
