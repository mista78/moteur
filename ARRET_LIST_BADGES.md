# Badge de Statut dans la Liste des Arrêts

## Vue d'ensemble

Cette fonctionnalité ajoute des **badges visuels** dans la liste des arrêts du formulaire pour montrer en temps réel comment chaque arrêt sera classifié après le calcul (rechute, nouvelle pathologie, ou première pathologie).

## Motivation

Avant cette fonctionnalité:
- ❌ L'utilisateur devait calculer puis regarder dans les résultats pour voir si un arrêt était une rechute
- ❌ Pas de feedback visuel pendant la saisie
- ❌ Difficile de voir les relations entre les arrêts

Après cette fonctionnalité:
- ✅ Badges visuels directement dans la liste des arrêts
- ✅ Feedback immédiat après calcul
- ✅ Voir facilement de quel arrêt une rechute provient
- ✅ Interface plus intuitive et pédagogique

## Modifications

### 1. Structure HTML (app.js & loadMockData)

**Ajout d'un espace pour le badge dans chaque header d'arrêt:**

```html
<div class="arret-header">
    <h3>Arrêt ${arretCount}</h3>
    <div id="arret_status_${arretCount}" class="arret-status-badge" style="display: none;"></div>
    <button class="btn btn-danger" onclick="removeArret(${arretCount})">Supprimer</button>
</div>
```

**Lignes modifiées:**
- `app.js:297` - Ajout du badge dans `addArret()`
- `app.js:687` - Ajout du badge dans `loadMockData()`

### 2. Fonction de mise à jour des badges (app.js)

**Nouvelle fonction `updateArretStatusBadges()` (lignes 830-866):**

```javascript
function updateArretStatusBadges(arrets) {
    const container = document.getElementById('arrets-container');
    const arretItems = container.querySelectorAll('.arret-item');

    arretItems.forEach((item, index) => {
        const id = item.id.split('-')[1];
        const badge = document.getElementById(`arret_status_${id}`);

        if (badge && arrets[index]) {
            const arret = arrets[index];
            let badgeHtml = '';
            let badgeStyle = '';

            if (arret.is_rechute === true) {
                // Afficher de quel arrêt c'est une rechute
                if (arret.rechute_of_arret_index !== undefined && arret.rechute_of_arret_index !== null) {
                    const sourceArretNum = arret.rechute_of_arret_index + 1;
                    badgeHtml = `🔄 Rechute de l'arrêt #${sourceArretNum}`;
                } else {
                    badgeHtml = '🔄 Rechute';
                }
                badgeStyle = 'background-color: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;';
            } else if (arret.is_rechute === false && index > 0) {
                badgeHtml = '🆕 Nouvelle pathologie';
                badgeStyle = 'background-color: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;';
            } else if (index === 0) {
                badgeHtml = '1ère pathologie';
                badgeStyle = 'color: #666; padding: 4px 8px; border-radius: 4px; font-size: 12px;';
            }

            if (badgeHtml) {
                badge.innerHTML = badgeHtml;
                badge.style = badgeStyle + '; display: inline-block; margin-left: 10px;';
            }
        }
    });
}
```

### 3. Appel de la fonction (app.js)

**Dans `displayFullResults()` (lignes 1176-1178):**

```javascript
// Update arret status badges in the form
if (data.arrets) {
    updateArretStatusBadges(data.arrets);
}
```

Cette fonction est appelée après l'affichage des résultats pour mettre à jour les badges dans la liste des arrêts.

### 4. Style CSS (index.html)

**Ajout du style pour les badges (lignes 394-401):**

```css
.arret-status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    margin-left: 10px;
    white-space: nowrap;
}
```

## Affichage Visuel

### Vue du formulaire AVANT calcul

```
┌─────────────────────────────────────────────────────────────┐
│  Arrêt 1                                      [Supprimer]   │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Date début: [2024-01-01]  Date fin: [2024-04-11]   │   │
│  │ ...                                                  │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  Arrêt 2                                      [Supprimer]   │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Date début: [2024-05-11]  Date fin: [2024-06-10]   │   │
│  │ ...                                                  │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### Vue du formulaire APRÈS calcul (avec badges)

```
┌─────────────────────────────────────────────────────────────┐
│  Arrêt 1  [1ère pathologie]                  [Supprimer]   │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Date début: [2024-01-01]  Date fin: [2024-04-11]   │   │
│  │ ...                                                  │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  Arrêt 2  [🔄 Rechute de l'arrêt #1]         [Supprimer]   │
│            ^^^ Badge jaune avec fond coloré                 │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Date début: [2024-05-11]  Date fin: [2024-06-10]   │   │
│  │ ...                                                  │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  Arrêt 3  [🔄 Rechute de l'arrêt #2]         [Supprimer]   │
│            ^^^ Badge jaune avec fond coloré                 │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Date début: [2024-06-30]  Date fin: [2024-07-30]   │   │
│  │ ...                                                  │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

## Types de badges

### 🔄 Badge Rechute (Jaune)

**Couleur:** Fond jaune clair (#fff3cd), texte orange foncé (#856404)

**Texte:**
- `🔄 Rechute de l'arrêt #X` (si source connue)
- `🔄 Rechute` (si source inconnue)

**Signification:** Cet arrêt est une rechute d'un arrêt précédent qui avait ouvert des droits.

### 🆕 Badge Nouvelle Pathologie (Vert)

**Couleur:** Fond vert clair (#d4edda), texte vert foncé (#155724)

**Texte:** `🆕 Nouvelle pathologie`

**Signification:** Cet arrêt n'est pas une rechute (droits pas encore ouverts OU > 1 an après le dernier arrêt).

### Badge Première Pathologie (Gris)

**Couleur:** Texte gris (#666), pas de fond

**Texte:** `1ère pathologie`

**Signification:** Premier arrêt de travail de l'affiliation.

## Exemples de scénarios

### Scénario 1: Rechutes en chaîne

**Saisie:**
```
Arrêt 1: 2024-01-01 à 2024-04-11 (102 jours)
Arrêt 2: 2024-05-11 à 2024-06-10 (31 jours) - 30j après arrêt 1
Arrêt 3: 2024-06-30 à 2024-07-30 (31 jours) - 20j après arrêt 2
```

**Affichage après calcul:**
```
Arrêt 1 [1ère pathologie]
Arrêt 2 [🔄 Rechute de l'arrêt #1]
Arrêt 3 [🔄 Rechute de l'arrêt #2]
```

### Scénario 2: Accumulation puis rechute

**Saisie:**
```
Arrêt 1: 2024-01-01 à 2024-02-16 (47 jours) - pas assez pour ouvrir droits
Arrêt 2: 2024-03-01 à 2024-04-20 (51 jours) - cumul 98j, ouvre droits
Arrêt 3: 2024-05-15 à 2024-06-15 (32 jours) - 25j après arrêt 2
```

**Affichage après calcul:**
```
Arrêt 1 [1ère pathologie]
Arrêt 2 [🆕 Nouvelle pathologie]  ← Accumule avec arrêt 1
Arrêt 3 [🔄 Rechute de l'arrêt #2] ← Rechute de #2, pas #1!
```

### Scénario 3: Nouvelle pathologie (> 1 an)

**Saisie:**
```
Arrêt 1: 2023-01-01 à 2023-04-01 (91 jours) - ouvre droits
Arrêt 2: 2024-05-01 à 2024-06-01 (32 jours) - 13 mois après arrêt 1
```

**Affichage après calcul:**
```
Arrêt 1 [1ère pathologie]
Arrêt 2 [🆕 Nouvelle pathologie] ← > 1 an, pas rechute
```

## Workflow utilisateur

### Étape 1: Saisie des arrêts
```
L'utilisateur ajoute plusieurs arrêts dans le formulaire.
Les badges sont masqués (display: none).
```

### Étape 2: Calcul
```
L'utilisateur clique sur "Calculer".
Le backend calcule et détermine is_rechute pour chaque arrêt.
```

### Étape 3: Affichage des résultats
```
displayFullResults() est appelée.
Les résultats s'affichent dans la section résultats.
```

### Étape 4: Mise à jour des badges
```
updateArretStatusBadges() est appelée automatiquement.
Les badges apparaissent dans la liste des arrêts du formulaire.
L'utilisateur voit maintenant la classification de chaque arrêt.
```

### Étape 5: Modifications
```
L'utilisateur peut modifier les arrêts.
Les badges restent visibles avec les informations du dernier calcul.
Un nouveau calcul mettra à jour les badges.
```

## Avantages

### ✅ Feedback visuel immédiat

L'utilisateur voit directement comment ses arrêts sont classifiés sans avoir à chercher dans les résultats.

### ✅ Meilleure compréhension

Les badges aident à comprendre:
- Pourquoi un arrêt est traité comme rechute
- De quel arrêt spécifique une rechute provient
- Comment les règles métier s'appliquent

### ✅ Cohérence visuelle

Les mêmes codes couleurs sont utilisés:
- Dans la liste des arrêts (formulaire)
- Dans le tableau des résultats
- Dans les explications

### ✅ Navigation facilitée

L'utilisateur peut voir les relations entre arrêts sans scroller jusqu'aux résultats.

## Impact sur les performances

- ✅ **Léger**: Parcours simple du DOM après calcul
- ✅ **Rapide**: Mise à jour instantanée des badges
- ✅ **Pas de requêtes**: Tout en JavaScript côté client

## Compatibilité

- ✅ **Tous les navigateurs modernes**: Chrome, Firefox, Safari, Edge
- ✅ **Responsive**: Les badges s'adaptent à la largeur
- ✅ **Pas de dépendances**: CSS et JavaScript natif

## Tests

### Test manuel

1. Démarrer le serveur: `php -S localhost:8000`
2. Ouvrir `http://localhost:8000`
3. Ajouter 3 arrêts:
   - Arrêt 1: 101 jours
   - Arrêt 2: 30 jours après
   - Arrêt 3: 20 jours après
4. Cliquer sur "Calculer"
5. **Vérifier** que les badges apparaissent:
   - Arrêt 1: `1ère pathologie`
   - Arrêt 2: `🔄 Rechute de l'arrêt #1`
   - Arrêt 3: `🔄 Rechute de l'arrêt #2`

### Test automatisé

```bash
php run_all_tests.php
# ✅ 114/114 tests passent
```

## Fichiers modifiés

1. **app.js**
   - Lignes 297: Ajout du badge dans `addArret()`
   - Lignes 687: Ajout du badge dans `loadMockData()`
   - Lignes 830-866: Nouvelle fonction `updateArretStatusBadges()`
   - Lignes 1176-1178: Appel de la fonction dans `displayFullResults()`

2. **index.html**
   - Lignes 394-401: Ajout du style CSS `.arret-status-badge`

## Documentation associée

- **RECHUTE_INTERFACE_FIX.md**: Fix principal de la détermination des rechutes
- **FRONTEND_RECHUTE_DISPLAY.md**: Affichage dans les résultats
- **RECHUTE_SOURCE_DISPLAY.md**: Affichage de la source des rechutes
- **VISUAL_EXAMPLE.md**: Exemples visuels avant/après
- **ARRET_LIST_BADGES.md**: Cette documentation

## Date: 2024-10-31
