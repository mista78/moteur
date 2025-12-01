<?php

namespace App\Services;

/**
 * Interface for date-related calculations
 * Single Responsibility: Handle all date operations
 */
interface DateCalculationInterface {

	/**
	 * Calculate age at a given date
	 *
	 * @param string $currentDate
	 * @param string $birthDate
	 * @return int Age in years
	 */
	public function calculateAge(string $currentDate, string $birthDate): int;

	/**
	 * Calculate number of trimesters between two dates
	 *
	 * @param string $affiliationDate
	 * @param string $currentDate
	 * @return int Number of trimesters
	 */
	public function calculateTrimesters(string $affiliationDate, string $currentDate): int;

	/**
	 * Merge consecutive prolongations into single periods
	 *
	 * @param array<int, array<string, mixed>> $arrets Array of work stoppage periods
	 * @return array<int, array<string, mixed>> Merged periods
	 */
	public function mergeProlongations(array $arrets): array;

	/**
	 * Calculate date d'effet (rights opening date) for work stoppages
	 *
	 * @param array<int, array<string, mixed>> $arrets Array of work stoppage periods
	 * @param string|null $birthDate
	 * @param int $previousCumulDays
	 * @return array<int, array<string, mixed>> Updated arrets with date-effet
	 */
	public function calculateDateEffet(array $arrets, ?string $birthDate = null, int $previousCumulDays = 0): array;

	/**
	 * Calculate payable days for each work stoppage period
	 *
	 * @param array<int, array<string, mixed>> $arrets Array of work stoppage periods with date-effet
	 * @param string|null $attestationDate
	 * @param string|null $lastPaymentDate
	 * @param string|null $currentDate
	 * @return array<string, mixed> ['total_days' => int, 'payment_details' => array]
	 */
	public function calculatePayableDays(
		array $arrets,
		?string $attestationDate = null,
		?string $lastPaymentDate = null,
		?string $currentDate = null
	): array;

	/**
	 * Get trimester number from date (1-4)
	 *
	 * @param string $date
	 * @return int Trimester number
	 */
	public function getTrimesterFromDate(string $date): int;

}
