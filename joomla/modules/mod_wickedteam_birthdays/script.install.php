<?php

/**
 * Helper class for doing jobs during the installation of this module
 *
 * @author     Heinl Christian <heinchrs@gmail.com>
 * @copyright  (C) 2015-2025 Heinl Christian
 * @license    GNU General Public License version 2 or later
 * @since   1.0
 */

class Mod_WickedTeam_BirthdaysInstallerScript
{
	/**
	 * The directory containing the extension files
	 * @var string
	 */
	private $dir           = null;

	/**
	 * The name of the extension
	 * @var string
	 */
	private $name          = '';

	/**
	 * The joomla type of the extension
	 * @var string
	 */
	private $extensionType    = 'module';

	/**
	 * Joomla folder where system plugins are located
	 * @var string
	 */
	private $pluginFolder     = 'system';

	/**
	 * The joomla alias name of the extension
	 * @var string
	 */
	private $alias             = 'wickedteam_birthdays';

	/**
	 * The joomla name of the extension without preceeding mod_, com_ or plg_ prefix
	 * @var string
	 */
	private $extname           = 'wickedteam_birthdays';

	/**
	 * The current version of the installed extension
	 * @var string
	 */
	private $installedVersion = '';

	/**
	 * The type of the installation (install or update)
	 * @var string
	 */
	private $installType      = 'install';

	/**
	 * Flag if informations should be shown
	 * @var boolean
	 */
	private $showMessage      = true;

	/**
	 * The joomla database
	 * @var string
	 */
	private $db               = null;

	/**
	 * The joomla main version
	 * @var integer
	 */
	private $jversion               = null;


	/**
	 * Constructor
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 */
	public function __construct(JAdapterInstance $adapter)
	{
		// Get dir where this extension is installed from -> upload directory
		$this->dir = __DIR__;

		// Get detailed extension name out of changelog text file via regex @package
		$this->name = $this->getNameFromChangelog();
		$this->extname = $this->extname ?: $this->alias;
		$this->db      = JFactory::getDbo();

		// Get the Joomla main version (first number infornt of the point)
		$this->jversion = (int) JVERSION;
	}

	/**
	 * Called before any type of action
	 *
	 * @param   string            $route    Which action is happening (install|uninstall|discover_install|update)
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function preflight($route, JAdapterInstance $adapter)
	{
		if (!in_array($route, array('install', 'update')))
		{
			return true;
		}

		// Get current version of the installed extension
		$this->installedVersion = $this->getVersion($this->getInstalledXMLFile());

		if ($this->showMessage && $this->isInstalled())
		{
			$this->installType = 'update';
		}

		/*
		print("Installed XML file: " . $this->getInstalledXMLFile());
		print("<br>Installed version: " . $this->installedVersion);
		print("<br>Installed: " . $this->installType);
		print("<br>dir: " . $this->dir);
		// die();
		*/

		return true;
	}

	/**
	 * Called after any type of action
	 *
	 * @param   string            $route    Which action is happening (install|uninstall|discover_install|update)
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function postflight($route, JAdapterInstance $adapter)
	{
		if (!in_array($route, array('install', 'update')))
		{
			return true;
		}

		$changelog = $this->getChangelog();

		JFactory::getApplication()->enqueueMessage($changelog, 'notice');

		return true;
	}

	/**
	 * Called on installation
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	//public function install(JAdapterInstance $adapter);

	/**
	 * Called on update
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	//public function update(JAdapterInstance $adapter);

	/**
	 * Called on uninstallation
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 */
	//public function uninstall(JAdapterInstance $adapter);


	/**
	 * Get name of extension from changelog file
	 *
	 * @return  string
	 */
	private function getNameFromChangelog()
	{
		$changelog = file_get_contents($this->dir . '/CHANGELOG.txt');

		if (!preg_match('# \* @package\s*(.*)#', $changelog, $match))
		{
			return '';
		}

		return $match[1];
	}

	/**
	 * Get changelog information from changelog file
	 *
	 * @return  array
	 */
	private function getChangelog()
	{
		$changelog = file_get_contents($this->dir . '/CHANGELOG.txt');

		// Delete comment block at beginning of the file
		$changelog = "\n" . trim(preg_replace('#^.* \*/#s', '', $changelog));
		$changelog = preg_replace("#\r#s", '', $changelog);

		// Sections are divided by two new line characters
		$parts = explode("\n\n", $changelog);

		if (empty($parts))
		{
			return '';
		}

		$changelog = array();

		// Add first entry to the changelog
		$changelog[] = array_shift($parts);

		$thisVersion = '';

		if (preg_match('#^[0-9]+-[a-z]+-[0-9]+ : v([0-9\.]+(?:-dev[0-9]+)?)\n#i', trim($changelog[0]), $match))
		{
			$thisVersion = $match[1];
		}

		// Check for all other changelog entries in the CHANGELOG.txt file if they have to be displayed
		foreach ($parts as $part)
		{
			$part = trim($part);

			// Check if the entry has a specific format
			if (! preg_match('#^[0-9]+-[a-z]+-[0-9]+ : v([0-9\.]+(?:-dev[0-9]+)?)\n#i', $part, $match))
			{
				continue;
			}

			$changelogVersion = $match[1];

			if (version_compare($changelogVersion, $this->installedVersion, '<='))
			{
				break;
			}

			$changelog[] = $part;
		}

		$badgeClasses = array(
			'default' => $this->jversion >= 3 ? 'label label-sm label-default' : 'badge badge-secondary',
			'success' => $this->jversion >= 3 ? 'label label-sm label-success' : 'badge text-white bg-success',
			'info'    => $this->jversion >= 3 ? 'label label-sm label-info' : 'badge text-white bg-info',
			'warning' => $this->jversion >= 3 ? 'label label-sm label-warning' : 'badge text-white bg-warning',
			'danger'  => $this->jversion >= 3 ? 'label label-sm label-important' : 'badge text-white bg-danger',
		);

		$changelog = implode("\n\n", $changelog);

		//  + Added   ! Removed   ^ Changed   # Fixed
		$changeTypes = array(
			'+' => array('title' => 'Added', 'class' => $badgeClasses['success']),
			'^' => array('title' => 'Changed', 'class' => $badgeClasses['info']),
			'#' => array('title' => 'Fixed', 'class' => $badgeClasses['warning']),
			'!' => array('title' => 'Removed', 'class' => $badgeClasses['danger']),
		);

		// Format the change types in HTML output
		foreach ($changeTypes as $char => $type)
		{
			$changelog = preg_replace(
				'#\n ' . preg_quote($char, '#') . ' #',
				"\n" . '<span class="' . $type['class'] . '" title="' . $type['title'] . '">' . $char . '</span> ',
				$changelog
			);
		}

		// Extract note
		$note = '';

		if (preg_match('#\n > (.*?)\n#s', $changelog, $match))
		{
			$note      = $match[1];
			$changelog = str_replace($match[0], "\n", $changelog);
		}
		print("<pre>");
		print_r($changelog);
		/*
		die();
		*/

		$changelog = preg_replace(
			"#(\n+)([0-9]+.*?) : v([0-9\.]+(?:-dev[0-9]*)?)([^\n]*?\n+)#",
			'\1'
			. '<h5>v\3 <small>\2</small></h5>'
			. '\4<pre>',
			$changelog
		) . '</pre>';

		$changelog = preg_replace(
			'#\[J([1-9][\.0-9]*)\]#',
			'<span class="' . $badgeClasses['default'] . '">J\1</span>',
			$changelog
		);

		$title1 = JText::sprintf('MOD_WICKEDTEAM_BIRTHDAYS_EXTENSION_INSTALLED', JText::_($this->name), $thisVersion);
		$title2 = JText::_('MOD_WICKEDTEAM_BIRTHDAYS_LATEST_CHANGES');

		if (strpos($thisVersion, 'dev') !== false)
		{
			$note = '';
		}

		return '<h3>' . $title1 . '</h3>'
			. '<h4>' . $title2 . '</h4>'
			. ($note ? '<div class="alert alert-warning">' . $note . '</div>' : '')
			. $changelog;
	}

	/**
	 * Get version of current installed extension
	 * @param   string  $file  XML file containing the version string
	 * @return  string  Version string
	 */
	public function getVersion($file = '')
	{
		$file = $file ?: $this->getCurrentXMLFile();

		if (!is_file($file))
		{
			return '';
		}

		$xml = JInstaller::parseXMLInstallFile($file);

		if (!$xml || ! isset($xml['version']))
		{
			return '';
		}

		return $xml['version'];
	}

	/**
	 * Check if extension is already installed
	 * @return  boolean
	 */
	public function isInstalled()
	{
		if (!is_file($this->getInstalledXMLFile()))
		{
			return false;
		}

		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('extension_id'))
			->from('#__extensions')
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote($this->extensionType))
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote($this->getElementName()))
			->setLimit(1);
		$this->db->setQuery($query);
		$result = $this->db->loadResult();

		return empty($result) ? false : true;
	}

	/**
	 * Get installation folder of joomla extension
	 * @return  string
	 */
	public function getMainFolder()
	{
		switch ($this->extensionType)
		{
			case 'plugin' :
				return JPATH_PLUGINS . '/' . $this->pluginFolder . '/' . $this->extname;

			case 'component' :
				return JPATH_ADMINISTRATOR . '/components/com_' . $this->extname;

			case 'module' :
				return JPATH_SITE . '/modules/mod_' . $this->extname;

			case 'library' :
				return JPATH_SITE . '/libraries/' . $this->extname;
		}
	}

	/**
	 * Get XML manifest filename of current joomla extension
	 * @return  string
	 */
	public function getInstalledXMLFile()
	{
		return $this->getXMLFile($this->getMainFolder());
	}

	/**
	 * Get XML manifest filename of joomla extension to be installed
	 * @return  string
	 */
	public function getCurrentXMLFile()
	{
		return $this->getXMLFile(__DIR__);
	}

	/**
	 * Create pathname of XML file based on specific folder
	 * @param   string  $folder  base folder for creating XML filename
	 * @return  string
	 */
	public function getXMLFile($folder)
	{
		switch ($this->extensionType)
		{
			case 'module' :
				return $folder . '/mod_' . $this->extname . '.xml';

			default :
				return $folder . '/' . $this->extname . '.xml';
		}
	}

	/**
	 * Create pathname of XML file based on specific folder
	 * @param   string  $type     joomla extension type
	 * @param   string  $extname  joomla extension name
	 * @return  string
	 */
	public function getElementName($type = null, $extname = null)
	{
		$type    = is_null($type) ? $this->extensionType : $type;
		$extname = is_null($extname) ? $this->extname : $extname;

		switch ($type)
		{
			case 'component' :
				return 'com_' . $extname;

			case 'module' :
				return 'mod_' . $extname;

			case 'plugin' :
			default:
				return $extname;
		}
	}
}
