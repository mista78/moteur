# RecapService - Génération d'Enregistrements ij_recap

## Date: 2025-11-06

## Vue d'ensemble

Le **RecapService** transforme les résultats du calculateur IJ en enregistrements compatibles avec la table SQL `ij_recap`. Cette table stocke un récapitulatif détaillé par période/taux pour chaque arrêt de travail.

## Schéma de la Table `ij_recap`

```sql
CREATE TABLE `ij_recap` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `adherent_number` varchar(7) NOT NULL,
  `exercice` varchar(4) NOT NULL,
  `periode` varchar(2) DEFAULT NULL,
  `num_sinistre` int(11) NOT NULL,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `id_arret` int(11) unsigned DEFAULT NULL,
  `num_taux` tinyint(3) unsigned NOT NULL,
  `MT_journalier` int(11) DEFAULT NULL,
  `id_revenu` int(11) DEFAULT NULL,
  `MT_revenu_ref` int(11) DEFAULT NULL,
  `classe` varchar(1) DEFAULT NULL,
  `personne_age` tinyint(3) unsigned DEFAULT NULL,
  `nb_trimestre` int(11) DEFAULT NULL,
  `date_de_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_de_dern_maj` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `adherent_number` (`adherent_number`),
  KEY `exercice` (`exercice`),
  KEY `periode` (`periode`),
  KEY `num_sinistre` (`num_sinistre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## Mapping des Données

### Données du Calculateur → Table ij_recap

| Champ ij_recap | Source | Description |
|----------------|--------|-------------|
| `adherent_number` | `inputData['adherent_number']` | Numéro adhérent (7 car.) |
| `exercice` | Extrait de la date | Année (2023, 2024, etc.) |
| `periode` | Extrait de la date | Mois (01-12) |
| `num_sinistre` | `inputData['num_sinistre']` | Numéro de sinistre |
| `date_start` | Calculé par mois | Date de début du mois |
| `date_end` | Calculé par mois | Date de fin du mois |
| `id_arret` | `detail['id']` ou `arret_index` | ID de l'arrêt |
| `num_taux` | `rate_breakdown['taux']` | Numéro de taux (1-9) |
| `MT_journalier` | `rate_breakdown['rate'] * 100` | Montant en centimes |
| `MT_revenu_ref` | Calculé selon classe | Revenu de référence |
| `classe` | `inputData['classe']` | Classe A/B/C |
| `personne_age` | `result['age']` | Âge de la personne |
| `nb_trimestre` | `result['nb_trimestres']` | Nombre de trimestres |

## Utilisation du Service

### 1. Instanciation Basique (avec détermination automatique de classe)

```php
<?php

require_once 'IJCalculator.php';
require_once 'Services/RecapService.php';

use IJCalculator\Services\RecapService;

// Calculer les IJ
$calculator = new IJCalculator('taux.csv');
$calculator->setPassValue(47000);

// Données avec revenu (pas besoin de spécifier la classe)
$inputData['revenu_n_moins_2'] = 94000;  // La classe sera déterminée automatiquement

$result = $calculator->calculateAmount($inputData);

// Générer les enregistrements de recap avec détermination de classe
$recapService = new RecapService();
$recapService->setCalculator($calculator);  // Activer l'auto-détermination
$recapRecords = $recapService->generateRecapRecords($result, $inputData);
```

**Note**: Appelez `setCalculator()` pour activer la détermination automatique de classe depuis `revenu_n_moins_2`.

### 2. Exemple Complet

```php
<?php

// Données d'entrée
$inputData = [
    'adherent_number' => '301261U',
    'num_sinistre' => 48,
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1960-01-15',
    'current_date' => '2024-01-15',
    'attestation_date' => '2024-01-15',
    'affiliation_date' => '2019-01-15',
    'nb_trimestres' => 22,
    'previous_cumul_days' => 0,
    'patho_anterior' => false,
    'prorata' => 1,
    'pass_value' => 47000,
    'arrets' => [
        [
            'arret-from-line' => '2023-09-04',
            'arret-to-line' => '2024-01-10',
            'rechute-line' => 0,
            'dt-line' => 0,
            'gpm-member-line' => 0,
            'declaration-date-line' => '2023-09-19',
            'id' => 1
        ]
    ]
];

// Calculer
$calculator = new IJCalculator('taux.csv');
$result = $calculator->calculateAmount($inputData);

// Générer recap
$recapService = new RecapService();
$recapRecords = $recapService->generateRecapRecords($result, $inputData);

// Afficher les enregistrements
foreach ($recapRecords as $record) {
    echo "Exercice {$record['exercice']} - Période {$record['periode']}\n";
    echo "  Taux: {$record['num_taux']}\n";
    echo "  MT journalier: " . ($record['MT_journalier'] / 100) . "€\n";
    echo "  Dates: {$record['date_start']} → {$record['date_end']}\n\n";
}
```

### 3. Génération SQL

```php
// Générer SQL INSERT pour tous les enregistrements
$sql = $recapService->generateBatchInsertSQL($recapRecords);
echo $sql;

// Ou pour un seul enregistrement
foreach ($recapRecords as $record) {
    $insertSQL = $recapService->generateInsertSQL($record);
    echo $insertSQL . "\n";
}
```

### 4. Validation

```php
// Valider chaque enregistrement avant insertion
foreach ($recapRecords as $record) {
    $validation = $recapService->validateRecord($record);

    if ($validation['valid']) {
        // Insérer dans la base
        echo "✓ Enregistrement valide\n";
    } else {
        // Afficher les erreurs
        echo "✗ Erreurs:\n";
        foreach ($validation['errors'] as $error) {
            echo "  - $error\n";
        }
    }
}
```

### 5. Affichage HTML

```php
// Générer un tableau HTML
$html = $recapService->formatRecapHTML($recapRecords);
echo $html;

// Ou intégrer dans une page
file_put_contents('recap_preview.html', "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Récapitulatif IJ</title>
</head>
<body>
    <h1>Récapitulatif IJ</h1>
    $html
</body>
</html>
");
```

## Exemple de Sortie

### Enregistrement Généré

```php
Array
(
    [adherent_number] => 301261U
    [exercice] => 2024
    [periode] => 1
    [num_sinistre] => 48
    [date_start] => 2024-01-01
    [date_end] => 2024-01-10
    [id_arret] => 1
    [num_taux] => 1
    [MT_journalier] => 7506        // 75.06€ en centimes
    [MT_revenu_ref] => 47000       // Revenu de référence
    [classe] => A
    [personne_age] => 64
    [nb_trimestre] => 22
    [_nb_jours] => 10              // Métadonnée (non insérée)
    [_montant_total] => 75060      // Métadonnée (non insérée)
)
```

### SQL INSERT Généré

```sql
INSERT INTO ij_recap (adherent_number, exercice, periode, num_sinistre, date_start, date_end, id_arret, num_taux, MT_journalier, MT_revenu_ref, classe, personne_age, nb_trimestre)
VALUES ('301261U', '2024', '1', 48, '2024-01-01', '2024-01-10', 1, 1, 7506, 47000, 'A', 64, 22);
```

## Cas d'Usage Typiques

### Cas 1: Un Seul Arrêt, Une Seule Période

**Entrée:**
- 1 arrêt de 100 jours
- Âge < 62 ans (taux unique)
- Même année (2024)

**Sortie:**
- **1 enregistrement** dans ij_recap

### Cas 2: Un Arrêt, Multiple Années

**Entrée:**
- 1 arrêt du 2023-09-01 au 2024-01-31
- Âge < 62 ans

**Sortie:**
- **2 enregistrements** dans ij_recap:
  - Exercice 2023: 2023-12-03 → 2023-12-31
  - Exercice 2024: 2024-01-01 → 2024-01-31

### Cas 3: Un Arrêt, Transition d'Âge (62-69 ans)

**Entrée:**
- 1 arrêt de 400 jours
- Âge 62-69 ans (3 périodes)

**Sortie:**
- **Jusqu'à 3 enregistrements**:
  - Période 1 (jours 1-365): Taux 1
  - Période 2 (jours 366-400): Taux 7

### Cas 4: Multiples Arrêts avec Rechute

**Entrée:**
- Arrêt 1: 100 jours
- Arrêt 2 (rechute): 50 jours

**Sortie:**
- **2+ enregistrements** (un pour chaque arrêt/période)

## Méthodes du Service

### `generateRecapRecords(array $result, array $inputData): array`

Génère les enregistrements de récapitulatif à partir des résultats de calcul.

**Paramètres:**
- `$result`: Résultat de `IJCalculator::calculateAmount()`
- `$inputData`: Données d'entrée originales

**Retour:**
- Array d'enregistrements prêts pour insertion

### `generateInsertSQL(array $record): string`

Génère une instruction SQL INSERT pour un enregistrement.

**Paramètres:**
- `$record`: Enregistrement de récapitulatif

**Retour:**
- String SQL INSERT

### `generateBatchInsertSQL(array $records): string`

Génère les instructions SQL INSERT pour tous les enregistrements.

**Paramètres:**
- `$records`: Array d'enregistrements

**Retour:**
- String avec toutes les instructions SQL

### `formatRecapHTML(array $records): string`

Génère un tableau HTML pour afficher les enregistrements.

**Paramètres:**
- `$records`: Array d'enregistrements

**Retour:**
- String HTML

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

## Format des Montants

### IMPORTANT: Conversion en Centimes

Les montants dans `ij_recap` sont stockés en **centimes** (entiers):

```php
// Calculateur retourne: 75.06€ (float)
// Base de données stocke: 7506 (int, centimes)

$MT_journalier = (int) round($rate * 100);
```

**Exemples:**
- 75.06€ → 7506
- 112.59€ → 11259
- 38.30€ → 3830

### Reconversion pour Affichage

```php
$montantEuros = $record['MT_journalier'] / 100;
echo number_format($montantEuros, 2, ',', ' ') . '€';
// Affiche: 75,06€
```

## Champs Calculés

### `MT_revenu_ref`

Revenu de référence calculé selon la classe:

```php
if (classe === 'A') {
    MT_revenu_ref = pass_value;  // 47 000€
}
elseif (classe === 'C') {
    MT_revenu_ref = pass_value * 3;  // 141 000€
}
elseif (classe === 'B') {
    MT_revenu_ref = revenu_n_moins_2;  // Revenu réel
}
```

## Tests

### Test avec Mock Data

Le test utilise les **vraies données de mock** (mock2.json par défaut) pour tester le RecapService.

```bash
php test_recap_service.php
```

**Pour tester un autre mock:**
1. Éditer `test_recap_service.php`
2. Changer `$mockFile = 'mock2.json'` à `'mock3.json'`, etc.
3. Relancer le test

**Sortie (mock2.json):**
```
=== Test RecapService ===

Loaded mock: mock2.json
Number of arrets: 6

Calcul effectué:
- Montant calculé: 17,318.92€
- Montant attendu: 17,318.92€
- Match: ✓ OK
- Jours: 116
- Âge: 67 ans

=== Enregistrements de Récapitulatif Générés ===

Nombre d'enregistrements: 4

Enregistrement #1:
  - Adhérent: 191566V
  - Exercice: 2023
  - Période: 1
  - Dates: 2023-12-07 → 2023-12-31
  - Taux: 1
  - MT journalier: 146.32€
  - Jours: 25
  - Classe: C
  - Âge: 67
  - Trimestres: 139
  - Validation: ✓ OK

Enregistrement #2:
  - Adhérent: 191566V
  - Exercice: 2024
  - Période: 1
  - Dates: 2024-01-01 → 2024-01-31
  - Taux: 1
  - MT journalier: 150.12€
  - Jours: 31
  - Classe: C
  - Âge: 67
  - Trimestres: 139
  - Validation: ✓ OK
```

**Scénarios testés:**
- ✅ **mock2.json**: 6 arrets, multiple rechute, 17,318.92€, classe C
- ✅ **mock3.json**: 1 arret long, 374 jours, 41,832.60€, classe B
- ✅ **mock.json**: Arrêt simple, 750.60€, classe A

**Avantages:**
- Utilise des données réelles validées
- Compare automatiquement avec valeurs attendues
- Tests avec différents scénarios (classe, âge, durée)
- Génère SQL prêt pour insertion

### Vérification HTML

Ouvrir `test_recap_preview.html` dans un navigateur pour voir le rendu du tableau.

## Intégration avec l'API

### Nouvel Endpoint: `generate-recap`

Ajouter dans `api.php`:

```php
case 'generate-recap':
    if ($method !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // Calculate
    $result = $calculator->calculateAmount($input);

    // Generate recap
    require_once 'Services/RecapService.php';
    $recapService = new IJCalculator\Services\RecapService();
    $recapRecords = $recapService->generateRecapRecords($result, $input);

    echo json_encode([
        'success' => true,
        'data' => [
            'calculation' => $result,
            'recap_records' => $recapRecords,
            'sql' => $recapService->generateBatchInsertSQL($recapRecords)
        ]
    ]);
    break;
```

### Appel depuis le Frontend

```javascript
async function generateRecap() {
    const data = collectFormData();

    const response = await fetch(`${API_URL}?endpoint=generate-recap`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    const result = await response.json();

    if (result.success) {
        console.log('Recap records:', result.data.recap_records);
        console.log('SQL:', result.data.sql);
        // Afficher dans l'interface
    }
}
```

## Avantages du Service

✅ **Séparation des préoccupations**: Logique de mapping isolée
✅ **Réutilisable**: Utilisable depuis API, CLI, tests
✅ **Validé**: Validation intégrée avant insertion
✅ **Flexible**: Génère SQL, HTML, ou objets PHP
✅ **Testé**: Tests unitaires inclus
✅ **Documenté**: Code commenté et exemples fournis
✅ **Détermination automatique de classe**: Auto-détermine la classe depuis revenu_n_moins_2 (nouveau!)

## Notes Importantes

### Champs Requis

Pour générer des enregistrements valides, l'`inputData` doit contenir:
- `adherent_number` (7 caractères)
- `num_sinistre` (entier)
- `classe` (A/B/C)

### Métadonnées

Les champs préfixés par `_` sont des métadonnées utiles mais non insérées:
- `_nb_jours`: Nombre de jours de la période
- `_montant_total`: Montant total de la période

### Timestamps

Les champs `date_de_creation` et `date_de_dern_maj` sont gérés automatiquement par MySQL (DEFAULT current_timestamp()).

## Détermination Automatique de Classe (Nouveau!)

Le RecapService peut maintenant **auto-déterminer la classe** (A/B/C) depuis `revenu_n_moins_2`:

```php
// Injecter le calculator pour activer l'auto-détermination
$recapService = new RecapService();
$recapService->setCalculator($calculator);

// Si revenu_n_moins_2 fourni, la classe est déterminée automatiquement
$inputData = [
    'revenu_n_moins_2' => 94000,  // 2 PASS = Classe B
    'pass_value' => 47000
];

$records = $recapService->generateRecapRecords($result, $inputData);
// Tous les records auront classe = 'B'
```

**Documentation complète**: Voir `RECAP_CLASS_DETERMINATION.md`

## Évolutions Futures

1. **Insertion directe en base**: Méthode `insertRecords($connection, $records)`
2. **Support transactions**: Rollback en cas d'erreur
3. **Batch optimisé**: INSERT multiple en une seule requête
4. **Export CSV**: Générer des fichiers CSV pour import
5. **Historique**: Tracker les modifications d'enregistrements

---

**Fichier**: `Services/RecapService.php`
**Test**: `test_recap_service.php`
**Auteur**: Claude Code
**Date**: 2025-11-06
**Statut**: ✅ Production Ready
