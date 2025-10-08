<?php

namespace IJCalculator\Services;

/**
 * Rate Service Implementation
 * Handles all rate lookups and calculations
 */
class RateService implements RateServiceInterface
{
    private array $rates = [];
    private ?float $passValue = null;

    public function __construct(string $csvPath = 'taux.csv')
    {
        $this->loadRates($csvPath);
    }

    public function setPassValue(float $value): void
    {
        $this->passValue = $value;
    }

    private function loadRates(string $csvPath): void
    {
        if (!file_exists($csvPath)) {
            throw new \RuntimeException("CSV file not found: {$csvPath}");
        }

        $file = fopen($csvPath, 'r');
        $header = fgetcsv($file, 0, ';');

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            $rateData = array_combine($header, $row);
            $this->rates[] = $rateData;
        }

        fclose($file);
    }

    public function getRateForYear(int $year): ?array
    {
        foreach ($this->rates as $rate) {
            $startYear = (int) date('Y', strtotime($rate['date_start']));
            $endYear = (int) date('Y', strtotime($rate['date_end']));

            if ($year >= $startYear && $year <= $endYear) {
                return $rate;
            }
        }

        return null;
    }

    public function getRateForDate(string $date): ?array
    {
        $dateTimestamp = strtotime($date);

        foreach ($this->rates as $rate) {
            $startTimestamp = strtotime($rate['date_start']);
            $endTimestamp = strtotime($rate['date_end']);

            if ($dateTimestamp >= $startTimestamp && $dateTimestamp <= $endTimestamp) {
                return $rate;
            }
        }

        return null;
    }

    public function getDailyRate(
        string $statut,
        string $classe,
        string|int $option,
        int $taux,
        int $year,
        ?string $date = null,
        ?int $age = null,
        ?bool $usePeriode2 = null
    ): float {
        // Get rate data
        if ($date) {
            $rateData = $this->getRateForDate($date);
        } else {
            $rateData = $this->getRateForYear($year);
        }

        if (!$rateData) {
            return 0.0;
        }

        $classeKey = strtolower($classe);

        // Determine tier based on taux number
        $tier = $this->determineTier($taux, $age, $usePeriode2);

        $columnKey = "taux_{$classeKey}{$tier}";
        $baseRate = isset($rateData[$columnKey]) ? (float) $rateData[$columnKey] : 0.0;

        // Apply option multiplier for CCPL and RSPM
        if (in_array(strtoupper($statut), ['CCPL', 'RSPM'])) {
            $optionValue = (float) str_replace(',', '.', (string) $option);

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
     * Determine CSV tier based on taux number
     *
     * @param int $taux Taux number (1-9)
     * @param int|null $age Age for special handling
     * @param bool|null $usePeriode2 Whether period 2 exists
     * @return int Tier number (1, 2, or 3)
     */
    private function determineTier(int $taux, ?int $age, ?bool $usePeriode2): int
    {
        //  Taux 1-3: Period 1 → tier 1 (full rate)

        if ($taux >= 1 && $taux <= 3) {

            return 1;
            
        }

        // Taux 7-9: Period 2 (366-730 days) → tier 3 (intermediate rate)
        if ($taux >= 7 && $taux <= 9) {
            return 3;
        }

        // Taux 4-6: Period 3
        if ($taux >= 4 && $taux <= 6) {
            // For 70+ years old: always tier 2 (reduced rate)
            if ($age !== null && $age >= 70) {
                return 2;
            }

            // For 62-69 years old: depends on usePeriode2
            // - If usePeriode2=true (arrêt >= 730j): period 3 starts at 731j → tier 2
            // - If usePeriode2=false (arrêt < 730j): period 3 starts at 366j → tier 3
            if ($usePeriode2 === true) {
                return 2;
            }


            return 3;
        }

        return 1; // default
    }
}
