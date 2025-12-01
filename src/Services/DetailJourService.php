<?php

namespace App\Services;

use DateTime;

/**
 * Service to generate ij_detail_jour records from calculation results
 * Maps daily_breakdown to ij_detail_jour table format with j1-j31 columns
 */
class DetailJourService {

	/**
	 * Generate ij_detail_jour records from calculation results
	 *
	 * @param array<string, mixed> $calculationResult Result from IJCalculator::calculateAmount()
	 * @param array<string, mixed> $inputData Original input data (for adherent_number, num_sinistre, etc.)
	 * @return array<int, array<string, mixed>> Array of records ready for ij_detail_jour insertion
	 */
	public function generateDetailJourRecords(array $calculationResult, array $inputData): array {
		$records = [];

		// Extract common data
		$adherentNumber = $inputData['adherent_number'] ?? null;
		$numSinistre = $inputData['num_sinistre'] ?? null;

		// Process each payment detail (each arret)
		if (isset($calculationResult['payment_details']) && is_array($calculationResult['payment_details'])) {
			foreach ($calculationResult['payment_details'] as $detail) {

				// Process daily breakdown if available
				if (isset($detail['daily_breakdown']) && is_array($detail['daily_breakdown'])) {

					// Group daily amounts by year and month
					$monthlyData = $this->groupByYearMonth($detail['daily_breakdown']);

					// Create a record for each month
					foreach ($monthlyData as $yearMonth => $days) {
						[$year, $month] = explode('-', $yearMonth);

						$record = [
							'adherent_number' => $adherentNumber,
							'exercice' => $year,
							'periode' => $month,
							'num_sinistre' => $numSinistre,
						];

						// Add daily amounts (j1 to j31)
						for ($day = 1; $day <= 31; $day++) {
							$record["j$day"] = isset($days[$day])
								? $this->convertToIntCents($days[$day])
								: null;
						}

						$records[] = $record;
					}
				}
			}
		}

		return $records;
	}

	/**
	 * Group daily breakdown by year and month
	 *
	 * @param array<int, array<string, mixed>> $dailyBreakdown Array of daily entries with date and amount
	 * @return array<string, array<int, float>> Grouped by 'YYYY-MM' with day number as key
	 */
	private function groupByYearMonth(array $dailyBreakdown): array {
		$grouped = [];

		foreach ($dailyBreakdown as $entry) {
			if (!isset($entry['date']) || !isset($entry['amount'])) {
				continue;
			}

			// Parse date
			$date = $entry['date'];
			$dateObj = DateTime::createFromFormat('Y-m-d', $date);

			if (!$dateObj) {
				continue;
			}

			$year = $dateObj->format('Y');
			$month = $dateObj->format('m');
			$day = (int)$dateObj->format('d');

			$yearMonth = "$year-$month";

			if (!isset($grouped[$yearMonth])) {
				$grouped[$yearMonth] = [];
			}

			// Store amount for this day
			$grouped[$yearMonth][$day] = $entry['amount'];
		}

		return $grouped;
	}

	/**
	 * Convert decimal amount to integer cents
	 * E.g., 75.06 -> 7506
	 *
	 * @param float $amount Amount in euros
	 * @return int Amount in cents
	 */
	private function convertToIntCents(float $amount): int {
		return (int)round($amount * 100);
	}

}
