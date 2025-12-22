<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Services\DetailsArretsService;

/**
 * ZDFCAIJNG Model
 *
 * @property int $id
 * @property string|null $TYPEDF
 * @property string|null $NUMCOTIS
 * @property string|null $SOUSNUMCOTIS
 * @property string|null $CODFAMIL
 * @property string|null $SEQFAMIL
 * @property string|null $LCCOTIS
 * @property string|null $NOCATEGAPR
 * @property string|null $CATEGAPR
 * @property string|null $NOGROUPEINIT
 * @property string|null $NOGROUPESECO
 * @property string|null $NOARRET
 * @property string|null $TYPELIGNE
 * @property string|null $CODEMALADIE
 * @property string|null $CODESPECIALITE
 * @property string|null $DATEDEBARRET
 * @property string|null $DATEFINARRET
 * @property string|null $DATEFFET
 * @property string|null $MOTIFEFFET
 * @property string|null $AGEFFET
 * @property string|null $DATEATTEST
 * @property string|null $DATEATTESTPAIE
 * @property string|null $DATEPROLONG
 * @property string|null $DATEREPTHERA1
 * @property string|null $MOTIFREPTHERA1
 * @property string|null $DATEREPTHERA2
 * @property string|null $MOTIFREPTHERA2
 * @property string|null $DATEALERTE1
 * @property string|null $MOTIFALERTE1
 * @property string|null $DATEALERTE2
 * @property string|null $MOTIFALERTE2
 * @property string|null $DATEALERTE3
 * @property string|null $MOTIFALERTE3
 * @property string|null $DATEALERTE4
 * @property string|null $MOTIFALERTE4
 * @property string|null $DATEFINDROIT
 * @property string|null $MOTIFFINDROIT
 * @property string|null $CLASSEPAIE
 * @property string|null $TAUXPAIE
 * @property string|null $POURCENTCOTIS
 * @property string|null $TOTJOURNONIJ
 * @property string|null $TOTJOURIJ
 * @property string|null $NOREGIME
 * @property string|null $REGIME
 * @property string|null $DATELIMITEPAIE
 * @property string|null $TOTJOURSLIMITEPAIE
 * @property string|null $DATESUSPAIE
 * @property string|null $MOTIFSUSPAIE
 * @property string|null $DATEMAJ
 * @property string|null $INITMAJ
 * @property string|null $MINOPAIE
 * @property string|null $INFREVENUS
 * @property string|null $SERREVENUS
 * @property string|null $RSPM
 * @property string|null $REFORME
 * @property int|null $num_sinistre
 * @property int|null $id_arret
 * @property int|null $export_id
 * @property int|null $actif
 * @property int $version
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ZDFCAIJNG extends Model
{
    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'ZDFCAIJ_NG';

    /**
     * La clé primaire associée à la table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * Format de date par défaut.
     *
     * @var string
     */
    private const DEFAULT_DATE = '01.01.9999';

    /**
     * Les attributs assignables en masse.
     *
     * @var array<string>
     */
    protected $fillable = [
        'TYPEDF',
        'NUMCOTIS',
        'SOUSNUMCOTIS',
        'CODFAMIL',
        'SEQFAMIL',
        'LCCOTIS',
        'NOCATEGAPR',
        'CATEGAPR',
        'NOGROUPEINIT',
        'NOGROUPESECO',
        'NOARRET',
        'TYPELIGNE',
        'CODEMALADIE',
        'CODESPECIALITE',
        'DATEDEBARRET',
        'DATEFINARRET',
        'DATEFFET',
        'MOTIFEFFET',
        'AGEFFET',
        'DATEATTEST',
        'DATEATTESTPAIE',
        'DATEPROLONG',
        'DATEREPTHERA1',
        'MOTIFREPTHERA1',
        'DATEREPTHERA2',
        'MOTIFREPTHERA2',
        'DATEALERTE1',
        'MOTIFALERTE1',
        'DATEALERTE2',
        'MOTIFALERTE2',
        'DATEALERTE3',
        'MOTIFALERTE3',
        'DATEALERTE4',
        'MOTIFALERTE4',
        'DATEFINDROIT',
        'MOTIFFINDROIT',
        'CLASSEPAIE',
        'TAUXPAIE',
        'POURCENTCOTIS',
        'TOTJOURNONIJ',
        'TOTJOURIJ',
        'NOREGIME',
        'REGIME',
        'DATELIMITEPAIE',
        'TOTJOURSLIMITEPAIE',
        'DATESUSPAIE',
        'MOTIFSUSPAIE',
        'DATEMAJ',
        'INITMAJ',
        'MINOPAIE',
        'INFREVENUS',
        'SERREVENUS',
        'RSPM',
        'REFORME',
        'num_sinistre',
        'id_arret',
        'export_id',
        'actif',
        'version',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'num_sinistre' => 'integer',
        'id_arret' => 'integer',
        'export_id' => 'integer',
        'actif' => 'integer',
        'version' => 'integer',
    ];

    /**
     * Relations
     */

    /**
     * Obtenir le sinistre associé à cet enregistrement.
     */
    public function sinistre()
    {
        return $this->belongsTo(IjSinistre::class, 'num_sinistre', 'id');
    }

    /**
     * Obtenir l'arrêt associé à cet enregistrement.
     */
    public function arret()
    {
        return $this->belongsTo(IjArret::class, 'id_arret', 'id');
    }

    /**
     * Obtenir l'export associé à cet enregistrement.
     */
    public function export()
    {
        return $this->belongsTo(Export::class, 'export_id', 'id');
    }

    /**
     * Méthodes Helper
     */

    /**
     * Obtenir le numéro adhérent.
     */
    public function getAdherentNumber(): string
    {
        return $this->NUMCOTIS . $this->LCCOTIS;
    }

    /**
     * Définir le numéro adhérent.
     */
    public function setAdherentNumber(string $adherent_number): void
    {
        $this->NUMCOTIS = substr($adherent_number, 0, -1);
        $this->LCCOTIS = strtoupper(substr($adherent_number, -1));
    }

    /**
     * Définir les informations adhérent.
     */
    public function setAdherentInfos($adherent): void
    {
        $this->setAdherentNumber($adherent->adherent_number);
        $this->TYPEDF = ($adherent->statut == 'CCPL') ? 'CCPL' : 'MEDE';
        $this->CATEGAPR = ($adherent->statut == 'CCPL') ? '050' : '010';
        $this->CODESPECIALITE = empty($adherent->code_specialite) 
            ? '000' 
            : str_pad((string)$adherent->code_specialite, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Définir les valeurs par défaut pour l'entité.
     */
    public function setDefaultValues(): void
    {
        $this->SOUSNUMCOTIS = '00000';
        $this->CODFAMIL = '0';
        $this->SEQFAMIL = '00';
        $this->NOCATEGAPR = '01';
        $this->NOGROUPESECO = '000';
        $this->DATEREPTHERA1 = self::DEFAULT_DATE;
        $this->MOTIFREPTHERA1 = '     '; // 5 espaces
        $this->DATEREPTHERA2 = self::DEFAULT_DATE;
        $this->MOTIFREPTHERA2 = '     '; // 5 espaces
        $this->DATEALERTE1 = self::DEFAULT_DATE;
        $this->MOTIFALERTE1 = '  '; // 2 espaces
        $this->DATEALERTE2 = self::DEFAULT_DATE;
        $this->MOTIFALERTE2 = '  '; // 2 espaces
        $this->DATEALERTE3 = self::DEFAULT_DATE;
        $this->MOTIFALERTE3 = '  '; // 2 espaces
        $this->DATEALERTE4 = self::DEFAULT_DATE;
        $this->MOTIFALERTE4 = '  '; // 2 espaces
        $this->DATELIMITEPAIE = self::DEFAULT_DATE;
        $this->DATESUSPAIE = self::DEFAULT_DATE;
        $this->MOTIFSUSPAIE = '  '; // 2 espaces
        $this->INFREVENUS = '000000000';
        $this->SERREVENUS = '000000000';
        $this->AGEFFET = '00000';
        $this->TOTJOURIJ = '0000';

        // Date de création ou modification
        $this->DATEMAJ = date('d.m.Y');
        $this->INITMAJ = 'TL';
    }

    /**
     * Initialiser la ligne avec les valeurs communes.
     */
    private function initLine($sinistre, $noSinistre): void
    {
        $this->setAdherentInfos($sinistre->adherent);
        $this->setDefaultValues();
        $this->num_sinistre = $sinistre->id;
        $this->CODEMALADIE = empty($sinistre->CODEMALADIE) ? '  ' : $sinistre->CODEMALADIE;
        $this->NOGROUPEINIT = str_pad((string)$noSinistre, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Définir la Ligne I (Sinistre).
     */
    public function setLineI($sinistre, $noSinistre): void
    {
        $this->initLine($sinistre, $noSinistre);
        $this->TYPELIGNE = 'I';
        $this->NOARRET = '000';
        $this->DATEDEBARRET = self::DEFAULT_DATE;
        $this->DATEFINARRET = self::DEFAULT_DATE;
        $this->DATEFFET = self::DEFAULT_DATE;
        $this->MOTIFEFFET = '  ';
        $this->DATEATTEST = self::DEFAULT_DATE;
        $this->DATEATTESTPAIE = self::DEFAULT_DATE;
        $this->DATEPROLONG = self::DEFAULT_DATE;
        $this->DATEFINDROIT = self::DEFAULT_DATE;
        $this->MOTIFFINDROIT = '  ';
        $this->CLASSEPAIE = ' ';
        $this->TAUXPAIE = ' ';
        $this->POURCENTCOTIS = '00000';
        $this->TOTJOURNONIJ = '0090';
        $this->TOTJOURIJ = '1095';
        $this->NOREGIME = '00';
        $this->REGIME = '     ';
        $this->TOTJOURSLIMITEPAIE = '0000';
        $this->MINOPAIE = '00000';
        $this->INFREVENUS = '000000000';
        $this->SERREVENUS = '000000000';
        $this->RSPM = ' ';
        $this->REFORME = '    ';
    }

    /**
     * Définir la Ligne A (Arret).
     */
    public function setLineA($sinistre, $arret, int $noarret, $noSinistre, string $class): void
    {
        $this->initLine($sinistre, $noSinistre);
        $date_deb_droit = empty($arret->date_deb_dr_force) ? $arret->date_deb_droit : $arret->date_deb_dr_force;
        
        $this->id_arret = $arret->id;
        $this->TYPELIGNE = 'A';
        $this->NOARRET = str_pad((string)$noarret, 3, '0', STR_PAD_LEFT);
        $this->DATEDEBARRET = $this->formatDate($arret->date_start);
        $this->DATEFINARRET = $this->formatDate($arret->date_end);
        $this->DATEFFET = $this->formatDate($date_deb_droit);
        $this->MOTIFEFFET = 'DT';
        
        if (isset($sinistre->adherent->date_naissance) && !empty($sinistre->adherent->date_naissance) && !empty($date_deb_droit)) {
            $this->AGEFFET = $this->setAge($sinistre->adherent->date_naissance->diffInYears($date_deb_droit));
        }
        
        $this->DATEATTEST = $this->formatDate($arret->date_dern_attestation);
        
        if (!empty($arret->date_dern_attestation)) {
            $this->DATEATTESTPAIE = ($arret->date_dern_attestation->day > 26) 
                ? $arret->date_dern_attestation->endOfMonth()->format('d.m.Y') 
                : $arret->date_dern_attestation->format('d.m.Y');
        } else {
            $this->DATEATTESTPAIE = self::DEFAULT_DATE;
        }
        
        $this->MOTIFFINDROIT = 'FD';
        
        if (!empty($arret->date_prolongation)) {
            $this->DATEPROLONG = $arret->date_prolongation->format('d.m.Y');
        }
        
        if (!empty($arret->date_reprise_activite)) {
            $this->DATEFINDROIT = $arret->date_reprise_activite->format('d.m.Y');
            $this->MOTIFFINDROIT = 'FM';
        } elseif ($this->formatDate($arret->date_deb_droit) != self::DEFAULT_DATE) {
            $this->DATEFINDROIT = $date_deb_droit->copy()->addDays(1094)->format('d.m.Y');
        } else {
            $this->DATEFINDROIT = self::DEFAULT_DATE;
        }
        
        $this->CLASSEPAIE = $class;
        $this->TAUXPAIE = isset($arret->ij_recap[0]) ? $arret->ij_recap[0]->num_taux : ' ';
        $this->POURCENTCOTIS = str_pad((string)$sinistre->adherent->option, 3, '0', STR_PAD_LEFT) . '00';
        $this->TOTJOURNONIJ = ($noarret == 1) 
            ? '0090' 
            : str_pad((string)$arret->date_start->diffInDays($date_deb_droit), 4, '0', STR_PAD_LEFT);
        $this->TOTJOURIJ = '1095';
        $this->NOREGIME = '00';
        $this->REGIME = '     ';
        $this->TOTJOURSLIMITEPAIE = '0000';
        $this->MINOPAIE = '10000';
        
        if (!empty($date_deb_droit)) {
            $this->INFREVENUS = str_pad(
                (string)$sinistre->adherent->getRevenuByYear((int)$date_deb_droit->format('Y')), 
                9, 
                '0', 
                STR_PAD_LEFT
            );
            $this->SERREVENUS = str_pad(
                (string)$sinistre->adherent->getRevenuByYear((int)$date_deb_droit->format('Y')), 
                9, 
                '0', 
                STR_PAD_LEFT
            );
        }
        
        $this->RSPM = ($sinistre->adherent->statut == 'RSMP') ? 'O' : 'N';
        $this->REFORME = '    ';
    }

    /**
     * Définir la Ligne D (Detail).
     */
    public function setLineD($sinistre, $arret, int $noarret, $date_effet, $date_fin_droit, string $motif_fin_droit, $taux, $noSinistre, string $class, string $noRegime): void
    {
        $this->initLine($sinistre, $noSinistre);
        $date_deb_droit = empty($arret->date_deb_dr_force) ? $arret->date_deb_droit : $arret->date_deb_dr_force;
        
        $this->TYPELIGNE = 'D';
        $this->NOARRET = str_pad((string)$noarret, 3, '0', STR_PAD_LEFT);
        $this->DATEDEBARRET = self::DEFAULT_DATE;
        $this->DATEFINARRET = self::DEFAULT_DATE;
        $this->DATEFFET = $this->formatDate($date_effet);
        $this->MOTIFEFFET = 'DT';
        
        if (isset($sinistre->adherent->date_naissance) && !empty($sinistre->adherent->date_naissance) && !empty($date_deb_droit)) {
            $this->AGEFFET = $this->setAge($sinistre->adherent->date_naissance->diffInYears($date_effet));
        }
        
        $this->DATEATTEST = self::DEFAULT_DATE;
        $this->DATEATTESTPAIE = self::DEFAULT_DATE;
        $this->DATEPROLONG = self::DEFAULT_DATE;
        $this->DATEFINDROIT = $this->formatDate($date_fin_droit);
        $this->MOTIFFINDROIT = $motif_fin_droit;
        $this->CLASSEPAIE = $class;
        $this->TAUXPAIE = $taux;
        $this->POURCENTCOTIS = str_pad((string)$sinistre->adherent->option, 3, '0', STR_PAD_LEFT) . '00';
        $this->TOTJOURNONIJ = '0000';
        
        if (!empty($date_effet) && !empty($date_fin_droit)) {
            $this->TOTJOURIJ = str_pad((string)($date_effet->diffInDays($date_fin_droit) + 1), 4, '0', STR_PAD_LEFT);
        }
        
        $this->NOREGIME = $noRegime;
        $this->REGIME = 'IJ' . $class . str_pad((string)$taux, 2, '0', STR_PAD_LEFT);
        
        if (!empty($date_deb_droit)) {
            $this->INFREVENUS = str_pad(
                (string)$sinistre->adherent->getRevenuByYear((int)$date_deb_droit->format('Y')), 
                9, 
                '0', 
                STR_PAD_LEFT
            );
            $this->SERREVENUS = str_pad(
                (string)$sinistre->adherent->getRevenuByYear((int)$date_deb_droit->format('Y')), 
                9, 
                '0', 
                STR_PAD_LEFT
            );
        }
        
        $this->TOTJOURSLIMITEPAIE = '0000';
        $this->MINOPAIE = '10000';
        $this->RSPM = ($sinistre->adherent->statut == 'RSMP') ? 'O' : 'N';
        $this->REFORME = '    ';
        $this->id_arret = $arret->id;
    }

    /**
     * Reporter la ligne depuis les données existantes.
     */
    public function reportLine($row, int $sinistre_id, ?int $arret_id): void
    {
        $this->TYPEDF = $row->TYPEDF;
        $this->NUMCOTIS = $row->NUMCOTIS;
        $this->SOUSNUMCOTIS = $row->SOUSNUMCOTIS;
        $this->CODFAMIL = $row->CODFAMIL;
        $this->SEQFAMIL = str_pad((string)$row->SEQFAMIL, 2, '0', STR_PAD_LEFT);
        $this->LCCOTIS = $row->LCCOTIS;
        $this->NOCATEGAPR = str_pad((string)$row->NOCATEGAPR, 2, '0', STR_PAD_LEFT);
        $this->CATEGAPR = str_pad((string)$row->CATEGAPR, 3, '0', STR_PAD_LEFT);
        $this->NOGROUPEINIT = str_pad((string)$row->NOGROUPEINIT, 3, '0', STR_PAD_LEFT);
        $this->NOGROUPESECO = str_pad((string)$row->NOGROUPESECO, 3, '0', STR_PAD_LEFT);
        $this->NOARRET = str_pad((string)$row->NOARRET, 3, '0', STR_PAD_LEFT);
        $this->TYPELIGNE = $row->TYPELIGNE;
        $this->CODEMALADIE = str_pad((string)$row->CODEMALADIE, 2, ' ', STR_PAD_RIGHT);
        $this->CODESPECIALITE = $row->CODESPECIALITE;
        $this->DATEDEBARRET = $this->dateFormatFromMFtoNG($row->DATEDEBARRET);
        $this->DATEFINARRET = $this->dateFormatFromMFtoNG($row->DATEFINARRET);
        $this->DATEFFET = $this->dateFormatFromMFtoNG($row->DATEFFET);
        $this->MOTIFEFFET = str_pad((string)$row->MOTIFEFFET, 2, ' ', STR_PAD_LEFT);
        $this->AGEFFET = str_pad((string)str_replace('.', '', $row->AGEFFET), 5, '0', STR_PAD_LEFT);
        $this->DATEATTEST = $this->dateFormatFromMFtoNG($row->DATEATTEST);
        $this->DATEATTESTPAIE = $this->dateFormatFromMFtoNG($row->DATEATTESTPAIE);
        $this->DATEPROLONG = $this->dateFormatFromMFtoNG($row->DATEPROLONG);
        $this->DATEREPTHERA1 = $this->dateFormatFromMFtoNG($row->DATEREPTHERA1);
        $this->MOTIFREPTHERA1 = str_pad((string)$row->MOTIFREPTHERA1, 5, ' ', STR_PAD_LEFT);
        $this->DATEREPTHERA2 = $this->dateFormatFromMFtoNG($row->DATEREPTHERA2);
        $this->MOTIFREPTHERA2 = str_pad((string)$row->MOTIFREPTHERA2, 5, ' ', STR_PAD_LEFT);
        $this->DATEALERTE1 = $this->dateFormatFromMFtoNG($row->DATEALERTE1);
        $this->MOTIFALERTE1 = str_pad((string)$row->MOTIFALERTE1, 2, ' ', STR_PAD_LEFT);
        $this->DATEALERTE2 = $this->dateFormatFromMFtoNG($row->DATEALERTE2);
        $this->MOTIFALERTE2 = str_pad((string)$row->MOTIFALERTE2, 2, ' ', STR_PAD_LEFT);
        $this->DATEALERTE3 = $this->dateFormatFromMFtoNG($row->DATEALERTE3);
        $this->MOTIFALERTE3 = str_pad((string)$row->MOTIFALERTE3, 2, ' ', STR_PAD_LEFT);
        $this->DATEALERTE4 = $this->dateFormatFromMFtoNG($row->DATEALERTE4);
        $this->MOTIFALERTE4 = str_pad((string)$row->MOTIFALERTE4, 2, ' ', STR_PAD_LEFT);
        $this->DATEFINDROIT = $this->dateFormatFromMFtoNG($row->DATEFINDROIT);
        $this->MOTIFFINDROIT = str_pad((string)$row->MOTIFFINDROIT, 2, ' ', STR_PAD_LEFT);
        $this->CLASSEPAIE = str_pad((string)$row->CLASSEPAIE, 1, ' ', STR_PAD_LEFT);
        $this->TAUXPAIE = str_pad((string)$row->TAUXPAIE, 1, ' ', STR_PAD_LEFT);
        $this->POURCENTCOTIS = str_pad(str_replace('.', '', $row->POURCENTCOTIS), 5, '0', STR_PAD_LEFT);
        $this->TOTJOURNONIJ = str_pad((string)$row->TOTJOURNONIJ, 4, '0', STR_PAD_LEFT);
        $this->TOTJOURIJ = str_pad((string)$row->TOTJOURIJ, 4, '0', STR_PAD_LEFT);
        $this->NOREGIME = str_pad((string)$row->NOREGIME, 2, '0', STR_PAD_LEFT);
        $this->REGIME = str_pad((string)$row->REGIME, 5, ' ', STR_PAD_LEFT);
        $this->DATELIMITEPAIE = $this->dateFormatFromMFtoNG($row->DATELIMITEPAIE);
        $this->TOTJOURSLIMITEPAIE = str_pad((string)$row->TOTJOURSLIMITEPAIE, 4, '0', STR_PAD_LEFT);
        $this->DATESUSPAIE = $this->dateFormatFromMFtoNG($row->DATESUSPAIE);
        $this->MOTIFSUSPAIE = str_pad((string)$row->MOTIFSUSPAIE, 2, ' ', STR_PAD_LEFT);
        $this->DATEMAJ = $this->dateFormatFromMFtoNG($row->DATEMAJ);
        $this->INITMAJ = $row->INITMAJ;
        $this->MINOPAIE = str_pad(str_replace('.', '', $row->MINOPAIE), 5, '0', STR_PAD_LEFT);
        $this->INFREVENUS = str_pad((string)$row->INFREVENUS, 9, '0', STR_PAD_LEFT);
        $this->SERREVENUS = str_pad((string)$row->SERREVENUS, 9, '0', STR_PAD_LEFT);
        $this->RSPM = str_pad((string)$row->RSPM, 1, ' ', STR_PAD_LEFT);
        $this->REFORME = str_pad((string)$row->REFORME, 4, ' ', STR_PAD_LEFT);
        $this->num_sinistre = $sinistre_id;
        $this->id_arret = $arret_id;
    }

    /**
     * Méthodes helper privées
     */

    /**
     * Définir l'âge avec le formatage approprié.
     */
    private function setAge(int $age): string
    {
        $age = str_pad((string)$age, 3, '0', STR_PAD_LEFT) . '00';

        // Contrôler les données MF erronées
        if ($age > 13000) {
            $age = '00000';
        }

        return $age;
    }

    /**
     * Formater la date pour la sortie.
     */
    private function formatDate($date): string
    {
        if (empty($date)) {
            return self::DEFAULT_DATE;
        }

        return $date->format('d.m.Y');
    }

    /**
     * Convertir le format de date de MF à NG.
     */
    private function dateFormatFromMFtoNG($date): string
    {
        return $date->format('d.m.Y');
    }

    /**
     * Méthodes de Logique Métier (depuis la classe Table)
     */

    /**
     * Insérer un sinistre avec toutes les données associées.
     */
    public static function insertSinistre(int $num_sinistre, $export = null)
    {
        $sinistre = IjSinistre::with(['recaps', 'arrets.ijRecap', 'adherent'])
            ->findOrFail($num_sinistre);
        if (!empty($export)) {
            $currentExport = $export;
        } else {
            $currentExport = Export::getCurrentExport();
        }



        $lineI = self::where('num_sinistre', $num_sinistre)
            ->where('TYPELIGNE', 'I')
            ->where('export_id', $currentExport->id)
            ->first();

        // Ligne non présente ou déjà exportée
        if (empty($lineI)) {
            // Sinistre créé par moteur_IJ
            if (empty($sinistre->NOGROUPEINIT)) {
                $noSinistre = self::getSinistreCount($sinistre->adherent) + 1;
                $sinistre->NOGROUPEINIT = $noSinistre;
                $sinistre->save();
                $lineI = new self();
            } else {
                // Sinistre créé par import MF ou déjà exporté
                $noSinistre = $sinistre->NOGROUPEINIT;
                $lineI = new self();
            }
        } else {
            // Mettre à jour la ligne et réinitialiser les lignes A/D et Zdfcar associés
            $noSinistre = $sinistre->NOGROUPEINIT;
            self::resetSinistre($lineI, $currentExport);
        }

        // LIGNE I = SINISTRE
        $lineI->setLineI($sinistre, $noSinistre);
        $lineI->export_id = $currentExport->id;
        $lineI->save();

        // Compteur d'arrêts
        $k = 0;
        $totIJI = 0;
        $lineA = null;

        // LIGNES A = ARRET
        foreach ($sinistre->arrets as $arret) {
            if ($arret->actif != '1') {
                continue;
            }
            
            $totIJA = 0;
            $k++;
            $lineA = new self();
            $lineA->export_id = $currentExport->id;
            $arret->NOARRET = $k;
            $arret->save();
            
            $currentTaux = null;
            $currentDateEffet = null;
            $lastDateFin = null;
            $motif_fin_droit = 'FD';
            $currentAgeEffet = null;
            $birthdate = $sinistre->adherent->date_naissance;

            $recaps = collect($arret->ij_recap)->sortBy('date_start');

            if ($recaps->count() > 0) {
                $class = self::getClass($arret, $recaps->first()->classe);
            } else {
                $class = self::getClass($arret);
            }
            $currentClass = $class;

            $lineA->setLineA($sinistre, $arret, $k, $noSinistre, $class);
            $lineA->save();

            $last = $recaps->last();
            $lineD = null;

            foreach ($recaps as $key => $recap) {
                if (!isset($currentTaux)) {
                    $currentTaux = $recap->num_taux;
                }

                if (!isset($currentClass)) {
                    $currentClass = self::getClass($arret, $recap->classe);
                }

                if (!isset($currentDateEffet)) {
                    $currentDateEffet = $recap->date_start;
                }

                if (!isset($lastDateFin)) {
                    $lastDateFin = $recap->date_end;
                }

                if (!isset($currentAgeEffet)) {
                    $currentAgeEffet = $recap->personne_age;
                }

                $noRegime = self::getNoRegime($class, $currentTaux);

                if ($recap->personne_age + 3 >= 62 && $recap->personne_age != $currentAgeEffet) {
                    try {
                        $year = $recap->date_start->year;
                        $birthday = Carbon::createFromDate($year, $birthdate->month, $birthdate->day);
                    } catch (\Exception $e) {
                        // Cas du 29 février → utiliser le 28 février dans une année non bissextile
                        $birthday = Carbon::parse($year . '-02-28');
                    }
                    
                    $date_fin_droit = $birthday->copy()->subDay();
                    $motif_fin_droit = 'FT';
                    $lineD = new self();
                    $lineD->setLineD($sinistre, $arret, $k, $currentDateEffet, $date_fin_droit, $motif_fin_droit, $currentTaux, $noSinistre, $currentClass, $noRegime);
                    $lineD->export_id = $currentExport->id;
                    $totIJA += (int)$lineD->TOTJOURIJ;
                    $lineD->save();
                    $currentDateEffet = $birthday;
                    $currentAgeEffet = null;
                    $motif_fin_droit = 'FD';
                    $currentTaux = null;
                    $currentClass = null;

                    continue;
                }

                // Changement de taux ou dernier recap -> insérer la ligne
                if ($currentTaux != $recap->num_taux || $currentClass != self::getClass($arret, $recap->classe) || $recap == $last) {
                    $lineD = new self();
                    $lineD->setLineD($sinistre, $arret, $k, $currentDateEffet, $recap->date_end, $motif_fin_droit, $currentTaux, $noSinistre, $currentClass, $noRegime);
                    $totIJA += (int)$lineD->TOTJOURIJ;
                    $lineD->export_id = $currentExport->id;
                    $lineD->save();
                    $currentDateEffet = $recap->date_start;
                    $currentTaux = $recap->num_taux;
                    $currentAgeEffet = null;
                    $lastDateFin = null;
                    $currentClass = self::getClass($arret, $recap->classe);
                    $motif_fin_droit = 'FD';
                }
            }

            if ($lineA->MOTIFFINDROIT == 'FM' && !empty($lineD)) {
                $lineD->MOTIFFINDROIT = 'FM';
            }
            
            $lineA->TOTJOURIJ = str_pad((string)$totIJA, 4, '0', STR_PAD_LEFT);
            
            if (!empty($lineD)) {
                $lineA->DATEFINDROIT = $lineD->DATEFINDROIT;
            }
            
            $lineA->save();
            $totIJI += $totIJA;
        }

        $lineI->TOTJOURIJ = str_pad((string)$totIJI, 4, '0', STR_PAD_LEFT);
        $lineI->save();

        // Désactiver les versions précédentes du sinistre pour ne pas les exporter
        $oldLines = self::where('num_sinistre', $num_sinistre)
            ->where(function($query) use ($currentExport) {
                $query->where('export_id', '!=', $currentExport->id)
                      ->orWhereNull('export_id');
            })
            ->get();

        foreach ($oldLines as $line) {
            $line->actif = 0;
            $line->save();
        }

        ZDFCARNG::rebuildCarFromSinistre($num_sinistre, $currentExport);
    }

    /**
     * Annuler l'export du sinistre.
     */
    public static function cancelSinistreExport(int $num_sinistre): bool
    {
        $exportWaitingRetourMf = Export::getLastExportNotIntegrated();
        
        if (!empty($exportWaitingRetourMf)) {
            if (self::where('export_id', $exportWaitingRetourMf->id)
                ->where('num_sinistre', $num_sinistre)
                ->exists()) {
                return false;
            }
        }

        $currentExport = Export::getLastExportNotExported();
        
        if (!empty($currentExport)) {
            self::where('export_id', $currentExport->id)
                ->where('num_sinistre', $num_sinistre)
                ->delete();
            
            ZDFCARNG::where('export_id', $currentExport->id)
                ->where('num_sinistre', $num_sinistre)
                ->delete();
        }

        return true;
    }

    /**
     * Obtenir le nombre de sinistres pour un adhérent.
     */
    private static function getSinistreCount($adherent): int
    {
        $NUMCOTIS = substr($adherent->adherent_number, 0, -1);
        $LCCOTIS = strtoupper(substr($adherent->adherent_number, -1));

        return self::where('TYPELIGNE', 'I')
            ->where('NUMCOTIS', $NUMCOTIS)
            ->where('LCCOTIS', $LCCOTIS)
            ->count();
    }

    /**
     * Obtenir la classe pour l'arrêt.
     */
    private static function getClass($arret, ?string $forceClass = null): string
    {
        if (empty($forceClass)) {
            $classe = (new DetailsArretsService())->getArretClasse($arret);
        } else {
            $classe = $forceClass;
        }

        $mapping = ['A' => 'D', 'B' => 'E', 'C' => 'F'];

        if ($arret->date_start >= Carbon::parse('2025-01-01')) {
            $classe = $mapping[$classe] ?? $classe;
        }

        if (empty($classe)) {
            $classe = ' ';
        }

        return $classe;
    }

    /**
     * Obtenir le numéro de régime.
     */
    private static function getNoRegime(string $class, int $taux): string
    {
        if (empty($class) || $class == ' ') {
            return '00';
        }

        $numRegimeClasse = [];
        foreach (range('A', 'F') as $index => $lettre) {
            $numRegimeClasse[$lettre] = ($index + 1) * 10;
        }

        return (string)($numRegimeClasse[$class] + ($taux - 1));
    }

    /**
     * Réinitialiser les données du sinistre.
     */
    private static function resetSinistre($lineI, $export): void
    {
        $lines = self::where('TYPELIGNE', '<>', 'I')
            ->where('num_sinistre', $lineI->num_sinistre)
            ->where('export_id', $export->id)
            ->get();

        foreach ($lines as $line) {
            if (!empty($line->id_arret)) {
                ZDFCARNG::where('id_arret', $line->id_arret)->delete();
            }
            $line->delete();
        }
    }

    /**
     * Obtenir la synthèse par export.
     */
    public static function getSyntheseByExport(int $export_id)
    {
        return self::where('TYPELIGNE', 'A')
            ->where('export_id', $export_id)
            ->get()
            ->map(function($l) {
                return [
                    'adh' => $l->getAdherentNumber(),
                    'classe' => $l->CLASSEPAIE,
                    'taux' => $l->TAUXPAIE,
                    'datedeb' => $l->DATEDEBARRET,
                    'datefin' => $l->DATEFINARRET,
                    'noarret' => $l->NOARRET,
                    'nogroupeinit' => $l->NOGROUPEINIT,
                ];
            });
    }
}