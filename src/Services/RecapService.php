<?php

namespace App\Services;

use DateTime;

/**
 * Service pour générer les enregistrements ij_recap depuis les résultats de calcul
 * Mappe la sortie du calculateur au format de la table ij_recap
 */
class RecapService {

	/** @var mixed */
	private $calculator;

	/**
	 * Définir l'instance du calculateur pour la détermination de classe
	 *
	 * @param mixed $calculator Instance IJCalculator
	 * @return void
	 */
	public function setCalculator($calculator): void {
		$this->calculator = $calculator;
	}

	/**
	 * Générer les enregistrements ij_recap depuis les résultats de calcul
	 *
	 * @param array<string, mixed> $calculationResult Résultat de IJCalculator::calculateAmount()
	 * @param array<string, mixed> $inputData Données d'entrée originales (pour adherent_number, num_sinistre, etc.)
	 * @return array<int, array<string, mixed>> Tableau d'enregistrements prêts pour insertion ij_recap
	 */
	public function generateRecapRecords(array $calculationResult, array $inputData): array {
		$records = [];

		// Extraire les données communes depuis l'entrée
		$adherentNumber = $inputData['adherent_number'] ?? null;
		$numSinistre = $inputData['num_sinistre'] ?? null;
		$age = $calculationResult['age'] ?? null;
		$nbTrimestres = $calculationResult['nb_trimestres'] ?? $inputData['nb_trimestres'] ?? null;

		// Traiter chaque détail de paiement (chaque arrêt)
		if (isset($calculationResult['payment_details']) && is_array($calculationResult['payment_details'])) {
			foreach ($calculationResult['payment_details'] as $k => $detail) {
				$idArret = $inputData['arrets'][$k]['id'] ?? $detail['arret_index'] ?? null;

				// Traiter chaque période de détail de taux
				if (isset($detail['rate_breakdown']) && is_array($detail['rate_breakdown'])) {
					foreach ($detail['rate_breakdown'] as $rateBreakdown) {
						// Obtenir la classe depuis rate_breakdown (déterminée par année dans AmountCalculationService)
						$breakdownClasse = $rateBreakdown['classe'] ?? 'A';

						// Obtenir le revenu pour ce rate_breakdown spécifique
						$revenuRef = null;
						if (isset($rateBreakdown['revenu_medecin'])) {
							$revenuRef = (int)$rateBreakdown['revenu_medecin'];
						}

						// Diviser rate_breakdown par mois
						$monthlyBreakdowns = $this->splitByMonth($rateBreakdown);

						foreach ($monthlyBreakdowns as $monthly) {
							$record = [
								// Identification primaire
								'adherent_number' => $adherentNumber,
								'num_sinistre' => $numSinistre,
								'id_arret' => $idArret,

								// Information de période
								'exercice' => $monthly['year'],
								'periode' => $monthly['month'], // Mois (01-12), pas période (1-3)
								'date_start' => $monthly['start'],
								'date_end' => $monthly['end'],

								// Information de taux et montant
								'num_taux' => $rateBreakdown['taux'] ?? null,
								'MT_journalier' => $this->convertToIntCents($rateBreakdown['rate'] ?? 0),

								// Information financière - Utiliser la classe depuis rate_breakdown
								'MT_revenu_ref' => $revenuRef,
								'classe' => $breakdownClasse,

								// Information personnelle
								'personne_age' => $rateBreakdown['age'] ?? $age,
								'nb_trimestre' => $rateBreakdown['nb_trimestres'] ?? $nbTrimestres,
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
	 * Diviser une entrée rate_breakdown par mois
	 *
	 * @param array<string, mixed> $rateBreakdown Entrée de détail de taux avec dates début/fin
	 * @return array<int, array<string, mixed>> Tableau de détails mensuels
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

			// Calculer les limites du mois
			$monthStart = max($startDate, new DateTime($year . '-' . $month . '-01'));
			$monthEnd = min($endDate, new DateTime($currentDate->format('Y-m-t'))); // Dernier jour du mois

			// Calculer les jours dans ce mois
			$days = $monthStart->diff($monthEnd)->days + 1;

			$monthlyBreakdowns[] = [
				'year' => $year,
				'month' => $month,
				'start' => $monthStart->format('Y-m-d'),
				'end' => $monthEnd->format('Y-m-d'),
				'days' => $days,
			];

			// Passer au mois suivant
			$currentDate->modify('first day of next month');
		}

		return $monthlyBreakdowns;
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
