<?php

/**
 * Classe IJCalculator
 *
 * Calculateur d'Indemnités Journalières (IJ) pour médecins selon les règles CARMF
 *
 * RÈGLES MÉTIER - SYSTÈME 27 TAUX
 * ================================
 *
 * 1. CRITÈRES DE DÉTERMINATION DU TAUX
 *    - Âge du médecin (< 62, 62-69, >= 70)
 *    - Pathologie antérieure à la dernière affiliation
 *    - Nombre de trimestres d'affiliation à un régime d'invalidité
 *
 * 2. STRUCTURE DES 27 TAUX
 *    Taux 1-3  : < 62 ans (taux plein, -1/3, -2/3)
 *    Taux 4-6  : >= 70 ans ou après 1 an au taux 7-9 (taux réduit, -1/3, -2/3)
 *    Taux 7-9  : 62-69 ans après 1 an au taux plein (taux -25%, -1/3, -2/3)
 *
 * 3. PATHOLOGIE ANTÉRIEURE
 *    Si la pathologie est antérieure à la dernière affiliation CARMF :
 *    - < 8 trimestres  : Pas d'indemnisation
 *    - 8-15 trimestres : Taux réduit d'1/3
 *    - 16-23 trimestres : Taux réduit de 2/3
 *    - >= 24 trimestres : Taux plein (coordination inter-régimes)
 *
 * 4. TRIMESTRES D'AFFILIATION
 *    - Calculés entre la date d'affiliation au régime d'invalidité et la date du
 *      premier arrêt pour la pathologie actuelle
 *    - Possibilité de cumuler les trimestres d'autres régimes obligatoires d'invalidité
 *
 * 5. PERSISTANCE HISTORIQUE
 *    Si le médecin a déjà bénéficié d'IJ au taux réduit pour cette même pathologie,
 *    le taux historique est conservé
 *
 * PARAMÈTRES D'ENTRÉE
 * ===================
 * - first_pathology_stop_date : Date du premier arrêt pour la pathologie actuelle
 * - historical_reduced_rate : Taux historique déjà appliqué (1-9)
 * - patho_anterior : Booléen indiquant une pathologie antérieure
 * - affiliation_date : Date d'affiliation au régime d'invalidité
 * - nb_trimestres : Nombre de trimestres (calculé automatiquement si affiliation_date fournie)
 */
class IJCalculator {
    private $rates = [];
    private $passValue = 47000; // Valeur par défaut du PASS CPAM

    public function __construct($csvPath = 'taux.csv') {
        $this->loadRates($csvPath);
    }

    public function setPassValue($value) {
        $this->passValue = $value;
    }

    private function loadRates($csvPath) {
        if (!file_exists($csvPath)) {
            throw new Exception("CSV file not found: $csvPath");
        }

        $file = fopen($csvPath, 'r');
        $headers = fgetcsv($file, 0, ';');

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            $rate = [];
            foreach ($headers as $index => $header) {
                $rate[$header] = $row[$index];
            }
            $this->rates[] = $rate;
        }
        fclose($file);
    }

    public function getRateForYear($year) {
        foreach ($this->rates as $rate) {
            $startDate = new DateTime($rate['date_start']);
            $endDate = new DateTime($rate['date_end']);
            $checkDate = new DateTime("$year-01-01");

            if ($checkDate >= $startDate && $checkDate <= $endDate) {
                return $rate;
            }
        }
        return null;
    }

    /**
     * Obtenir le numéro de trimestre à partir d'une date
     * T1 (01/01-31/03) = 1, T2 (01/04-30/06) = 2, T3 (01/07-30/09) = 3, T4 (01/10-31/12) = 4
     */
    private function getTrimesterFromDate($date) {
        $dateObj = new DateTime($date);
        $month = (int)$dateObj->format('m');

        if ($month <= 3) {
            return 1;
        } elseif ($month <= 6) {
            return 2;
        } elseif ($month <= 9) {
            return 3;
        } else {
            return 4;
        }
    }

    /**
     * Calculer le nombre de trimestres entre la date d'affiliation et la date actuelle
     * Trimestres: T1 (01/01-31/03), T2 (01/04-30/06), T3 (01/07-30/09), T4 (01/10-31/12)
     * Si la date d'affiliation tombe dans un trimestre, ce trimestre est compté comme complet
     */
    public function calculateTrimesters($affiliationDate, $currentDate) {
        if (empty($affiliationDate) || $affiliationDate === '0000-00-00') {
            return 0;
        }

        $affDate = new DateTime($affiliationDate);
        $curDate = new DateTime($currentDate);

        if ($affDate > $curDate) {
            return 0;
        }

        // Déterminer le trimestre de départ
        $affYear = (int)$affDate->format('Y');
        $affMonth = (int)$affDate->format('m');

        // Déterminer dans quel trimestre tombe la date d'affiliation
        if ($affMonth <= 3) {
            $startQuarter = 1;
            $startYear = $affYear;
        } elseif ($affMonth <= 6) {
            $startQuarter = 2;
            $startYear = $affYear;
        } elseif ($affMonth <= 9) {
            $startQuarter = 3;
            $startYear = $affYear;
        } else {
            $startQuarter = 4;
            $startYear = $affYear;
        }

        // Déterminer le trimestre de fin
        $curYear = (int)$curDate->format('Y');
        $curMonth = (int)$curDate->format('m');

        if ($curMonth <= 3) {
            $endQuarter = 1;
            $endYear = $curYear;
        } elseif ($curMonth <= 6) {
            $endQuarter = 2;
            $endYear = $curYear;
        } elseif ($curMonth <= 9) {
            $endQuarter = 3;
            $endYear = $curYear;
        } else {
            $endQuarter = 4;
            $endYear = $curYear;
        }

        // Calculer le total des trimestres
        $totalQuarters = 0;
        $year = $startYear;
        $quarter = $startQuarter;

        while ($year < $endYear || ($year === $endYear && $quarter <= $endQuarter)) {
            $totalQuarters++;
            $quarter++;
            if ($quarter > 4) {
                $quarter = 1;
                $year++;
            }
        }

        return $totalQuarters;
    }

    public function getRateForDate($date) {
        $checkDate = new DateTime($date);
        foreach ($this->rates as $rate) {
            $startDate = new DateTime($rate['date_start']);
            $endDate = new DateTime($rate['date_end']);

            if ($checkDate >= $startDate && $checkDate <= $endDate) {
                return $rate;
            }
        }
        return null;
    }

    public function calculateAge($currentDate, $birthDate) {
        $current = new DateTime($currentDate);
        $birth = new DateTime($birthDate);

        if ($birth->format('m') < $current->format('m')) {
            return (int)$current->format('Y') - (int)$birth->format('Y');
        } elseif ($birth->format('m') == $current->format('m')) {
            if ($birth->format('d') <= $current->format('d')) {
                return (int)$current->format('Y') - (int)$birth->format('Y');
            } else {
                return (int)$current->format('Y') - (int)$birth->format('Y') - 1;
            }
        } else {
            return (int)$current->format('Y') - (int)$birth->format('Y') - 1;
        }
    }

    public function mergeProlongations($arrets) {
        usort($arrets, function($a, $b) {
            return strtotime($a['arret-from-line']) - strtotime($b['arret-from-line']);
        });

        $merged = [];
        foreach ($arrets as $arret) {
            if (empty($merged)) {
                $merged[] = $arret;
                continue;
            }

            $last = &$merged[count($merged) - 1];
            $lastEnd = new DateTime($last['arret-to-line']);
            $currentStart = new DateTime($arret['arret-from-line']);

            $lastEnd->modify('+1 weekday');

            if ($lastEnd->format('Y-m-d') == $currentStart->format('Y-m-d')) {
                $last['arret-to-line'] = $arret['arret-to-line'];
            } else {
                $merged[] = $arret;
            }
        }

        return $merged;
    }

    public function calculateDateEffet($arrets, $birthDate = null, $previousCumulDays = 0) {
        $arrets = $this->mergeProlongations($arrets);

        $cumulDays = $previousCumulDays;
        $dateEffetArret = null;

        foreach ($arrets as $index => &$arret) {
            $startDate = new DateTime($arret['arret-from-line']);
            $endDate = new DateTime($arret['arret-to-line']);
            $duration = $startDate->diff($endDate)->days + 1;
            $cumulDays += $duration;

            // Si la date est forcée, ignorer le calcul
            if (isset($arret['date-effet-forced'])) {
                $arret['date-effet'] = $arret['date-effet-forced'];
                continue;
            }

            // Premier passage de 90 jours - calculer la date d'effet pour le sinistre
            if ($cumulDays > 90 && $dateEffetArret === null) {
                $daysIntoThreshold = 90 - ($cumulDays - $duration);
                $dateEffet = clone $startDate;
                $dateEffet->modify("+$daysIntoThreshold days");

                // Gérer les DT non excusées (dt-line = 'N' signifie non excusée)
                $maxDate = clone $dateEffet;
                if (isset($arret['dt-line']) && $arret['dt-line'] == 0 && isset($arret['declaration-date-line']) && !empty($arret['declaration-date-line'])) {
                    $dtDate = new DateTime($arret['declaration-date-line']);
                    $dtDate->modify('+30 days');
                    if ($dtDate > $maxDate) {
                        $maxDate = $dtDate;
                    }
                }

                // Gérer la mise à jour du membre GPM (gpm-member-line = 'O' signifie oui)
                if (isset($arret['dt-line']) && strtoupper($arret['dt-line']) == 1 && isset($arret['date_maj_compte']) && !empty($arret['date_maj_compte'])) {
                    $gpmDate = new DateTime($arret['date_maj_compte']);
                    $gpmDate->modify('+30 days');
                    if ($gpmDate > $maxDate) {
                        $maxDate = $gpmDate;
                    }
                }

                $arret['date-effet'] = $maxDate->format('Y-m-d');
                $dateEffetArret = $index;
            }
            // Arrêt consécutif ou rechute non consécutive après 90 jours
            elseif ($cumulDays > 90 && $dateEffetArret !== null && $index > 0) {
                $prevEndDate = new DateTime($arrets[$index - 1]['arret-to-line']);
                $prevEndDate->modify('+1 day');

                // Arrêt consécutif
                if ($prevEndDate->format('Y-m-d') == $startDate->format('Y-m-d')) {
                    $maxDate = clone $startDate;

                    // Gérer les DT non excusées
                    if (isset($arret['dt-line']) && $arret['dt-line'] == '1' && isset($arret['declaration-date-line']) && !empty($arret['declaration-date-line'])) {
                        $dtDate = new DateTime($arret['declaration-date-line']);
                        $dtDate->modify('+31 days');
                        if ($dtDate > $maxDate) {
                            $maxDate = $dtDate;
                        }
                    }

                    // Gérer la mise à jour du membre GPM
                    if (isset($arret['gpm-member-line']) && $arret['gpm-member-line'] == '1' && isset($arret['declaration-date-line']) && !empty($arret['declaration-date-line'])) {
                        $gpmDate = new DateTime($arret['declaration-date-line']);
                        $gpmDate->modify('+31 days');
                        if ($gpmDate > $maxDate) {
                            $maxDate = $gpmDate;
                        }
                    }

                    if ($maxDate <= $endDate) {
                        $arret['date-effet'] = $maxDate->format('Y-m-d');
                    }
                }
                // Rechute avec droits au 1er jour
                elseif (isset($arret['rechute-line']) && $arret['rechute-line'] == '1') {
                    $arret['date-effet'] = $startDate->format('Y-m-d');
                }
                // Rechute non consécutive avec droits au 15ème jour
                else {
                    if ($duration >= 15) {
                        $dateEffet = clone $startDate;
                        $dateEffet->modify('+14 days');
                        $arret['date-effet'] = $dateEffet->format('Y-m-d');
                    }
                }
            }
        }

        return $arrets;
    }

    public function calculatePayableDays($arrets, $attestationDate = null, $lastPaymentDate = null, $currentDate = null) {
        $lastPayment = $lastPaymentDate ? new DateTime($lastPaymentDate) : null;
        $current = $currentDate ? new DateTime($currentDate) : new DateTime();

        $totalDays = 0;
        $paymentDetails = [];

        foreach ($arrets as $index => $arret) {
            if (!isset($arret['date-effet'])) {
                $paymentDetails[$index] = [
                    'arret_index' => $index,
                    'arret_from' => $arret['arret-from-line'],
                    'arret_to' => $arret['arret-to-line'],
                    'date_effet' => null,
                    'attestation_date' => null,
                    'payment_start' => null,
                    'payment_end' => null,
                    'payable_days' => 0,
                    'reason' => 'No date effet'
                ];
                continue;
            }

            // Utiliser la date d'attestation spécifique à l'arrêt, ou utiliser la globale
            $arretAttestationDate = $arret['attestation-date-line'] ?? $attestationDate;

            $dateEffet = new DateTime($arret['date-effet']);
            $endDate = new DateTime($arret['arret-to-line']);

            // Si pas d'attestation, utiliser la date de fin de l'arrêt
            if (!$arretAttestationDate) {
                // Sans attestation, calculer jusqu'à la date de fin de l'arrêt ou date actuelle
                $attestation = min($endDate, $current);
                $arretAttestationDate = null; // Pour tracking
            } else {
                $attestation = new DateTime($arretAttestationDate);

                // Si l'attestation est après le 27, prolonger jusqu'à la fin du mois
                if ((int)$attestation->format('d') >= 27) {
                    $attestation->modify('last day of this month');
                }
            }

            $paymentStart = $dateEffet;
            if ($lastPayment && $lastPayment > $dateEffet && $lastPayment < $endDate) {
                $paymentStart = clone $lastPayment;
            }

            if ($lastPayment && $lastPayment < $dateEffet) {
                $paymentStart = $dateEffet;
            }

            $paymentEnd = min($endDate, $attestation);

            $arretDays = 0;
            if ($paymentStart <= $paymentEnd) {
                // Si date de début == date de fin, payer 0 jours (règle exclusive VBA)
                if ($paymentStart->format('Y-m-d') === $paymentEnd->format('Y-m-d')) {
                    $arretDays = 0;
                } else {
                    $arretDays = $paymentStart->diff($paymentEnd)->days + 1;
                }
            }

            $totalDays += $arretDays;

            $paymentDetails[$index] = [
                'arret_index' => $index,
                'arret_from' => $arret['arret-from-line'],
                'arret_to' => $arret['arret-to-line'],
                'arret_diff' => $arret['arret_diff'] ?? null,
                'date_effet' => $arret['date-effet'],
                'attestation_date' => $arretAttestationDate,
                'attestation_date_extended' => $attestation->format('Y-m-d'),
                'payment_start' => $paymentStart->format('Y-m-d'),
                'payment_end' => $paymentEnd->format('Y-m-d'),
                'payable_days' => $arretDays,
                'reason' => $arretDays > 0 ? ($arretAttestationDate ? 'Paid' : 'Paid (no attestation - calculated to end date)') : 'Outside payment period'
            ];
        }

        return [
            'total_days' => $totalDays,
            'payment_details' => $paymentDetails
        ];
    }

    public function calculateEndPaymentDates($arrets, $previousCumulDays, $birthDate, $currentDate) {
        $age = $this->calculateAge($currentDate, $birthDate);
        $firstDateEffet = null;

        foreach ($arrets as $arret) {
            if (isset($arret['date-effet'])) {
                $firstDateEffet = new DateTime($arret['date-effet']);
                break;
            }
        }

        if (!$firstDateEffet) {
            return null;
        }

        $result = [];

        if ($age >= 70) {
            // Période unique de 365 jours pour 70+
            $endDate = clone $firstDateEffet;
            $endDate->modify('+' . (365 - $previousCumulDays) . ' days');
            $endDate->modify('-1 day');
            $result['end_period_1'] = $endDate->format('Y-m-d');
        } elseif ($age >= 62) {
            // Trois périodes pour 62-69
            $endDate1 = clone $firstDateEffet;
            $endDate1->modify('+' . (365 - $previousCumulDays) . ' days');
            $endDate1->modify('-1 day');
            $result['end_period_1'] = $endDate1->format('Y-m-d');

            $endDate2 = clone $firstDateEffet;
            $endDate2->modify('+' . (730 - $previousCumulDays) . ' days');
            $endDate2->modify('-1 day');
            $result['end_period_2'] = $endDate2->format('Y-m-d');

            $endDate3 = clone $firstDateEffet;
            $endDate3->modify('+' . (1095 - $previousCumulDays) . ' days');
            $endDate3->modify('-1 day');
            $result['end_period_3'] = $endDate3->format('Y-m-d');
        }

        return $result;
    }

    public function calculateAmount($data) {
        $arrets = $data['arrets'];
        $classe = strtoupper($data['classe']);
        $statut = strtoupper($data['statut']);
        $option = $data['option'] ?? '0,25';
        $birthDate = $data['birth_date'];
        $currentDate = $data['current_date'] ?? date('Y-m-d');
        $previousCumulDays = $data['previous_cumul_days'] ?? 0;
        $affiliationDate = $data['affiliation_date'] ?? null;
        $nbTrimestres = $data['nb_trimestres'] ?? 0;
        $pathoAnterior = $data['patho_anterior'] ?? false;
        $attestationDate = $data['attestation_date'] ?? null;
        $lastPaymentDate = $data['last_payment_date'] ?? null;
        $forcedRate = $data['forced_rate'] ?? null;
        $prorata = $data['prorata'] ?? 1;
        $firstPathologyStopDate = $data['first_pathology_stop_date'] ?? null;
        $historicalReducedRate = $data['historical_reduced_rate'] ?? null;

        // Auto-calculer les trimestres si la date d'affiliation est fournie
        // IMPORTANT: Pour pathologie antérieure, calculer jusqu'à la date du 1er arrêt pour cette pathologie
        if ($affiliationDate && !empty($affiliationDate) && $affiliationDate !== '0000-00-00') {
            $endDateForTrimestres = $currentDate;
            if ($pathoAnterior && $firstPathologyStopDate && !empty($firstPathologyStopDate)) {
                $endDateForTrimestres = $firstPathologyStopDate;
            }
            $nbTrimestres = $this->calculateTrimesters($affiliationDate, $endDateForTrimestres);
        }

        // Calculer les dates d'effet
        $arrets = $this->calculateDateEffet($arrets, $birthDate, $previousCumulDays);

        // Calculer les jours indemnisables
        $paymentResult = $this->calculatePayableDays($arrets, $attestationDate, $lastPaymentDate, $currentDate);
        $nbJours = $paymentResult['total_days'];
        $paymentDetails = $paymentResult['payment_details'];

        // Vérifier l'affiliation minimale
        if ($nbTrimestres < 8) {
            $nbJours = 0;
        }

        // Vérifier le maximum de 3 ans
        if ($previousCumulDays > 1095) {
            $nbJours = 0;
        }

        // Vérifier la limite 70+
        $age = $this->calculateAge($currentDate, $birthDate);
        if ($age >= 70) {
            if ($nbJours + $previousCumulDays > 365) {
                $nbJours = 365 - $previousCumulDays;
                if ($nbJours < 0) $nbJours = 0;
            }
        }

        // Obtenir l'année pour la table des taux
        $firstDateEffet = null;
        foreach ($arrets as $arret) {
            if (isset($arret['date-effet'])) {
                $firstDateEffet = $arret['date-effet'];
                break;
            }
        }
        $year = $firstDateEffet ? (int)date('Y', strtotime($firstDateEffet)) : (int)date('Y');

        // Calculer le montant en fonction de l'âge et des jours cumulés avec les détails des taux
        $calculationResult = $this->calculateMontantByAgeWithDetails($nbJours, $previousCumulDays, $age, $classe, $statut, $option, $year, $nbTrimestres, $pathoAnterior, $paymentDetails, $affiliationDate, $currentDate, $birthDate, $historicalReducedRate);
        $amount = $calculationResult['montant'];
        $paymentDetails = $calculationResult['payment_details'];

        // Appliquer le taux forcé si fourni
        if ($forcedRate !== null) {
            $amount = $forcedRate;
        }

        // Appliquer le prorata
        $amount *= $prorata;

        // Calculer les dates de fin de paiement
        $endDates = $this->calculateEndPaymentDates($arrets, $previousCumulDays, $birthDate, $currentDate);

        return [
            'nb_jours' => $nbJours,
            'montant' => round($amount, 2),
            'arrets' => $arrets,
            'payment_details' => $paymentDetails,
            'end_payment_dates' => $endDates,
            'total_cumul_days' => $previousCumulDays + $nbJours,
            'age' => $age,
            'nb_trimestres' => $nbTrimestres
        ];
    }

    private function calculateMontantByAge($nbJours, $cumulJoursAnciens, $age, $classe, $statut, $option, $year, $nbTrimestres, $pathoAnterior) {
        $montant = 0;
        $tauxBase = 1;

        // Pathology anterior adjustments
        if ($pathoAnterior) {
            if ($nbTrimestres <= 15 && $nbTrimestres > 7) {
                $tauxBase = 3;
            } elseif ($nbTrimestres <= 23 && $nbTrimestres > 15) {
                $tauxBase = 2;
            }
        }

        if ($age < 62) {
            $taux = $tauxBase;
            $dailyRate = $this->getRate($statut, $classe, $option, $taux, $year);
            $montant = $nbJours * $dailyRate;
        } elseif ($age >= 62 && $age <= 69) {
            // Three-tier calculation
            $tauxAdjust = 0;
            if ($pathoAnterior && $nbTrimestres <= 15 && $nbTrimestres > 7) {
                $tauxAdjust = 1;
            } elseif ($pathoAnterior && $nbTrimestres <= 23 && $nbTrimestres > 15) {
                $tauxAdjust = 2;
            }

            $nbJoursRestants = $nbJours;
            $cumulActuel = $cumulJoursAnciens;

            // Period 1: 0-365 days
            if ($cumulActuel + $nbJoursRestants <= 365) {
                $joursP1 = $nbJoursRestants;
                $taux = 1 + $tauxAdjust;
                $dailyRate = $this->getRate($statut, $classe, $option, $taux, $year);
                $montant += $joursP1 * $dailyRate;
            } elseif ($cumulActuel < 365) {
                $joursP1 = 365 - $cumulActuel;
                $taux = 1 + $tauxAdjust;
                $dailyRate = $this->getRate($statut, $classe, $option, $taux, $year);
                $montant += $joursP1 * $dailyRate;
                $nbJoursRestants -= $joursP1;
                $cumulActuel += $joursP1;
            }

            // Period 2: 366-730 days
            if ($nbJoursRestants > 0) {
                if ($cumulActuel + $nbJoursRestants <= 730) {
                    $joursP2 = $nbJoursRestants;
                    $taux = 7 + $tauxAdjust;
                    $dailyRate = $this->getRate($statut, $classe, $option, $taux, $year);
                    $montant += $joursP2 * $dailyRate;
                } elseif ($cumulActuel < 730) {
                    $joursP2 = 730 - $cumulActuel;
                    $taux = 7 + $tauxAdjust;
                    $dailyRate = $this->getRate($statut, $classe, $option, $taux, $year);
                    $montant += $joursP2 * $dailyRate;
                    $nbJoursRestants -= $joursP2;
                    $cumulActuel += $joursP2;
                }
            }

            // Period 3: 731-1095 days
            if ($nbJoursRestants > 0) {
                if ($cumulActuel + $nbJoursRestants <= 1095) {
                    $joursP3 = $nbJoursRestants;
                    $taux = 4 + $tauxAdjust;
                    $dailyRate = $this->getRate($statut, $classe, $option, $taux, $year);
                    $montant += $joursP3 * $dailyRate;
                } elseif ($cumulActuel < 1095) {
                    $joursP3 = 1095 - $cumulActuel;
                    $taux = 4 + $tauxAdjust;
                    $dailyRate = $this->getRate($statut, $classe, $option, $taux, $year);
                    $montant += $joursP3 * $dailyRate;
                }
            }
        } else { // age >= 70
            $tauxBase = 4;
            if ($pathoAnterior && $nbTrimestres <= 15 && $nbTrimestres > 7) {
                $tauxBase = 6;
            } elseif ($pathoAnterior && $nbTrimestres <= 23 && $nbTrimestres > 15) {
                $tauxBase = 5;
            }
            $dailyRate = $this->getRate($statut, $classe, $option, $tauxBase, $year);
            $montant = $nbJours * $dailyRate;
        }

        return $montant;
    }

    /**
     * Détermine le numéro de taux selon les règles métier (système 27 taux)
     *
     * Taux 1-3 : < 62 ans (taux plein, -1/3, -2/3)
     * Taux 4-6 : >= 70 ans ou après 1 an au taux 7-9 (taux réduit, -1/3, -2/3)
     * Taux 7-9 : 62-69 ans après 1 an au taux plein (taux -25%, -1/3, -2/3)
     *
     * @param int $age Age du médecin
     * @param int $nbTrimestres Nombre de trimestres d'affiliation
     * @param bool $pathoAnterior Pathologie antérieure au dernier régime
     * @param int|null $historicalReducedRate Taux historique si déjà appliqué pour cette pathologie
     * @return int Numéro de taux (1-9)
     */
    private function determineTauxNumber($age, $nbTrimestres, $pathoAnterior, $historicalReducedRate = null) {
        // Si taux historique déjà appliqué pour cette pathologie, le conserver
        if ($historicalReducedRate !== null) {
            return $historicalReducedRate;
        }

        // Pas de pathologie antérieure OU >= 24 trimestres → Taux plein
        if (!$pathoAnterior || $nbTrimestres >= 24) {
            if ($age < 62) {
                return 1; // Taux plein jeune
            } elseif ($age >= 62 && $age <= 69) {
                return 7; // Taux plein -25% (62-69 après 1 an)
            } else { // >= 70
                return 4; // Taux réduit senior
            }
        }

        // Pathologie antérieure ET < 24 trimestres
        // Si < 8 trimestres → Pas d'indemnisation (géré ailleurs)

        if ($nbTrimestres >= 8 && $nbTrimestres <= 15) {
            // Réduction d'1/3
            if ($age < 62) {
                return 2; // Taux 1 - 1/3
            } elseif ($age >= 62 && $age <= 69) {
                return 8; // Taux 7 - 1/3
            } else { // >= 70
                return 5; // Taux 4 - 1/3
            }
        } elseif ($nbTrimestres >= 16 && $nbTrimestres <= 23) {
            // Réduction de 2/3
            if ($age < 62) {
                return 3; // Taux 1 - 2/3
            } elseif ($age >= 62 && $age <= 69) {
                return 9; // Taux 7 - 2/3
            } else { // >= 70
                return 6; // Taux 4 - 2/3
            }
        }

        // Par défaut (ne devrait pas arriver)
        return 1;
    }

    private function calculateMontantByAgeWithDetails($nbJours, $cumulJoursAnciens, $age, $classe, $statut, $option, $year, $nbTrimestres, $pathoAnterior, $paymentDetails, $affiliationDate = null, $currentDate = null, $birthDate = null, $historicalReducedRate = null) {
        $montant = 0;

        // Ajouter les informations de taux à chaque détail de paiement
        foreach ($paymentDetails as $index => &$detail) {
            if ($detail['payable_days'] <= 0) {
                $detail['daily_rate'] = 0;
                $detail['montant'] = 0;
                continue;
            }

            // Diviser le paiement par année civile si la période couvre plusieurs années
            $paymentStart = new DateTime($detail['payment_start']);
            $paymentEnd = new DateTime($detail['payment_end']);
            $yearlyBreakdown = $this->splitPaymentByYear($paymentStart, $paymentEnd, $birthDate);

            // Calculer les montants en traitant chaque segment selon l'âge à ce moment
            $arretMontant = 0;
            $rateInfo = [];
            $joursDansArret = 0; // Compteur pour les périodes 1/2/3 (pour âges 62-69)

            // Déterminer si l'arrêt a une durée >= 730 jours (pour la logique période 2 des 62-69 ans)
            $arretDiff = $detail['arret_diff'] ?? 0;
            $usePeriode2 = ($arretDiff >= 730);

            foreach ($yearlyBreakdown as $yearData) {
                // Calculer l'âge au début de ce segment
                $segmentAge = $this->calculateAge($yearData['start'], $birthDate);

                // Calculer nb_trimestres pour cette période spécifique
                $periodNbTrimestres = $nbTrimestres;
                if ($affiliationDate && !empty($affiliationDate) && $affiliationDate !== '0000-00-00') {
                    $periodNbTrimestres = $this->calculateTrimesters($affiliationDate, $yearData['start']);
                }

                if ($segmentAge < 62) {
                    // Age < 62 : taux unique selon trimestres
                    // Note : ne PAS incrémenter $joursDansArret ici, car ce compteur est uniquement pour 62-69 ans
                    $taux = $this->determineTauxNumber($segmentAge, $periodNbTrimestres, $pathoAnterior, $historicalReducedRate);
                    $dailyRate = $this->getRate($statut, $classe, $option, $taux, $yearData['year'], $yearData['start'], $segmentAge);
                    $arretMontant += $yearData['days'] * $dailyRate;
                    $trimester = $this->getTrimesterFromDate($yearData['start']);

                    $rateInfo[] = [
                        'year' => $yearData['year'],
                        'month' => $yearData['month'],
                        'trimester' => $trimester,
                        'nb_trimestres' => $periodNbTrimestres,
                        'period' => 1,
                        'start' => $yearData['start'],
                        'end' => $yearData['end'],
                        'days' => $yearData['days'],
                        'rate' => $dailyRate,
                        'taux' => $taux
                    ];
                    // Ne PAS incrémenter $joursDansArret pour age < 62
                } elseif ($segmentAge >= 62 && $segmentAge <= 69) {
                    // Pour 62-69, calculer les périodes par ARRÊT individuel
                    // RÈGLE : Arrêt avec durée >= 730j utilise période 2 (taux 7-9), sinon saute directement à période 3 (taux 4-6)

                    $nbJoursRestants = $yearData['days'];

                    // Traiter les jours de ce segment selon leur position dans L'ARRÊT
                    while ($nbJoursRestants > 0) {
                        // Déterminer dans quelle période de l'arrêt on se trouve
                        if ($joursDansArret < 365) {
                            // Période 1 de cet arrêt : jours 1-365 → taux 1-3 (taux plein)
                            $joursP = min($nbJoursRestants, 365 - $joursDansArret);
                            $periodNumber = 1;

                            if (!$pathoAnterior || $periodNbTrimestres >= 24) {
                                $taux = 1;
                            } elseif ($periodNbTrimestres >= 8 && $periodNbTrimestres <= 15) {
                                $taux = 2;
                            } elseif ($periodNbTrimestres >= 16 && $periodNbTrimestres <= 23) {
                                $taux = 3;
                            } else {
                                $taux = 1;
                            }
                        } elseif ($joursDansArret < 730 && $usePeriode2) {
                            // Période 2 de cet arrêt : jours 366-730 → taux 7-9 (UNIQUEMENT si arret_diff >= 730)
                            $joursP = min($nbJoursRestants, 730 - $joursDansArret);
                            $periodNumber = 2;

                            if (!$pathoAnterior || $periodNbTrimestres >= 24) {
                                $taux = 7;
                            } elseif ($periodNbTrimestres >= 8 && $periodNbTrimestres <= 15) {
                                $taux = 8;
                            } elseif ($periodNbTrimestres >= 16 && $periodNbTrimestres <= 23) {
                                $taux = 9;
                            } else {
                                $taux = 7;
                            }
                        } elseif ($joursDansArret < 1095) {
                            // Période 3 de cet arrêt : jours 366-1095 (ou 731-1095 si usePeriode2) → taux 4-6 (taux réduit)
                            $joursP = min($nbJoursRestants, 1095 - $joursDansArret);
                            $periodNumber = 3;

                            if (!$pathoAnterior || $periodNbTrimestres >= 24) {
                                $taux = 4;
                            } elseif ($periodNbTrimestres >= 8 && $periodNbTrimestres <= 15) {
                                $taux = 5;
                            } elseif ($periodNbTrimestres >= 16 && $periodNbTrimestres <= 23) {
                                $taux = 6;
                            } else {
                                $taux = 4;
                            }
                        } else {
                            // Au-delà de 1095 jours dans cet arrêt
                            break;
                        }

                        $dailyRate = $this->getRate($statut, $classe, $option, $taux, $yearData['year'], $yearData['start'], $segmentAge);
                        $arretMontant += $joursP * $dailyRate;
                        $trimester = $this->getTrimesterFromDate($yearData['start']);

                        $rateInfo[] = [
                            'year' => $yearData['year'],
                            'month' => $yearData['month'],
                            'trimester' => $trimester,
                            'nb_trimestres' => $periodNbTrimestres,
                            'period' => $periodNumber,
                            'start' => $yearData['start'],
                            'end' => $yearData['end'],
                            'days' => $joursP,
                            'rate' => $dailyRate,
                            'taux' => $taux
                        ];

                        $joursDansArret += $joursP;
                        $nbJoursRestants -= $joursP;
                    }
                } else { // segmentAge >= 70
                    // Age >= 70 : taux réduit (taux 4, 5 ou 6)
                    // Note : ne PAS incrémenter $joursDansArret ici, car ce compteur est uniquement pour 62-69 ans
                    $taux = $this->determineTauxNumber($segmentAge, $periodNbTrimestres, $pathoAnterior, $historicalReducedRate);
                    $dailyRate = $this->getRate($statut, $classe, $option, $taux, $yearData['year'], $yearData['start'], $segmentAge);
                    $arretMontant += $yearData['days'] * $dailyRate;
                    $trimester = $this->getTrimesterFromDate($yearData['start']);

                    $rateInfo[] = [
                        'year' => $yearData['year'],
                        'month' => $yearData['month'],
                        'trimester' => $trimester,
                        'nb_trimestres' => $periodNbTrimestres,
                        'period' => 'senior', // Pas de période 1/2/3 pour 70+
                        'start' => $yearData['start'],
                        'end' => $yearData['end'],
                        'days' => $yearData['days'],
                        'rate' => $dailyRate,
                        'taux' => $taux
                    ];
                    // Ne PAS incrémenter $joursDansArret pour age >= 70
                }
            }

            $detail['montant'] = round($arretMontant, 2);
            $detail['rate_breakdown'] = $rateInfo;

            // Generate day-by-day breakdown
            $detail['daily_breakdown'] = $this->generateDailyBreakdown($rateInfo);

            $montant += $detail['montant'];
        }

        return [
            'montant' => $montant,
            'payment_details' => $paymentDetails
        ];
    }

    /**
     * Generate day-by-day payment breakdown from rate info
     *
     * @param array $rateInfo Rate breakdown information
     * @return array Day-by-day payment details
     */
    private function generateDailyBreakdown($rateInfo) {
        $dailyBreakdown = [];

        foreach ($rateInfo as $segment) {
            $startDate = new DateTime($segment['start']);
            $endDate = new DateTime($segment['end']);
            $days = $segment['days'];
            $rate = $segment['rate'];

            // Generate entry for each day in the segment
            for ($i = 0; $i < $days; $i++) {
                $currentDate = clone $startDate;
                $currentDate->modify("+{$i} days");

                $dailyBreakdown[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'day_of_week' => $currentDate->format('l'),
                    'year' => $segment['year'],
                    'month' => $segment['month'],
                    'trimester' => $segment['trimester'],
                    'period' => $segment['period'],
                    'taux' => $segment['taux'],
                    'daily_rate' => $rate,
                    'amount' => round($rate, 2),
                    'nb_trimestres' => $segment['nb_trimestres']
                ];
            }
        }

        return $dailyBreakdown;
    }

    private function splitPaymentByYear($startDate, $endDate, $birthDate = null) {
        $segments = [];
        $current = clone $startDate;

        while ($current <= $endDate) {
            // Obtenir la fin du mois ou la fin de la période de taux, selon ce qui vient en premier
            $year = (int)$current->format('Y');
            $month = (int)$current->format('m');

            // Obtenir la fin du mois en cours
            $monthEnd = new DateTime($current->format('Y-m-t'));

            // Obtenir la période de taux pour la date actuelle
            $rateData = $this->getRateForDate($current->format('Y-m-d'));

            if ($rateData) {
                $periodEnd = new DateTime($rateData['date_end']);
            } else {
                $periodEnd = new DateTime("$year-12-31");
            }

            // Vérifier s'il y a un anniversaire dans cette période (pour les 62-69 ans)
            $birthdayEnd = null;
            if ($birthDate && !empty($birthDate) && $birthDate !== '0000-00-00') {
                $birth = new DateTime($birthDate);
                $birthMonth = (int)$birth->format('m');
                $birthDay = (int)$birth->format('d');

                // Créer la date d'anniversaire pour l'année en cours
                $birthdayThisYear = new DateTime(sprintf('%d-%02d-%02d', $year, $birthMonth, $birthDay));

                // Si l'anniversaire tombe dans le segment actuel
                if ($birthdayThisYear > $current && $birthdayThisYear <= min($monthEnd, $periodEnd, $endDate)) {
                    $birthdayEnd = clone $birthdayThisYear;
                    $birthdayEnd->modify('-1 day'); // Jour avant l'anniversaire
                }
            }

            // Utiliser la date la plus proche : anniversaire-1, fin de mois, fin de période de taux, ou fin de paiement
            if ($birthdayEnd) {
                $segmentEnd = min($birthdayEnd, $monthEnd, $periodEnd, $endDate);
            } else {
                $segmentEnd = min($monthEnd, $periodEnd, $endDate);
            }

            $days = $current->diff($segmentEnd)->days + 1;

            $segments[] = [
                'year' => $year,
                'month' => $month,
                'start' => $current->format('Y-m-d'),
                'end' => $segmentEnd->format('Y-m-d'),
                'days' => $days
            ];

            $current = clone $segmentEnd;
            $current->modify('+1 day');
        }

        return $segments;
    }

    private function getRate($statut, $classe, $option, $taux, $year, $date = null, $age = null) {
        // Utiliser la date si fournie, sinon utiliser l'année
        if ($date) {
            $rateData = $this->getRateForDate($date);
        } else {
            $rateData = $this->getRateForYear($year);
        }

        if (!$rateData) {
            return 0;
        }

        $classeKey = strtolower($classe);

        // Mapper le taux vers la COLONNE CSV
        // Les colonnes CSV représentent :
        // - taux_X1 : Taux plein (< 62 ans, OU périodes 1+2 pour 62-69 ans)
        // - taux_X2 : Taux réduit senior (≥ 70 ans uniquement)
        // - taux_X3 : Taux intermédiaire (période 3 pour 62-69 ans, jours 731-1095)
        //
        // Mapping des taux (1-9) vers les colonnes CSV :
        // - Taux 1-3 (< 62 ans, période 1 pour 62-69) → colonne 1 (taux plein)
        // - Taux 7-9 (période 2 pour 62-69 ans, 366-730j) → colonne 3 (taux intermédiaire)
        // - Taux 4-6 (période 3 pour 62-69, OU ≥ 70 ans) → colonne 3 pour 62-69, colonne 2 pour 70+
        if ($taux >= 1 && $taux <= 3) {
            $tier = 1; // Taux plein
        } elseif ($taux >= 7 && $taux <= 9) {
            $tier = 3; // Taux intermédiaire pour période 2 des 62-69 ans
        } elseif ($taux >= 4 && $taux <= 6) {
            // Pour période 3 : taux_a3 pour 62-69 ans, taux_a2 pour 70+ ans
            // Par défaut on utilise tier=3, mais si age >= 70 on utilisera tier=2
            if ($age !== null && $age >= 70) {
                $tier = 2; // Taux réduit senior pour 70+
            } else {
                $tier = 3; // Taux intermédiaire pour période 3 des 62-69 ans
            }
        } else {
            $tier = 1; // par défaut
        }

        $columnKey = "taux_{$classeKey}{$tier}";
        $baseRate = isset($rateData[$columnKey]) ? (float)$rateData[$columnKey] : 0;

        // Appliquer le multiplicateur d'option pour CCPL et RSPM
        if (in_array(strtoupper($statut), ['CCPL', 'RSPM'])) {
            // Convertir l'option du format chaîne (ex: "0,25" ou "0.25") en float
            $optionValue = (float)str_replace(',', '.', $option);

            // Gérer les deux formats : 0.25 (déjà décimal) ou 25 (pourcentage à convertir)
            if ($optionValue > 1) {
                $optionValue = $optionValue / 100; // Convertir 25 -> 0.25, 100 -> 1.0
            }

            // L'option représente un pourcentage : 0.25 = 25%, 0.5 = 50%, 1 = 100%
            if ($optionValue > 0 && $optionValue <= 1) {
                $baseRate *= $optionValue;
            }
        }

        return $baseRate;
    }

    /**
     * Déterminer automatiquement la classe du médecin basée sur les revenus N-2
     *
     * RÈGLES:
     * - Classe A: Revenus < 1 PASS
     * - Classe B: Revenus entre 1 PASS et 3 PASS
     * - Classe C: Revenus > 3 PASS
     * - Si revenus non communiqués (taxé d'office): Classe A
     * - La classe est définie à la date d'ouverture des droits
     *
     * @param float|null $revenuNMoins2 Revenus de l'année N-2
     * @param string|null $dateOuvertureDroits Date d'ouverture des droits (pour déterminer l'année N)
     * @param bool $taxeOffice Si true, applique la classe A d'office (revenus non communiqués)
     * @return string Classe déterminée (A, B ou C)
     */
    public function determineClasse($revenuNMoins2 = null, $dateOuvertureDroits = null, $taxeOffice = false) {
        // Si taxé d'office (revenus non communiqués), retourner classe A
        if ($taxeOffice || $revenuNMoins2 === null) {
            return 'A';
        }

        // Obtenir la valeur du PASS pour l'année appropriée
        $passValue = $this->passValue;

        // Si une date d'ouverture des droits est fournie, utiliser le PASS de cette année
        if ($dateOuvertureDroits) {
            $year = (int)date('Y', strtotime($dateOuvertureDroits));
            $rateData = $this->getRateForYear($year);
            // Note: Le PASS peut varier par année, mais pour simplifier on utilise la valeur configurée
            // Dans une implémentation complète, on pourrait avoir un tableau de PASS par année
        }

        // Déterminer la classe selon les revenus
        if ($revenuNMoins2 < $passValue) {
            return 'A';
        } elseif ($revenuNMoins2 <= (3 * $passValue)) {
            return 'B';
        } else {
            return 'C';
        }
    }

    /**
     * Obtenir l'année N-2 à partir d'une date d'ouverture des droits
     *
     * @param string $dateOuvertureDroits Date d'ouverture des droits
     * @return int Année N-2
     */
    public function getAnneeNMoins2($dateOuvertureDroits) {
        $anneeN = (int)date('Y', strtotime($dateOuvertureDroits));
        return $anneeN - 2;
    }

    public function calculateRevenuAnnuel($classe, $revenu = null) {
        // For doctors, revenue per day calculation:
        // Class A: montant_1_pass / 730
        // Class B: revenu / 730 (requires actual revenue parameter)
        // Class C: (montant_1_pass * 3) / 730

        $classe = strtoupper($classe);

        switch ($classe) {
            case 'A':
                // Classe A: montant_1_pass / 730
                return [
                    'classe' => 'A',
                    'nb_pass' => 1,
                    'revenu_annuel' => $this->passValue,
                    'revenu_per_day' => $this->passValue / 730,
                    'pass_value' => $this->passValue
                ];

            case 'B':
                // Classe B: revenu / 730
                // If no revenue provided, use 2 PASS as default (middle of range)
                if ($revenu === null) {
                    $revenu = 2 * $this->passValue;
                }
                return [
                    'classe' => 'B',
                    'revenu_annuel' => $revenu,
                    'revenu_per_day' => $revenu / 730,
                    'nb_pass' => $revenu / $this->passValue,
                    'pass_value' => $this->passValue
                ];

            case 'C':
                // Classe C: (montant_1_pass * 3) / 730
                return [
                    'classe' => 'C',
                    'nb_pass' => 3,
                    'revenu_annuel' => $this->passValue * 3,
                    'revenu_per_day' => ($this->passValue * 3) / 730,
                    'pass_value' => $this->passValue
                ];

            default:
                // Default to Class A
                return [
                    'classe' => 'A',
                    'nb_pass' => 1,
                    'revenu_annuel' => $this->passValue,
                    'revenu_per_day' => $this->passValue / 730,
                    'pass_value' => $this->passValue
                ];
        }
    }
}
