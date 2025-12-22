<?php

namespace App\Services;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Utilitaire de Normalisation de Dates
 *
 * Gère la normalisation des dates depuis plusieurs sources:
 * - Entités ORM de base de données (objets DateTime)
 * - Chaînes API JSON (divers formats)
 * - Fichiers JSON mock (chaînes ISO)
 *
 * Garantit que toutes les dates sont formatées de manière cohérente en chaînes 'Y-m-d' pour IJCalculator
 */
class DateNormalizer {

	/**
	 * Liste des champs de date qui doivent être normalisés
	 * @var array<int, string>
	 */
	private const DATE_FIELDS = [
		'birth_date',
		'current_date',
		'attestation_date',
		'last_payment_date',
		'affiliation_date',
		'first_pathology_stop_date',
		'date_ouverture_droits',
		'arret-from-line',
		'arret-to-line',
		'declaration-date-line',
		'attestation-date-line',
		'date-effet',
		'date-effet-forced',
		'date_deb_droit',
		'date_deb_dr_force',
		'date_fin_paiem_force',
		'date_naissance',
		'date_declaration',
		'date_maj_compte',
		'ouverture-date-line',
		'payment_start',
		'payment_end',
		'date_start',
		'date_end',
	];

	/**
	 * Normaliser toutes les dates dans les données d'entrée
	 * Traite récursivement les tableaux et convertit les objets DateTime en chaînes
	 *
	 * @param mixed $data Données d'entrée (tableau, DateTime, chaîne, ou autre)
	 * @return mixed Données normalisées avec toutes les dates en chaînes 'Y-m-d'
	 */
	public static function normalize($data) {
		// Gérer null
		if ($data === null) {
			return null;
		}

		// Gérer les objets DateTime
		if ($data instanceof DateTimeInterface) {
			return $data->format('Y-m-d');
		}

		// Gérer les tableaux (récursivement)
		if (is_array($data)) {
			$normalized = [];
			foreach ($data as $key => $value) {
				// Vérifier si c'est un champ de date connu
				if (in_array($key, static::DATE_FIELDS)) {
					$normalized[$key] = static::normalizeDate($value);
				} else {
					// Normaliser récursivement les tableaux imbriqués
					$normalized[$key] = static::normalize($value);
				}
			}

			return $normalized;
		}

		// Gérer les objets (entités ORM)
		if (is_object($data)) {
			// Convertir l'objet en tableau et normaliser
			$array = static::objectToArray($data);

			return static::normalize($array);
		}

		// Retourner les valeurs primitives telles quelles
		return $data;
	}

	/**
	 * Normaliser une valeur de date unique
	 * Gère divers formats d'entrée et convertit en chaîne 'Y-m-d'
	 *
	 * @param mixed $value Valeur de date (DateTime, chaîne, ou null)
	 * @return string|null Chaîne de date normalisée ou null
	 */
	private static function normalizeDate($value): ?string {
		// Gérer null ou chaîne vide
		if ($value === null || $value === '' || $value === '0000-00-00') {
			return null;
		}

		// Gérer les objets DateTime
		if ($value instanceof DateTimeInterface) {
			return $value->format('Y-m-d');
		}

		// Gérer les dates en chaîne
		if (is_string($value)) {

			// Déjà au bon format (Y-m-d)
			if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
				// Valider que la date est réellement valide
				$parts = explode('-', $value);
				if (checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
					return $value;
				}
			}

			// Essayer les formats de date courants
			$formats = [
				'Y-m-d', // 2024-01-15 (ISO)
				'd/m/Y', // 15/01/2024 (Européen)
				'm/d/Y', // 01/15/2024 (US)
				'Y/m/d', // 2024/01/15
				'd-m-Y', // 15-01-2024
				'm-d-Y', // 01-15-2024
				'Y.m.d', // 2024.01.15
				'd.m.Y', // 15.01.2024
			];

			foreach ($formats as $format) {
				$date = DateTime::createFromFormat($format, $value);
				if ($date !== false) {
					// Validation additionnelle pour s'assurer que la date analysée correspond à l'entrée
					// (empêche les dates comme 2024-13-45 d'être acceptées)
					if ($date->format($format) === $value) {
						return $date->format('Y-m-d');
					}
				}
			}

			// Dernier recours: essayer d'analyser avec le constructeur DateTime
			try {
				$date = new DateTime($value);

				return $date->format('Y-m-d');
			} catch (\Exception $e) {
				// Date invalide, retourner null
				error_log("DateNormalizer: Échec de l'analyse de la date '{$value}': " . $e->getMessage());

				return null;
			}
		} else {
			return $value->format('Y-m-d');
		}

		return $value->format('Y-m-d');
	}

	/**
	 * Convertir un objet en tableau associatif
	 * Gère les entités ORM avec getters
	 *
	 * @param object $object Objet à convertir
	 * @return array<string, mixed> Représentation en tableau associatif
	 */
	private static function objectToArray($object): array {
		$array = [];

		// Essayer get_object_vars d'abord (pour les propriétés publiques)
		$vars = get_object_vars($object);
		if (!empty($vars)) {
			foreach ($vars as $key => $value) {
				$array[$key] = $value;
			}
		}

		// Essayer les méthodes ORM courantes (toArray, getArrayCopy, etc.)
		if (method_exists($object, 'toArray')) {
			return $object->toArray();
		}

		if (method_exists($object, 'getArrayCopy')) {
			return $object->getArrayCopy();
		}

		// Essayer d'utiliser la réflexion pour accéder aux propriétés privées/protégées
		try {
			$reflection = new ReflectionClass($object);
			$properties = $reflection->getProperties();

			foreach ($properties as $property) {
				$property->setAccessible(true);
				$key = $property->getName();
				$value = $property->getValue($object);
				$array[$key] = $value;
			}
		} catch (\Exception $e) {
			error_log('DateNormalizer: Échec de conversion de l\'objet en tableau: ' . $e->getMessage());
		}

		return $array;
	}

	/**
	 * Valider et normaliser une chaîne de date au format Y-m-d
	 *
	 * @param string|null $date Chaîne de date à valider
	 * @param bool $allowNull Si les valeurs null sont autorisées
	 * @throws \InvalidArgumentException Si la date est invalide et null non autorisé
	 * @return string|null Date normalisée ou null
	 */
	public static function validateDate(?string $date, bool $allowNull = true): ?string {
		if ($date === null || $date === '' || $date === '0000-00-00') {
			if ($allowNull) {
				return null;
			}

			throw new InvalidArgumentException('La date ne peut pas être null');
		}

		$normalized = static::normalizeDate($date);
		if ($normalized === null && !$allowNull) {
			throw new InvalidArgumentException("Format de date invalide: {$date}");
		}

		return $normalized;
	}

	/**
	 * Normaliser les dates dans le tableau arrets spécifiquement
	 * Gère à la fois un seul arrêt et un tableau d'arrêts
	 *
	 * @param array<int|string, mixed>|null $arrets Données des arrêts
	 * @return array<int|string, mixed>|null Arrêts normalisés
	 */
	public static function normalizeArrets(?array $arrets): ?array {
		if ($arrets === null) {
			return null;
		}

		// Vérifier si c'est un seul arrêt ou un tableau d'arrêts
		// Un seul arrêt aura la clé 'arret-from-line'
		if (isset($arrets['arret-from-line'])) {
			// Seul arrêt
			return static::normalize($arrets);
		}

		// Tableau d'arrêts
		$normalized = [];
		foreach ($arrets as $index => $arret) {
			$normalized[$index] = static::normalize($arret);
		}

		return $normalized;
	}

}
