# Structure complète CakePHP 5 - Indemnités Journalières

## Vue d'ensemble

Cette application CakePHP 5 implémente le système de calcul des indemnités journalières (IJ) pour les professionnels de santé avec un système de 27 taux basé sur l'âge, la classe, et la période.

## Fichiers créés

### 1. Service Layer

#### src/Service/IJCalculatorService.php
- **Rôle**: Service principal de calcul des indemnités journalières
- **Caractéristiques**:
  - Calcul par segments d'âge (per-segment age calculation)
  - Système de 27 taux (9 numéros × 3 colonnes)
  - Gestion des transitions d'âge pendant les périodes de paiement
  - Lecture des taux depuis CSV
  - Type hints stricts (declare(strict_types=1))
- **Méthodes principales**:
  - `calculateAmount(array $data): array` - Calcul du montant total
  - `calculateAge(string $date, string $birthDate): int` - Calcul de l'âge
  - `getRate()` - Récupération du taux selon le contexte

### 2. Controllers

#### src/Controller/IndemniteJournaliereController.php
- **Rôle**: Contrôleur pour les endpoints web et API
- **Actions**:
  - `index()` - Affichage du formulaire de calcul
  - `calculate()` - Traitement POST du formulaire
  - `apiCalculate()` - Endpoint JSON API
  - `view($id)` - Consultation d'un calcul sauvegardé
- **Caractéristiques**:
  - Gestion des erreurs avec Flash messages
  - Support JSON pour l'API
  - Sauvegarde optionnelle dans la base de données

### 3. Models - Entities

#### src/Model/Entity/Arret.php
- **Rôle**: Entité représentant un arrêt de travail
- **Propriétés**:
  - Dates: arret_from, arret_to, attestation_date, declaration_date
  - Métadonnées: arret_diff, rechute, option, code_pathologie
  - Adhérent: adherent_number, birth_date
- **Méthodes**:
  - `toCalculatorFormat()` - Conversion au format attendu par le calculateur

#### src/Model/Entity/Calculation.php
- **Rôle**: Entité représentant un calcul sauvegardé
- **Propriétés**:
  - Paramètres: statut, classe, option, birth_date
  - Résultats: nb_jours, montant, age, total_cumul_days
  - Données: calculation_data (JSON), input_data (JSON)
- **Virtual Fields**:
  - `formatted_montant` - Montant formaté avec €
- **Méthodes**:
  - `getCalculationData()` - Décodage des données de calcul JSON
  - `getInputData()` - Décodage des données d'entrée JSON

### 4. Models - Tables

#### src/Model/Table/ArretsTable.php
- **Rôle**: Table pour la gestion des arrêts de travail
- **Validation**:
  - Validation des dates (arret_from, arret_to)
  - Vérification que arret_to >= arret_from
  - Validation des champs obligatoires
- **Custom Finders**:
  - `findByAdherent(string $adherentNumber)` - Arrêts par adhérent
  - `findActive()` - Arrêts actifs (non terminés)
- **Méthodes utilitaires**:
  - `toCalculatorFormat(array $arrets)` - Conversion batch

#### src/Model/Table/CalculationsTable.php
- **Rôle**: Table pour les calculs sauvegardés
- **Validation**:
  - Validation du statut (M, RSPM, CCPL)
  - Validation de la classe (A, B, C)
  - Validation de l'option (25, 50, 100)
  - Validation des montants positifs
- **Custom Finders**:
  - `findByAdherent(string $adherentNumber)` - Calculs par adhérent
  - `findRecent(int $days = 30)` - Calculs récents
  - `findByStatut(string $statut)` - Calculs par statut
- **Statistiques**:
  - `getAdherentStats(string $adherentNumber)` - Statistiques adhérent

### 5. Forms

#### src/Form/IJCalculationForm.php
- **Rôle**: Formulaire de validation pour les calculs IJ
- **Champs validés**:
  - Adhérent: adherent_number (requis)
  - Professionnel: statut, classe, option (requis)
  - Dates: birth_date (requise), current_date, attestation_date, etc.
  - Trimestres: nb_trimestres (≥0)
  - Cumul: previous_cumul_days (≥0)
  - Prorata: entre 0 et 1
  - Arrêts: tableau (requis, non vide)

### 6. Migrations

#### config/Migrations/20241006000001_CreateArrets.php
- **Table**: arrets
- **Colonnes**:
  - Dates: arret_from, arret_to, attestation_date, declaration_date, date_effet
  - Entiers: arret_diff, rechute, option
  - Strings: code_pathologie, adherent_number
  - Timestamps: created, modified
- **Index**:
  - adherent_number, arret_from, arret_to, code_pathologie

#### config/Migrations/20241006000002_CreateCalculations.php
- **Table**: calculations
- **Colonnes**:
  - Identification: adherent_number
  - Paramètres: statut, classe, option, birth_date
  - Résultats: nb_jours, montant (decimal 10,2), age
  - Cumuls: total_cumul_days, nb_trimestres
  - Pathologie: patho_anterior (boolean)
  - Données: calculation_data (text), input_data (text)
  - Timestamps: calculated_at, created, modified
- **Index**:
  - adherent_number, statut, calculated_at, birth_date

### 7. Configuration

#### config/app_local.example.php
- **Configuration IJ**:
  - `IJ.passValue` - Valeur du PASS (47000)
  - `IJ.ratesFile` - Chemin vers taux.csv
  - `IJ.availableOptions` - [25, 50, 100]
  - `IJ.availableStatuts` - ['M', 'RSPM', 'CCPL']
  - `IJ.availableClasses` - ['A', 'B', 'C']
  - `IJ.minimumQuarters` - 8
  - `IJ.maxDaysTotal` - 1095
  - `IJ.maxDaysPerYearSenior` - 365
  - `IJ.rightsStartThreshold` - 90

### 8. Tests

#### tests/TestCase/Service/IJCalculatorServiceTest.php
- **Tests complets**: 12 scénarios mock + tests unitaires
- **Scénarios testés**:
  - mock1: Calcul de base (750.60€)
  - mock2: Multiple arrêts (17318.92€)
  - mock3: Calcul étendu (41832.60€)
  - mock4: Avec last_payment_date (37875.88€)
  - mock5: last_payment_date différent (34276.56€)
  - mock6: Affiliation récente (31412.61€)
  - mock7: CCPL avec patho anterior (74331.79€)
  - mock8: Nouveau cas (19291.28€)
  - mock9: **Transition d'âge 70 ans** (53467.98€, 730 jours)
  - mock10: **Période 2 taux intermédiaire** (51744.25€, 725 jours)
  - mock11: Cas supplémentaire (10245.69€)
  - mock12: Avec paiement partiel (8330.25€)
- **Tests unitaires**:
  - `testCalculateAge()` - Vérification du calcul d'âge
  - `testInsufficientQuarters()` - Exception si < 8 trimestres

#### tests/Fixture/
- Contient tous les fichiers mock.json à mock12.json
- Chaque fichier contient un tableau d'arrêts de travail au format JSON

### 9. Documentation

#### README_CAKEPHP.md
- **Installation complète**: Étapes détaillées
- **Structure du projet**: Organisation des fichiers
- **Utilisation API**: Endpoints et exemples
- **Service de calcul**: Utilisation programmatique
- **Modèles de données**: Exemples CRUD
- **Tests**: Commandes et validation
- **Configuration taux**: Format CSV
- **Logique de calcul**: 27 taux, règles par âge
- **Développement**: Extensibilité
- **Dépannage**: Solutions aux problèmes courants

#### CAKEPHP5_MIGRATION.md (existant)
- Guide de migration depuis le code PHP original
- Changements clés pour CakePHP 5
- Exemples d'utilisation dans les controllers
- Tests validés (12 mocks)
- Structure recommandée

#### composer_requirements.txt
- Dépendances Composer nécessaires
- Package recommandés
- Exemple de composer.json complet
- Étapes d'installation

## Structure des répertoires

```
/home/mista/work/ij/
├── config/
│   ├── Migrations/
│   │   ├── 20241006000001_CreateArrets.php
│   │   └── 20241006000002_CreateCalculations.php
│   ├── app_local.example.php
│   └── taux.csv
├── src/
│   ├── Controller/
│   │   └── IndemniteJournaliereController.php
│   ├── Model/
│   │   ├── Entity/
│   │   │   ├── Arret.php
│   │   │   └── Calculation.php
│   │   └── Table/
│   │       ├── ArretsTable.php
│   │       └── CalculationsTable.php
│   ├── Service/
│   │   └── IJCalculatorService.php
│   └── Form/
│       └── IJCalculationForm.php
├── tests/
│   ├── Fixture/
│   │   ├── mock.json
│   │   ├── mock2.json
│   │   ├── ...
│   │   └── mock12.json
│   └── TestCase/
│       └── Service/
│           └── IJCalculatorServiceTest.php
├── README_CAKEPHP.md
├── CAKEPHP5_MIGRATION.md
├── CAKEPHP5_STRUCTURE.md (ce fichier)
└── composer_requirements.txt
```

## Points clés de l'architecture

### 1. Séparation des responsabilités

- **Service Layer**: Logique métier pure (IJCalculatorService)
- **Controller**: Gestion HTTP et réponses
- **Model**: Persistance et validation
- **Form**: Validation des entrées utilisateur

### 2. Type Safety

Tous les fichiers utilisent:
- `declare(strict_types=1);`
- Type hints sur les paramètres
- Type hints sur les retours
- PHPDoc complet

### 3. Validation multi-niveaux

1. **Form validation** (IJCalculationForm): Validation initiale des données
2. **Entity validation** (ArretsTable, CalculationsTable): Validation ORM
3. **Service validation** (IJCalculatorService): Règles métier

### 4. Testabilité

- Service indépendant testable unitairement
- 12 scénarios de test complets
- Fixtures JSON pour reproductibilité
- Tests d'exception pour les cas limites

### 5. Extensibilité

- Configuration centralisée (IJ.*)
- Méthodes protégées pour override
- Événements CakePHP (via behaviors)
- Custom finders pour requêtes complexes

## Logique de calcul - Résumé

### Système de 27 taux

**9 numéros de taux** × **3 colonnes CSV** = **27 taux possibles**

#### Numéros de taux (1-9)
- Déterminés par: âge, trimestres, pathologie antérieure, période dans l'arrêt

#### Colonnes CSV (1-3)
1. **Colonne 1** (taux_X1): Taux plein
2. **Colonne 2** (taux_X2): Taux réduit senior (70+)
3. **Colonne 3** (taux_X3): Taux intermédiaire (périodes 2 et 3)

### Mapping taux → colonne

```php
// Taux 1-3 → colonne 1 (taux plein)
if ($taux >= 1 && $taux <= 3) {
    $tier = 1;
}

// Taux 7-9 → colonne 3 (taux intermédiaire pour période 2)
elseif ($taux >= 7 && $taux <= 9) {
    $tier = 3;
}

// Taux 4-6 → colonne 3 (62-69) ou colonne 2 (70+)
elseif ($taux >= 4 && $taux <= 6) {
    $tier = ($age >= 70) ? 2 : 3;
}
```

### Périodes pour 62-69 ans

- **Période 1** (jours 1-365): Taux 1-3 → colonne 1
- **Période 2** (jours 366-730): Taux 7-9 → colonne 3 (si arret_diff ≥ 730)
- **Période 3** (jours 731-1095): Taux 4-6 → colonne 3

### Age-Based Segmentation

Le calcul est effectué **par segment d'âge**:

```php
foreach ($yearlyBreakdown as $yearData) {
    $segmentAge = $this->calculateAge($yearData['start'], $birthDate);

    if ($segmentAge < 62) {
        // Logique < 62 ans
    } elseif ($segmentAge >= 62 && $segmentAge <= 69) {
        // Logique 62-69 ans avec périodes
    } else { // >= 70
        // Logique 70+ ans
    }
}
```

Ceci permet de gérer correctement les **transitions d'âge** pendant les périodes de paiement (exemple: mock9 avec transition à 70 ans).

## Prochaines étapes possibles

### Améliorations suggérées

1. **Interface utilisateur**
   - Templates CakePHP pour le formulaire
   - Dashboard de visualisation des calculs
   - Export PDF des résultats

2. **API REST complète**
   - CRUD complet pour Arrets
   - CRUD complet pour Calculations
   - Authentication/Authorization

3. **Gestion des taux**
   - Migration des taux CSV vers base de données
   - Interface d'administration des taux
   - Historisation des modifications

4. **Rapports**
   - Statistiques globales
   - Export Excel des calculs
   - Graphiques de tendances

5. **Intégration**
   - Import CSV d'arrêts de travail
   - API externe pour récupération automatique
   - Webhooks pour notifications

## Support et maintenance

### Documentation de référence

- **CakePHP 5**: https://book.cakephp.org/5/
- **Migrations**: https://book.cakephp.org/migrations/4/
- **PHPUnit**: https://phpunit.de/

### Fichiers de référence

- Logique métier: `src/Service/IJCalculatorService.php`
- Tests: `tests/TestCase/Service/IJCalculatorServiceTest.php`
- Documentation: `README_CAKEPHP.md`

### Commandes utiles

```bash
# Lancer les tests
vendor/bin/phpunit

# Créer une migration
bin/cake bake migration CreateNouvelleTable

# Migrer la base de données
bin/cake migrations migrate

# Code standards
vendor/bin/phpcs src/ tests/

# Générer une entité
bin/cake bake model NomTable

# Générer un controller
bin/cake bake controller NomController
```

## Conclusion

Cette structure CakePHP 5 complète fournit:

✅ **Service de calcul robuste** avec gestion des 27 taux
✅ **API REST** pour intégration
✅ **Persistance ORM** avec validation
✅ **Tests complets** (12 scénarios validés)
✅ **Documentation exhaustive**
✅ **Configuration flexible**
✅ **Type safety strict** (PHP 8.1+)
✅ **Architecture extensible**

Tous les fichiers suivent les conventions CakePHP 5 et les standards PSR-12.
