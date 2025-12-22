<?php

namespace App\Tools;

class Tools
{
    /**
     * Mapping de correspondance des champs entre les champs JSON et les champs de base de données
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
     * Renommer les clés du tableau basé sur le mapping de correspondance
     *
     * @param array $array Le tableau à transformer
     * @param array $mapping Le mapping de clés
     * @return array Le tableau transformé
     */
    public static function renommerCles(array $array, array $mapping): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $mapping[$key] ?? $key;
            // Si la valeur est un tableau, renommer récursivement les clés
            if (is_array($value)) {
                $result[$newKey] = self::renommerCles($value, $mapping);
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Enregistrer des messages
     *
     * @param array $context Information de contexte
     * @param string $message Message à enregistrer
     * @param string $level Niveau de log (info, error, warning, debug)
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

            // Mapper is_rechute vers rechute-line (0 ou 1)
            if (isset($arret['is_rechute'])) {
                $output['rechute-line'] = $arret['is_rechute'] ? 1 : 0;
                // Garder is_rechute pour compatibilité ascendante
            } else {
                // S'assurer que tous les arrêts ont rechute-line
                $output['rechute-line'] = 0;
                $output['is_rechute'] = false;
            }

            // Mapper decompte_days vers decompte-line
            if (isset($arret['decompte_days'])) {
                $output['decompte-line'] = $arret['decompte_days'];
                // Garder decompte_days pour compatibilité ascendante
            }

            // S'assurer que ouverture-date-line est défini depuis date-effet
            if (isset($arret['date-effet'])) {
                $output['ouverture-date-line'] = $arret['date-effet'];
            }

            $formatted[] = $output;
        }

        return $formatted;
    }
}