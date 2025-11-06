<?php

namespace IJCalculator\Services;

/**
 * Service to generate ij_recap records from calculation results
 * Maps calculator output to ij_recap table format
 */
class RecapService
{
    /**
     * Generate ij_recap records from calculation results
     *
     * @param array $calculationResult Result from IJCalculator::calculateAmount()
     * @param array $inputData Original input data (for adherent_number, num_sinistre, etc.)
     * @return array Array of records ready for ij_recap insertion
     */
    public function generateRecapRecords(array $calculationResult, array $inputData): array
    {
        $records = [];

        // Extract common data from input
        $adherentNumber = $inputData['adherent_number'] ?? null;
        $numSinistre = $inputData['num_sinistre'] ?? null;
        $classe = $inputData['classe'] ?? null;
        $age = $calculationResult['age'] ?? null;
        $nbTrimestres = $calculationResult['nb_trimestres'] ?? $inputData['nb_trimestres'] ?? null;

        // Get revenue reference if available
        $revenuRef = null;
        if (isset($inputData['revenu_n_moins_2'])) {
            $revenuRef = (int) $inputData['revenu_n_moins_2'];
        } elseif (isset($inputData['pass_value']) && $classe === 'A') {
            $revenuRef = (int) $inputData['pass_value'];
        } elseif (isset($inputData['pass_value']) && $classe === 'C') {
            $revenuRef = (int) ($inputData['pass_value'] * 3);
        }

        // Process each payment detail (each arret)
        if (isset($calculationResult['payment_details']) && is_array($calculationResult['payment_details'])) {
            foreach ($calculationResult['payment_details'] as $detail) {
                $idArret = $detail['id'] ?? $detail['arret_index'] ?? null;

                // Process each rate breakdown period
                if (isset($detail['rate_breakdown']) && is_array($detail['rate_breakdown'])) {
                    foreach ($detail['rate_breakdown'] as $rateBreakdown) {
                        $record = [
                            // Primary identification
                            'adherent_number' => $adherentNumber,
                            'num_sinistre' => $numSinistre,
                            'id_arret' => $idArret,

                            // Period information
                            'exercice' => $rateBreakdown['year'] ?? null,
                            'periode' => $rateBreakdown['period'] ?? null,
                            'date_start' => $rateBreakdown['start'] ?? null,
                            'date_end' => $rateBreakdown['end'] ?? null,

                            // Rate and amount information
                            'num_taux' => $rateBreakdown['taux'] ?? null,
                            'MT_journalier' => $this->convertToIntCents($rateBreakdown['rate'] ?? 0),

                            // Financial information
                            'MT_revenu_ref' => $revenuRef,
                            'classe' => $classe,

                            // Personal information
                            'personne_age' => $age,
                            'nb_trimestre' => $nbTrimestres,

                            // Additional metadata (not in table but useful)
                            '_nb_jours' => $rateBreakdown['days'] ?? 0,
                            '_montant_total' => $this->convertToIntCents(
                                ($rateBreakdown['rate'] ?? 0) * ($rateBreakdown['days'] ?? 0)
                            ),
                        ];

                        $records[] = $record;
                    }
                }
            }
        }

        return $records;
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
            'num_sinistre',
            'date_start',
            'date_end',
            'id_arret',
            'num_taux',
            'MT_journalier',
            'MT_revenu_ref',
            'classe',
            'personne_age',
            'nb_trimestre'
        ];

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

        $sql = "INSERT INTO ij_recap (" . implode(', ', $columns) . ")\n";
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
     * Format recap records for display (HTML table)
     *
     * @param array $records Array of record data
     * @return string HTML table
     */
    public function formatRecapHTML(array $records): string
    {
        if (empty($records)) {
            return '<p>Aucun enregistrement de récapitulatif.</p>';
        }

        $html = '<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #667eea; color: white;">';
        $html .= '<th style="padding: 10px; border: 1px solid #ddd;">Adhérent</th>';
        $html .= '<th style="padding: 10px; border: 1px solid #ddd;">Exercice</th>';
        $html .= '<th style="padding: 10px; border: 1px solid #ddd;">Période</th>';
        $html .= '<th style="padding: 10px; border: 1px solid #ddd;">Début</th>';
        $html .= '<th style="padding: 10px; border: 1px solid #ddd;">Fin</th>';
        $html .= '<th style="padding: 10px; border: 1px solid #ddd;">Taux</th>';
        $html .= '<th style="padding: 10px; border: 1px solid #ddd;">MT/Jour (€)</th>';
        $html .= '<th style="padding: 10px; border: 1px solid #ddd;">Jours</th>';
        $html .= '<th style="padding: 10px; border: 1px solid #ddd;">Classe</th>';
        $html .= '<th style="padding: 10px; border: 1px solid #ddd;">Âge</th>';
        $html .= '<th style="padding: 10px; border: 1px solid #ddd;">Trimestres</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($records as $index => $record) {
            $bgColor = $index % 2 === 0 ? '#f8f9fa' : 'white';
            $html .= '<tr style="background-color: ' . $bgColor . ';">';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . ($record['adherent_number'] ?? '-') . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . ($record['exercice'] ?? '-') . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . ($record['periode'] ?? '-') . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . ($record['date_start'] ?? '-') . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . ($record['date_end'] ?? '-') . '</td>';

            // Taux with badge
            $taux = $record['num_taux'] ?? '-';
            if ($taux !== '-') {
                $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><span style="background-color: #667eea; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold;">' . $taux . '</span></td>';
            } else {
                $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">-</td>';
            }

            // Amount in euros
            $mtJour = ($record['MT_journalier'] ?? 0) / 100;
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: right;">' . number_format($mtJour, 2, ',', ' ') . '€</td>';

            // Days
            $nbJours = $record['_nb_jours'] ?? '-';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . $nbJours . '</td>';

            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . ($record['classe'] ?? '-') . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . ($record['personne_age'] ?? '-') . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . ($record['nb_trimestre'] ?? '-') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    /**
     * Validate a recap record before insertion
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
            'num_taux' => 'Numéro de taux'
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

        // Validate num_taux (1-9)
        if (isset($record['num_taux'])) {
            $numTaux = $record['num_taux'];
            if ($numTaux < 1 || $numTaux > 9) {
                $errors[] = "Numéro de taux doit être entre 1 et 9 (actuel: $numTaux)";
            }
        }

        // Validate classe (A, B, or C)
        if (isset($record['classe'])) {
            $classe = $record['classe'];
            if (!in_array($classe, ['A', 'B', 'C'])) {
                $errors[] = "Classe doit être A, B ou C (actuel: $classe)";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
