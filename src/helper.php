<?php

/**
 * @package    WickedTeamBirthdays
 * @author     Heinl Christian <heinchrs@gmail.com>
 * @copyright  (C) 2015-2025 Heinl Christian
 * @license    GNU General Public License version 2 or later
 */

// -- No direct access
defined('_JEXEC') or die;

/**
 * Helper class to extract birth dates of the corresponding
 * WickedTeam members.
 *
 * @author  Heinl Christian <heinchrs@gmail.com>
 * @since   1.0
 */
class WickedTeamBirthdays
{
	/**
	 * Get birthday information from WickedTeam members
	 *
	 * @param   type   $params   Module parameters
	 * @return  array
	 */
	static public function getBirthdays($params)
	{
		// Parameter
		$anzahlGeburtstage = (int) $params->get('anzahl_geburtstage');
		$nurAktuellerMonat = $params->get('nur_geburtstage_von_monat');
		$tageInZukunft = (int) $params->get('anzahl_tage_in_zukunft');
		$inactiveGroupIds = $params->get('wickedteam_groups');
		$paramDebug = (int) $params->get('debug');

		// Set Debug
		$debug = ($paramDebug == 1) ? true : false;

		if ($debug)
		{
			$df = fopen(dirname(__FILE__) . DS . 'debug.txt', 'w');
			fwrite($df, "Start Debugging:\n");
		}

		// Database variables
		$wickedteamFieldsTable = '#__wickedteam_fields';
		$wickedteamFieldsValuesTable = '#__wickedteam_member_field_values';

		// WickedTeam field id of birthday field in table jos_wickedteam_fields
		$wickedteamBirthdayId = 3;

		// Debug:
		if ($debug)
		{
			fwrite($df, "### PARAMETER ##########\n");
			fwrite($df, "Parameter Anazhl Geburtstage: " . $anzahlGeburtstage . "\n");
			fwrite($df, "Parameter Nur Aktueller Monat: " . $nurAktuellerMonat . "\n");
			fwrite($df, "Parameter Tage in Zukunft: " . $tageInZukunft . "\n");
		}

		// Calculations of current timestamp plus number of days in seconds
		$timestampLimit = time() + ($tageInZukunft * 24 * 60 * 60);

		// Open datenbase connection
		$db = JFactory::getDbo();

		// Get format of birthdays field in table jos_wickedteam_fields
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->clear();
		$query->select('options');
		$query->from($wickedteamFieldsTable);
		$query->where('id = ' . $wickedteamBirthdayId);

		// Limit Query to 1
		$db->setQuery($query, 0, 1);
		$result = $db->loadResult();

		// Remove possible / and \ from result
		$result = stripcslashes($result);

		// Decode JSON Format, Bereite Reihenfolge der Liste vor die zur Altersberechnung benötigt wird
		$jsonDecode = json_decode($result);

		// BSP: %d.%m.%Y
		$birthdayFormat = $jsonDecode->{'format'};

		// %-Zeichen entfernen
		$birthdayFormat = str_replace('%', '', $birthdayFormat);
		$birthdayFormatArray = explode('.', $birthdayFormat);

		// Debug:
		if ($debug)
		{
			fwrite($df, "\n### DATE FORMAT ##########\n");
			fwrite($df, "Result: " . print_r($result, true) . "\n");
			fwrite($df, "Format: " . $birthdayFormat . "\n");
			fwrite($df, "Format Array: " . print_r($birthdayFormatArray, true) . "\n");
		}

		// Hole Geburtstage
		$query = $db->getQuery(true);
		$query->clear();
		$query->select('f.value, g.title, GROUP_CONCAT(c.catid SEPARATOR ",") as Kategorie');
		$query->from('' . $wickedteamFieldsValuesTable . ' AS f');
		$query->leftjoin('#__wickedteam_member_category AS c ON c.member_id = f.member_id');
		$query->leftjoin('#__wickedteam_members AS g ON g.id = f.member_id');
		$query->where('f.field_id = ' . $wickedteamBirthdayId . '');
		$query->where('g.published = 1');
		$query->group('f.member_id');
		$db->setQuery($query);
		$result = $db->loadAssoclist();

		// Debug:
		if ($debug)
		{
			fwrite($df, "\n### GEBURTSTAGE ##########\n");
			fwrite($df, "Database Result: " . print_r($result, true) . "\n");
		}

		// Alter ermitteln, Daten aussortieren, Arrayeinträge definieren
		foreach ($result as $key => $value)
		{
			// Datenbank Wert splitten
			$databaseValueArray = explode('.', $value['value']);

			// Datenbankwert zuordnen unter Verwendung der Formatierung
			foreach ($birthdayFormatArray as $key2 => $dateValue)
			{
				// Tag zuordnen
				if ($dateValue == 'd')
				{
					$day = $databaseValueArray[$key2];
				}

				// Monat zuordnen
				if ($dateValue == 'm')
				{
					$month = $databaseValueArray[$key2];
				}

				// Jahr zuordnen
				if ($dateValue == 'Y')
				{
					$year = $databaseValueArray[$key2];
				}
			}

			// Aktuelles Alter berechnen
			$yearDifference  = date('Y') - $year;
			$monthDifference = date('m') - $month;
			$dayDifference   = date('d') - $day;

			if (($dayDifference <= 0 && $monthDifference == 0) || $monthDifference < 0)
			{
				$yearDifference--;
			}

			// Alter um eins erhöhen, da Funktion aktuelles Alter ausgibt
			$yearDifference++;

			// Zu Array hinzufügen
			$result[$key]['wird_x_jahre'] = $yearDifference;

			// Tag, Monat, Jahr hinzufügen um damit zu arbeiten
			$result[$key]['jahr'] = $year;
			$result[$key]['monat'] = $month;
			$result[$key]['tag'] = $day;

			// Zeitstempel 23:59:59 Uhr, 14.09.2013 z.B.
			$result[$key]['timestamp'] = mktime(23, 59, 59, $month, $day, date('Y'));

			/*
			 * Geburtstage filtern
			 * Alle Timestamps die nicht im vorgegebenen Rahmen liegen, werden hier entfernt.
			 * Konkret: Alles was in der Vergangenheit liegt und alles was über die definierten Tage hinaus in der Zukunft liegt
			 */
			if ($result[$key]['timestamp'] < time() || $result[$key]['timestamp'] > $timestampLimit)
			{
				unset($result[$key]);
			}

			// Wenn gewünscht alle Geburtstage löschen die nicht im aktuellen Monat liegen
			if ($nurAktuellerMonat == 1)
			{
				if ($result[$key]['monat'] != date('m'))
				{
					unset($result[$key]);
				}
			}

			 // Get array of all categories the member is participated
			 $categories = explode(",", $value['Kategorie']);

			foreach ($categories as $category)
			{
				// Check if the category is in the set of categories which should not be displayed
				if (in_array($category, $inactiveGroupIds))
				{
					unset($result[$key]);
				}
			}
		}

		// Debug:
		if ($debug)
		{
			fwrite($df, "\n### AFTER FOREACH ##########\n");
			fwrite($df, "Result: " . print_r($result, true) . "\n");
		}

		// Array sorieren, kürzen, return vorbereiten
		if (count($result) > 0)
		{
			// Geburtstage die nicht durch das Raster gefallen sind werden nun sortiert
			foreach ($result as $key => $row)
			{
				$tag[$key]   = $row['tag'];
				$monat[$key] = $row['monat'];
			}

			array_multisort($monat, SORT_ASC, $tag, SORT_ASC, $result);

			// Array nun noch an die Anzahl der gewünschten Geburtstage anpassen
			$finalArray = array_slice($result, 0, $anzahlGeburtstage);
		}
		else
		{
			$finalArray = array();
		}

		// Debug:
		if ($debug)
		{
			fwrite($df, "\n### FINAL ARRAY ##########\n");
			fwrite($df, "Result: " . print_r($finalArray, true) . "\n");
		}

		// Close Debugfile
		if ($debug)
		{
			fclose($df);
		}

		// Rückgabe
		return $finalArray;
	}
}
