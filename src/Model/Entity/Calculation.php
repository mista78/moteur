<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Calculation Entity
 *
 * Représente un calcul d'indemnités journalières sauvegardé
 *
 * @property int $id
 * @property string $adherent_number Numéro d'adhérent
 * @property string $statut Statut (M, RSPM, CCPL)
 * @property string $classe Classe (A, B, C)
 * @property int $option Option de cotisation
 * @property string $birth_date Date de naissance
 * @property int $nb_jours Nombre de jours indemnisables
 * @property float $montant Montant calculé
 * @property int $age Age lors du calcul
 * @property int $total_cumul_days Total jours cumulés
 * @property int $nb_trimestres Nombre de trimestres
 * @property bool $patho_anterior Pathologie antérieure
 * @property string|null $calculation_data Données de calcul (JSON)
 * @property string|null $input_data Données d'entrée (JSON)
 * @property \Cake\I18n\DateTime $calculated_at Date du calcul
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class Calculation extends Entity
{
    /**
     * Fields that can be mass assigned
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'adherent_number' => true,
        'statut' => true,
        'classe' => true,
        'option' => true,
        'birth_date' => true,
        'nb_jours' => true,
        'montant' => true,
        'age' => true,
        'total_cumul_days' => true,
        'nb_trimestres' => true,
        'patho_anterior' => true,
        'calculation_data' => true,
        'input_data' => true,
        'calculated_at' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Virtual fields
     *
     * @var list<string>
     */
    protected array $_virtual = ['formatted_montant'];

    /**
     * Get formatted amount
     *
     * @return string
     */
    protected function _getFormattedMontant(): string
    {
        return number_format($this->montant, 2, ',', ' ') . ' €';
    }

    /**
     * Get calculation data as array
     *
     * @return array<string, mixed>
     */
    public function getCalculationData(): array
    {
        return json_decode($this->calculation_data ?? '{}', true);
    }

    /**
     * Get input data as array
     *
     * @return array<string, mixed>
     */
    public function getInputData(): array
    {
        return json_decode($this->input_data ?? '{}', true);
    }
}
