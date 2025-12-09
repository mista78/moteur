<?php

namespace App\Tools;

class Tools
{
    /**
     * Field correspondence mapping between JSON fields and database fields
     */
    public static array $correspondance = [
        'debutArret' => 'arret-from-line',
        'finArret' => 'arret-to-line',
        'date_end_init' => 'arret-to-line-init',
        'date_start' => 'arret-from-line',
        'date_end' => 'arret-to-line',
        'date_declaration' => 'declaration-date-line',
        'DT_excused' => 'dt-line',
        'date_deb_droit' => 'ouverture-date-line',
        'code_pathologie' => 'code-patho-line',
        'date_deb_dr_force' => 'date-deb-dr-force',
        'date_prolongation' => 'date-prolongation',
        'date_naissance' => 'birth_date',
        'adherent_number' => 'adherent-number',
        "indemnisation_from_line" => "indemnisation-from-line",
        "indemnisation_to_line" => "indemnisation-to-line",
        "taux_line" => "taux-line"
    ];

    /**
     * Rename array keys based on correspondence mapping
     *
     * @param array $array The array to transform
     * @param array $mapping The key mapping
     * @return array The transformed array
     */
    public static function renommerCles(array $array, array $mapping): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $mapping[$key] ?? $key;
            // If value is an array, recursively rename keys
            if (is_array($value)) {
                $result[$newKey] = self::renommerCles($value, $mapping);
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Log messages
     *
     * @param array $context Context information
     * @param string $message Message to log
     * @param string $level Log level (info, error, warning, debug)
     */
    public static function messageLog(array $context, string $message = '', string $level = 'info'): void
    {
        $contextString = implode(' | ', $context);
        $logMessage = "[{$contextString}] {$message}";

        switch ($level) {
            case 'error':
                error_log("ERROR: " . $logMessage);
                break;
            case 'warning':
                error_log("WARNING: " . $logMessage);
                break;
            case 'debug':
                error_log("DEBUG: " . $logMessage);
                break;
            default:
                error_log("INFO: " . $logMessage);
        }
    }


    public static function formatForOutput(array $arrets): array
    {
        $formatted = [];

        foreach ($arrets as $arret) {
            $output = $arret;

            // Map is_rechute to rechute-line (0 or 1)
            if (isset($arret['is_rechute'])) {
                $output['rechute-line'] = $arret['is_rechute'] ? 1 : 0;
                // Keep is_rechute for backward compatibility
            } else {
                // Ensure all arrets have rechute-line
                $output['rechute-line'] = 0;
                $output['is_rechute'] = false;
            }

            // Map decompte_days to decompte-line
            if (isset($arret['decompte_days'])) {
                $output['decompte-line'] = $arret['decompte_days'];
                // Keep decompte_days for backward compatibility
            }

            // Ensure ouverture-date-line is set from date-effet
            if (isset($arret['date-effet'])) {
                $output['ouverture-date-line'] = $arret['date-effet'];
            }

            $formatted[] = $output;
        }

        return $formatted;
    }
}