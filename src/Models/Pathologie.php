<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pathologie Model
 *
 * Represents a pathology/medical condition code
 */
class Pathologie extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pathologie';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'code_pathologie';

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
        'code_pathologie',
        'libelle',
        'description',
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
     * Get the arrets for this pathologie
     */
    public function arrets()
    {
        return $this->hasMany(IjArret::class, 'code_pathologie', 'code_pathologie');
    }
}
