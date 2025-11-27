<?php

namespace App\IJCalculator\Services;

/**
 * Interface for rate calculation service
 * Single Responsibility: Handle rate lookups and calculations
 */
interface RateServiceInterface {

	/**
	 * Get daily rate for given parameters
	 *
	 * @param string $statut Professional status (M, RSPM, CCPL)
	 * @param string $classe Contribution class (A, B, C)
	 * @param string|float|int $option Option percentage (can be string like "0,25", int like 25, or float like 0.25)
	 * @param int $taux Tax number (1-9)
	 * @param int $year Year for rate lookup
	 * @param string|null $date Specific date for rate lookup
	 * @param int|null $age Age of the person
	 * @param bool|null $usePeriode2 Whether period 2 is used (for tier determination)
	 * @return float Daily rate
	 */
	public function getDailyRate(
		string $statut,
		string $classe,
		string|int|float $option,
		int $taux,
		int $year,
		?string $date = null,
		?int $age = null,
		?bool $usePeriode2 = null,
		?float $revenu = null
	): float;

	/**
	 * Get rate data for a specific year
	 *
	 * @param int $year
	 * @return array|null
	 */
	public function getRateForYear(int $year): ?array;

	/**
	 * Get rate data for a specific date
	 *
	 * @param string $date
	 * @return array|null
	 */
	public function getRateForDate(string $date): ?array;

}
