<?php

/**
 * Test manuel pour mock28.json
 * Calcule les dates d'effet selon les règles de text.txt
 * SANS utiliser IJCalculator
 */

echo "\n";
echo "================================================================================\n";
echo "           TEST MANUEL MOCK28 - Règles text.txt vs IJCalculator               \n";
echo "================================================================================\n\n";

// Charger mock28
$mock = json_decode(file_get_contents(__DIR__ . '/mock28.json'), true);

echo "DONNÉES MOCK28:\n";
echo "===============\n\n";
echo "Numéro sinistre: 15531 (même pathologie)\n";
echo "Naissance: 1958-12-07\n";
echo "Nombre d'arrêts: " . count($mock) . "\n\n";

foreach ($mock as $i => $arret) {
    echo "Arrêt #" . ($i+1) . ":\n";
    echo "  Période: {$arret['arret-from-line']} → {$arret['arret-to-line']}\n";
    echo "  Durée: {$arret['arret_diff']} jours\n";
    echo "  Ouverture droits (VBA): {$arret['ouverture-date-line']}\n";
    echo "\n";
}

echo "================================================================================\n\n";

echo "RÈGLES FONCTIONNELLES (text.txt):\n";
echo "==================================\n\n";

echo "Ligne 367: «Le jour de début de droit est la date ultérieure entre\n";
echo "           [le 91ème jour d'arrêt pour un sinistre] OU\n";
echo "           [le 31ème jour suivant la mise à jour du compte cotisant] OU\n";
echo "           [le 31ème jour suivant la déclaration tardive non excusée]»\n\n";

echo "Ligne 410: «[Le 91ème jour d'arrêt de travail] correspond au dépassement\n";
echo "           de 90 du cumul du nombre de jours entre une date de début\n";
echo "           d'arrêt de travail et une date de fin d'arrêt de travail.»\n\n";

echo "Ligne 423: «Pour une rechute (< 1 an), le seuil est de 15 jours\n";
echo "           au lieu de 90 jours.»\n\n";

echo "================================================================================\n\n";

echo "CALCUL MANUEL DES DATES D'EFFET:\n";
echo "=================================\n\n";

$cumul = 0;
$dateEffetResults = [];

foreach ($mock as $i => $arret) {
    echo "Arrêt #" . ($i+1) . ": {$arret['arret-from-line']} → {$arret['arret-to-line']} ({$arret['arret_diff']}j)\n";
    echo "───────────────────────────────────────────────────────────────────────\n";

    $startDate = new DateTime($arret['arret-from-line']);
    $endDate = new DateTime($arret['arret-to-line']);
    $duree = $arret['arret_diff'];

    // Ancien cumul
    $oldCumul = $cumul;
    $cumul += $duree;

    echo "  Cumul avant: {$oldCumul}j\n";
    echo "  Cumul après: {$cumul}j\n";

    // Déterminer si rechute
    $isRechute = false;
    if ($i > 0) {
        $prevEnd = new DateTime($mock[$i-1]['arret-to-line']);
        $interval = $prevEnd->diff($startDate);
        $daysBetween = $interval->days;

        if ($daysBetween < 365) {
            $isRechute = true;
            echo "  Type: RECHUTE (< 1 an depuis arrêt précédent)\n";
        } else {
            echo "  Type: NOUVELLE PATHOLOGIE (> 1 an depuis arrêt précédent)\n";
        }
    } else {
        echo "  Type: PREMIÈRE PATHOLOGIE\n";
    }

    // Déterminer le seuil
    $seuil = $isRechute ? 15 : 90;
    echo "  Seuil: {$seuil} jours\n";

    // Calculer date d'effet selon text.txt ligne 410
    $dateEffet = null;

    if ($cumul > $seuil) {
        // Calculer le 91ème jour (ou 15ème pour rechute)
        $joursDepuisDebut = $seuil - $oldCumul;
        $dateEffetCalculee = clone $startDate;
        $dateEffetCalculee->modify("+{$joursDepuisDebut} days");

        echo "  Calcul: cumul ({$cumul}j) > seuil ({$seuil}j) ✓\n";
        echo "  Date d'effet calculée: {$dateEffetCalculee->format('Y-m-d')} (jour " . ($seuil + 1) . ")\n";

        // Appliquer règle ligne 367 (date ultérieure)
        $dates = [$dateEffetCalculee->format('Y-m-d')];

        // Vérifier date de déclaration tardive
        if (isset($arret['declaration-date-line']) && !empty($arret['declaration-date-line'])) {
            $declDate = new DateTime($arret['declaration-date-line']);
            $diffDecl = $startDate->diff($declDate)->days;
            if ($diffDecl > 60) {
                $date31Decl = clone $declDate;
                $date31Decl->modify('+31 days');
                $dates[] = $date31Decl->format('Y-m-d');
                echo "  Déclaration tardive: +31j depuis {$declDate->format('Y-m-d')} = {$date31Decl->format('Y-m-d')}\n";
            }
        }

        // Vérifier maj compte
        if (isset($arret['date_maj_compte']) && !empty($arret['date_maj_compte'])) {
            $majDate = new DateTime($arret['date_maj_compte']);
            $date31Maj = clone $majDate;
            $date31Maj->modify('+31 days');
            $dates[] = $date31Maj->format('Y-m-d');
            echo "  MAJ compte: +31j depuis {$majDate->format('Y-m-d')} = {$date31Maj->format('Y-m-d')}\n";
        }

        $dateEffet = max($dates);
        echo "  Date d'effet FINALE: {$dateEffet}\n";
    } else {
        echo "  Cumul ({$cumul}j) ≤ seuil ({$seuil}j) → PAS de date d'effet\n";
        echo "  Date d'effet FINALE: null\n";
    }

    $dateEffetResults[] = [
        'arret' => $i + 1,
        'periode' => "{$arret['arret-from-line']} → {$arret['arret-to-line']}",
        'duree' => $duree,
        'cumul' => $cumul,
        'type' => $isRechute ? 'RECHUTE' : 'NOUVELLE',
        'seuil' => $seuil,
        'date_effet_manuelle' => $dateEffet,
        'ouverture_vba' => $arret['ouverture-date-line'],
    ];

    echo "\n";
}

echo "================================================================================\n\n";

echo "COMPARAISON AVEC IJCALCULATOR:\n";
echo "===============================\n\n";

// Maintenant exécuter IJCalculator
require_once __DIR__ . '/IJCalculator.php';
$calculator = new IJCalculator(__DIR__ . '/taux.csv');

// Configuration manuelle pour mock28 (from test_mocks.php lines 391-406)
$config = [
    'expected' => 0,
    'statut' => 'M',
    'classe' => 'C',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1958-12-07',
    'current_date' => date("Y-m-d"),
    'attestation_date' => null,
    'last_payment_date' => null,
    'affiliation_date' => '2013-01-01',
    'nb_trimestres' => 23,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 1,
];

$result = $calculator->calculateAmount([
    'arrets' => $mock,
    'statut' => $config['statut'],
    'classe' => $config['classe'],
    'option' => $config['option'],
    'pass_value' => $config['pass_value'],
    'birth_date' => $config['birth_date'],
    'current_date' => $config['current_date'],
    'attestation_date' => $config['attestation_date'],
    'last_payment_date' => $config['last_payment_date'],
    'affiliation_date' => $config['affiliation_date'],
    'nb_trimestres' => $config['nb_trimestres'],
    'previous_cumul_days' => $config['previous_cumul_days'],
    'prorata' => $config['prorata'],
    'patho_anterior' => $config['patho_anterior'],
]);

echo "┌─────┬──────────────────────┬────────┬────────┬──────────────┬──────────────┬────────┐\n";
echo "│ N°  │ Période              │ Durée  │ Cumul  │ Date effet   │ Date effet   │ Match? │\n";
echo "│     │                      │        │        │ MANUELLE     │ IJCALCULATOR │        │\n";
echo "├─────┼──────────────────────┼────────┼────────┼──────────────┼──────────────┼────────┤\n";

$allMatch = true;
foreach ($dateEffetResults as $i => $manual) {
    $ijcalc = $result['payment_details'][$i];
    $ijcalcDateEffet = $ijcalc['date_effet'] ?? 'null';
    $manualDateEffet = $manual['date_effet_manuelle'] ?? 'null';

    $match = ($ijcalcDateEffet === $manualDateEffet) ? '✓' : '✗';
    if ($match === '✗') {
        $allMatch = false;
    }

    printf("│ %-3d │ %-20s │ %4dj  │ %4dj  │ %-12s │ %-12s │   %s    │\n",
        $manual['arret'],
        substr($manual['periode'], 0, 20),
        $manual['duree'],
        $manual['cumul'],
        $manualDateEffet,
        $ijcalcDateEffet,
        $match
    );
}

echo "└─────┴──────────────────────┴────────┴────────┴──────────────┴──────────────┴────────┘\n\n";

if ($allMatch) {
    echo "✅ TOUTES LES DATES D'EFFET CORRESPONDENT!\n";
} else {
    echo "❌ DIFFÉRENCES DÉTECTÉES!\n\n";
    echo "Détails des différences:\n";
    foreach ($dateEffetResults as $i => $manual) {
        $ijcalc = $result['payment_details'][$i];
        $ijcalcDateEffet = $ijcalc['date_effet'] ?? 'null';
        $manualDateEffet = $manual['date_effet_manuelle'] ?? 'null';

        if ($ijcalcDateEffet !== $manualDateEffet) {
            echo "\n  Arrêt #{$manual['arret']}:\n";
            echo "    Manuelle: {$manualDateEffet}\n";
            echo "    IJCalculator: {$ijcalcDateEffet}\n";
            echo "    Type: {$manual['type']}\n";
            echo "    Seuil: {$manual['seuil']}j\n";
            echo "    Cumul: {$manual['cumul']}j\n";
        }
    }
}

echo "\n";
