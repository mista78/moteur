<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Export Model
 *
 * Represents an export batch for ZDFCAIJ and ZDFCAR records
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $date
 * @property int $creation_totij_nb
 * @property int $creation_totnonij_nb
 * @property int $creation_sinistre_nb
 * @property int $creation_arret_nb
 * @property int $creation_periode_nb
 * @property int $modification_sinistre_nb
 * @property int $modification_arret_nb
 * @property int $modification_periode_nb
 * @property int $creation_car_nb
 * @property int $modification_car_nb
 * @property \Illuminate\Support\Carbon|null $date_creation_fichiers
 * @property \Illuminate\Support\Carbon|null $mf_export_returned_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Export extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'exports';

    public $timestamps = false;

    /**
     * The primary key associated with the table.
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
        'date',
        'creation_totij_nb',
        'creation_totnonij_nb',
        'creation_sinistre_nb',
        'creation_arret_nb',
        'creation_periode_nb',
        'modification_sinistre_nb',
        'modification_arret_nb',
        'modification_periode_nb',
        'creation_car_nb',
        'modification_car_nb',
        'date_creation_fichiers',
        'mf_export_returned_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date:Y-m-d',
        'creation_totij_nb' => 'integer',
        'creation_totnonij_nb' => 'integer',
        'creation_sinistre_nb' => 'integer',
        'creation_arret_nb' => 'integer',
        'creation_periode_nb' => 'integer',
        'modification_sinistre_nb' => 'integer',
        'modification_arret_nb' => 'integer',
        'modification_periode_nb' => 'integer',
        'creation_car_nb' => 'integer',
        'modification_car_nb' => 'integer',
        'date_creation_fichiers' => 'datetime',
        'mf_export_returned_at' => 'datetime',
    ];

    /**
     * Default attribute values.
     *
     * @var array
     */
    protected $attributes = [
        'creation_totij_nb' => 0,
        'creation_totnonij_nb' => 0,
        'creation_sinistre_nb' => 0,
        'creation_arret_nb' => 0,
        'creation_periode_nb' => 0,
        'modification_sinistre_nb' => 0,
        'modification_arret_nb' => 0,
        'modification_periode_nb' => 0,
        'creation_car_nb' => 0,
        'modification_car_nb' => 0,
    ];

    /**
     * Relationships
     */

    /**
     * Get the ZDFCAIJNG records for this export.
     */
    public function zdfcaijRecords()
    {
        return $this->hasMany(ZDFCAIJNG::class, 'export_id', 'id');
    }

    /**
     * Get the ZDFCARNG records for this export.
     */
    public function zdfcarRecords()
    {
        return $this->hasMany(ZDFCARNG::class, 'export_id', 'id');
    }

    /**
     * Static Methods (Business Logic)
     */

    /**
     * Get the current export (or create one if none exists)
     * 
     * This is the main method used by ZDFCAIJNG and ZDFCARNG
     * 
     * @return Export
     */
    public static function getCurrentExport(): Export
    {
        $export = self::getLastExportNotExported();

        if (empty($export)) {
            $export = new self();
            $export->date = Carbon::now();
            $export->save();
        }

        return $export;
    }

    /**
     * Get the last export that hasn't been exported yet
     * (no date_creation_fichiers)
     * 
     * @return Export|null
     */
    public static function getLastExportNotExported(): ?Export
    {
        return self::whereNull('date_creation_fichiers')
            ->orderByDesc('date')
            ->first();
    }

    /**
     * Get the last export that was exported but not yet integrated
     * (has date_creation_fichiers but no mf_export_returned_at)
     * 
     * @return Export|null
     */
    public static function getLastExportNotIntegrated(): ?Export
    {
        return self::whereNotNull('date_creation_fichiers')
            ->whereNull('mf_export_returned_at')
            ->orderByDesc('date')
            ->first();
    }

    /**
     * Finalize an export by setting the date_creation_fichiers
     * 
     * @param Export $export
     * @return bool
     */
    public static function finalize(Export $export): bool
    {
        $export->date_creation_fichiers = Carbon::now();
        return $export->save();
    }

    /**
     * Mark an export as integrated (MF has returned it)
     * 
     * @param Export $export
     * @return bool
     */
    public static function markAsIntegrated(Export $export): bool
    {
        $export->mf_export_returned_at = Carbon::now();
        return $export->save();
    }

    /**
     * Scopes
     */

    /**
     * Scope for exports not yet exported
     */
    public function scopeNotExported($query)
    {
        return $query->whereNull('date_creation_fichiers');
    }

    /**
     * Scope for exports that are exported but not integrated
     */
    public function scopeExportedNotIntegrated($query)
    {
        return $query->whereNotNull('date_creation_fichiers')
                     ->whereNull('mf_export_returned_at');
    }

    /**
     * Scope for completed exports (exported and integrated)
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('date_creation_fichiers')
                     ->whereNotNull('mf_export_returned_at');
    }

    /**
     * Scope for exports by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /**
     * Helper Methods
     */

    /**
     * Check if export has been exported (finalized)
     * 
     * @return bool
     */
    public function isExported(): bool
    {
        return $this->date_creation_fichiers !== null;
    }

    /**
     * Check if export has been integrated
     * 
     * @return bool
     */
    public function isIntegrated(): bool
    {
        return $this->mf_export_returned_at !== null;
    }

    /**
     * Check if export is in progress (not yet exported)
     * 
     * @return bool
     */
    public function isInProgress(): bool
    {
        return $this->date_creation_fichiers === null;
    }

    /**
     * Check if export is waiting for integration
     * 
     * @return bool
     */
    public function isWaitingIntegration(): bool
    {
        return $this->date_creation_fichiers !== null 
            && $this->mf_export_returned_at === null;
    }

    /**
     * Get total number of IJ records
     * 
     * @return int
     */
    public function getTotalIjRecords(): int
    {
        return $this->creation_totij_nb;
    }

    /**
     * Get total number of non-IJ records
     * 
     * @return int
     */
    public function getTotalNonIjRecords(): int
    {
        return $this->creation_totnonij_nb;
    }

    /**
     * Get total number of inserts
     * 
     * @return int
     */
    public function getTotalInserts(): int
    {
        return $this->creation_sinistre_nb 
             + $this->creation_arret_nb 
             + $this->creation_periode_nb 
             + $this->creation_car_nb;
    }

    /**
     * Get total number of updates
     * 
     * @return int
     */
    public function getTotalUpdates(): int
    {
        return $this->modification_sinistre_nb 
             + $this->modification_arret_nb 
             + $this->modification_periode_nb 
             + $this->modification_car_nb;
    }

    /**
     * Get total number of operations
     * 
     * @return int
     */
    public function getTotalOperations(): int
    {
        return $this->getTotalInserts() + $this->getTotalUpdates();
    }

    /**
     * Increment insert counter
     * 
     * @param string $type Type: 'sinistre', 'arret', 'periode', 'car'
     * @param int $count
     * @return void
     */
    public function incrementInserts(string $type, int $count = 1): void
    {
        $field = "creation_{$type}_nb";
        if (property_exists($this, $field)) {
            $this->$field += $count;
        }
    }

    /**
     * Increment update counter
     * 
     * @param string $type Type: 'sinistre', 'arret', 'periode', 'car'
     * @param int $count
     * @return void
     */
    public function incrementUpdates(string $type, int $count = 1): void
    {
        $field = "modification_{$type}_nb";
        if (property_exists($this, $field)) {
            $this->$field += $count;
        }
    }

    /**
     * Get export summary
     * 
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'status' => $this->getStatus(),
            'totals' => [
                'ij' => $this->creation_totij_nb,
                'non_ij' => $this->creation_totnonij_nb,
            ],
            'inserts' => [
                'sinistre' => $this->creation_sinistre_nb,
                'arret' => $this->creation_arret_nb,
                'periode' => $this->creation_periode_nb,
                'car' => $this->creation_car_nb,
                'total' => $this->getTotalInserts(),
            ],
            'updates' => [
                'sinistre' => $this->modification_sinistre_nb,
                'arret' => $this->modification_arret_nb,
                'periode' => $this->modification_periode_nb,
                'car' => $this->modification_car_nb,
                'total' => $this->getTotalUpdates(),
            ],
            'dates' => [
                'created' => $this->date->format('Y-m-d'),
                'exported' => $this->date_creation_fichiers 
                    ? $this->date_creation_fichiers->format('Y-m-d H:i:s') 
                    : null,
                'integrated' => $this->mf_export_returned_at 
                    ? $this->mf_export_returned_at->format('Y-m-d H:i:s') 
                    : null,
            ],
        ];
    }

    /**
     * Get export status
     * 
     * @return string
     */
    public function getStatus(): string
    {
        if ($this->isIntegrated()) {
            return 'completed';
        } elseif ($this->isWaitingIntegration()) {
            return 'waiting_integration';
        } elseif ($this->isExported()) {
            return 'exported';
        } else {
            return 'in_progress';
        }
    }

    /**
     * Get human-readable status
     * 
     * @return string
     */
    public function getStatusLabel(): string
    {
        return match($this->getStatus()) {
            'completed' => 'Completed',
            'waiting_integration' => 'Waiting for Integration',
            'exported' => 'Exported',
            'in_progress' => 'In Progress',
            default => 'Unknown',
        };
    }
}