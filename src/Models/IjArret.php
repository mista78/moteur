<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * IJ Arret Model
 *
 * Represents a work stoppage (arrêt de travail) for medical professionals
 */
class IjArret extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ij_arret';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'adherent_number',
        'code_pathologie',
        'num_sinistre',
        'date_start',
        'date_end',
        'date_reprise_activite',
        'date_end_init',
        'date_prolongation',
        'first_day',
        'is_rechute',
        'is_prolongation',
        'date_declaration',
        'DT_excused',
        'valid_med_controleur',
        'cco_a_jour',
        'date_dern_attestation',
        'date_deb_droit',
        'date_deb_dr_force',
        'taux',
        'NOARRET',
        'source',
        'version',
        'actif',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'date_reprise_activite' => 'date',
        'date_end_init' => 'date',
        'date_prolongation' => 'date',
        'date_declaration' => 'date',
        'date_dern_attestation' => 'date',
        'date_deb_droit' => 'date',
        'date_deb_dr_force' => 'date',
        'first_day' => 'boolean',
        'is_rechute' => 'boolean',
        'is_prolongation' => 'boolean',
        'DT_excused' => 'boolean',
        'valid_med_controleur' => 'boolean',
        'cco_a_jour' => 'boolean',
        'taux' => 'float',
        'NOARRET' => 'integer',
        'version' => 'integer',
        'actif' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'first_day' => 0,
        'is_rechute' => 0,
        'is_prolongation' => 0,
        'source' => 'OPEN',
        'version' => 1,
        'actif' => 1,
    ];

    /**
     * Get the sinistre (claim) associated with this arret
     */
    public function sinistre()
    {
        return $this->belongsTo(IjSinistre::class, 'num_sinistre');
    }

    /**
     * Get the adherent associated with this arret
     */
    public function adherent()
    {
        return $this->belongsTo(AdherentInfos::class, 'adherent_number', 'adherent_number');
    }

    /**
     * Get the pathologie associated with this arret
     */
    public function pathologie()
    {
        return $this->belongsTo(Pathologie::class, 'code_pathologie', 'code_pathologie');
    }

    /**
     * Scope to get only active arrets
     */
    public function scopeActive($query)
    {
        return $query->where('actif', 1);
    }

    /**
     * Scope to get only rechute arrets
     */
    public function scopeRechute($query)
    {
        return $query->where('is_rechute', 1);
    }

    /**
     * Scope to get only prolongation arrets
     */
    public function scopeProlongation($query)
    {
        return $query->where('is_prolongation', 1);
    }

    /**
     * Check if this arret is a rechute
     *
     * @return bool
     */
    public function isRechute(): bool
    {
        return (bool) $this->is_rechute;
    }

    /**
     * Check if this arret is a prolongation
     *
     * @return bool
     */
    public function isProlongation(): bool
    {
        return (bool) $this->is_prolongation;
    }

    /**
     * Check if this arret is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->actif;
    }

    /**
     * Get the duration in days
     *
     * @return int|null
     */
    public function getDurationInDays(): ?int
    {
        if (!$this->date_start || !$this->date_end) {
            return null;
        }

        return $this->date_start->diffInDays($this->date_end) + 1;
    }

    /**
     * Check if arret has been prolonged
     *
     * @return bool
     */
    public function hasProlongation(): bool
    {
        return $this->date_prolongation !== null;
    }

    /**
     * Check if activity has been resumed
     *
     * @return bool
     */
    public function hasResumedActivity(): bool
    {
        return $this->date_reprise_activite !== null;
    }
}
