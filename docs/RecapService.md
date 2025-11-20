# RecapService - Documentation Complète

## Vue d'ensemble

`RecapService` transforme les résultats de calcul IJ en enregistrements pour la table `ij_recap` de la base de données. Il génère les enregistrements mensuels groupés par taux et produit les requêtes SQL d'insertion.

**Emplacement** : `Services/RecapService.php`

**Namespace** : `App\IJCalculator\Services`

**Lignes de code** : 151 lignes

## Purpose

Génère des enregistrements pour le stockage en base de données avec :
- Groupement par mois/année/taux
- Calcul des montants en centimes
- Génération SQL INSERT
- Support auto-détermination classe

## Méthodes Principales

### 1. generateRecapRecords()

Transforme les résultats de calcul en enregistrements `ij_recap`.

```php
public function generateRecapRecords(array $result, array $inputData): array
```

#### Paramètres

- **`$result`** : Résultat de `calculateTotalAmount()`
- **`$inputData`** : Données d'entrée originales avec :
  - `adherent_number` (obligatoire, 7 caractères)
  - `num_sinistre` (obligatoire, integer)
  - `classe` ou `revenu_n_moins_2`

#### Valeur de Retour

Array d'enregistrements avec structure :

```php
[
    [
        'adherent_number' => '1234567',
        'num_sinistre' => 8038,
        'annee' => 2023,
        'mois' => 12,
        'taux' => 1,
        'montant_centimes' => 450600,  // 4506.00€ en centimes
        'nb_jours' => 31,
        'classe' => 'B',
        'created' => '2024-01-15 10:30:00'
    ]
]
```

### 2. generateInsertSQL()

Génère une requête SQL INSERT pour un enregistrement.

```php
public function generateInsertSQL(array $record): string
```

### 3. generateBatchInsertSQL()

Génère une requête SQL INSERT pour plusieurs enregistrements.

```php
public function generateBatchInsertSQL(array $records): string
```

### 4. setCalculator()

Active l'auto-détermination de la classe.

```php
public function setCalculator(IJCalculator $calculator): void
```

## Exemples d'Utilisation

### Exemple 1 : Génération Basique

```php
use App\IJCalculator\IJCalculator;
use App\IJCalculator\Services\RecapService;

// Calcul IJ
$calculator = new IJCalculator(['taux.csv']);
$data = [
    'statut' => 'M',
    'classe' => 'B',
    'option' => 100,
    'birth_date' => '1960-01-15',
    'affiliation_date' => '2019-01-15',
    'adherent_number' => '1234567',  // Requis
    'num_sinistre' => 8038,          // Requis
    'arrets' => [/* ... */]
];

$result = $calculator->calculateTotalAmount($data);

// Générer records
$recapService = new RecapService();
$records = $recapService->generateRecapRecords($result, $data);

// Afficher
foreach ($records as $record) {
    echo "Mois {$record['mois']}/{$record['annee']} : ";
    echo "{$record['nb_jours']} jours, ";
    echo number_format($record['montant_centimes'] / 100, 2) . "€\n";
}
```

### Exemple 2 : Génération SQL

```php
$recapService = new RecapService();
$records = $recapService->generateRecapRecords($result, $data);

// SQL pour un seul record
$sql = $recapService->generateInsertSQL($records[0]);
echo $sql . ";\n";

// SQL batch pour tous les records
$batchSQL = $recapService->generateBatchInsertSQL($records);
echo $batchSQL . ";\n";
```

### Exemple 3 : Avec Auto-détermination Classe

```php
$calculator = new IJCalculator(['taux.csv']);
$recapService = new RecapService();

// Activer auto-détermination
$recapService->setCalculator($calculator);

$data = [
    'statut' => 'M',
    // 'classe' => 'B',  // Omis
    'revenu_n_moins_2' => 85000,  // Auto-détermine classe
    'pass_value' => 47000,
    'adherent_number' => '1234567',
    'num_sinistre' => 8038,
    'arrets' => [/* ... */]
];

$result = $calculator->calculateTotalAmount($data);
$records = $recapService->generateRecapRecords($result, $data);

// Classe sera 'B' automatiquement
echo "Classe déterminée : " . $records[0]['classe'];
```

### Exemple 4 : Validation

```php
$recapService = new RecapService();

// Valider avant insertion
foreach ($records as $record) {
    $validation = $recapService->validateRecord($record);
    if (!$validation['valid']) {
        echo "Erreur : " . implode(", ", $validation['errors']) . "\n";
    }
}
```

## Structure Table ij_recap

```sql
CREATE TABLE ij_recap (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adherent_number VARCHAR(7) NOT NULL,
    num_sinistre INT NOT NULL,
    annee INT NOT NULL,
    mois INT NOT NULL,
    taux INT NOT NULL,
    montant_centimes INT NOT NULL,
    nb_jours INT NOT NULL,
    classe VARCHAR(1),
    created DATETIME,
    INDEX idx_adherent (adherent_number),
    INDEX idx_sinistre (num_sinistre)
);
```

## Points Importants

1. **Groupement** : Un record par mois/taux
2. **Centimes** : Montants stockés en centimes (entiers)
3. **Validation** : Vérifie adherent_number et num_sinistre
4. **Auto-classe** : Utilise setCalculator() pour auto-détermination
5. **Timestamps** : created générée automatiquement

## Voir Aussi

- [DetailJourService](./DetailJourService.md) - Détail quotidien
- [IJCalculator](./IJCalculator.md) - Classe principale
