<?php

namespace IJCalculator\Services;

use DateTime;

/**
 * Service de Calcul de Montants
 * Responsabilité Unique : Calculer les montants IJ basés sur les périodes de paiement, l'âge et les taux
 */
class AmountCalculationService implements AmountCalculationInterface
{
    private DateCalculationInterface $dateService;
    private RateServiceInterface $rateService;
    private TauxDeterminationInterface $tauxService;

    public function __construct(
        DateCalculationInterface $dateService,
        RateServiceInterface $rateService,
        TauxDeterminationInterface $tauxService
    ) {
        $this->dateService = $dateService;
        $this->rateService = $rateService;
        $this->tauxService = $tauxService;
    }

    /**
     * Calculer le montant IJ pour les paramètres donnés
     */
    public function calculateAmount(array $data): array
    {
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
        if ($affiliationDate && !empty($affiliationDate) && $affiliationDate !== '0000-00-00') {
            $endDateForTrimestres = $currentDate;
            if ($pathoAnterior && $firstPathologyStopDate && !empty($firstPathologyStopDate)) {
                $endDateForTrimestres = $firstPathologyStopDate;
            }
            $nbTrimestres = $this->dateService->calculateTrimesters($affiliationDate, $endDateForTrimestres);
        }

        // Calculer la date d'effet
        $arrets = $this->dateService->calculateDateEffet($arrets, $birthDate, $previousCumulDays);

        // Calculer les jours payables
        $paymentResult = $this->dateService->calculatePayableDays($arrets, $attestationDate, $lastPaymentDate, $currentDate);
        $nbJours = $paymentResult['total_days'];
        $paymentDetails = $paymentResult['payment_details'];

        // Vérifier l'affiliation minimale
        if ($nbTrimestres < 8) {
            $nbJours = 0;
        }

        // Vérifier le maximum de 3 ans
        if ($previousCumulDays >= 1095) {
            $nbJours = 0;
        }

        // Calculer l'âge au début des IJ (première date d'effet) et à la date actuelle
        $firstDateEffetForAge = null;
        foreach ($arrets as $arret) {
            if (isset($arret['date-effet'])) {
                $firstDateEffetForAge = $arret['date-effet'];
                break;
            }
        }

        $age = $this->dateService->calculateAge($currentDate, $birthDate);
        $ageAtStart = $firstDateEffetForAge ? $this->dateService->calculateAge($firstDateEffetForAge, $birthDate) : $age;

        // Vérifier la limite selon l'âge et la transition d'âge
        $originalNbJours = $nbJours;
        if ($ageAtStart >= 70) {
            // Règle 1: Si déjà 70 ans ou plus au début → limite 365 jours
            if ($nbJours + $previousCumulDays > 365) {
                $nbJours = 365 - $previousCumulDays;
                if ($nbJours < 0)
                    $nbJours = 0;
            }
        } elseif ($ageAtStart < 70 && $age >= 70) {
            // Règle 2: Si moins de 70 ans au début mais atteint 70 ans pendant la période → limite 730 jours
            if ($nbJours + $previousCumulDays > 730) {
                $nbJours = 730 - $previousCumulDays;
                if ($nbJours < 0)
                    $nbJours = 0;
            }
        }
        // Sinon: moins de 70 ans pendant toute la période → limite 1095 jours (déjà vérifié ci-dessus)

        // Si nbJours a été réduit par la limite d'âge, ajuster paymentDetails
        if ($nbJours < $originalNbJours) {
            $paymentDetails = $this->truncatePaymentDetails($paymentDetails, $nbJours);
        }

        // Initialiser le montant
        $amount = 0;

        // Calculer le montant uniquement s'il y a des jours payables
        if ($nbJours > 0) {
            // Obtenir l'année pour la table des taux
            $firstDateEffet = null;
            foreach ($arrets as $arret) {
                if (isset($arret['date-effet'])) {
                    $firstDateEffet = $arret['date-effet'];
                    break;
                }
            }
            $year = $firstDateEffet ? (int) date('Y', strtotime($firstDateEffet)) : (int) date('Y');

            // Calculer le montant avec les détails
            $calculationResult = $this->calculateMontantByAgeWithDetails(
                $nbJours,
                $previousCumulDays,
                $age,
                $classe,
                $statut,
                $option,
                $year,
                $nbTrimestres,
                $pathoAnterior,
                $paymentDetails,
                $affiliationDate,
                $currentDate,
                $birthDate,
                $historicalReducedRate
            );
            $amount = $calculationResult['montant'];
            $paymentDetails = $calculationResult['payment_details'];

            // Appliquer le prorata
            $amount *= $prorata;
        }

        // Appliquer le taux forcé si fourni (forced_rate = taux journalier forcé)
        if ($forcedRate !== null && $nbJours > 0) {
            // forced_rate est le TAUX JOURNALIER, pas le montant total
            $forcedDailyRate = $forcedRate;
            $amount = $forcedDailyRate * $nbJours;

            // Mettre à jour les payment_details avec le taux forcé
            foreach ($paymentDetails as &$detail) {
                if ($detail['payable_days'] > 0 && isset($detail['rate_breakdown'])) {
                    // Recalculer les montants avec le taux forcé
                    $detailDays = $detail['payable_days'];
                    $detailMontant = $detailDays * $forcedDailyRate;

                    $detail['montant'] = round($detailMontant, 2);
                    $detail['daily_rate'] = $forcedDailyRate;

                    // Mettre à jour rate_breakdown avec le taux forcé
                    foreach ($detail['rate_breakdown'] as &$breakdown) {
                        $breakdown['rate'] = $forcedDailyRate;
                        $breakdown['taux'] = 'Forcé';
                    }
                    unset($breakdown);

                    // Mettre à jour daily_breakdown avec le taux forcé
                    if (isset($detail['daily_breakdown'])) {
                        foreach ($detail['daily_breakdown'] as &$day) {
                            $day['daily_rate'] = $forcedDailyRate;
                            $day['amount'] = $forcedDailyRate;
                            $day['taux'] = 'Forcé';
                        }
                        unset($day);
                    }
                }
            }
            unset($detail);
        }

        // Calculer les dates de fin de paiement
        $endDates = $this->calculateEndPaymentDates($arrets, $previousCumulDays, $birthDate, $currentDate);

        // Calculer le total des jours calendaires de tous les arrêts (pour rechute/pathologie)
        $totalArretDays = 0;
        foreach ($arrets as $arret) {
            if (isset($arret['arret_diff'])) {
                $totalArretDays += $arret['arret_diff'];
            }
        }

        return [
            'nb_jours' => $nbJours,
            'montant' => round($amount, 2),
            'arrets' => $arrets,
            'payment_details' => $paymentDetails,
            'end_payment_dates' => $endDates,
            'total_cumul_days' => $previousCumulDays + $totalArretDays,
            'age' => $age,
            'nb_trimestres' => $nbTrimestres
        ];
    }

    /**
     * Calculer les dates de fin de paiement basées sur l'âge et les jours cumulés
     */
    public function calculateEndPaymentDates(
        array $arrets,
        int $previousCumulDays,
        string $birthDate,
        string $currentDate
    ): ?array {
        $age = $this->dateService->calculateAge($currentDate, $birthDate);
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

        // Calculer l'âge au début de l'IJ
        $ageAtStart = $this->dateService->calculateAge($firstDateEffet->format('Y-m-d'), $birthDate);

        $result = [];

        if ($ageAtStart >= 70) {
            // Règle 1: Déjà 70 ans ou plus au début → période unique de 365 jours
            $endDate = clone $firstDateEffet;
            $endDate->modify('+' . (365 - $previousCumulDays) . ' days');
            $endDate->modify('-1 day');
            $result['end_period_1'] = $endDate->format('Y-m-d');
        } elseif ($ageAtStart < 70 && $age >= 70) {
            // Règle 2: Moins de 70 au début mais atteint 70 pendant la période → limite 730 jours (2 périodes)
            $endDate1 = clone $firstDateEffet;
            $endDate1->modify('+' . (365 - $previousCumulDays) . ' days');
            $endDate1->modify('-1 day');
            $result['end_period_1'] = $endDate1->format('Y-m-d');

            $endDate2 = clone $firstDateEffet;
            $endDate2->modify('+' . (730 - $previousCumulDays) . ' days');
            $endDate2->modify('-1 day');
            $result['end_period_2'] = $endDate2->format('Y-m-d');
            // Pas de période 3 dans ce cas
        } elseif ($age >= 62) {
            // Règle 3: 62-69 ans pendant toute la période → trois périodes (1095 jours)
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

    /**
     * Calculate amount by age with detailed rate breakdown
     */
    private function calculateMontantByAgeWithDetails(
        int $nbJours,
        int $cumulJoursAnciens,
        int $age,
        string $classe,
        string $statut,
        string|int|float $option,
        int $year,
        int $nbTrimestres,
        bool $pathoAnterior,
        array $paymentDetails,
        ?string $affiliationDate = null,
        ?string $currentDate = null,
        ?string $birthDate = null,
        ?int $historicalReducedRate = null
    ): array {
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
            $joursDansArret = 0; // Compteur pour les périodes 1/2/3 (pour les âges 62-69)

            // Déterminer si l'arrêt a une durée >= 730 jours (pour la logique de période 2 pour 62-69 ans)
            $arretDiff = $detail['arret_diff'] ?? 0;
            $usePeriode2 = ($arretDiff >= 730);

            foreach ($yearlyBreakdown as $yearData) {
                // Calculer l'âge au début de ce segment
                $segmentAge = $this->dateService->calculateAge($yearData['start'], $birthDate);

                // Calculer nb_trimestres pour cette période spécifique
                // Pour la pathologie antérieure, nb_trimestres doit être calculé jusqu'à la date du PREMIER arrêt,
                // pas jusqu'à chaque date de début de segment de paiement
                $periodNbTrimestres = $nbTrimestres;
                if ($affiliationDate && !empty($affiliationDate) && $affiliationDate !== '0000-00-00') {
                    // Utiliser la date de début de l'arrêt (date de première pathologie) au lieu de la date de début du segment de paiement
                    $firstArretDate = $detail['arret_from'];
                    $periodNbTrimestres = $this->dateService->calculateTrimesters($affiliationDate, $firstArretDate);
                }

                if ($segmentAge < 62) {
                    // Âge < 62 : taux unique basé sur les trimestres
                    $taux = $this->tauxService->determineTauxNumber($segmentAge, $periodNbTrimestres, $pathoAnterior, $historicalReducedRate);
                    $dailyRate = $this->rateService->getDailyRate($statut, $classe, $option, $taux, $yearData['year'], $yearData['start'], $segmentAge, null);
                    $arretMontant += $yearData['days'] * $dailyRate;
                    $trimester = $this->dateService->getTrimesterFromDate($yearData['start']);

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
                } elseif ($segmentAge >= 62 && $segmentAge <= 69) {
                    // Pour 62-69, calculer les périodes par arrêt INDIVIDUEL
                    $nbJoursRestants = $yearData['days'];
                    $segmentStartDate = new DateTime($yearData['start']);
                    $daysConsumedInSegment = 0; // Track position within this segment

                    // Traiter les jours de ce segment selon leur position dans L'ARRÊT
                    while ($nbJoursRestants > 0) {
                        // Déterminer dans quelle période de l'arrêt nous sommes
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
                            // Period 2 of this arrêt: days 366-730 → taux 7-9 (ONLY if arret_diff >= 730)
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
                            // Period 3 of this arrêt: days 366-1095 (or 731-1095 if usePeriode2) → taux 4-6 (reduced rate)
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
                            // Beyond 1095 days in this arrêt
                            break;
                        }

                        // Calculate actual start and end dates for this period within the segment
                        $periodStart = clone $segmentStartDate;
                        $periodStart->modify("+{$daysConsumedInSegment} days");

                        $periodEnd = clone $periodStart;
                        $periodEnd->modify("+". ($joursP - 1) ." days");

                        // For taux 4-6 (period 3), pass $usePeriode2 to determine tier
                        $dailyRate = $this->rateService->getDailyRate($statut, $classe, $option, $taux, $yearData['year'], $periodStart->format('Y-m-d'), $segmentAge, $usePeriode2);
                        $arretMontant += $joursP * $dailyRate;
                        $trimester = $this->dateService->getTrimesterFromDate($periodStart->format('Y-m-d'));

                        $rateInfo[] = [
                            'year' => $yearData['year'],
                            'month' => $yearData['month'],
                            'trimester' => $trimester,
                            'nb_trimestres' => $periodNbTrimestres,
                            'period' => $periodNumber,
                            'start' => $periodStart->format('Y-m-d'),
                            'end' => $periodEnd->format('Y-m-d'),
                            'days' => $joursP,
                            'rate' => $dailyRate,
                            'taux' => $taux
                        ];

                        $joursDansArret += $joursP;
                        $nbJoursRestants -= $joursP;
                        $daysConsumedInSegment += $joursP;
                    }
                } else { // segmentAge >= 70
                    // Age >= 70: reduced rate (taux 4, 5 or 6)
                    $taux = $this->tauxService->determineTauxNumber($segmentAge, $periodNbTrimestres, $pathoAnterior, $historicalReducedRate);
                    $dailyRate = $this->rateService->getDailyRate($statut, $classe, $option, $taux, $yearData['year'], $yearData['start'], $segmentAge);
                    $arretMontant += $yearData['days'] * $dailyRate;
                    $trimester = $this->dateService->getTrimesterFromDate($yearData['start']);

                    $rateInfo[] = [
                        'year' => $yearData['year'],
                        'month' => $yearData['month'],
                        'trimester' => $trimester,
                        'nb_trimestres' => $periodNbTrimestres,
                        'period' => 'senior', // No period 1/2/3 for 70+
                        'start' => $yearData['start'],
                        'end' => $yearData['end'],
                        'days' => $yearData['days'],
                        'rate' => $dailyRate,
                        'taux' => $taux
                    ];
                }
            }

            $optin = 1;


            if ($pathoAnterior && $periodNbTrimestres > 7 && $periodNbTrimestres <= 15) {
                $optin = 1/3;
            } elseif ($pathoAnterior && $periodNbTrimestres > 15 && $periodNbTrimestres <= 23) {
                $optin = 2/3;
            }

            $detail['montant'] = round($arretMontant * $optin, 2, PHP_ROUND_HALF_UP);
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
     */
    private function generateDailyBreakdown(array $rateInfo): array
    {
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
                    'amount' => $rate
                ];
            }
        }

        return $dailyBreakdown;
    }

    /**
     * Split payment period by calendar year, month, rate periods, and birthdays
     */
    private function splitPaymentByYear(DateTime $startDate, DateTime $endDate, ?string $birthDate = null): array
    {
        $segments = [];
        $current = clone $startDate;

        while ($current <= $endDate) {
            // Get end of current month or end of rate period, whichever comes first
            $year = (int) $current->format('Y');
            $month = (int) $current->format('m');

            // Get end of current month
            $monthEnd = new DateTime($current->format('Y-m-t'));

            // Get rate period for current date
            $rateData = $this->rateService->getRateForDate($current->format('Y-m-d'));

            if ($rateData) {
                $periodEnd = new DateTime($rateData['date_end']);
            } else {
                $periodEnd = new DateTime("$year-12-31");
            }

            // Check if there's a birthday in this period (for age transitions)
            $birthdayEnd = null;
            if ($birthDate && !empty($birthDate) && $birthDate !== '0000-00-00') {
                $birth = new DateTime($birthDate);
                $birthMonth = (int) $birth->format('m');
                $birthDay = (int) $birth->format('d');

                // Create birthday date for current year
                $birthdayThisYear = new DateTime(sprintf('%d-%02d-%02d', $year, $birthMonth, $birthDay));

                // If birthday falls in current segment
                if ($birthdayThisYear > $current && $birthdayThisYear <= min($monthEnd, $periodEnd, $endDate)) {
                    $birthdayEnd = clone $birthdayThisYear;
                    $birthdayEnd->modify('-1 day'); // Day before birthday
                }
            }

            // Use the closest date: birthday-1, month end, rate period end, or payment end
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

    /**
     * Truncate payment details to respect the day limit (for age restrictions)
     *
     * @param array $paymentDetails Payment details array
     * @param int $maxDays Maximum number of days allowed
     * @return array Truncated payment details
     */
    private function truncatePaymentDetails(array $paymentDetails, int $maxDays): array
    {
        $truncatedDetails = [];
        $daysCounted = 0;

        foreach ($paymentDetails as $detail) {
            if ($daysCounted >= $maxDays) {
                break;
            }

            $remainingDays = $maxDays - $daysCounted;
            $detailDays = $detail['payable_days'] ?? 0;

            if ($detailDays <= $remainingDays) {
                // Include entire detail
                $truncatedDetails[] = $detail;
                $daysCounted += $detailDays;
            } else {
                // Truncate this detail
                $truncatedDetail = $detail;
                $truncatedDetail['payable_days'] = $remainingDays;

                // Adjust payment_end date
                if (isset($detail['payment_start']) && $remainingDays > 0) {
                    $paymentStart = new \DateTime($detail['payment_start']);
                    $paymentEnd = clone $paymentStart;
                    $paymentEnd->modify('+' . ($remainingDays - 1) . ' days');
                    $truncatedDetail['payment_end'] = $paymentEnd->format('Y-m-d');
                }

                $truncatedDetails[] = $truncatedDetail;
                $daysCounted += $remainingDays;
                break;
            }
        }

        return $truncatedDetails;
    }
}
