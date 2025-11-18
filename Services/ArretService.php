<?php

namespace App\IJCalculator\Services;

use DateTime;

/**
 * Service for managing arret collections
 * Handles loading, formatting, and validating arret data from various sources
 */
class ArretService
{
    /**
     * Load arrets from a JSON file
     *
     * @param string $filePath Path to JSON file
     * @return array Array of arrets
     * @throws \RuntimeException If file not found or invalid JSON
     */
    public function loadFromJson(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Arrets file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $arrets = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in arrets file: " . json_last_error_msg());
        }

        if (!is_array($arrets)) {
            throw new \RuntimeException("Arrets file must contain an array");
        }

        return $this->normalizeArrets($arrets);
    }

    /**
     * Load arrets from CakePHP entities or database result
     *
     * @param mixed $entities CakePHP entities collection or array of entities
     * @return array Array of arrets in standard format
     */
    public function loadFromEntities($entities): array
    {
        $arrets = [];

        foreach ($entities as $entity) {
            // Convert entity to array (works for CakePHP entities and stdClass)
            if (is_object($entity)) {
                if (method_exists($entity, 'toArray')) {
                    // CakePHP entity
                    $arret = $entity->toArray();
                } else {
                    // stdClass or other object
                    $arret = (array) $entity;
                }
            } else {
                $arret = $entity;
            }

            $arrets[] = $arret;
        }

        return $this->normalizeArrets($arrets);
    }

    /**
     * Normalize arrets data to ensure consistent field names and formats
     *
     * @param array $arrets Array of arrets
     * @return array Normalized arrets
     */
    public function normalizeArrets(array $arrets): array
    {
        $normalized = [];

        foreach ($arrets as $arret) {
            $normalized[] = $this->normalizeArret($arret);
        }

        return $normalized;
    }

    /**
     * Normalize a single arret
     *
     * @param array $arret Arret data
     * @return array Normalized arret
     */
    public function normalizeArret(array $arret): array
    {
        // Map common field variations to standard names
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

        // Apply field mappings
        foreach ($fieldMappings as $from => $to) {
            if (isset($arret[$from]) && !isset($arret[$to])) {
                $normalized[$to] = $arret[$from];
            }
        }

        // Normalize dates (handle DateTime objects)
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

        // Ensure required fields exist
        if (!isset($normalized['arret-from-line']) || !isset($normalized['arret-to-line'])) {
            throw new \RuntimeException("Arret missing required fields: arret-from-line and arret-to-line");
        }

        // Set defaults for optional fields
        $normalized['dt-line'] = $normalized['dt-line'] ?? 0;
        $normalized['rechute-line'] = $normalized['rechute-line'] ?? 0;
        $normalized['ouverture-date-line'] = $normalized['ouverture-date-line'] ?? '';

        return $normalized;
    }

    /**
     * Normalize a date value
     *
     * @param mixed $date Date value (string, DateTime, etc.)
     * @return string|null Normalized date string (Y-m-d format) or null
     */
    private function normalizeDate($date): ?string
    {
        if ($date === null || $date === '' || $date === '0000-00-00') {
            return null;
        }

        if ($date instanceof DateTime || $date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        if (is_string($date)) {
            // Try to parse various date formats
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
     * Validate arret data
     *
     * @param array $arret Arret data
     * @return bool True if valid
     * @throws \InvalidArgumentException If invalid
     */
    public function validateArret(array $arret): bool
    {
        $required = ['arret-from-line', 'arret-to-line'];

        foreach ($required as $field) {
            if (!isset($arret[$field]) || empty($arret[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Validate date format
        try {
            $fromDate = new DateTime($arret['arret-from-line']);
            $toDate = new DateTime($arret['arret-to-line']);

            if ($fromDate > $toDate) {
                throw new \InvalidArgumentException("arret-from-line must be before or equal to arret-to-line");
            }
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid date format: " . $e->getMessage());
        }

        return true;
    }

    /**
     * Validate an array of arrets
     *
     * @param array $arrets Array of arrets
     * @return bool True if all valid
     * @throws \InvalidArgumentException If any invalid
     */
    public function validateArrets(array $arrets): bool
    {
        if (empty($arrets)) {
            throw new \InvalidArgumentException("Arrets array cannot be empty");
        }

        foreach ($arrets as $index => $arret) {
            try {
                $this->validateArret($arret);
            } catch (\InvalidArgumentException $e) {
                throw new \InvalidArgumentException("Invalid arret at index {$index}: " . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Convert arrets to JSON string
     *
     * @param array $arrets Array of arrets
     * @param bool $prettyPrint Whether to format JSON with indentation
     * @return string JSON string
     */
    public function toJson(array $arrets, bool $prettyPrint = false): string
    {
        $options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($prettyPrint) {
            $options |= JSON_PRETTY_PRINT;
        }

        return json_encode($arrets, $options);
    }

    /**
     * Save arrets to JSON file
     *
     * @param array $arrets Array of arrets
     * @param string $filePath Path to save file
     * @param bool $prettyPrint Whether to format JSON with indentation
     * @return bool True on success
     * @throws \RuntimeException If unable to write file
     */
    public function saveToJson(array $arrets, string $filePath, bool $prettyPrint = true): bool
    {
        $json = $this->toJson($arrets, $prettyPrint);

        $result = file_put_contents($filePath, $json);

        if ($result === false) {
            throw new \RuntimeException("Unable to write arrets to file: {$filePath}");
        }

        return true;
    }

    /**
     * Filter arrets by date range
     *
     * @param array $arrets Array of arrets
     * @param string|null $startDate Start date (Y-m-d)
     * @param string|null $endDate End date (Y-m-d)
     * @return array Filtered arrets
     */
    public function filterByDateRange(array $arrets, ?string $startDate = null, ?string $endDate = null): array
    {
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
     * Sort arrets by start date
     *
     * @param array $arrets Array of arrets
     * @param bool $ascending True for ascending (oldest first), false for descending
     * @return array Sorted arrets
     */
    public function sortByDate(array $arrets, bool $ascending = true): array
    {
        usort($arrets, function ($a, $b) use ($ascending) {
            $comparison = strtotime($a['arret-from-line']) - strtotime($b['arret-from-line']);
            return $ascending ? $comparison : -$comparison;
        });

        return $arrets;
    }

    /**
     * Get arrets grouped by num_sinistre
     *
     * @param array $arrets Array of arrets
     * @return array Arrets grouped by num_sinistre
     */
    public function groupBySinistre(array $arrets): array
    {
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
     * Count total days across all arrets
     *
     * @param array $arrets Array of arrets
     * @return int Total days
     */
    public function countTotalDays(array $arrets): int
    {
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
}
