<?php

namespace App\Services;

/**
 * Interface for amount calculation service
 * Single Responsibility: Handle all IJ amount calculations
 */
interface AmountCalculationInterface {

	/**
	 * Calculate IJ amount for given parameters
	 *
	 * @param array<string, mixed> $data Input data containing arrets, classe, statut, dates, etc.
	 * @return array<string, mixed> Result with nb_jours, montant, payment_details, etc.
	 */
	public function calculateAmount(array $data): array;

	/**
	 * Calculate end payment dates based on age and cumulative days
	 *
	 * @param array<int, array<string, mixed>> $arrets Work stoppage periods
	 * @param int $previousCumulDays Previously accumulated days
	 * @param string $birthDate Birth date
	 * @param string $currentDate Current date
	 * @return array<string, string>|null End dates for payment periods
	 */
	public function calculateEndPaymentDates(
		array $arrets,
		int $previousCumulDays,
		string $birthDate,
		string $currentDate
	): ?array;

}
