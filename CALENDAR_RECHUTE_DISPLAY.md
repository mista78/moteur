# Affichage des Rechutes dans le Calendrier

## Vue d'ensemble

Cette documentation décrit l'implémentation complète de l'affichage des relations de rechute dans la vue calendrier, qui permet aux utilisateurs de voir visuellement quels arrêts sont des rechutes et de quels arrêts ils proviennent.

## Objectif

Afficher dans le calendrier:
- 🔄 **Bordures oranges** pour les rechutes
- 🆕 **Bordures vertes** pour les nouvelles pathologies
- 📌 **Pas de bordure spéciale** pour la première pathologie
- **Labels modifiés** pour les dates de début d'arrêt
- **Tooltips informatifs** avec type d'arrêt
- **Légende complète** expliquant les indicateurs visuels

## Implémentation

### 1. Extraction des Données (calendar_functions.js:105-167)

**Fonction `extractCalendarData()`** - Extraction des informations de rechute:

```javascript
// Build a map of arret info (is_rechute, rechute_of_arret_index)
const arretInfo = {};
if (data && data.arrets && Array.isArray(data.arrets)) {
    data.arrets.forEach((arret, index) => {
        arretInfo[index] = {
            is_rechute: arret.is_rechute,
            rechute_of_arret_index: arret.rechute_of_arret_index,
            arret_from: arret['arret-from-line'],
            arret_to: arret['arret-to-line']
        };
    });
}
```

Ces informations sont ensuite passées à chaque entrée de paiement:

```javascript
// Pour les dates de début d'arrêt
payments.push({
    date: detail.arret_from,
    rate: 0,
    amount: 0,
    taux: 0,
    period: 0,
    arret_index: arretIdx,
    arret_from: detail.arret_from || '',
    arret_to: detail.arret_to || '',
    is_arret_start: true,
    is_rechute: info.is_rechute,                          // ← Ajouté
    rechute_of_arret_index: info.rechute_of_arret_index   // ← Ajouté
});

// Pour chaque jour payé
payments.push({
    date: day.date,
    rate: day.daily_rate || 0,
    amount: day.amount || 0,
    taux: day.taux || 0,
    period: day.period || 0,
    arret_index: arretIdx,
    arret_from: detail.arret_from || '',
    arret_to: detail.arret_to || '',
    is_arret_start: false,
    is_rechute: info.is_rechute,                          // ← Ajouté
    rechute_of_arret_index: info.rechute_of_arret_index   // ← Ajouté
});
```

### 2. Affichage dans le Calendrier (calendar_functions.js:216-276)

**Fonction `renderCalendar()`** - Rendu visuel avec bordures et labels:

```javascript
// Add payment info
if (payments.length > 0) {
    payments.forEach(payment => {
        const isArretStart = payment.is_arret_start === true;
        const isPaid = payment.rate > 0;

        let bgColor, displayText, titleText, borderStyle = '';

        // Determine arret type info
        let arretTypeInfo = '';
        if (payment.is_rechute === true) {
            if (payment.rechute_of_arret_index !== undefined && payment.rechute_of_arret_index !== null) {
                arretTypeInfo = ` - 🔄 Rechute de l'arrêt #${payment.rechute_of_arret_index + 1}`;
            } else {
                arretTypeInfo = ' - 🔄 Rechute';
            }
            borderStyle = 'border: 3px solid #ff9800;'; // Orange border for rechute
        } else if (payment.is_rechute === false && payment.arret_index > 0) {
            arretTypeInfo = ' - 🆕 Nouvelle pathologie';
            borderStyle = 'border: 3px solid #4caf50;'; // Green border for new pathology
        } else if (payment.arret_index === 0) {
            arretTypeInfo = ' - 1ère pathologie';
        }

        if (isArretStart) {
            bgColor = '#ffc107';
            let startLabel = '🏥 Début';

            // Add rechute indicator for start date
            if (payment.is_rechute === true) {
                startLabel = '🔄 Début rechute';
                if (payment.rechute_of_arret_index !== undefined && payment.rechute_of_arret_index !== null) {
                    startLabel = `🔄 Rechute #${payment.rechute_of_arret_index + 1}`;
                }
            } else if (payment.is_rechute === false && payment.arret_index > 0) {
                startLabel = '🆕 Nouvelle patho';
            }

            displayText = startLabel;
            titleText = `Arrêt #${payment.arret_index + 1}${arretTypeInfo} - Début: ${payment.arret_from}`;
        } else if (isPaid) {
            bgColor = '#28a745';
            displayText = `${payment.rate.toFixed(2)}€`;
            titleText = `Arrêt #${payment.arret_index + 1}${arretTypeInfo}: ${payment.rate.toFixed(2)}€`;
        } else {
            bgColor = '#dc3545';
            displayText = 'Non payé';
            titleText = `Arrêt #${payment.arret_index + 1}${arretTypeInfo} - Jour non payé (avant droits)`;
        }

        html += `<div class="calendar-payment" style="background: ${bgColor}; ${borderStyle}" title="${titleText}">`;
        html += displayText;
        html += '</div>';
    });
}
```

### 3. Légende Améliorée (calendar_functions.js:59-95)

**Deux sections dans la légende:**

```javascript
html += '<div class=\"calendar-legend\">';
html += '<h4 style=\"margin-bottom: 10px; color: #667eea;\">📅 Légende</h4>';

// Section 1: États des jours
html += '<div style=\"margin-bottom: 15px;\">';
html += '<strong>États des jours:</strong><br>';
html += '<div class=\"calendar-legend-item\">';
html += '<div class=\"calendar-legend-color\" style=\"background: #28a745;\"></div>';
html += '<span>Jour payé</span>';
html += '</div>';
html += '<div class=\"calendar-legend-item\">';
html += '<div class=\"calendar-legend-color\" style=\"background: #dc3545;\"></div>';
html += '<span>Jour non payé (avant droits)</span>';
html += '</div>';
html += '<div class=\"calendar-legend-item\">';
html += '<div class=\"calendar-legend-color\" style=\"background: #ffc107;\"></div>';
html += '<span>Début d\'arrêt</span>';
html += '</div>';
html += '</div>';

// Section 2: Types d'arrêts (bordures)
html += '<div>';
html += '<strong>Types d\'arrêts (bordures):</strong><br>';
html += '<div class=\"calendar-legend-item\">';
html += '<div class=\"calendar-legend-color\" style=\"background: #fff; border: 3px solid #ff9800;\"></div>';
html += '<span>🔄 Rechute</span>';
html += '</div>';
html += '<div class=\"calendar-legend-item\">';
html += '<div class=\"calendar-legend-color\" style=\"background: #fff; border: 3px solid #4caf50;\"></div>';
html += '<span>🆕 Nouvelle pathologie</span>';
html += '</div>';
html += '<div class=\"calendar-legend-item\">';
html += '<div class=\"calendar-legend-color\" style=\"background: #fff; border: 2px solid #ccc;\"></div>';
html += '<span>1ère pathologie</span>';
html += '</div>';
html += '</div>';

html += '</div>';
```

## Visualisation

### Exemple: Mock2 avec 6 arrêts

**Données:**
```
Arrêt 1: 2021-07-19 → 2021-08-30  [1ère pathologie]
Arrêt 2: 2021-12-17 → 2022-01-02  [🆕 Nouvelle pathologie]
Arrêt 3: 2022-10-27 → 2022-11-13  [🆕 Nouvelle pathologie]
Arrêt 4: 2022-11-24 → 2022-12-24  [🆕 Nouvelle pathologie]
Arrêt 5: 2023-09-26 → 2023-10-10  [🔄 Rechute de l'arrêt #4]
Arrêt 6: 2023-11-23 → 2024-03-31  [🔄 Rechute de l'arrêt #5]
```

**Affichage dans le calendrier:**

```
┌─────────────────────────────────────────────────────┐
│  Novembre 2022                                      │
├─────────────────────────────────────────────────────┤
│  L   M   M   J   V   S   D                         │
│                                                     │
│       1   2   3   4   5   6                        │
│  7   8   9  10  11  12  13                         │
│ 14  15  16  17  18  19  20                         │
│ 21  22  23  24  25  26  27  ← Arrêt 3 (vert)      │
│                 ╔══╗                               │
│                 ║13║ vert (nouvelle patho)         │
│                 ╚══╝                               │
│ 24  25  26  27  28  29  30  ← Arrêt 4 commence     │
│     ╔═══════════════════════╗                      │
│     ║🏥 Début               ║ orange (nouvelle)    │
│     ╚═══════════════════════╝                      │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│  Octobre 2023                                       │
├─────────────────────────────────────────────────────┤
│  L   M   M   J   V   S   D                         │
│                                                     │
│       1   2   3   4   5   6   7   8               │
│  9  10  11  12  13  14  15                         │
│ 16  17  18  19  20  21  22                         │
│ 23  24  25  26  27  28  29  ← Arrêt 5 (rechute 4) │
│                 ╔══════════╗                       │
│                 ║🔄 Rechute║ bordure orange        │
│                 ║   #4     ║                       │
│                 ╚══════════╝                       │
│ 30  31                                             │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│  Novembre 2023                                      │
├─────────────────────────────────────────────────────┤
│  L   M   M   J   V   S   D                         │
│                                                     │
│             1   2   3   4   5                      │
│  6   7   8   9  10  11  12                         │
│ 13  14  15  16  17  18  19                         │
│ 20  21  22  23  24  25  26  ← Arrêt 6 (rechute 5) │
│             ╔══════════════╗                       │
│             ║🔄 Rechute #5 ║ bordure orange        │
│             ╚══════════════╝                       │
│ 27  28  29  30                                     │
└─────────────────────────────────────────────────────┘
```

## Tests

### Test Automatisé: test_calendar_display.php

**Résultat du test:**

```
=== TEST CALENDAR DISPLAY - Mock2 (6 arrêts) ===

Analysing 6 arrêts:

Arrêt #1: 2021-07-19 → 2021-08-30
  Date-effet: Pas de date-effet
  Est rechute: NON
  📌 1ère pathologie

Arrêt #2: 2021-12-17 → 2022-01-02
  Date-effet: Pas de date-effet
  Est rechute: NON
  🆕 Nouvelle pathologie

Arrêt #3: 2022-10-27 → 2022-11-13
  Date-effet: Pas de date-effet
  Est rechute: NON
  🆕 Nouvelle pathologie

Arrêt #4: 2022-11-24 → 2022-12-24
  Date-effet: 2024-01-26
  Est rechute: NON
  🆕 Nouvelle pathologie

Arrêt #5: 2023-09-26 → 2023-10-10
  Date-effet: 2024-01-10
  Est rechute: OUI
  🔄 Rechute de l'arrêt #4

Arrêt #6: 2023-11-23 → 2024-03-31
  Date-effet: 2023-12-07
  Est rechute: OUI
  🔄 Rechute de l'arrêt #5
```

**Données extraites pour le calendrier:**

```php
Array(
    [0] => Array(is_rechute => , rechute_of_arret_index => )     // Arrêt 1
    [1] => Array(is_rechute => , rechute_of_arret_index => )     // Arrêt 2
    [2] => Array(is_rechute => , rechute_of_arret_index => )     // Arrêt 3
    [3] => Array(is_rechute => , rechute_of_arret_index => )     // Arrêt 4
    [4] => Array(is_rechute => 1, rechute_of_arret_index => 3)   // Arrêt 5 (rechute de 4)
    [5] => Array(is_rechute => 1, rechute_of_arret_index => 4)   // Arrêt 6 (rechute de 5)
)
```

✅ **Les données sont correctement passées au calendrier!**

### Test Manuel

1. Démarrer le serveur: `php -S localhost:8000`
2. Ouvrir: `http://localhost:8000`
3. Charger un mock avec plusieurs arrêts (ex: mock2.json)
4. Cliquer sur "Calculer"
5. Basculer sur l'onglet "Calendrier"

**Vérifications:**
- ✅ Les jours d'arrêts ont des bordures colorées
- ✅ Les dates de début montrent le type (🔄 Rechute #X, 🆕 Nouvelle patho)
- ✅ Les tooltips affichent l'information complète
- ✅ La légende explique les indicateurs visuels
- ✅ Navigation entre mois fonctionne
- ✅ Cohérence avec les badges de la liste et le tableau résultats

## Code Couleur Unifié

```
╔════════════════════════════════════════════════════════════════════════╗
║  Type d'arrêt       │  Liste arrêts   │  Tableau       │  Calendrier   ║
╠════════════════════════════════════════════════════════════════════════╣
║  1ère pathologie    │  Badge gris     │  Texte gris    │  Pas de       ║
║                     │  #666           │  #666          │  bordure      ║
╠════════════════════════════════════════════════════════════════════════╣
║  🔄 Rechute         │  Badge jaune    │  Fond jaune    │  Bordure      ║
║                     │  #fff3cd        │  #fff3cd       │  orange       ║
║                     │  Texte #856404  │  Texte #856404 │  #ff9800      ║
╠════════════════════════════════════════════════════════════════════════╣
║  🆕 Nouvelle patho. │  Badge vert     │  Fond vert     │  Bordure      ║
║                     │  #d4edda        │  #d4edda       │  verte        ║
║                     │  Texte #155724  │  Texte #155724 │  #4caf50      ║
╚════════════════════════════════════════════════════════════════════════╝
```

## Flux de Données

```
Backend (DateService.php)
    │
    ├─> Calcule is_rechute pour chaque arrêt
    ├─> Identifie rechute_of_arret_index (source de la rechute)
    │
    ▼
API Response (JSON)
    │
    ├─> data.arrets[i].is_rechute
    ├─> data.arrets[i].rechute_of_arret_index
    │
    ▼
Frontend (app.js)
    │
    ├─> displayFullResults() → updateArretStatusBadges()  [Liste arrêts]
    ├─> displayFullResults() → Tableau résultats          [Tableau]
    ├─> generateCalendarView() → extractCalendarData()    [Calendrier]
    │
    ▼
Calendar (calendar_functions.js)
    │
    ├─> extractCalendarData(): Extrait arretInfo
    ├─> renderCalendar(): Affiche avec bordures et labels
    │
    ▼
Affichage Visuel
    │
    ├─> Bordures colorées (orange=rechute, vert=nouvelle)
    ├─> Labels modifiés (🔄 Rechute #X, 🆕 Nouvelle patho)
    ├─> Tooltips informatifs
    └─> Légende complète
```

## Avantages pour l'Utilisateur

### ✅ Vue Temporelle

Le calendrier permet de voir:
- **Quand** les arrêts ont eu lieu
- **Combien de temps** ils ont duré
- **Les relations** entre arrêts (rechutes)
- **Les périodes** de paiement et non-paiement

### ✅ Identification Rapide

Grâce aux bordures colorées:
- **Orange** → Immédiatement visible comme rechute
- **Vert** → Clairement une nouvelle pathologie
- **Pas de bordure** → Première pathologie

### ✅ Compréhension Intuitive

Les labels au survol expliquent:
- Le type d'arrêt
- De quel arrêt provient une rechute
- Les montants payés par jour
- L'état de chaque jour (payé/non payé)

### ✅ Cohérence Visuelle

Même langage visuel partout:
- Liste des arrêts (badges)
- Tableau des résultats (colonne Type)
- Calendrier (bordures et labels)
- Légende explicative

## Compatibilité

- ✅ **Tous navigateurs modernes**: Chrome, Firefox, Safari, Edge
- ✅ **Responsive**: S'adapte à différentes tailles d'écran
- ✅ **Pas de dépendances**: JavaScript natif uniquement
- ✅ **Performance**: Rendu rapide même avec beaucoup d'arrêts

## Fichiers Modifiés

### calendar_functions.js

**Lignes 108-119**: Extraction des informations de rechute
```javascript
const arretInfo = {};
if (data && data.arrets && Array.isArray(data.arrets)) {
    data.arrets.forEach((arret, index) => {
        arretInfo[index] = {
            is_rechute: arret.is_rechute,
            rechute_of_arret_index: arret.rechute_of_arret_index,
            arret_from: arret['arret-from-line'],
            arret_to: arret['arret-to-line']
        };
    });
}
```

**Lignes 138-140, 157-159**: Passage des flags aux entrées de paiement
```javascript
is_rechute: info.is_rechute,
rechute_of_arret_index: info.rechute_of_arret_index
```

**Lignes 232-245**: Détermination du type et style de bordure
```javascript
let arretTypeInfo = '';
if (payment.is_rechute === true) {
    if (payment.rechute_of_arret_index !== undefined && payment.rechute_of_arret_index !== null) {
        arretTypeInfo = ` - 🔄 Rechute de l'arrêt #${payment.rechute_of_arret_index + 1}`;
    } else {
        arretTypeInfo = ' - 🔄 Rechute';
    }
    borderStyle = 'border: 3px solid #ff9800;'; // Orange border for rechute
} else if (payment.is_rechute === false && payment.arret_index > 0) {
    arretTypeInfo = ' - 🆕 Nouvelle pathologie';
    borderStyle = 'border: 3px solid #4caf50;'; // Green border for new pathology
}
```

**Lignes 247-259**: Labels de début modifiés
```javascript
if (isArretStart) {
    bgColor = '#ffc107';
    let startLabel = '🏥 Début';

    if (payment.is_rechute === true) {
        startLabel = '🔄 Début rechute';
        if (payment.rechute_of_arret_index !== undefined && payment.rechute_of_arret_index !== null) {
            startLabel = `🔄 Rechute #${payment.rechute_of_arret_index + 1}`;
        }
    } else if (payment.is_rechute === false && payment.arret_index > 0) {
        startLabel = '🆕 Nouvelle patho';
    }

    displayText = startLabel;
    titleText = `Arrêt #${payment.arret_index + 1}${arretTypeInfo} - Début: ${payment.arret_from}`;
}
```

**Lignes 60-93**: Légende améliorée avec deux sections
```javascript
html += '<div class=\"calendar-legend\">';
html += '<h4 style=\"margin-bottom: 10px; color: #667eea;\">📅 Légende</h4>';

html += '<div style=\"margin-bottom: 15px;\">';
html += '<strong>États des jours:</strong><br>';
// ... états des jours

html += '<div>';
html += '<strong>Types d\'arrêts (bordures):</strong><br>';
// ... types d'arrêts
```

## Documentation Associée

- **RECHUTE_INTERFACE_FIX.md**: Fix backend de détermination des rechutes
- **FRONTEND_RECHUTE_DISPLAY.md**: Affichage dans les résultats
- **RECHUTE_SOURCE_DISPLAY.md**: Affichage de la source des rechutes
- **ARRET_LIST_BADGES.md**: Badges dans la liste des arrêts
- **INTERFACE_BADGES_VISUAL.md**: Visualisation des badges
- **FINAL_SUMMARY_UI_IMPROVEMENTS.md**: Résumé final de toutes les améliorations
- **CALENDAR_RECHUTE_DISPLAY.md**: Cette documentation

## Conclusion

✅ **L'affichage des rechutes dans le calendrier est complet et fonctionnel!**

Le calendrier offre maintenant:
- Visualisation temporelle claire des arrêts
- Identification immédiate des rechutes (bordures oranges)
- Indication de la source des rechutes (🔄 Rechute #X)
- Distinction visuelle des nouvelles pathologies (bordures vertes)
- Tooltips informatifs sur chaque jour
- Légende complète et pédagogique
- Cohérence avec les autres parties de l'interface

**Date: 2024-10-31**
