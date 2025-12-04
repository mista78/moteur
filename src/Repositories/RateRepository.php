<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\IjTaux;
use RuntimeException;

/**
 * Repository for loading and managing rate data
 * Now uses database (ij_taux table) instead of CSV
 */
class RateRepository
{
    private string $csvPath;

    /**
     * @param string $csvPath Legacy CSV path (kept for backward compatibility)
     */
    public function __construct(string $csvPath)
    {
        $this->csvPath = $csvPath;
    }

    /**
     * Load rates from database (primary method)
     * Returns rates in the same format as CSV for compatibility
     *
     * @return array<int, array<string, mixed>> Array of rate records
     */
    public function loadRates(): array
    {
        try {
            $rates = IjTaux::getAllRatesOrdered();

            $ratesArray = [];
            foreach ($rates as $rate) {
                $ratesArray[] = [
                    'date_start' => $rate->date_start,
                    'date_end' => $rate->date_end,
                    'taux_a1' => $rate->taux_a1,
                    'taux_a2' => $rate->taux_a2,
                    'taux_a3' => $rate->taux_a3,
                    'taux_b1' => $rate->taux_b1,
                    'taux_b2' => $rate->taux_b2,
                    'taux_b3' => $rate->taux_b3,
                    'taux_c1' => $rate->taux_c1,
                    'taux_c2' => $rate->taux_c2,
                    'taux_c3' => $rate->taux_c3,
                ];
            }

            return $ratesArray;
        } catch (\Exception $e) {
            // Fallback to CSV if database fails
            return $this->loadRatesFromCsv();
        }
    }

    /**
     * Load rates from CSV file (fallback/legacy method)
     *
     * @return array<int, array<string, string>> Array of rate records
     * @throws RuntimeException If CSV file cannot be read
     */
    private function loadRatesFromCsv(): array
    {
        $rates = [];

        if (!file_exists($this->csvPath)) {
            throw new RuntimeException("CSV file not found: {$this->csvPath}");
        }

        $file = fopen($this->csvPath, 'r');
        if ($file === false) {
            throw new RuntimeException("Unable to open CSV file: {$this->csvPath}");
        }

        $headers = fgetcsv($file, 0, ';');
        if ($headers === false) {
            fclose($file);
            throw new RuntimeException("Empty or invalid CSV file");
        }

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            $rate = [];
            foreach ($headers as $index => $header) {
                $rate[$header] = $row[$index] ?? '';
            }
            $rates[] = $rate;
        }

        fclose($file);

        return $rates;
    }

    /**
     * Get the CSV file path
     *
     * @return string
     */
    public function getCsvPath(): string
    {
        return $this->csvPath;
    }

    /**
     * Check if CSV file exists
     *
     * @return bool
     */
    public function fileExists(): bool
    {
        return file_exists($this->csvPath);
    }
}
