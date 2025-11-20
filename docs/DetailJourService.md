# DetailJourService - Documentation Complète

## Vue d'ensemble

`DetailJourService` transforme les détails quotidiens des calculs IJ en enregistrements pour la table `ij_detail_jour`. Il mappe chaque jour du mois aux colonnes j1-j31 avec les montants en centimes.

**Emplacement** : `Services/DetailJourService.php`

**Namespace** : `App\IJCalculator\Services`

**Lignes de code** : 114 lignes

## Purpose

Génère des enregistrements mensuels avec :
- Mapping des jours aux colonnes j1-j31
- Montants en centimes pour chaque jour
- Un enregistrement par mois
- Génération SQL INSERT

## Structure Table ij_detail_jour

```sql
CREATE TABLE ij_detail_jour (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adherent_number VARCHAR(7) NOT NULL,
    num_sinistre INT NOT NULL,
    annee INT NOT NULL,
    mois INT NOT NULL,
    j1 INT DEFAULT 0,
    j2 INT DEFAULT 0,
    j3 INT DEFAULT 0,
    -- ... j4 à j30 ...
    j31 INT DEFAULT 0,
    created DATETIME,
    INDEX idx_adherent (adherent_number),
    INDEX idx_sinistre (num_sinistre)
);
```

Chaque colonne `jN` contient le montant en centimes pour le jour N du mois.

## Méthodes Principales

### 1. generateDetailJourRecords()

Transforme le daily_breakdown en enregistrements `ij_detail_jour`.

```php
public function generateDetailJourRecords(array $result, array $inputData): array
```

#### Paramètres

- **`$result`** : Résultat de `calculateTotalAmount()` avec `daily_breakdown`
- **`$inputData`** : Données avec `adherent_number` et `num_sinistre`

#### Valeur de Retour

Array d'enregistrements avec structure :

```php
[
    [
        'adherent_number' => '1234567',
        'num_sinistre' => 8038,
        'annee' => 2023,
        'mois' => 12,
        'j1' => 7506,      // 75.06€ en centimes
        'j2' => 7506,
        'j3' => 7506,
        // ... j4 à j31 ...
        'created' => '2024-01-15 10:30:00'
    ]
]
```

### 2. generateInsertSQL()

Génère SQL INSERT pour un enregistrement.

```php
public function generateInsertSQL(array $record): string
```

### 3. generateBatchInsertSQL()

Génère SQL INSERT pour plusieurs enregistrements.

```php
public function generateBatchInsertSQL(array $records): string
```

## Exemples d'Utilisation

### Exemple 1 : Génération Basique

```php
use App\IJCalculator\IJCalculator;
use App\IJCalculator\Services\DetailJourService;

// Calcul IJ
$calculator = new IJCalculator(['taux.csv']);
$data = [
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1960-01-15',
    'adherent_number' => '1234567',
    'num_sinistre' => 8038,
    'arrets' => [
        [
            'arret-from-line' => '2023-12-01',
            'arret-to-line' => '2023-12-31'
        ]
    ]
];

$result = $calculator->calculateTotalAmount($data);

// Générer détails jours
$detailService = new DetailJourService();
$records = $detailService->generateDetailJourRecords($result, $data);

// Afficher
foreach ($records as $record) {
    echo "Mois {$record['mois']}/{$record['annee']}:\n";
    for ($day = 1; $day <= 31; $day++) {
        $key = "j$day";
        if ($record[$key] > 0) {
            $montant = $record[$key] / 100;
            echo "  Jour $day : " . number_format($montant, 2) . "€\n";
        }
    }
}
```

### Exemple 2 : Génération SQL

```php
$detailService = new DetailJourService();
$records = $detailService->generateDetailJourRecords($result, $data);

// SQL batch
$sql = $detailService->generateBatchInsertSQL($records);

// Exécuter ou sauvegarder
file_put_contents('insert_detail_jour.sql', $sql);
```

### Exemple 3 : Multi-mois

```php
$data = [
    'statut' => 'M',
    'classe' => 'B',
    'option' => 100,
    'birth_date' => '1960-01-15',
    'adherent_number' => '1234567',
    'num_sinistre' => 8038,
    'arrets' => [
        [
            'arret-from-line' => '2023-11-15',  // Sur 2 mois
            'arret-to-line' => '2024-01-15'
        ]
    ]
];

$result = $calculator->calculateTotalAmount($data);
$records = $detailService->generateDetailJourRecords($result, $data);

// Un record par mois
echo "Nombre de mois : " . count($records) . "\n";
// Résultat : 3 (novembre 2023, décembre 2023, janvier 2024)

foreach ($records as $record) {
    $total = 0;
    for ($day = 1; $day <= 31; $day++) {
        $total += $record["j$day"];
    }
    echo "{$record['mois']}/{$record['annee']} : " .
         number_format($total / 100, 2) . "€\n";
}
```

### Exemple 4 : Visualisation Calendrier

```php
$detailService = new DetailJourService();
$records = $detailService->generateDetailJourRecords($result, $data);

foreach ($records as $record) {
    echo "\n=== {$record['mois']}/{$record['annee']} ===\n";
    echo "Lu Ma Me Je Ve Sa Di\n";

    for ($day = 1; $day <= 31; $day++) {
        $montant = $record["j$day"] / 100;
        if ($montant > 0) {
            printf("%02d ", $day);
        } else {
            echo "-- ";
        }

        if ($day % 7 == 0) echo "\n";
    }
    echo "\n";
}
```

## Mapping Jours → Colonnes

Le service effectue le mapping automatique :

```
Date: 2023-12-01 → Colonne j1
Date: 2023-12-02 → Colonne j2
...
Date: 2023-12-31 → Colonne j31
```

Si un jour n'a pas de paiement, la colonne correspondante = 0.

## Points Importants

1. **Un record par mois** : Chaque mois = 1 ligne en base
2. **Colonnes j1-j31** : Mapping direct jour → colonne
3. **Centimes** : Montants en centimes (entiers)
4. **Zéros** : Jours sans paiement = 0
5. **Multi-mois** : Arrêt sur plusieurs mois = plusieurs records

## Utilisation Conjointe avec RecapService

```php
// Générer les deux types de records
$recapService = new RecapService();
$detailService = new DetailJourService();

$recapRecords = $recapService->generateRecapRecords($result, $data);
$detailRecords = $detailService->generateDetailJourRecords($result, $data);

// Générer SQL
$sqlRecap = $recapService->generateBatchInsertSQL($recapRecords);
$sqlDetail = $detailService->generateBatchInsertSQL($detailRecords);

// Enregistrer
file_put_contents('insert_all.sql', $sqlRecap . "\n\n" . $sqlDetail);
```

## Voir Aussi

- [RecapService](./RecapService.md) - Enregistrements mensuels groupés
- [IJCalculator](./IJCalculator.md) - Classe principale
- [AmountCalculationService](./AmountCalculationService.md) - Génère daily_breakdown
