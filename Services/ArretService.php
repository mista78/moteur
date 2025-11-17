<?php

namespace App\IJCalculator\Services;

use DateTime;

/**
 * Service to generate ij_arret table records from calculation results
 */
class ArretService {

	/**
	 * DateService instance for date calculations
	 */
	private ?DateCalculationInterface $dateService = null;

	/**
	 * Set DateService instance
	 */
	public function setDateService(DateCalculationInterface $dateService): void {
		$this->dateService = $dateService;
	}

	/**
	 * Get or create DateService instance
	 */
	private function getDateService(): DateCalculationInterface {
		if ($this->dateService === null) {
			$this->dateService = new DateService();
		}
		return $this->dateService;
	}

	/**
	 * Generate ij_arret records from calculation result
	 *
	 * @param array $calculationResult Result from IJCalculator->calculateAmount()
	 * @param array $inputData Original input data with adherent_number, num_sinistre, etc.
	 * @return array Array of records ready for database insertion
	 */
	public function generateArretRecords(array $calculationResult, array $inputData): array {
		$records = [];

		// Validate required input fields
		$this->validateInputData($inputData);

		$adherentNumber = $inputData['adherent_number'];
		$numSinistre = $inputData['num_sinistre'];
		$attestationDate = $inputData['attestation_date'] ?? null;

		// Use payment_details for accurate payment information
		$paymentDetails = $calculationResult['payment_details'] ?? [];

		foreach ($paymentDetails as $index => $paymentDetail) {
			// Get the corresponding arrêt data
			$arret = $calculationResult['arrets'][$index] ?? [];

			// Merge payment details with arrêt data
			$mergedData = array_merge($arret, $paymentDetail);

			$record = $this->transformArretToDbFormat(
				$mergedData,
				$adherentNumber,
				$numSinistre,
				$attestationDate,
				$index,
				$paymentDetail
			);

			$records[] = $record;
		}

		return $records;
	}

	/**
	 * Transform a single arrêt to database format
	 */
	private function transformArretToDbFormat(
		array $arret,
		string $adherentNumber,
		int $numSinistre,
		?string $attestationDate,
		int $index,
		?array $paymentDetail = null
	): array {
		// Handle DT_excused logic (inverted from dt-line)
		// dt-line = 1 means NOT excused (penalty applies)
		// DT_excused = 1 means excused (no penalty)
		$dtExcused = null;
		if (isset($arret['dt-line'])) {
			$dtExcused = ($arret['dt-line'] == 1) ? 0 : 1;
		}
		// Alternative: if DT_excused field exists directly
		if (isset($arret['DT_excused'])) {
			$dtExcused = (int)$arret['DT_excused'];
		}

		// Determine date_prolongation from merged_arrets
		$dateProlongation = null;
		if (isset($arret['has_prolongations']) && $arret['has_prolongations']) {
			// If this arrêt has prolongations, use the last merged arrêt's end date
			if (isset($arret['merged_arrets']) && !empty($arret['merged_arrets'])) {
				$lastMerged = end($arret['merged_arrets']);
				$dateProlongation = $lastMerged['to'] ?? null;
			}
		}

		// Determine first_day based on payment_details
		// first_day = 1 if first day of arrêt is paid (no décompte)
		// first_day = 0 if first day is excused (décompte exists)
		$firstDay = 0;
		if ($paymentDetail !== null) {
			$paymentStart = $paymentDetail['payment_start'] ?? null;
			$arretFrom = $arret['arret-from-line'] ?? $arret['arret_from'] ?? null;

			// If payment starts on same day as arrêt, first day is paid
			if ($paymentStart && $arretFrom && $paymentStart === $arretFrom) {
				$firstDay = 1;
			}

			// If no decompte days, first day is paid
			if (isset($paymentDetail['decompte_days']) && $paymentDetail['decompte_days'] == 0) {
				$firstDay = 1;
			}
		}

		// Get taux from arret data
		$taux = null;
		if (isset($arret['taux'])) {
			$taux = (float)$arret['taux'];
		} elseif (isset($arret['taux_number'])) {
			$taux = (float)$arret['taux_number'];
		}

		// Get code_pathologie
		$codePathologie = $arret['code-patho-line'] ?? $arret['code_pathologie'] ?? null;
		if (!$codePathologie) {
			throw new \InvalidArgumentException("code_pathologie is required for ij_arret record");
		}

		// Get date_deb_droit and normalize it
		$dateDebDroit = $arret['date-effet'] ?? $arret['ouverture-date-line'] ?? null;
		if ($dateDebDroit === '0000-00-00' || $dateDebDroit === '') {
			$dateDebDroit = null;
		}

		// Get decompte_days from payment_details
		$decompteDays = 0;
		if ($paymentDetail !== null && isset($paymentDetail['decompte_days'])) {
			$decompteDays = (int)$paymentDetail['decompte_days'];
		}

		// Get rechute status
		$rechute = 0;
		if (isset($arret['rechute-line'])) {
			$rechute = (int)$arret['rechute-line'];
		} elseif (isset($arret['is_rechute'])) {
			$rechute = $arret['is_rechute'] ? 1 : 0;
		}

		return [
			'adherent_number' => $adherentNumber,
			'code_pathologie' => $codePathologie,
			'num_sinistre' => $numSinistre,
			'date_start' => $this->normalizeDate($arret['arret-from-line'] ?? null),
			'date_end' => $this->normalizeDate($arret['arret-to-line'] ?? null),
			'date_prolongation' => $this->normalizeDate($dateProlongation),
			'first_day' => $firstDay,
			'decompte_days' => $decompteDays,
			'rechute' => $rechute,
			'date_declaration' => $this->normalizeDate($arret['declaration-date-line'] ?? $arret['date_declaration'] ?? null),
			'DT_excused' => $dtExcused,
			'valid_med_controleur' => isset($arret['valid_med_controleur']) ? (int)$arret['valid_med_controleur'] : null,
			'cco_a_jour' => isset($arret['cco_a_jour']) ? (int)$arret['cco_a_jour'] : null,
			'date_dern_attestation' => $this->normalizeDate($attestationDate),
			'date_deb_droit' => $dateDebDroit,
			'date_deb_dr_force' => $this->normalizeDate($arret['date_deb_dr_force'] ?? null),
			'taux' => $taux,
			'NOARRET' => $arret['NOARRET'] ?? null,
			'version' => 1,
			'actif' => 1,
		];
	}

	/**
	 * Validate required input data
	 */
	private function validateInputData(array $inputData): void {
		$required = ['adherent_number', 'num_sinistre'];

		foreach ($required as $field) {
			if (!isset($inputData[$field]) || empty($inputData[$field])) {
				throw new \InvalidArgumentException("Missing required field: $field");
			}
		}

		// Validate adherent_number length (must be 7 characters)
		if (strlen($inputData['adherent_number']) !== 7) {
			throw new \InvalidArgumentException("adherent_number must be exactly 7 characters");
		}

		// Validate num_sinistre is integer
		if (!is_numeric($inputData['num_sinistre'])) {
			throw new \InvalidArgumentException("num_sinistre must be an integer");
		}
	}

	/**
	 * Generate SQL INSERT statement for a single record
	 */
	public function generateInsertSQL(array $record): string {
		$fields = [];
		$values = [];

		foreach ($record as $field => $value) {
			$fields[] = "`$field`";

			if ($value === null) {
				$values[] = 'NULL';
			} elseif (is_numeric($value)) {
				$values[] = $value;
			} else {
				$values[] = "'" . addslashes($value) . "'";
			}
		}

		$fieldsStr = implode(', ', $fields);
		$valuesStr = implode(', ', $values);

		return "INSERT INTO `ij_arret` ($fieldsStr) VALUES ($valuesStr);";
	}

	/**
	 * Generate batch SQL INSERT statement for multiple records
	 */
	public function generateBatchInsertSQL(array $records): string {
		if (empty($records)) {
			return '';
		}

		// Get field names from first record
		$firstRecord = reset($records);
		$fields = array_keys($firstRecord);
		$fieldsStr = implode(', ', array_map(fn($f) => "`$f`", $fields));

		$allValues = [];
		foreach ($records as $record) {
			$values = [];
			foreach ($fields as $field) {
				$value = $record[$field] ?? null;

				if ($value === null) {
					$values[] = 'NULL';
				} elseif (is_numeric($value)) {
					$values[] = $value;
				} else {
					$values[] = "'" . addslashes($value) . "'";
				}
			}
			$allValues[] = '(' . implode(', ', $values) . ')';
		}

		$valuesStr = implode(",\n", $allValues);

		return "INSERT INTO `ij_arret` ($fieldsStr) VALUES\n$valuesStr;";
	}

	/**
	 * Validate a record before insertion
	 */
	public function validateRecord(array $record): bool {
		$required = ['adherent_number', 'code_pathologie', 'num_sinistre'];

		foreach ($required as $field) {
			if (!isset($record[$field]) || empty($record[$field])) {
				return false;
			}
		}

		// Validate adherent_number length
		if (strlen($record['adherent_number']) !== 7) {
			return false;
		}

		// Validate dates if present
		$dateFields = ['date_start', 'date_end', 'date_prolongation', 'date_declaration',
		               'date_dern_attestation', 'date_deb_droit', 'date_deb_dr_force'];

		foreach ($dateFields as $field) {
			if (isset($record[$field]) && $record[$field] !== null) {
				if (!$this->isValidDate($record[$field])) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Normalize date value (convert '0000-00-00' and empty strings to null)
	 */
	private function normalizeDate(?string $date): ?string {
		if ($date === null || $date === '' || $date === '0000-00-00') {
			return null;
		}
		return $date;
	}

	/**
	 * Check if a string is a valid date
	 */
	private function isValidDate(?string $date): bool {
		if ($date === null || $date === '' || $date === '0000-00-00') {
			return true; // NULL is valid for optional date fields
		}

		$d = DateTime::createFromFormat('Y-m-d', $date);
		return $d && $d->format('Y-m-d') === $date;
	}

	/**
	 * Generate records including original arrêts details from merged arrêts
	 * This creates one record for the merged arrêt and separate records for each original
	 *
	 * @param array $calculationResult Result from IJCalculator->calculateAmount()
	 * @param array $inputData Original input data
	 * @param bool $includeOriginals If true, creates records for original arrêts that were merged
	 * @return array Array of records
	 */
	public function generateDetailedArretRecords(
		array $calculationResult,
		array $inputData,
		bool $includeOriginals = false
	): array {
		$records = [];

		$this->validateInputData($inputData);

		$adherentNumber = $inputData['adherent_number'];
		$numSinistre = $inputData['num_sinistre'];
		$attestationDate = $inputData['attestation_date'] ?? null;

		$arrets = $calculationResult['arrets_merged'] ?? $calculationResult['arrets'];

		foreach ($arrets as $index => $arret) {
			// Main merged record
			$mainRecord = $this->transformArretToDbFormat(
				$arret,
				$adherentNumber,
				$numSinistre,
				$attestationDate,
				$index
			);
			$records[] = $mainRecord;

			// If includeOriginals is true and this arrêt has merged prolongations
			if ($includeOriginals && isset($arret['merged_arrets']) && !empty($arret['merged_arrets'])) {
				foreach ($arret['merged_arrets'] as $mergedInfo) {
					// Create a record for each original merged arrêt
					$originalRecord = [
						'adherent_number' => $adherentNumber,
						'code_pathologie' => $arret['code-patho-line'] ?? $arret['code_pathologie'] ?? null,
						'num_sinistre' => $numSinistre,
						'date_start' => $mergedInfo['from'],
						'date_end' => $mergedInfo['to'],
						'date_prolongation' => null,
						'first_day' => 0,
						'date_declaration' => $arret['declaration-date-line'] ?? null,
						'DT_excused' => isset($arret['dt-line']) ? (($arret['dt-line'] == 1) ? 0 : 1) : null,
						'valid_med_controleur' => isset($arret['valid_med_controleur']) ? (int)$arret['valid_med_controleur'] : null,
						'cco_a_jour' => isset($arret['cco_a_jour']) ? (int)$arret['cco_a_jour'] : null,
						'date_dern_attestation' => $attestationDate,
						'date_deb_droit' => null,
						'date_deb_dr_force' => null,
						'taux' => null,
						'NOARRET' => null,
						'version' => 1,
						'actif' => 0, // Mark as inactive since it's merged into another
					];
					$records[] = $originalRecord;
				}
			}
		}

		return $records;
	}

	/**
	 * Calculate date_effet for arrêts without full calculation
	 *
	 * @param array $arrets Array of arrêts to process
	 * @param string|null $birthDate Birth date for age calculation (optional)
	 * @param int $previousCumulDays Previous cumulative days (default: 0)
	 * @return array Arrêts with date-effet calculated
	 */
	public function calculateDateEffetForArrets(
		array $arrets,
		?string $birthDate = null,
		int $previousCumulDays = 0
	): array {
		$dateService = $this->getDateService();

		// Calculate date-effet using DateService
		$arretsWithDateEffet = $dateService->calculateDateEffet(
			$arrets,
			$birthDate,
			$previousCumulDays
		);

		return $arretsWithDateEffet;
	}

	/**
	 * Generate ij_arret records from arrêts list only (without full calculation)
	 * This calculates date_effet internally
	 *
	 * @param array $arrets Array of arrêts
	 * @param array $inputData Optional input data (adherent_number, num_sinistre can be in arrêts)
	 * @return array Array of ij_arret records
	 */
	public function generateArretRecordsFromList(array $arrets, array $inputData = []): array {
		// Extract common data from first arrêt if not provided in inputData
		$firstArret = !empty($arrets) ? $arrets[0] : [];

		$adherentNumber = $inputData['adherent_number']
			?? $firstArret['adherent_number']
			?? $firstArret['num_adherent']
			?? null;

		$numSinistre = $inputData['num_sinistre']
			?? $firstArret['num_sinistre']
			?? $firstArret['sinistre_id']
			?? null;

		$attestationDate = $inputData['attestation_date']
			?? $firstArret['attestation_date']
			?? $firstArret['date_dern_attestation']
			?? null;

		$birthDate = $inputData['birth_date']
			?? $firstArret['birth_date']
			?? null;

		$previousCumulDays = $inputData['previous_cumul_days'] ?? 0;

		// Validate only if not found in arrêts
		if (!$adherentNumber || !$numSinistre) {
			// Try to validate from inputData if provided
			if (!empty($inputData)) {
				$this->validateInputData($inputData);
				$adherentNumber = $inputData['adherent_number'];
				$numSinistre = $inputData['num_sinistre'];
			}
		}

		// Calculate date-effet for arrêts
		$arretsWithDateEffet = $this->calculateDateEffetForArrets(
			$arrets,
			$birthDate,
			$previousCumulDays
		);

		// Generate records
		$records = [];
		foreach ($arretsWithDateEffet as $index => $arret) {
			// Get adherent_number and num_sinistre from arrêt itself if available
			$arretAdherent = $arret['adherent_number'] ?? $arret['num_adherent'] ?? $adherentNumber;
			$arretSinistre = $arret['num_sinistre'] ?? $arret['sinistre_id'] ?? $numSinistre;

			// Create a mock payment_detail from calculated data
			$paymentDetail = [
				'arret_from' => $arret['arret-from-line'] ?? null,
				'arret_to' => $arret['arret-to-line'] ?? null,
				'payment_start' => $arret['payment_start'] ?? $arret['date-effet'] ?? null,
				'payment_end' => null,
				'decompte_days' => $arret['decompte_days'] ?? 0,
				'payable_days' => $arret['payable_days'] ?? 0,
			];

			$mergedData = array_merge($arret, $paymentDetail);

			$record = $this->transformArretToDbFormat(
				$mergedData,
				$arretAdherent,
				$arretSinistre,
				$attestationDate,
				$index,
				$paymentDetail
			);

			$records[] = $record;
		}

		return $records;
	}
}
