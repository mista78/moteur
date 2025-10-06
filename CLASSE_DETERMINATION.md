# Détermination Automatique de la Classe du Médecin

## Vue d'ensemble

Le système IJCalculator inclut maintenant une fonction de **détermination automatique de la classe** du médecin basée sur ses revenus de l'année N-2.

## Règles métier

### Classification

Il existe trois classes de cotisations:

| Classe | Revenus N-2 | En PASS |
|--------|-------------|---------|
| **A** | < 47 000 € | < 1 PASS |
| **B** | 47 000 € - 141 000 € | 1 à 3 PASS |
| **C** | > 141 000 € | > 3 PASS |

### Règles spéciales

1. **Revenus non communiqués**: Si le médecin est taxé d'office (revenus non communiqués), la classe A lui est appliquée automatiquement.

2. **Année de référence**: La classe de cotisation est fonction des revenus **N-2**
   - Exemple: Pour des cotisations 2024, on utilise les revenus de 2022
   - Exemple: Pour des cotisations 2023, on utilise les revenus de 2021

3. **Date de détermination**: La classe est définie à la **date d'ouverture des droits**

## API

### IJCalculator::determineClasse()

```php
public function determineClasse(
    ?float $revenuNMoins2 = null,
    ?string $dateOuvertureDroits = null,
    bool $taxeOffice = false
): string
```

#### Paramètres

- `$revenuNMoins2` (float|null): Revenus de l'année N-2 en euros
  - Si `null`, retourne 'A' (revenus non disponibles)

- `$dateOuvertureDroits` (string|null): Date d'ouverture des droits (format Y-m-d)
  - Utilisée pour déterminer l'année N et donc l'année N-2
  - Optionnelle pour la logique actuelle

- `$taxeOffice` (bool): Indique si le médecin est taxé d'office
  - Si `true`, retourne automatiquement 'A'
  - Défaut: `false`

#### Retour

String: 'A', 'B' ou 'C'

### IJCalculator::getAnneeNMoins2()

```php
public function getAnneeNMoins2(string $dateOuvertureDroits): int
```

Calcule l'année N-2 à partir de la date d'ouverture des droits.

#### Paramètres

- `$dateOuvertureDroits` (string): Date d'ouverture des droits

#### Retour

int: Année N-2

## Exemples d'utilisation

### 1. Détermination simple

```php
require_once 'IJCalculator.php';

$calculator = new IJCalculator('taux.csv');

// Médecin avec revenus de 85 000 € en N-2
$classe = $calculator->determineClasse(85000, '2024-01-15');
echo "Classe: {$classe}"; // Classe: B
```

### 2. Revenus non communiqués

```php
// Médecin taxé d'office
$classe = $calculator->determineClasse(null, '2024-01-15', true);
echo "Classe: {$classe}"; // Classe: A

// Ou simplement sans revenus
$classe = $calculator->determineClasse(null, '2024-01-15');
echo "Classe: {$classe}"; // Classe: A
```

### 3. Cas limites

```php
// Exactement 1 PASS (47 000 €)
$classe = $calculator->determineClasse(47000);
echo "Classe: {$classe}"; // Classe: B

// Juste en dessous (46 999 €)
$classe = $calculator->determineClasse(46999);
echo "Classe: {$classe}"; // Classe: A

// Exactement 3 PASS (141 000 €)
$classe = $calculator->determineClasse(141000);
echo "Classe: {$classe}"; // Classe: B

// Juste au-dessus (142 000 €)
$classe = $calculator->determineClasse(142000);
echo "Classe: {$classe}"; // Classe: C
```

### 4. Utilisation dans un calcul IJ complet

```php
$calculator = new IJCalculator('taux.csv');

// Déterminer la classe automatiquement
$dateOuvertureDroits = '2024-01-15';
$revenusMedecin = 85000; // Revenus de l'année N-2

$classe = $calculator->determineClasse($revenusMedecin, $dateOuvertureDroits);

// Utiliser la classe dans le calcul
$result = $calculator->calculateAmount([
    'arrets' => $arrets,
    'statut' => 'M',
    'classe' => $classe,  // Classe déterminée automatiquement
    'option' => 100,
    'birth_date' => '1989-09-26',
    'current_date' => '2024-09-09',
    'attestation_date' => '2024-01-31',
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => false,
]);

echo "Classe utilisée: {$classe}\n";
echo "Montant: {$result['montant']} €\n";
```

### 5. Calcul de l'année N-2

```php
$dateOuvertureDroits = '2024-01-15';
$anneeNMoins2 = $calculator->getAnneeNMoins2($dateOuvertureDroits);

echo "Date d'ouverture: {$dateOuvertureDroits}\n";
echo "Année N-2: {$anneeNMoins2}\n";
// Output: Année N-2: 2022
```

## Intégration CakePHP 5

### Dans un Controller

```php
namespace App\Controller;

use App\Service\IJCalculatorService;

class IndemniteJournaliereController extends AppController
{
    public function calculate()
    {
        $calculator = new IJCalculatorService(CONFIG . 'taux.csv');

        // Récupérer les revenus depuis la base de données ou le formulaire
        $revenuNMoins2 = $this->request->getData('revenu_n_moins_2');
        $dateOuvertureDroits = $this->request->getData('date_ouverture_droits');
        $taxeOffice = $this->request->getData('taxe_office', false);

        // Déterminer la classe automatiquement
        $classe = $calculator->determineClasse(
            $revenuNMoins2,
            $dateOuvertureDroits,
            $taxeOffice
        );

        // Continuer avec le calcul en utilisant la classe déterminée
        $result = $calculator->calculateAmount([
            'classe' => $classe,
            // ... autres paramètres
        ]);

        $this->set([
            'classe_determinee' => $classe,
            'result' => $result,
        ]);
    }
}
```

### Dans un Formulaire

```php
// src/Form/IJCalculationForm.php

public function validationDefault(Validator $validator): Validator
{
    $validator
        ->decimal('revenu_n_moins_2')
        ->allowEmptyString('revenu_n_moins_2')
        ->greaterThanOrEqual('revenu_n_moins_2', 0, 'Les revenus doivent être positifs');

    $validator
        ->boolean('taxe_office')
        ->allowEmptyString('taxe_office');

    return $validator;
}
```

### Vue avec sélection automatique

```php
<!-- templates/IndemniteJournaliere/calculate.php -->

<div class="form-group">
    <label>Revenus N-2</label>
    <input type="number"
           name="revenu_n_moins_2"
           class="form-control"
           placeholder="Ex: 85000">
    <small>Revenus de l'année <?= date('Y') - 2 ?></small>
</div>

<div class="form-check">
    <input type="checkbox"
           name="taxe_office"
           id="taxe_office"
           class="form-check-input">
    <label for="taxe_office">Taxé d'office (revenus non communiqués)</label>
</div>

<?php if (isset($classe_determinee)): ?>
<div class="alert alert-info">
    Classe déterminée automatiquement: <strong><?= h($classe_determinee) ?></strong>
</div>
<?php endif; ?>
```

## API REST

### Endpoint de détermination de classe

```php
public function apiDetermineClasse()
{
    $this->request->allowMethod(['post']);
    $this->viewBuilder()->setClassName('Json');

    try {
        $calculator = new IJCalculatorService(CONFIG . 'taux.csv');

        $revenuNMoins2 = $this->request->getData('revenu_n_moins_2');
        $dateOuvertureDroits = $this->request->getData('date_ouverture_droits');
        $taxeOffice = $this->request->getData('taxe_office', false);

        $classe = $calculator->determineClasse(
            $revenuNMoins2,
            $dateOuvertureDroits,
            $taxeOffice
        );

        $anneeNMoins2 = $calculator->getAnneeNMoins2($dateOuvertureDroits);

        $this->set([
            'success' => true,
            'data' => [
                'classe' => $classe,
                'annee_n_moins_2' => $anneeNMoins2,
                'revenu_n_moins_2' => $revenuNMoins2,
                'taxe_office' => $taxeOffice,
            ],
            '_serialize' => ['success', 'data'],
        ]);
    } catch (\Exception $e) {
        $this->response = $this->response->withStatus(400);
        $this->set([
            'success' => false,
            'error' => $e->getMessage(),
            '_serialize' => ['success', 'error'],
        ]);
    }
}
```

### Exemple de requête cURL

```bash
curl -X POST http://localhost:8765/indemnite-journaliere/api-determine-classe.json \
  -H "Content-Type: application/json" \
  -d '{
    "revenu_n_moins_2": 85000,
    "date_ouverture_droits": "2024-01-15",
    "taxe_office": false
  }'
```

### Réponse JSON

```json
{
  "success": true,
  "data": {
    "classe": "B",
    "annee_n_moins_2": 2022,
    "revenu_n_moins_2": 85000,
    "taxe_office": false
  }
}
```

## Scénarios de test

### Test simple

```bash
php test_determine_classe.php
```

Ce script affiche:
- 10 scénarios de test couvrant tous les cas
- Comparaison des montants entre classes
- Tableau récapitulatif des seuils

### Tests unitaires

```php
// tests/TestCase/Service/IJCalculatorServiceTest.php

public function testDetermineClasseA()
{
    $calculator = new IJCalculatorService(CONFIG . 'taux.csv');

    $classe = $calculator->determineClasse(30000, '2024-01-15');
    $this->assertEquals('A', $classe);
}

public function testDetermineClasseB()
{
    $calculator = new IJCalculatorService(CONFIG . 'taux.csv');

    $classe = $calculator->determineClasse(85000, '2024-01-15');
    $this->assertEquals('B', $classe);
}

public function testDetermineClasseC()
{
    $calculator = new IJCalculatorService(CONFIG . 'taux.csv');

    $classe = $calculator->determineClasse(200000, '2024-01-15');
    $this->assertEquals('C', $classe);
}

public function testDetermineClasseTaxeOffice()
{
    $calculator = new IJCalculatorService(CONFIG . 'taux.csv');

    $classe = $calculator->determineClasse(null, '2024-01-15', true);
    $this->assertEquals('A', $classe);
}

public function testDetermineClasseSeuilExact()
{
    $calculator = new IJCalculatorService(CONFIG . 'taux.csv');

    // Exactement 1 PASS → Classe B
    $classe = $calculator->determineClasse(47000);
    $this->assertEquals('B', $classe);

    // Exactement 3 PASS → Classe B
    $classe = $calculator->determineClasse(141000);
    $this->assertEquals('B', $classe);

    // Juste au-dessus de 3 PASS → Classe C
    $classe = $calculator->determineClasse(142000);
    $this->assertEquals('C', $classe);
}

public function testGetAnneeNMoins2()
{
    $calculator = new IJCalculatorService(CONFIG . 'taux.csv');

    $annee = $calculator->getAnneeNMoins2('2024-01-15');
    $this->assertEquals(2022, $annee);

    $annee = $calculator->getAnneeNMoins2('2023-06-30');
    $this->assertEquals(2021, $annee);
}
```

## Tableau de décision

| Revenus N-2 | Taxé office | Classe résultante |
|-------------|-------------|-------------------|
| < 47 000 € | Non | A |
| 47 000 - 141 000 € | Non | B |
| > 141 000 € | Non | C |
| N'importe quel montant | Oui | A |
| NULL | Non | A |

## Calcul du revenu journalier par classe

### Formules de calcul

Après détermination de la classe, le revenu journalier est calculé selon les formules suivantes :

| Classe | Formule | Description |
|--------|---------|-------------|
| **A** | `montant_1_pass / 730` | 1 PASS divisé par 730 jours |
| **B** | `revenu / 730` | Revenu réel divisé par 730 jours |
| **C** | `(montant_1_pass * 3) / 730` | 3 PASS divisés par 730 jours |

### Exemple avec PASS = 47 000 €

```php
$calculator = new IJCalculator('taux.csv');
$calculator->setPassValue(47000);

// Classe A
$resultA = $calculator->calculateRevenuAnnuel('A');
// Résultat: 64,38 €/jour (47 000 / 730)

// Classe B avec revenu de 85 000 €
$resultB = $calculator->calculateRevenuAnnuel('B', 85000);
// Résultat: 116,44 €/jour (85 000 / 730)

// Classe C
$resultC = $calculator->calculateRevenuAnnuel('C');
// Résultat: 193,15 €/jour (141 000 / 730)
```

### Cas limites

Lorsque le revenu de classe B correspond exactement aux seuils :

- **Revenu B = 1 PASS (47 000 €)** : 64,38 €/jour = identique à classe A
- **Revenu B = 3 PASS (141 000 €)** : 193,15 €/jour = identique à classe C

### Utilisation de la fonction calculateRevenuAnnuel()

```php
public function calculateRevenuAnnuel($classe, $revenu = null)
```

**Paramètres :**
- `$classe` (string) : Classe du médecin ('A', 'B' ou 'C')
- `$revenu` (float|null) : Revenu annuel réel (obligatoire pour classe B)

**Retour (array) :**
```php
[
    'classe' => 'B',                    // Classe utilisée
    'nb_pass' => 1.81,                  // Nombre de PASS
    'revenu_annuel' => 85000,           // Revenu annuel
    'revenu_per_day' => 116.44,         // Revenu par jour
    'pass_value' => 47000               // Valeur du PASS utilisée
]
```

## Configuration

### Modifier la valeur du PASS

```php
// Dans config/app_local.php
'IJ' => [
    'passValue' => 48000,  // Nouvelle valeur du PASS
]

// Ou programmatiquement
$calculator->setPassValue(48000);
```

### Historique des valeurs PASS

| Année | PASS |
|-------|------|
| 2024 | 47 000 € |
| 2023 | 43 992 € |
| 2022 | 41 136 € |

Note: L'implémentation actuelle utilise une valeur unique du PASS. Pour une gestion historique complète, il faudrait stocker les valeurs par année.

## Cas d'usage

### 1. Médecin en début de carrière

```php
$classe = $calculator->determineClasse(30000, '2024-01-15');
// Classe A: Revenus faibles, taux d'indemnisation de base
```

### 2. Médecin établi

```php
$classe = $calculator->determineClasse(85000, '2024-01-15');
// Classe B: Revenus moyens, taux intermédiaire
```

### 3. Médecin à hauts revenus

```php
$classe = $calculator->determineClasse(200000, '2024-01-15');
// Classe C: Revenus élevés, taux maximal
```

### 4. Installation récente - revenus pas encore déclarés

```php
$classe = $calculator->determineClasse(null, '2024-01-15');
// Classe A par défaut
```

## Améliorations futures

1. **Historique PASS par année**: Stocker les valeurs PASS historiques pour un calcul précis selon l'année N-2

2. **Gestion des revenus partiels**: Gérer le cas d'un médecin n'ayant exercé qu'une partie de l'année N-2

3. **Évolution automatique**: Mettre à jour automatiquement la classe si les revenus N-2 changent

4. **Notification**: Alerter le médecin si un changement de revenus pourrait modifier sa classe

5. **Validation externe**: Intégration avec des APIs fiscales pour récupération automatique des revenus

## Conclusion

La fonction `determineClasse()` simplifie considérablement le processus de détermination de classe en automatisant les règles métier basées sur les revenus N-2 et le statut fiscal du médecin.
