<?php

require_once __DIR__ . '/Services/RateServiceInterface.php';
require_once __DIR__ . '/Services/RateService.php';
require_once __DIR__ . '/Services/DateCalculationInterface.php';
require_once __DIR__ . '/Services/DateService.php';
require_once __DIR__ . '/Services/TauxDeterminationInterface.php';
require_once __DIR__ . '/Services/TauxDeterminationService.php';
require_once __DIR__ . '/Services/AmountCalculationInterface.php';
require_once __DIR__ . '/Services/AmountCalculationService.php';

use IJCalculator\Services\RateService;
use IJCalculator\Services\RateServiceInterface;
use IJCalculator\Services\DateService;
use IJCalculator\Services\DateCalculationInterface;
use IJCalculator\Services\TauxDeterminationService;
use IJCalculator\Services\TauxDeterminationInterface;
use IJCalculator\Services\AmountCalculationService;
use IJCalculator\Services\AmountCalculationInterface;

/**
 * Classe IJCalculator - Refactored with SOLID principles
 *
 * Calculateur d'Indemnités Journalières (IJ) pour médecins selon les règles CARMF
 *
 * ARCHITECTURE
 * ============
 * Cette classe utilise maintenant l'injection de dépendances avec trois services spécialisés :
 * - RateService : Gère toutes les recherches et calculs de taux
 * - DateService : Gère les opérations liées aux dates
 * - TauxDeterminationService : Détermine les numéros de taux et les classes de cotisation
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
class IJCalculator
{
    private $rates = [];
    private $passValue = 47000; // Valeur par défaut du PASS CPAM

    // Services injectés
    private RateServiceInterface $rateService;
    private DateCalculationInterface $dateService;
    private TauxDeterminationInterface $tauxService;
    private AmountCalculationInterface $amountService;

    /**
     * Constructeur avec injection de dépendances
     *
     * @param string $csvPath Chemin vers le fichier CSV des taux
     * @param RateServiceInterface|null $rateService Service de taux optionnel (pour les tests/mocks)
     * @param DateCalculationInterface|null $dateService Service de dates optionnel (pour les tests/mocks)
     * @param TauxDeterminationInterface|null $tauxService Service de taux optionnel (pour les tests/mocks)
     * @param AmountCalculationInterface|null $amountService Service de calcul de montants optionnel (pour les tests/mocks)
     */
    public function __construct(
        $csvPath = 'taux.csv',
        ?RateServiceInterface $rateService = null,
        ?DateCalculationInterface $dateService = null,
        ?TauxDeterminationInterface $tauxService = null,
        ?AmountCalculationInterface $amountService = null
    ) {
        // Rétrocompatibilité : conserver le chargement interne des taux
        $this->loadRates($csvPath);

        // Utiliser les services injectés ou créer les services par défaut
        $this->rateService = $rateService ?? new RateService($csvPath);
        $this->dateService = $dateService ?? new DateService();
        $this->tauxService = $tauxService ?? new TauxDeterminationService();

        // Créer le service de calcul de montants avec les dépendances
        $this->amountService = $amountService ?? new AmountCalculationService(
            $this->dateService,
            $this->rateService,
            $this->tauxService
        );

        // Synchroniser la valeur du PASS
        $this->tauxService->setPassValue($this->passValue);
    }

    public function setPassValue($value)
    {
        $this->passValue = $value;
        $this->rateService->setPassValue($value);
        $this->tauxService->setPassValue($value);
    }

    private function loadRates($csvPath)
    {
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

    public function getRateForYear($year)
    {
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
    private function getTrimesterFromDate($date)
    {
        $dateObj = new DateTime($date);
        $month = (int) $dateObj->format('m');

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
    public function calculateTrimesters($affiliationDate, $currentDate)
    {
        // Déléguer au DateService
        return $this->dateService->calculateTrimesters($affiliationDate, $currentDate);
    }
    public function sortByDateStartDesc(array $data): array
    {
        // Cloner le tableau pour éviter de modifier le tableau original (bonne pratique)
        $sorted_data = $data;

        usort($sorted_data, function ($a, $b) {
            // Convertir les chaînes de date en timestamps UNIX pour une comparaison fiable
            $time_a = strtotime($a['date_start']);
            $time_b = strtotime($b['date_start']);

            // Retourne un entier :
            // > 0 si $b est "plus ancien" que $a (pour le tri décroissant)
            // < 0 si $b est "plus récent" que $a
            // 0 si les deux sont égaux

            // Pour un tri décroissant (DESC), si $a est plus récent ($time_a > $time_b), 
            // nous voulons que $a vienne avant $b, donc le résultat doit être négatif.
            // C'est l'inverse d'un tri croissant.
            return $time_b - $time_a;
            // Alternative plus idiomatique pour PHP 7+ :
            // return $time_b <=> $time_a; 
        });

        return $sorted_data;
    }
    public function getRateForDate($date)
    {
        $checkDate = new DateTime($date);
        foreach ($this->sortByDateStartDesc($this->rates) as $rate) {
            $startDate = new DateTime($rate['date_start']);
            $endDate = new DateTime($rate['date_end']);

            if ($startDate <= $checkDate) {
                return $rate;
            }
        }
        return null;
    }

    public function calculateAge($currentDate, $birthDate)
    {
        // Déléguer au DateService
        return $this->dateService->calculateAge($currentDate, $birthDate);
    }

    public function mergeProlongations($arrets)
    {
        // Déléguer au DateService
        return $this->dateService->mergeProlongations($arrets);
    }

    public function calculateDateEffet($arrets, $birthDate = null, $previousCumulDays = 0)
    {
        // Déléguer au DateService
        return $this->dateService->calculateDateEffet($arrets, $birthDate, $previousCumulDays);
    }
   
    public function calculatePayableDays($arrets, $attestationDate = null, $lastPaymentDate = null, $currentDate = null)
    {
        // Delegate to DateService
        return $this->dateService->calculatePayableDays($arrets, $attestationDate, $lastPaymentDate, $currentDate);
    }

    public function calculateEndPaymentDates($arrets, $previousCumulDays, $birthDate, $currentDate)
    {
        // Delegate to AmountCalculationService
        return $this->amountService->calculateEndPaymentDates($arrets, $previousCumulDays, $birthDate, $currentDate);
    }

    public function calculateAmount($data)
    {
        // Validate and auto-correct option based on statut (functional rules)
        $data = $this->validateAndCorrectOption($data);

        // Delegate to AmountCalculationService
        return $this->amountService->calculateAmount($data);
    }

    /**
     * Validate and auto-correct option based on statut
     *
     * Règles fonctionnelles:
     * - Médecin (M): option 100% uniquement
     * - CCPL: options 25%, 50% (pas de 100%)
     * - RSPM: options 25%, 100% (pas de 50%)
     *
     * @param array $data Input data
     * @return array Corrected data
     */
    private function validateAndCorrectOption(array $data): array
    {
        $statut = strtoupper($data['statut'] ?? 'M');
        $option = $data['option'] ?? 1;

        // Normalize option to float (handle "0,25" or "0.25" or 0.25 or 25 or "25")
        if (is_string($option)) {
            $option = (float) str_replace(',', '.', $option);
        }

        // Convert percentage format (25, 50, 100) to decimal format (0.25, 0.5, 1.0)
        $optionDecimal = $option;
        if ($option > 1) {
            $optionDecimal = $option / 100;
        }

        $correctedOption = $option; // Keep original format

        switch ($statut) {
            case 'M': // Médecin
                // Only 100% allowed
                if ($optionDecimal != 1) {
                    // Preserve format: if input was > 1, return 100, else return 1
                    $correctedOption = ($option > 1) ? 100 : 1;
                    error_log("IJCalculator: Option auto-corrected for Médecin from {$option} to {$correctedOption}");
                }
                break;

            case 'CCPL':
                // Only 25% and 50% allowed (no 100%)
                if ($optionDecimal == 1) {
                    // 100% not allowed for CCPL, default to 25%
                    $correctedOption = ($option > 1) ? 25 : 0.25;
                    error_log("IJCalculator: Option auto-corrected for CCPL from {$option} to {$correctedOption}");
                } elseif ($optionDecimal != 0.25 && $optionDecimal != 0.5) {
                    // Invalid option, default to 25%
                    $correctedOption = ($option > 1) ? 25 : 0.25;
                    error_log("IJCalculator: Option auto-corrected for CCPL from {$option} to {$correctedOption}");
                }
                break;

            case 'RSPM':
                // Only 25% and 100% allowed (no 50%)
                if ($optionDecimal == 0.5) {
                    // 50% not allowed for RSPM, default to 25%
                    $correctedOption = ($option > 1) ? 25 : 0.25;
                    error_log("IJCalculator: Option auto-corrected for RSPM from {$option} to {$correctedOption}");
                } elseif ($optionDecimal != 0.25 && $optionDecimal != 1) {
                    // Invalid option, default to 25%
                    $correctedOption = ($option > 1) ? 25 : 0.25;
                    error_log("IJCalculator: Option auto-corrected for RSPM from {$option} to {$correctedOption}");
                }
                break;
        }

        $data['option'] = $correctedOption;
        return $data;
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
    private function determineTauxNumber($age, $nbTrimestres, $pathoAnterior, $historicalReducedRate = null)
    {
        // Delegate to TauxDeterminationService
        return $this->tauxService->determineTauxNumber($age, $nbTrimestres, $pathoAnterior, $historicalReducedRate);
    }

    private function calculateMontantByAgeWithDetails($nbJours, $cumulJoursAnciens, $age, $classe, $statut, $option, $year, $nbTrimestres, $pathoAnterior, $paymentDetails, $affiliationDate = null, $currentDate = null, $birthDate = null, $historicalReducedRate = null)
    {
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
                    $dailyRate = $this->getRate($statut, $classe, $option, $taux, $yearData['year'], $yearData['start'], $segmentAge, null);
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
                        // dd($this->rates);

                        // Pour taux 4-6 (période 3), passer $usePeriode2 pour déterminer le tier
                        $dailyRate = $this->getRate($statut, $classe, $option, $taux, $yearData['year'], $yearData['start'], $segmentAge, $usePeriode2);
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
    private function generateDailyBreakdown($rateInfo)
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
                    'amount' => round($rate, 2),
                    'nb_trimestres' => $segment['nb_trimestres']
                ];
            }
        }

        return $dailyBreakdown;
    }

    private function splitPaymentByYear($startDate, $endDate, $birthDate = null)
    {
        $segments = [];
        $current = clone $startDate;

        while ($current <= $endDate) {
            // Obtenir la fin du mois ou la fin de la période de taux, selon ce qui vient en premier
            $year = (int) $current->format('Y');
            $month = (int) $current->format('m');

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
                $birthMonth = (int) $birth->format('m');
                $birthDay = (int) $birth->format('d');

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

    private function getRate($statut, $classe, $option, $taux, $year, $date = null, $age = null, $usePeriode2 = null)
    {
        // Delegate to RateService
        return $this->rateService->getDailyRate($statut, $classe, $option, $taux, $year, $date, $age, $usePeriode2);
    }

    public function calculateRevenuAnnuel($classe, $revenu = null)
    {
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

    /**
     * Détermine automatiquement la classe de cotisation (A/B/C) selon les revenus N-2
     *
     * @param float|null $revenuNMoins2 Revenus de l'année N-2 en euros
     * @param string|null $dateOuvertureDroits Date d'ouverture des droits (format Y-m-d)
     * @param bool $taxeOffice Indique si le médecin est taxé d'office
     * @return string Classe déterminée: 'A', 'B' ou 'C'
     */
    public function determineClasse(
        ?float $revenuNMoins2 = null,
        ?string $dateOuvertureDroits = null,
        bool $taxeOffice = false
    ): string {
        // Delegate to TauxDeterminationService
        return $this->tauxService->determineClasse($revenuNMoins2, $dateOuvertureDroits, $taxeOffice);
    }
}
