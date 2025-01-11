<?php

/**
 * @package WickedTeamBirthdays
 * @author  Heinl Christian <heinchrs@gmail.com>
 * @copyright  (C) 2015-2025 Heinl Christian
 * @license GNU General Public License version 2 or later
 */


// -- No direct access
defined('_JEXEC') or die;

// Prüfen ob Komponente WickedTeam installiert ist
if (JComponentHelper::isEnabled('com_wickedteam', true))
{
	// Include the helper-php
	require_once dirname(__FILE__) . DS . 'helper.php';

	// Get data
	$data = WickedTeamBirthdays::getBirthdays($params);

	// Show formular data
	require JModuleHelper::getLayoutPath('mod_wickedteam_birthdays');

	// Include CSS
	if ($params->get('load_css') == 1)
	{
		JHTML::_('stylesheet', 'media/mod_wickedteam_birthdays/css/mod_wickedteam_birthdays.css');
	}
}
else
{
	// Report error
	echo 'Komponente WickedTeam konnte nicht gefunden werden';
}
