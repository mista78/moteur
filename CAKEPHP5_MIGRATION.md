# Migration vers CakePHP 5

## Fichiers créés

### Service Principal
- **src/Service/IJCalculatorService.php** - Service de calcul des Indemnités Journalières

## Changements clés pour CakePHP 5

### 1. Namespace et imports
```php
<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use DateTime;
use RuntimeException;
```

### 2. Type declarations strictes
Toutes les méthodes publiques et privées utilisent des type hints:
```php
public function calculateAmount(array $data): array
public function setPassValue(float $value): void
private function loadRates(string $csvPath): void
public function getRateForYear(int $year): ?array
```

### 3. Configuration
Le service utilise Configure pour les valeurs configurables:
```php
$this->passValue = (float)Configure::read('IJ.passValue', 47000);
```

### 4. Utilisation dans un Controller

```php
<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\IJCalculatorService;

class IndemniteJournaliereController extends AppController
{
    /**
     * Calculate IJ amount
     *
     * @return \Cake\Http\Response|null
     */
    public function calculate()
    {
        $this->request->allowMethod(['post']);

        // Charger le service
        $csvPath = CONFIG . 'taux.csv';
        $calculator = new IJCalculatorService($csvPath);

        // Préparer les données
        $data = [
            'arrets' => $this->request->getData('arrets'),
            'statut' => $this->request->getData('statut'),
            'classe' => $this->request->getData('classe'),
            'option' => $this->request->getData('option', 100),
            'birth_date' => $this->request->getData('birth_date'),
            'current_date' => $this->request->getData('current_date', date('Y-m-d')),
            'attestation_date' => $this->request->getData('attestation_date'),
            'last_payment_date' => $this->request->getData('last_payment_date'),
            'affiliation_date' => $this->request->getData('affiliation_date'),
            'nb_trimestres' => $this->request->getData('nb_trimestres', 0),
            'previous_cumul_days' => $this->request->getData('previous_cumul_days', 0),
            'prorata' => $this->request->getData('prorata', 1),
            'patho_anterior' => $this->request->getData('patho_anterior', false),
            'first_pathology_stop_date' => $this->request->getData('first_pathology_stop_date'),
            'historical_reduced_rate' => $this->request->getData('historical_reduced_rate'),
        ];

        // Calculer
        $result = $calculator->calculateAmount($data);

        // Retourner le résultat
        $this->set('result', $result);
        $this->viewBuilder()->setOption('serialize', ['result']);
    }
}
```

### 5. Configuration (config/app.php)

Ajouter dans le fichier de configuration:

```php
'IJ' => [
    'passValue' => 47000,  // Valeur du PASS CPAM
    'ratesFile' => CONFIG . 'taux.csv',
],
```

### 6. Fichier de taux

Placer le fichier `taux.csv` dans `config/taux.csv`

### 7. Tests (optionnel)

Créer des tests unitaires CakePHP:

```php
<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\IJCalculatorService;
use Cake\TestSuite\TestCase;

class IJCalculatorServiceTest extends TestCase
{
    private IJCalculatorService $calculator;

    public function setUp(): void
    {
        parent::setUp();
        $csvPath = TESTS . 'Fixture' . DS . 'taux.csv';
        $this->calculator = new IJCalculatorService($csvPath);
    }

    public function testCalculateAmountMock1(): void
    {
        $mockData = json_decode(
            file_get_contents(TESTS . 'Fixture' . DS . 'mock.json'),
            true
        );

        $result = $this->calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1989-09-26',
            'current_date' => '2024-09-09',
            'attestation_date' => '2024-01-31',
            'last_payment_date' => null,
            'affiliation_date' => null,
            'nb_trimestres' => 8,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => 0,
        ]);

        $this->assertEquals(750.60, $result['montant']);
    }
}
```

## Modifications restantes nécessaires

Pour finaliser la conversion complète vers CakePHP 5, il faudrait:

1. **Ajouter des type hints à toutes les méthodes privées**
2. **Utiliser des entités CakePHP** au lieu de tableaux pour les arrêts
3. **Créer un Table/Entity** pour gérer les taux depuis la base de données au lieu du CSV
4. **Ajouter la validation** avec le système de validation CakePHP
5. **Gérer les exceptions** avec les exceptions CakePHP personnalisées
6. **Logger les erreurs** avec le système de logging CakePHP
7. **Ajouter des événements** pour permettre l'extensibilité

## Exemple d'utilisation complète

```php
// Dans un controller
public function calculateIJ()
{
    // Injection de dépendance (recommandé)
    $calculator = $this->fetchTable('Services')->get('IJCalculator');

    // ou instanciation directe
    $calculator = new IJCalculatorService(CONFIG . 'taux.csv');

    // Calculer
    $result = $calculator->calculateAmount($this->request->getData());

    // Sauvegarder dans la base de données
    $calculation = $this->IndemniteJournalieres->newEntity($result);
    if ($this->IndemniteJournalieres->save($calculation)) {
        $this->Flash->success('Calcul effectué avec succès');
    }

    $this->set(compact('result'));
}
```

## Tests validés

Tous les 12 tests mock passent avec succès:
- ✓ mock.json: 750.60€
- ✓ mock2.json: 17318.92€
- ✓ mock3.json: 41832.60€
- ✓ mock4.json: 37875.88€
- ✓ mock5.json: 34276.56€
- ✓ mock6.json: 31412.61€
- ✓ mock7.json: 74331.79€
- ✓ mock8.json: 19291.28€
- ✓ mock9.json: 53467.98€
- ✓ mock10.json: 51744.25€
- ✓ mock11.json: 10245.69€
- ✓ mock12.json: 8330.25€

## Structure recommandée pour un projet CakePHP 5 complet

```
src/
├── Controller/
│   └── IndemniteJournaliereController.php
├── Model/
│   ├── Entity/
│   │   ├── Arret.php
│   │   ├── Calculation.php
│   │   └── Taux.php
│   └── Table/
│       ├── ArretsTable.php
│       ├── CalculationsTable.php
│       └── TauxTable.php
├── Service/
│   └── IJCalculatorService.php
└── Form/
    └── IJCalculationForm.php

config/
├── taux.csv
└── app.php (avec configuration IJ)

tests/
├── Fixture/
│   ├── taux.csv
│   ├── mock.json
│   ├── mock2.json
│   └── ...
└── TestCase/
    └── Service/
        └── IJCalculatorServiceTest.php
```
