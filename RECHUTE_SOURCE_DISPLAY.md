# Rechute Source Display Feature

## Vue d'ensemble

Cette fonctionnalité améliore l'affichage dans l'interface web pour montrer **de quel arrêt** un arrêt est une rechute. Au lieu d'afficher simplement "🔄 Rechute", l'interface affiche maintenant "🔄 Rechute de l'arrêt #X".

## Motivation

Dans les cas où il y a plusieurs arrêts, il est important de savoir:
- Quel arrêt a ouvert les droits initialement?
- De quel arrêt spécifique une rechute est-elle issue?
- Quelle est la chaîne de relations entre les arrêts?

## Modifications

### Backend (Services/DateService.php)

**Lignes 341-350** - Identification de l'arrêt source:

```php
// Si c'est une rechute, identifier de quel arrêt (le dernier avec date-effet)
if ($siRechute) {
    // Trouver le dernier arrêt précédent qui a une date-effet
    for ($i = $increment - 1; $i >= 0; $i--) {
        if (isset($arrets[$i]['date-effet']) && !empty($arrets[$i]['date-effet'])) {
            $currentData['rechute_of_arret_index'] = $i;
            break;
        }
    }
}
```

**Logique:**
- Quand un arrêt est déterminé comme rechute
- On remonte les arrêts précédents
- On trouve le **dernier arrêt avec date-effet** (droits ouverts)
- On stocke son index dans `rechute_of_arret_index`

### Frontend (app.js)

**Lignes 1081-1089** - Affichage de la source:

```javascript
if (arret.is_rechute === true) {
    // Show which arret this is a rechute of
    if (arret.rechute_of_arret_index !== undefined && arret.rechute_of_arret_index !== null) {
        const sourceArretNum = arret.rechute_of_arret_index + 1; // +1 for human-readable numbering
        typeLabel = `🔄 Rechute de l'arrêt #${sourceArretNum}`;
    } else {
        typeLabel = '🔄 Rechute';
    }
    typeStyle = 'background-color: #fff3cd; color: #856404; font-weight: bold;';
}
```

**Lignes 1115-1116** - Mise à jour de l'explication:

```javascript
<strong>🔄 Rechute de l'arrêt #X :</strong> Indique que cet arrêt est une rechute de l'arrêt #X
(le dernier arrêt avec droits ouverts). Droits déjà ouverts + arrêt < 1 an après → Paiement dès le 15ème jour
```

## Exemples de scénarios

### Scénario 1: Rechutes multiples d'un même arrêt

```
Arrêt 1: 101 jours → Date effet: 2024-03-31 (DROITS OUVERTS)
Arrêt 2:  31 jours → Date effet: 2024-05-25 (🔄 Rechute de l'arrêt #1)
Arrêt 3:  31 jours → Date effet: 2024-07-14 (🔄 Rechute de l'arrêt #2)
```

**Analyse:**
- Arrêt 2 est une rechute de l'arrêt #1 (qui a ouvert les droits)
- Arrêt 3 est une rechute de l'arrêt #2 (maintenant l'arrêt #2 a aussi une date-effet)

### Scénario 2: Accumulation puis rechute

```
Arrêt 1:  47 jours → Pas de date-effet (1ère pathologie)
Arrêt 2:  51 jours → Date effet: 2024-04-13 (🆕 Nouvelle pathologie - cumul 98j)
Arrêt 3:  32 jours → Date effet: 2024-05-29 (🔄 Rechute de l'arrêt #2)
```

**Analyse:**
- Arrêt 1: Pas assez de jours pour ouvrir les droits
- Arrêt 2: Accumule avec arrêt 1, atteint 98 jours, ouvre les droits → "Nouvelle pathologie"
- Arrêt 3: Rechute de l'arrêt #2 (le dernier avec date-effet, pas arrêt #1 qui n'a pas de date-effet)

### Scénario 3: Chaîne de rechutes

```
Arrêt 1: 120 jours → Date effet: 2024-03-20 (1ère pathologie)
Arrêt 2:  40 jours → Date effet: 2024-05-25 (🔄 Rechute de l'arrêt #1)
Arrêt 3:  35 jours → Date effet: 2024-07-09 (🔄 Rechute de l'arrêt #2)
Arrêt 4:  30 jours → Date effet: 2024-08-13 (🔄 Rechute de l'arrêt #3)
```

**Analyse:**
- Chaque arrêt est une rechute du précédent
- Tous les arrêts ont une date-effet
- Chaque rechute référence l'arrêt immédiatement précédent (le dernier avec date-effet)

## Affichage dans l'interface

### Tableau des arrêts

Avant:
```
┌────┬────────────┬────────────┬─────────────┬────────┬──────────────┐
│ N° │ Début      │ Fin        │ Date effet  │ Durée  │ Type         │
├────┼────────────┼────────────┼─────────────┼────────┼──────────────┤
│ 1  │ 2024-01-01 │ 2024-04-11 │ 2024-03-31  │ 102j   │ 1ère pathol. │
│ 2  │ 2024-05-11 │ 2024-06-10 │ 2024-05-25  │ 31j    │ 🔄 Rechute   │
│ 3  │ 2024-06-30 │ 2024-07-30 │ 2024-07-14  │ 31j    │ 🔄 Rechute   │
└────┴────────────┴────────────┴─────────────┴────────┴──────────────┘
```

Après (avec source):
```
┌────┬────────────┬────────────┬─────────────┬────────┬──────────────────────────┐
│ N° │ Début      │ Fin        │ Date effet  │ Durée  │ Type                     │
├────┼────────────┼────────────┼─────────────┼────────┼──────────────────────────┤
│ 1  │ 2024-01-01 │ 2024-04-11 │ 2024-03-31  │ 102j   │ 1ère pathologie          │
│ 2  │ 2024-05-11 │ 2024-06-10 │ 2024-05-25  │ 31j    │ 🔄 Rechute de l'arrêt #1 │
│ 3  │ 2024-06-30 │ 2024-07-30 │ 2024-07-14  │ 31j    │ 🔄 Rechute de l'arrêt #2 │
└────┴────────────┴────────────┴─────────────┴────────┴──────────────────────────┘
```

## Avantages

✅ **Clarté**: L'utilisateur voit immédiatement de quel arrêt une rechute provient

✅ **Traçabilité**: Permet de suivre la chaîne de causalité entre les arrêts

✅ **Compréhension**: Aide à comprendre pourquoi un arrêt est traité comme rechute

✅ **Débogage**: Facilite la vérification de la logique de détermination

✅ **Pédagogique**: Montre concrètement comment les règles métier s'appliquent

## Tests

### Test automatisé

```bash
php test_rechute_display.php
```

**Résultats attendus:**
- Scénario 1: Rechutes en chaîne
  - Arrêt 2 → Rechute de l'arrêt #1
  - Arrêt 3 → Rechute de l'arrêt #2

- Scénario 2: Accumulation puis rechute
  - Arrêt 1 → Première pathologie (pas de date-effet)
  - Arrêt 2 → Nouvelle pathologie (atteint le seuil)
  - Arrêt 3 → Rechute de l'arrêt #2 (pas #1)

### Test manuel

1. Démarrer le serveur: `php -S localhost:8000`
2. Ouvrir `http://localhost:8000`
3. Charger mock2.json (plusieurs arrêts)
4. Vérifier la colonne "Type" dans "Détail des arrêts"
5. Vérifier que les rechutes affichent "de l'arrêt #X"

### Tous les tests passent

```bash
php run_all_tests.php
# ✅ 114/114 tests passent
```

## Structure des données

### Données backend (JSON)

```json
{
  "arrets": [
    {
      "arret-from-line": "2024-01-01",
      "arret-to-line": "2024-04-11",
      "date-effet": "2024-03-31",
      "is_rechute": false
    },
    {
      "arret-from-line": "2024-05-11",
      "arret-to-line": "2024-06-10",
      "date-effet": "2024-05-25",
      "is_rechute": true,
      "rechute_of_arret_index": 0  // ← Nouvelle propriété
    },
    {
      "arret-from-line": "2024-06-30",
      "arret-to-line": "2024-07-30",
      "date-effet": "2024-07-14",
      "is_rechute": true,
      "rechute_of_arret_index": 1  // ← Référence l'arrêt #2
    }
  ]
}
```

## Compatibilité

- ✅ **Rétrocompatible**: Si `rechute_of_arret_index` est absent, affiche "🔄 Rechute" (sans source)
- ✅ **Tests existants**: Tous les 114 tests passent sans modification
- ✅ **API**: Pas de changement des endpoints
- ✅ **Calculs**: Aucun impact sur les calculs de montants

## Impact sur les performances

- ✅ **Minimal**: Boucle simple O(n) pour trouver l'arrêt source
- ✅ **Pas de requêtes supplémentaires**: Calcul en mémoire
- ✅ **Pas de latence**: Ajout instantané lors du traitement

## Documentation mise à jour

- ✅ **RECHUTE_INTERFACE_FIX.md**: Documentation principale
- ✅ **FRONTEND_RECHUTE_DISPLAY.md**: Affichage visuel
- ✅ **RECHUTE_SOURCE_DISPLAY.md**: Cette documentation
- ✅ **test_rechute_display.php**: Tests de validation

## Date: 2024-10-31
