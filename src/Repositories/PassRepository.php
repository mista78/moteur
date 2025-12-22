<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PlafondSecuSociale;

/**
 * Repository pour charger et gérer les données PASS (Plafond Annuel Sécurité Sociale)
 */
class PassRepository
{
    /**
     * Charger les valeurs PASS depuis la base de données, indexées par année
     *
     * Retourne un tableau comme : [2024 => 46368, 2023 => 43992, ...]
     *
     * @return array<int, int>
     */
    public function loadPassValuesByYear(): array
    {
        try {
            return PlafondSecuSociale::getPassValuesByYear();
        } catch (\Exception $e) {
            // Si la base de données échoue, retourner un tableau vide ou des valeurs par défaut
            return $this->getDefaultPassValues();
        }
    }

    /**
     * Obtenir la valeur PASS pour une année spécifique
     *
     * @param int $year
     * @return int|null
     */
    public function getPassForYear(int $year): ?int
    {
        try {
            return PlafondSecuSociale::getPassForYear($year);
        } catch (\Exception $e) {
            // Repli sur les valeurs par défaut
            $defaults = $this->getDefaultPassValues();
            return $defaults[$year] ?? null;
        }
    }

    /**
     * Obtenir la valeur PASS pour une date spécifique
     *
     * @param string $date Date au format Y-m-d
     * @return int|null
     */
    public function getPassForDate(string $date): ?int
    {
        try {
            return PlafondSecuSociale::getPassForDate($date);
        } catch (\Exception $e) {
            // Extraire l'année et utiliser le repli
            $year = (int) date('Y', strtotime($date));
            return $this->getPassForYear($year);
        }
    }

    /**
     * Obtenir la dernière valeur PASS
     *
     * @return int
     */
    public function getLatestPass(): int
    {
        try {
            return PlafondSecuSociale::getLatestPass() ?? 46368; // Valeur par défaut 2024
        } catch (\Exception $e) {
            return 46368; // Repli par défaut
        }
    }

    /**
     * Valeurs PASS par défaut (repli si la base de données est indisponible)
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
