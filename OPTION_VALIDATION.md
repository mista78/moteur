# Validation Automatique des Options par Statut

## Vue d'ensemble

Le système IJCalculator implémente maintenant une validation automatique des options de cotisation basée sur le statut du professionnel, conformément aux règles fonctionnelles CARMF.

## Règles Fonctionnelles

### Médecin (M)
- **Options autorisées:** 100% uniquement
- **Comportement:** Toute option invalide est automatiquement corrigée à 100%

### Conjoint Collaborateur (CCPL)
- **Options autorisées:** 25%, 50%
- **Options interdites:** 100%
- **Comportement:** Option 100% ou toute option invalide est automatiquement corrigée à 25% (par défaut)

### RSPM (Régime Simplifié des Praticiens et Auxiliaires Médicaux)
- **Options autorisées:** 25%, 100%
- **Options interdites:** 50%
- **Comportement:** Option 50% ou toute option invalide est automatiquement corrigée à 25% (par défaut)

## Implémentation

### Backend (IJCalculator.php)

La validation est implémentée dans la méthode `validateAndCorrectOption()` qui:

1. **Normalise le format d'option**
   - Accepte les valeurs décimales (0.25, 0.5, 1.0)
   - Accepte les valeurs en pourcentage (25, 50, 100)
   - Accepte les chaînes avec virgule ("0,25") ou point ("0.25")

2. **Valide selon le statut**
   - Vérifie si l'option est valide pour le statut donné
   - Applique les corrections automatiques si nécessaire
   - Préserve le format d'origine (décimal ou pourcentage)

3. **Journalisation**
   - Toutes les corrections sont enregistrées via `error_log()`
   - Format: `"IJCalculator: Option auto-corrected for [STATUT] from [OLD] to [NEW]"`

### Frontend (app.js)

La validation frontend limite dynamiquement les options disponibles:

1. **Fonction `setupOptionValidation()`**
   - Écoute les changements du champ statut
   - Met à jour dynamiquement les options disponibles dans le dropdown
   - Préserve la sélection actuelle si elle est valide

2. **Options dynamiques selon le statut**
   ```javascript
   Médecin (M):  [100%]
   CCPL:         [25%, 50%]
   RSPM:         [25%, 100%]
   ```

3. **Intégration avec le chargement des mocks**
   - Déclenche la validation lors du chargement d'un mock
   - Convertit automatiquement le format d'option (100 → "1")

## Formats d'Option Supportés

Le système accepte plusieurs formats d'entrée:

| Format | Exemple | Description |
|--------|---------|-------------|
| Entier | 25, 50, 100 | Valeur en pourcentage |
| Décimal | 0.25, 0.5, 1.0 | Valeur décimale |
| Chaîne (virgule) | "0,25", "0,5", "1" | Format français |
| Chaîne (point) | "0.25", "0.5", "1.0" | Format anglais |

Tous ces formats sont automatiquement normalisés et validés.

## Tests

### Tests Unitaires
- **Fichier:** `run_all_tests.php`
- **Résultat:** ✅ 245 tests passent
- **Couverture:** Tous les mocks (mock.json à mock21.json) incluent les combinaisons statut/option valides

### Test de Démonstration
- **Fichier:** `test_option_validation_demo.php`
- **Contenu:**
  - Test 1: CCPL avec option invalide 100% → corrigée à 25%
  - Test 2: CCPL avec option valide 25% → acceptée
  - Test 3: RSPM avec option invalide 50% → corrigée à 25%
  - Test 4: Médecin avec option invalide 50% → corrigée à 100%

## Comportement en Production

1. **Validation silencieuse**
   - Les corrections sont appliquées automatiquement
   - Aucune erreur n'est levée
   - Les corrections sont journalisées pour le débogage

2. **Préservation du format**
   - Si l'entrée est en format pourcentage (25, 50, 100), la sortie sera en pourcentage
   - Si l'entrée est en format décimal (0.25, 0.5, 1.0), la sortie sera en décimal

3. **Compatibilité ascendante**
   - Fonctionne avec les données existantes
   - Aucun changement requis pour les tests existants
   - Support de tous les formats d'option historiques

## Exemples d'Utilisation

### API Call avec validation automatique

```php
// Exemple 1: CCPL avec option invalide
$data = [
    'statut' => 'CCPL',
    'option' => 100,  // Invalide pour CCPL
    // ... autres paramètres
];
$result = $calculator->calculateAmount($data);
// Option automatiquement corrigée à 25

// Exemple 2: RSPM avec option valide
$data = [
    'statut' => 'RSPM',
    'option' => 0.25,  // Valide pour RSPM
    // ... autres paramètres
];
$result = $calculator->calculateAmount($data);
// Option acceptée sans modification
```

### Interface Web

Les utilisateurs ne peuvent sélectionner que les options valides grâce au dropdown dynamique:

1. Sélectionner "Médecin" → Seul 100% est disponible
2. Sélectionner "CCPL" → Options 25% et 50% disponibles
3. Sélectionner "RSPM" → Options 25% et 100% disponibles

## Références

- **Spécification fonctionnelle:** `text.txt` (lignes 117, 188-194, 308-309)
- **Code source backend:** `IJCalculator.php:499-556`
- **Code source frontend:** `app.js:100-170`
- **Documentation projet:** `CLAUDE.md`

## Historique

- **2025-10-08:** Implémentation initiale de la validation automatique des options
  - Backend: Validation et correction automatique dans `IJCalculator::validateAndCorrectOption()`
  - Frontend: Options dynamiques basées sur le statut
  - Tests: Tous les tests existants (245) passent avec succès
