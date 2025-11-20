# AmountCalculationService - Documentation Complète

## Vue d'ensemble

`AmountCalculationService` est le service orchestrateur principal qui coordonne tous les autres services pour calculer les montants totaux des IJ. Il gère le calcul multi-périodes, les détails quotidiens, et l'auto-détermination de la classe.

**Emplacement** : `Services/AmountCalculationService.php`

**Interface** : `Services/AmountCalculationInterface.php`

**Namespace** : `App\IJCalculator\Services`

**Lignes de code** : 736 lignes

## Principe d'Orchestration

Ce service coordonne :
- **RateService** : Récupération des taux
- **DateService** : Calculs de dates et jours payables
- **TauxDeterminationService** : Détermination des taux

## Méthode Principale

### calculateTotalAmount()

Calcule le montant total avec tous les détails.

```php
public function calculateTotalAmount(array $data): array
```

#### Paramètres `$data`

```php
[
    'statut' => 'M',                    // M, RSPM, CCPL
    'classe' => 'A',                    // A, B, C (optionnel si revenu_n_moins_2)
    'option' => 100,                    // 25, 50, 75, 100
    'birth_date' => '1960-01-15',
    'current_date' => '2024-01-15',
    'attestation_date' => '2024-01-31',
    'affiliation_date' => '2019-01-15',
    'nb_trimestres' => 22,              // Auto-calculé si affiliation_date
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000,
    'revenu_n_moins_2' => 50000,        // Optionnel, pour auto-détermination
    'arrets' => [/* ... */]
]
```

#### Valeur de Retour

```php
[
    'nb_jours' => int,                  // Total jours payables
    'montant' => float,                 // Montant total €
    'age' => int,                       // Âge du médecin
    'total_cumul_days' => int,          // Jours cumulés totaux
    'end_payment_dates' => [            // Dates fin par période
        'end_period_1' => 'Y-m-d',
        'end_period_2' => 'Y-m-d',
        'end_period_3' => 'Y-m-d'
    ],
    'payment_details' => [              // Détails par arrêt
        [
            'arret_index' => 0,
            'arret_from' => 'Y-m-d',
            'arret_to' => 'Y-m-d',
            'date-effet' => 'Y-m-d',
            'nb_jours' => int,
            'decompte_days' => int,
            'rate_breakdown' => [       // Résumé agrégé par période/mois
                [
                    'year' => int,
                    'month' => int,
                    'trimester' => int,
                    'nb_trimestres' => int,
                    'period' => int|string,  // 1/2/3 ou 'senior'
                    'start' => 'Y-m-d',
                    'end' => 'Y-m-d',
                    'days' => int,
                    'rate' => float,
                    'taux' => int
                ]
            ],
            'daily_breakdown' => [      // Détail jour par jour
                [
                    'date' => 'Y-m-d',
                    'day_of_week' => string,
                    'year' => int,
                    'month' => int,
                    'trimester' => int,
                    'period' => int|string,
                    'taux' => int,
                    'daily_rate' => float,
                    'amount' => float,
                    'nb_trimestres' => int
                ]
            ]
        ]
    ]
]
```

## Exemples d'Utilisation

### Exemple 1 : Calcul Complet Standard

```php
use App\IJCalculator\Services\AmountCalculationService;
use App\IJCalculator\Services\RateService;
use App\IJCalculator\Services\DateService;
use App\IJCalculator\Services\TauxDeterminationService;

// Initialiser les services
$rateService = new RateService('taux.csv');
$dateService = new DateService();
$tauxService = new TauxDeterminationService();

$amountService = new AmountCalculationService(
    $rateService,
    $dateService,
    $tauxService
);

// Données d'entrée
$data = [
    'statut' => 'M',
    'classe' => 'B',
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
            'arret-from-line' => '2023-09-01',
            'arret-to-line' => '2023-12-31',
            'dt-line' => 0,
            'gpm-member-line' => 0
        ]
    ]
];

// Calcul
$result = $amountService->calculateTotalAmount($data);

// Affichage
echo "Montant total : " . number_format($result['montant'], 2, ',', ' ') . " €\n";
echo "Jours payables : {$result['nb_jours']}\n";
echo "Âge : {$result['age']} ans\n";
```

### Exemple 2 : Auto-détermination de la Classe

```php
$data = [
    'statut' => 'M',
    // 'classe' => 'B',  // Omis volontairement
    'option' => 100,
    'birth_date' => '1965-03-20',
    'current_date' => '2024-01-15',
    'affiliation_date' => '2015-01-01',
    'revenu_n_moins_2' => 85000,  // Auto-détermine classe B
    'pass_value' => 47000,
    'arrets' => [/* ... */]
];

$result = $amountService->calculateTotalAmount($data);
// La classe B sera utilisée automatiquement
```

### Exemple 3 : Médecin 62-69 ans Multi-périodes

```php
$data = [
    'statut' => 'M',
    'classe' => 'C',
    'option' => 100,
    'birth_date' => '1960-01-01',  // 64 ans
    'current_date' => '2024-01-15',
    'attestation_date' => '2026-01-15',  // Couvre toutes périodes
    'affiliation_date' => '2000-01-01',
    'arrets' => [
        [
            'arret-from-line' => '2023-09-01',
            'arret-to-line' => '2026-01-15'  // Long arrêt
        ]
    ]
];

$result = $amountService->calculateTotalAmount($data);

// Vérifier les 3 périodes
echo "Fin période 1 : {$result['end_payment_dates']['end_period_1']}\n";
echo "Fin période 2 : {$result['end_payment_dates']['end_period_2']}\n";
echo "Fin période 3 : {$result['end_payment_dates']['end_period_3']}\n";

// Analyser les taux par période
foreach ($result['payment_details'][0]['daily_breakdown'] as $day) {
    echo "{$day['date']} : Taux {$day['taux']}, Période {$day['period']}, {$day['amount']}€\n";
}
```

### Exemple 4 : Avec Rechute

```php
$data = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1970-05-15',
    'current_date' => '2024-01-15',
    'affiliation_date' => '2015-01-01',
    'arrets' => [
        // Premier arrêt
        [
            'arret-from-line' => '2023-01-01',
            'arret-to-line' => '2023-05-31'
        ],
        // Rechute
        [
            'arret-from-line' => '2023-09-01',
            'arret-to-line' => '2023-11-30'
        ]
    ]
];

$result = $amountService->calculateTotalAmount($data);

// Comparer les deux arrêts
foreach ($result['payment_details'] as $detail) {
    echo "Arrêt {$detail['arret_index']} :\n";
    echo "  Date-effet : {$detail['date-effet']}\n";
    echo "  Jours payables : {$detail['nb_jours']}\n";
    echo "  Décompte : {$detail['decompte_days']} jours\n";
    echo "  Montant : " . array_sum(array_column($detail['daily_breakdown'], 'amount')) . "€\n\n";
}
```

## Logique Interne

### Pipeline de Calcul

```
1. Validation des données
   ↓
2. Auto-détermination classe (si nécessaire)
   ↓
3. Calcul âge et trimestres
   ↓
4. Fusion des prolongations
   ↓
5. Calcul dates d'effet (règle 90 jours)
   ↓
6. Calcul jours payables
   ↓
7. Détermination taux pour chaque jour
   ↓
8. Calcul montants quotidiens
   ↓
9. Agrégation et formatage résultat
```

### Gestion Multi-périodes (62-69 ans)

Le service gère automatiquement :
- **Période 1** (jours 1-365) : Taux plein (1-3)
- **Période 2** (jours 366-730) : Taux -25% (7-9)
- **Période 3** (jours 731-1095) : Taux senior (4-6)

## Deux Niveaux de Détail : rate_breakdown vs daily_breakdown

Le service génère **deux formats** de données complémentaires :

### 1. rate_breakdown - Résumé Agrégé

**Usage** : Analyse, rapports, statistiques

```php
// Groupé par mois/période avec taux identique
[
    'year' => 2023,
    'month' => 12,
    'trimester' => 4,
    'nb_trimestres' => 22,
    'period' => 1,              // 1, 2, 3 ou 'senior'
    'start' => '2023-12-01',
    'end' => '2023-12-31',
    'days' => 31,               // 31 jours groupés
    'rate' => 75.06,
    'taux' => 1
]
```

**Avantages** :
- Compact (1 entrée par segment de taux)
- Idéal pour bases de données
- Facilite l'analyse par période
- Utilisé par RecapService

### 2. daily_breakdown - Détail Quotidien

**Usage** : Calendriers, exports détaillés, vérifications

```php
// Une entrée par jour
[
    'date' => '2023-12-01',
    'day_of_week' => 'Friday',
    'year' => 2023,
    'month' => 12,
    'trimester' => 4,
    'period' => 1,
    'taux' => 1,
    'daily_rate' => 75.06,
    'amount' => 75.06,
    'nb_trimestres' => 22
]
```

**Avantages** :
- Précis jour par jour
- Jour de la semaine inclus
- Idéal pour interface calendrier
- Utilisé par DetailJourService

### Exemple d'Utilisation des Deux Formats

```php
$result = $amountService->calculateTotalAmount($data);

foreach ($result['payment_details'] as $detail) {
    // 1. Analyser avec rate_breakdown (compact)
    echo "=== Résumé mensuel ===\n";
    $totalParMois = [];

    foreach ($detail['rate_breakdown'] as $segment) {
        $key = "{$segment['month']}/{$segment['year']}";
        $montant = $segment['days'] * $segment['rate'];

        if (!isset($totalParMois[$key])) {
            $totalParMois[$key] = 0;
        }
        $totalParMois[$key] += $montant;
    }

    foreach ($totalParMois as $mois => $montant) {
        echo "$mois : " . number_format($montant, 2) . "€\n";
    }

    // 2. Vérifier avec daily_breakdown (précis)
    echo "\n=== Vérification quotidienne ===\n";
    $totalVerification = 0;

    foreach ($detail['daily_breakdown'] as $day) {
        $totalVerification += $day['amount'];

        // Afficher jours spécifiques si besoin
        if ($day['day_of_week'] === 'Monday') {
            echo "{$day['date']} (Lundi) : {$day['amount']}€\n";
        }
    }

    echo "\nTotal vérifié : " . number_format($totalVerification, 2) . "€\n";
}
```

### Cas d'Usage Recommandés

| Besoin | Format | Raison |
|--------|--------|--------|
| Rapport mensuel | `rate_breakdown` | Compact et groupé |
| Statistiques par période | `rate_breakdown` | Agrégation naturelle |
| Export comptable | `rate_breakdown` | Format synthétique |
| Calendrier visuel | `daily_breakdown` | Affichage jour par jour |
| Validation détaillée | `daily_breakdown` | Vérification précise |
| Interface web | `daily_breakdown` | Interactivité |
| Table ij_recap | `rate_breakdown` | Optimisé pour BDD |
| Table ij_detail_jour | `daily_breakdown` | Colonnes j1-j31 |

## Points Importants

1. **Orchestration complète** : Coordonne tous les services
2. **Auto-détermination** : Classe automatique si revenu fourni
3. **Multi-périodes** : Gère les 3 périodes pour âge 62-69
4. **Double détail** : rate_breakdown (agrégé) + daily_breakdown (quotidien)
5. **Dates fin** : Calcule les dates de fin pour chaque période
6. **Optimisé** : rate_breakdown réduit le volume de données

## Voir Aussi

- [IJCalculator](./IJCalculator.md) - Classe principale
- [RateService](./RateService.md) - Service de taux
- [DateService](./DateService.md) - Service de dates
- [TauxDeterminationService](./TauxDeterminationService.md) - Détermination taux
