# Décompte dans l'Interface Web - Résumé Visuel

## Avant vs Après

### AVANT (Sans décompte)

```
┌─────────────────────────────────────────────────────────────┐
│ Résultats du calcul IJ                                      │
├─────────────────────────────────────────────────────────────┤
│ Trimestres d'affiliation: 16 trimestres                     │
│ Nombre de jours indemnisables: 31 jours                     │
│ Montant total: 2,326.86 €                                   │
│ Cumul total de jours: 121 jours                             │
└─────────────────────────────────────────────────────────────┘

Détail des paiements par arrêt
┌────┬────────────┬────────────┬────────────┬─────────┬────────────┐
│ N° │ Début      │ Fin        │ Date effet │ Jours   │ Montant    │
│    │ arrêt      │ arrêt      │            │ payés   │            │
├────┼────────────┼────────────┼────────────┼─────────┼────────────┤
│ 1  │ 2024-01-01 │ 2024-04-30 │ 2024-03-31 │   31    │ 2,326.86€  │
└────┴────────────┴────────────┴────────────┴─────────┴────────────┘
```

**Problème** : On ne voit pas les 90 jours qui ont été nécessaires avant le paiement!

---

### APRÈS (Avec décompte) ✨

```
┌─────────────────────────────────────────────────────────────┐
│ Résultats du calcul IJ                                      │
├─────────────────────────────────────────────────────────────┤
│ Trimestres d'affiliation: 16 trimestres                     │
│ Nombre de jours indemnisables: 31 jours                     │
│                                                              │
│ ┌──────────────────────────────────────────────────────┐   │
│ │ ⏱️ Décompte total (jours non payés): 90 jours       │   │
│ │ (fond jaune, texte orange)                           │   │
│ └──────────────────────────────────────────────────────┘   │
│                                                              │
│ Montant total: 2,326.86 €                                   │
│ Cumul total de jours: 121 jours                             │
└─────────────────────────────────────────────────────────────┘

Détail des paiements par arrêt
┌────┬────────────┬────────────┬────────┬────────────┬────────────┬─────────┬────────────┐
│ N° │ Début      │ Fin        │ Durée  │ Décompte   │ Date effet │ Jours   │ Montant    │
│    │ arrêt      │ arrêt      │        │ (non payé) │            │ payés   │            │
├────┼────────────┼────────────┼────────┼────────────┼────────────┼─────────┼────────────┤
│ 1  │ 2024-01-01 │ 2024-04-30 │  121j  │  🟨 90j   │ 2024-03-31 │   31    │ 2,326.86€  │
│    │            │            │        │ (gras)     │            │         │            │
└────┴────────────┴────────────┴────────┴────────────┴────────────┴─────────┴────────────┘

┌──────────────────────────────────────────────────────────────────────────┐
│ ℹ️ Décompte (jours non payés) :                                          │
│                                                                           │
│ Le décompte représente les jours qui comptent vers le seuil             │
│ (90 jours pour nouvelle pathologie, 15 jours pour rechute)              │
│ mais qui ne sont pas payés car situés avant la date d'effet.            │
│                                                                           │
│ Exemple: Un arrêt de 120 jours avec date d'effet au jour 91 aura        │
│ 90 jours de décompte et 30 jours payables.                              │
└──────────────────────────────────────────────────────────────────────────┘
```

**Avantage** : Tout est clair maintenant! 121j = 90j (décompte) + 31j (payés)

---

## Détails des modifications visuelles

### 1. Résumé principal - Nouvelle ligne jaune

**Emplacement** : Entre "Jours indemnisables" et "Montant total"

**Style** :
- Fond : `#fff9e6` (jaune très clair)
- Texte : `#856404` (orange foncé)
- Police : Gras
- Icône : ⏱️

**Condition** : Affiché uniquement si décompte > 0

**Code** :
```javascript
⏱️ Décompte total (jours non payés): 90 jours
```

---

### 2. Tableau détaillé - Nouvelle colonne "Décompte"

**Position** : Après "Durée" et avant "Date effet"

**En-tête** :
```html
<th>Décompte<br>(non payé)</th>
```

**Cellules** :

**Cas 1 : Décompte > 0**
```html
<td style="background-color: #fff3cd; color: #856404; font-weight: bold;">
    90j
</td>
```
- Fond jaune pâle
- Texte orange foncé
- Gras

**Cas 2 : Décompte = 0**
```html
<td style="color: #999;">
    0j
</td>
```
- Texte gris clair
- Pas de fond coloré

---

### 3. Encadré explicatif

**Position** : Sous le tableau, avant "Récapitulatif par période"

**Style** :
- Fond : `#e7f3ff` (bleu très clair)
- Bordure gauche : 4px solide `#667eea` (bleu)
- Padding : 12px
- Border-radius : 4px
- Icône : ℹ️

**Texte** :
- Titre en couleur `#667eea` (bleu)
- Corps en `#555` (gris foncé)
- Taille : 13px
- Exemple en italique

---

## Cas d'usage dans l'interface

### Cas 1 : Nouvelle pathologie (90 jours de décompte)

```
Arrêt #1:
┌───────────────────────────────────────────────────────────┐
│ Période: 2024-01-01 → 2024-04-30                         │
│ Durée: 121j                                               │
│ Décompte (non payé): 🟨 90j  ← Fond jaune, gras         │
│   → Du 2024-01-01 au 2024-03-30 (accumulation)           │
│ Date effet: 2024-03-31                                    │
│ Jours payés: 31                                           │
│ Période de paiement: 2024-03-31 → 2024-04-30            │
│ Montant: 2,326.86 €                                      │
└───────────────────────────────────────────────────────────┘

Calcul:
90 jours (décompte) + 31 jours (payés) = 121 jours (total)
```

### Cas 2 : Rechute (15 jours de décompte)

```
Arrêt #2 (RECHUTE):
┌───────────────────────────────────────────────────────────┐
│ Période: 2024-01-01 → 2024-02-29                         │
│ Durée: 60j                                                │
│ Décompte (non payé): 🟨 14j  ← Plus court pour rechute  │
│   → Du 2024-01-01 au 2024-01-14 (accumulation)           │
│ Date effet: 2024-01-15                                    │
│ Jours payés: 46                                           │
│ Période de paiement: 2024-01-15 → 2024-02-29            │
│ Montant: 3,383.96 €                                      │
└───────────────────────────────────────────────────────────┘

Calcul:
14 jours (décompte) + 46 jours (payés) = 60 jours (total)
```

### Cas 3 : Pas de décompte (date d'effet forcée)

```
Arrêt #3:
┌───────────────────────────────────────────────────────────┐
│ Période: 2024-03-01 → 2024-03-31                         │
│ Durée: 31j                                                │
│ Décompte (non payé): 0j  ← Gris, pas de fond            │
│ Date effet: 2024-03-01  ← Même jour que début           │
│ Jours payés: 31                                           │
│ Période de paiement: 2024-03-01 → 2024-03-31            │
│ Montant: 2,280.22 €                                      │
└───────────────────────────────────────────────────────────┘

Calcul:
0 jours (décompte) + 31 jours (payés) = 31 jours (total)
```

---

## Comment tester dans le navigateur

### Étape 1 : Ouvrir l'interface
```bash
# Dans le terminal
cd /home/mista/work/ij
php -S localhost:8000
```

### Étape 2 : Accéder à l'interface
Ouvrir dans le navigateur :
```
http://localhost:8000/index.html
```

### Étape 3 : Charger un mock
Cliquer sur : **📋 Mock 1** ou **📋 Mock 2**

### Étape 4 : Calculer
Cliquer sur : **💰 Calculer Tout**

### Étape 5 : Observer les résultats

**Vous devriez voir** :
1. ✅ Ligne jaune "Décompte total" dans le résumé
2. ✅ Colonne "Décompte (non payé)" dans le tableau
3. ✅ Cellules jaunes pour décompte > 0
4. ✅ Encadré bleu explicatif sous le tableau

---

## Code couleur - Aide visuelle

```
┌────────────────────────────────────────────────────────┐
│                    LÉGENDE COULEURS                     │
├────────────────────────────────────────────────────────┤
│                                                         │
│ 🟨 Jaune (#fff3cd / #fff9e6)                          │
│    → Décompte (jours non payés)                       │
│    → Accumulation vers le seuil                        │
│                                                         │
│ 🟦 Bleu (#e7f3ff / #667eea)                           │
│    → Information / Explication                         │
│    → Encadré pédagogique                               │
│                                                         │
│ ⚪ Gris (#999)                                          │
│    → Décompte = 0                                      │
│    → Pas d'accumulation nécessaire                     │
│                                                         │
│ 🟩 Vert (#28a745)                                      │
│    → Jours payés                                       │
│    → Statut "Paid"                                     │
│                                                         │
│ 🟥 Rouge (#dc3545)                                     │
│    → Jours non payés (bloqués)                        │
│    → Erreur / Statut bloquant                          │
│                                                         │
└────────────────────────────────────────────────────────┘
```

---

## Vérification rapide

Pour vérifier que tout fonctionne :

```bash
# 1. Lancer les tests PHP
php run_all_tests.php
# Résultat attendu: ✓ ALL TESTS PASSED (114 tests)

# 2. Tester le décompte
php test_decompte.php
# Vérifie que decompte_days est bien calculé

# 3. Tester le décompte rechute
php test_decompte_rechute.php
# Vérifie le seuil de 15 jours pour rechute

# 4. Ouvrir l'interface web
# Charger Mock 1 ou Mock 2 et observer les nouvelles colonnes
```

---

## Résumé des fichiers modifiés

✅ **app.js** - Interface web
- Ligne 896 : Ajout colonne "Décompte"
- Lignes 856-871 : Calcul décompte total
- Lignes 905-912 : Affichage cellules décompte
- Lignes 943-952 : Encadré explicatif

✅ **Services/DateService.php** - Calcul backend
- Lignes 614-646 : Méthode `calculateDecompteDays()`
- Tous les `payment_details` incluent maintenant `decompte_days`

✅ **Documentation**
- DECOMPTE_FEATURE.md - Documentation complète
- WEB_INTERFACE_DECOMPTE.md - Ce fichier (guide visuel)

---

## Formule de vérification

Pour chaque arrêt, vous pouvez vérifier :

```
Durée totale ≥ Décompte + Jours payés

Exemple:
121j (durée) = 90j (décompte) + 31j (payés) ✓
```

**Note** : L'inégalité (≥) est utilisée car les jours payés peuvent être limités par l'attestation ou la date actuelle.
