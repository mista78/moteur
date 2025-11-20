# TauxDeterminationService - Documentation Complète

## Vue d'ensemble

`TauxDeterminationService` est le service responsable de la détermination du numéro de taux (1-9) selon l'âge, les trimestres d'affiliation, et la pathologie antérieure. Il gère également la détermination de la classe de cotisation (A/B/C) à partir du revenu N-2.

**Emplacement** : `Services/TauxDeterminationService.php`

**Interface** : `Services/TauxDeterminationInterface.php`

**Namespace** : `App\IJCalculator\Services`

**Lignes de code** : 148 lignes

## Principe de Responsabilité Unique

Ce service suit le principe SOLID de **responsabilité unique** :
- ✅ Détermination du numéro de taux (1-9)
- ✅ Application des règles de pathologie antérieure
- ✅ Détermination de la classe de cotisation (A/B/C) depuis le revenu
- ❌ Ne calcule PAS les montants ni les dates

## Système de Taux (1-9)

### Taux selon l'Âge

| Taux | Âge | Description | Utilisation |
|------|-----|-------------|-------------|
| 1-3 | < 62 ans | Taux plein / -1/3 / -2/3 | Tous les jours |
| 4-6 | ≥ 70 ans | Taux senior réduit / -1/3 / -2/3 | Maximum 365 jours |
| 7-9 | 62-69 ans | Taux -25% / -1/3 / -2/3 | Période 2 (jours 366-730) |

### Réductions pour Pathologie Antérieure

| Trimestres | Taux Appliqué | Description |
|-----------|---------------|-------------|
| < 8 | 0 (inéligible) | Pas d'indemnisation |
| 8-15 | Taux +1 | Réduction 1/3 |
| 16-23 | Taux +2 | Réduction 2/3 |
| ≥ 24 | Taux base | Taux plein |

### Exemples de Détermination

```
Médecin 50 ans, 20 trimestres, pathologie antérieure :
→ Âge < 62 → Base taux 1
→ 20 trimestres (16-23) → +1
→ Taux final : 2

Médecin 65 ans, 30 trimestres, pas pathologie antérieure, période 2 :
→ Âge 62-69, période 2 → Base taux 7
→ ≥ 24 trimestres → +0
→ Taux final : 7

Médecin 72 ans, 10 trimestres, pathologie antérieure :
→ Âge ≥ 70 → Base taux 4
→ 10 trimestres (8-15) → +1
→ Taux final : 5
```

## Méthodes Principales

### 1. determineTauxNumber()

Détermine le numéro de taux (1-9) selon les paramètres.

```php
public function determineTauxNumber(
    int $age,
    int $nbTrimestres,
    bool $pathoAnterior,
    ?int $historicalReducedRate = null,
    bool $usePeriode2 = false
): int
```

#### Paramètres

- **`$age`** (int) : Âge du médecin
- **`$nbTrimestres`** (int) : Nombre de trimestres d'affiliation
- **`$pathoAnterior`** (bool) : Pathologie antérieure à l'affiliation
- **`$historicalReducedRate`** (int, optionnel) : Taux historique déjà appliqué
- **`$usePeriode2`** (bool) : Utilise période 2 (pour âge 62-69)

#### Valeur de Retour

`int` : Numéro de taux (0-9)
- **0** : Inéligible (< 8 trimestres avec pathologie antérieure)
- **1-9** : Numéro de taux applicable

#### Exemples

**Exemple 1 : Médecin < 62 ans, Taux Plein**

```php
use App\IJCalculator\Services\TauxDeterminationService;

$tauxService = new TauxDeterminationService();

$taux = $tauxService->determineTauxNumber(
    age: 50,
    nbTrimestres: 30,
    pathoAnterior: false,
    historicalReducedRate: null,
    usePeriode2: false
);

echo $taux; // 1 (taux plein < 62 ans)
```

**Exemple 2 : Pathologie Antérieure avec Réduction**

```php
$taux = $tauxService->determineTauxNumber(
    age: 55,
    nbTrimestres: 12,  // Entre 8 et 15
    pathoAnterior: true,
    historicalReducedRate: null,
    usePeriode2: false
);

echo $taux; // 2 (1 + 1 pour réduction 1/3)
```

**Exemple 3 : Inéligible (< 8 Trimestres)**

```php
$taux = $tauxService->determineTauxNumber(
    age: 55,
    nbTrimestres: 6,  // < 8 trimestres
    pathoAnterior: true,
    historicalReducedRate: null,
    usePeriode2: false
);

echo $taux; // 0 (inéligible)
```

**Exemple 4 : Médecin 62-69 ans, Période 1**

```php
$taux = $tauxService->determineTauxNumber(
    age: 65,
    nbTrimestres: 40,
    pathoAnterior: false,
    historicalReducedRate: null,
    usePeriode2: false  // Période 1
);

echo $taux; // 1 (période 1 utilise taux 1-3)
```

**Exemple 5 : Médecin 62-69 ans, Période 2**

```php
$taux = $tauxService->determineTauxNumber(
    age: 65,
    nbTrimestres: 40,
    pathoAnterior: false,
    historicalReducedRate: null,
    usePeriode2: true  // Période 2 (jours 366-730)
);

echo $taux; // 7 (période 2 utilise taux 7-9)
```

**Exemple 6 : Médecin ≥ 70 ans**

```php
$taux = $tauxService->determineTauxNumber(
    age: 72,
    nbTrimestres: 50,
    pathoAnterior: false,
    historicalReducedRate: null,
    usePeriode2: false
);

echo $taux; // 4 (taux senior ≥ 70 ans)
```

**Exemple 7 : Taux Historique Prioritaire**

```php
// Si le médecin a déjà eu un taux réduit pour cette pathologie
$taux = $tauxService->determineTauxNumber(
    age: 50,
    nbTrimestres: 30,
    pathoAnterior: false,
    historicalReducedRate: 5,  // Taux historique
    usePeriode2: false
);

echo $taux; // 5 (conserve le taux historique)
```

**Exemple 8 : Pathologie Antérieure - Maximum de Réduction**

```php
$taux = $tauxService->determineTauxNumber(
    age: 58,
    nbTrimestres: 18,  // Entre 16 et 23
    pathoAnterior: true,
    historicalReducedRate: null,
    usePeriode2: false
);

echo $taux; // 3 (1 + 2 pour réduction 2/3)
```

### 2. determineClasse()

Détermine la classe de cotisation (A/B/C) à partir du revenu N-2 et du PASS.

```php
public function determineClasse(float $revenuNMoins2, float $passValue): ?string
```

#### Règles de Détermination

| Revenu N-2 | Classe | Description |
|-----------|---------|-------------|
| < 1 PASS | A | Cotisation minimale |
| 1-3 PASS | B | Cotisation moyenne |
| > 3 PASS | C | Cotisation maximale |

#### Paramètres

- **`$revenuNMoins2`** (float) : Revenu de l'année N-2 en euros
- **`$passValue`** (float) : Valeur du PASS pour l'année

#### Valeur de Retour

- **`'A'`** : Classe A (revenu < 1 PASS)
- **`'B'`** : Classe B (revenu entre 1 et 3 PASS)
- **`'C'`** : Classe C (revenu > 3 PASS)
- **`null`** : Si paramètres invalides

#### Exemples

**Exemple 1 : Classe A**

```php
$tauxService = new TauxDeterminationService();

$classe = $tauxService->determineClasse(
    revenuNMoins2: 35000,
    passValue: 47000
);

echo $classe; // "A" (35000 < 47000)
```

**Exemple 2 : Classe B**

```php
$classe = $tauxService->determineClasse(
    revenuNMoins2: 85000,
    passValue: 47000
);

echo $classe; // "B" (47000 < 85000 < 141000)
// 85000 / 47000 = 1.8 PASS (entre 1 et 3)
```

**Exemple 3 : Classe C**

```php
$classe = $tauxService->determineClasse(
    revenuNMoins2: 200000,
    passValue: 47000
);

echo $classe; // "C" (200000 > 141000)
// 200000 / 47000 = 4.25 PASS (> 3)
```

**Exemple 4 : Limite Classe A/B**

```php
$classe = $tauxService->determineClasse(
    revenuNMoins2: 47000,  // Exactement 1 PASS
    passValue: 47000
);

echo $classe; // "B" (≥ 1 PASS)
```

**Exemple 5 : Limite Classe B/C**

```php
$classe = $tauxService->determineClasse(
    revenuNMoins2: 141000,  // Exactement 3 PASS
    passValue: 47000
);

echo $classe; // "C" (≥ 3 PASS)
```

## Arbre de Décision Complet

### Pour determineTauxNumber()

```
1. Vérifier éligibilité :
   └─ Si pathoAnterior = true ET nbTrimestres < 8
      → Retourne 0 (inéligible)

2. Vérifier taux historique :
   └─ Si historicalReducedRate existe
      → Retourne historicalReducedRate

3. Déterminer taux de base selon âge :
   ├─ Si age < 62
   │  └─ Base = 1 (taux plein)
   ├─ Si age >= 70
   │  └─ Base = 4 (taux senior)
   └─ Si age 62-69
      ├─ Si usePeriode2 = true
      │  └─ Base = 7 (période 2)
      └─ Sinon
         └─ Base = 1 (période 1)

4. Appliquer réduction pathologie antérieure :
   └─ Si pathoAnterior = true
      ├─ Si nbTrimestres 8-15 → Base + 1
      ├─ Si nbTrimestres 16-23 → Base + 2
      └─ Si nbTrimestres >= 24 → Base + 0

5. Retourner taux final
```

### Pour determineClasse()

```
1. Calculer nb_pass = revenuNMoins2 / passValue

2. Déterminer classe :
   ├─ Si nb_pass < 1 → Classe A
   ├─ Si 1 <= nb_pass < 3 → Classe B
   └─ Si nb_pass >= 3 → Classe C
```

## Exemples d'Utilisation Avancés

### Exemple 1 : Calcul de Taux pour Tous les Âges

```php
$tauxService = new TauxDeterminationService();

$ages = [50, 65, 72];
$resultats = [];

foreach ($ages as $age) {
    $taux = $tauxService->determineTauxNumber(
        age: $age,
        nbTrimestres: 30,
        pathoAnterior: false,
        historicalReducedRate: null,
        usePeriode2: false
    );
    $resultats[$age] = $taux;
}

print_r($resultats);
// [50 => 1, 65 => 1, 72 => 4]
```

### Exemple 2 : Impact des Trimestres sur le Taux

```php
$tauxService = new TauxDeterminationService();

$trimestresTests = [6, 10, 18, 25];

foreach ($trimestresTests as $trimestres) {
    $taux = $tauxService->determineTauxNumber(
        age: 55,
        nbTrimestres: $trimestres,
        pathoAnterior: true,
        historicalReducedRate: null,
        usePeriode2: false
    );

    echo "$trimestres trimestres → Taux $taux\n";
}

// Résultat :
// 6 trimestres → Taux 0 (inéligible)
// 10 trimestres → Taux 2 (1 + 1)
// 18 trimestres → Taux 3 (1 + 2)
// 25 trimestres → Taux 1 (1 + 0)
```

### Exemple 3 : Évolution des Classes selon Revenu

```php
$tauxService = new TauxDeterminationService();
$passValue = 47000;

$revenus = [30000, 50000, 85000, 150000, 250000];

foreach ($revenus as $revenu) {
    $classe = $tauxService->determineClasse($revenu, $passValue);
    $nbPass = round($revenu / $passValue, 2);
    echo "$revenu € ($nbPass PASS) → Classe $classe\n";
}

// Résultat :
// 30000 € (0.64 PASS) → Classe A
// 50000 € (1.06 PASS) → Classe B
// 85000 € (1.81 PASS) → Classe B
// 150000 € (3.19 PASS) → Classe C
// 250000 € (5.32 PASS) → Classe C
```

### Exemple 4 : Simulation Complète Médecin 62-69 ans

```php
$tauxService = new TauxDeterminationService();

$medecin = [
    'age' => 65,
    'nbTrimestres' => 40,
    'pathoAnterior' => false
];

// Période 1 (jours 1-365)
$tauxP1 = $tauxService->determineTauxNumber(
    age: $medecin['age'],
    nbTrimestres: $medecin['nbTrimestres'],
    pathoAnterior: $medecin['pathoAnterior'],
    historicalReducedRate: null,
    usePeriode2: false
);

// Période 2 (jours 366-730)
$tauxP2 = $tauxService->determineTauxNumber(
    age: $medecin['age'],
    nbTrimestres: $medecin['nbTrimestres'],
    pathoAnterior: $medecin['pathoAnterior'],
    historicalReducedRate: null,
    usePeriode2: true
);

// Période 3 (jours 731-1095) - utilise taux senior
$tauxP3 = $tauxService->determineTauxNumber(
    age: $medecin['age'],
    nbTrimestres: $medecin['nbTrimestres'],
    pathoAnterior: $medecin['pathoAnterior'],
    historicalReducedRate: 4,  // Force taux senior
    usePeriode2: false
);

echo "Période 1 : Taux $tauxP1\n";  // 1
echo "Période 2 : Taux $tauxP2\n";  // 7
echo "Période 3 : Taux $tauxP3\n";  // 4
```

### Exemple 5 : Tests Unitaires

```php
// Test d'inéligibilité
$taux = $tauxService->determineTauxNumber(55, 5, true, null, false);
assert($taux === 0, "< 8 trimestres avec pathologie antérieure = inéligible");

// Test taux plein jeune médecin
$taux = $tauxService->determineTauxNumber(50, 30, false, null, false);
assert($taux === 1, "< 62 ans sans pathologie = taux 1");

// Test réduction 1/3
$taux = $tauxService->determineTauxNumber(55, 12, true, null, false);
assert($taux === 2, "8-15 trimestres avec pathologie = taux 2");

// Test réduction 2/3
$taux = $tauxService->determineTauxNumber(55, 18, true, null, false);
assert($taux === 3, "16-23 trimestres avec pathologie = taux 3");

// Test senior
$taux = $tauxService->determineTauxNumber(72, 50, false, null, false);
assert($taux === 4, "≥ 70 ans = taux 4");

// Test classe A
$classe = $tauxService->determineClasse(30000, 47000);
assert($classe === 'A', "< 1 PASS = classe A");

// Test classe B
$classe = $tauxService->determineClasse(85000, 47000);
assert($classe === 'B', "1-3 PASS = classe B");

// Test classe C
$classe = $tauxService->determineClasse(200000, 47000);
assert($classe === 'C', "> 3 PASS = classe C");
```

## Points Importants

1. **Taux historique prioritaire** : Si un taux historique existe, il est toujours appliqué

2. **Inéligibilité stricte** : < 8 trimestres avec pathologie antérieure = taux 0

3. **Âge 62-69 spécial** : Trois périodes avec taux différents (1, 7, 4)

4. **Trimestres décisifs** : Les seuils 8, 16, 24 sont critiques pour les réductions

5. **PASS annuel** : La valeur du PASS change chaque année, affectant les classes

6. **Déterministe** : Mêmes paramètres = même résultat (pas d'aléatoire)

## Cas Limites

### Trimestres

- **7 trimestres** + pathologie antérieure = **inéligible** (0)
- **8 trimestres** + pathologie antérieure = **éligible** avec réduction (taux +1)
- **24 trimestres** + pathologie antérieure = **taux plein** (pas de réduction)

### Âges

- **61 ans 364 jours** = taux 1-3 (< 62)
- **62 ans 0 jour** = taux 1-3 période 1, puis 7-9 période 2
- **69 ans 364 jours** = peut utiliser taux 7-9
- **70 ans 0 jour** = taux 4-6 uniquement

### Revenus

- **46 999 €** (PASS 47000) = Classe **A**
- **47 000 €** (PASS 47000) = Classe **B**
- **141 000 €** (PASS 47000) = Classe **C**

## Gestion des Erreurs

Le service ne lève généralement pas d'exceptions mais retourne des valeurs par défaut :

```php
// Paramètres invalides
$classe = $tauxService->determineClasse(-1000, 47000);
echo $classe; // null

$classe = $tauxService->determineClasse(50000, 0);
echo $classe; // null
```

## Voir Aussi

- [IJCalculator](./IJCalculator.md) - Classe principale
- [RateService](./RateService.md) - Utilise le taux déterminé
- [DateService](./DateService.md) - Calcule les trimestres
- [AmountCalculationService](./AmountCalculationService.md) - Orchestration complète
