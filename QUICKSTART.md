# Quick Start Guide - CakePHP 5 IJ Calculator

## Installation rapide (5 minutes)

### 1. Prérequis

- PHP 8.1+
- Composer
- MySQL/PostgreSQL

### 2. Créer un nouveau projet CakePHP 5

```bash
composer create-project --prefer-dist cakephp/app:~5.0 ij-calculator
cd ij-calculator
```

### 3. Copier les fichiers créés

Copiez tous les fichiers de ce projet vers votre nouveau projet CakePHP:

```bash
# Depuis le répertoire /home/mista/work/ij/

# Service
cp src/Service/IJCalculatorService.php <votre-projet>/src/Service/

# Controller
cp src/Controller/IndemniteJournaliereController.php <votre-projet>/src/Controller/

# Models
cp src/Model/Entity/Arret.php <votre-projet>/src/Model/Entity/
cp src/Model/Entity/Calculation.php <votre-projet>/src/Model/Entity/
cp src/Model/Table/ArretsTable.php <votre-projet>/src/Model/Table/
cp src/Model/Table/CalculationsTable.php <votre-projet>/src/Model/Table/

# Forms
cp src/Form/IJCalculationForm.php <votre-projet>/src/Form/

# Migrations
cp config/Migrations/*.php <votre-projet>/config/Migrations/

# Tests
cp -r tests/TestCase/Service/ <votre-projet>/tests/TestCase/
cp -r tests/Fixture/*.json <votre-projet>/tests/Fixture/

# Configuration et données
cp config/taux.csv <votre-projet>/config/
cp config/app_local.example.php <votre-projet>/config/
```

### 4. Configurer la base de données

```bash
# Copier le fichier de configuration
cp config/app_local.example.php config/app_local.php

# Éditer config/app_local.php et configurer:
# - Security.salt (générer avec: bin/cake security generate_salt)
# - Datasources.default (connexion MySQL/PostgreSQL)
```

Exemple de configuration MySQL:

```php
'Datasources' => [
    'default' => [
        'host' => 'localhost',
        'username' => 'root',
        'password' => 'your_password',
        'database' => 'ij_calculator',
        'url' => env('DATABASE_URL', null),
    ],
],
```

### 5. Installer les dépendances

```bash
composer require cakephp/migrations:^4.0
composer require --dev phpunit/phpunit:^10.0
```

### 6. Créer la base de données

```bash
# Créer la base
mysql -u root -p -e "CREATE DATABASE ij_calculator CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Ou pour PostgreSQL
psql -U postgres -c "CREATE DATABASE ij_calculator ENCODING 'UTF8';"
```

### 7. Exécuter les migrations

```bash
bin/cake migrations migrate
```

Vous devriez voir:

```
== 20241006000001 CreateArrets: migrating
== 20241006000001 CreateArrets: migrated (0.0234s)

== 20241006000002 CreateCalculations: migrating
== 20241006000002 CreateCalculations: migrated (0.0189s)
```

### 8. Vérifier l'installation avec les tests

```bash
vendor/bin/phpunit tests/TestCase/Service/IJCalculatorServiceTest.php
```

Vous devriez voir **14 tests passés** (12 mocks + 2 tests unitaires).

## Utilisation

### Test rapide - API

Créez un fichier `test_api.php`:

```php
<?php
require 'vendor/autoload.php';

use App\Service\IJCalculatorService;

$calculator = new IJCalculatorService(CONFIG . 'taux.csv');

$result = $calculator->calculateAmount([
    'arrets' => [
        [
            'arret-from-line' => '2024-01-15',
            'arret-to-line' => '2024-01-20',
            'arret_diff' => 6,
            'attestation-date-line' => '2024-01-15',
            'declaration-date-line' => '2024-01-15',
            'rechute-line' => 0,
            'option' => 100,
            'date-effet' => '2024-01-15',
            'code_pathologie' => 'M10',
            'adherent_number' => '123456',
            'date_naissance' => '1989-09-26',
        ]
    ],
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

echo "Montant: " . $result['montant'] . "€\n";
echo "Jours: " . $result['nb_jours'] . "\n";
```

Exécuter:

```bash
php test_api.php
```

Résultat attendu:

```
Montant: 750.6€
Jours: 5
```

### Démarrer le serveur web

```bash
bin/cake server
```

Accéder à: `http://localhost:8765/indemnite-journaliere`

## Fichiers de test disponibles

12 scénarios de test sont disponibles dans `tests/Fixture/`:

| Fichier | Montant attendu | Description |
|---------|----------------|-------------|
| mock.json | 750.60€ | Calcul de base |
| mock2.json | 17318.92€ | Multiples arrêts |
| mock3.json | 41832.60€ | Calcul étendu |
| mock4.json | 37875.88€ | Avec last_payment_date |
| mock5.json | 34276.56€ | last_payment_date différent |
| mock6.json | 31412.61€ | Affiliation récente |
| mock7.json | 74331.79€ | CCPL avec patho anterior |
| mock8.json | 19291.28€ | Cas supplémentaire |
| mock9.json | 53467.98€ | **Transition âge 70 ans** |
| mock10.json | 51744.25€ | **Période 2 intermédiaire** |
| mock11.json | 10245.69€ | Cas supplémentaire |
| mock12.json | 8330.25€ | Paiement partiel |

## Endpoint API

### POST /indemnite-journaliere/api-calculate.json

Exemple avec cURL:

```bash
curl -X POST http://localhost:8765/indemnite-journaliere/api-calculate.json \
  -H "Content-Type: application/json" \
  -d '{
    "arrets": [{
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
    }],
    "statut": "M",
    "classe": "A",
    "option": 100,
    "birth_date": "1989-09-26",
    "current_date": "2024-09-09",
    "attestation_date": "2024-01-31",
    "nb_trimestres": 8,
    "previous_cumul_days": 0,
    "prorata": 1,
    "patho_anterior": false
  }'
```

Réponse:

```json
{
  "success": true,
  "data": {
    "montant": 750.6,
    "nb_jours": 5,
    "age": 34,
    "total_cumul_days": 5,
    "taux_detail": "..."
  }
}
```

## Utilisation dans votre code

### Dans un controller

```php
<?php
namespace App\Controller;

use App\Service\IJCalculatorService;

class MesCalculsController extends AppController
{
    public function calculer()
    {
        $calculator = new IJCalculatorService(CONFIG . 'taux.csv');

        $data = $this->request->getData();
        $result = $calculator->calculateAmount($data);

        $this->set('result', $result);
    }
}
```

### Sauvegarder dans la base de données

```php
<?php
// Dans un controller avec CalculationsTable chargée

$calculation = $this->Calculations->newEntity([
    'adherent_number' => '123456',
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1989-09-26',
    'nb_jours' => $result['nb_jours'],
    'montant' => $result['montant'],
    'age' => $result['age'],
    'total_cumul_days' => $result['total_cumul_days'],
    'nb_trimestres' => 8,
    'patho_anterior' => false,
    'calculation_data' => json_encode($result),
    'input_data' => json_encode($data),
    'calculated_at' => new \DateTime(),
]);

if ($this->Calculations->save($calculation)) {
    $this->Flash->success('Calcul sauvegardé');
}
```

### Récupérer des arrêts

```php
<?php
// Dans un controller avec ArretsTable chargée

// Tous les arrêts d'un adhérent
$arrets = $this->Arrets->find('byAdherent', ['123456'])->all();

// Convertir au format calculateur
$arretsData = $this->Arrets->toCalculatorFormat($arrets->toArray());

// Calculer
$result = $calculator->calculateAmount([
    'arrets' => $arretsData,
    'statut' => 'M',
    // ... autres paramètres
]);
```

## Dépannage rapide

### Les tests échouent

```bash
# Vérifier que taux.csv existe
ls -la config/taux.csv

# Vérifier que les mocks existent
ls -la tests/Fixture/mock*.json

# Vérifier les permissions
chmod -R 755 config/
chmod -R 755 tests/
```

### Erreur de base de données

```bash
# Tester la connexion
bin/cake database status

# Réinitialiser les migrations
bin/cake migrations rollback --target=0
bin/cake migrations migrate
```

### Erreur "Class not found"

```bash
# Régénérer l'autoloader
composer dump-autoload
```

## Documentation complète

- **README_CAKEPHP.md** - Guide complet d'utilisation
- **CAKEPHP5_STRUCTURE.md** - Architecture et structure détaillée
- **CAKEPHP5_MIGRATION.md** - Guide de migration depuis PHP original

## Prochaines étapes

1. ✅ Installation terminée
2. ✅ Tests validés
3. 📝 Créer les templates de vues (optionnel)
4. 📝 Ajouter l'authentification (optionnel)
5. 📝 Déployer en production

## Support

Pour toute question ou problème:

1. Consulter `README_CAKEPHP.md` section "Dépannage"
2. Consulter la documentation CakePHP 5: https://book.cakephp.org/5/
3. Vérifier les logs: `logs/error.log` et `logs/debug.log`

---

**Temps total d'installation: ~5 minutes**

Bon développement ! 🚀
