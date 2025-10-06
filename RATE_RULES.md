# Règles de Détermination des Taux d'Indemnisation

## Vue d'ensemble

Le système utilise **27 taux différents** pour calculer les indemnités journalières (IJ) des médecins. Le taux applicable dépend de plusieurs critères combinés.

## Critères de Sélection

### 1. Âge du médecin
- **< 62 ans** : Taux 1-3 (taux plein)
- **62-69 ans** : Taux 7-9 (après 1 an au taux plein, réduit de 25%)
- **≥ 70 ans** : Taux 4-6 (taux réduit senior)

### 2. Pathologie antérieure
La pathologie est considérée comme **antérieure** si son origine remonte à une date antérieure à la **dernière date d'affiliation à la Caisse CARMF**.

### 3. Trimestres d'affiliation
Nombre de trimestres d'affiliation à un régime obligatoire d'invalidité (tous régimes confondus) entre :
- La date d'affiliation au régime d'invalidité
- La **date du premier arrêt de travail pour la pathologie cause de l'arrêt actuel**

## Tableau des 27 Taux

| Âge        | Taux | Conditions                                    | Calcul                        |
|------------|------|-----------------------------------------------|-------------------------------|
| < 62 ans   | 1    | Pas de patho antérieure OU ≥ 24 trimestres   | Taux plein                    |
| < 62 ans   | 2    | Patho antérieure ET 8-15 trimestres           | Taux 1 réduit d'1/3           |
| < 62 ans   | 3    | Patho antérieure ET 16-23 trimestres          | Taux 1 réduit de 2/3          |
| 62-69 ans  | 7    | Pas de patho antérieure OU ≥ 24 trimestres   | Taux plein -25%               |
| 62-69 ans  | 8    | Patho antérieure ET 8-15 trimestres           | Taux 7 réduit d'1/3           |
| 62-69 ans  | 9    | Patho antérieure ET 16-23 trimestres          | Taux 7 réduit de 2/3          |
| ≥ 70 ans   | 4    | Pas de patho antérieure OU ≥ 24 trimestres   | Taux réduit senior            |
| ≥ 70 ans   | 5    | Patho antérieure ET 8-15 trimestres           | Taux 4 réduit d'1/3           |
| ≥ 70 ans   | 6    | Patho antérieure ET 16-23 trimestres          | Taux 4 réduit de 2/3          |

### Formules de Réduction

Pour chaque tranche d'âge, il existe 3 niveaux de taux :
- **Niveau X** : Taux plein (déterminé par le Conseil d'Administration)
- **Niveau X+1** : Taux de niveau X × (2/3) — réduction d'1/3
- **Niveau X+2** : Taux de niveau X × (1/3) — réduction de 2/3

## Règles Spéciales

### Pas d'indemnisation
Si le médecin justifie de **moins de 8 trimestres** d'affiliation à un régime obligatoire d'invalidité, **aucune indemnisation** n'est versée.

### Coordination inter-régimes
Si le médecin justifie avoir cotisé le nombre suffisant de trimestres à un régime obligatoire d'invalidité permettant d'atteindre **24 trimestres d'affiliation continue** à un régime obligatoire d'invalidité, le **taux plein** lui est appliqué.

À défaut, le taux est calculé au prorata du nombre de trimestres d'affiliation à un régime d'invalidité (tous régimes confondus).

### Persistance du taux historique
Si le médecin a déjà bénéficié des IJ au taux réduit par le passé **pour cette même pathologie**, le **taux déjà appliqué par le passé** lui sera appliqué (même si sa situation a évolué).

Cette règle garantit la cohérence du traitement d'une pathologie donnée sur toute sa durée.

## Périodes d'indemnisation pour 62-69 ans

Pour les médecins âgés de 62 à 69 ans, le calcul s'effectue en **trois périodes** :

1. **Période 1 (jours 1-365)** : Taux plein de la catégorie
2. **Période 2 (jours 366-730)** : Taux réduit de 25% (taux 7-9)
3. **Période 3 (jours 731-1095)** : Taux moyen (taux 4-6)

Les taux appliqués dans chaque période dépendent toujours du nombre de trimestres et de la pathologie antérieure.

## Implémentation dans IJCalculator

### Paramètres requis

```php
$data = [
    'patho_anterior' => true,  // Booléen
    'affiliation_date' => '2020-01-15',  // Date d'affiliation au régime
    'first_pathology_stop_date' => '2023-06-20',  // Date du 1er arrêt pour cette pathologie
    'historical_reduced_rate' => null,  // Taux historique (1-9) ou null
    'nb_trimestres' => null,  // Calculé automatiquement si affiliation_date fournie
    // ... autres paramètres
];

$result = $calculator->calculateAmount($data);
```

### Fonction de détermination du taux

La méthode `determineTauxNumber()` (ligne 624) implémente l'arbre de décision complet selon les règles ci-dessus.

### Application des réductions

La méthode `getRate()` (ligne 942) :
1. Récupère le taux de base depuis le CSV selon la colonne appropriée
2. Applique les réductions pour pathologie antérieure (×2/3 ou ×1/3)
3. Applique le multiplicateur d'option pour CCPL et RSPM

## Exemple de Calcul

**Cas** : Médecin de 58 ans, pathologie antérieure, 12 trimestres d'affiliation

1. **Âge < 62** → Taux de base : 1, 2 ou 3
2. **Pathologie antérieure** → Vérifier trimestres
3. **12 trimestres** → Entre 8 et 15 → Taux 2
4. **Taux 2 appliqué** : Taux plein (colonne 1 du CSV) × (2/3)

**Formule** : `IJ quotidienne = taux_csv_colonne_1 × (2/3) × nombre_jours`

## Notes importantes

- Les trimestres sont comptés de manière continue (T1, T2, T3, T4 par année)
- Le trimestre d'affiliation compte comme trimestre complet
- Pour les 62-69 ans, le passage d'une période à l'autre change le taux, même en cours de mois
- Les taux historiques sont prioritaires sur les règles standards
