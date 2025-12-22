<?php

namespace App\Services;

use DateTime;
use RuntimeException;

/**
 * Implémentation du Service de Taux
 * Gère toutes les recherches et calculs de taux
 */
class RateService implements RateServiceInterface {

	/** @var array<int, array<string, mixed>> */
	private array $rates = [];

	private ?float $passValue = null;

	/**
	 * Constructeur - supporte à la fois le chemin CSV hérité (string) et le nouveau format tableau
	 *
	 * @param array<int, array<string, mixed>>|string $csvPathOrRates Chemin du fichier CSV (string) ou tableau de taux
	 */
	public function __construct(array|string $csvPathOrRates = []) {
		if (is_string($csvPathOrRates)) {
			// Support hérité: charger depuis le chemin du fichier CSV
			$this->loadRatesFromCsv($csvPathOrRates);
		} elseif (is_array($csvPathOrRates)) {
			// Nouveau format: utiliser directement le tableau de taux
			$this->loadRates($csvPathOrRates);
		}
	}

	/**
	 * @return void
	 */
	public function setPassValue(float $value): void {
		$this->passValue = $value;
	}

	/**
	 * Charger les taux depuis un tableau (nouveau format)
	 * @param array<int, array<string, mixed>> $rates
	 * @return void
	 */
	private function loadRates(array $rates): void {
		$this->rates = $rates;
	}

	/**
	 * Charger les taux depuis le chemin du fichier CSV (format hérité)
	 * @return void
	 */
	private function loadRatesFromCsv(string $csvPath): void {
		if (!file_exists($csvPath)) {
			throw new RuntimeException("CSV file not found: {$csvPath}");
		}

		$file = fopen($csvPath, 'r');
		if ($file === false) {
			throw new RuntimeException("Unable to open CSV file: {$csvPath}");
		}

		$headers = fgetcsv($file, 0, ';');
		if ($headers === false) {
			fclose($file);

			throw new RuntimeException('Empty or invalid CSV file');
		}

		$rates = [];
		while (($row = fgetcsv($file, 0, ';')) !== false) {
			$rate = [];
			foreach ($headers as $index => $header) {
				$value = $row[$index] ?? '';

				// Convertir les chaînes de dates en objets DateTime
				if ($header === 'date_start' || $header === 'date_end') {
					$rate[$header] = new DateTime($value);
				} else {
					$rate[$header] = $value;
				}
			}
			$rates[] = $rate;
		}
		fclose($file);

		$this->loadRates($rates);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getRateForYear(int $year): ?array {
		foreach ($this->rates as $rate) {
			// Gérer à la fois les objets DateTime et les dates en chaîne
			if ($rate['date_start'] instanceof \DateTime) {
				$startYear = (int)$rate['date_start']->format('Y');
				$endYear = (int)$rate['date_end']->format('Y');
			} else {
				$startYear = (int)date('Y', strtotime($rate['date_start']));
				$endYear = (int)date('Y', strtotime($rate['date_end']));
			}

			if ($year >= $startYear && $year <= $endYear) {
				return $rate;
			}
		}

		return null;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getRateForDate(string $date): ?array {
		$dateTimestamp = strtotime($date);
		foreach (DateNormalizer::normalize($this->rates) as $rate) {
			// Gérer à la fois les objets DateTime et les dates en chaîne
			$dateStart = $rate['date_start'];
			$dateEnd = $rate['date_end'];
			// Convertir DateTime en chaîne si nécessaire
			if ($dateStart instanceof \DateTimeInterface) {
				$dateStart = $dateStart->format('Y-m-d');
			}
			if ($dateEnd instanceof \DateTimeInterface) {
				$dateEnd = $dateEnd->format('Y-m-d');

			}

			$startTimestamp = strtotime((string)$dateStart);
			$endTimestamp = strtotime($dateEnd);

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
		?bool $usePeriode2 = null,
		?float $revenu = null,
		?string $calculationDate = null
	): float {

		$effectiveDate = $date ?? "$year-01-01";
		$dateEffetTimestamp = strtotime($effectiveDate);
		$isDateEffetAfter2025 = $dateEffetTimestamp >= strtotime('2025-01-01');

		// Réforme 2025 : Calcul basé sur PASS UNIQUEMENT pour les nouveaux arrêts (date_effet >= 2025)
		if ($isDateEffetAfter2025) {
			return $this->calculate2025Rate($statut, $classe, $option, $taux);
		}

		// Pour les arrêts avec date_effet < 2025 :
		// Utiliser le taux basé sur l'année du JOUR calculé (pas l'année courante)
		// Si calculationDate fourni, utiliser son année
		// Sinon, utiliser l'année de date_effet
		$dayYear = $calculationDate
			? (int)date('Y', strtotime($calculationDate))
			: (int)date('Y', $dateEffetTimestamp);

		if ($dayYear >= 2025) {
			// Jour en 2025 ou après → Utiliser les taux 2025 de la DB
			$rateData = $this->getRateForYear(2025);
		} else {
			// Jour en 2024 ou avant → Utiliser les taux de l'année du jour
			if ($calculationDate) {
				$rateData = $this->getRateForDate($calculationDate);
			} elseif ($date) {
				$rateData = $this->getRateForDate($date);
			} else {
				$rateData = $this->getRateForYear($year);
			}
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
	 * Calcul des taux selon la réforme 2025
	 * Basé sur la valeur du PASS (Plafond Annuel de la Sécurité Sociale)
	 *
	 * Formule de base par classe:
	 * - Classe A: 1 * PASS / 730
	 * - Classe B: 2 * PASS / 730
	 * - Classe C: 3 * PASS / 730
	 *
	 * Puis application des réductions de taux:
	 * - Taux 1: 100% (base)
	 * - Taux 2: base - 1/3 = 66.67%
	 * - Taux 3: base - 2/3 = 33.33%
	 * - Taux 4: 50% de base
	 * - Taux 5: taux 4 - 1/3
	 * - Taux 6: taux 4 - 2/3
	 * - Taux 7: 75% de base
	 * - Taux 8: taux 7 - 1/3
	 * - Taux 9: taux 7 - 2/3
	 *
	 * @param string $statut Statut professionnel (M, RSPM, CCPL)
	 * @param string $classe Classe de cotisation (A, B, C)
	 * @param string|int|float $option Pourcentage d'option (pour CCPL/RSPM)
	 * @param int $taux Numéro de taux (1-9)
	 * @return float Taux journalier calculé
	 */
	private function calculate2025Rate(
		string $statut,
		string $classe,
		string|int|float $option,
		int $taux
	): float {
		// Valeur du PASS (peut être surchargée via setPassValue)
		$passValue = $this->passValue ?? 46368; // PASS 2024 par défaut

		// Multiplicateur de classe: A=1, B=2, C=3
		$classeMultiplier = match(strtoupper($classe)) {
			'A' => 1,
			'B' => 2,
			'C' => 3,
			default => 2 // Par défaut classe B
		};

		// Calcul du taux de base: (multiplicateur * PASS) / 730
		$baseRate = ($classeMultiplier * $passValue) / 730;

		// Application des réductions selon le numéro de taux
		$rate = match($taux) {
			// Taux 1-3: Taux plein avec réductions par tiers
			1 => $baseRate,                    // 100%
			2 => $baseRate * (2/3),            // 66.67% (- 1/3)
			3 => $baseRate * (1/3),            // 33.33% (- 2/3)

			// Taux 4-6: 50% du taux de base avec réductions par tiers
			4 => $baseRate * 0.5,              // 50%
			5 => $baseRate * 0.5 * (2/3),      // 33.33% de base
			6 => $baseRate * 0.5 * (1/3),      // 16.67% de base

			// Taux 7-9: 75% du taux de base avec réductions par tiers
			7 => $baseRate * 0.75,             // 75%
			8 => $baseRate * 0.75 * (2/3),     // 50% de base
			9 => $baseRate * 0.75 * (1/3),     // 25% de base

			default => $baseRate
		};

		// Application du pourcentage d'option pour CCPL et RSPM
		if (in_array(strtoupper($statut), ['CCPL', 'RSPM'])) {
			$optionValue = (float)str_replace(',', '.', (string)$option);

			// Convertir en décimal si nécessaire (100 -> 1.0)
			if ($optionValue > 1) {
				$optionValue = $optionValue / 100;
			}

			// Appliquer l'option si valide (entre 0 et 1)
			if ($optionValue > 0 && $optionValue <= 1) {
				$rate *= $optionValue;
			}
		}

		return round($rate, 2);
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
