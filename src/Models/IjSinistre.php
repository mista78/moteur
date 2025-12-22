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
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'adherent_number',
        'code_pathologie',
        'numero_dossier',
        'date_debut',
        'date_fin',
        'statut',
        'NOGROUPEINIT',
        'CODEMALADIE',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'numero_dossier' => 'integer',
        'date_debut' => 'date:Y-m-d',
        'date_fin' => 'date:Y-m-d',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the arrets (work stoppages) for this sinistre
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function arrets()
    {
        return $this->hasMany(IjArret::class, 'num_sinistre', 'id');
    }

    /**
     * Get the detail jour records for this sinistre
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function detailJours()
    {
        return $this->hasMany(IjDetailJour::class, 'num_sinistre', 'id');
    }

    /**
     * Get the recap records for this sinistre
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function recaps()
    {
        return $this->hasMany(IjRecap::class, 'num_sinistre', 'id');
    }

    /**
     * Get the recap indem (view) records for this sinistre
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function recapIndems()
    {
        return $this->hasMany(RecapIdem::class, 'num_sinistre', 'id')
                    ->orderBy('indemnisation_from_line', 'desc');
    }

    /**
     * Get the adherent associated with this sinistre
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function adherent()
    {
        return $this->belongsTo(AdherentInfos::class, 'adherent_number', 'adherent_number');
    }

    /**
     * Scope: Active sinistres
     */
    public function scopeActive($query)
    {
        return $query->where('statut', 'active');
    }

    /**
     * Scope: By date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('date_debut', [$from, $to]);
    }

    /**
     * Get only active arrets
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getArretsActifs()
    {
        return $this->arrets()->where('actif', 1)->get();
    }

    /**
     * Access adherent via dynamic property (for compatibility)
     */
    public function getAdherentAttribute()
    {
        if (!$this->relationLoaded('adherent')) {
            $this->load('adherent');
        }
        return $this->getRelation('adherent');
    }
}