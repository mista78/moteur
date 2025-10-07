<?php

namespace IJCalculator\Services;

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

        $interval = $affiliation->diff($current);
        $months = ($interval->y * 12) + $interval->m;

        return (int) floor($months / 3);
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

            $lastEnd->modify('+1 weekday');

            if ($lastEnd->format('Y-m-d') == $currentStart->format('Y-m-d')) {
                $last['arret-to-line'] = $arret['arret-to-line'];
            } else {
                $merged[] = $arret;
            }
        }

        return $merged;
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
                $increment++;
                if (count($arrets) === $increment) {
                    break;
                }
                continue;
            }

            // Premier arrêt - calcul de la date d'ouverture des droits (90 jours)
            if ($arretDroits === 0) {
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
                $siRechute = ($currentData['rechute-line'] ?? 0) > 0 ? 1 : 0;

                // Rechute immédiate (rechute-line = 1) - droits au 1er jour
                if ($siRechute === 1 && $currentData['rechute-line'] === 1) {
                    $dates = $startDate->format('Y-m-d');
                }
                // Arrêt >= 15 jours mais pas rechute immédiate - droits au 15ème jour
                elseif ($arret_diff >= 15 && $currentData['rechute-line'] !== 1) {
                    $dateEffet = clone $startDate;
                    $dateEffet->modify('+14 days');
                    $dates = $dateEffet->format('Y-m-d');
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
            // Vérifier valid_med_controleur - pas de paiement si != 1
            if (isset($arret['valid_med_controleur']) && $arret['valid_med_controleur'] != 1) {
                $paymentDetails[$index] = [
                    'arret_index' => $index,
                    'arret_from' => $arret['arret-from-line'],
                    'arret_to' => $arret['arret-to-line'],
                    'date_effet' => $arret['date-effet'] ?? null,
                    'attestation_date' => null,
                    'payment_start' => null,
                    'payment_end' => null,
                    'payable_days' => 0,
                    'reason' => 'Not validated by medical controller (valid_med_controleur != 1)'
                ];
                continue;
            }

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

            $paymentDetails[$index] = [
                'arret_index' => $index,
                'arret_from' => $arret['arret-from-line'],
                'arret_to' => $arret['arret-to-line'],
                'arret_diff' => $arret['arret_diff'] ?? null,
                'date_effet' => $arret['date-effet'],
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
}
