<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Analytics Log Model
 *
 * Example model that uses the analytics database connection
 * This might be used for storing calculation logs, metrics, etc.
 */
class AnalyticsLog extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'mysql_analytics';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'calculation_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'adherent_number',
        'calculation_type',
        'input_data',
        'output_data',
        'execution_time',
        'success',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'execution_time' => 'float',
        'success' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
