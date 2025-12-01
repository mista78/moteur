<?php

namespace App\Services;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Date Normalizer Utility
 *
 * Handles date normalization from multiple sources:
 * - Database ORM entities (DateTime objects)
 * - JSON API strings (various formats)
 * - Mock JSON files (ISO strings)
 *
 * Ensures all dates are consistently formatted as 'Y-m-d' strings for IJCalculator
 */
class DateNormalizer {

	/**
	 * List of date fields that should be normalized
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
	 * Normalize all dates in input data
	 * Recursively processes arrays and converts DateTime objects to strings
	 *
	 * @param mixed $data Input data (array, DateTime, string, or other)
	 * @return mixed Normalized data with all dates as 'Y-m-d' strings
	 */
	public static function normalize($data) {
		// Handle null
		if ($data === null) {
			return null;
		}

		// Handle DateTime objects
		if ($data instanceof DateTimeInterface) {
			return $data->format('Y-m-d');
		}

		// Handle arrays (recursively)
		if (is_array($data)) {
			$normalized = [];
			foreach ($data as $key => $value) {
				// Check if this is a known date field
				if (in_array($key, static::DATE_FIELDS)) {
					$normalized[$key] = static::normalizeDate($value);
				} else {
					// Recursively normalize nested arrays
					$normalized[$key] = static::normalize($value);
				}
			}

			return $normalized;
		}

		// Handle objects (ORM entities)
		if (is_object($data)) {
			// Convert object to array and normalize
			$array = static::objectToArray($data);

			return static::normalize($array);
		}

		// Return primitive values as-is
		return $data;
	}

	/**
	 * Normalize a single date value
	 * Handles various input formats and converts to 'Y-m-d' string
	 *
	 * @param mixed $value Date value (DateTime, string, or null)
	 * @return string|null Normalized date string or null
	 */
	private static function normalizeDate($value): ?string {
		// Handle null or empty string
		if ($value === null || $value === '' || $value === '0000-00-00') {
			return null;
		}

		// Handle DateTime objects
		if ($value instanceof DateTimeInterface) {
			return $value->format('Y-m-d');
		}

		// Handle string dates
		if (is_string($value)) {

			// Already in correct format (Y-m-d)
			if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
				// Validate the date is actually valid
				$parts = explode('-', $value);
				if (checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
					return $value;
				}
			}

			// Try common date formats
			$formats = [
				'Y-m-d', // 2024-01-15 (ISO)
				'd/m/Y', // 15/01/2024 (European)
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
					// Additional validation to ensure the parsed date matches the input
					// (prevents dates like 2024-13-45 from being accepted)
					if ($date->format($format) === $value) {
						return $date->format('Y-m-d');
					}
				}
			}

			// Last resort: try to parse with DateTime constructor
			try {
				$date = new DateTime($value);

				return $date->format('Y-m-d');
			} catch (\Exception $e) {
				// Invalid date, return null
				error_log("DateNormalizer: Failed to parse date '{$value}': " . $e->getMessage());

				return null;
			}
		} else {
			return $value->format('Y-m-d');
		}

		return $value->format('Y-m-d');
	}

	/**
	 * Convert an object to an associative array
	 * Handles ORM entities with getters
	 *
	 * @param object $object Object to convert
	 * @return array<string, mixed> Associative array representation
	 */
	private static function objectToArray($object): array {
		$array = [];

		// Try get_object_vars first (for public properties)
		$vars = get_object_vars($object);
		if (!empty($vars)) {
			foreach ($vars as $key => $value) {
				$array[$key] = $value;
			}
		}

		// Try common ORM methods (toArray, getArrayCopy, etc.)
		if (method_exists($object, 'toArray')) {
			return $object->toArray();
		}

		if (method_exists($object, 'getArrayCopy')) {
			return $object->getArrayCopy();
		}

		// Try to use reflection to access private/protected properties
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
			error_log('DateNormalizer: Failed to convert object to array: ' . $e->getMessage());
		}

		return $array;
	}

	/**
	 * Validate and normalize a date string to Y-m-d format
	 *
	 * @param string|null $date Date string to validate
	 * @param bool $allowNull Whether null values are allowed
	 * @throws \InvalidArgumentException If date is invalid and null not allowed
	 * @return string|null Normalized date or null
	 */
	public static function validateDate(?string $date, bool $allowNull = true): ?string {
		if ($date === null || $date === '' || $date === '0000-00-00') {
			if ($allowNull) {
				return null;
			}

			throw new InvalidArgumentException('Date cannot be null');
		}

		$normalized = static::normalizeDate($date);
		if ($normalized === null && !$allowNull) {
			throw new InvalidArgumentException("Invalid date format: {$date}");
		}

		return $normalized;
	}

	/**
	 * Normalize dates in arrets array specifically
	 * Handles both single arret and array of arrets
	 *
	 * @param array<int|string, mixed>|null $arrets Arrets data
	 * @return array<int|string, mixed>|null Normalized arrets
	 */
	public static function normalizeArrets(?array $arrets): ?array {
		if ($arrets === null) {
			return null;
		}

		// Check if this is a single arret or array of arrets
		// Single arret will have 'arret-from-line' key
		if (isset($arrets['arret-from-line'])) {
			// Single arret
			return static::normalize($arrets);
		}

		// Array of arrets
		$normalized = [];
		foreach ($arrets as $index => $arret) {
			$normalized[$index] = static::normalize($arret);
		}

		return $normalized;
	}

}
