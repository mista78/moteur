<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IjSinistre;
use RuntimeException;

/**
 * Sinistre Service
 *
 * Handles business logic related to sinistres (claims)
 * Delegates date-effet calculations to DateService (separation of concerns)
 */
class SinistreService implements SinistreServiceInterface
{
    private DateCalculationInterface $dateService;

    /**
     * Constructor with dependency injection
     *
     * @param DateCalculationInterface $dateService Date calculation service
     */
    public function __construct(DateCalculationInterface $dateService)
    {
        $this->dateService = $dateService;
    }

    /**
     * Get sinistre with calculated date effet for all arrets
     *
     * @param int $sinistreId The sinistre ID
     * @return array{sinistre: IjSinistre, arrets_with_date_effet: array, recap_indems: array}
     * @throws \RuntimeException If sinistre not found
     */
    public function getSinistreWithDateEffet(int $sinistreId): array
    {
        // Eager load relationships to avoid N+1 queries
        // Include recapIndems (ordered by indemnisation_from_line desc)
        $sinistre = IjSinistre::with(['arrets', 'adherent', 'recapIndems'])->find($sinistreId);

        if (!$sinistre) {
            throw new RuntimeException("Sinistre not found with ID: {$sinistreId}");
        }

        return $this->calculateDateEffetForSinistre($sinistre);
    }

    /**
     * Get sinistre with calculated date effet for specific adherent
     *
     * @param string $adherentNumber The adherent number
     * @param int $sinistreId The sinistre ID
     * @return array{sinistre: IjSinistre, arrets_with_date_effet: array, recap_indems: array}
     * @throws \RuntimeException If sinistre not found or doesn't belong to adherent
     */
    public function getSinistreWithDateEffetForAdherent(string $adherentNumber, int $sinistreId): array
    {
        $sinistre = IjSinistre::with(['arrets', 'adherent', 'recapIndems'])
            ->where('id', $sinistreId)
            ->where('adherent_number', $adherentNumber)
            ->first();

        if (!$sinistre) {
            throw new RuntimeException("Sinistre not found or doesn't belong to adherent {$adherentNumber}");
        }

        return $this->calculateDateEffetForSinistre($sinistre);
    }

    /**
     * Get all sinistres for an adherent with calculated date effet
     *
     * @param string $adherentNumber The adherent number
     * @return array Array of sinistres with date effet calculated
     */
    public function getAllSinistresWithDateEffet(string $adherentNumber): array
    {
        $sinistres = IjSinistre::with(['arrets', 'adherent', 'recapIndems'])
            ->where('adherent_number', $adherentNumber)
            ->orderBy('date_debut', 'desc')
            ->get();

        $result = [];
        foreach ($sinistres as $sinistre) {
            $result[] = $this->calculateDateEffetForSinistre($sinistre);
        }

        return $result;
    }

    /**
     * Calculate date effet for a sinistre's arrets
     *
     * Private helper method that delegates to DateService
     *
     * @param IjSinistre $sinistre The sinistre model
     * @return array{sinistre: IjSinistre, arrets_with_date_effet: array, recap_indems: array, other_recap_indems: array}
     */
    private function calculateDateEffetForSinistre(IjSinistre $sinistre): array
    {
        // Convert Eloquent collection to array format expected by DateService
        $arrets = $sinistre->arrets->map(function ($arret) {
            return $arret->toArray();
        })->toArray();

        // Get birth date from adherent relationship
        $birthDate = $sinistre->adherent?->birth_date ?? null;

        // Delegate calculation to DateService (business logic stays in service layer)
        $arretsWithDateEffet = empty($arrets)
            ? []
            : $this->dateService->calculateDateEffet($arrets, $birthDate);

        // Get recap indems for OTHER sinistres (exclude current sinistre)
        $adherentNumber = $sinistre->adherent_number;
        $otherRecapIndems = \App\Models\RecapIdem::byAdherent($adherentNumber)
            ->where('num_sinistre', '!=', $sinistre->id)
            ->orderBy('indemnisation_from_line', 'desc')
            ->get()
            ->map(function ($recap) {
                return $recap->toArray();
            })
            ->toArray();

        return [
            'sinistre' => $sinistre,
            'arrets_with_date_effet' => $arretsWithDateEffet,
            'recap_indems' => $otherRecapIndems, // RecapIndems WITHOUT current sinistre
        ];
    }
}
