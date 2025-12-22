<?php

namespace App\Services;

use DateTime;

/**
 * Service pour générer les enregistrements ij_detail_jour depuis les résultats de calcul
 * Mappe daily_breakdown au format de table ij_detail_jour avec les colonnes j1-j31
 */
class DetailJourService {

	/**
	 * Générer les enregistrements ij_detail_jour depuis les résultats de calcul
	 *
	 * @param array<string, mixed> $calculationResult Résultat de IJCalculator::calculateAmount()
	 * @param array<string, mixed> $inputData Données d'entrée originales (pour adherent_number, num_sinistre, etc.)
	 * @return array<int, array<string, mixed>> Tableau d'enregistrements prêts pour insertion ij_detail_jour
	 */
	public function generateDetailJourRecords(array $calculationResult, array $inputData): array {
		$records = [];

		// Extraire les données communes
		$adherentNumber = $inputData['adherent_number'] ?? null;
		$numSinistre = $inputData['num_sinistre'] ?? null;

		// Traiter chaque détail de paiement (chaque arrêt)
		if (isset($calculationResult['payment_details']) && is_array($calculationResult['payment_details'])) {
			foreach ($calculationResult['payment_details'] as $detail) {

				// Traiter le détail journalier si disponible
				if (isset($detail['daily_breakdown']) && is_array($detail['daily_breakdown'])) {

					// Grouper les montants journaliers par année et mois
					$monthlyData = $this->groupByYearMonth($detail['daily_breakdown']);

					// Créer un enregistrement pour chaque mois
					foreach ($monthlyData as $yearMonth => $days) {
						[$year, $month] = explode('-', $yearMonth);

						$record = [
							'adherent_number' => $adherentNumber,
							'exercice' => $year,
							'periode' => $month,
							'num_sinistre' => $numSinistre,
						];

						// Ajouter les montants journaliers (j1 à j31)
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
	 * Grouper le détail journalier par année et mois
	 *
	 * @param array<int, array<string, mixed>> $dailyBreakdown Tableau d'entrées journalières avec date et montant
	 * @return array<string, array<int, float>> Groupé par 'YYYY-MM' avec numéro de jour comme clé
	 */
	private function groupByYearMonth(array $dailyBreakdown): array {
		$grouped = [];

		foreach ($dailyBreakdown as $entry) {
			if (!isset($entry['date']) || !isset($entry['amount'])) {
				continue;
			}

			// Analyser la date
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

			// Stocker le montant pour ce jour
			$grouped[$yearMonth][$day] = $entry['amount'];
		}

		return $grouped;
	}

	/**
	 * Convertir un montant décimal en centimes entiers
	 * Ex: 75.06 -> 7506
	 *
	 * @param float $amount Montant en euros
	 * @return int Montant en centimes
	 */
	private function convertToIntCents(float $amount): int {
		return (int)round($amount * 100);
	}

}
