# Détail Jour par Jour - Daily Breakdown

## Vue d'ensemble

Le système de calcul des indemnités journalières inclut maintenant un **détail jour par jour** complet qui affiche :

- Le taux appliqué pour chaque jour
- Le montant payé pour chaque jour
- L'âge du bénéficiaire à chaque date
- La période et le trimestre de chaque paiement

## Structure des données

### Résultat de `calculateAmount()`

```php
$result = $calculator->calculateAmount($data);

$result = [
    'nb_jours' => 730,
    'montant' => 53467.98,
    'payment_details' => [
        [
            'payment_start' => '2022-05-23',
            'payment_end' => '2024-05-21',
            'payable_days' => 730,
            'montant' => 53467.98,

            // Nouveau : détail jour par jour
            'daily_breakdown' => [
                [
                    'date' => '2022-05-23',
                    'day_of_week' => 'Monday',
                    'year' => 2022,
                    'month' => 5,
                    'trimester' => 2,
                    'period' => 1,
                    'taux' => 1,
                    'daily_rate' => 69.00,
                    'amount' => 69.00,
                    'nb_trimestres' => 8
                ],
                [
                    'date' => '2022-05-24',
                    'day_of_week' => 'Tuesday',
                    // ... etc pour chaque jour
                ],
                // ... 730 entrées au total
            ],

            // Déjà existant : récapitulatif par segment
            'rate_breakdown' => [
                [
                    'year' => 2022,
                    'month' => 5,
                    'trimester' => 2,
                    'nb_trimestres' => 8,
                    'period' => 1,
                    'start' => '2022-05-23',
                    'end' => '2022-05-31',
                    'days' => 9,
                    'rate' => 69.00,
                    'taux' => 1
                ],
                // ... autres segments
            ]
        ]
    ]
];
```

## Utilisation

### 1. Calcul de base

```php
require_once 'IJCalculator.php';

$calculator = new IJCalculator('taux.csv');

$result = $calculator->calculateAmount([
    'arrets' => $arrets,
    'statut' => 'M',
    'classe' => 'A',
    'option' => 100,
    'birth_date' => '1989-09-26',
    'current_date' => '2024-09-09',
    'attestation_date' => '2024-01-31',
    'nb_trimestres' => 8,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => false,
]);
```

### 2. Affichage jour par jour

```php
foreach ($result['payment_details'] as $arretIndex => $detail) {
    echo "Arrêt #" . ($arretIndex + 1) . "\n";
    echo "Période: {$detail['payment_start']} → {$detail['payment_end']}\n\n";

    foreach ($detail['daily_breakdown'] as $day) {
        printf("%s (%s) - Taux %d - %0.2f €\n",
            $day['date'],
            substr($day['day_of_week'], 0, 3),
            $day['taux'],
            $day['amount']
        );
    }
}
```

### 3. Regroupement par taux

```php
$byTaux = [];
foreach ($result['payment_details'] as $detail) {
    foreach ($detail['daily_breakdown'] as $day) {
        $taux = $day['taux'];
        if (!isset($byTaux[$taux])) {
            $byTaux[$taux] = ['days' => 0, 'total' => 0];
        }
        $byTaux[$taux]['days']++;
        $byTaux[$taux]['total'] += $day['amount'];
    }
}

foreach ($byTaux as $taux => $info) {
    echo "Taux {$taux}: {$info['days']} jours = {$info['total']} €\n";
}
```

### 4. Regroupement par mois

```php
$byMonth = [];
foreach ($result['payment_details'] as $detail) {
    foreach ($detail['daily_breakdown'] as $day) {
        $monthKey = $day['year'] . '-' . str_pad($day['month'], 2, '0', STR_PAD_LEFT);
        if (!isset($byMonth[$monthKey])) {
            $byMonth[$monthKey] = ['days' => 0, 'total' => 0];
        }
        $byMonth[$monthKey]['days']++;
        $byMonth[$monthKey]['total'] += $day['amount'];
    }
}

foreach ($byMonth as $month => $info) {
    echo "{$month}: {$info['days']} jours = {$info['total']} €\n";
}
```

## Scripts de test inclus

### test_daily_breakdown.php

Affiche le détail complet jour par jour avec :
- Tableau détaillé de tous les jours
- Récapitulatif par taux
- Récapitulatif par mois

**Usage:**
```bash
php test_daily_breakdown.php
```

### test_daily_mock9.php

Test spécifique pour mock9 montrant la **transition d'âge à 70 ans** :
- 244 jours à taux 1 (âge 69)
- Transition exacte le jour du 70ème anniversaire
- 486 jours à taux 4 (âge 70+)

**Usage:**
```bash
php test_daily_mock9.php
```

## Exemple de sortie

```
═══════════════════════════════════════════════════════════════════════════════
  DÉTAIL JOUR PAR JOUR
═══════════════════════════════════════════════════════════════════════════════

Date         Jour       Année Mois Tri Période Taux   Taux jour.   Montant
────────────────────────────────────────────────────────────────────────────────
2024-01-22   Mon        2024 1    Q1  P1      1           75.06 €      75.06 €
2024-01-23   Tue        2024 1    Q1  P1      1           75.06 €      75.06 €
2024-01-24   Wed        2024 1    Q1  P1      1           75.06 €      75.06 €
...

═══════════════════════════════════════════════════════════════════════════════
  RÉCAPITULATIF PAR TAUX
═══════════════════════════════════════════════════════════════════════════════

Taux   Période    Nombre jours    Taux jour.      Montant total
──────────────────────────────────────────────────────────────────────
1      Période 1            10 j         75.06 €        750.60 €
```

## Cas d'usage

### 1. Vérification des calculs

Le détail jour par jour permet de vérifier exactement quel taux a été appliqué à quelle date, utile pour :
- Déboguer des calculs complexes
- Expliquer un montant au bénéficiaire
- Auditer les paiements

### 2. Transition d'âge

Pour les cas comme mock9 où l'âge change pendant la période de paiement :
```php
// Identifier le jour de transition
$previousTaux = null;
foreach ($detail['daily_breakdown'] as $day) {
    if ($previousTaux !== null && $day['taux'] != $previousTaux) {
        echo "Transition de taux le {$day['date']}\n";
        echo "  Taux {$previousTaux} → Taux {$day['taux']}\n";
    }
    $previousTaux = $day['taux'];
}
```

### 3. Export vers Excel/CSV

```php
// Header CSV
echo "Date,Jour,Année,Mois,Trimestre,Période,Taux,Taux_Jour,Montant\n";

// Données
foreach ($result['payment_details'] as $detail) {
    foreach ($detail['daily_breakdown'] as $day) {
        echo "{$day['date']},{$day['day_of_week']},{$day['year']},";
        echo "{$day['month']},{$day['trimester']},{$day['period']},";
        echo "{$day['taux']},{$day['daily_rate']},{$day['amount']}\n";
    }
}
```

### 4. Génération de factures détaillées

```php
function generateInvoiceHTML($result) {
    $html = '<table>';
    $html .= '<thead><tr><th>Date</th><th>Taux</th><th>Montant</th></tr></thead>';
    $html .= '<tbody>';

    foreach ($result['payment_details'] as $detail) {
        foreach ($detail['daily_breakdown'] as $day) {
            $html .= sprintf('<tr><td>%s</td><td>%d</td><td>%.2f €</td></tr>',
                $day['date'],
                $day['taux'],
                $day['amount']
            );
        }
    }

    $html .= '</tbody></table>';
    return $html;
}
```

## Intégration CakePHP 5

### Dans un Controller

```php
namespace App\Controller;

use App\Service\IJCalculatorService;

class IndemniteJournaliereController extends AppController
{
    public function viewDailyBreakdown($calculationId)
    {
        $calculation = $this->Calculations->get($calculationId);
        $inputData = json_decode($calculation->input_data, true);

        $calculator = new IJCalculatorService(CONFIG . 'taux.csv');
        $result = $calculator->calculateAmount($inputData);

        $this->set('dailyBreakdown', $result['payment_details'][0]['daily_breakdown']);
        $this->set('calculation', $calculation);
    }
}
```

### Dans une Vue CakePHP

```php
<!-- templates/IndemniteJournaliere/view_daily_breakdown.php -->

<h2>Détail jour par jour</h2>

<table class="table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Jour</th>
            <th>Période</th>
            <th>Taux</th>
            <th>Montant</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($dailyBreakdown as $day): ?>
        <tr>
            <td><?= h($day['date']) ?></td>
            <td><?= h($day['day_of_week']) ?></td>
            <td>P<?= h($day['period']) ?></td>
            <td><?= h($day['taux']) ?></td>
            <td><?= number_format($day['amount'], 2, ',', ' ') ?> €</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

### API JSON

```php
public function apiDailyBreakdown()
{
    $this->request->allowMethod(['post']);
    $this->viewBuilder()->setClassName('Json');

    try {
        $calculator = new IJCalculatorService(CONFIG . 'taux.csv');
        $result = $calculator->calculateAmount($this->request->getData());

        $this->set([
            'success' => true,
            'data' => [
                'total_days' => $result['nb_jours'],
                'total_amount' => $result['montant'],
                'daily_breakdown' => $result['payment_details'][0]['daily_breakdown']
            ],
            '_serialize' => ['success', 'data'],
        ]);
    } catch (\Exception $e) {
        $this->response = $this->response->withStatus(400);
        $this->set([
            'success' => false,
            'error' => $e->getMessage(),
            '_serialize' => ['success', 'error'],
        ]);
    }
}
```

## Performance

### Nombre d'éléments

Pour un arrêt de 730 jours :
- `daily_breakdown` : 730 entrées (une par jour)
- `rate_breakdown` : ~10-30 entrées (segments regroupés)

### Mémoire

Chaque entrée `daily_breakdown` : ~200 bytes
730 jours ≈ 146 KB de mémoire

### Optimisation

Si les données jour par jour ne sont pas nécessaires, utiliser uniquement `rate_breakdown` :

```php
// Utiliser rate_breakdown au lieu de daily_breakdown
foreach ($result['payment_details'] as $detail) {
    foreach ($detail['rate_breakdown'] as $segment) {
        echo "{$segment['start']} → {$segment['end']}: ";
        echo "{$segment['days']} jours × {$segment['rate']} € = ";
        echo ($segment['days'] * $segment['rate']) . " €\n";
    }
}
```

## Champs disponibles

### daily_breakdown (jour par jour)

| Champ | Type | Description |
|-------|------|-------------|
| `date` | string | Date du jour (Y-m-d) |
| `day_of_week` | string | Nom du jour (Monday, Tuesday...) |
| `year` | int | Année |
| `month` | int | Mois (1-12) |
| `trimester` | int | Trimestre (1-4) |
| `period` | int\|string | Période (1/2/3 ou "senior") |
| `taux` | int | Numéro de taux (1-9) |
| `daily_rate` | float | Taux journalier en € |
| `amount` | float | Montant pour ce jour |
| `nb_trimestres` | int | Nombre de trimestres d'affiliation |

### rate_breakdown (par segment)

| Champ | Type | Description |
|-------|------|-------------|
| `year` | int | Année du segment |
| `month` | int | Mois du segment |
| `trimester` | int | Trimestre |
| `nb_trimestres` | int | Nombre de trimestres |
| `period` | int\|string | Période |
| `start` | string | Date de début du segment |
| `end` | string | Date de fin du segment |
| `days` | int | Nombre de jours du segment |
| `rate` | float | Taux journalier |
| `taux` | int | Numéro de taux |

## Comparaison

### Quand utiliser `daily_breakdown` ?

✅ Affichage détaillé jour par jour
✅ Export CSV/Excel
✅ Génération de factures détaillées
✅ Audit et vérification précise
✅ Identification des transitions

### Quand utiliser `rate_breakdown` ?

✅ Résumé par période
✅ Performance (moins de données)
✅ Affichage synthétique
✅ Rapports mensuels/trimestriels

## Conclusion

Le système de `daily_breakdown` fournit une transparence totale sur le calcul des indemnités journalières, permettant de voir exactement quel taux a été appliqué pour chaque jour de la période de paiement.
