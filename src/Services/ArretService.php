<?php

namespace App\Services;

use DateTime;
use InvalidArgumentException;
use RuntimeException;

/**
 * Service pour gérer les collections d'arrêts
 * Gère le chargement, le formatage et la validation des données d'arrêt depuis diverses sources
 */
class ArretService {

	/**
	 * Charger les arrêts depuis un fichier JSON
	 *
	 * @param string $filePath Chemin vers le fichier JSON
	 * @throws \RuntimeException Si le fichier n'est pas trouvé ou JSON invalide
	 * @return array<int, array<string, mixed>> Tableau d'arrêts
	 */
	public function loadFromJson(string $filePath): array {
		if (!file_exists($filePath)) {
			throw new RuntimeException("Fichier d'arrêts non trouvé: {$filePath}");
		}

		$content = file_get_contents($filePath);
		if ($content === false) {
			throw new RuntimeException('Échec de lecture du fichier d\'arrêts');
		}

		$arrets = json_decode($content, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new RuntimeException('JSON invalide dans le fichier d\'arrêts: ' . json_last_error_msg());
		}

		if (!is_array($arrets)) {
			throw new RuntimeException('Le fichier d\'arrêts doit contenir un tableau');
		}

		return $this->normalizeArrets($arrets);
	}

	/**
	 * Charger les arrêts depuis les entités CakePHP ou résultat de base de données
	 *
	 * @param mixed $entities Collection d'entités CakePHP ou tableau d'entités
	 * @return array<int, array<string, mixed>> Tableau d'arrêts au format standard
	 */
	public function loadFromEntities($entities): array {
		$arrets = [];

		foreach ($entities as $entity) {
			// Convertir l'entité en tableau (fonctionne pour les entités CakePHP et stdClass)
			if (is_object($entity)) {
				if (method_exists($entity, 'toArray')) {
					// Entité CakePHP
					$arret = $entity->toArray();
				} else {
					// stdClass ou autre objet
					$arret = (array)$entity;
				}
			} else {
				$arret = $entity;
			}

			$arrets[] = $arret;
		}

		return $this->normalizeArrets($arrets);
	}

	/**
	 * Normaliser les données d'arrêts pour garantir des noms de champs et formats cohérents
	 *
	 * @param array<int, array<string, mixed>> $arrets Tableau d'arrêts
	 * @return array<int, array<string, mixed>> Arrêts normalisés
	 */
	public function normalizeArrets(array $arrets): array {
		$normalized = [];

		foreach ($arrets as $arret) {
			$normalized[] = $this->normalizeArret($arret);
		}

		return $normalized;
	}

	/**
	 * Normaliser un seul arrêt
	 *
	 * @param array<string, mixed> $arret Données d'arrêt
	 * @return array<string, mixed> Arrêt normalisé
	 */
	public function normalizeArret(array $arret): array {
		// Mapper les variations de champs communes vers les noms standard
		$fieldMappings = [
			'arret_from' => 'arret-from-line',
			'arret_to' => 'arret-to-line',
			'declaration_date' => 'declaration-date-line',
			'dt' => 'dt-line',
			'rechute' => 'rechute-line',
			'ouverture_date' => 'ouverture-date-line',
			'code_patho' => 'code-patho-line',
			'decompte' => 'decompte-line',
		];

		$normalized = $arret;

		// Appliquer les mappages de champs
		foreach ($fieldMappings as $from => $to) {
			if (isset($arret[$from]) && !isset($arret[$to])) {
				$normalized[$to] = $arret[$from];
			}
		}

		// Normaliser les dates (gérer les objets DateTime)
		$dateFields = [
			'arret-from-line',
			'arret-to-line',
			'declaration-date-line',
			'ouverture-date-line',
			'date-deb-dr-force',
			'date-prolongation',
			'created_at',
			'updated_at',
		];

		foreach ($dateFields as $field) {
			if (isset($normalized[$field])) {
				$normalized[$field] = $this->normalizeDate($normalized[$field]);
			}
		}

		// S'assurer que les champs requis existent
		if (!isset($normalized['arret-from-line']) || !isset($normalized['arret-to-line'])) {
			throw new RuntimeException('Arrêt manquant les champs requis: arret-from-line et arret-to-line');
		}

		// Définir les valeurs par défaut pour les champs optionnels
		$normalized['dt-line'] = $normalized['dt-line'] ?? 0;
		$normalized['rechute-line'] = $normalized['rechute-line'] ?? 0;
		$normalized['ouverture-date-line'] = $normalized['ouverture-date-line'] ?? '';

		return $normalized;
	}

	/**
	 * Normaliser une valeur de date
	 *
	 * @param mixed $date Valeur de date (chaîne, DateTime, etc.)
	 * @return string|null Chaîne de date normalisée (format Y-m-d) ou null
	 */
	private function normalizeDate($date): ?string {
		if ($date === null || $date === '' || $date === '0000-00-00') {
			return null;
		}

		if ($date instanceof DateTime || $date instanceof \DateTimeInterface) {
			return $date->format('Y-m-d');
		}

		if (is_string($date)) {
			// Essayer d'analyser divers formats de date
			try {
				$dt = new DateTime($date);

				return $dt->format('Y-m-d');
			} catch (\Exception $e) {
				return null;
			}
		}

		return null;
	}

	/**
	 * Valider les données d'arrêt
	 *
	 * @param array<string, mixed> $arret Données d'arrêt
	 * @throws \InvalidArgumentException Si invalide
	 * @return bool Vrai si valide
	 */
	public function validateArret(array $arret): bool {
		$required = ['arret-from-line', 'arret-to-line'];

		foreach ($required as $field) {
			if (!isset($arret[$field]) || empty($arret[$field])) {
				throw new InvalidArgumentException("Champ requis manquant: {$field}");
			}
		}

		// Valider le format de date
		try {
			$fromDate = new DateTime($arret['arret-from-line']);
			$toDate = new DateTime($arret['arret-to-line']);

			if ($fromDate > $toDate) {
				throw new InvalidArgumentException('arret-from-line doit être avant ou égal à arret-to-line');
			}
		} catch (\Exception $e) {
			throw new InvalidArgumentException('Format de date invalide: ' . $e->getMessage());
		}

		return true;
	}

	/**
	 * Valider un tableau d'arrêts
	 *
	 * @param array<int, array<string, mixed>> $arrets Tableau d'arrêts
	 * @throws \InvalidArgumentException Si un est invalide
	 * @return bool Vrai si tous sont valides
	 */
	public function validateArrets(array $arrets): bool {
		if (empty($arrets)) {
			throw new InvalidArgumentException('Le tableau d\'arrêts ne peut pas être vide');
		}

		foreach ($arrets as $index => $arret) {
			try {
				$this->validateArret($arret);
			} catch (\InvalidArgumentException $e) {
				throw new InvalidArgumentException("Arrêt invalide à l'index {$index}: " . $e->getMessage());
			}
		}

		return true;
	}

	/**
	 * Convertir les arrêts en chaîne JSON
	 *
	 * @param array<int, array<string, mixed>> $arrets Tableau d'arrêts
	 * @param bool $prettyPrint Si formater le JSON avec indentation
	 * @return string Chaîne JSON
	 */
	public function toJson(array $arrets, bool $prettyPrint = false): string {
		$options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
		if ($prettyPrint) {
			$options |= JSON_PRETTY_PRINT;
		}

		$json = json_encode($arrets, $options);
		if ($json === false) {
			throw new \RuntimeException('Échec d\'encodage des arrêts en JSON: ' . json_last_error_msg());
		}

		return $json;
	}

	/**
	 * Sauvegarder les arrêts dans un fichier JSON
	 *
	 * @param array<int, array<string, mixed>> $arrets Tableau d'arrêts
	 * @param string $filePath Chemin pour sauvegarder le fichier
	 * @param bool $prettyPrint Si formater le JSON avec indentation
	 * @throws \RuntimeException Si impossible d'écrire le fichier
	 * @return bool Vrai en cas de succès
	 */
	public function saveToJson(array $arrets, string $filePath, bool $prettyPrint = true): bool {
		$json = $this->toJson($arrets, $prettyPrint);

		$result = file_put_contents($filePath, $json);

		if ($result === false) {
			throw new RuntimeException("Impossible d'écrire les arrêts dans le fichier: {$filePath}");
		}

		return true;
	}

	/**
	 * Filtrer les arrêts par plage de dates
	 *
	 * @param array<int, array<string, mixed>> $arrets Tableau d'arrêts
	 * @param string|null $startDate Date de début (Y-m-d)
	 * @param string|null $endDate Date de fin (Y-m-d)
	 * @return array<int, array<string, mixed>> Arrêts filtrés
	 */
	public function filterByDateRange(array $arrets, ?string $startDate = null, ?string $endDate = null): array {
		return array_filter($arrets, function ($arret) use ($startDate, $endDate) {
			$arretFrom = $arret['arret-from-line'];
			$arretTo = $arret['arret-to-line'];

			if ($startDate && $arretTo < $startDate) {
				return false;
			}

			if ($endDate && $arretFrom > $endDate) {
				return false;
			}

			return true;
		});
	}

	/**
	 * Trier les arrêts par date de début
	 *
	 * @param array<int, array<string, mixed>> $arrets Tableau d'arrêts
	 * @param bool $ascending Vrai pour croissant (plus ancien en premier), faux pour décroissant
	 * @return array<int, array<string, mixed>> Arrêts triés
	 */
	public function sortByDate(array $arrets, bool $ascending = true): array {
		usort($arrets, function ($a, $b) use ($ascending) {
			$comparison = strtotime($a['arret-from-line']) - strtotime($b['arret-from-line']);

			return $ascending ? $comparison : -$comparison;
		});

		return $arrets;
	}

	/**
	 * Obtenir les arrêts groupés par num_sinistre
	 *
	 * @param array<int, array<string, mixed>> $arrets Tableau d'arrêts
	 * @return array<int|string, array<int, array<string, mixed>>> Arrêts groupés par num_sinistre
	 */
	public function groupBySinistre(array $arrets): array {
		$grouped = [];

		foreach ($arrets as $arret) {
			$numSinistre = $arret['num_sinistre'] ?? 'unknown';

			if (!isset($grouped[$numSinistre])) {
				$grouped[$numSinistre] = [];
			}

			$grouped[$numSinistre][] = $arret;
		}

		return $grouped;
	}

	/**
	 * Compter le total de jours sur tous les arrêts
	 *
	 * @param array<int, array<string, mixed>> $arrets Tableau d'arrêts
	 * @return int Total de jours
	 */
	public function countTotalDays(array $arrets): int {
		$totalDays = 0;

		foreach ($arrets as $arret) {
			if (isset($arret['arret_diff'])) {
				$totalDays += $arret['arret_diff'];
			} else {
				$from = new DateTime($arret['arret-from-line']);
				$to = new DateTime($arret['arret-to-line']);
				$totalDays += $from->diff($to)->days + 1;
			}
		}

		return $totalDays;
	}

	/**
	 * Formater les arrêts au format de sortie standard (correspondant à la structure arrets.json)
	 * Mappe les champs enrichis vers les noms de champs originaux
	 *
	 * @param array<int, array<string, mixed>> $arrets Tableau d'arrêts avec champs enrichis
	 * @return array<int, array<string, mixed>> Arrêts formatés
	 */
	public function formatForOutput(array $arrets): array {
		$formatted = [];

		foreach ($arrets as $arret) {
			$output = $arret;

			// Mapper is_rechute vers rechute-line (0 ou 1)
			if (isset($arret['is_rechute'])) {
				$output['rechute-line'] = $arret['is_rechute'] ? 1 : 0;
				// Garder is_rechute pour compatibilité ascendante
			} else {
				// S'assurer que tous les arrêts ont rechute-line
				$output['rechute-line'] = 0;
				$output['is_rechute'] = false;
			}

			// Mapper decompte_days vers decompte-line
			if (isset($arret['decompte_days'])) {
				$output['decompte-line'] = $arret['decompte_days'];
				// Garder decompte_days pour compatibilité ascendante
			}

			// S'assurer que ouverture-date-line est défini depuis date-effet
			if (isset($arret['date-effet'])) {
				$output['ouverture-date-line'] = $arret['date-effet'];
			}

			$formatted[] = $output;
		}

		return $formatted;
	}

	/**
	 * Formater un seul arrêt au format de sortie standard
	 *
	 * @param array<string, mixed> $arret Arrêt avec champs enrichis
	 * @return array<string, mixed> Arrêt formaté
	 */
	public function formatArretForOutput(array $arret): array {
		return $this->formatForOutput([$arret])[0];
	}

}
