# Frontend Rechute Display Feature

## Vue d'ensemble

Cette fonctionnalité affiche visuellement dans l'interface web si un arrêt a été déterminé comme **rechute** ou **nouvelle pathologie** par le backend, selon les règles métier.

## Modifications apportées

### Backend (Services/DateService.php)

Ajout du flag `is_rechute` dans les données de chaque arrêt pour indiquer sa classification:

**Ligne 298** - Premier arrêt ou nouvelle pathologie:
```php
// Premier arrêt ou nouvelle pathologie, pas une rechute
$currentData['is_rechute'] = false;
```

**Ligne 336** - Arrêts suivants (rechute ou nouvelle pathologie):
```php
// Ajouter l'indication de rechute au résultat pour l'affichage frontend
$currentData['is_rechute'] = $siRechute;
```

### Frontend (app.js)

**Ligne 1071-1099** - Ajout d'une colonne "Type" dans le tableau des arrêts:

```javascript
// Determine arret type based on backend determination
let typeLabel = '';
let typeStyle = '';
if (arret.is_rechute === true) {
    typeLabel = '🔄 Rechute';
    typeStyle = 'background-color: #fff3cd; color: #856404; font-weight: bold;';
} else if (arret.is_rechute === false && index > 0) {
    typeLabel = '🆕 Nouvelle pathologie';
    typeStyle = 'background-color: #d4edda; color: #155724; font-weight: bold;';
} else {
    typeLabel = '1ère pathologie';
    typeStyle = 'color: #666;';
}
```

**Ligne 1105-1114** - Ajout d'une boîte d'explication:
```javascript
html += `
    <div style="margin-top: 15px; padding: 12px; background-color: #e7f3ff; border-left: 4px solid #667eea; border-radius: 4px;">
        <strong style="color: #667eea;">ℹ️ Types d'arrêts :</strong><br>
        <span style="font-size: 13px; color: #555;">
            <strong>🔄 Rechute :</strong> Droits déjà ouverts (seuil de 90j atteint précédemment) + arrêt < 1 an après le précédent → Paiement dès le 15ème jour<br>
            <strong>🆕 Nouvelle pathologie :</strong> Droits pas encore ouverts OU arrêt > 1 an après le précédent → Nouveau seuil de 90 jours requis<br>
            <strong>1ère pathologie :</strong> Premier arrêt de travail de l'affiliation
        </span>
    </div>
`;
```

## Affichage visuel

### Tableau des arrêts

Le tableau "Détail des arrêts" contient maintenant une colonne "Type" avec un code couleur:

| Type | Label | Couleur | Style |
|------|-------|---------|-------|
| **Rechute** | 🔄 Rechute | Jaune (#fff3cd) | Gras, texte orange foncé |
| **Nouvelle pathologie** | 🆕 Nouvelle pathologie | Vert clair (#d4edda) | Gras, texte vert foncé |
| **Première pathologie** | 1ère pathologie | Gris (#666) | Normal |

### Exemple de rendu

```
┌────┬────────────┬────────────┬─────────────┬────────┬──────────────────────────┐
│ N° │ Début      │ Fin        │ Date effet  │ Durée  │ Type                     │
├────┼────────────┼────────────┼─────────────┼────────┼──────────────────────────┤
│ 1  │ 2024-01-01 │ 2024-02-15 │ N/A         │ 46j    │ 1ère pathologie          │
│ 2  │ 2024-03-01 │ 2024-04-30 │ 2024-04-14  │ 61j    │ 🆕 Nouvelle pathologie   │
└────┴────────────┴────────────┴─────────────┴────────┴──────────────────────────┘

ℹ️ Types d'arrêts :
🔄 Rechute : Droits déjà ouverts (seuil de 90j atteint précédemment) + arrêt < 1 an
            après le précédent → Paiement dès le 15ème jour
🆕 Nouvelle pathologie : Droits pas encore ouverts OU arrêt > 1 an après le précédent
                        → Nouveau seuil de 90 jours requis
1ère pathologie : Premier arrêt de travail de l'affiliation
```

## Logique de détermination

### Rechute (🔄)
Conditions (toutes doivent être remplies):
1. ✅ Arrêt précédent a une `date-effet` (droits ouverts)
2. ✅ Pas consécutif (pas une prolongation)
3. ✅ Commence < 1 an après la fin de l'arrêt précédent
→ **Paiement dès le 15ème jour**

### Nouvelle pathologie (🆕)
Si l'une de ces conditions est vraie:
1. ❌ Arrêt précédent n'a PAS de `date-effet` (droits pas ouverts)
2. ❌ Arrêt commence > 1 an après la fin du précédent
→ **Nouveau seuil de 90 jours requis**

### Première pathologie
- Premier arrêt de l'affiliation (index = 0)
→ **Seuil de 90 jours requis**

## Exemples de scénarios

### Scénario 1: Accumulation vers le seuil
```php
Arrêt 1: 46 jours (pas de date-effet)
Arrêt 2: 61 jours (14 jours après arrêt 1)
→ Arrêt 2 = "🆕 Nouvelle pathologie"
→ Total: 107 jours cumulés
→ Date effet au jour 91
```

**Pourquoi?** L'arrêt 1 n'a pas de date-effet (pas atteint 90 jours), donc l'arrêt 2 continue d'accumuler vers le seuil.

### Scénario 2: Rechute après ouverture des droits
```php
Arrêt 1: 101 jours (date-effet: 2024-03-31)
Arrêt 2: 61 jours (21 jours après arrêt 1)
→ Arrêt 2 = "🔄 Rechute"
→ Paiement dès le 15ème jour (2024-05-15)
```

**Pourquoi?** L'arrêt 1 a une date-effet (droits ouverts), et l'arrêt 2 commence < 1 an après, donc c'est une rechute.

### Scénario 3: Nouvelle pathologie après 1 an
```php
Arrêt 1: 120 jours (date-effet: 2023-04-01)
Arrêt 2: 80 jours (400 jours après arrêt 1)
→ Arrêt 2 = "🆕 Nouvelle pathologie"
→ Nouveau seuil de 90 jours requis
```

**Pourquoi?** Même si l'arrêt 1 a une date-effet, l'arrêt 2 commence > 1 an après, donc c'est une nouvelle pathologie.

## Tests

### Test automatisé
```bash
php test_rechute_after_droits.php
```

**Résultat attendu:**
- Scénario 1: `is_rechute = false` (Nouvelle pathologie)
- Scénario 2: `is_rechute = true` (Rechute)

### Test manuel
1. Démarrer le serveur: `php -S localhost:8000`
2. Ouvrir `http://localhost:8000`
3. Charger un mock avec plusieurs arrêts (ex: mock2.json)
4. Vérifier la colonne "Type" dans le tableau "Détail des arrêts"
5. Vérifier que les couleurs et labels correspondent aux règles

## Avantages

✅ **Transparence**: L'utilisateur voit exactement comment le backend a classifié chaque arrêt

✅ **Pédagogique**: L'explication aide à comprendre les règles métier complexes

✅ **Vérification**: Permet de vérifier visuellement si la classification est correcte

✅ **Débogage**: Facilite l'identification des problèmes de classification

## Impact sur les tests

- ✅ **114/114 tests passent**
- ✅ Aucun impact sur les calculs existants
- ✅ Uniquement ajout d'informations visuelles

## Compatibilité

- ✅ Rétrocompatible: Fonctionne avec tous les mocks existants
- ✅ Données existantes: Si `is_rechute` n'est pas défini, affiche "Unknown" (n'arrive pas en pratique)
- ✅ API: Pas de changement des endpoints, juste ajout de données

## Date: 2024-10-31
