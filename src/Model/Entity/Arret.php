<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Arret Entity
 *
 * Représente un arrêt de travail
 *
 * @property int $id
 * @property string $arret_from Début de l'arrêt
 * @property string $arret_to Fin de l'arrêt
 * @property int $arret_diff Nombre de jours
 * @property string|null $attestation_date Date d'attestation
 * @property string|null $declaration_date Date de déclaration
 * @property int $rechute Indicateur de rechute (0, 1, 15)
 * @property int $option Option de cotisation
 * @property string|null $date_effet Date d'effet des droits
 * @property string $code_pathologie Code pathologie
 * @property string $adherent_number Numéro d'adhérent
 * @property string $birth_date Date de naissance
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class Arret extends Entity
{
    /**
     * Fields that can be mass assigned
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'arret_from' => true,
        'arret_to' => true,
        'arret_diff' => true,
        'attestation_date' => true,
        'declaration_date' => true,
        'rechute' => true,
        'option' => true,
        'date_effet' => true,
        'code_pathologie' => true,
        'adherent_number' => true,
        'birth_date' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Convert to array format for calculator
     *
     * @return array<string, mixed>
     */
    public function toCalculatorFormat(): array
    {
        return [
            'arret-from-line' => $this->arret_from,
            'arret-to-line' => $this->arret_to,
            'arret_diff' => $this->arret_diff,
            'attestation-date-line' => $this->attestation_date,
            'declaration-date-line' => $this->declaration_date,
            'rechute-line' => $this->rechute,
            'option' => $this->option,
            'date-effet' => $this->date_effet,
            'code_pathologie' => $this->code_pathologie,
            'adherent_number' => $this->adherent_number,
            'date_naissance' => $this->birth_date,
        ];
    }
}
