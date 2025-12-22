<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Modèle ZDFCARNG
 *
 * Représente les enregistrements de carrière/cotisation pour l'export
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
     * La table associée au modèle
     *
     * @var string
     */
    protected $table = 'ZDFCAR_NG';

    /**
     * La clé primaire associée à la table
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Les attributs assignables en masse
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
     * Les attributs qui doivent être castés
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
     * Valeurs d'attributs par défaut
     *
     * @var array
     */
    protected $attributes = [
        'actif' => 1,
    ];

    /**
     * Relations
     */

    /**
     * Obtenir l'arrêt associé à cet enregistrement
     */
    public function arret()
    {
        return $this->belongsTo(IjArret::class, 'id_arret', 'id');
    }

    /**
     * Obtenir le sinistre associé à cet enregistrement
     */
    public function sinistre()
    {
        return $this->belongsTo(IjSinistre::class, 'num_sinistre', 'id');
    }

    /**
     * Obtenir l'export associé à cet enregistrement
     */
    public function export()
    {
        return $this->belongsTo(Export::class, 'export_id', 'id');
    }

    /**
     * Méthodes Helper
     */

    /**
     * Définir le numéro d'adhérent
     */
    public function setAdherentNumber(string $adherent_number): void
    {
        $this->NUMCOTIS = substr($adherent_number, 0, -1);
        $this->LCCOTIS = strtoupper(substr($adherent_number, -1));
    }

    /**
     * Définir les informations de l'adhérent
     */
    public function setAdherentInfos($adherent): void
    {
        $this->setAdherentNumber($adherent->adherent_number);
        $this->TYPEDF = ($adherent->statut == 'CCPL') ? 'CCPL' : 'MEDE';
        $this->CATEGAPR = ($adherent->statut == 'CCPL') ? '050' : '010';
    }

    /**
     * Définir les valeurs par défaut pour l'entité
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
     * Initialiser la ligne avec les valeurs communes
     */
    public function initLine($sinistre): void
    {
        $this->setAdherentInfos($sinistre->adherent);
        $this->setDefaultValues();
        $this->num_sinistre = $sinistre->id;

        // Obtenir le code pathologie du premier arrêt
        $arrets = $sinistre->arrets;
        if ($arrets && $arrets->count() > 0) {
            $this->CODEMALADIE = $arrets->first()->code_pathologie;
        }
    }

    /**
     * Définir les valeurs depuis la ligne ZDFCAIJNG
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
     * Définir la ligne depuis les données recap
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
     * Mettre à jour la ligne avec les données recap
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
     * Importer depuis la ligne D de ZDFCAIJNG
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
     * Mettre à jour depuis la ligne D de ZDFCAIJNG
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
     * Méthodes de Logique Métier (depuis la classe Table)
     */

    /**
     * Insérer l'enregistrement recap
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
     * Rafraîchir tous les enregistrements ZDFCAR
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
     * Reconstruire le CAR depuis la ligne I
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
     * Insérer depuis la ligne A
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
                    $current->addMonth()->startOfMonth(); // Passer au mois suivant
                }
            }

            // Fin de l'arrêt -> Modifier codefin
            if (isset($zdfcar)) {
                $zdfcar->CODEFIN = 'FD';
                $zdfcar->save();
            }
        }
    }

    /**
     * Reconstruire le CAR depuis le sinistre
     */
    public static function rebuildCarFromSinistre(int $num_sinistre, $export): void
    {
        // Désactiver les versions précédentes du sinistre
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