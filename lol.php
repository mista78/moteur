<?php

class IJCalculator {
    private $rates = [];
    private $passValue = 47000; // PASS CPAM default value

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

            // If date is forced, skip calculation
            if (isset($arret['date-effet-forced'])) {
                $arret['date-effet'] = $arret['date-effet-forced'];
                continue;
            }

            // First time passing 90 days - calculate date effet for the sinistre
            if ($cumulDays > 90 && $dateEffetArret === null) {
                $daysIntoThreshold = 90 - ($cumulDays - $duration);
                $dateEffet = clone $startDate;
                $dateEffet->modify("+$daysIntoThreshold days");

                // Handle unexcused DT (dt-line = 'N' means not excused)
                $maxDate = clone $dateEffet;
                if (isset($arret['dt-line']) && strtoupper($arret['dt-line']) == 'N' && isset($arret['declaration-date-line']) && !empty($arret['declaration-date-line'])) {
                    $dtDate = new DateTime($arret['declaration-date-line']);
                    $dtDate->modify('+31 days');
                    if ($dtDate > $maxDate) {
                        $maxDate = $dtDate;
                    }
                }

                // Handle GPM member update (gpm-member-line = 'O' means yes)
                if (isset($arret['gpm-member-line']) && strtoupper($arret['gpm-member-line']) == 'O' && isset($arret['declaration-date-line']) && !empty($arret['declaration-date-line'])) {
                    $gpmDate = new DateTime($arret['declaration-date-line']);
                    $gpmDate->modify('+31 days');
                    if ($gpmDate > $maxDate) {
                        $maxDate = $gpmDate;
                    }
                }

                $arret['date-effet'] = $maxDate->format('Y-m-d');
                $dateEffetArret = $index;
            }
            // Consecutive arrêt or non-consecutive rechute after 90 days
            elseif ($cumulDays > 90 && $dateEffetArret !== null && $index > 0) {
                $prevEndDate = new DateTime($arrets[$index - 1]['arret-to-line']);
                $prevEndDate->modify('+1 day');

                // Consecutive arrêt
                if ($prevEndDate->format('Y-m-d') == $startDate->format('Y-m-d')) {
                    $maxDate = clone $startDate;

                    // Handle unexcused DT
                    if (isset($arret['dt-line']) && $arret['dt-line'] == '1' && isset($arret['declaration-date-line']) && !empty($arret['declaration-date-line'])) {
                        $dtDate = new DateTime($arret['declaration-date-line']);
                        $dtDate->modify('+31 days');
                        if ($dtDate > $maxDate) {
                            $maxDate = $dtDate;
                        }
                    }

                    // Handle GPM member update
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
                // Rechute with 1st day rights
                elseif (isset($arret['rechute-line']) && $arret['rechute-line'] == '1') {
                    $arret['date-effet'] = $startDate->format('Y-m-d');
                }
                // Non-consecutive rechute with 15th day rights
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

            // Use arrêt-specific attestation date, or fall back to global one
            $arretAttestationDate = $arret['attestation-date-line'] ?? $attestationDate;

            if (!$arretAttestationDate) {
                $paymentDetails[$index] = [
                    'arret_index' => $index,
                    'arret_from' => $arret['arret-from-line'],
                    'arret_to' => $arret['arret-to-line'],
                    'date_effet' => $arret['date-effet'],
                    'attestation_date' => null,
                    'payment_start' => null,
                    'payment_end' => null,
                    'payable_days' => 0,
                    'reason' => 'No attestation date'
                ];
                continue;
            }

            $attestation = new DateTime($arretAttestationDate);
            $originalAttestation = clone $attestation;

            // If attestation after day 27, extend to end of month
            if ((int)$attestation->format('d') >= 27) {
                $attestation->modify('last day of this month');
            }

            $dateEffet = new DateTime($arret['date-effet']);
            $endDate = new DateTime($arret['arret-to-line']);

            $paymentStart = $dateEffet;
            if ($lastPayment && $lastPayment > $dateEffet && $lastPayment < $endDate) {
                $paymentStart = clone $lastPayment;
            }

            if ($lastPayment && $lastPayment < $dateEffet) {
                $paymentStart = $dateEffet;
            }

            $paymentEnd = min($endDate, $attestation);

            $arretDays = 0;
            if ($paymentStart < $paymentEnd && $paymentStart < $attestation) {
                if ($attestation > $dateEffet) {
                    if ($attestation < $endDate) {
                        $arretDays = $paymentStart->diff($attestation)->days + 1;
                    } else {
                        $arretDays = $paymentStart->diff($endDate)->days + 1;
                    }
                }
            }

            $totalDays += $arretDays;

            $paymentDetails[$index] = [
                'arret_index' => $index,
                'arret_from' => $arret['arret-from-line'],
                'arret_to' => $arret['arret-to-line'],
                'date_effet' => $arret['date-effet'],
                'attestation_date' => $arretAttestationDate,
                'attestation_date_extended' => $attestation->format('Y-m-d'),
                'payment_start' => $paymentStart->format('Y-m-d'),
                'payment_end' => $paymentEnd->format('Y-m-d'),
                'payable_days' => $arretDays,
                'reason' => $arretDays > 0 ? 'Paid' : 'Outside payment period'
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
            // Single 365 day period for 70+
            $endDate = clone $firstDateEffet;
            $endDate->modify('+' . (365 - $previousCumulDays) . ' days');
            $endDate->modify('-1 day');
            $result['end_period_1'] = $endDate->format('Y-m-d');
        } elseif ($age >= 62) {
            // Three periods for 62-69
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
        $nbTrimestres = $data['nb_trimestres'] ?? 0;
        $pathoAnterior = $data['patho_anterior'] ?? false;
        $attestationDate = $data['attestation_date'] ?? null;
        $lastPaymentDate = $data['last_payment_date'] ?? null;
        $forcedRate = $data['forced_rate'] ?? null;
        $prorata = $data['prorata'] ?? 1;

        // Calculate dates effet
        $arrets = $this->calculateDateEffet($arrets, $birthDate, $previousCumulDays);

        // Calculate payable days
        $paymentResult = $this->calculatePayableDays($arrets, $attestationDate, $lastPaymentDate, $currentDate);
        $nbJours = $paymentResult['total_days'];
        $paymentDetails = $paymentResult['payment_details'];

        // Check minimum affiliation
        if ($nbTrimestres < 8) {
            $nbJours = 0;
        }

        // Check maximum 3 years
        if ($previousCumulDays > 1095) {
            $nbJours = 0;
        }

        // Check 70+ limit
        $age = $this->calculateAge($currentDate, $birthDate);
        if ($age >= 70) {
            if ($nbJours + $previousCumulDays > 365) {
                $nbJours = 365 - $previousCumulDays;
                if ($nbJours < 0) $nbJours = 0;
            }
        }

        // Get year for rate table
        $firstDateEffet = null;
        foreach ($arrets as $arret) {
            if (isset($arret['date-effet'])) {
                $firstDateEffet = $arret['date-effet'];
                break;
            }
        }
        $year = $firstDateEffet ? (int)date('Y', strtotime($firstDateEffet)) : (int)date('Y');

        // Calculate amount based on age and cumulative days with rate details
        $calculationResult = $this->calculateMontantByAgeWithDetails($nbJours, $previousCumulDays, $age, $classe, $statut, $option, $year, $nbTrimestres, $pathoAnterior, $paymentDetails);
        $amount = $calculationResult['montant'];
        $paymentDetails = $calculationResult['payment_details'];

        // Apply forced rate if provided
        if ($forcedRate !== null) {
            $amount = $forcedRate;
        }

        // Apply prorata
        $amount *= $prorata;

        // Calculate end payment dates
        $endDates = $this->calculateEndPaymentDates($arrets, $previousCumulDays, $birthDate, $currentDate);

        return [
            'nb_jours' => $nbJours,
            'montant' => round($amount, 2),
            'arrets' => $arrets,
            'payment_details' => $paymentDetails,
            'end_payment_dates' => $endDates,
            'total_cumul_days' => $previousCumulDays + $nbJours,
            'age' => $age
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

    private function calculateMontantByAgeWithDetails($nbJours, $cumulJoursAnciens, $age, $classe, $statut, $option, $year, $nbTrimestres, $pathoAnterior, $paymentDetails) {
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

        // Add rate information to each payment detail
        foreach ($paymentDetails as $index => &$detail) {
            if ($detail['payable_days'] <= 0) {
                $detail['daily_rate'] = 0;
                $detail['montant'] = 0;
                continue;
            }

            // Split payment by calendar year if period spans multiple years
            $paymentStart = new DateTime($detail['payment_start']);
            $paymentEnd = new DateTime($detail['payment_end']);
            $yearlyBreakdown = $this->splitPaymentByYear($paymentStart, $paymentEnd);

            if ($age < 62) {
                $taux = $tauxBase;
                $arretMontant = 0;
                $rateInfo = [];

                foreach ($yearlyBreakdown as $yearData) {
                    $dailyRate = $this->getRate($statut, $classe, $option, $taux, $yearData['year'], $yearData['start']);
                    $arretMontant += $yearData['days'] * $dailyRate;
                    $rateInfo[] = [
                        'year' => $yearData['year'],
                        'days' => $yearData['days'],
                        'rate' => $dailyRate,
                        'taux' => $taux
                    ];
                }

                $detail['montant'] = round($arretMontant, 2);
                $detail['rate_breakdown'] = $rateInfo;
                $montant += $detail['montant'];
            } elseif ($age >= 62 && $age <= 69) {
                // For 62-69, we need to calculate based on cumulative position AND split by year
                $tauxAdjust = 0;
                if ($pathoAnterior && $nbTrimestres <= 15 && $nbTrimestres > 7) {
                    $tauxAdjust = 1;
                } elseif ($pathoAnterior && $nbTrimestres <= 23 && $nbTrimestres > 15) {
                    $tauxAdjust = 2;
                }

                // Calculate cumulative days up to this arrêt
                $cumulBeforeArret = $cumulJoursAnciens;
                for ($i = 0; $i < $index; $i++) {
                    $cumulBeforeArret += $paymentDetails[$i]['payable_days'];
                }

                $arretMontant = 0;
                $rateInfo = [];

                // Process each year segment
                foreach ($yearlyBreakdown as $yearData) {
                    $nbJoursRestants = $yearData['days'];
                    $cumulActuel = $cumulBeforeArret;

                    // Period 1: 0-365 days
                    if ($cumulActuel < 365 && $nbJoursRestants > 0) {
                        $joursP1 = min($nbJoursRestants, 365 - $cumulActuel);
                        $taux = 1 + $tauxAdjust;
                        $dailyRate = $this->getRate($statut, $classe, $option, $taux, $yearData['year'], $yearData['start']);
                        $arretMontant += $joursP1 * $dailyRate;
                        $rateInfo[] = [
                            'year' => $yearData['year'],
                            'period' => 1,
                            'days' => $joursP1,
                            'rate' => $dailyRate,
                            'taux' => $taux
                        ];
                        $nbJoursRestants -= $joursP1;
                        $cumulActuel += $joursP1;
                    }

                    // Period 2: 366-730 days
                    if ($cumulActuel < 730 && $nbJoursRestants > 0) {
                        $joursP2 = min($nbJoursRestants, 730 - $cumulActuel);
                        $taux = 7 + $tauxAdjust;
                        $dailyRate = $this->getRate($statut, $classe, $option, $taux, $yearData['year'], $yearData['start']);
                        $arretMontant += $joursP2 * $dailyRate;
                        $rateInfo[] = [
                            'year' => $yearData['year'],
                            'period' => 2,
                            'days' => $joursP2,
                            'rate' => $dailyRate,
                            'taux' => $taux
                        ];
                        $nbJoursRestants -= $joursP2;
                        $cumulActuel += $joursP2;
                    }

                    // Period 3: 731-1095 days
                    if ($cumulActuel < 1095 && $nbJoursRestants > 0) {
                        $joursP3 = min($nbJoursRestants, 1095 - $cumulActuel);
                        $taux = 4 + $tauxAdjust;
                        $dailyRate = $this->getRate($statut, $classe, $option, $taux, $yearData['year'], $yearData['start']);
                        $arretMontant += $joursP3 * $dailyRate;
                        $rateInfo[] = [
                            'year' => $yearData['year'],
                            'period' => 3,
                            'days' => $joursP3,
                            'rate' => $dailyRate,
                            'taux' => $taux
                        ];
                    }

                    $cumulBeforeArret += $yearData['days'];
                }

                $detail['montant'] = round($arretMontant, 2);
                $detail['rate_breakdown'] = $rateInfo;
                $montant += $detail['montant'];
            } else { // age >= 70
                $tauxBase = 4;
                if ($pathoAnterior && $nbTrimestres <= 15 && $nbTrimestres > 7) {
                    $tauxBase = 6;
                } elseif ($pathoAnterior && $nbTrimestres <= 23 && $nbTrimestres > 15) {
                    $tauxBase = 5;
                }

                $arretMontant = 0;
                $rateInfo = [];

                foreach ($yearlyBreakdown as $yearData) {
                    $dailyRate = $this->getRate($statut, $classe, $option, $tauxBase, $yearData['year'], $yearData['start']);
                    $arretMontant += $yearData['days'] * $dailyRate;
                    $rateInfo[] = [
                        'year' => $yearData['year'],
                        'days' => $yearData['days'],
                        'rate' => $dailyRate,
                        'taux' => $tauxBase
                    ];
                }

                $detail['montant'] = round($arretMontant, 2);
                $detail['rate_breakdown'] = $rateInfo;
                $montant += $detail['montant'];
            }
        }

        return [
            'montant' => $montant,
            'payment_details' => $paymentDetails
        ];
    }

    private function splitPaymentByYear($startDate, $endDate) {
        $segments = [];
        $current = clone $startDate;

        while ($current <= $endDate) {
            // Get the rate period for current date
            $rateData = $this->getRateForDate($current->format('Y-m-d'));

            if (!$rateData) {
                // If no rate found, fall back to year-end
                $year = (int)$current->format('Y');
                $periodEnd = new DateTime("$year-12-31");
            } else {
                // Use the rate period end date
                $periodEnd = new DateTime($rateData['date_end']);
            }

            $segmentEnd = min($periodEnd, $endDate);
            $days = $current->diff($segmentEnd)->days + 1;

            $segments[] = [
                'year' => (int)$current->format('Y'),
                'start' => $current->format('Y-m-d'),
                'end' => $segmentEnd->format('Y-m-d'),
                'days' => $days
            ];

            $current = clone $segmentEnd;
            $current->modify('+1 day');
        }

        return $segments;
    }

    private function getRate($statut, $classe, $option, $taux, $year, $date = null) {
        // Use date if provided, otherwise use year
        if ($date) {
            $rateData = $this->getRateForDate($date);
        } else {
            $rateData = $this->getRateForYear($year);
        }

        if (!$rateData) {
            return 0;
        }

        $classeKey = strtolower($classe);

        // Map taux to column (1-3 for tiers)
        // Period 1 (days 0-365): taux 1,2,3 -> column 1 (highest rate)
        // Period 2 (days 366-730): taux 7,8,9 -> column 3 (lowest rate)
        // Period 3 (days 731-1095): taux 4,5,6 -> column 3 (lowest rate)
        if ($taux >= 1 && $taux <= 3) {
            $tier = 1;
        } elseif ($taux >= 7 && $taux <= 9) {
            $tier = 3; // Changed from 2 to 3
        } elseif ($taux >= 4 && $taux <= 6) {
            $tier = 3;
        } else {
            $tier = 1; // default
        }

        $columnKey = "taux_{$classeKey}{$tier}";
        $baseRate = isset($rateData[$columnKey]) ? (float)$rateData[$columnKey] : 0;

        // Apply option multiplier for CCPL and RSPM
        if (in_array(strtoupper($statut), ['CCPL', 'RSPM'])) {
            // Convert option from string format (e.g., "0,25" or "0.25") to float
            $optionValue = (float)str_replace(',', '.', $option);

            // Option represents percentage: 0.25 = 25%, 0.5 = 50%, 1 = 100%
            if ($optionValue > 0 && $optionValue <= 1) {
                $baseRate *= $optionValue;
            }
        }

        return $baseRate;
    }

    public function calculateRevenuAnnuel($classe, $nbPass = null) {
        // For doctors:
        // Class A = 1 PASS
        // Class B = annual revenue / 730 per PASS
        // Class C = 3 PASS

        $classe = strtoupper($classe);

        if ($nbPass === null) {
            switch ($classe) {
                case 'A':
                    $nbPass = 1;
                    break;
                case 'C':
                    $nbPass = 3;
                    break;
                default:
                    $nbPass = 1;
            }
        }

        if ($classe == 'B') {
            // For class B, calculate based on 730 days
            return [
                'nb_pass' => $nbPass,
                'revenu_annuel' => $nbPass * $this->passValue,
                'revenu_per_day' => ($nbPass * $this->passValue) / 730
            ];
        }

        return [
            'nb_pass' => $nbPass,
            'revenu_annuel' => $nbPass * $this->passValue,
            'pass_value' => $this->passValue
        ];
    }
}
