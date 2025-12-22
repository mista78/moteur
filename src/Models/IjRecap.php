<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * IJ Recap Model
 *
 * Represents a summary record of IJ calculations
 */
class IjRecap extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ij_recap';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'num_sinistre',
        'id_arret',
        'adherent_number',
        'montant_total',
        'nbe_jours',
        'date_debut',
        'date_fin',
        'date_start',
        'date_end',
        'statut',
        'classe',
        'option',
        'num_taux',
        'personne_age',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'montant_total' => 'decimal:2',
        'nbe_jours' => 'integer',
        'date_debut' => 'date:Y-m-d',
        'date_fin' => 'date:Y-m-d',
        'date_start' => 'date:Y-m-d',
        'date_end' => 'date:Y-m-d',
        'num_taux' => 'integer',
        'personne_age' => 'integer',
        'id_arret' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the sinistre associated with this recap
     */
    public function sinistre()
    {
        return $this->belongsTo(IjSinistre::class, 'num_sinistre');
    }

    /**
     * Get the arret associated with this recap
     */
    public function arret()
    {
        return $this->belongsTo(IjArret::class, 'id_arret', 'id');
    }

    /**
     * Get the adherent associated with this recap
     */
    public function adherent()
    {
        return $this->belongsTo(AdherentInfos::class, 'adherent_number', 'adherent_number');
    }

    
}