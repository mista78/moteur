<?php

declare(strict_types=1);

namespace App\Repositories;

use RuntimeException;

/**
 * Repository for loading and managing rate data from CSV
 */
class RateRepository
{
    private string $csvPath;

    public function __construct(string $csvPath)
    {
        $this->csvPath = $csvPath;
    }

    /**
     * Load rates from CSV file
     *
     * @return array<int, array<string, string>> Array of rate records
     * @throws RuntimeException If CSV file cannot be read
     */
    public function loadRates(): array
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
