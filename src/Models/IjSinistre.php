<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * IJ Sinistre Model
 *
 * Represents a claim (sinistre) for sick leave benefits
 */
class IjSinistre extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ij_sinistre';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'adherent_number',
        'code_pathologie',
        'date_debut',
        'date_fin',
        'statut',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the arrets (work stoppages) for this sinistre
     */
    public function arrets()
    {
        return $this->hasMany(IjArret::class, 'num_sinistre');
    }

    /**
     * Get the detail jour records for this sinistre
     */
    public function detailJours()
    {
        return $this->hasMany(IjDetailJour::class, 'num_sinistre');
    }

    /**
     * Get the recap records for this sinistre
     */
    public function recaps()
    {
        return $this->hasMany(IjRecap::class, 'num_sinistre');
    }

    /**
     * Get the adherent associated with this sinistre
     */
    public function adherent()
    {
        return $this->belongsTo(AdherentInfos::class, 'adherent_number', 'adherent_number');
    }
}
