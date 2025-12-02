<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Legacy Adherent Model
 *
 * Example model that uses the secondary database connection
 * This might be used to access data from a legacy system
 */
class LegacyAdherent extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'mysql_secondary';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'adherent_infos';

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
}
