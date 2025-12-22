<?php

namespace App\Services;

use App\Models\AdherentInfos;

/**
 * DetailsAdherentsService
 * 
 * Service for retrieving adherent income information by year
 */
class DetailsAdherentsService
{
    /**
     * Get adherent revenue by year
     * 
     * @param string $adherent_number
     * @return array Array of revenue by year [year => revenue_in_cents]
     */
    public function revenuAdhParAnnee(string $adherent_number): array
    {
        // TODO: Implement the actual logic to retrieve adherent revenue by year
        // This should return an array like: [2020 => 4700000, 2021 => 5000000, 2022 => 5200000]
        // where values are in cents (e.g., 4700000 = 47000 euros)
        
        $adherent = AdherentInfos::where('adherent_number', $adherent_number)->first();
        
        if (!$adherent) {
            return [];
        }
        
        // Example implementation - replace with actual logic
        // This might come from a revenue history table or similar
        $currentYear = date('Y');
        $revenues = [];
        
        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            // Get revenue for this year - implement your actual logic here
            $revenues[$year] = $this->getRevenuForYear($adherent_number, $year);
        }
        
        return $revenues;
    }
    
    /**
     * Get revenue for a specific year
     * 
     * @param string $adherent_number
     * @param int $year
     * @return int Revenue in cents
     */
    private function getRevenuForYear(string $adherent_number, int $year): int
    {
        // TODO: Implement actual logic
        // This might query a revenue history table or use a method on the adherent model
        
        $adherent = AdherentInfos::where('adherent_number', $adherent_number)->first();
        
        if (!$adherent) {
            return 0;
        }
        
        // If AdherentInfos has a getRevenuByYear method, use it
        if (method_exists($adherent, 'getRevenuByYear')) {
            return $adherent->getRevenuByYear($year) * 100; // Convert to cents
        }
        
        // Otherwise return a default value
        return 4700000; // 47000 euros in cents
    }
}