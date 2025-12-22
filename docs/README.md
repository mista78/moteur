# Documentation Technique - Système de Calcul IJ CARMF

> Documentation complète en français pour le système de calcul des Indemnités Journalières (IJ) des médecins selon les règles CARMF.

## 📚 Table des Matières

### Classe Principale

- **[IJCalculator](./IJCalculator.md)** - Classe principale d'orchestration du système IJ

### Services de Calcul (SOLID)

- **[RateService](./RateService.md)** - Gestion des taux journaliers et tables historiques
- **[DateService](./DateService.md)** - Calculs de dates, âge, trimestres, date-effet
- **[TauxDeterminationService](./TauxDeterminationService.md)** - Détermination des numéros de taux (1-9)
- **[AmountCalculationService](./AmountCalculationService.md)** - Orchestration et calcul des montants

### Services de Données

- **[RecapService](./RecapService.md)** - Génération enregistrements table `ij_recap`
- **[DetailJourService](./DetailJourService.md)** - Génération enregistrements table `ij_detail_jour`
- **[ArretService](./ArretService.md)** - Gestion des collections d'arrêts de travail
- **[DateNormalizer](./DateNormalizer.md)** - Normalisation des formats de dates

### Services Métier

- **[SinistreService](./SinistreService.md)** - Gestion des sinistres avec calcul date-effet
- **[DetailsArretsService](./DetailsArretsService.md)** - Détermination automatique classe d'arrêt
- **[DetailsAdherentsService](./DetailsAdherentsService.md)** - Récupération revenus adhérents par année

## 🎯 Vue d'Ensemble du Système

### Architecture SOLID

Le système utilise une architecture basée sur les principes SOLID avec séparation des responsabilités :

```
IJCalculator (Orchestrateur)
    ├── RateService (Taux)
    ├── DateService (Dates)
    ├── TauxDeterminationService (Détermination)
    └── AmountCalculationService (Calculs)
         ├── RecapService (Base de données - recap)
         ├── DetailJourService (Base de données - détail)
         ├── ArretService (Gestion arrêts)
         ├── DateNormalizer (Normalisation)
         ├── SinistreService (Gestion sinistres)
         ├── DetailsArretsService (Détermination classe)
         └── DetailsAdherentsService (Revenus adhérents)
```

### Flux de Calcul

```
1. Validation données
2. Auto-détermination classe (si nécessaire)
3. Calcul âge et trimestres
4. Fusion prolongations
5. Calcul dates d'effet (règle 90 jours)
6. Détermination taux (1-9)
7. Calcul jours payables
8. Calcul montants quotidiens
9. Génération enregistrements base de données
```

## 🚀 Démarrage Rapide

### Installation

```bash
# Aucune installation nécessaire, système standalone PHP
cd /home/mista/work/ij
```

### Exemple Minimal

```php
<?php
require_once 'IJCalculator.php';

use App\IJCalculator\IJCalculator;

// Initialiser
$calculator = new IJCalculator(['taux.csv']);

// Données
$data = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1960-01-15',
    'current_date' => '2024-01-15',
    'affiliation_date' => '2019-01-15',
    'arrets' => [
        [
            'arret-from-line' => '2023-09-01',
            'arret-to-line' => '2023-12-31'
        ]
    ]
];

// Calculer
$result = $calculator->calculateTotalAmount($data);

// Afficher
echo "Montant : {$result['montant']}€\n";
echo "Jours : {$result['nb_jours']}\n";
```

## 📖 Documentation par Composant

### 1. IJCalculator - Classe Principale

**Utilisation** : Orchestration complète du système
**Documentation** : [IJCalculator.md](./IJCalculator.md)

**Méthodes clés** :
- `calculateTotalAmount()` - Calcul complet avec tous les détails
- `calculateDateEffet()` - Calcul des dates d'effet (règle 90 jours)
- `calculateEndPaymentDate()` - Dates de fin par période
- `determineClasseFromRevenu()` - Auto-détermination classe

### 2. RateService - Gestion des Taux

**Utilisation** : Recherche et calcul des taux journaliers
**Documentation** : [RateService.md](./RateService.md)

**Méthodes clés** :
- `getDailyRate()` - Calcul taux journalier avec tous paramètres
- `getRateForYear()` - Récupération taux par année
- `getRateForDate()` - Récupération taux par date

**Données** : Table CSV `taux.csv` avec 27 taux (9 × 3 classes)

### 3. DateService - Calculs de Dates

**Utilisation** : Toutes les opérations liées aux dates
**Documentation** : [DateService.md](./DateService.md)

**Méthodes clés** :
- `calculateAge()` - Calcul âge
- `calculateTrimesters()` - Calcul trimestres d'affiliation
- `mergeProlongations()` - Fusion arrêts consécutifs
- `calculateDateEffet()` - Règle des 90 jours
- `calculatePayableDays()` - Jours payables avec détail quotidien
- `isRechute()` - Détection rechute

**Règles critiques** :
- 90 jours pour nouvelle pathologie
- 15 jours pour rechute
- Trimestres partiels = complets

### 4. TauxDeterminationService - Détermination Taux

**Utilisation** : Détermination du numéro de taux (1-9)
**Documentation** : [TauxDeterminationService.md](./TauxDeterminationService.md)

**Méthodes clés** :
- `determineTauxNumber()` - Taux selon âge/trimestres/pathologie
- `determineClasse()` - Classe selon revenu N-2

**Système 9 taux** :
- Taux 1-3 : < 62 ans
- Taux 4-6 : ≥ 70 ans
- Taux 7-9 : 62-69 ans période 2

### 5. AmountCalculationService - Orchestration

**Utilisation** : Calcul complet avec coordination de tous les services
**Documentation** : [AmountCalculationService.md](./AmountCalculationService.md)

**Méthode principale** :
- `calculateTotalAmount()` - Orchestration complète du pipeline

**Responsabilités** :
- Coordination des services
- Auto-détermination classe
- Gestion multi-périodes
- Génération **deux formats** de détails :
  - `rate_breakdown` : Résumé agrégé par période/mois
  - `daily_breakdown` : Détail jour par jour

### 6. RecapService - Enregistrements Récap

**Utilisation** : Génération enregistrements pour table `ij_recap`
**Documentation** : [RecapService.md](./RecapService.md)

**Méthodes clés** :
- `generateRecapRecords()` - Transformation résultats → records
- `generateInsertSQL()` - Génération SQL INSERT
- `generateBatchInsertSQL()` - SQL batch

**Format** : Un record par mois/taux avec montants en centimes

### 7. DetailJourService - Détail Quotidien

**Utilisation** : Génération enregistrements pour table `ij_detail_jour`
**Documentation** : [DetailJourService.md](./DetailJourService.md)

**Méthodes clés** :
- `generateDetailJourRecords()` - Mapping jours → colonnes j1-j31
- `generateInsertSQL()` - SQL INSERT avec colonnes j1-j31

**Format** : Un record par mois avec colonnes j1-j31 (montants en centimes)

### 8. ArretService - Gestion Arrêts

**Utilisation** : Chargement, validation, manipulation arrêts
**Documentation** : [ArretService.md](./ArretService.md)

**Méthodes clés** :
- `loadFromJson()` / `loadFromEntities()` - Chargement multi-sources
- `normalizeArrets()` - Normalisation noms de champs
- `validateArrets()` - Validation données
- `sortByDate()` / `filterByDateRange()` - Tri et filtrage
- `groupBySinistre()` / `countTotalDays()` - Groupement et agrégation

**Fonctionnalités** :
- Multi-sources (JSON, BDD, tableaux)
- Normalisation automatique
- Validation robuste
- Utilitaires pratiques

### 9. DateNormalizer - Normalisation Dates

**Utilisation** : Normalisation formats de dates
**Documentation** : [DateNormalizer.md](./DateNormalizer.md)

**Méthodes clés** :
- `normalize()` - Normalisation date unique
- `normalizeArray()` - Normalisation tableau
- `normalizeRecord()` - Normalisation enregistrement
- `isValid()` - Validation
- `toDateTime()` - Conversion vers DateTime

**Formats acceptés** :
- DateTime objects
- ISO 8601 ('Y-m-d')
- Formats européens (DD/MM/YYYY)
- Formats base de données (DATETIME)

**Format sortie** : Toujours 'Y-m-d'

## 🔑 Concepts Clés

### Système de 27 Taux

**9 numéros × 3 classes = 27 taux différents**

| Numéros | Âge | Utilisation |
|---------|-----|-------------|
| 1-3 | < 62 ans | Taux plein / -1/3 / -2/3 |
| 4-6 | ≥ 70 ans | Senior réduit / -1/3 / -2/3 |
| 7-9 | 62-69 ans | Période 2 : -25% / -1/3 / -2/3 |

| Classes | PASS | Montant (2024) |
|---------|------|----------------|
| A | 1 PASS | 47 000€ |
| B | 2 PASS | 94 000€ |
| C | 3 PASS | 141 000€ |

### Règle des 90 Jours

**Nouvelle pathologie** :
- Seuil : 90 jours cumulés
- Pénalité DT : +31 jours
- Pénalité GPM : +31 jours

**Rechute** :
- Seuil : 15 jours cumulés
- Pénalité DT : +15 jours
- Pénalité GPM : +15 jours
- Critères : Droits ouverts + Non consécutif + < 1 an

### Périodes selon l'Âge

**< 62 ans** : Un seul taux (1-3)

**62-69 ans** : Trois périodes
- Période 1 (jours 1-365) : Taux plein (1-3)
- Période 2 (jours 366-730) : Taux -25% (7-9)
- Période 3 (jours 731-1095) : Taux senior (4-6)

**≥ 70 ans** : Maximum 365 jours, taux senior (4-6)

### Pathologie Antérieure

| Trimestres | Réduction | Taux |
|-----------|-----------|------|
| < 8 | Inéligible | 0 |
| 8-15 | -1/3 | Base +1 |
| 16-23 | -2/3 | Base +2 |
| ≥ 24 | Plein | Base +0 |

## 💡 Cas d'Usage Courants

### Calcul Simple

```php
$calculator = new IJCalculator(['taux.csv']);
$result = $calculator->calculateTotalAmount($data);
```

**Documentation** : [IJCalculator.md - Exemple 1](./IJCalculator.md#exemple-1--calcul-simple-avec-un-arrêt)

### Calcul avec Rechute

```php
$data['arrets'] = [
    ['arret-from-line' => '2023-01-01', 'arret-to-line' => '2023-05-31'],
    ['arret-from-line' => '2023-09-01', 'arret-to-line' => '2023-11-30']
];
```

**Documentation** : [IJCalculator.md - Exemple 2](./IJCalculator.md#exemple-2--calcul-avec-rechute)

### Auto-détermination Classe

```php
$data = [
    // 'classe' => 'B',  // Omis
    'revenu_n_moins_2' => 85000,  // Auto-détermine B
    'pass_value' => 47000
];
```

**Documentation** : [IJCalculator.md - Exemple 3](./IJCalculator.md#exemple-3--auto-détermination-de-la-classe)

### Génération Base de Données

```php
$recapService = new RecapService();
$detailService = new DetailJourService();

$recapRecords = $recapService->generateRecapRecords($result, $data);
$detailRecords = $detailService->generateDetailJourRecords($result, $data);
```

**Documentation** :
- [RecapService.md](./RecapService.md)
- [DetailJourService.md](./DetailJourService.md)

## 🧪 Tests

### Tests Unitaires

```bash
# Tous les tests
php run_all_tests.php

# Par service
php Tests/RateServiceTest.php
php Tests/DateServiceTest.php
php Tests/TauxDeterminationServiceTest.php
php Tests/AmountCalculationServiceTest.php
php Tests/RechuteTest.php
```

### Tests d'Intégration

```bash
# 18+ scénarios réels
php test_mocks.php

# Tests spécifiques
php test_rechute_integration.php
php test_decompte.php
```

### Tests Debug

```bash
php debug_mock2.php   # Rechute
php debug_mock9.php   # Transition 70 ans
php debug_mock20.php  # Période 2
```

## 📊 Données de Test

### Mock Files

- `mock.json` - Cas basique simple
- `mock2.json` - Rechute
- `mock7.json` - CCPL + pathologie antérieure
- `mock9.json` - Transition 70 ans
- `mock10.json` - Période 2 (62-69 ans)
- `mock20.json`, `mock28.json` - Scénarios complexes

### Fichier Taux

- `taux.csv` - Table historique 2022-2025

## 🔧 Développement

### Structure Fichiers

```
/
├── docs/                     # Cette documentation
│   ├── README.md            # Ce fichier
│   ├── IJCalculator.md
│   ├── RateService.md
│   ├── DateService.md
│   ├── TauxDeterminationService.md
│   ├── AmountCalculationService.md
│   ├── RecapService.md
│   ├── DetailJourService.md
│   ├── ArretService.md
│   ├── DateNormalizer.md
│   ├── SinistreService.md
│   ├── DetailsArretsService.md
│   └── DetailsAdherentsService.md
├── IJCalculator.php          # Classe principale
├── Services/                 # Services SOLID
│   ├── RateService.php
│   ├── DateService.php
│   ├── TauxDeterminationService.php
│   ├── AmountCalculationService.php
│   ├── RecapService.php
│   ├── DetailJourService.php
│   ├── ArretService.php
│   ├── DateNormalizer.php
│   ├── SinistreService.php
│   ├── DetailsArretsService.php
│   └── DetailsAdherentsService.php
├── Tests/                    # Tests unitaires
├── api.php                   # API REST
└── taux.csv                  # Données taux
```

### Serveur de Développement

```bash
# Serveur PHP standalone
php -S localhost:8000

# Serveur CakePHP (si intégration)
bin/cake server
```

## 📚 Documentation Complémentaire

### Documentation Markdown Existante

- `CLAUDE.md` - Guide complet pour Claude Code
- `README.md` - Documentation projet
- `REFACTORING.md` - Architecture SOLID
- `RATE_RULES.md` - Système 27 taux détaillé
- `TESTING_SUMMARY.md` - Stratégie de test
- `CLASS_DETERMINATION_SUMMARY.md` - Auto-détermination classe
- `RECHUTE_IMPLEMENTATION_SUMMARY.md` - Logique rechute
- `DECOMPTE_FEATURE.md` - Jours décompte

### Documentation Services Base

- `RECAP_SERVICE_DOCUMENTATION.md` - RecapService détaillé
- `DETAIL_JOUR_SERVICE_DOCUMENTATION.md` - DetailJourService détaillé
- `ARRET_SERVICE_DOC.md` - ArretService détaillé
- `ARRETS_ENDPOINT_DOC.md` - API batch date-effet

## ❓ Support

### Ressources

- **Tests** : Voir `/Tests/` pour exemples d'utilisation
- **Mocks** : Voir `mock*.json` pour données de test
- **API** : Voir `api.php` pour endpoints REST

### Patterns Courants

1. **Initialisation** : Toujours charger `taux.csv`
2. **Validation** : Valider les arrêts avant calcul
3. **Normalisation** : Normaliser dates et champs
4. **Calcul** : Utiliser `calculateTotalAmount()` pour calcul complet
5. **Persistance** : Utiliser RecapService + DetailJourService

## 🎓 Pour Commencer

1. **Débutants** : Commencer par [IJCalculator.md](./IJCalculator.md)
2. **Développeurs** : Lire [CLAUDE.md](../CLAUDE.md) puis cette doc
3. **Architectes** : Voir [REFACTORING.md](../REFACTORING.md)

## 📝 Licence

Usage interne uniquement.

---

**Dernière mise à jour** : 2024
**Version** : 1.0
**Auteur** : Système IJ CARMF
