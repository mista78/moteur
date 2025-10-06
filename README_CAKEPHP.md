# CakePHP 5 - Indemnités Journalières

Application CakePHP 5 pour le calcul des indemnités journalières des professionnels de santé.

## Installation

### Prérequis

- PHP 8.1 ou supérieur
- Composer
- MySQL 5.7+ ou PostgreSQL 9.6+
- Extension PHP: intl, mbstring, pdo

### Étapes d'installation

1. **Installer les dépendances**

```bash
composer install
```

2. **Configuration de la base de données**

Copier le fichier de configuration:

```bash
cp config/app_local.example.php config/app_local.php
```

Éditer `config/app_local.php` et configurer les paramètres de connexion à la base de données dans la section `Datasources`.

3. **Générer une clé de sécurité**

```bash
bin/cake security generate_salt
```

Copier la valeur générée dans `config/app_local.php` sous `Security.salt`.

4. **Créer les tables de base de données**

```bash
bin/cake migrations migrate
```

5. **Copier le fichier de taux**

Assurez-vous que le fichier `config/taux.csv` existe avec les taux d'indemnités journalières.

## Structure du projet

```
src/
├── Controller/
│   └── IndemniteJournaliereController.php    # Contrôleur principal
├── Model/
│   ├── Entity/
│   │   ├── Arret.php                          # Entité arrêt de travail
│   │   └── Calculation.php                    # Entité calcul sauvegardé
│   └── Table/
│       ├── ArretsTable.php                    # Table des arrêts
│       └── CalculationsTable.php              # Table des calculs
├── Service/
│   └── IJCalculatorService.php                # Service de calcul IJ
└── Form/
    └── IJCalculationForm.php                  # Formulaire de validation

config/
├── Migrations/                                # Migrations de base de données
├── taux.csv                                   # Fichier de taux
└── app_local.php                              # Configuration locale

tests/
├── Fixture/                                   # Données de test (mock*.json)
└── TestCase/
    └── Service/
        └── IJCalculatorServiceTest.php        # Tests du service
```

## Utilisation

### API

#### Calculer une indemnité journalière

**Endpoint:** `POST /indemnite-journaliere/api-calculate.json`

**Body (JSON):**

```json
{
  "arrets": [
    {
      "arret-from-line": "2024-01-15",
      "arret-to-line": "2024-01-20",
      "arret_diff": 6,
      "attestation-date-line": "2024-01-15",
      "declaration-date-line": "2024-01-15",
      "rechute-line": 0,
      "option": 100,
      "date-effet": "2024-01-15",
      "code_pathologie": "M10",
      "adherent_number": "123456",
      "date_naissance": "1989-09-26"
    }
  ],
  "statut": "M",
  "classe": "A",
  "option": 100,
  "birth_date": "1989-09-26",
  "current_date": "2024-09-09",
  "attestation_date": "2024-01-15",
  "nb_trimestres": 8,
  "previous_cumul_days": 0,
  "prorata": 1,
  "patho_anterior": false
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "montant": 750.60,
    "nb_jours": 5,
    "age": 34,
    "total_cumul_days": 5,
    "taux_detail": "..."
  }
}
```

### Interface Web

Accédez à `/indemnite-journaliere` pour utiliser le formulaire de calcul.

## Service de calcul

Le service `IJCalculatorService` peut être utilisé dans n'importe quel controller ou commande:

```php
use App\Service\IJCalculatorService;

// Dans un controller
$calculator = new IJCalculatorService(CONFIG . 'taux.csv');

$result = $calculator->calculateAmount([
    'arrets' => $arrets,
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1989-09-26',
    'current_date' => '2024-09-09',
    'attestation_date' => '2024-01-31',
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => false,
]);

// Résultat
echo $result['montant'];  // 750.60
echo $result['nb_jours']; // 5
```

## Modèles de données

### Arret (Arrêt de travail)

```php
$arret = $this->Arrets->newEntity([
    'arret_from' => '2024-01-15',
    'arret_to' => '2024-01-20',
    'arret_diff' => 6,
    'attestation_date' => '2024-01-15',
    'declaration_date' => '2024-01-15',
    'rechute' => 0,
    'option' => 100,
    'code_pathologie' => 'M10',
    'adherent_number' => '123456',
    'birth_date' => '1989-09-26',
]);

$this->Arrets->save($arret);
```

### Calculation (Calcul sauvegardé)

```php
$calculation = $this->Calculations->newEntity([
    'adherent_number' => '123456',
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1989-09-26',
    'nb_jours' => 5,
    'montant' => 750.60,
    'age' => 34,
    'total_cumul_days' => 5,
    'nb_trimestres' => 8,
    'patho_anterior' => false,
    'calculation_data' => json_encode($result),
    'input_data' => json_encode($data),
    'calculated_at' => new DateTime(),
]);

$this->Calculations->save($calculation);
```

## Tests

Exécuter tous les tests:

```bash
vendor/bin/phpunit
```

Exécuter uniquement les tests du service IJCalculator:

```bash
vendor/bin/phpunit tests/TestCase/Service/IJCalculatorServiceTest.php
```

Les tests valident les 12 scénarios de calcul (mock.json à mock12.json).

## Configuration des taux

Le fichier `config/taux.csv` contient les taux d'indemnités journalières avec la structure suivante:

```csv
date_start,date_end,taux_a1,taux_a2,taux_a3,taux_b1,taux_b2,taux_b3,taux_c1,taux_c2,taux_c3
2022-01-01,2022-12-31,146.49,103.15,56.30,118.86,83.20,45.50,91.23,64.75,34.70
2023-01-01,2023-12-31,150.12,105.18,57.50,121.75,85.00,46.70,93.50,66.30,35.50
...
```

- `taux_a1`, `taux_b1`, `taux_c1`: Taux plein (classes A, B, C)
- `taux_a2`, `taux_b2`, `taux_c2`: Taux réduit senior (70+)
- `taux_a3`, `taux_b3`, `taux_c3`: Taux intermédiaire (périodes 2 et 3)

## Logique de calcul

### Système de 27 taux

Le système utilise 27 taux différents basés sur:

- **9 numéros de taux (1-9)** selon l'âge et la période
- **3 colonnes CSV** (1=plein, 2=senior, 3=intermédiaire)
- **3 classes** (A, B, C)

### Règles par âge

#### Moins de 62 ans
- Taux 1-3 selon les trimestres et pathologie antérieure
- Utilise la colonne 1 (taux plein)

#### 62-69 ans
- **Période 1 (jours 1-365)**: Taux 1-3 → colonne 1
- **Période 2 (jours 366-730)**: Taux 7-9 → colonne 3 (si arret_diff ≥ 730)
- **Période 3 (jours 731-1095)**: Taux 4-6 → colonne 3

#### 70 ans et plus
- Taux 4-6 selon les trimestres
- Utilise la colonne 2 (taux réduit senior)
- Maximum 365 jours par affiliation

### Seuils importants

- **90 jours**: Seuil de démarrage des droits
- **8 trimestres**: Minimum requis pour les indemnités
- **1095 jours**: Maximum total (3 ans)
- **365 jours**: Maximum annuel pour les 70+

## Développement

### Ajouter un nouveau champ de calcul

1. Ajouter la colonne dans la migration
2. Mettre à jour l'entité `Calculation`
3. Mettre à jour la validation dans `CalculationsTable`
4. Adapter le service `IJCalculatorService` si nécessaire

### Ajouter un nouveau statut professionnel

1. Ajouter le statut dans `config/app_local.php` sous `IJ.availableStatuts`
2. Créer une nouvelle méthode de calcul de taux si nécessaire
3. Mettre à jour la validation dans `CalculationsTable` et `IJCalculationForm`

## Dépannage

### Les tests échouent

- Vérifier que le fichier `config/taux.csv` existe
- Vérifier que les fichiers mock*.json sont dans `tests/Fixture/`
- Vérifier la configuration de la base de données de test

### Erreur de calcul

- Activer le mode debug dans `config/app_local.php`
- Consulter les logs dans `logs/error.log`
- Vérifier les paramètres d'entrée avec le formulaire de validation

### Base de données

- Vérifier la connexion: `bin/cake database status`
- Re-migrer: `bin/cake migrations rollback && bin/cake migrations migrate`

## Licence

Propriétaire - Tous droits réservés
