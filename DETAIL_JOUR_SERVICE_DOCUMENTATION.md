# DetailJourService - Génération d'Enregistrements ij_detail_jour

## Date: 2025-11-06

## Vue d'ensemble

Le **DetailJourService** transforme les `daily_breakdown` du calculateur IJ en enregistrements compatibles avec la table SQL `ij_detail_jour`. Cette table stocke les montants journaliers (j1 à j31) pour chaque mois/période.

## Schéma de la Table `ij_detail_jour`

```sql
CREATE TABLE `ij_detail_jour` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `adherent_number` varchar(7) NOT NULL,
  `exercice` varchar(4) DEFAULT NULL,
  `periode` varchar(2) DEFAULT NULL,
  `num_sinistre` int(11) NOT NULL,
  `j1` int(11) DEFAULT NULL,
  `j2` int(11) DEFAULT NULL,
  `j3` int(11) DEFAULT NULL,
  ...
  `j31` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `adherent_number` (`adherent_number`),
  KEY `exercice` (`exercice`),
  KEY `periode` (`periode`),
  KEY `num_sinistre` (`num_sinistre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Mapping des Données

### Daily Breakdown → Table ij_detail_jour

| Champ ij_detail_jour | Source | Description |
|---------------------|--------|-------------|
| `adherent_number` | `inputData['adherent_number']` | Numéro adhérent (7 car.) |
| `exercice` | Extrait de la date | Année (2024, etc.) |
| `periode` | Extrait de la date | Mois (01-12) |
| `num_sinistre` | `inputData['num_sinistre']` | Numéro de sinistre |
| `j1` à `j31` | `daily_breakdown[n]['amount'] * 100` | Montant du jour en centimes |

**Logique de groupement:**
- Un enregistrement par mois (exercice + periode)
- Chaque jour du mois mappé à sa colonne correspondante (j1-j31)
- Jours sans paiement = NULL

## Utilisation du Service

### 1. Instanciation Basique

```php
<?php

require_once 'IJCalculator.php';
require_once 'Services/DetailJourService.php';

use IJCalculator\Services\DetailJourService;

// Calculer les IJ
$calculator = new IJCalculator('taux.csv');
$result = $calculator->calculateAmount($inputData);

// Générer les enregistrements de détail journalier
$detailJourService = new DetailJourService();
$detailJourRecords = $detailJourService->generateDetailJourRecords($result, $inputData);
```

### 2. Exemple Complet

```php
<?php

// Données d'entrée
$inputData = [
    'adherent_number' => '249296F',
    'num_sinistre' => 30,
    'statut' => 'M',
    'classe' => 'B',
    'option' => 100,
    'birth_date' => '1962-05-01',
    'current_date' => '2024-12-27',
    'attestation_date' => '2024-12-27',
    'affiliation_date' => null,
    'nb_trimestres' => 50,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000,
    'arrets' => [/* ... */]
];

// Calculer
$calculator = new IJCalculator('taux.csv');
$result = $calculator->calculateAmount($inputData);

// Générer detail_jour
$detailJourService = new DetailJourService();
$detailJourRecords = $detailJourService->generateDetailJourRecords($result, $inputData);

// Afficher les enregistrements
foreach ($detailJourRecords as $record) {
    echo "Exercice {$record['exercice']} - Mois {$record['periode']}\n";

    // Compter les jours payables
    $days = 0;
    $total = 0;
    for ($i = 1; $i <= 31; $i++) {
        if (isset($record["j$i"]) && $record["j$i"] !== null) {
            $days++;
            $total += $record["j$i"];
        }
    }

    echo "  - Jours: $days\n";
    echo "  - Total: " . ($total / 100) . "€\n\n";
}
```

### 3. Génération SQL

```php
// Générer SQL INSERT pour tous les enregistrements
$sql = $detailJourService->generateBatchInsertSQL($detailJourRecords);
echo $sql;

// Ou pour un seul enregistrement
foreach ($detailJourRecords as $record) {
    $insertSQL = $detailJourService->generateInsertSQL($record);
    echo $insertSQL . "\n";
}
```

### 4. Validation

```php
// Valider chaque enregistrement avant insertion
foreach ($detailJourRecords as $record) {
    $validation = $detailJourService->validateRecord($record);

    if ($validation['valid']) {
        echo "✓ Enregistrement valide\n";
    } else {
        echo "✗ Erreurs:\n";
        foreach ($validation['errors'] as $error) {
            echo "  - $error\n";
        }
    }
}
```

### 5. Statistiques

```php
// Obtenir des statistiques sur les enregistrements
$stats = $detailJourService->getStatistics($detailJourRecords);

echo "Total mois: " . $stats['total_months'] . "\n";
echo "Total jours: " . $stats['total_days'] . "\n";
echo "Montant total: " . number_format($stats['total_amount'], 2) . "€\n\n";

echo "Détail par mois:\n";
foreach ($stats['months'] as $month => $data) {
    echo "  $month: " . $data['days'] . " jours, ";
    echo number_format($data['amount'], 2) . "€\n";
}
```

### 6. Affichage HTML

```php
// Générer un tableau HTML
$html = $detailJourService->formatDetailJourHTML($detailJourRecords);
echo $html;

// Ou intégrer dans une page
file_put_contents('detail_jour_preview.html', "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Détail Journalier IJ</title>
    <style>
        table { border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #ddd; padding: 4px; }
    </style>
</head>
<body>
    <h1>Détail Journalier IJ</h1>
    $html
</body>
</html>
");
```

## Exemple de Sortie

### Enregistrement Généré (Mars 2024)

```php
Array
(
    [adherent_number] => 249296F
    [exercice] => 2024
    [periode] => 03
    [num_sinistre] => 30
    [j1] => NULL
    [j2] => NULL
    ...
    [j27] => NULL
    [j28] => 11259        // 112.59€ en centimes
    [j29] => 11259        // 112.59€
    [j30] => 11259        // 112.59€
    [j31] => 11259        // 112.59€
)
```

### SQL INSERT Généré

```sql
INSERT INTO ij_detail_jour (adherent_number, exercice, periode, num_sinistre, j1, j2, ..., j31)
VALUES ('249296F', '2024', '03', 30, NULL, NULL, ..., NULL, 11259, 11259, 11259, 11259);
```

## Cas d'Usage Typiques

### Cas 1: Un Mois Complet

**Entrée:**
- 1 arrêt de 31 jours (ex: janvier 2024)

**Sortie:**
- **1 enregistrement** avec j1 à j31 remplis

### Cas 2: Multiple Mois

**Entrée:**
- 1 arrêt de 90 jours (mars-mai 2024)

**Sortie:**
- **3 enregistrements**:
  - Mars: j1-j31 (4 jours payables après date d'effet)
  - Avril: j1-j30 (30 jours)
  - Mai: j1-j31 (31 jours)

### Cas 3: Changement d'Année

**Entrée:**
- 1 arrêt du 2023-12-15 au 2024-01-31

**Sortie:**
- **2 enregistrements**:
  - Exercice 2023, période 12: j15-j31
  - Exercice 2024, période 01: j1-j31

### Cas 4: Multiples Arrêts

**Entrée:**
- 2 arrêts en 2024 (mars-juin, septembre-décembre)

**Sortie:**
- **8 enregistrements** (un par mois)

## Méthodes du Service

### `generateDetailJourRecords(array $result, array $inputData): array`

Génère les enregistrements de détail journalier à partir des résultats de calcul.

**Paramètres:**
- `$result`: Résultat de `IJCalculator::calculateAmount()`
- `$inputData`: Données d'entrée originales

**Retour:**
- Array d'enregistrements prêts pour insertion (un par mois)

**Logique:**
1. Extrait les `daily_breakdown` de chaque arret
2. Groupe par année et mois
3. Mappe chaque jour à sa colonne j1-j31
4. Convertit les montants en centimes

### `generateInsertSQL(array $record): string`

Génère une instruction SQL INSERT pour un enregistrement.

**Paramètres:**
- `$record`: Enregistrement de détail journalier

**Retour:**
- String SQL INSERT

### `generateBatchInsertSQL(array $records): string`

Génère les instructions SQL INSERT pour tous les enregistrements.

**Paramètres:**
- `$records`: Array d'enregistrements

**Retour:**
- String avec toutes les instructions SQL

### `formatDetailJourHTML(array $records): string`

Génère un tableau HTML pour afficher les enregistrements.

**Paramètres:**
- `$records`: Array d'enregistrements

**Retour:**
- String HTML avec tableau scrollable

### `validateRecord(array $record): array`

Valide un enregistrement avant insertion.

**Paramètres:**
- `$record`: Enregistrement à valider

**Retour:**
```php
[
    'valid' => true/false,
    'errors' => ['error1', 'error2', ...]
]
```

**Validations:**
- adherent_number: 7 caractères requis
- exercice: 4 chiffres (année) requis
- periode: 1-12 (mois) requis
- Au moins un jour (j1-j31) doit avoir une valeur

### `getStatistics(array $records): array`

Calcule des statistiques sur les enregistrements.

**Paramètres:**
- `$records`: Array d'enregistrements

**Retour:**
```php
[
    'total_months' => 10,
    'total_days' => 279,
    'total_amount' => 31412.61,  // En euros
    'months' => [
        '2024-03' => ['days' => 4, 'amount' => 450.36],
        '2024-04' => ['days' => 30, 'amount' => 3377.70],
        // ...
    ]
]
```

## Format des Montants

### IMPORTANT: Conversion en Centimes

Les montants dans `ij_detail_jour` sont stockés en **centimes** (entiers):

```php
// Daily breakdown retourne: 112.59€ (float)
// Base de données stocke: 11259 (int, centimes)

$jN = (int) round($amount * 100);
```

**Exemples:**
- 112.59€ → 11259
- 75.06€ → 7506
- 0€ → NULL (pas 0, mais NULL)

### Reconversion pour Affichage

```php
$montantEuros = $record['j15'] / 100;
echo number_format($montantEuros, 2, ',', ' ') . '€';
// Affiche: 112,59€
```

## Tests

### Test avec Mock Data

Le test utilise les **vraies données de mock** (mock6.json par défaut).

```bash
php test_detail_jour_service.php
```

**Pour tester un autre mock:**
1. Éditer `test_detail_jour_service.php`
2. Changer `$mockFile = 'mock6.json'` à `'mock2.json'`, etc.
3. Relancer le test

**Sortie (mock6.json):**
```
=== Test DetailJourService ===

Loaded mock: mock6.json
Number of arrets: 2

Calcul effectué:
- Montant: 31,412.61€
- Jours: 279
- Âge: 63 ans

Daily breakdown available: 279 days

=== Enregistrements de Détail Journalier Générés ===

Nombre d'enregistrements (mois): 10

Enregistrement #1:
  - Adhérent: 249296F
  - Exercice: 2024
  - Période (mois): 03
  - Jours payables: 4
  - Total du mois: 450.36€
  - Exemples de jours:
    J28: 112.59€
    J29: 112.59€
    J30: 112.59€
    J31: 112.59€
  - Validation: ✓ OK

=== Statistiques ===

Total mois: 10
Total jours: 279
Montant total: 31,412.61€

Détail par mois:
  2024-03: 4 jours, 450.36€
  2024-04: 30 jours, 3,377.70€
  2024-05: 31 jours, 3,490.29€
  2024-06: 30 jours, 3,377.70€
  2024-07: 31 jours, 3,490.29€
  2024-08: 31 jours, 3,490.29€
  2024-09: 30 jours, 3,377.70€
  2024-10: 31 jours, 3,490.29€
  2024-11: 30 jours, 3,377.70€
  2024-12: 31 jours, 3,490.29€
```

**Scénarios testés:**
- ✅ **mock6.json**: 2 arrets, 279 jours, 10 mois, 31,412.61€
- ✅ **mock2.json**: 6 arrets, multiple mois
- ✅ **mock3.json**: 1 long arret, 374 jours

**Avantages:**
- Utilise des données réelles validées
- Vérifie le mapping jour par jour
- Tests avec différentes durées et années
- Génère SQL prêt pour insertion

### Vérification HTML

Ouvrir `test_detail_jour_preview.html` dans un navigateur pour voir le rendu du tableau avec tous les jours (j1-j31).

## Intégration avec l'API

### Nouvel Endpoint: `generate-detail-jour`

Ajouter dans `api.php`:

```php
case 'generate-detail-jour':
    if ($method !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // Calculate
    $result = $calculator->calculateAmount($input);

    // Generate detail_jour
    require_once 'Services/DetailJourService.php';
    $detailJourService = new IJCalculator\Services\DetailJourService();
    $detailJourRecords = $detailJourService->generateDetailJourRecords($result, $input);

    // Get statistics
    $stats = $detailJourService->getStatistics($detailJourRecords);

    echo json_encode([
        'success' => true,
        'data' => [
            'calculation' => $result,
            'detail_jour_records' => $detailJourRecords,
            'statistics' => $stats,
            'sql' => $detailJourService->generateBatchInsertSQL($detailJourRecords)
        ]
    ]);
    break;
```

### Appel depuis le Frontend

```javascript
async function generateDetailJour() {
    const data = collectFormData();

    const response = await fetch(`${API_URL}?endpoint=generate-detail-jour`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    const result = await response.json();

    if (result.success) {
        console.log('Mois:', result.data.statistics.total_months);
        console.log('Jours:', result.data.statistics.total_days);
        console.log('Montant:', result.data.statistics.total_amount);
        console.log('SQL:', result.data.sql);
        // Afficher dans l'interface
    }
}
```

## Différences avec RecapService

| Aspect | RecapService | DetailJourService |
|--------|-------------|------------------|
| **Granularité** | Par période/taux | Par jour |
| **Structure** | Un enregistrement par période/taux | Un enregistrement par mois |
| **Colonnes** | MT_journalier, num_taux, date_start/end | j1 à j31 |
| **Usage** | Récapitulatif comptable | Détail jour par jour |
| **Taille** | Compact (4 enregistrements) | Détaillé (10 enregistrements) |
| **Source** | rate_breakdown | daily_breakdown |

**Recommandation:** Utiliser les deux services ensemble pour avoir à la fois le récapitulatif (recap) et le détail quotidien (detail_jour).

## Avantages du Service

✅ **Détail jour par jour**: Chaque jour est tracé individuellement
✅ **Regroupement mensuel**: Organisation claire par mois
✅ **Séparation des préoccupations**: Logique isolée
✅ **Réutilisable**: API, CLI, tests
✅ **Validé**: Validation intégrée
✅ **Statistiques**: Analyse automatique
✅ **Flexible**: SQL, HTML, ou objets PHP
✅ **Testé**: Tests unitaires avec mocks

## Notes Importantes

### Champs Requis

Pour générer des enregistrements valides, l'`inputData` doit contenir:
- `adherent_number` (7 caractères)
- `num_sinistre` (entier)

### Daily Breakdown Requis

Ce service nécessite que le calculateur génère le `daily_breakdown`. Si absent, aucun enregistrement ne sera généré.

### Structure du Daily Breakdown

```php
'daily_breakdown' => [
    [
        'date' => '2024-03-28',
        'amount' => 112.59,
        'day_type' => 'payable',
        'taux' => 1
    ],
    // ...
]
```

### Null vs 0

- Jours sans paiement: **NULL** (pas 0)
- Permet de distinguer "pas de paiement" de "paiement de 0€"

### Performance

- Un enregistrement par mois (exercice + periode)
- Pour 1 an = max 12 enregistrements
- Pour 3 ans = max 36 enregistrements

## Évolutions Futures

1. **Insertion directe en base**: Méthode `insertRecords($connection, $records)`
2. **Support transactions**: Rollback en cas d'erreur
3. **Batch optimisé**: INSERT multiple VALUES
4. **Export CSV**: Format jour par jour
5. **Graphiques**: Visualisation des montants quotidiens
6. **Agrégation**: Sommes hebdomadaires, mensuelles

---

**Fichier**: `Services/DetailJourService.php`
**Test**: `test_detail_jour_service.php`
**Auteur**: Claude Code
**Date**: 2025-11-06
**Statut**: ✅ Production Ready
