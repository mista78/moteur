<?php

namespace App\IJCalculator\Services;

use DateTime;

/**
 * Date Calculation Service
 * Handles all date-related calculations
 */
class DateService implements DateCalculationInterface
{
    public function calculateAge(string $currentDate, string $birthDate): int
    {
        $current = new DateTime($currentDate);
        $birth = new DateTime($birthDate);

        if ($birth->format('m') < $current->format('m')) {
            return (int) $current->format('Y') - (int) $birth->format('Y');
        } elseif ($birth->format('m') == $current->format('m')) {
            if ($birth->format('d') <= $current->format('d')) {
                return (int) $current->format('Y') - (int) $birth->format('Y');
            } else {
                return (int) $current->format('Y') - (int) $birth->format('Y') - 1;
            }
        } else {
            return (int) $current->format('Y') - (int) $birth->format('Y') - 1;
        }
    }

    public function calculateTrimesters(string $affiliationDate, string $currentDate): int
    {
        if (empty($affiliationDate) || $affiliationDate === '0000-00-00') {
            return 0;
        }

        $affiliation = new DateTime($affiliationDate);
        $current = new DateTime($currentDate);

        if ($affiliation > $current) {
            return 0;
        }

        // Obtenir le trimestre pour chaque date
        // Q1 (01/01-31/03), Q2 (01/04-30/06), Q3 (01/07-30/09), Q4 (01/10-31/12)
        $affiliationYear = (int) $affiliation->format('Y');
        $currentYear = (int) $current->format('Y');

        $affiliationQuarter = $this->getTrimesterFromDate($affiliationDate);
        $currentQuarter = $this->getTrimesterFromDate($currentDate);

        // Calculer le nombre total de trimestres
        // Règle : Si la date d'affiliation tombe dans un trimestre, ce trimestre compte comme complet
        $yearsDiff = $currentYear - $affiliationYear;
        $totalQuarters = ($yearsDiff * 4) + ($currentQuarter - $affiliationQuarter) + 1;

        // Règle supplémentaire : Si la date actuelle n'est PAS à la FIN de son trimestre,
        // arrondir AU DESSUS pour inclure le prochain trimestre complet
        // Cela garantit que les trimestres partiels comptent comme complets pour les calculs de pathologie antérieure
        $isEndOfQuarter = $this->isLastDayOfQuarter($currentDate);
        if (!$isEndOfQuarter) {
            $totalQuarters += 1;
        }

        return max(0, $totalQuarters);
    }

    private function isLastDayOfQuarter(string $date): bool
    {
        $dateTime = new DateTime($date);
        $month = (int) $dateTime->format('n');
        $day = (int) $dateTime->format('j');

        // Q1 se termine le 31 mars
        if ($month === 3 && $day === 31) return true;
        // Q2 se termine le 30 juin
        if ($month === 6 && $day === 30) return true;
        // Q3 se termine le 30 septembre
        if ($month === 9 && $day === 30) return true;
        // Q4 se termine le 31 décembre
        if ($month === 12 && $day === 31) return true;

        return false;
    }

    public function getTrimesterFromDate(string $date): int
    {
        $dateTime = new DateTime($date);
        $month = (int) $dateTime->format('n');

        if ($month >= 1 && $month <= 3) {
            return 1;
        } elseif ($month >= 4 && $month <= 6) {
            return 2;
        } elseif ($month >= 7 && $month <= 9) {
            return 3;
        } else {
            return 4;
        }
    }

    /**
     * Vérifier si une date est un jour férié français
     */
    private function isFrenchPublicHoliday(DateTime $date): bool
    {
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');
        $day = (int) $date->format('j');

        // Jours fériés fixes
        $fixedHolidays = [
            '01-01', // Jour de l'an
            '05-01', // Fête du travail
            '05-08', // Victoire 1945
            '07-14', // Fête nationale
            '08-15', // Assomption
            '11-01', // Toussaint
            '11-11', // Armistice 1918
            '12-25', // Noël
        ];

        $dateStr = sprintf('%02d-%02d', $month, $day);
        if (in_array($dateStr, $fixedHolidays)) {
            return true;
        }

        // Pâques et jours fériés mobiles
        $easter = easter_date($year);
        $easterDate = new DateTime();
        $easterDate->setTimestamp($easter);

        // Lundi de Pâques (+1 jour)
        $easterMonday = clone $easterDate;
        $easterMonday->modify('+1 day');

        // Ascension (+39 jours)
        $ascension = clone $easterDate;
        $ascension->modify('+39 days');

        // Lundi de Pentecôte (+50 jours)
        $pentecoteMonday = clone $easterDate;
        $pentecoteMonday->modify('+50 days');

        $mobileHolidays = [
            $easterMonday->format('Y-m-d'),
            $ascension->format('Y-m-d'),
            $pentecoteMonday->format('Y-m-d'),
        ];

        return in_array($date->format('Y-m-d'), $mobileHolidays);
    }

    /**
     * Ajouter 1 jour ouvré (sautant weekends ET jours fériés)
     */
    private function addOneBusinessDay(DateTime $date): DateTime
    {
        $result = clone $date;
        do {
            $result->modify('+1 day');
        } while ($result->format('N') >= 6 || $this->isFrenchPublicHoliday($result));

        return $result;
    }

    public function mergeProlongations(array $arrets): array
    {
        usort($arrets, function ($a, $b) {
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

            // Calculer le prochain jour ouvré après la fin du dernier arrêt
            $nextBusinessDay = $this->addOneBusinessDay($lastEnd);

            if ($nextBusinessDay->format('Y-m-d') == $currentStart->format('Y-m-d')) {
                $last['arret-to-line'] = $arret['arret-to-line'];
            } else {
                $merged[] = $arret;
            }
        }

        return $merged;
    }

    /**
     * Déterminer automatiquement si un arrêt est une rechute
     * Règle: Si rechute-line est déjà défini (forcé par commission), le respecter.
     * Sinon, calculer automatiquement: rechute si date début < (date fin dernier arrêt + 1 an)
     * et que l'arrêt n'est pas une prolongation (consécutif)
     * ET que les droits ont déjà été ouverts (date-effet existe pour l'arrêt précédent)
     */
    private function isRechute(array $currentArret, ?array $previousArret): bool
    {
        // Si rechute-line est explicitement défini (forcé par commission), le respecter
        // Note: rechute-line peut être 1 (rechute) ou un nombre > 1 (ex: 15 pour délai de 15 jours)
        if (isset($currentArret['rechute-line']) && $currentArret['rechute-line'] !== null && $currentArret['rechute-line'] !== '') {
            return (int)$currentArret['rechute-line'] > 0;
        }

        // Pas de précédent arrêt → pas une rechute
        if (!$previousArret) {
            return false;
        }

        // CRITICAL: Si l'arrêt précédent n'a pas de date-effet (droits pas ouverts),
        // alors ce n'est pas une rechute, juste une accumulation vers le seuil de 90 jours
        if (!isset($previousArret['date-effet']) || empty($previousArret['date-effet'])) {
            return false;
        }

        $lastEnd = new DateTime($previousArret['arret-to-line']);
        $currentStart = new DateTime($currentArret['arret-from-line']);

        // Vérifier si consécutif (prolongation)
        $nextBusinessDay = $this->addOneBusinessDay($lastEnd);
        if ($nextBusinessDay->format('Y-m-d') == $currentStart->format('Y-m-d')) {
            // C'est une prolongation, pas une rechute
            return false;
        }

        // Vérifier si < 1 an après la fin du dernier arrêt
        // Règle: date début <= date fin dernier + 1 an - 1 jour
        $oneYearAfterLast = clone $lastEnd;
        $oneYearAfterLast->modify('+1 year')->modify('-1 day');

        return $currentStart <= $oneYearAfterLast;
    }

    public function calculateDateEffet(array $arrets, ?string $birthDate = null, int $previousCumulDays = 0): array
    {
        $arrets = $this->mergeProlongations($arrets);

        static $dateDT;
        static $dateCotis;

        $nbJours = $previousCumulDays;
        $arretDroits = 0;
        $increment = 0;

        while (true) {
            $dates = '';
            $lessDate = 0;
            $currentData = &$arrets[$increment];

            // Si valid_med_controleur != 1, pas de date d'effet
            if (isset($currentData['valid_med_controleur']) && $currentData['valid_med_controleur'] != 1) {
                $currentData['date-effet'] = null;
                $increment++;
                if (count($arrets) === $increment) {
                    break;
                }
                continue;
            }

            $startDate = new DateTime($currentData['arret-from-line']);
            $endDate = new DateTime($currentData['arret-to-line']);
            $arret_diff = $startDate->diff($endDate)->days + 1;
            $currentData['arret_diff'] = $arret_diff;

            $newNbJours = $nbJours + $arret_diff;

            // Si date_deb_droit existe et n'est pas 0000-00-00, l'utiliser comme date-effet
            if (isset($currentData['date_deb_droit']) && !empty($currentData['date_deb_droit']) && $currentData['date_deb_droit'] !== '0000-00-00') {
                $currentData['date-effet'] = $currentData['date_deb_droit'];
                $nbJours = $newNbJours;
                $arretDroits++; // Mark that rights have been opened for this arret
                $increment++;
                if (count($arrets) === $increment) {
                    break;
                }
                continue;
            }

            // Si la date est forcée, ignorer le calcul
            if (isset($currentData['date-effet-forced'])) {
                $currentData['date-effet'] = $currentData['date-effet-forced'];
                $nbJours = $newNbJours;
                $arretDroits++; // Mark that rights have been opened for this arret
                $increment++;
                if (count($arrets) === $increment) {
                    break;
                }
                continue;
            }

            // Premier arrêt - calcul de la date d'ouverture des droits (90 jours)
            if ($arretDroits === 0) {
                // Premier arrêt ou nouvelle pathologie, pas une rechute
                $currentData['is_rechute'] = false;

                $lessDate = 90 - ($newNbJours - $arret_diff);
                $dateDeb = clone $startDate;
                $dateDeb->modify("+$lessDate days");

                // Gérer les DT non excusées (dt-line = 0)
                if ((isset($currentData['dt-line']) && $currentData['dt-line'] == '0') && !empty($currentData['declaration-date-line'])) {
                    if ($increment > 0) {
                        $choice = $arrets[0];
                    }
                    $slect = $choice ?? $currentData;
                    $dtDate = new DateTime($slect['declaration-date-line']);
                    $dtDate->modify('+30 days');
                    $dateDT = $dtDate->format('Y-m-d');
                }

                // Gérer la mise à jour du compte (dt-line = 1 et date_maj_compte présente)
                if ((isset($currentData['dt-line']) && $currentData['dt-line'] == '1') && (isset($currentData['date_maj_compte']) && $currentData['date_maj_compte'] != '')) {
                    $cotisDate = new DateTime($currentData['date_maj_compte']);
                    $cotisDate->modify('+30 days');
                    $dateCotis = $cotisDate->format('Y-m-d');
                }

                // Si on dépasse 90 jours, on définit la date d'effet comme le max des 3 dates
                if ($newNbJours > 90) {
                    $dates = date('Y-m-d', max([
                        strtotime($dateDeb->format('Y-m-d')),
                        strtotime($dateDT ?? '1970-01-01'),
                        strtotime($dateCotis ?? '1970-01-01'),
                    ]));
                    $arretDroits++;
                }
            }
            // Arrêts suivants - rechute ou prolongation
            elseif ($increment > 0) {
                // Déterminer si c'est une rechute (forcée via rechute-line OU automatique < 1 an)
                $previousArret = $increment > 0 ? $arrets[$increment - 1] : null;
                $siRechute = $this->isRechute($currentData, $previousArret);

                // Ajouter l'indication de rechute au résultat pour l'affichage frontend
                $currentData['is_rechute'] = $siRechute;

                // Si c'est une rechute, identifier de quel arrêt (le dernier avec date-effet)
                if ($siRechute) {
                    // Trouver le dernier arrêt précédent qui a une date-effet
                    for ($i = $increment - 1; $i >= 0; $i--) {
                        if (isset($arrets[$i]['date-effet']) && !empty($arrets[$i]['date-effet'])) {
                            $currentData['rechute_of_arret_index'] = $i;
                            break;
                        }
                    }
                }

                // Rechute: droits au 15ème jour (règle des 15 jours pour rechute)
                // Note: Si MC veut forcer au jour 1, utiliser le champ date-effet qui sera traité plus haut
                if ($siRechute) {
                    // Date de base: 15ème jour d'arrêt
                    $dateDeb = clone $startDate;
                    $dateDeb->modify('+14 days');

                    // Variables pour DT et cotisations (comme pour le seuil de 90 jours)
                    $dateDT = null;
                    $dateCotis = null;

                    // Gérer les DT non excusées (dt-line = 0) - 15ème jour après déclaration
                    if ((isset($currentData['dt-line']) && $currentData['dt-line'] == '0') && !empty($currentData['declaration-date-line'])) {
                        if ($increment > 0) {
                            $choice = $arrets[0];
                        }
                        $slect = $choice ?? $currentData;
                        $dtDate = new DateTime($slect['declaration-date-line']);
                        $dtDate->modify('+14 days'); // +14 pour obtenir le 15ème jour
                        $dateDT = $dtDate->format('Y-m-d');
                    }

                    // Gérer la mise à jour du compte (dt-line = 1 et date_maj_compte présente) - 15ème jour après MAJ
                    if ((isset($currentData['dt-line']) && $currentData['dt-line'] == '1') && (isset($currentData['date_maj_compte']) && $currentData['date_maj_compte'] != '')) {
                        $cotisDate = new DateTime($currentData['date_maj_compte']);
                        $cotisDate->modify('+14 days'); // +14 pour obtenir le 15ème jour
                        $dateCotis = $cotisDate->format('Y-m-d');
                    }

                    // Calculer le max des 3 dates (15ème jour arrêt, DT+15j, MAJ+15j)
                    $dates = date('Y-m-d', max([
                        strtotime($dateDeb->format('Y-m-d')),
                        strtotime($dateDT ?? '1970-01-01'),
                        strtotime($dateCotis ?? '1970-01-01'),
                    ]));
                } else {
                    // Réinitialiser pour nouvelle pathologie (ne pas accumuler avec pathologies précédentes)
                    $arretDroits = 0;
                    $nbJours = 0; // Reset pour nouvelle pathologie
                    $newNbJours = $arret_diff; // Seulement les jours de cet arrêt

                    $lessDate = 90 - $arret_diff;
                    $dateDeb = clone $startDate;
                    $dateDeb->modify("+$lessDate days");

                    // Gérer les DT non excusées (31 jours pour nouvelle pathologie)
                    $dateDT = null;
                    $dateCotis = null;

                    if ((isset($currentData['dt-line']) && $currentData['dt-line'] == '0') && !empty($currentData['declaration-date-line'])) {
                        $dtDate = new DateTime($currentData['declaration-date-line']);
                        $dtDate->modify('+30 days');
                        $dateDT = $dtDate->format('Y-m-d');
                    }

                    // Gérer la mise à jour du compte (31 jours pour nouvelle pathologie)
                    if ((isset($currentData['dt-line']) && $currentData['dt-line'] == '1') && (isset($currentData['date_maj_compte']) && $currentData['date_maj_compte'] != '')) {
                        $cotisDate = new DateTime($currentData['date_maj_compte']);
                        $cotisDate->modify('+30 days');
                        $dateCotis = $cotisDate->format('Y-m-d');
                    }

                    // Si on dépasse 90 jours, on définit la date d'effet
                    if ($newNbJours > 90) {
                        $dates = date('Y-m-d', max([
                            strtotime($dateDeb->format('Y-m-d')),
                            strtotime($dateDT ?? '1970-01-01'),
                            strtotime($dateCotis ?? '1970-01-01'),
                        ]));
                        $arretDroits++;
                    }
                }
            }

            $currentData['date-effet'] = $dates;
            $nbJours = $newNbJours;
            $increment++;

            if (count($arrets) === $increment) {
                break;
            }
        }

        return $arrets;
    }

    /**
     * Calculer le 1er jour du semestre suivant le 75ème anniversaire
     * Si anniversaire entre 01/01 et 30/06 → retourne 01/07 de cette année
     * Si anniversaire entre 01/07 et 31/12 → retourne 01/01 de l'année suivante
     */
    private function getFirstDayOfSemesterAfter75thBirthday(string $birthDate): ?string
    {
        if (empty($birthDate) || $birthDate === '0000-00-00') {
            return null;
        }

        $birth = new DateTime($birthDate);
        $age75Date = clone $birth;
        $age75Date->modify('+75 years');

        $month = (int) $age75Date->format('n');
        $year = (int) $age75Date->format('Y');

        // Si anniversaire entre janvier et juin (mois 1-6)
        if ($month >= 1 && $month <= 6) {
            return "$year-07-01";
        } else {
            // Si anniversaire entre juillet et décembre (mois 7-12)
            $nextYear = $year + 1;
            return "$nextYear-01-01";
        }
    }

    public function calculatePayableDays(
        array $arrets,
        ?string $attestationDate = null,
        ?string $lastPaymentDate = null,
        ?string $currentDate = null
    ): array {
        $lastPayment = $lastPaymentDate ? new DateTime($lastPaymentDate) : null;
        $current = $currentDate ? new DateTime($currentDate) : new DateTime();

        $totalDays = 0;
        $paymentDetails = [];

        foreach ($arrets as $index => $arret) {
            // Vérifier cco_a_jour - pas de paiement si compte cotisant pas à jour
            if (isset($arret['cco_a_jour']) && $arret['cco_a_jour'] != 1) {
                // Calculer les jours de décompte (avant date d'effet)
                $decompte_days = $this->calculateDecompteDays($arret);

                $paymentDetails[$index] = [
                    'arret_index' => $index,
                    'arret_from' => $arret['arret-from-line'],
                    'arret_to' => $arret['arret-to-line'],
                    'date_effet' => $arret['date-effet'] ?? null,
                    'decompte_days' => $decompte_days,
                    'attestation_date' => null,
                    'payment_start' => null,
                    'payment_end' => null,
                    'payable_days' => 0,
                    'reason' => 'Account not up to date (cco_a_jour != 1)'
                ];
                continue;
            }

            // Vérifier valid_med_controleur - pas de paiement si != 1
            if (isset($arret['valid_med_controleur']) && $arret['valid_med_controleur'] != 1) {
                $decompte_days = $this->calculateDecompteDays($arret);

                $paymentDetails[$index] = [
                    'arret_index' => $index,
                    'arret_from' => $arret['arret-from-line'],
                    'arret_to' => $arret['arret-to-line'],
                    'date_effet' => $arret['date-effet'] ?? null,
                    'decompte_days' => $decompte_days,
                    'attestation_date' => null,
                    'payment_start' => null,
                    'payment_end' => null,
                    'payable_days' => 0,
                    'reason' => 'Not validated by medical controller (valid_med_controleur != 1)'
                ];
                continue;
            }

            // Vérifier dt-line - pas de paiement si DT non excusée (dt-line === "0" string)
            if (isset($arret['dt-line']) && $arret['dt-line'] === '0') {
                $decompte_days = $this->calculateDecompteDays($arret);

                $paymentDetails[$index] = [
                    'arret_index' => $index,
                    'arret_from' => $arret['arret-from-line'],
                    'arret_to' => $arret['arret-to-line'],
                    'date_effet' => $arret['date-effet'] ?? null,
                    'decompte_days' => $decompte_days,
                    'attestation_date' => null,
                    'payment_start' => null,
                    'payment_end' => null,
                    'payable_days' => 0,
                    'reason' => 'DT not excused (dt-line === "0")'
                ];
                continue;
            }

            if (!isset($arret['date-effet'])) {
                $decompte_days = $this->calculateDecompteDays($arret);

                $paymentDetails[$index] = [
                    'arret_index' => $index,
                    'arret_from' => $arret['arret-from-line'],
                    'arret_to' => $arret['arret-to-line'],
                    'date_effet' => null,
                    'decompte_days' => $decompte_days,
                    'attestation_date' => null,
                    'payment_start' => null,
                    'payment_end' => null,
                    'payable_days' => 0,
                    'reason' => 'No date effet'
                ];
                continue;
            }

            // Vérifier limite 75 ans - pas de paiement si date d'effet >= 1er jour semestre après 75ème anniversaire
            if (isset($arret['date_naissance']) && !empty($arret['date_naissance'])) {
                $limitDate75 = $this->getFirstDayOfSemesterAfter75thBirthday($arret['date_naissance']);
                if ($limitDate75 && $arret['date-effet'] >= $limitDate75) {
                    $decompte_days = $this->calculateDecompteDays($arret);

                    $paymentDetails[$index] = [
                        'arret_index' => $index,
                        'arret_from' => $arret['arret-from-line'],
                        'arret_to' => $arret['arret-to-line'],
                        'date_effet' => $arret['date-effet'],
                        'decompte_days' => $decompte_days,
                        'attestation_date' => null,
                        'payment_start' => null,
                        'payment_end' => null,
                        'payable_days' => 0,
                        'reason' => "Age limit: date effet >= first day of semester after 75th birthday ($limitDate75)"
                    ];
                    continue;
                }
            }

            // Utiliser uniquement la date d'attestation globale (pas de date d'attestation par arrêt)
            $arretAttestationDate = $attestationDate;

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
                if ((int) $attestation->format('d') >= 27) {
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

            // Calculer les jours de décompte (avant date d'effet)
            $decompte_days = $this->calculateDecompteDays($arret);

            $paymentDetails[$index] = [
                'arret_index' => $index,
                'arret_from' => $arret['arret-from-line'],
                'arret_to' => $arret['arret-to-line'],
                'arret_diff' => $arret['arret_diff'] ?? null,
                'date_effet' => $arret['date-effet'],
                'decompte_days' => $decompte_days,
                'attestation_date' => $arretAttestationDate,
                'attestation_date_extended' => $attestation->format('Y-m-d'),
                'payment_start' => $arretDays > 0 ? $paymentStart->format('Y-m-d') : '',
                'payment_end' => $arretDays > 0 ? $paymentEnd->format('Y-m-d') : '',
                'payable_days' => $arretDays,
                'reason' => $arretDays > 0 ? ($arretAttestationDate ? 'Paid' : 'Paid (no attestation - calculated to end date)') : 'Outside payment period'
            ];
        }

        return [
            'total_days' => $totalDays,
            'payment_details' => $paymentDetails
        ];
    }

    /**
     * Calculer les jours de décompte (avant date d'effet) pour un arrêt
     * Ce sont les jours qui comptent vers le seuil mais ne sont pas payés
     *
     * @param array $arret L'arrêt à analyser
     * @return int Nombre de jours de décompte
     */
    private function calculateDecompteDays(array $arret): int
    {
        // Si pas de date d'effet, tous les jours sont en décompte
        if (!isset($arret['date-effet']) || empty($arret['date-effet'])) {
            if (isset($arret['arret_diff'])) {
                return $arret['arret_diff'];
            }
            $startDate = new DateTime($arret['arret-from-line']);
            $endDate = new DateTime($arret['arret-to-line']);
            return $startDate->diff($endDate)->days + 1;
        }

        $startDate = new DateTime($arret['arret-from-line']);
        $dateEffet = new DateTime($arret['date-effet']);

        // Si date d'effet est avant ou égale au début de l'arrêt, pas de décompte
        if ($dateEffet <= $startDate) {
            return 0;
        }

        // Calculer les jours entre le début et la date d'effet (exclusif)
        // date d'effet - 1 jour car le paiement commence à date d'effet
        $dayBeforeEffet = clone $dateEffet;
        $dayBeforeEffet->modify('-1 day');

        // Si le jour avant effet est avant le début, pas de décompte
        if ($dayBeforeEffet < $startDate) {
            return 0;
        }

        // Décompte = jours du début jusqu'au jour avant date d'effet (inclus)
        return $startDate->diff($dayBeforeEffet)->days + 1;
    }
}
