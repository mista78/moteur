# Affichage du Mock et Valeurs Attendues dans les Résultats

## Date: 2025-11-06

## Fonctionnalité Ajoutée

L'interface web affiche maintenant **dans les résultats** :
1. Quel fichier mock a été utilisé pour le calcul
2. Les **valeurs attendues** du mock (montant, jours)
3. Une **comparaison automatique** entre les valeurs calculées et attendues (✓ ou ✗)

## Aperçu Visuel

```
╔═══════════════════════════════════════════════════════════╗
║ ✅ Mock 2 chargé                                          ║
║ ─────────────────────────────────────────────────────────║
║ Valeurs attendues:                                        ║
║ ✓ Montant: 17318.92€    ✓ Jours: 230                    ║
║ Multiple stoppages with rechute scenario                  ║
╚═══════════════════════════════════════════════════════════╝

💰 Résultats Complets
...
```

## Modifications Apportées

### 1. Variable Globale: `currentMockInfo`

**Fichier**: `app.js` (ligne 4)

```javascript
let currentMockInfo = null; // Store current mock file info and expected values
```

**Structure:**
```javascript
currentMockInfo = {
    file: 'mock2.json',      // Nom du fichier
    label: 'Mock 2',          // Label affiché
    config: {                 // Configuration attendue
        expected_montant: 17318.92,
        expected_nb_jours: 230,
        description: 'Multiple stoppages with rechute'
    }
};
```

### 2. Fonction `displayLoadedMock()` Améliorée

**Fichier**: `app.js` (lignes 259-300)

```javascript
function displayLoadedMock(mockFile, config = null) {
    // ... création de l'indicateur ...

    // Store mock info globally
    currentMockInfo = {
        file: mockFile,
        label: label,
        config: config
    };
}
```

**Changements:**
- Ajout du paramètre `config`
- Stockage des informations dans `currentMockInfo`
- Permet de réutiliser les données dans les résultats

### 3. Fonction `loadMockData()` Mise à Jour

**Fichier**: `app.js` (lignes 676-686)

```javascript
if (result.success) {
    // Clear all form fields first
    clearAllFormFields();

    // Load configuration if available
    const config = result.config || null;

    // Show which mock is loaded (pass config for expected values)
    displayLoadedMock(mockFile, config);

    if (config) {
        // ... populate form fields ...
    }
}
```

**Changements:**
- Extraction du `config` du résultat API
- Passage du `config` à `displayLoadedMock()`
- Ordre optimisé (clear puis display)

### 4. Fonction `displayFullResults()` - Affichage dans Résultats

**Fichier**: `app.js` (lignes 922-962)

```javascript
function displayFullResults(data) {
    const resultsDiv = document.getElementById('results');

    let html = '<div class="results">';

    // Show loaded mock indicator if available
    if (currentMockInfo) {
        html += `<div style="margin-bottom: 15px; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; font-weight: bold; font-size: 14px; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
            <div style="margin-bottom: 8px;">✅ ${currentMockInfo.label} chargé</div>`;

        // Show expected values if available
        if (currentMockInfo.config) {
            const config = currentMockInfo.config;
            html += '<div style="font-size: 12px; font-weight: normal; opacity: 0.95; margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.3);">';
            html += '<strong>Valeurs attendues:</strong><br>';

            if (config.expected_montant !== undefined) {
                const match = data.montant !== undefined && Math.abs(data.montant - config.expected_montant) < 0.01;
                const icon = match ? '✓' : '✗';
                const color = match ? '#d4edda' : '#f8d7da';
                html += `<span style="background: ${color}; color: #333; padding: 2px 6px; border-radius: 3px; margin-right: 8px;">${icon} Montant: ${config.expected_montant.toFixed(2)}€</span>`;
            }

            if (config.expected_nb_jours !== undefined) {
                const match = data.nb_jours !== undefined && data.nb_jours === config.expected_nb_jours;
                const icon = match ? '✓' : '✗';
                const color = match ? '#d4edda' : '#f8d7da';
                html += `<span style="background: ${color}; color: #333; padding: 2px 6px; border-radius: 3px;">${icon} Jours: ${config.expected_nb_jours}</span>`;
            }

            if (config.description) {
                html += `<br><em style="font-size: 11px;">${config.description}</em>`;
            }

            html += '</div>';
        }

        html += '</div>';
    }

    html += '<h2>💰 Résultats Complets</h2>';
    // ... reste du code ...
}
```

**Caractéristiques:**
- Badge gradient bleu-violet en haut des résultats
- Affichage du nom du mock
- Comparaison automatique des valeurs
- Badges verts (✓) si match, rouges (✗) si différence
- Description du scenario de test
- Séparateur visuel entre nom et valeurs

### 5. Fonction `clearAllFormFields()` Mise à Jour

**Fichier**: `app.js` (lignes 664-669)

```javascript
// Hide loaded mock indicator and clear mock info
const indicator = document.getElementById('loaded-mock-indicator');
if (indicator) {
    indicator.style.display = 'none';
}
currentMockInfo = null;
```

**Changements:**
- Réinitialisation de `currentMockInfo` à `null`
- Empêche l'affichage d'infos de mock obsolètes

## Affichage Visuel Détaillé

### Badge dans les Résultats

**Style:**
- Gradient: #667eea → #764ba2 (bleu-violet)
- Texte blanc, gras
- Border-radius: 8px
- Padding: 12px
- Box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3)
- Margin-bottom: 15px

### Structure du Badge

```
╔══════════════════════════════════════════╗
║ ✅ Mock 2 chargé                         ║
║ ──────────────────────────────────────── ║ ← Séparateur
║ Valeurs attendues:                       ║
║ [✓ Montant: 17318.92€] [✓ Jours: 230]  ║
║ Multiple stoppages with rechute          ║
╚══════════════════════════════════════════╝
```

### Badges de Comparaison

**Match (✓):**
- Background: #d4edda (vert clair)
- Color: #333 (texte noir)
- Icône: ✓

**Différence (✗):**
- Background: #f8d7da (rouge clair)
- Color: #333 (texte noir)
- Icône: ✗

## Exemples de Cas d'Usage

### Cas 1: Mock avec Valeurs Attendues - Match Parfait

**Mock chargé:** mock2.json
```json
{
    "expected_montant": 17318.92,
    "expected_nb_jours": 230,
    "description": "Multiple stoppages with rechute"
}
```

**Résultat calculé:**
- Montant: 17318.92€
- Jours: 230

**Affichage:**
```
✅ Mock 2 chargé
────────────────────────────────
Valeurs attendues:
✓ Montant: 17318.92€    ✓ Jours: 230
Multiple stoppages with rechute
```

### Cas 2: Mock avec Différence Détectée

**Mock chargé:** mock7.json
```json
{
    "expected_montant": 74331.79,
    "expected_nb_jours": 965,
    "description": "CCPL with pathology anterior"
}
```

**Résultat calculé:**
- Montant: 74000.00€ (DIFFÉRENT!)
- Jours: 965

**Affichage:**
```
✅ Mock 7 chargé
────────────────────────────────
Valeurs attendues:
✗ Montant: 74331.79€    ✓ Jours: 965
CCPL with pathology anterior
```

### Cas 3: Calcul Manuel (Sans Mock)

**Aucun mock chargé**

**Affichage:**
```
💰 Résultats Complets
[Pas de badge de mock]
```

## Logique de Comparaison

### Montant

```javascript
const match = data.montant !== undefined &&
              Math.abs(data.montant - config.expected_montant) < 0.01;
```

- Tolérance: 0.01€ (1 centime)
- Permet de gérer les erreurs d'arrondi

### Jours

```javascript
const match = data.nb_jours !== undefined &&
              data.nb_jours === config.expected_nb_jours;
```

- Comparaison stricte (entier)
- Pas de tolérance (les jours sont exacts)

## Format API: `load-mock` Endpoint

### Requête

```
GET /api.php?endpoint=load-mock&file=mock2.json
```

### Réponse

```json
{
    "success": true,
    "data": {
        "arrets": [...]
    },
    "config": {
        "statut": "M",
        "classe": "A",
        "birth_date": "1960-01-15",
        "expected_montant": 17318.92,
        "expected_nb_jours": 230,
        "description": "Multiple stoppages with rechute"
    }
}
```

**Champs importants:**
- `config.expected_montant`: Montant attendu (float)
- `config.expected_nb_jours`: Nombre de jours attendu (int)
- `config.description`: Description du scenario de test (string)

## Avantages

✅ **Validation automatique**: Compare instantanément les résultats
✅ **Feedback visuel**: Indicateurs verts/rouges clairs
✅ **Traçabilité**: Sait toujours quel mock a été utilisé
✅ **Débogage facilité**: Repère immédiatement les écarts
✅ **Tests rapides**: Valide les calculs en un coup d'œil
✅ **Documentation**: La description explique le scenario

## Workflow Complet

1. **Utilisateur clique** sur "📋 Mock 2"
2. **API charge** le mock avec config
3. **Badge apparaît**: "✅ Mock 2 chargé" (en haut du formulaire)
4. **Formulaire remplit** avec données du mock
5. **Utilisateur clique** "💰 Calculer Tout"
6. **Calcul s'effectue** avec les données
7. **Résultats s'affichent** avec badge en haut:
   - Nom du mock
   - Valeurs attendues
   - Comparaison (✓/✗)
   - Description

## Tests

### Test Manuel

1. **Démarrer le serveur**:
   ```bash
   php -S localhost:8000
   ```

2. **Ouvrir**: `http://localhost:8000`

3. **Charger un mock**:
   - Cliquer sur "📋 Mock 2"
   - Vérifier badge: "✅ Mock 2 chargé"

4. **Calculer**:
   - Cliquer "💰 Calculer Tout"
   - Vérifier résultats avec badge en haut

5. **Vérifier comparaison**:
   - Badges verts si match
   - Badges rouges si différence

### Test de Différence

Modifier temporairement une valeur dans le formulaire:
1. Charger Mock 2
2. Modifier un champ (ex: option 100 → 50)
3. Calculer
4. Observer ✗ rouge sur les valeurs

## Compatibilité

- ✅ **Tous navigateurs modernes**
- ✅ **Responsive design**
- ✅ **Pas de dépendances externes**
- ✅ **Rétrocompatible**
- ✅ **Fonctionne avec/sans mock**

## Notes Techniques

### Persistance

- `currentMockInfo` persiste pendant toute la session
- Effacé uniquement par `clearAllFormFields()`
- Permet de savoir quel mock a généré les résultats

### Précision des Comparaisons

- **Montant**: Tolérance 0.01€ (gère les arrondis JavaScript)
- **Jours**: Comparaison stricte (entiers)

### Gestion des Cas Limites

- Si `config` absent: Badge simple sans comparaison
- Si `expected_*` absent: Ne compare pas ce champ
- Si `data.*` absent: Considère comme non-match

## Évolutions Futures

1. **Plus de métriques**: Comparer aussi l'âge, trimestres, etc.
2. **Export comparaison**: Générer un rapport de test
3. **Historique**: Garder trace des tests précédents
4. **Statistiques**: Pourcentage de réussite des tests
5. **Alertes**: Notifier si écart > seuil critique

---

**Auteur**: Claude Code
**Date**: 2025-11-06
**Fichiers modifiés**: `app.js`
**Tests**: Manuel (interface web)
**Statut**: ✅ Production Ready
