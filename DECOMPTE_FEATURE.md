# Fonctionnalité Décompte (Jours Non Payés)

## Vue d'ensemble

La fonctionnalité "Décompte" affiche les **jours non payés** qui s'accumulent avant que le paiement ne commence (avant la date d'effet).

## Qu'est-ce que le décompte ?

Le **décompte** représente les jours d'arrêt qui :
- ✅ **Comptent** vers le seuil requis (90 jours ou 15 jours)
- ❌ **Ne sont PAS payés** (situés avant la date d'effet)
- 📊 **S'accumulent** pour déterminer quand le paiement commence

## Règles métier

### Nouvelle pathologie
- **Seuil** : 90 jours de décompte
- **Paiement** : Commence au 91ème jour (date d'effet)
- **Exemple** : Un arrêt de 120 jours aura 90 jours de décompte + 30 jours payables

### Rechute (< 1 an après précédent arrêt)
- **Seuil** : 15 jours de décompte
- **Paiement** : Commence au 15ème jour (date d'effet)
- **Exemple** : Un arrêt rechute de 60 jours aura 14 jours de décompte + 46 jours payables

## Affichage dans l'interface web

### 1. Résumé principal

Dans la section résultats, une nouvelle ligne affiche :

```
⏱️ Décompte total (jours non payés): XX jours
```

- Fond jaune clair (`#fff9e6`)
- Texte orange foncé (`#856404`)
- Affiché uniquement si > 0

### 2. Tableau détaillé par arrêt

Une nouvelle colonne **"Décompte (non payé)"** montre :

| Colonne | Description |
|---------|-------------|
| **Durée** | Nombre total de jours de l'arrêt |
| **Décompte (non payé)** | Jours avant date d'effet (non payés) |
| **Date effet** | Date de début de paiement |
| **Jours payés** | Jours effectivement payables |

**Code couleur :**
- **> 0 jours** : Fond jaune (`#fff3cd`), texte orange (`#856404`), gras
- **0 jours** : Texte gris (`#999`)

### 3. Encadré explicatif

Sous le tableau, un encadré bleu explique :

```
ℹ️ Décompte (jours non payés) :
Le décompte représente les jours qui comptent vers le seuil (90 jours pour
nouvelle pathologie, 15 jours pour rechute) mais qui ne sont pas payés car
situés avant la date d'effet.

Exemple: Un arrêt de 120 jours avec date d'effet au jour 91 aura 90 jours
de décompte et 30 jours payables.
```

## Exemples visuels

### Exemple 1 : Nouvelle pathologie

```
Arrêt #1:
  Période: 2024-01-01 → 2024-04-30
  Durée: 121j
  Décompte (non payé): 90j      ← Visible en jaune
  Date effet: 2024-03-31
  Jours payés: 31
```

**Calcul** :
- Du 2024-01-01 au 2024-03-30 = 90 jours (décompte)
- Du 2024-03-31 au 2024-04-30 = 31 jours (payés)
- Total = 121 jours

### Exemple 2 : Rechute

```
Arrêt #2 (RECHUTE):
  Période: 2024-01-01 → 2024-02-29
  Durée: 60j
  Décompte (non payé): 14j      ← Visible en jaune
  Date effet: 2024-01-15
  Jours payés: 46
```

**Calcul** :
- Du 2024-01-01 au 2024-01-14 = 14 jours (décompte)
- Du 2024-01-15 au 2024-02-29 = 46 jours (payés)
- Total = 60 jours

### Exemple 3 : Paiement immédiat (date d'effet forcée)

```
Arrêt #3:
  Période: 2024-03-01 → 2024-03-31
  Durée: 31j
  Décompte (non payé): 0j       ← Grisé
  Date effet: 2024-03-01
  Jours payés: 31
```

**Calcul** :
- Pas de décompte car date d'effet = début d'arrêt
- Tous les jours sont payables

## Modifications techniques

### Fichiers modifiés

#### 1. Services/DateService.php

**Nouvelle méthode** : `calculateDecompteDays(array $arret): int`

```php
/**
 * Calculer les jours de décompte (avant date d'effet) pour un arrêt
 * Ce sont les jours qui comptent vers le seuil mais ne sont pas payés
 */
private function calculateDecompteDays(array $arret): int
{
    // Si pas de date d'effet, tous les jours sont en décompte
    if (!isset($arret['date-effet']) || empty($arret['date-effet'])) {
        return $arret['arret_diff'] ?? 0;
    }

    $startDate = new DateTime($arret['arret-from-line']);
    $dateEffet = new DateTime($arret['date-effet']);

    // Si date d'effet ≤ début, pas de décompte
    if ($dateEffet <= $startDate) {
        return 0;
    }

    // Décompte = jours du début jusqu'au jour avant date d'effet
    $dayBeforeEffet = clone $dateEffet;
    $dayBeforeEffet->modify('-1 day');

    return $startDate->diff($dayBeforeEffet)->days + 1;
}
```

**Champ ajouté** : `decompte_days` dans tous les `payment_details`

#### 2. app.js

**Modifications** :

1. **Ligne 896** : Nouvelle colonne dans le tableau
   ```javascript
   '<th>Décompte<br>(non payé)</th>'
   ```

2. **Lignes 905-912** : Affichage du décompte avec code couleur
   ```javascript
   if (detail.decompte_days !== undefined && detail.decompte_days > 0) {
       decompteHtml = `<td style="background-color: #fff3cd; color: #856404; font-weight: bold;">
           ${detail.decompte_days}j
       </td>`;
   } else {
       decompteHtml = `<td style="color: #999;">0j</td>`;
   }
   ```

3. **Lignes 856-871** : Calcul et affichage du décompte total
   ```javascript
   let totalDecompte = 0;
   data.payment_details.forEach((detail) => {
       if (detail.decompte_days) {
           totalDecompte += detail.decompte_days;
       }
   });
   ```

4. **Lignes 943-952** : Encadré explicatif

## Tests

### Tests automatisés
✅ 114 tests passent (0 échecs)
- Tests unitaires : DateService, AmountCalculationService
- Tests d'intégration : Mocks réels

### Tests manuels

**Fichiers de test créés** :
- `test_decompte.php` - Démonstration nouvelle pathologie
- `test_decompte_rechute.php` - Démonstration rechute

**Commandes** :
```bash
# Test nouvelle pathologie (90 jours)
php test_decompte.php

# Test rechute (15 jours)
php test_decompte_rechute.php

# Tous les tests
php run_all_tests.php
```

## Utilisation

### Dans l'interface web

1. **Ouvrir** : `index.html` dans un navigateur
2. **Charger** : Un mock ou saisir des données
3. **Calculer** : Cliquer sur "Calculer"
4. **Observer** :
   - Décompte total dans le résumé
   - Décompte par arrêt dans le tableau détaillé
   - Explication dans l'encadré bleu

### Via l'API PHP

```php
$calculator = new IJCalculator('taux.csv');
$result = $calculator->calculateAmount($data);

// Accéder au décompte
foreach ($result['payment_details'] as $detail) {
    echo "Décompte: {$detail['decompte_days']} jours\n";
}
```

## Bénéfices

1. **Transparence** : Les utilisateurs voient clairement pourquoi certains jours ne sont pas payés
2. **Compréhension** : Distinction claire entre accumulation et paiement
3. **Vérification** : Facilite la validation des calculs (Durée = Décompte + Payés)
4. **Diagnostic** : Aide à identifier les problèmes de date d'effet

## Notes

- Le décompte est **toujours ≥ 0**
- Le décompte est **≤ durée totale de l'arrêt**
- **Durée totale = Décompte + Jours payables** (peut différer à cause de l'attestation/date actuelle)
- Les jours de décompte **comptent** dans le cumul total pour les limites 365/730/1095 jours

## Compatibilité

- ✅ **Rétrocompatible** : Tous les tests existants passent
- ✅ **Sans régression** : Aucune modification du comportement de calcul
- ✅ **Additive** : Ajout d'information uniquement, pas de changement de logique
