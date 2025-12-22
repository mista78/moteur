<?php

namespace App\Services;

use App\Models\AdherentInfos;

/**
 * DetailsAdherentsService
 *
 * Service pour récupérer les informations de revenus des adhérents par année
 */
class DetailsAdherentsService
{
    /**
     * Obtenir le revenu de l'adhérent par année
     *
     * @param string $adherent_number
     * @return array Tableau des revenus par année [année => revenu_en_centimes]
     */
    public function revenuAdhParAnnee(string $adherent_number): array
    {
        // TODO: Implémenter la logique réelle pour récupérer le revenu de l'adhérent par année
        // Cela devrait retourner un tableau comme: [2020 => 4700000, 2021 => 5000000, 2022 => 5200000]
        // où les valeurs sont en centimes (ex: 4700000 = 47000 euros)
        
        $adherent = AdherentInfos::where('adherent_number', $adherent_number)->first();
        
        if (!$adherent) {
            return [];
        }

        // Exemple d'implémentation - remplacer par la logique réelle
        // Cela pourrait provenir d'une table d'historique de revenus ou similaire
        $currentYear = date('Y');
        $revenues = [];

        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            // Obtenir le revenu pour cette année - implémenter votre logique réelle ici
            $revenues[$year] = $this->getRevenuForYear($adherent_number, $year);
        }
        
        return $revenues;
    }
    
    /**
     * Obtenir le revenu pour une année spécifique
     *
     * @param string $adherent_number
     * @param int $year
     * @return int Revenu en centimes
     */
    private function getRevenuForYear(string $adherent_number, int $year): int
    {
        // TODO: Implémenter la logique réelle
        // Cela pourrait interroger une table d'historique de revenus ou utiliser une méthode sur le modèle adherent

        $adherent = AdherentInfos::where('adherent_number', $adherent_number)->first();

        if (!$adherent) {
            return 0;
        }

        // Si AdherentInfos a une méthode getRevenuByYear, l'utiliser
        if (method_exists($adherent, 'getRevenuByYear')) {
            return $adherent->getRevenuByYear($year) * 100; // Convertir en centimes
        }

        // Sinon retourner une valeur par défaut
        return 4700000; // 47000 euros en centimes
    }
}