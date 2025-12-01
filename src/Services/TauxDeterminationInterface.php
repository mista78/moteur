<?php

namespace App\Services;

/**
 * Interface for taux (rate tier) determination
 * Single Responsibility: Determine which taux number to use based on conditions
 */
interface TauxDeterminationInterface {

	/**
	 * Set a single PASS value for all years
	 * @param float $value The PASS value
	 * @return void
	 */
	public function setPassValue(float $value): void;

	/**
	 * Set PASS values by year
	 * @param array<int, float> $passValues Array with year => pass_value mapping
	 * @return void
	 */
	public function setPassValuesByYear(array $passValues): void;

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
	 * @param int|null $year Year for which to determine class (uses year-specific PASS)
	 * @return string Class (A, B, or C)
	 */
	public function determineClasse(
		?float $revenuNMoins2 = null,
		?string $dateOuvertureDroits = null,
		bool $taxeOffice = false,
		?int $year = null
	): string;

}
