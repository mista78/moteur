<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * IJ Taux Model
 *
 * Represents rate records for IJ calculations
 * Contains historical rates for classes A, B, and C with 3 tiers each
 */
class IjTaux extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ij_taux';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'date_start',
        'date_end',
        'taux_a1',
        'taux_a2',
        'taux_a3',
        'taux_b1',
        'taux_b2',
        'taux_b3',
        'taux_c1',
        'taux_c2',
        'taux_c3',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'taux_a1' => 'float',
        'taux_a2' => 'float',
        'taux_a3' => 'float',
        'taux_b1' => 'float',
        'taux_b2' => 'float',
        'taux_b3' => 'float',
        'taux_c1' => 'float',
        'taux_c2' => 'float',
        'taux_c3' => 'float',
    ];

    /**
     * Get rate for a specific year
     *
     * @param int $year
     * @return IjTaux|null
     */
    public static function getRateForYear(int $year): ?IjTaux
    {
        return static::whereYear('date_start', '<=', $year)
            ->whereYear('date_end', '>=', $year)
            ->first();
    }

    /**
     * Get rate for a specific date
     *
     * @param string $date Date in Y-m-d format
     * @return IjTaux|null
     */
    public static function getRateForDate(string $date): ?IjTaux
    {
        return static::where('date_start', '<=', $date)
            ->where('date_end', '>=', $date)
            ->first();
    }

    /**
     * Get all rates ordered by date
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllRatesOrdered()
    {
        return static::orderBy('date_start', 'asc')->get();
    }
}
