<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PlafondSecuSociale;

/**
 * Repository for loading and managing PASS (Plafond Annuel Sécurité Sociale) data
 */
class PassRepository
{
    /**
     * Load PASS values from database, indexed by year
     *
     * Returns array like: [2024 => 46368, 2023 => 43992, ...]
     *
     * @return array<int, int>
     */
    public function loadPassValuesByYear(): array
    {
        try {
            return PlafondSecuSociale::getPassValuesByYear();
        } catch (\Exception $e) {
            // If database fails, return empty array or default values
            return $this->getDefaultPassValues();
        }
    }

    /**
     * Get PASS value for a specific year
     *
     * @param int $year
     * @return int|null
     */
    public function getPassForYear(int $year): ?int
    {
        try {
            return PlafondSecuSociale::getPassForYear($year);
        } catch (\Exception $e) {
            // Fallback to default
            $defaults = $this->getDefaultPassValues();
            return $defaults[$year] ?? null;
        }
    }

    /**
     * Get PASS value for a specific date
     *
     * @param string $date Date in Y-m-d format
     * @return int|null
     */
    public function getPassForDate(string $date): ?int
    {
        try {
            return PlafondSecuSociale::getPassForDate($date);
        } catch (\Exception $e) {
            // Extract year and use fallback
            $year = (int) date('Y', strtotime($date));
            return $this->getPassForYear($year);
        }
    }

    /**
     * Get latest PASS value
     *
     * @return int
     */
    public function getLatestPass(): int
    {
        try {
            return PlafondSecuSociale::getLatestPass() ?? 46368; // Default 2024 value
        } catch (\Exception $e) {
            return 46368; // Default fallback
        }
    }

    /**
     * Default PASS values (fallback if database unavailable)
     *
     * @return array<int, int>
     */
    private function getDefaultPassValues(): array
    {
        return [
            2024 => 46368,
            2023 => 43992,
            2022 => 41136,
            2021 => 41136,
            2020 => 41136,
            2019 => 40524,
            2018 => 39732,
            2017 => 39228,
            2016 => 38616,
            2015 => 38040,
        ];
    }
}
