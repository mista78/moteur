<?php

namespace IJCalculator\Services;

/**
 * Taux Determination Service
 * Determines which taux number to use based on conditions
 */
class TauxDeterminationService implements TauxDeterminationInterface
{
    private ?float $passValue = null;

    public function setPassValue(float $value): void
    {
        $this->passValue = $value;
    }

    public function determineTauxNumber(
        int $age,
        int $nbTrimestres,
        bool $pathoAnterior,
        ?int $historicalReducedRate = null
    ): int {
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

    public function determineClasse(
        ?float $revenuNMoins2 = null,
        ?string $dateOuvertureDroits = null,
        bool $taxeOffice = false
    ): string {
        // Si taxé d'office, toujours classe A
        if ($taxeOffice) {
            return 'A';
        }

        // Si revenus non communiqués, classe A par défaut
        if ($revenuNMoins2 === null) {
            return 'A';
        }

        // Récupérer le PASS de l'année N-2
        if ($dateOuvertureDroits) {
            $anneeNMoins2 = $this->getAnneeNMoins2($dateOuvertureDroits);
            // Utiliser le PASS configuré ou une valeur par défaut
            $pass = $this->passValue ?? 47000; // Valeur par défaut si non configurée
        } else {
            $pass = $this->passValue ?? 47000;
        }

        // Déterminer la classe selon les seuils
        if ($revenuNMoins2 < $pass) {
            return 'A';
        } elseif ($revenuNMoins2 >= $pass && $revenuNMoins2 <= ($pass * 3)) {
            return 'B';
        } else {
            return 'C';
        }
    }

    /**
     * Obtenir l'année N-2 à partir de la date d'ouverture des droits
     *
     * @param string $dateOuvertureDroits
     * @return int
     */
    private function getAnneeNMoins2(string $dateOuvertureDroits): int
    {
        $date = new \DateTime($dateOuvertureDroits);
        return (int) $date->format('Y') - 2;
    }
}
