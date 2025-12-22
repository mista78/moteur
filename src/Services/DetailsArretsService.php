<?php

namespace App\Services;

use App\Models\IjArret;

/**
 * DetailsArretsService
 *
 * Service pour déterminer la classification des arrêts basée sur le revenu et le PSS
 */
class DetailsArretsService
{
    /**
     * Get arret class based on adherent income and dates
     * 
     * @param IjArret|object $arret
     * @return string
     */
    public function getArretClasse($arret): string
    {
        $detailsAdherentsService = new DetailsAdherentsService();
        $revenuAdhParAnnee = $detailsAdherentsService->revenuAdhParAnnee($arret->adherent_number);
        
        $tauxDeterminationService = new TauxDeterminationService();
        $pssParAnnee = $tauxDeterminationService->pssParAnnee();

        $dateDebutDroit = $arret->date_deb_dr_force ?? $arret->date_deb_droit;

        if (!empty($dateDebutDroit)) {
            if (is_object($dateDebutDroit)) {
                $dateDebutDroit = $dateDebutDroit->format('Y-m-d');
            }
        }

        $year = $arret->date_start;
        if (is_object($year)) {
            $year = (int)$year->format('Y') - 2;
        } else {
            $year = (int)date('Y', strtotime($year)) - 2;
        }

        $tauxClass = new TauxDeterminationService(47000);
        $tauxClass->setPassValuesByYear($pssParAnnee);

        // Convertir le revenu de centimes en euros (diviser par 100)
        $revenue = isset($revenuAdhParAnnee[$year]) 
            ? $revenuAdhParAnnee[$year] / 100 
            : 0;

        return $tauxClass->determineClasse($revenue, $dateDebutDroit);
    }
}