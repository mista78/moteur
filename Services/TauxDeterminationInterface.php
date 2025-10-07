<?php

namespace IJCalculator\Services;

/**
 * Interface for taux (rate tier) determination
 * Single Responsibility: Determine which taux number to use based on conditions
 */
interface TauxDeterminationInterface
{
    /**
     * Determine taux number based on age, trimesters, and pathology
     *
     * @param int $age Age of the person
     * @param int $nbTrimestres Number of affiliation trimesters
     * @param bool $pathoAnterior Whether pathology is anterior
     * @param int|null $historicalReducedRate Historical reduced rate if any
     * @return int Taux number (1-9)
     */
    public function determineTauxNumber(
        int $age,
        int $nbTrimestres,
        bool $pathoAnterior,
        ?int $historicalReducedRate = null
    ): int;

    /**
     * Determine contribution class based on revenue
     *
     * @param float|null $revenuNMoins2 Revenue from N-2 year
     * @param string|null $dateOuvertureDroits Rights opening date
     * @param bool $taxeOffice Whether taxed by office
     * @return string Class (A, B, or C)
     */
    public function determineClasse(
        ?float $revenuNMoins2 = null,
        ?string $dateOuvertureDroits = null,
        bool $taxeOffice = false
    ): string;
}
