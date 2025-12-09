# Règle Date d'Effet - Réforme 2025

## ⚠️ RÈGLE CRITIQUE

**La DATE D'EFFET de l'arrêt détermine le système de calcul, PAS la date de paiement ou la date du jour calculé.**

## Principe

```
Si date_effet < 2025-01-01
  → Utilise les taux historiques (CSV/Base de données)
  → Même si l'arrêt continue en 2025 ou au-delà

Si date_effet >= 2025-01-01
  → Utilise la formule PASS (nouveau système)
```

## Exemples Détaillés

### Exemple 1 : Arrêt Débutant en Décembre 2024

**Scénario** :
```json
{
  "date_effet": "2024-12-15",
  "date_fin": "2025-02-28",
  "classe": "A",
  "taux": 1
}
```

**Analyse** :
- Date d'effet : **15 décembre 2024** (< 2025-01-01)
- L'arrêt continue en janvier et février 2025
- Durée totale : 76 jours (dont ~47 jours en 2025)

**Calcul** :
- ✅ Utilise les **taux historiques 2024** (base de données)
- ❌ N'utilise **PAS** la formule PASS
- Tous les jours (décembre 2024 + janvier/février 2025) sont calculés avec les taux 2024

**Pourquoi ?**
> L'arrêt a débuté sous l'ancien système, il reste sous l'ancien système jusqu'à sa fin.

---

### Exemple 2 : Arrêt Débutant en Janvier 2025

**Scénario** :
```json
{
  "date_effet": "2025-01-10",
  "date_fin": "2025-03-31",
  "classe": "A",
  "taux": 1
}
```

**Analyse** :
- Date d'effet : **10 janvier 2025** (≥ 2025-01-01)
- Nouvel arrêt débutant en 2025
- Durée : 80 jours

**Calcul** :
- ✅ Utilise la **formule PASS** : `1 × 46368 / 730 = 63.52 €/jour`
- Tous les jours sont calculés avec la nouvelle formule

**Pourquoi ?**
> L'arrêt débute sous le nouveau système, il utilise le nouveau système.

---

### Exemple 3 : Classe C Avec Arrêt Avant 2025

**Scénario** :
```json
{
  "date_effet": "2024-11-20",
  "date_fin": "2025-04-15",
  "classe": "C",
  "taux": 1
}
```

**Analyse** :
- Date d'effet : **20 novembre 2024** (< 2025-01-01)
- Classe C
- L'arrêt couvre novembre-décembre 2024 ET janvier-avril 2025

**Calcul** :
- ✅ Utilise les **taux historiques 2024** pour la classe C
- ❌ **N'utilise PAS** `mt_pass` (3 × PASS / 730)
- Utilise le taux C 2024 de la base de données

**Important** :
> Pour les classes A et C, si date_effet < 2025, on utilise les taux 2025 de la base de données (s'ils existent), PAS la formule `mt_pass`.

---

## Tableau Récapitulatif

| Date d'Effet | Durée Arrêt | Système | Classe A | Classe C |
|--------------|-------------|---------|----------|----------|
| 2024-10-15 | Oct-Déc 2024 | Taux 2024 DB | Taux DB | Taux DB |
| 2024-12-01 | Déc 2024 - Fév 2025 | Taux 2024 DB | Taux DB | Taux DB |
| 2024-12-31 | Déc 2024 - Mar 2025 | Taux 2024 DB | Taux DB | Taux DB |
| **2025-01-01** | Jan-Mar 2025 | **Formule PASS** | **63.52 €** | **190.55 €** |
| 2025-01-15 | Jan-Apr 2025 | Formule PASS | 63.52 € | 190.55 € |
| 2025-06-10 | Jun-Aoû 2025 | Formule PASS | 63.52 € | 190.55 € |

## Cas Particuliers

### Cas 1 : Rechute d'un Arrêt 2024

**Scénario** :
- Arrêt initial : date_effet = 2024-10-01 (taux 2024)
- Rechute : date_effet = 2025-02-01

**Calcul** :
- Arrêt initial → Taux 2024
- Rechute → **Formule PASS** (nouvelle date_effet en 2025)

> Chaque arrêt est évalué selon SA propre date d'effet.

---

### Cas 2 : Prolongation d'un Arrêt 2024

**Scénario** :
- Arrêt : date_effet = 2024-12-15
- Prolongation en 2025 (même pathologie, même date_effet)

**Calcul** :
- ✅ Continue avec les taux 2024
- La date d'effet originale (2024-12-15) reste la référence

---

## Implémentation Technique

### Code (RateService.php)

```php
public function getDailyRate(...): float {
    // Date d'effet de l'arrêt (paramètre $date)
    $effectiveDate = $date ?? "$year-01-01";

    // Détection basée sur la date d'effet
    $isAfter2025Reform = strtotime($effectiveDate) >= strtotime('2025-01-01');

    if ($isAfter2025Reform) {
        // Nouveau système : Formule PASS
        return $this->calculate2025Rate($statut, $classe, $option, $taux);
    }

    // Ancien système : Taux historiques (CSV/DB)
    // Recherche du taux pour la date d'effet
    $rateData = $this->getRateForDate($date);
    // ...
}
```

### Flux de Décision

```
Entrée : date_effet de l'arrêt

┌─────────────────────────┐
│ date_effet < 2025-01-01 ?
└─────────────────────────┘
           │
     ┌─────┴─────┐
     │           │
    OUI         NON
     │           │
     ▼           ▼
┌─────────┐ ┌──────────┐
│ Taux DB │ │ Formule  │
│ 2024    │ │ PASS     │
└─────────┘ └──────────┘
     │           │
     ▼           ▼
  Classes     Classe × PASS / 730
  A/B/C       Réductions automatiques
  Taux DB
```

## Tests

### Commande

```bash
php test_date_effet_2025.php
```

### Résultats Attendus

```
✓ Date d'effet 2024-12-15 → Taux historiques
✓ Date d'effet 2025-01-15 → Formule PASS
✓ Classe A/C avant 2025 → Taux DB, PAS mt_pass
```

## FAQ

### Q1 : Un arrêt débutant le 31/12/2024 utilise quel système ?

**R** : Taux historiques 2024 (date_effet < 2025-01-01), même si l'arrêt continue en 2025.

---

### Q2 : Un arrêt débutant le 01/01/2025 utilise quel système ?

**R** : Formule PASS (date_effet >= 2025-01-01).

---

### Q3 : Si un arrêt 2024 continue en 2025, les jours en 2025 utilisent la formule PASS ?

**R** : **NON**. Tous les jours utilisent les taux 2024 car la date_effet est en 2024.

---

### Q4 : Pour la classe A ou C, si date_effet = 2024-11-01 et continue en 2025 ?

**R** : Utilise les **taux 2025 de la base de données**, PAS la formule `mt_pass`.

---

### Q5 : Comment mettre à jour les taux 2025 pour A et C dans la base ?

**R** :
```sql
-- Ajouter les taux 2025 (si pas déjà présents)
INSERT INTO ij_taux (date_start, date_end, taux_a1, taux_c1, ...)
VALUES ('2025-01-01', '2025-12-31', XX.XX, XX.XX, ...);
```

---

## Points Clés

1. ✅ **Date d'effet** = Critère de décision
2. ❌ **Date de paiement** = Non pertinente
3. ❌ **Date du jour** = Non pertinente
4. ✅ **Arrêt 2024 → Taux 2024** (même si continue en 2025)
5. ✅ **Nouvel arrêt 2025 → Formule PASS**
6. ✅ **Classes A/C avant 2025 → Taux DB, PAS mt_pass**

---

## Résumé Visuel

```
┌─────────────────────────────────────────────────────┐
│                  CALENDRIER 2024-2025               │
├─────────────────────────────────────────────────────┤
│                                                      │
│  NOV 2024   DÉC 2024   │   JAN 2025   FÉV 2025     │
│                         │                            │
│  ──────────────────────▼────────────────────        │
│         Arrêt A         │    Continue               │
│      (date_effet       │  → Taux 2024              │
│       = 2024-11-15)     │    PAS formule PASS       │
│                         │                            │
│                        Seuil 2025                    │
│                         │                            │
│                         │  ──────────────            │
│                         │    Arrêt B                 │
│                         │  (date_effet               │
│                         │   = 2025-01-10)            │
│                         │  → Formule PASS            │
│                         │                            │
└─────────────────────────────────────────────────────┘

Arrêt A : Taux historiques 2024 pour TOUS les jours
Arrêt B : Formule PASS pour tous les jours
```

---

**La date d'effet est LE critère déterminant. Rien d'autre ne compte.** ✅
