<?php

namespace App\IJCalculator\Services;

use DateTime;

/**
 * Service to generate ij_recap records from calculation results
 * Maps calculator output to ij_recap table format
 */
class RecapService {

	private $calculator;

	/**
	 * Set the calculator instance for class determination
	 *
	 * @param mixed $calculator IJCalculator instance
	 * @return void
	 */
	public function setCalculator($calculator): void {
		$this->calculator = $calculator;
	}

	/**
	 * Generate ij_recap records from calculation results
	 *
	 * @param array $calculationResult Result from IJCalculator::calculateAmount()
	 * @param array $inputData Original input data (for adherent_number, num_sinistre, etc.)
	 * @return array Array of records ready for ij_recap insertion
	 */
	public function generateRecapRecords(array $calculationResult, array $inputData): array {
		$records = [];

		// Extract common data from input
		$adherentNumber = $inputData['adherent_number'] ?? null;
		$numSinistre = $inputData['num_sinistre'] ?? null;

		// Auto-determine class if calculator available and revenu_n_moins_2 provided
		$classe = $this->determineClasse($inputData);
		$age = $calculationResult['age'] ?? null;
		$nbTrimestres = $calculationResult['nb_trimestres'] ?? $inputData['nb_trimestres'] ?? null;

		// Get revenue reference if available
		$revenuRef = null;
		if (isset($inputData['revenu_n_moins_2'])) {
			$revenuRef = (int)$inputData['revenu_n_moins_2'];
		} elseif (isset($inputData['pass_value']) && $classe === 'A') {
			$revenuRef = (int)$inputData['pass_value'];
		} elseif (isset($inputData['pass_value']) && $classe === 'C') {
			$revenuRef = (int)($inputData['pass_value'] * 3);
		}

		// Process each payment detail (each arret)
		if (isset($calculationResult['payment_details']) && is_array($calculationResult['payment_details'])) {
			foreach ($calculationResult['payment_details'] as $k => $detail) {
				$idArret = $inputData['arrets'][$k]['id'] ?? $detail['arret_index'] ?? null;

				// Process each rate breakdown period
				if (isset($detail['rate_breakdown']) && is_array($detail['rate_breakdown'])) {
					foreach ($detail['rate_breakdown'] as $rateBreakdown) {
						// Split rate_breakdown by month
						$monthlyBreakdowns = $this->splitByMonth($rateBreakdown);

						foreach ($monthlyBreakdowns as $monthly) {
							$record = [
								// Primary identification
								'adherent_number' => $adherentNumber,
								'num_sinistre' => $numSinistre,
								'id_arret' => $idArret,

								// Period information
								'exercice' => $monthly['year'],
								'periode' => $monthly['month'], // Month (01-12), not period (1-3)
								'date_start' => $monthly['start'],
								'date_end' => $monthly['end'],

								// Rate and amount information
								'num_taux' => $rateBreakdown['taux'] ?? null,
								'MT_journalier' => $this->convertToIntCents($rateBreakdown['rate'] ?? 0),

								// Financial information
								'MT_revenu_ref' => $revenuRef,
								'classe' => $classe,

								// Personal information
								'personne_age' => $rateBreakdown['segmentAge'] ?? $age,
								'nb_trimestre' => $rateBreakdown['nb_trimestres'],
							];

							$records[] = $record;
						}
					}
				}
			}
		}

		return $records;
	}

	/**
	 * Determine class from input data using calculator if available
	 *
	 * @param array $inputData Input data with classe or revenu_n_moins_2
	 * @return string|null Determined class (A/B/C) or null
	 */
	private function determineClasse(array $inputData): ?string {
		// If class is already provided, use it
		if (isset($inputData['classe']) && !empty($inputData['classe'])) {
			return $inputData['classe'];
		}

		// If calculator is available and revenu_n_moins_2 is provided, auto-determine
		if ($this->calculator !== null && isset($inputData['revenu_n_moins_2'])) {
			$revenuNMoins2 = (float)$inputData['revenu_n_moins_2'];
			$taxeOffice = isset($inputData['taxe_office']) ? (bool)$inputData['taxe_office'] : false;
			$dateOuvertureDroits = $inputData['date_ouverture_droits'] ?? null;

			return $this->calculator->determineClasse($revenuNMoins2, $dateOuvertureDroits, $taxeOffice);
		}

		// No class determination possible
		return null;
	}

	/**
	 * Split a rate_breakdown entry by month
	 *
	 * @param array $rateBreakdown Rate breakdown entry with start/end dates
	 * @return array Array of monthly breakdowns
	 */
	private function splitByMonth(array $rateBreakdown): array {
		if (!isset($rateBreakdown['start']) || !isset($rateBreakdown['end'])) {
			return [];
		}

		$startDate = new DateTime($rateBreakdown['start']);
		$endDate = new DateTime($rateBreakdown['end']);
		$monthlyBreakdowns = [];

		$currentDate = clone $startDate;

		while ($currentDate <= $endDate) {
			$year = $currentDate->format('Y');
			$month = $currentDate->format('m');

			// Calculate month boundaries
			$monthStart = max($startDate, new DateTime($year . '-' . $month . '-01'));
			$monthEnd = min($endDate, new DateTime($currentDate->format('Y-m-t'))); // Last day of month

			// Calculate days in this month
			$days = $monthStart->diff($monthEnd)->days + 1;

			$monthlyBreakdowns[] = [
				'year' => $year,
				'month' => $month,
				'start' => $monthStart->format('Y-m-d'),
				'end' => $monthEnd->format('Y-m-d'),
				'days' => $days,
			];

			// Move to next month
			$currentDate->modify('first day of next month');
		}

		return $monthlyBreakdowns;
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
