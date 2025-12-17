<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IjSinistre;

/**
 * Interface for Sinistre Service
 * Handles business logic related to sinistres (claims)
 */
interface SinistreServiceInterface
{
    /**
     * Get sinistre with calculated date effet for all arrets
     *
     * @param int $sinistreId The sinistre ID
     * @return array{sinistre: IjSinistre, arrets_with_date_effet: array, recap_indems: array}
     * @throws \RuntimeException If sinistre not found
     */
    public function getSinistreWithDateEffet(int $sinistreId): array;

    /**
     * Get sinistre with calculated date effet for specific adherent
     *
     * @param string $adherentNumber The adherent number
     * @param int $sinistreId The sinistre ID
     * @return array{sinistre: IjSinistre, arrets_with_date_effet: array, recap_indems: array}
     * @throws \RuntimeException If sinistre not found or doesn't belong to adherent
     */
    public function getSinistreWithDateEffetForAdherent(string $adherentNumber, int $sinistreId): array;

    /**
     * Get all sinistres for an adherent with calculated date effet
     *
     * @param string $adherentNumber The adherent number
     * @return array Array of sinistres with date effet calculated
     */
    public function getAllSinistresWithDateEffet(string $adherentNumber): array;
}
