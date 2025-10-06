# Formules de Calcul du Revenu par Classe

## Vue d'ensemble

Les formules de calcul du revenu journalier ont été mises à jour pour refléter les règles métier spécifiques à chaque classe de médecin.

## Nouvelles formules

### Classe A
```
Revenu par jour = montant_1_pass / 730
```

- **Description** : 1 PASS divisé par 730 jours
- **Exemple** : Avec PASS = 47 000 €
  - Revenu annuel : 47 000 €
  - Revenu par jour : 47 000 / 730 = **64,38 €/jour**

### Classe B
```
Revenu par jour = revenu / 730
```

- **Description** : Revenu réel du médecin divisé par 730 jours
- **Particularité** : Nécessite le revenu réel en paramètre
- **Exemples** :
  - Revenu de 85 000 € : 85 000 / 730 = **116,44 €/jour**
  - Revenu de 100 000 € : 100 000 / 730 = **136,99 €/jour**
  - Revenu de 141 000 € : 141 000 / 730 = **193,15 €/jour**

### Classe C
```
Revenu par jour = (montant_1_pass * 3) / 730
```

- **Description** : 3 PASS divisés par 730 jours
- **Exemple** : Avec PASS = 47 000 €
  - Revenu annuel : 141 000 €
  - Revenu par jour : 141 000 / 730 = **193,15 €/jour**

## Utilisation de l'API

### IJCalculator::calculateRevenuAnnuel()

```php
public function calculateRevenuAnnuel($classe, $revenu = null)
```

#### Paramètres

| Paramètre | Type | Description | Obligatoire |
|-----------|------|-------------|-------------|
| `$classe` | string | Classe du médecin ('A', 'B' ou 'C') | Oui |
| `$revenu` | float\|null | Revenu annuel réel du médecin | Oui pour classe B |

#### Retour

Retourne un array contenant :

```php
[
    'classe' => 'B',              // Classe utilisée
    'nb_pass' => 1.81,            // Nombre de PASS (revenu / PASS)
    'revenu_annuel' => 85000,     // Revenu annuel
    'revenu_per_day' => 116.44,   // Revenu par jour
    'pass_value' => 47000         // Valeur du PASS utilisée
]
```

## Exemples de code

### Exemple 1 : Classe A

```php
require_once 'IJCalculator.php';

$calculator = new IJCalculator('taux.csv');
$calculator->setPassValue(47000);

$result = $calculator->calculateRevenuAnnuel('A');

echo "Classe A:\n";
echo "  Revenu annuel: " . number_format($result['revenu_annuel'], 2) . " €\n";
echo "  Revenu par jour: " . number_format($result['revenu_per_day'], 2) . " €\n";
echo "  Nb PASS: " . $result['nb_pass'] . "\n";

// Output:
// Classe A:
//   Revenu annuel: 47 000,00 €
//   Revenu par jour: 64,38 €
//   Nb PASS: 1
```

### Exemple 2 : Classe B avec revenu spécifique

```php
$revenuMedecin = 85000;
$result = $calculator->calculateRevenuAnnuel('B', $revenuMedecin);

echo "Classe B (revenu: 85 000 €):\n";
echo "  Revenu annuel: " . number_format($result['revenu_annuel'], 2) . " €\n";
echo "  Revenu par jour: " . number_format($result['revenu_per_day'], 2) . " €\n";
echo "  Nb PASS: " . number_format($result['nb_pass'], 2) . "\n";

// Output:
// Classe B (revenu: 85 000 €):
//   Revenu annuel: 85 000,00 €
//   Revenu par jour: 116,44 €
//   Nb PASS: 1,81
```

### Exemple 3 : Classe C

```php
$result = $calculator->calculateRevenuAnnuel('C');

echo "Classe C:\n";
echo "  Revenu annuel: " . number_format($result['revenu_annuel'], 2) . " €\n";
echo "  Revenu par jour: " . number_format($result['revenu_per_day'], 2) . " €\n";
echo "  Nb PASS: " . $result['nb_pass'] . "\n";

// Output:
// Classe C:
//   Revenu annuel: 141 000,00 €
//   Revenu par jour: 193,15 €
//   Nb PASS: 3
```

### Exemple 4 : Classe B sans revenu spécifié

```php
// Si aucun revenu n'est fourni pour classe B, utilise 2 PASS par défaut
$result = $calculator->calculateRevenuAnnuel('B');

echo "Classe B (par défaut):\n";
echo "  Revenu annuel: " . number_format($result['revenu_annuel'], 2) . " €\n";
echo "  Revenu par jour: " . number_format($result['revenu_per_day'], 2) . " €\n";
echo "  Nb PASS: " . number_format($result['nb_pass'], 2) . "\n";

// Output:
// Classe B (par défaut):
//   Revenu annuel: 94 000,00 €
//   Revenu par jour: 128,77 €
//   Nb PASS: 2,00
```

## Cas limites et validations

### Cas limite 1 : Classe B avec revenu = 1 PASS

Lorsque le revenu de classe B correspond exactement à 1 PASS :

```php
$result = $calculator->calculateRevenuAnnuel('B', 47000);
// revenu_per_day = 64,38 € (identique à classe A)
```

### Cas limite 2 : Classe B avec revenu = 3 PASS

Lorsque le revenu de classe B correspond exactement à 3 PASS :

```php
$result = $calculator->calculateRevenuAnnuel('B', 141000);
// revenu_per_day = 193,15 € (identique à classe C)
```

### Tableau de comparaison

| Classe | Revenu annuel | Revenu/jour | Nb PASS |
|--------|---------------|-------------|---------|
| A | 47 000 € | 64,38 € | 1 |
| B (1 PASS) | 47 000 € | 64,38 € | 1 |
| B (85 000 €) | 85 000 € | 116,44 € | 1,81 |
| B (100 000 €) | 100 000 € | 136,99 € | 2,13 |
| B (3 PASS) | 141 000 € | 193,15 € | 3 |
| C | 141 000 € | 193,15 € | 3 |

### Ratios entre classes

Avec PASS = 47 000 € et revenu B = 85 000 € :

- **Ratio B/A** : 116,44 / 64,38 = **1,81x**
- **Ratio C/A** : 193,15 / 64,38 = **3,00x**
- **Ratio C/B** : 193,15 / 116,44 = **1,66x**

## Test avec différentes valeurs de PASS

Le tableau ci-dessous montre l'impact de différentes valeurs de PASS :

| PASS | Classe A (€/jour) | Classe B 85k (€/jour) | Classe C (€/jour) |
|------|-------------------|------------------------|-------------------|
| 43 992 € | 60,26 € | 116,44 € | 180,79 € |
| 47 000 € | 64,38 € | 116,44 € | 193,15 € |
| 50 000 € | 68,49 € | 116,44 € | 205,48 € |

**Observation** : Le revenu journalier de classe B reste constant (116,44 €) car il dépend du revenu réel (85 000 €), pas du PASS.

## Intégration CakePHP 5

### Service avec types stricts

```php
namespace App\Service;

class IJCalculatorService
{
    /**
     * Calculate annual revenue and daily rate for a given class
     *
     * @param string $classe Doctor's contribution class (A, B, or C)
     * @param float|null $revenu Actual annual revenue (required for class B)
     * @return array<string, mixed> Revenue calculation details
     */
    public function calculateRevenuAnnuel(string $classe, ?float $revenu = null): array
    {
        $classe = strtoupper($classe);

        switch ($classe) {
            case 'A':
                return [
                    'classe' => 'A',
                    'nb_pass' => 1,
                    'revenu_annuel' => $this->passValue,
                    'revenu_per_day' => $this->passValue / 730,
                    'pass_value' => $this->passValue
                ];

            case 'B':
                if ($revenu === null) {
                    $revenu = 2 * $this->passValue;
                }
                return [
                    'classe' => 'B',
                    'revenu_annuel' => $revenu,
                    'revenu_per_day' => $revenu / 730,
                    'nb_pass' => $revenu / $this->passValue,
                    'pass_value' => $this->passValue
                ];

            case 'C':
                return [
                    'classe' => 'C',
                    'nb_pass' => 3,
                    'revenu_annuel' => $this->passValue * 3,
                    'revenu_per_day' => ($this->passValue * 3) / 730,
                    'pass_value' => $this->passValue
                ];

            default:
                return [
                    'classe' => 'A',
                    'nb_pass' => 1,
                    'revenu_annuel' => $this->passValue,
                    'revenu_per_day' => $this->passValue / 730,
                    'pass_value' => $this->passValue
                ];
        }
    }
}
```

### Utilisation dans un controller

```php
namespace App\Controller;

use App\Service\IJCalculatorService;

class IndemniteJournaliereController extends AppController
{
    public function calculate()
    {
        $calculator = new IJCalculatorService(CONFIG . 'taux.csv');

        // Récupérer les données du formulaire
        $classe = $this->request->getData('classe');
        $revenu = $this->request->getData('revenu_n_moins_2');

        // Calculer le revenu journalier
        $result = $calculator->calculateRevenuAnnuel($classe, $revenu);

        $this->set([
            'revenu_data' => $result,
            'revenu_per_day' => $result['revenu_per_day'],
            'nb_pass' => $result['nb_pass']
        ]);
    }
}
```

## Tests

Un fichier de test complet est disponible : `test_revenue_calculation.php`

### Exécution des tests

```bash
php test_revenue_calculation.php
```

### Résultat attendu

```
========================================
VALIDATION DES FORMULES
========================================

✓ Classe A: formule correcte
✓ Classe B: formule correcte
✓ Classe C: formule correcte

✓ TOUTES LES FORMULES SONT CORRECTES
```

## Modifications apportées

### Fichiers modifiés

1. **IJCalculator.php** (ligne 1097-1150)
   - Refonte complète de la fonction `calculateRevenuAnnuel()`
   - Changement de signature : `$nbPass` → `$revenu`
   - Implémentation des nouvelles formules

2. **src/Service/IJCalculatorService.php** (ligne 1110-1171)
   - Ajout de la documentation PHPDoc complète
   - Types stricts : `string $classe`, `?float $revenu`, retour `array`
   - Mêmes formules que IJCalculator.php

3. **CLASSE_DETERMINATION.md** (section ajoutée)
   - Documentation des formules
   - Exemples d'utilisation
   - Cas limites

4. **test_revenue_calculation.php** (nouveau fichier)
   - Tests complets des 3 formules
   - Comparaisons entre classes
   - Tests avec différentes valeurs de PASS
   - Validation des cas limites

### Ancien vs Nouveau comportement

#### Ancien comportement

```php
// Ancienne signature
public function calculateRevenuAnnuel($classe, $nbPass = null)

// Classe B : utilisait nbPass * PASS / 730
$result = $calculator->calculateRevenuAnnuel('B', 2);
// → 94 000 / 730 = 128,77 €/jour (basé sur nb de PASS)
```

#### Nouveau comportement

```php
// Nouvelle signature
public function calculateRevenuAnnuel($classe, $revenu = null)

// Classe B : utilise le revenu réel / 730
$result = $calculator->calculateRevenuAnnuel('B', 85000);
// → 85 000 / 730 = 116,44 €/jour (basé sur revenu réel)
```

## Compatibilité

### Breaking changes

⚠️ **Attention** : Cette modification change la signature de la fonction `calculateRevenuAnnuel()` :

- **Avant** : `calculateRevenuAnnuel($classe, $nbPass = null)`
- **Après** : `calculateRevenuAnnuel($classe, $revenu = null)`

Le second paramètre a changé de signification :
- **Avant** : Nombre de PASS (ex: 1, 2, 3)
- **Après** : Revenu annuel en euros (ex: 47000, 85000, 141000)

### Migration

Si vous utilisez actuellement `calculateRevenuAnnuel()` avec le paramètre `$nbPass`, vous devez le convertir :

```php
// Ancien code
$result = $calculator->calculateRevenuAnnuel('B', 2);

// Nouveau code
$revenu = 2 * $calculator->passValue; // 2 * 47000 = 94000
$result = $calculator->calculateRevenuAnnuel('B', $revenu);
```

## Conclusion

Les formules de calcul du revenu par classe sont maintenant correctement implémentées selon les règles métier :

- ✅ **Classe A** : 1 PASS / 730
- ✅ **Classe B** : Revenu réel / 730
- ✅ **Classe C** : 3 PASS / 730

Tous les tests passent avec succès, et la documentation a été mise à jour en conséquence.
