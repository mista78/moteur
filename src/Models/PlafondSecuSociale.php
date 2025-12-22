<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modèle Plafond Sécurité Sociale
 *
 * Représente les valeurs PASS (Plafond Annuel de la Sécurité Sociale) par année
 * Utilisé pour déterminer les classes de cotisation (A/B/C) basées sur le revenu
 */
class PlafondSecuSociale extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'plafond_secu_sociale';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_plafond_secu_sociale';

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
        'date_deb_effet',
        'date_fin_effet',
        'MT_PASS',
        'MT_PTSS',
        'MT_PMSS',
        'MT_PHSS',
        'MT_PJSS',
        'MT_PHRSS',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_deb_effet' => 'date',
        'date_fin_effet' => 'date',
        'MT_PASS' => 'integer',
        'MT_PTSS' => 'integer',
        'MT_PMSS' => 'integer',
        'MT_PHSS' => 'integer',
        'MT_PJSS' => 'integer',
        'MT_PHRSS' => 'integer',
        'date_de_creation' => 'datetime',
        'date_de_dern_maj' => 'datetime',
    ];

    /**
     * Obtenir toutes les valeurs PASS indexées par année
     *
     * Retourne un tableau comme : [2024 => 46368, 2023 => 43992, ...]
     *
     * @return array<int, int>
     */
    public static function getPassValuesByYear(): array
    {
        $records = static::orderBy('date_deb_effet', 'desc')->get();

        $passByYear = [];
        foreach ($records as $record) {
            // Extraire l'année depuis date_deb_effet
            $year = (int) $record->date_deb_effet->format('Y');

            // Utiliser la valeur MT_PASS pour cette année
            if (!isset($passByYear[$year])) {
                $passByYear[$year] = $record->MT_PASS;
            }
        }

        return $passByYear;
    }

    /**
     * Get PASS value for a specific year
     *
     * @param int $year
     * @return int|null
     */
    public static function getPassForYear(int $year): ?int
    {
        $record = static::whereYear('date_deb_effet', '=', $year)
            ->orderBy('date_deb_effet', 'desc')
            ->first();

        return $record ? $record->MT_PASS : null;
    }

    /**
     * Get PASS value for a specific date
     *
     * @param string $date Date in Y-m-d format
     * @return int|null
     */
    public static function getPassForDate(string $date): ?int
    {
        $record = static::where('date_deb_effet', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->where('date_fin_effet', '>=', $date)
                      ->orWhereNull('date_fin_effet');
            })
            ->orderBy('date_deb_effet', 'desc')
            ->first();

        return $record ? $record->MT_PASS : null;
    }

    /**
     * Get latest PASS value
     *
     * @return int|null
     */
    public static function getLatestPass(): ?int
    {
        $record = static::orderBy('date_deb_effet', 'desc')->first();

        return $record ? $record->MT_PASS : null;
    }
}
