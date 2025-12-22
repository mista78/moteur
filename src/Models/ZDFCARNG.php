<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * ZDFCARNG Model
 *
 * Represents career/contribution records for export
 *
 * @property int $id
 * @property string|null $TYPEDF
 * @property string|null $NUMCOTIS
 * @property string|null $SOUSNUMCOTIS
 * @property string|null $CODFAMIL
 * @property string|null $SEQFAMIL
 * @property string|null $NOCATEGAPR
 * @property string|null $CATEGAPR
 * @property string|null $NOREGIME
 * @property string|null $REGIME
 * @property string|null $NOSOUSREGIME
 * @property string|null $SOUSREGIME
 * @property string|null $DATEFFET
 * @property string|null $MINORATION
 * @property string|null $DATEFIN
 * @property string|null $CODEFIN
 * @property string|null $CODEMOTIF
 * @property string|null $AJOURNEMENT
 * @property string|null $NBRPOINTS
 * @property string|null $REGIMELIQ
 * @property string|null $LCCOTIS
 * @property string|null $DATEREPRISE
 * @property string|null $TYPECARRIERE
 * @property string|null $MPTC
 * @property int|null $num_sinistre
 * @property int|null $id_arret
 * @property int|null $export_id
 * @property int|null $actif
 * @property int $version
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ZDFCARNG extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ZDFCAR_NG';

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
        'TYPEDF',
        'NUMCOTIS',
        'SOUSNUMCOTIS',
        'CODFAMIL',
        'SEQFAMIL',
        'NOCATEGAPR',
        'CATEGAPR',
        'NOREGIME',
        'REGIME',
        'NOSOUSREGIME',
        'SOUSREGIME',
        'DATEFFET',
        'MINORATION',
        'DATEFIN',
        'CODEFIN',
        'CODEMOTIF',
        'AJOURNEMENT',
        'NBRPOINTS',
        'REGIMELIQ',
        'LCCOTIS',
        'DATEREPRISE',
        'TYPECARRIERE',
        'MPTC',
        'num_sinistre',
        'id_arret',
        'export_id',
        'actif',
    ];

    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'num_sinistre' => 'integer',
        'id_arret' => 'integer',
        'export_id' => 'integer',
        'actif' => 'integer',
    ];

    /**
     * Default attribute values.
     *
     * @var array
     */
    protected $attributes = [
        'actif' => 1,
    ];

    /**
     * Relationships
     */
    
    /**
     * Get the arret associated with this record.
     */
    public function arret()
    {
        return $this->belongsTo(IjArret::class, 'id_arret', 'id');
    }

    /**
     * Get the sinistre associated with this record.
     */
    public function sinistre()
    {
        return $this->belongsTo(IjSinistre::class, 'num_sinistre', 'id');
    }

    /**
     * Get the export associated with this record.
     */
    public function export()
    {
        return $this->belongsTo(Export::class, 'export_id', 'id');
    }

    /**
     * Helper Methods
     */

    /**
     * Set adherent number.
     */
    public function setAdherentNumber(string $adherent_number): void
    {
        $this->NUMCOTIS = substr($adherent_number, 0, -1);
        $this->LCCOTIS = strtoupper(substr($adherent_number, -1));
    }

    /**
     * Set adherent information.
     */
    public function setAdherentInfos($adherent): void
    {
        $this->setAdherentNumber($adherent->adherent_number);
        $this->TYPEDF = ($adherent->statut == 'CCPL') ? 'CCPL' : 'MEDE';
        $this->CATEGAPR = ($adherent->statut == 'CCPL') ? '050' : '010';
    }

    /**
     * Set default values for the entity.
     */
    public function setDefaultValues(): void
    {
        $this->SOUSNUMCOTIS = '00000';
        $this->CODFAMIL = '0';
        $this->SEQFAMIL = '00';
        $this->NOCATEGAPR = '01';
        $this->NOSOUSREGIME = '00';
        $this->SOUSREGIME = '   ';
        $this->MINORATION = '10000';
        $this->CODEMOTIF = 'DT';
        $this->AJOURNEMENT = '00000';
        $this->TYPECARRIERE = '000';
        $this->MPTC = 'P';
        $this->REGIMELIQ = 'IJ   ';
    }

    /**
     * Initialize line with common values.
     */
    public function initLine($sinistre): void
    {
        $this->setAdherentInfos($sinistre->adherent);
        $this->setDefaultValues();
        $this->num_sinistre = $sinistre->id;
        
        // Get first arret's pathology code
        $arrets = $sinistre->arrets;
        if ($arrets && $arrets->count() > 0) {
            $this->CODEMALADIE = $arrets->first()->code_pathologie;
        }
    }

    /**
     * Set values from ZDFCAIJNG line.
     */
    public function setValuesFromZDFCAIJ($line): void
    {
        $similar_fields = [
            'TYPEDF', 'NUMCOTIS', 'LCCOTIS', 'SOUSNUMCOTIS', 
            'CODFAMIL', 'SEQFAMIL', 'CATEGAPR', 'NOREGIME', 
            'NOCATEGAPR', 'num_sinistre', 'id_arret', 'export_id'
        ];

        foreach ($similar_fields as $field) {
            $this->{$field} = $line->{$field};
        }
        
        $this->NOSOUSREGIME = '00';
        $this->SOUSREGIME = '   ';
        $this->CODEMOTIF = 'DT';
        $this->AJOURNEMENT = '00000';
        $this->REGIMELIQ = 'IJ   ';
        $this->TYPECARRIERE = '000';
        $this->MPTC = 'P';
        $this->MINORATION = '10000';
    }

    /**
     * Set line from recap data.
     */
    public function setLine($sinistre, $recap, $arret, string $class, string $noRegime): void
    {
        $this->initLine($sinistre);
        $this->NOREGIME = $noRegime;
        $this->REGIME = 'IJ' . $class . str_pad((string)$recap->num_taux, 2, '0', STR_PAD_LEFT);
        $this->DATEFFET = $recap->date_start->format('d.m.Y');
        $this->DATEFIN = $recap->date_end->format('d.m.Y');
        $this->CODEFIN = ($recap->date_end->format('Y-m-d') == $arret->date_end->format('Y-m-d')) ? 'FD' : 'FT';
        $this->NBRPOINTS = '000' . str_pad(
            (string)($recap->date_end->day - $recap->date_start->day + 1), 
            2, 
            '0', 
            STR_PAD_LEFT
        ) . '00';
        $this->DATEREPRISE = $this->DATEFFET;
        $this->id_arret = $arret->id;
    }

    /**
     * Update line with recap data.
     */
    public function updateLine($recap, $arret): void
    {
        $this->DATEFIN = $recap->date_end->format('d.m.Y');
        $this->CODEFIN = ($recap->date_end->format('Y-m-d') == $arret->date_end->format('Y-m-d')) ? 'FD' : 'FT';
        
        $dateffet = Carbon::createFromFormat('d.m.Y', $this->DATEFFET);
        $this->NBRPOINTS = '000' . str_pad(
            (string)($recap->date_end->day - $dateffet->day + 1), 
            2, 
            '0', 
            STR_PAD_LEFT
        ) . '00';
    }

    /**
     * Import from ZDFCAIJNG Line D.
     */
    public function importFromLineD($lineD, string $regime, Carbon $dateffet, Carbon $datefin): void
    {
        $this->setValuesFromZDFCAIJ($lineD);
        $this->REGIME = $regime;
        $this->DATEFFET = $dateffet->format('d.m.Y');
        
        $endOfMonth = $dateffet->copy()->endOfMonth();
        $this->DATEFIN = ($endOfMonth > $datefin) 
            ? $datefin->format('d.m.Y') 
            : $endOfMonth->format('d.m.Y');
        
        $this->CODEFIN = 'FT';
        
        $finDate = Carbon::createFromFormat('d.m.Y', $this->DATEFIN);
        $this->NBRPOINTS = '000' . str_pad(
            (string)($finDate->day - $dateffet->day + 1), 
            2, 
            '0', 
            STR_PAD_LEFT
        ) . '00';
        
        $this->DATEREPRISE = $this->DATEFFET;
    }

    /**
     * Update from ZDFCAIJNG Line D.
     */
    public function updateFromLineD(Carbon $dateffet, Carbon $datefin): void
    {
        $endOfMonth = $dateffet->copy()->endOfMonth();
        $this->DATEFIN = ($endOfMonth > $datefin) 
            ? $datefin->format('d.m.Y') 
            : $endOfMonth->format('d.m.Y');
        
        $effectDate = Carbon::createFromFormat('d.m.Y', $this->DATEFFET);
        $finDate = Carbon::createFromFormat('d.m.Y', $this->DATEFIN);
        
        $this->NBRPOINTS = '000' . str_pad(
            (string)($finDate->day - $effectDate->day + 1), 
            2, 
            '0', 
            STR_PAD_LEFT
        ) . '00';
    }

    /**
     * Business Logic Methods (from Table class)
     */

    /**
     * Insert recap record.
     */
    public static function insertRecap($sinistre, $recap, $arret, string $class, string $noRegime): void
    {
        $zdfcar = self::where('id_arret', $arret->id)
            ->where('DATEFFET', 'LIKE', '%.' . $recap->date_start->format('m.Y'))
            ->where('REGIME', 'IJ' . $class . str_pad((string)$recap->num_taux, 2, '0', STR_PAD_LEFT))
            ->first();

        if (empty($zdfcar)) {
            $zdfcar = new self();
            $zdfcar->setLine($sinistre, $recap, $arret, $class, $noRegime);
        } else {
            $zdfcar->updateLine($recap, $arret);
        }

        $zdfcar->save();
    }

    /**
     * Refresh all ZDFCAR records.
     */
    public static function refreshAll(): void
    {
        $request = ZDFCAIJNG::where('TYPELIGNE', 'I')
            ->where('actif', 1);
        
        $recordCount = $request->count();
        $current_line = 0;
        $limit = 10;

        while ($current_line < $recordCount) {
            $lines = $request->limit($limit)->offset($current_line)->get();

            foreach ($lines as $line) {
                self::rebuildCarFromLineI($line);
            }
            $current_line += $limit;
        }
    }

    /**
     * Rebuild CAR from Line I.
     */
    public static function rebuildCarFromLineI($lineI, ?int $export_id = null): void
    {
        $linesA = ZDFCAIJNG::where('TYPELIGNE', 'A')
            ->where('actif', 1)
            ->where('num_sinistre', $lineI->num_sinistre);
        
        if ($export_id !== null) {
            $linesA->where('export_id', $export_id);
        }
        
        $linesA = $linesA->get();

        foreach ($linesA as $lineA) {
            self::insertFromLineA($lineA);
        }
    }

    /**
     * Insert from Line A.
     */
    private static function insertFromLineA($lineA): void
    {
        $linesD = ZDFCAIJNG::where('TYPELIGNE', 'D')
            ->where('id_arret', $lineA->id_arret)
            ->where('actif', 1)
            ->orderByRaw("STR_TO_DATE(DATEFFET, '%d.%m.%Y') ASC")
            ->get();

        if ($linesD->count() > 0) {
            foreach ($linesD as $lineD) {
                $current = Carbon::createFromFormat('d.m.Y', $lineD->DATEFFET);
                $dateFin = Carbon::createFromFormat('d.m.Y', $lineD->DATEFINDROIT);

                $regime = 'IJ' . $lineD->CLASSEPAIE . str_pad((string)$lineD->TAUXPAIE, 2, '0', STR_PAD_LEFT);

                while ($current <= $dateFin) {
                    $zdfcar = self::where('id_arret', $lineA->id_arret)
                        ->where('DATEFFET', 'LIKE', '%.' . $current->format('m.Y'))
                        ->where('REGIME', $regime)
                        ->where('actif', 1)
                        ->first();
                    
                    if (empty($zdfcar)) {
                        $zdfcar = new self();
                        $zdfcar->importFromLineD($lineD, $regime, $current->copy(), $dateFin->copy());
                    } else {
                        $zdfcar->updateFromLineD($current->copy(), $dateFin->copy());
                    }
                    
                    $zdfcar->save();
                    $current->addMonth()->startOfMonth(); // Move to next month
                }
            }

            // End of arret -> Modify codefin
            if (isset($zdfcar)) {
                $zdfcar->CODEFIN = 'FD';
                $zdfcar->save();
            }
        }
    }

    /**
     * Rebuild CAR from sinistre.
     */
    public static function rebuildCarFromSinistre(int $num_sinistre, $export): void
    {
        // Deactivate previous versions of sinistre
        $oldLines = self::where('num_sinistre', $num_sinistre)
            ->where(function($query) use ($export) {
                $query->where('export_id', '!=', $export->id)
                      ->orWhereNull('export_id');
            })
            ->get();

        foreach ($oldLines as $line) {
            $line->actif = 0;
            $line->save();
        }

        $ligneI = ZDFCAIJNG::where('TYPELIGNE', 'I')
            ->where('actif', 1)
            ->where('num_sinistre', $num_sinistre)
            ->first();

        if ($ligneI) {
            self::rebuildCarFromLineI($ligneI, $export->id);
        }
    }
}