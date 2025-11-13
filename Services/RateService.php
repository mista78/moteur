<?php

namespace App\IJCalculator\Services;

/**
 * Implémentation du Service de Taux
 * Gère toutes les recherches et calculs de taux
 */
class RateService implements RateServiceInterface {

	private array $rates = [];

	private ?float $passValue = null;

	public function __construct(array $csvPath = []) {
		$this->loadRates($csvPath);
	}

	/**
	 * @return void
	 */
	public function setPassValue(float $value): void {
		$this->passValue = $value;
	}

	/**
	 * @return void
	 */
	private function loadRates(array $rates): void {
		$this->rates = $rates;
	}

	public function getRateForYear(int $year): ?array {
		foreach ($this->rates as $rate) {
			$startYear = (int)date('Y', strtotime($rate['date_start']));
			$endYear = (int)date('Y', strtotime($rate['date_end']));

			if ($year >= $startYear && $year <= $endYear) {
				return $rate;
			}
		}

		return null;
	}

	public function getRateForDate(string $date): ?array {
		$dateTimestamp = strtotime($date);

		foreach ($this->rates as $rate) {
			$startTimestamp = strtotime($rate['date_start']->format('Y-m-d'));
			$endTimestamp = strtotime($rate['date_end']->format('Y-m-d'));

			if ($dateTimestamp >= $startTimestamp && $dateTimestamp <= $endTimestamp) {
				return $rate;
			}
		}

		return null;
	}

	public function getDailyRate(
		string $statut,
		string $classe,
		string|int|float $option,
		int $taux,
		int $year,
		?string $date = null,
		?int $age = null,
		?bool $usePeriode2 = null
	): float {
		// Obtenir les données de taux
		if ($date) {
			$rateData = $this->getRateForDate($date);
		} else {
			$rateData = $this->getRateForYear($year);
		}
		if (!$rateData) {
			return 0.0;
		}

		$classeKey = strtolower($classe);

		// Déterminer le palier basé sur le numéro de taux
		$tier = $this->determineTier($taux, $age, $usePeriode2);
		$columnKey = "taux_{$classeKey}{$tier}";
		$baseRate = isset($rateData[$columnKey]) ? (float)$rateData[$columnKey] : 0.0;

		// Appliquer le multiplicateur d'option pour CCPL et RSPM
		if (in_array(strtoupper($statut), ['CCPL', 'RSPM'])) {
			$optionValue = (float)str_replace(',', '.', (string)$option);

			if ($optionValue > 1) {
				$optionValue = $optionValue / 100;
			}

			if ($optionValue > 0 && $optionValue <= 1) {
				$baseRate *= $optionValue;
			}
		}

		return $baseRate;
	}

	/**
	 * Déterminer le palier CSV basé sur le numéro de taux
	 *
	 * @param int $taux Numéro de taux (1-9)
	 * @param int|null $age Âge pour traitement spécial
	 * @param bool|null $usePeriode2 Si la période 2 existe
	 * @return int Numéro de palier (1, 2, ou 3)
	 */
	private function determineTier(int $taux, ?int $age, ?bool $usePeriode2): int {
		//  Taux 1-3 : Période 1 → palier 1 (taux plein)

		if ($taux >= 1 && $taux <= 3) {
			return 1;
		}

		// Taux 7-9 : Période 2 (366-730 jours) → palier 3 (taux intermédiaire)
		if ($taux >= 7 && $taux <= 9) {
			return 3;
		}

		// Taux 4-6 : Période 3
		if ($taux >= 4 && $taux <= 6) {
			// Pour 70 ans et plus : toujours palier 2 (taux réduit)
			if ($age !== null && $age >= 70) {
				return 2;
			}
			// Pour 62-69 ans : dépend de usePeriode2
			// - Si usePeriode2=true (arrêt >= 730j) : période 3 commence à 731j → palier 2
			// - Si usePeriode2=false (arrêt < 730j) : période 3 commence à 366j → palier 3
			if ($usePeriode2 === true) {
				return 2;
			}

			return 3;
		}

		return 1; // par défaut
	}

}
