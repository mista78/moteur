# Calcul sans date d'attestation

## Vue d'ensemble

Le système IJCalculator permet maintenant de calculer les indemnités journalières **sans date d'attestation**. Cette fonctionnalité offre plus de flexibilité pour les calculs en cours ou lorsque l'attestation n'est pas encore disponible.

## Comportement

### Avec attestation (comportement classique)
Le calcul s'arrête à la date d'attestation (ou fin du mois si attestation ≥ 27).

### Sans attestation (nouveau)
Le calcul s'effectue jusqu'à:
- **La date de fin de l'arrêt** si l'arrêt est terminé
- **La date actuelle** si l'arrêt est en cours (date fin > date actuelle)

## Logique implémentée

### Code PHP (IJCalculator.php et IJCalculatorService.php)

```php
// Si pas d'attestation, utiliser la date de fin de l'arrêt
if (!$arretAttestationDate) {
    // Sans attestation, calculer jusqu'à la date de fin de l'arrêt ou date actuelle
    $attestation = min($endDate, $current);
    $arretAttestationDate = null; // Pour tracking
} else {
    $attestation = new DateTime($arretAttestationDate);

    // Si l'attestation est après le 27, prolonger jusqu'à la fin du mois
    if ((int)$attestation->format('d') >= 27) {
        $attestation->modify('last day of this month');
    }
}
```

### Calcul des jours payables

```php
$arretDays = 0;
if ($paymentStart <= $paymentEnd) {
    $arretDays = $paymentStart->diff($paymentEnd)->days + 1;
}
```

### Raison du paiement

```php
'reason' => $arretDays > 0 ?
    ($arretAttestationDate ? 'Paid' : 'Paid (no attestation - calculated to end date)')
    : 'Outside payment period'
```

## Interface utilisateur

### index.html

Le champ d'attestation est maintenant clairement marqué comme **optionnel**:

```html
<div class="form-group">
    <label for="attestation_date">Date d'attestation (optionnelle)</label>
    <input type="date" id="attestation_date">
    <small style="color: #888; font-size: 12px;">Si omise, calcul jusqu'à la fin de chaque arrêt</small>
</div>
```

### Info-box ajoutée

```html
<div class="info-box">
    <strong>Note:</strong> La date d'attestation peut être définie globalement ci-dessus ou individuellement pour chaque arrêt. Les dates individuelles ont la priorité sur la date globale.
    <br><strong>Calcul sans attestation:</strong> Si aucune date d'attestation n'est fournie, le calcul sera effectué jusqu'à la date de fin de chaque arrêt (ou la date actuelle si l'arrêt est en cours).
</div>
```

## Cas d'usage

### 1. Calcul d'un arrêt terminé sans attestation

```php
$calculator = new IJCalculator('taux.csv');

$result = $calculator->calculateAmount([
    'arrets' => [
        [
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-03-31',
            'rechute-line' => '0'
        ]
    ],
    'statut' => 'M',
    'classe' => 'B',
    'option' => 100,
    'birth_date' => '1989-09-26',
    'current_date' => '2024-09-09',
    'attestation_date' => null, // Pas d'attestation
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => false,
]);

// Résultat: calcul jusqu'au 2024-03-31
```

### 2. Calcul d'un arrêt en cours sans attestation

```php
$result = $calculator->calculateAmount([
    'arrets' => [
        [
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-12-31',
            'rechute-line' => '0'
        ]
    ],
    'current_date' => '2024-06-15', // Date actuelle
    'attestation_date' => null,
    // ... autres paramètres
]);

// Résultat: calcul jusqu'au 2024-06-15 (min entre fin arrêt et date actuelle)
```

### 3. Mix d'arrêts avec et sans attestation

```php
$result = $calculator->calculateAmount([
    'arrets' => [
        [
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-01-31',
            'attestation-date-line' => '2024-01-31' // A une attestation
        ],
        [
            'arret-from-line' => '2024-02-01',
            'arret-to-line' => '2024-02-29',
            // Pas d'attestation - calcul jusqu'au 2024-02-29
        ],
        [
            'arret-from-line' => '2024-03-01',
            'arret-to-line' => '2024-03-31',
            'attestation-date-line' => '2024-03-15' // A une attestation
        ]
    ],
    'attestation_date' => null,
    // ... autres paramètres
]);

// Arrêt 1: jusqu'à attestation (31/01)
// Arrêt 2: jusqu'à fin arrêt (29/02)
// Arrêt 3: jusqu'à attestation (15/03)
```

## Tests

### Exécution des tests

```bash
php test_no_attestation.php
```

### Résultats des tests

```
✓ Test 1: Calcul sans attestation fonctionne
✓ Test 2: Calcul avec attestation fonctionne
✓ Test 3: Plusieurs arrêts sans attestation fonctionnent
✓ Test 4: Arrêt en cours sans attestation fonctionne
✓ Test 5: Mélange d'arrêts avec/sans attestation fonctionne
```

### Cas testés

| Test | Description | Attestation | Résultat |
|------|-------------|-------------|----------|
| 1 | Arrêt simple | Non | Calcul jusqu'à fin arrêt ✓ |
| 2 | Arrêt simple | Oui (2024-03-15) | Calcul jusqu'à attestation ✓ |
| 3 | 3 arrêts consécutifs | Non | Calcul pour tous ✓ |
| 4 | Arrêt en cours | Non | Calcul jusqu'à date actuelle ✓ |
| 5 | Mix avec/sans | Mixte | Calcul adapté par arrêt ✓ |

## Détails de l'implémentation

### Fichiers modifiés

#### 1. IJCalculator.php (lignes 354-404)

**Avant:**
```php
if (!$arretAttestationDate) {
    $paymentDetails[$index] = [
        // ...
        'payable_days' => 0,
        'reason' => 'No attestation date'
    ];
    continue; // Arrêt du traitement
}
```

**Après:**
```php
if (!$arretAttestationDate) {
    // Sans attestation, calculer jusqu'à la date de fin de l'arrêt ou date actuelle
    $attestation = min($endDate, $current);
    $arretAttestationDate = null; // Pour tracking
} else {
    $attestation = new DateTime($arretAttestationDate);
    // ...
}

// Calcul continue normalement
$arretDays = 0;
if ($paymentStart <= $paymentEnd) {
    $arretDays = $paymentStart->diff($paymentEnd)->days + 1;
}
```

#### 2. IJCalculatorService.php (lignes 407-457)

Même logique que IJCalculator.php avec types stricts.

#### 3. index.html (lignes 363-367, 375-378)

- Champ attestation marqué comme optionnel
- Ajout d'un helper text
- Info-box explicative

### Changements de comportement

#### Avant
- ❌ Attestation **obligatoire**
- ❌ Sans attestation: 0 jour payable
- ❌ Raison: "No attestation date"

#### Après
- ✅ Attestation **optionnelle**
- ✅ Sans attestation: calcul jusqu'à fin arrêt
- ✅ Raison: "Paid (no attestation - calculated to end date)"

## Avantages

### 1. Flexibilité
Permet de faire des calculs même sans attestation disponible

### 2. Calculs en cours
Utile pour les arrêts en cours où l'attestation n'existe pas encore

### 3. Estimations
Permet d'estimer les montants avant réception de l'attestation

### 4. Mixte
Possibilité de mélanger arrêts avec et sans attestation

### 5. Rétrocompatibilité
Le comportement avec attestation reste inchangé

## Scénarios d'utilisation

### Scénario 1: Estimation préliminaire

**Contexte**: Médecin souhaite estimer ses IJ avant de recevoir l'attestation

**Action**:
1. Saisir les dates d'arrêt
2. Ne pas remplir la date d'attestation
3. Calculer

**Résultat**: Calcul basé sur la durée complète de l'arrêt

### Scénario 2: Suivi d'arrêt en cours

**Contexte**: Arrêt de longue durée en cours, besoin de suivi mensuel

**Action**:
1. Saisir arrêt avec date de fin lointaine
2. Calculer régulièrement sans attestation

**Résultat**: Montant calculé jusqu'à la date actuelle à chaque fois

### Scénario 3: Régularisation progressive

**Contexte**: Plusieurs arrêts, attestations reçues progressivement

**Action**:
1. Commencer sans attestations
2. Ajouter les attestations au fur et à mesure
3. Recalculer

**Résultat**: Calculs se précisent avec chaque attestation

### Scénario 4: Vérification de cohérence

**Contexte**: Vérifier si les dates d'arrêt génèrent un droit à IJ

**Action**:
1. Saisir uniquement les dates d'arrêt
2. Calculer sans attestation

**Résultat**: Permet de valider le droit avant de traiter l'attestation

## API et intégration

### Endpoint CakePHP

```php
// IndemniteJournaliereController.php
public function apiCalculate(): void
{
    $this->request->allowMethod(['post']);

    $data = $this->request->getData();
    // attestation_date est maintenant optionnel

    $calculator = new IJCalculatorService(CONFIG . 'taux.csv');
    $result = $calculator->calculateAmount($data);

    $this->set([
        'success' => true,
        'data' => $result,
        '_serialize' => ['success', 'data'],
    ]);
}
```

### Requête JSON

```json
{
  "arrets": [
    {
      "arret-from-line": "2024-01-01",
      "arret-to-line": "2024-03-31",
      "rechute-line": "0"
    }
  ],
  "statut": "M",
  "classe": "B",
  "option": 100,
  "birth_date": "1989-09-26",
  "current_date": "2024-09-09",
  "attestation_date": null,
  "nb_trimestres": 8,
  "previous_cumul_days": 0,
  "prorata": 1,
  "patho_anterior": false
}
```

### Réponse

```json
{
  "success": true,
  "data": {
    "nb_jours": 91,
    "montant": 10245.69,
    "payment_details": [
      {
        "arret_index": 0,
        "payable_days": 91,
        "attestation_date": null,
        "reason": "Paid (no attestation - calculated to end date)",
        "payment_start": "2024-01-01",
        "payment_end": "2024-03-31"
      }
    ]
  }
}
```

## Limites et considérations

### 1. Précision
Sans attestation, le calcul est moins précis (date estimée vs date réelle)

### 2. Conformité légale
L'attestation reste obligatoire pour le paiement effectif - cette fonctionnalité est pour l'estimation uniquement

### 3. Date actuelle
Pour les arrêts en cours, le montant change chaque jour

### 4. Validation
Le système ne vérifie pas si l'absence d'attestation est intentionnelle ou un oubli

## Recommandations

### Pour les développeurs
1. Toujours indiquer clairement quand l'attestation manque
2. Afficher un avertissement pour les calculs sans attestation
3. Logger les calculs sans attestation pour audit

### Pour les utilisateurs
1. Utiliser cette fonctionnalité pour des estimations uniquement
2. Fournir l'attestation dès que disponible pour un calcul précis
3. Vérifier régulièrement les arrêts en cours

### Pour les administrateurs
1. Monitorer les calculs sans attestation
2. Mettre en place des alertes pour les arrêts sans attestation > X jours
3. Former les utilisateurs à l'utilisation correcte

## Compatibilité

- **PHP**: 7.4+ (IJCalculator.php), 8.1+ (IJCalculatorService.php)
- **CakePHP**: 5.x (pour IJCalculatorService.php)
- **Rétrocompatibilité**: Totale - le comportement avec attestation est inchangé

## Conclusion

La fonctionnalité de calcul sans attestation apporte une flexibilité importante au système IJCalculator tout en maintenant la précision lorsque l'attestation est disponible. Elle permet des estimations rapides et facilite le suivi des arrêts en cours.

**Points clés:**
- ✅ Attestation désormais optionnelle
- ✅ Calcul jusqu'à fin d'arrêt si pas d'attestation
- ✅ Mixte: certains arrêts avec, d'autres sans
- ✅ Rétrocompatible avec le comportement existant
- ✅ Tests complets validant tous les scénarios
