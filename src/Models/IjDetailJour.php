<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * IJ Detail Jour Model
 *
 * Represents daily detail records for IJ calculations (monthly breakdown)
 * Stores up to 31 days of data per record (one row per month/period)
 */
class IjDetailJour extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ij_detail_jour';

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
        'adherent_number',
        'exercice',
        'periode',
        'num_sinistre',
        'j1', 'j2', 'j3', 'j4', 'j5', 'j6', 'j7', 'j8', 'j9', 'j10',
        'j11', 'j12', 'j13', 'j14', 'j15', 'j16', 'j17', 'j18', 'j19', 'j20',
        'j21', 'j22', 'j23', 'j24', 'j25', 'j26', 'j27', 'j28', 'j29', 'j30', 'j31',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'num_sinistre' => 'integer',
        'j1' => 'integer',
        'j2' => 'integer',
        'j3' => 'integer',
        'j4' => 'integer',
        'j5' => 'integer',
        'j6' => 'integer',
        'j7' => 'integer',
        'j8' => 'integer',
        'j9' => 'integer',
        'j10' => 'integer',
        'j11' => 'integer',
        'j12' => 'integer',
        'j13' => 'integer',
        'j14' => 'integer',
        'j15' => 'integer',
        'j16' => 'integer',
        'j17' => 'integer',
        'j18' => 'integer',
        'j19' => 'integer',
        'j20' => 'integer',
        'j21' => 'integer',
        'j22' => 'integer',
        'j23' => 'integer',
        'j24' => 'integer',
        'j25' => 'integer',
        'j26' => 'integer',
        'j27' => 'integer',
        'j28' => 'integer',
        'j29' => 'integer',
        'j30' => 'integer',
        'j31' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get the sinistre (claim) associated with this detail
     */
    public function sinistre()
    {
        return $this->belongsTo(IjSinistre::class, 'num_sinistre');
    }

    /**
     * Get the adherent associated with this detail
     */
    public function adherent()
    {
        return $this->belongsTo(AdherentInfos::class, 'adherent_number', 'adherent_number');
    }

    /**
     * Get all daily values as an array (j1-j31)
     *
     * @return array<int, int|null>
     */
    public function getDailyValues(): array
    {
        $values = [];
        for ($i = 1; $i <= 31; $i++) {
            $values[$i] = $this->{"j$i"};
        }
        return $values;
    }

    /**
     * Set all daily values from an array
     *
     * @param array<int, int|null> $values Array of daily values (1-31)
     * @return void
     */
    public function setDailyValues(array $values): void
    {
        for ($i = 1; $i <= 31; $i++) {
            if (isset($values[$i])) {
                $this->{"j$i"} = $values[$i];
            }
        }
    }

    /**
     * Get the value for a specific day
     *
     * @param int $day Day number (1-31)
     * @return int|null
     */
    public function getDayValue(int $day): ?int
    {
        if ($day < 1 || $day > 31) {
            return null;
        }
        return $this->{"j$day"};
    }

    /**
     * Set the value for a specific day
     *
     * @param int $day Day number (1-31)
     * @param int|null $value Value to set
     * @return void
     */
    public function setDayValue(int $day, ?int $value): void
    {
        if ($day >= 1 && $day <= 31) {
            $this->{"j$day"} = $value;
        }
    }

    /**
     * Get the sum of all daily values
     *
     * @return int
     */
    public function getTotalAmount(): int
    {
        $total = 0;
        for ($i = 1; $i <= 31; $i++) {
            $total += $this->{"j$i"} ?? 0;
        }
        return $total;
    }

    /**
     * Get the count of non-null days
     *
     * @return int
     */
    public function getActiveDaysCount(): int
    {
        $count = 0;
        for ($i = 1; $i <= 31; $i++) {
            if ($this->{"j$i"} !== null) {
                $count++;
            }
        }
        return $count;
    }
}
