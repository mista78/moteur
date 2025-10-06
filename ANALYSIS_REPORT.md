# Rapport d'Analyse - Tests des Mocks IJ

## Résumé des Tests

### ✅ Tests Réussis
- **mock.json**: Calculé 750.60€ (pas de résultat attendu)
- **mock2.json**: Calculé 8659.46€ (pas de résultat attendu)
- **mock4.json**: Calculé 18937.94€ (pas de résultat attendu)

### ❌ Tests Échoués

#### Mock3 (CCPL)
- **Calculé**: 55,452.80€
- **Attendu**: 34,276.56€
- **Différence**: +21,176.24€ (62% trop élevé)
- **Paramètres**:
  - Statut: CCPL
  - Classe: C
  - Option: 100
  - Age: 63 ans
  - Jours: 374
  - Code pathologie: C

#### Mock5 (CCPL)
- **Calculé**: 137,106.24€
- **Attendu**: 34,276.56€
- **Différence**: +102,829.68€ (400% trop élevé)
- **Paramètres**:
  - Statut: CCPL
  - Classe: C (dans test, mais A dans mock JSON)
  - Option: 25
  - Age: 40 ans
  - Jours: 915
  - Code pathologie: A

**Note importante**: Mock3 et Mock5 ont EXACTEMENT le même montant attendu (34,276.56€), ce qui suggère une règle spéciale pour CCPL.

#### Mock6 (Statut S)
- **Calculé**: 0.00€
- **Attendu**: 31,412.61€
- **Paramètres**:
  - Statut: S (spécial?)
  - Classe: B
  - Option: 100
  - Age: 62 ans
  - Code pathologie: S

**Problème**: Le statut 'S' n'est pas reconnu par le système actuel.

#### Mock7 (Médecin)
- **Calculé**: 54,627.12€
- **Attendu**: 57,099.15€
- **Différence**: -2,472.03€ (4.3% trop bas)
- **Paramètres**:
  - Statut: M (Médecin)
  - Classe: A
  - Option: 100
  - Age: 64 ans
  - Jours: 1003
  - Date naissance: 1960-01-29
  - 64ème anniversaire: 2024-01-29

## Bugs Corrigés

### 1. Taux-to-Tier Mapping ✅
**Problème**: Les taux 7-9 et 4-6 utilisaient tous le tier 3.

**Correction appliquée**:
```php
// Avant (INCORRECT):
if ($taux >= 7 && $taux <= 9) {
    $tier = 3; // Mauvais!
}

// Après (CORRECT):
if ($taux >= 7 && $taux <= 9) {
    $tier = 2; // Tier 2 pour période 2 (366-730 jours)
}
```

**Impact**:
- Mock7 amélioré de 61,033.32€ → 54,627.12€ (se rapproche de l'attendu 57,099.15€)

## Problèmes Identifiés

### 1. CCPL/RSPM - Gestion de l'Option
**Statut**: 🔴 Non résolu

**Observations**:
- Mock3 (CCPL, option 100): Ratio calculé/attendu = 0.618
- Mock5 (CCPL, option 25): Ratio calculé/attendu = 0.25 (correspond exactement!)
- Les deux mocks attendent le MÊME montant (34,276.56€)

**Hypothèses testées**:
1. ❌ Option comme multiplicateur simple sur les taux CCPL classe C
2. ❌ Utiliser code_pathologie au lieu de classe
3. ❌ Appliquer option sur classe A de base
4. ✅ Pour option 25: le ratio est exactement 0.25 → la formule fonctionne partiellement

**Question**: Comment l'option CCPL/RSPM devrait-elle fonctionner exactement?
- Est-ce que option 100 a un comportement spécial?
- Est-ce que la classe utilisée dépend de l'option?
- Y a-t-il une table de taux séparée pour CCPL/RSPM que nous n'utilisons pas?

### 2. Statut 'S' Non Supporté
**Statut**: 🔴 Non résolu

**Problème**: Mock6 utilise statut='S' qui n'est pas géré par le système.

**Impact**: Calcul retourne 0€ au lieu de 31,412.61€

**Actions nécessaires**:
- Clarifier ce qu'est le statut 'S'
- Ajouter la logique de calcul pour ce statut
- Déterminer les taux à utiliser

### 3. Mock7 - Légère Différence
**Statut**: 🟡 Partiellement résolu

**Analyse détaillée**:
- Date d'effet: 2022-01-02 (âge 61 ans)
- 64ème anniversaire: 2024-01-29
- Date fin: 2024-09-30
- Total: 1003 jours

**Répartition actuelle**:
- Avant 64 ans (02/01/2022 - 28/01/2024): 757 jours
  - P1 (0-365): 365j × taux_a1
  - P2 (366-730): 365j × taux_a2
  - P3 (731-757): 27j × taux_a3
- Après 64 ans (29/01/2024 - 30/09/2024): 246 jours
  - P3 (731-1003): 246j × taux_a3

**Calcul attendu vs réel**:
- Attendu: 57,099.15€
- Calculé: 54,627.12€
- Écart: 2,472.03€

**Hypothèse**: Le découpage par birthday est fait, mais peut-être que les taux par âge ne sont pas appliqués correctement après 64 ans?

## Taux Utilisés (2024)

### Classe A
- taux_a1: 75.06€ (tier 1 - période 0-365 jours)
- taux_a2: 38.3€ (tier 2 - période 366-730 jours)
- taux_a3: 56.3€ (tier 3 - période 731-1095 jours)

### Classe B
- taux_b1: 112.59€
- taux_b2: 57.45€
- taux_b3: 84.45€

### Classe C
- taux_c1: 150.12€
- taux_c2: 76.6€
- taux_c3: 112.59€

## Recommandations

1. **URGENT**: Clarifier la logique CCPL/RSPM
   - Comment l'option doit être appliquée?
   - Faut-il utiliser les taux A/B/C ou des taux spéciaux?
   - Pourquoi mock3 et mock5 attendent le même montant?

2. **URGENT**: Implémenter le statut 'S'
   - Obtenir la spécification
   - Créer la logique de calcul

3. **Moyen**: Ajuster mock7
   - Vérifier si la logique d'âge après 64 ans est correcte
   - Peut-être qu'il manque un ajustement de taux basé sur l'âge exact?

## Code Modifié

### IJCalculator.php - Ligne 852
```php
// Correction du mapping taux → tier
elseif ($taux >= 7 && $taux <= 9) {
    $tier = 2; // Tier 2 pour période 2 (au lieu de 3)
}
```

Cette correction a amélioré les calculs mais n'a pas résolu tous les problèmes.
