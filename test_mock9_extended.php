<?php

require_once 'IJCalculator.php';

// Mock9 with extended end date to 2050
$mockData = [
    [
        "arret-from-line" => "2022-02-22",
        "arret-to-line" => "2050-05-21",  // Extended to 2050
        "id" => 56,
        "arret_diff" => 10316,  // Much longer
        "attestation-date-line" => "2050-05-21",  // Attestation at end
        "etat_anterieur" => 0,
        "dt-line" => 1,
        "date_maj_compte" => null,
        "declaration-date-line" => "2022-02-22",
        "rechute-line" => 0,
        "option" => 100,
        "valid_med_controleur" => 1,
        "date_deb_droit" => "2022-05-23",
        "cco_a_jour" => 1,
        "code_pathologie" => "A",
        "num_sinistre" => 60,
        "adherent_number" => "136547F",
        "date_deb_dr_force" => null,
        "date_fin_paiem_force" => null,
        "date_naissance" => "1953-01-22"
    ]
];

$calculator = new IJCalculator('taux.csv');

$data = [
    'statut' => 'M',
    'classe' => 'B',
    'option' => 100,
    'pass_value' => 47000,
    'birth_date' => '1953-01-22',
    'current_date' => date("Y-m-d"),
    'attestation_date' => '2050-05-21',  // Attestation at end
    'last_payment_date' => null,
    'affiliation_date' => null,
    'nb_trimestres' => 60,
    'previous_cumul_days' => 0,
    'prorata' => 1,
    'patho_anterior' => 0,
    'arrets' => $mockData
];

$calculator->setPassValue(47000);
$result = $calculator->calculateAmount($data);

echo "=== MOCK9 EXTENDED TEST (END DATE: 2050-05-21) ===\n\n";
echo "Birth date: {$data['birth_date']}\n";
echo "Arret: {$mockData[0]['arret-from-line']} to {$mockData[0]['arret-to-line']}\n";
echo "Attestation: {$data['attestation_date']}\n";
echo "Arret diff: {$mockData[0]['arret_diff']} days\n\n";

if (isset($result['arrets'][0]['date-effet'])) {
    $dateEffet = $result['arrets'][0]['date-effet'];
    echo "Date effet: {$dateEffet}\n";

    $calculator2 = new IJCalculator('taux.csv');
    $ageAtStart = $calculator2->calculateAge($dateEffet, $data['birth_date']);
    $ageNow = $calculator2->calculateAge(date('Y-m-d'), $data['birth_date']);
    echo "Age at date effet: {$ageAtStart} years\n";
    echo "Age now: {$ageNow} years\n\n";
}

echo "=== RESULTS ===\n";
echo "Nombre de jours calculé: " . $result['nb_jours'] . "\n";
echo "Nombre de jours ATTENDU: 730 (limite car passe de <70 à 70+ pendant IJ)\n";
echo "Montant calculé: " . $result['montant'] . " €\n\n";

if ($result['nb_jours'] == 730) {
    echo "✅ TEST PASSED: Correctly limited to 730 days\n\n";
} else {
    echo "❌ TEST FAILED: Expected 730 days but got {$result['nb_jours']}\n\n";
}

echo "Payment Details:\n";
echo "================\n";
if (isset($result['payment_details']) && is_array($result['payment_details'])) {
    foreach ($result['payment_details'] as $detailIndex => $detail) {
        echo "\nArrêt $detailIndex:\n";
        echo "  Payment start: " . ($detail['payment_start'] ?? 'N/A') . "\n";
        echo "  Payment end: " . ($detail['payment_end'] ?? 'N/A') . "\n";
        echo "  Payable days: " . ($detail['payable_days'] ?? 0) . "\n";
        echo "  Montant: " . ($detail['montant'] ?? 0) . " €\n\n";

        if (isset($detail['rate_breakdown']) && is_array($detail['rate_breakdown'])) {
            echo "  Rate breakdown (" . count($detail['rate_breakdown']) . " entries):\n";
            $totalDays = 0;
            $lastEntry = count($detail['rate_breakdown']) - 1;

            // Show first 5 entries
            for ($i = 0; $i < min(5, count($detail['rate_breakdown'])); $i++) {
                $breakdown = $detail['rate_breakdown'][$i];
                $totalDays += ($breakdown['days'] ?? 0);
                echo sprintf(
                    "    [%2d] %s -> %s : %3d jours × %7.2f€ (Taux: %s)\n",
                    $i,
                    $breakdown['start'] ?? 'N/A',
                    $breakdown['end'] ?? 'N/A',
                    $breakdown['days'] ?? 0,
                    $breakdown['rate'] ?? 0,
                    $breakdown['taux'] ?? 'N/A'
                );
            }

            if (count($detail['rate_breakdown']) > 10) {
                echo "    ... [" . (count($detail['rate_breakdown']) - 10) . " entries omitted] ...\n";
            }

            // Show last 5 entries
            for ($i = max(5, $lastEntry - 4); $i <= $lastEntry; $i++) {
                $breakdown = $detail['rate_breakdown'][$i];
                $totalDays += ($breakdown['days'] ?? 0);
                echo sprintf(
                    "    [%2d] %s -> %s : %3d jours × %7.2f€ (Taux: %s)\n",
                    $i,
                    $breakdown['start'] ?? 'N/A',
                    $breakdown['end'] ?? 'N/A',
                    $breakdown['days'] ?? 0,
                    $breakdown['rate'] ?? 0,
                    $breakdown['taux'] ?? 'N/A'
                );
            }

            // Count all days for verification
            $totalDays = 0;
            foreach ($detail['rate_breakdown'] as $breakdown) {
                $totalDays += ($breakdown['days'] ?? 0);
            }

            echo "\n  Total days in breakdown: $totalDays\n";

            // Show last date in breakdown
            if ($lastEntry >= 0) {
                $lastBreakdown = $detail['rate_breakdown'][$lastEntry];
                echo "  Last breakdown entry ends: " . ($lastBreakdown['end'] ?? 'N/A') . "\n";
            }
        }
    }
}

if (isset($result['end_payment_dates'])) {
    echo "\n\nEnd Payment Dates:\n";
    echo "==================\n";
    foreach ($result['end_payment_dates'] as $key => $date) {
        echo "$key: $date\n";
    }
}
