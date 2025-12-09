<?php

declare(strict_types=1);

namespace App\Models;

use App\Tools\Tools;
use Illuminate\Database\Eloquent\Model;

/**
 * Recap Indem Model
 *
 * Represents a view for indemnity recap
 * This is a database VIEW, not a table - it's read-only
 */
class RecapIdem extends Model
{
    /**
     * The table associated with the model (view name).
     *
     * @var string
     */
    protected $table = 'recap_indem';
    
    /**
     * The primary key for the model.
     * Note: Views typically don't have a true primary key
     *
     * @var string|null
     */
    protected $primaryKey = null;
    
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
    
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'num_sinistre' => 'integer',
        'numero_dossier' => 'integer',
        'id_arret' => 'integer',
        'taux_line' => 'integer',
        'indemnisation_from_line' => 'date:Y-m-d',
        'indemnisation_to_line' => 'date:Y-m-d',
        'ij_sinistre_created_at' => 'datetime',
        'ij_sinistre_updated_at' => 'datetime',
        'ij_recap_created_at' => 'datetime',
        'ij_recap_updated_at' => 'datetime',
    ];

    /**
     * The attributes that aren't mass assignable (view is read-only).
     *
     * @var array
     */
    protected $guarded = ['*'];

    /**
     * Boot method - make view read-only
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(fn() => false);
        static::updating(fn() => false);
        static::deleting(fn() => false);
    }

    /**
     * Get the IJ Sinistre associated with this recap
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ijSinistre()
    {
        return $this->belongsTo(IjSinistre::class, 'num_sinistre', 'id');
    }

    /**
     * Get the IJ Arret associated with this recap
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ijArret()
    {
        return $this->belongsTo(IjArret::class, 'id_arret', 'id');
    }

    /**
     * Scope: Filter by adherent number
     */
    public function scopeByAdherent($query, string $adherentNumber)
    {
        return $query->where('adherent_number', $adherentNumber);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('indemnisation_from_line', [$from, $to]);
    }

    /**
     * Scope: Filter by pathology code
     */
    public function scopeByPathologie($query, string $codePathologie)
    {
        return $query->where('code_pathologie', $codePathologie);
    }

    public function toArray()
    {
        $array = parent::toArray();
        return Tools::renommerCles($array, Tools::$correspondance);
    }
}