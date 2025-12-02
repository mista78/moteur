<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Adherent Infos Model
 *
 * Represents member information (adherent details)
 */
class AdherentInfos extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'adherent_infos';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'adherent_number';

    /**
     * The "type" of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'adherent_number',
        'nom',
        'prenom',
        'email',
        // Add other fields as needed
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the IJ arrets for this adherent
     */
    public function arrets()
    {
        return $this->hasMany(IjArret::class, 'adherent_number', 'adherent_number');
    }

    /**
     * Get the IJ detail jour records for this adherent
     */
    public function detailJours()
    {
        return $this->hasMany(IjDetailJour::class, 'adherent_number', 'adherent_number');
    }

    /**
     * Get the IJ recap records for this adherent
     */
    public function recaps()
    {
        return $this->hasMany(IjRecap::class, 'adherent_number', 'adherent_number');
    }
}
