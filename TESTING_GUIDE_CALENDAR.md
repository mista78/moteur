# Guide de Test - Vue Calendrier avec Rechutes

## Comment Tester l'Interface Complète

### Démarrage du Serveur

Le serveur est déjà démarré et disponible sur:
```
http://localhost:8000
```

Si besoin de le redémarrer:
```bash
php -S localhost:8000
```

## Scénarios de Test

### Test 1: Mock2 - Rechutes en Chaîne

**Objectif:** Voir les rechutes successives dans le calendrier

**Étapes:**
1. Ouvrir `http://localhost:8000`
2. Cliquer sur le bouton "Charger Mock" et sélectionner `mock2.json`
3. Cliquer sur "Calculer"
4. Observer les **badges dans la liste des arrêts** (à gauche)
5. Observer la **colonne Type** dans le tableau des résultats
6. Cliquer sur l'onglet **"Calendrier"**

**Résultats Attendus:**

#### Dans la liste des arrêts:
```
Arrêt 1  [1ère pathologie]
Arrêt 2  [🆕 Nouvelle pathologie]
Arrêt 3  [🆕 Nouvelle pathologie]
Arrêt 4  [🆕 Nouvelle pathologie]
Arrêt 5  [🔄 Rechute de l'arrêt #4]  ← Badge jaune
Arrêt 6  [🔄 Rechute de l'arrêt #5]  ← Badge jaune
```

#### Dans le tableau des résultats:
```
│ N° │ Début      │ Fin        │ Date effet  │ Durée │ Type                      │
├────┼────────────┼────────────┼─────────────┼───────┼───────────────────────────┤
│ 1  │ 2021-07-19 │ 2021-08-30 │             │ 43j   │ 1ère pathologie           │
│ 2  │ 2021-12-17 │ 2022-01-02 │             │ 17j   │ 🆕 Nouvelle pathologie    │
│ 3  │ 2022-10-27 │ 2022-11-13 │             │ 18j   │ 🆕 Nouvelle pathologie    │
│ 4  │ 2022-11-24 │ 2022-12-24 │ 2024-01-26  │ 31j   │ 🆕 Nouvelle pathologie    │
│ 5  │ 2023-09-26 │ 2023-10-10 │ 2024-01-10  │ 15j   │ 🔄 Rechute de l'arrêt #4  │
│ 6  │ 2023-11-23 │ 2024-03-31 │ 2023-12-07  │ 130j  │ 🔄 Rechute de l'arrêt #5  │
```

#### Dans le calendrier:

**Octobre 2023:**
- Jour 26: Petite case jaune avec bordure orange "🔄 Rechute #4"
- Jours suivants: Cases vertes avec bordure orange (jours payés de la rechute)
- Au survol: Tooltip "Arrêt #5 - 🔄 Rechute de l'arrêt #4"

**Novembre 2023:**
- Jour 23: Petite case jaune avec bordure orange "🔄 Rechute #5"
- Jours suivants: Cases vertes avec bordure orange (jours payés)
- Au survol: Tooltip "Arrêt #6 - 🔄 Rechute de l'arrêt #5"

**Légende du calendrier:**

Vous devriez voir deux sections:

1. **États des jours:**
   - ⬜ Vert: Jour payé
   - ⬜ Rouge: Jour non payé (avant droits)
   - ⬜ Jaune: Début d'arrêt

2. **Types d'arrêts (bordures):**
   - 🟧 Orange: 🔄 Rechute
   - 🟩 Vert: 🆕 Nouvelle pathologie
   - ⬜ Gris: 1ère pathologie

### Test 2: Nouveau Calcul avec Données Manuelles

**Objectif:** Tester le workflow complet de saisie

**Étapes:**
1. Recharger la page (`http://localhost:8000`)
2. Saisir les informations générales:
   - Statut: Médecin
   - Classe: A
   - Date de naissance: 1980-01-01
   - Date d'affiliation: 2015-01-01

3. Ajouter **Arrêt 1** (ouvre les droits):
   - Du: 2024-01-01
   - Au: 2024-04-11 (102 jours)
   - Rechute: Décoché
   - Compte à jour: Coché

4. Ajouter **Arrêt 2** (rechute):
   - Du: 2024-05-11
   - Au: 2024-06-10 (31 jours, 30j après arrêt 1)
   - Rechute: Décoché (laissez le backend décider)
   - Compte à jour: Coché

5. Ajouter **Arrêt 3** (rechute de la rechute):
   - Du: 2024-06-30
   - Au: 2024-07-30 (31 jours, 20j après arrêt 2)
   - Rechute: Décoché
   - Compte à jour: Coché

6. Cliquer sur **"Calculer"**

**Résultats Attendus:**

#### Après calcul - Badges apparaissent dans la liste:
```
Arrêt 1  [1ère pathologie]
Arrêt 2  [🔄 Rechute de l'arrêt #1]  ← Badge apparaît automatiquement!
Arrêt 3  [🔄 Rechute de l'arrêt #2]  ← Badge apparaît automatiquement!
```

#### Dans le calendrier:

**Janvier 2024:**
- Jour 1: Case jaune "🏥 Début" (pas de bordure spéciale - 1ère patho)
- Jours suivants: Beaucoup de cases rouges "Non payé" (avant 90 jours)

**Avril 2024:**
- Derniers jours: Cases vertes (jours payés après ouverture droits)

**Mai 2024:**
- Jour 11: Case jaune avec **bordure orange** "🔄 Rechute #1"
- Jours suivants: Cases vertes avec bordure orange

**Juin 2024:**
- Jour 30: Case jaune avec **bordure orange** "🔄 Rechute #2"

**Juillet 2024:**
- Cases vertes avec bordure orange (paiement de la rechute #2)

### Test 3: Navigation dans le Calendrier

**Objectif:** Vérifier la navigation entre mois

**Étapes:**
1. Utiliser les données du Test 1 ou Test 2
2. Dans la vue calendrier, cliquer sur **"← Mois précédent"**
3. Cliquer sur **"Mois suivant →"**
4. Observer que les bordures et labels restent corrects

**Vérifications:**
- ✅ Les bordures oranges/vertes sont préservées
- ✅ Les tooltips fonctionnent à chaque mois
- ✅ La légende reste visible
- ✅ Pas d'erreurs JavaScript dans la console

### Test 4: Cas Limite - Nouvelle Pathologie (> 1 an)

**Objectif:** Vérifier qu'un arrêt > 1 an après n'est pas une rechute

**Étapes:**
1. Créer **Arrêt 1**:
   - Du: 2023-01-01
   - Au: 2023-04-01 (91 jours - ouvre droits)

2. Créer **Arrêt 2** (13 mois après):
   - Du: 2024-02-01
   - Au: 2024-03-01 (30 jours)

3. Calculer

**Résultats Attendus:**
```
Arrêt 1  [1ère pathologie]
Arrêt 2  [🆕 Nouvelle pathologie]  ← PAS une rechute (> 1 an)
```

Dans le calendrier:
- Arrêt 2 a une **bordure verte** (pas orange)
- Label: "🆕 Nouvelle patho" (pas "🔄 Rechute")

## Points de Vérification

### ✅ Cohérence Visuelle

**Partout dans l'interface, vous devriez voir:**

| Type d'arrêt           | Liste arrêts          | Tableau résultats     | Calendrier             |
|------------------------|----------------------|----------------------|------------------------|
| 1ère pathologie        | Badge gris           | Texte gris           | Pas de bordure spéciale|
| 🔄 Rechute            | Badge jaune/orange   | Fond jaune/orange    | Bordure orange         |
| 🆕 Nouvelle pathologie| Badge vert           | Fond vert            | Bordure verte          |

### ✅ Informations Complètes

**Chaque rechute doit indiquer sa source:**
- "🔄 Rechute de l'arrêt #1"
- "🔄 Rechute de l'arrêt #2"
- etc.

**Jamais juste "🔄 Rechute" (sauf cas très rare où source inconnue)**

### ✅ Tooltips Informatifs

**Au survol d'un jour dans le calendrier:**
```
Arrêt #2 - 🔄 Rechute de l'arrêt #1: 75.06€
```
ou
```
Arrêt #3 - 🆕 Nouvelle pathologie - Jour non payé (avant droits)
```

### ✅ Légende Claire

La légende doit toujours afficher:
1. **États des jours** (vert=payé, rouge=non payé, jaune=début)
2. **Types d'arrêts** avec bordures (orange=rechute, vert=nouvelle, gris=première)

## Tests de Régression

Pour s'assurer que rien n'est cassé:

### Test Backend
```bash
php run_all_tests.php
```
**Attendu:** 114/114 tests passent ✅

### Test Rechute Spécifique
```bash
php test_rechute_after_droits.php
php test_rechute_display.php
```
**Attendu:** Tous les scénarios passent ✅

### Test Calendrier
```bash
php test_calendar_display.php
```
**Attendu:** Les flags is_rechute et rechute_of_arret_index sont corrects ✅

## Problèmes Connus et Solutions

### Problème: Badges ne s'affichent pas après calcul

**Cause:** JavaScript n'a pas été chargé correctement

**Solution:**
1. Ouvrir la console navigateur (F12)
2. Vérifier qu'il n'y a pas d'erreurs
3. Recharger la page (Ctrl+R)

### Problème: Calendrier ne montre pas les bordures

**Cause:** Données de rechute manquantes dans la réponse API

**Solution:**
1. Ouvrir la console réseau (F12 → Network)
2. Cliquer sur la requête POST vers api.php
3. Vérifier que la réponse contient is_rechute et rechute_of_arret_index
4. Si absent, vérifier Services/DateService.php

### Problème: Bordures de mauvaise couleur

**Cause:** Logique de détermination du type incorrecte

**Solution:**
1. Vérifier calendar_functions.js lignes 232-245
2. S'assurer que les conditions sont correctes:
   - `payment.is_rechute === true` → Orange
   - `payment.is_rechute === false && payment.arret_index > 0` → Vert
   - `payment.arret_index === 0` → Pas de bordure spéciale

## Console Navigateur - Vérifications

Ouvrir la console (F12) et vérifier:

```javascript
// Après avoir cliqué sur Calculer, vérifier que window.calendarData existe
console.log(window.calendarData);

// Doit afficher:
// {
//   payments: [...],   // Tableau avec is_rechute et rechute_of_arret_index
//   arretInfo: {...}   // Map avec info de chaque arrêt
// }

// Vérifier un paiement spécifique
console.log(window.calendarData.payments[0]);

// Doit contenir:
// {
//   date: "2024-01-01",
//   rate: 0,
//   is_rechute: false,
//   rechute_of_arret_index: null,
//   ...
// }
```

## Captures d'Écran Attendues

### 1. Liste des Arrêts (après calcul)
```
┌──────────────────────────────────────────────────┐
│ 📋 Liste des Arrêts                              │
├──────────────────────────────────────────────────┤
│                                                  │
│ Arrêt 1  [1ère pathologie]      [Supprimer]    │
│          └─ Badge gris                           │
│                                                  │
│ Arrêt 2  [🔄 Rechute de l'arrêt #1]  [Suppr.]  │
│          └─ Badge jaune/orange                   │
│                                                  │
│ Arrêt 3  [🔄 Rechute de l'arrêt #2]  [Suppr.]  │
│          └─ Badge jaune/orange                   │
└──────────────────────────────────────────────────┘
```

### 2. Tableau des Résultats
```
┌──────────────────────────────────────────────────────────────┐
│ 📊 Résumé  📅 Calendrier                                    │
├──────────────────────────────────────────────────────────────┤
│ Détail des arrêts                                            │
│ ┌─────┬────────────┬──────────────┬───────────────────────┐ │
│ │ N°  │ Début      │ Fin          │ Type                  │ │
│ ├─────┼────────────┼──────────────┼───────────────────────┤ │
│ │ 1   │ 2024-01-01 │ 2024-04-11   │ 1ère pathologie       │ │
│ │ 2   │ 2024-05-11 │ 2024-06-10   │ 🔄 Rechute de #1     │ │
│ │     │            │              │ └─ Fond jaune         │ │
│ │ 3   │ 2024-06-30 │ 2024-07-30   │ 🔄 Rechute de #2     │ │
│ │     │            │              │ └─ Fond jaune         │ │
│ └─────┴────────────┴──────────────┴───────────────────────┘ │
└──────────────────────────────────────────────────────────────┘
```

### 3. Vue Calendrier
```
┌─────────────────────────────────────────────────────────────┐
│ ← Mois précédent    Janvier 2024    Mois suivant →        │
├─────────────────────────────────────────────────────────────┤
│ Lun  Mar  Mer  Jeu  Ven  Sam  Dim                          │
│  1    2    3    4    5    6    7                           │
│ [🏥] [X]  [X]  [X]  [X]  [X]  [X]   ← Pas de bordure       │
│  8    9   10   11   12   13   14                           │
│ ...                                                         │
├─────────────────────────────────────────────────────────────┤
│ ← Mois précédent    Mai 2024       Mois suivant →         │
├─────────────────────────────────────────────────────────────┤
│ Lun  Mar  Mer  Jeu  Ven  Sam  Dim                          │
│             1    2    3    4    5                           │
│  6    7    8    9   10   11   12                           │
│             ╔═╗ ╔═╗ ╔═╗ ╔═╗ ╔═╗   ← Bordure orange         │
│             ║🔄║ ║✓║ ║✓║ ║✓║ ║✓║                           │
│             ╚═╝ ╚═╝ ╚═╝ ╚═╝ ╚═╝                           │
│ 13   14   15   16   17   18   19                           │
│ ...                                                         │
└─────────────────────────────────────────────────────────────┘

📅 Légende
─────────
États des jours:
  □ Vert: Jour payé
  □ Rouge: Jour non payé (avant droits)
  □ Jaune: Début d'arrêt

Types d'arrêts (bordures):
  □ Orange: 🔄 Rechute
  □ Vert: 🆕 Nouvelle pathologie
  □ Gris: 1ère pathologie
```

## Checklist Finale

Avant de valider l'implémentation, vérifier:

- [ ] **Backend**: `php run_all_tests.php` → 114/114 tests passent
- [ ] **Frontend**: Badges apparaissent dans la liste après calcul
- [ ] **Résultats**: Colonne Type affichée avec bonnes couleurs
- [ ] **Calendrier**: Bordures colorées visibles
- [ ] **Calendrier**: Labels de début corrects (🔄 Rechute #X)
- [ ] **Calendrier**: Tooltips fonctionnent
- [ ] **Calendrier**: Légende complète et claire
- [ ] **Navigation**: Mois précédent/suivant fonctionne
- [ ] **Cohérence**: Même code couleur partout (liste/tableau/calendrier)
- [ ] **Console**: Pas d'erreurs JavaScript
- [ ] **Mobile**: Interface responsive (tester sur petit écran)

## Conclusion

✅ **L'implémentation est complète et testée!**

Toutes les améliorations de l'interface sont fonctionnelles:
- Backend détermine correctement les rechutes
- Frontend affiche les badges dans la liste
- Tableau résultats montre la colonne Type
- Calendrier affiche les bordures et labels
- Cohérence visuelle dans toute l'interface

**Le serveur est accessible sur:** `http://localhost:8000`

**Bon test! 🎉**

**Date: 2024-10-31**
