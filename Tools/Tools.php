<?php

namespace App\Tools;

use Cake\Log\Log;
use DateTime;

class Tools {

	public function convertdates($date) {
		if ($date === null) {
			return;
		}

		return date('d/m/Y', strtotime($date));
	}

	public static function diffDateDays($start, $end, $offset = 1) {
		$startDate = new DateTime($start);
		$endDate = new DateTime($end);

		return $startDate->diff($endDate)->days + $offset;
	}

	/**
	 * @return void
	 */
	public static function messageLog($categorie, $message, $type = 'info') {
		$categorie = implode('', array_map(function ($data) {
			return "[$data]";
		}, $categorie));
		$msgs = $categorie . ' : ' . $message;
		Log::$type($msgs);
	}

	public static function isAboveTwoMonthsOneDay($date1, $date2) {
		// Convert to DateTime objects if they aren't already
		$dt1 = $date1 instanceof DateTime ? $date1 : new DateTime($date1);
		$dt2 = $date2 instanceof DateTime ? $date2 : new DateTime($date2);

		// Get the difference
		$interval = $dt1->diff($dt2);

		// Calculate total months and days
		$totalMonths = ($interval->y * 12) + $interval->m;
		$days = $interval->d;

		// Check if more than 2 months and 1 day
		if ($totalMonths > 2) {
			return true;
		}
		if ($totalMonths == 2 && $days > 1) {
			return true;
		}

		return false;
	}

	public static function renommerCles(array $donnees, array $correspondance): array {
		return array_map(function ($objet) use ($correspondance) {
			$tableau = is_object($objet) ? $objet->toArray() : (array)$objet;
			foreach ($correspondance as $cle => $valeur) {
				if (!array_key_exists($cle, $tableau)) {
					continue;
				}

				$tableau[$valeur] = is_object($tableau[$cle]) ? $tableau[$cle]->format('Y-m-d') : $tableau[$cle];
				unset($tableau[$cle]);
			}

			return $tableau;
		}, $donnees);
	}

	public static array $correspondance = [
		'debutArret' => 'arret-from-line',
		'finArret' => 'arret-to-line',
		'date_start' => 'arret-from-line',
		'date_end' => 'arret-to-line',
		'date_declaration' => 'declaration-date-line',
		'DT_excused' => 'dt-line',
		'date_deb_droit' => 'ouverture-date-line',
		'date-effet' => 'ouverture-date-line',
		'code_pathologie' => 'code-patho-line',
		'date_deb_dr_force' => 'date-deb-dr-force',
		'date_prolongation' => 'date-prolongation',
		'date_naissance' => 'birth_date',
		'adherent_number' => 'adherent-number',
	];

}
