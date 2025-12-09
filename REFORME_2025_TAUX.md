# Réforme 2025 - Nouveau Calcul des Taux

## Vue d'ensemble

À partir du **1er janvier 2025**, le calcul des indemnités journalières (IJ) est modifié pour utiliser une **formule basée sur le PASS** (Plafond Annuel de la Sécurité Sociale) au lieu des taux fixes historiques stockés en base de données.

## Principe de la Réforme

### Détection Automatique

Le système détecte automatiquement si la **date d'effet** (date_effet) de l'arrêt est :
- **< 2025-01-01** → Utilise les taux historiques (CSV/base de données)
- **≥ 2025-01-01** → Utilise la nouvelle formule basée sur PASS

### ⚠️ RÈGLE CRITIQUE

**C'est la DATE D'EFFET de l'arrêt qui détermine le système, PAS la date de paiement !**

**Exemples** :
- Arrêt avec `date_effet = 2024-12-15` qui continue en janvier 2025
  - ✅ Utilise les taux historiques 2024
  - ❌ N'utilise PAS la formule PASS
  - Même si les paiements sont faits en 2025

- Arrêt avec `date_effet = 2025-01-10`
  - ✅ Utilise la formule PASS
  - Nouvel arrêt = nouveau système

### Formule de Base

```
Taux journalier = (Multiplicateur_Classe × PASS) / 730
```

Où le multiplicateur de classe est :
- **Classe A** : 1
- **Classe B** : 2
- **Classe C** : 3

## Calcul Détaillé par Taux

### Taux 1-3 : Taux Plein (< 62 ans ou pathologie antérieure)

| Taux | Formule | Pourcentage | Exemple Classe B (PASS=46368€) |
|------|---------|-------------|--------------------------------|
| **Taux 1** | Base × 100% | 100% | 127.04 € |
| **Taux 2** | Base × 2/3 | 66.67% | 84.69 € |
| **Taux 3** | Base × 1/3 | 33.33% | 42.35 € |

### Taux 4-6 : Taux Senior (≥ 70 ans)

| Taux | Formule | Pourcentage | Exemple Classe B (PASS=46368€) |
|------|---------|-------------|--------------------------------|
| **Taux 4** | Base × 50% | 50% | 63.52 € |
| **Taux 5** | Taux 4 × 2/3 | 33.33% | 42.35 € |
| **Taux 6** | Taux 4 × 1/3 | 16.67% | 21.17 € |

### Taux 7-9 : Période 2 (62-69 ans, jours 366-730)

| Taux | Formule | Pourcentage | Exemple Classe B (PASS=46368€) |
|------|---------|-------------|--------------------------------|
| **Taux 7** | Base × 75% | 75% | 95.28 € |
| **Taux 8** | Taux 7 × 2/3 | 50% | 63.52 € |
| **Taux 9** | Taux 7 × 1/3 | 25% | 31.76 € |

## Tableau Complet des Taux (PASS 2024 = 46 368 €)

| Classe | Taux 1 | Taux 2 | Taux 3 | Taux 4 | Taux 5 | Taux 6 | Taux 7 | Taux 8 | Taux 9 |
|--------|--------|--------|--------|--------|--------|--------|--------|--------|--------|
| **A** | 63.52€ | 42.35€ | 21.17€ | 31.76€ | 21.17€ | 10.59€ | 47.64€ | 31.76€ | 15.88€ |
| **B** | 127.04€ | 84.69€ | 42.35€ | 63.52€ | 42.35€ | 21.17€ | 95.28€ | 63.52€ | 31.76€ |
| **C** | 190.55€ | 127.04€ | 63.52€ | 95.28€ | 63.52€ | 31.76€ | 142.92€ | 95.28€ | 47.64€ |

## Application de l'Option (CCPL/RSPM)

Pour les statuts **CCPL** et **RSPM**, le taux calculé est multiplié par le pourcentage d'option :

```
Taux final = Taux de base × (Option% / 100)
```

### Exemple

Médecin CCPL, Classe A, Option 50%, Taux 1 :
```
Base = 1 × 46368 / 730 = 63.52 €
Option 50% = 63.52 × 0.5 = 31.76 €
```

## Exemples Concrets

### Exemple 1 : Médecin Classe A, Arrêt Court

```json
{
  "statut": "M",
  "classe": "A",
  "date_effet": "2025-01-15",
  "taux": 1
}
```

**Calcul** :
```
Taux = (1 × 46368) / 730 = 63.52 €/jour
```

### Exemple 2 : Médecin Classe C, Période 2 (62-69 ans)

```json
{
  "statut": "M",
  "classe": "C",
  "date_effet": "2025-03-01",
  "taux": 7
}
```

**Calcul** :
```
Base = (3 × 46368) / 730 = 190.55 €
Taux 7 = 190.55 × 0.75 = 142.92 €/jour
```

### Exemple 3 : CCPL Classe B, Option 50%

```json
{
  "statut": "CCPL",
  "classe": "B",
  "option": 50,
  "date_effet": "2025-06-01",
  "taux": 1
}
```

**Calcul** :
```
Base = (2 × 46368) / 730 = 127.04 €
Option 50% = 127.04 × 0.5 = 63.52 €/jour
```

## Implémentation Technique

### Méthode calculate2025Rate()

```php
/**
 * src/Services/RateService.php
 */
private function calculate2025Rate(
    string $statut,
    string $classe,
    string|int|float $option,
    int $taux
): float {
    // 1. Récupérer la valeur du PASS
    $passValue = $this->passValue ?? 46368;

    // 2. Déterminer le multiplicateur de classe
    $classeMultiplier = match(strtoupper($classe)) {
        'A' => 1,
        'B' => 2,
        'C' => 3,
        default => 2
    };

    // 3. Calculer le taux de base
    $baseRate = ($classeMultiplier * $passValue) / 730;

    // 4. Appliquer les réductions selon le numéro de taux
    $rate = match($taux) {
        1 => $baseRate,
        2 => $baseRate * (2/3),
        3 => $baseRate * (1/3),
        4 => $baseRate * 0.5,
        5 => $baseRate * 0.5 * (2/3),
        6 => $baseRate * 0.5 * (1/3),
        7 => $baseRate * 0.75,
        8 => $baseRate * 0.75 * (2/3),
        9 => $baseRate * 0.75 * (1/3),
        default => $baseRate
    };

    // 5. Appliquer l'option pour CCPL/RSPM
    if (in_array(strtoupper($statut), ['CCPL', 'RSPM'])) {
        $optionValue = (float)$option / 100;
        $rate *= $optionValue;
    }

    return round($rate, 2);
}
```

### Détection de la Réforme

```php
public function getDailyRate(...): float {
    $effectiveDate = $date ?? "$year-01-01";
    $isAfter2025Reform = strtotime($effectiveDate) >= strtotime('2025-01-01');

    if ($isAfter2025Reform) {
        return $this->calculate2025Rate($statut, $classe, $option, $taux);
    }

    // Utilise les taux historiques (CSV/DB)
    // ...
}
```

## Tests

### Exécuter les Tests

```bash
# Test complet de la réforme 2025
php test_reforme_2025.php

# Tests unitaires
./vendor/bin/phpunit test/RateServiceTest.php
```

### Cas de Test Couverts

1. ✅ Calcul des taux de base par classe (A, B, C)
2. ✅ Application des 9 réductions de taux
3. ✅ Matrice complète Classes × Taux
4. ✅ Options CCPL/RSPM (25%, 50%, 75%, 100%)
5. ✅ Comparaison avant/après 2025
6. ✅ Cas réels d'utilisation

## Configuration du PASS

### Méthode 1 : Définir Manuellement

```php
$rateService = new RateService([]);
$rateService->setPassValue(46368); // PASS 2024
```

### Méthode 2 : Via Base de Données

Le PASS est automatiquement chargé depuis la table `plafond_secu_sociale` si `PassRepository` est injecté :

```php
// config/dependencies.php
$passRepo = $c->get(PassRepository::class);
// Le PASS pour 2025 sera automatiquement récupéré
```

## Compatibilité Ascendante

✅ **100% Compatible** - Les calculs avant 2025 fonctionnent exactement comme avant :

```php
// Arrêt avec date d'effet 2024 → Utilise taux CSV/DB
$rate2024 = $rateService->getDailyRate(
    statut: 'M',
    classe: 'B',
    option: 100,
    taux: 1,
    year: 2024,
    date: '2024-12-31'
);
// Utilise les taux historiques

// Arrêt avec date d'effet 2025 → Utilise formule PASS
$rate2025 = $rateService->getDailyRate(
    statut: 'M',
    classe: 'B',
    option: 100,
    taux: 1,
    year: 2025,
    date: '2025-01-01'
);
// Utilise la nouvelle formule
```

## Impact sur l'API

### Aucun Changement pour les Clients

Les endpoints API restent identiques :

```bash
POST /api/calculations
```

Le calcul bascule automatiquement selon la date d'effet :
- Si `date_effet >= 2025-01-01` → Formule PASS
- Si `date_effet < 2025-01-01` → Taux historiques

## Évolution du PASS

Le PASS évolue chaque année. Valeurs de référence :

| Année | PASS | Source |
|-------|------|--------|
| 2024 | 46 368 € | Officiel |
| 2025 | À définir | À mettre à jour |

**Important** : Mettre à jour la valeur du PASS chaque année dans la base de données `plafond_secu_sociale`.

## FAQ

### Q : Que se passe-t-il si le PASS n'est pas défini ?

**R** : Le système utilise la valeur par défaut : 46 368 € (PASS 2024)

### Q : Comment mettre à jour le PASS pour 2026 ?

**R** : Deux méthodes :
1. Insérer dans `plafond_secu_sociale` : `INSERT INTO plafond_secu_sociale (year, pass_value) VALUES (2026, XXXX)`
2. Ou appeler `$rateService->setPassValue(XXXX)` manuellement

### Q : Les calculs 2024 sont-ils affectés ?

**R** : Non, tous les calculs avec `date_effet < 2025-01-01` utilisent les taux historiques

### Q : Peut-on forcer l'ancien système ?

**R** : Oui, en modifiant la date d'effet pour être avant 2025, mais **non recommandé**

## Références

- **Code** : `src/Services/RateService.php` (méthode `calculate2025Rate`)
- **Tests** : `test_reforme_2025.php`
- **Tests Unitaires** : `test/RateServiceTest.php`
- **Documentation PASS** : `PASS_DB_INTEGRATION.md`

## Résumé

| Aspect | Détail |
|--------|--------|
| **Date d'application** | ≥ 2025-01-01 |
| **Formule de base** | (Classe × PASS) / 730 |
| **Classes** | A=1, B=2, C=3 |
| **Taux** | 9 taux avec réductions automatiques |
| **PASS par défaut** | 46 368 € (2024) |
| **Compatibilité** | 100% rétrocompatible |
| **Tests** | ✅ Complets et validés |

---

**Réforme 2025 implémentée et testée avec succès !** ✅
