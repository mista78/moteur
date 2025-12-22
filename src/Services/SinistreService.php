<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IjSinistre;
use App\Models\RecapIdem;
use App\Tools\Tools;
use RuntimeException;

/**
 * Service Sinistre
 *
 * Gère la logique métier liée aux sinistres
 * Délègue les calculs de date-effet au DateService (séparation des préoccupations)
 */
class SinistreService implements SinistreServiceInterface
{
    private DateCalculationInterface $dateService;

    /**
     * Constructeur avec injection de dépendances
     *
     * @param DateCalculationInterface $dateService Service de calcul de dates
     */
    public function __construct(DateCalculationInterface $dateService)
    {
        $this->dateService = $dateService;
    }

    /**
     * Obtenir le sinistre avec date effet calculée pour tous les arrêts
     *
     * @param int $sinistreId L'ID du sinistre
     * @return array{sinistre: IjSinistre, arrets_with_date_effet: array, recap_indems: array}
     * @throws \RuntimeException Si le sinistre n'est pas trouvé
     */
    public function getSinistreWithDateEffet(int $sinistreId): array
    {
        // Chargement eager des relations pour éviter les requêtes N+1
        // Inclure recapIndems (ordonnés par indemnisation_from_line desc)
        $sinistre = IjSinistre::with(['arrets', 'adherent', 'recapIndems'])->find($sinistreId);

        if (!$sinistre) {
            throw new RuntimeException("Sinistre non trouvé avec ID: {$sinistreId}");
        }

        return $this->calculateDateEffetForSinistre($sinistre);
    }

    /**
     * Obtenir le sinistre avec date effet calculée pour un adhérent spécifique
     *
     * @param string $adherentNumber Le numéro d'adhérent
     * @param int $numero_dossier Le numero_dossier du sinistre
     * @return array{sinistre: IjSinistre, arrets_with_date_effet: array, recap_indems: array}
     * @throws \RuntimeException Si le sinistre n'est pas trouvé ou n'appartient pas à l'adhérent
     */
    public function getSinistreWithDateEffetForAdherent(string $adherentNumber, int $numero_dossier): array
    {
        $sinistre = IjSinistre::with(['arrets', 'adherent', 'recapIndems'])
            ->where('numero_dossier', $numero_dossier)
            ->where('adherent_number', $adherentNumber)
            ->first();

        if (!$sinistre) {
            throw new RuntimeException("Sinistre non trouvé ou n'appartient pas à l'adhérent {$adherentNumber}");
        }

        return $this->calculateDateEffetForSinistre($sinistre);
    }

    /**
     * Obtenir tous les sinistres pour un adhérent avec date effet calculée
     *
     * @param string $adherentNumber Le numéro d'adhérent
     * @return array Tableau de sinistres avec date effet calculée
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
     * Calculer la date effet pour les arrêts d'un sinistre
     *
     * Méthode helper privée qui délègue au DateService
     *
     * @param IjSinistre $sinistre Le modèle sinistre
     * @return array{sinistre: IjSinistre, arrets_with_date_effet: array, recap_indems: array, other_recap_indems: array}
     */
    private function calculateDateEffetForSinistre(IjSinistre $sinistre): array
    {
        // Convertir la collection Eloquent au format tableau attendu par DateService
        $arrets = $sinistre->toArray()["arrets"];

        // Obtenir la date de naissance depuis la relation adherent
        $birthDate = $sinistre->adherent?->birth_date ?? null;

        // Déléguer le calcul au DateService (la logique métier reste dans la couche service)
        $arretsWithDateEffet = empty($arrets)
            ? []
            : $this->dateService->calculateDateEffet($arrets, $birthDate);

        // Obtenir les recap indems pour les AUTRES sinistres (exclure le sinistre actuel)
        $adherentNumber = $sinistre->adherent_number;
        $otherRecapIndems = RecapIdem::byAdherent($adherentNumber)
            ->where('num_sinistre', '!=', $sinistre->id)
            ->orderBy('indemnisation_from_line', 'desc')
            ->get()
            ->toArray();
        return [
            // 'sinistre' => $sinistre,
            'arrets_with_date_effet' => Tools::formatForOutput($arretsWithDateEffet),
            'recap_indems' => $otherRecapIndems, // RecapIndems SANS le sinistre actuel
        ];
    }
}
