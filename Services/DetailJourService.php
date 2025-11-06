<?php

namespace IJCalculator\Services;

/**
 * Service to generate ij_detail_jour records from calculation results
 * Maps daily_breakdown to ij_detail_jour table format with j1-j31 columns
 */
class DetailJourService
{
    /**
     * Generate ij_detail_jour records from calculation results
     *
     * @param array $calculationResult Result from IJCalculator::calculateAmount()
     * @param array $inputData Original input data (for adherent_number, num_sinistre, etc.)
     * @return array Array of records ready for ij_detail_jour insertion
     */
    public function generateDetailJourRecords(array $calculationResult, array $inputData): array
    {
        $records = [];

        // Extract common data
        $adherentNumber = $inputData['adherent_number'] ?? null;
        $numSinistre = $inputData['num_sinistre'] ?? null;

        // Process each payment detail (each arret)
        if (isset($calculationResult['payment_details']) && is_array($calculationResult['payment_details'])) {
            foreach ($calculationResult['payment_details'] as $detail) {

                // Process daily breakdown if available
                if (isset($detail['daily_breakdown']) && is_array($detail['daily_breakdown'])) {

                    // Group daily amounts by year and month
                    $monthlyData = $this->groupByYearMonth($detail['daily_breakdown']);

                    // Create a record for each month
                    foreach ($monthlyData as $yearMonth => $days) {
                        list($year, $month) = explode('-', $yearMonth);

                        $record = [
                            'adherent_number' => $adherentNumber,
                            'exercice' => $year,
                            'periode' => $month,
                            'num_sinistre' => $numSinistre,
                        ];

                        // Add daily amounts (j1 to j31)
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
     * Group daily breakdown by year and month
     *
     * @param array $dailyBreakdown Array of daily entries with date and amount
     * @return array Grouped by 'YYYY-MM' with day number as key
     */
    private function groupByYearMonth(array $dailyBreakdown): array
    {
        $grouped = [];

        foreach ($dailyBreakdown as $entry) {
            if (!isset($entry['date']) || !isset($entry['amount'])) {
                continue;
            }

            // Parse date
            $date = $entry['date'];
            $dateObj = \DateTime::createFromFormat('Y-m-d', $date);

            if (!$dateObj) {
                continue;
            }

            $year = $dateObj->format('Y');
            $month = $dateObj->format('m');
            $day = (int) $dateObj->format('d');

            $yearMonth = "$year-$month";

            if (!isset($grouped[$yearMonth])) {
                $grouped[$yearMonth] = [];
            }

            // Store amount for this day
            $grouped[$yearMonth][$day] = $entry['amount'];
        }

        return $grouped;
    }

    /**
     * Convert decimal amount to integer cents
     * E.g., 75.06 -> 7506
     *
     * @param float $amount Amount in euros
     * @return int Amount in cents
     */
    private function convertToIntCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Generate SQL INSERT statement for a single record
     *
     * @param array $record Record data
     * @return string SQL INSERT statement
     */
    public function generateInsertSQL(array $record): string
    {
        $columns = [
            'adherent_number',
            'exercice',
            'periode',
            'num_sinistre'
        ];

        // Add day columns
        for ($i = 1; $i <= 31; $i++) {
            $columns[] = "j$i";
        }

        $values = [];
        foreach ($columns as $col) {
            $value = $record[$col] ?? null;

            if ($value === null) {
                $values[] = 'NULL';
            } elseif (is_string($value)) {
                $values[] = "'" . addslashes($value) . "'";
            } else {
                $values[] = $value;
            }
        }

        $sql = "INSERT INTO ij_detail_jour (" . implode(', ', $columns) . ")\n";
        $sql .= "VALUES (" . implode(', ', $values) . ");";

        return $sql;
    }

    /**
     * Generate SQL INSERT statements for all records
     *
     * @param array $records Array of record data
     * @return string SQL INSERT statements
     */
    public function generateBatchInsertSQL(array $records): string
    {
        $sql = '';
        foreach ($records as $record) {
            $sql .= $this->generateInsertSQL($record) . "\n";
        }
        return $sql;
    }

    /**
     * Format detail_jour records for display (HTML table)
     *
     * @param array $records Array of record data
     * @return string HTML table
     */
    public function formatDetailJourHTML(array $records): string
    {
        if (empty($records)) {
            return '<p>Aucun enregistrement de détail journalier.</p>';
        }

        $html = '<table style="width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px;">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #667eea; color: white;">';
        $html .= '<th style="padding: 8px; border: 1px solid #ddd;">Adhérent</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #ddd;">Exercice</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #ddd;">Période</th>';

        // Add day columns (j1-j31)
        for ($i = 1; $i <= 31; $i++) {
            $html .= '<th style="padding: 4px; border: 1px solid #ddd; font-size: 10px;">J' . $i . '</th>';
        }

        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($records as $index => $record) {
            $bgColor = $index % 2 === 0 ? '#f8f9fa' : 'white';
            $html .= '<tr style="background-color: ' . $bgColor . ';"><td style="padding: 8px; border: 1px solid #ddd;">' . ($record['adherent_number'] ?? '-') . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . ($record['exercice'] ?? '-') . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . ($record['periode'] ?? '-') . '</td>';

            // Display daily amounts
            for ($i = 1; $i <= 31; $i++) {
                $value = $record["j$i"] ?? null;

                if ($value === null) {
                    $html .= '<td style="padding: 2px; border: 1px solid #ddd; text-align: center; color: #ccc;">-</td>';
                } else {
                    $amount = $value / 100;
                    $html .= '<td style="padding: 2px; border: 1px solid #ddd; text-align: right; font-size: 10px;">' . number_format($amount, 2, ',', '') . '</td>';
                }
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    /**
     * Validate a detail_jour record before insertion
     *
     * @param array $record Record to validate
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public function validateRecord(array $record): array
    {
        $errors = [];

        // Required fields
        $requiredFields = [
            'adherent_number' => 'Numéro adhérent',
            'num_sinistre' => 'Numéro sinistre',
            'exercice' => 'Exercice',
            'periode' => 'Période'
        ];

        foreach ($requiredFields as $field => $label) {
            if (!isset($record[$field]) || $record[$field] === null) {
                $errors[] = "$label est requis";
            }
        }

        // Validate adherent_number format (7 characters)
        if (isset($record['adherent_number'])) {
            $adherentNumber = $record['adherent_number'];
            if (strlen($adherentNumber) !== 7) {
                $errors[] = "Numéro adhérent doit faire 7 caractères (actuel: $adherentNumber)";
            }
        }

        // Validate exercice format (4 characters, year)
        if (isset($record['exercice'])) {
            $exercice = $record['exercice'];
            if (strlen($exercice) !== 4 || !is_numeric($exercice)) {
                $errors[] = "Exercice doit être une année sur 4 chiffres (actuel: $exercice)";
            }
        }

        // Validate periode format (1-12 for months)
        if (isset($record['periode'])) {
            $periode = (int) $record['periode'];
            if ($periode < 1 || $periode > 12) {
                $errors[] = "Période doit être entre 1 et 12 (actuel: $periode)";
            }
        }

        // Check that at least one day has a value
        $hasValue = false;
        for ($i = 1; $i <= 31; $i++) {
            if (isset($record["j$i"]) && $record["j$i"] !== null) {
                $hasValue = true;
                break;
            }
        }

        if (!$hasValue) {
            $errors[] = "Au moins un jour doit avoir une valeur";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get summary statistics for detail_jour records
     *
     * @param array $records Array of record data
     * @return array Statistics
     */
    public function getStatistics(array $records): array
    {
        $stats = [
            'total_months' => count($records),
            'total_days' => 0,
            'total_amount' => 0,
            'months' => []
        ];

        foreach ($records as $record) {
            $monthKey = $record['exercice'] . '-' . str_pad($record['periode'], 2, '0', STR_PAD_LEFT);
            $monthDays = 0;
            $monthAmount = 0;

            for ($i = 1; $i <= 31; $i++) {
                if (isset($record["j$i"]) && $record["j$i"] !== null) {
                    $monthDays++;
                    $monthAmount += $record["j$i"];
                }
            }

            $stats['total_days'] += $monthDays;
            $stats['total_amount'] += $monthAmount;

            $stats['months'][$monthKey] = [
                'days' => $monthDays,
                'amount' => $monthAmount / 100 // Convert to euros
            ];
        }

        $stats['total_amount'] = $stats['total_amount'] / 100; // Convert to euros

        return $stats;
    }
}
