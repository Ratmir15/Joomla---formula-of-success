<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.model');

if(!defined('_JP_LOG_NONE'))
{
	define("_JP_LOG_NONE",		0);
	define("_JP_LOG_ERROR",		1);
	define("_JP_LOG_WARNING",	2);
	define("_JP_LOG_INFO",		3);
	define("_JP_LOG_DEBUG",		4);
}


/**
 * A class to handle the JoomlaPack registry data. It only handles the current profile's data
 * and also uses memory caching. The data are only saved when you call the save() method.
 *
 */
class JoomlapackModelRegistry extends JModel
{
	/** @var integer Profile ID */
	var $_id;

	/** @var array Registry values */
	var $_registry = null;

	/** @var array Global registry values */
	var $_globalregistry = null;

	/** @var array Name of registry values defined as "global" */
	var $_globals = array(
		'OutputDirectory',		// One output directory, no matter what's the profile
		'enableFrontend',		// Frontend options are also something that goes beyon profiles
		'secretWord',			// Ditto
		'siteRoot',				// Site root is unique, component-wide
		'authlevel',			// Minimum access level
		'nagscreen',			// Is the license nag screen displayed yet?
		'settingsmode',			// The settings mode (custom, optimistic, normal, conservative)
		'easymode',				// When true, it enables the Easy Mode. When false, the normal (advanced) mode is enabled.
		'frontendemail',		// Send emails after front-end backup
		'cubeinfile'			// Store CUBEObject in a temporary file instead of in database
	);

	/**
	 * Overides the JModel implementation to provide a Singleton implementation
	 *
	 * @param	string	The model type to instantiate
	 * @param	string	Prefix for the model class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JoomlapackModelRegistry	A model object, or false on failure
	 */
	function &getInstance( $type = 'model', $prefix = '', $config = array() )
	{
		static $instance;

		if(!is_object($instance))
		{
			$instance = new JoomlapackModelRegistry();
		}

		return $instance;
	}

	/**
	 * Public constructor. Also loads the current profile's registry to speed up further usage
	 *
	 * @param integer $profile_id The profile ID to pretend it's active (optional)
	 */
	function __construct($config = array())
	{
		parent::__construct();

		if( isset($config['id']) )
		{
			$profile_id = $config['id'];
		}
		else
		{
			$session =& JFactory::getSession();
			$profile_id = $session->get('profile', null, 'joomlapack');
		}

		// Load the Registry by default
		$this->_id = $profile_id;
		$this->_loadRegistry();
	}

	/**
	 * Forces a complete reload of the registry
	 */
	function reload()
	{
		$session =& JFactory::getSession();
		$profile_id = $session->get('profile', null, 'joomlapack');
		$this->_id = $profile_id;
		$this->_loadRegistry();
	}

	/**
	 * Gets a registry value. If the value doesn't exist, the default is returned and
	 * saved to the registry table.
	 *
	 * @param string $key Registry key
	 * @param mixed $default Default value
	 * @return mixed The value of the specified registry key
	 */
	function get($key, $default = null)
	{
		if(is_null($this->_registry))
		{
			$this->_loadRegistry();
		}

		// Special hack: OutputDirectoryConfig used for getting the name of the OutputDirectory with [ROOT] and [ROOTPARENT] variables...
		if($key == 'OutputDirectoryConfig')
		{
			return $this->_processOutputDirectoryOnSave($this->get('OutputDirectory', $default));
		}
		
		if(in_array($key, $this->_globals))
		{
			// This is a global key, fetch it from there
			if(isset($this->_globalregistry[$key]))
			{
				return $this->_globalregistry[$key];
			}
			else
			{
				$default = is_null($default) ? $this->_getDefaultFor($key) : $default;
				$this->_globalregistry[$key] = $default;
				return $default;
			}
		}
		else
		{
			// This is a local (profile) key
			if(isset($this->_registry[$key]))
			{
				return $this->_registry[$key];
			}
			else
			{
				$default = is_null($default) ? $this->_getDefaultFor($key) : $default;
				$this->_registry[$key] = $default;
				return $default;
			}
		}
	}

	/**
	 * A simplistic function to set a registry key/value pair
	 *
	 * @param string $key Registry key
	 * @param mixed $value The value it holds
	 */
	function set($key, $value)
	{
		if(in_array($key, $this->_globals))
		{
			$this->_globalregistry[$key] = $value;
		}
		else
		{
			$this->_registry[$key] = $value;
		}
	}

	/**
	 * Gets the Joomla! temporary directory path
	 *
	 * @return string
	 */
	function getTemporaryDirectory()
	{
		$jreg =& JFactory::getConfig();
		return $jreg->getValue('config.tmp_path');
	}

	/**
	 * Saves the current profile's registry to database. This is only necessary for
	 * the 'Save' operation of the configuration page.
	 *
	 * @return boolean True on success
	 * @access public
	 *
	 */
	function save()
	{
		// Run Settings Mode toggles before saving (possibly overriding custom values)
		$this->applySettingsMode();

		$db = JFactory::getDBO();

		// Drop old values first
		$query = 'DELETE FROM '.$db->nameQuote('#__jp_registry').
				' WHERE '.$db->nameQuote('profile').' = '.$db->Quote($this->_id).';'."\n";

		$query .= 'DELETE FROM '.$db->nameQuote('#__jp_registry').
				' WHERE '.$db->nameQuote('profile').' = '.$db->Quote(0).';'."\n";

		// Create INSERT queries for new values
		foreach($this->_registry as $key => $value)
		{
			$query .= 'INSERT INTO '.$db->nameQuote('#__jp_registry').
					'('.$db->nameQuote('profile').','.$db->nameQuote('key').','.$db->nameQuote('value').')'.
					' VALUES '.
					'('.$db->Quote($this->_id).','.$db->Quote($key).','.$db->Quote($value).');'."\n";
		}

		foreach($this->_globalregistry as $key => $value)
		{
			// Process OutputDirectory
			if($key == 'OutputDirectory') $value = $this->_processOutputDirectoryOnSave($value);
			
			$query .= 'INSERT INTO '.$db->nameQuote('#__jp_registry').
					'('.$db->nameQuote('profile').','.$db->nameQuote('key').','.$db->nameQuote('value').')'.
					' VALUES '.
					'('.$db->Quote(0).','.$db->Quote($key).','.$db->Quote($value).');'."\n";
		}

		// Run batch query inside a transaction (doesn't screw up the registry in case of SQL errors)
		$db->setQuery($query);
		$db->queryBatch(true,true);
	}

	/**
	 * Update current profile from request's data (data binding). It assumes that you
	 * are giving only the array of data, not the REQUEST array itself.
	 *
	 * @param array $data The data array holding all the data
	 * @return bool True on success
	 */
	function bindFromData(&$data)
	{
		if(!is_array($data))
		{
			JError::raiseError('500', JText::_('MODELPROFILE_ERROR_BINDING'));
			return false;
		}

		foreach($data as $key => $value)
		{
			$this->set($key, $value);
		}
		return true;
	}

	/**
	 * Gives access to the raw profile's registry array
	 *
	 * @return array
	 */
	function &getRawRegistryArray()
	{
		return $this->_registry;
	}

	/**
	 * Loads the current profile's registry into $_registry class variable
	 *
	 * @access private
	 */
	function _loadRegistry()
	{
		// Get all of profile's data
		$db =& JFactory::getDBO();
		$query = 'SELECT '.$db->nameQuote('key').','.$db->nameQuote('value').
				' FROM '.$db->nameQuote('#__jp_registry').
				' WHERE '.$db->nameQuote('profile').' = '.$db->Quote($this->_id);
		$db->setQuery($query);
		$tmp = $db->loadAssocList();
		$this->_registry = array();
		if( is_array($tmp) ) foreach($tmp as $entry)
		{
			$this->_registry[$entry['key']] = $entry['value'];
		}
		unset($tmp);

		// Get global variables
		$query = 'SELECT '.$db->nameQuote('key').','.$db->nameQuote('value').
				' FROM '.$db->nameQuote('#__jp_registry').
				' WHERE '.$db->nameQuote('profile').' = '.$db->Quote(0);
		$db->setQuery($query);
		$tmp = $db->loadAssocList('key');
		$this->_globalregistry = array();
		if( is_array($tmp) ) foreach($tmp as $entry)
		{
			// Process OutputDirectory
			if($entry['key'] == 'OutputDirectory') {
				$entry['value'] = $this->_processOutputDirectoryOnLoad($entry['value']);	
			}
			
			$this->_globalregistry[$entry['key']] = $entry['value'];
		}
		unset($tmp);
	}

	function _getDefaultFor($key)
	{
		switch($key)
		{
			case 'MySQLCompat':
				$default = 'default';
				break;

			case "BackupType":
				$default = 'full';
				break;

			case "OutputDirectory":
				$default = JPATH_COMPONENT_ADMINISTRATOR.DS.'backup';
				break;

			case "packerengine":
				$default = 'zip';
				break;

			case "TarNameTemplate":
				$default = 'site-[HOST]-[DATE]-[TIME]';
				break;

			case "dbAlgorithm":
				$default = 'smart';
				break;

			case "packAlgorithm":
				$default = 'smart';
				break;

			case "InstallerPackage":
				$default = 'jpi4.jpa';
				break;

			case "logLevel":
				$default = _JP_LOG_DEBUG;
				break;

			case "backupMethod":
				$default = 'ajax';
				break;

			case "enableFrontend":
				$default = false;
				break;

			case "secretWord":
				$default = '';
				break;

			case "mnRowsPerStep":
				$default = 100;
				break;

			case "mnMaxFragmentSize":
				$default = 1048756;
				break;

			case "mnMaxFragmentFiles":
				$default = 50;
				break;

			case "mnZIPForceOpen":
				$default = false;
				break;

			case "mnZIPCompressionThreshold":
				$default = 1024768;
				break;

			case "mnZIPDirReadChunk":
				$default = 1024768;
				break;

			case "mnMaxExecTimeAllowed":
				$default = 14;
				break;

			case "mnMinimumExectime":
				$default = 2;
				break;

			case "mnExectimeBiasPercent":
				$default = 75;
				break;

			case "mnMaxOpsPerStep":
				$default = 100;
				break;

			case "mysqldumpPath":
				$default = '/usr/bin/mysqldump';
				break;

			case "mnMSDDataChunk":
				$default = 16384;
				break;

			case "mnMSDMaxQueryLines":
				$default = 100;
				break;

			case "mnMSDLinesPerSession":
				$default = 100;
				break;

			case "mnArchiverChunk":
				$default = 0;
				break;

			case "siteRoot":
				$default = JPATH_SITE;
				break;

			case "enableSizeQuotas":
				$default = false;
				break;

			case "enableCountQuotas":
				$default = false;
				break;

			case "sizeQuota":
				$default = 30;
				break;

			case "countQuota":
				$default = 3;
				break;

			case "enableMySQLKeepalive":
				$default = false;
				break;

			case "authlevel":
				$default = 25;
				break;
					
			case "nagscreen":
				$default = false;
				break;

			case "gzipbinary":
				$default = "gzip";
				break;

			case "effvfolder":
				$default = "external_files"; // External Files' virtual folder in the backup set
				break;

			case 'settingsmode':
				$default = 'optimistic';
				break;

			case 'easymode':
				$default = false;
				break;

			case 'frontendemail':
				$default = true;
				break;

			case 'cubeinfile':
				$default = true;
				break;

			case 'listerengine':
				$default = 'smart';
				break;

			case 'minexectime':
				$default = 2000;
				break;

			case 'arbitraryfeemail':
				$default = '';
				break;
				
			case 'dereferencesymlinks':
				$default = true;
				break;

			case 'splitpartsize':
				$default = 1;
				break;

			case 'df_host':
			case 'df_user':
			case 'df_pass':
			case 'df_initdir':
				$default = '';
				break;

			case 'df_port':
				$default = '21';
				break;

			case 'df_usessl':
				$default = false;
				break;

			case 'df_passive':
				$default = true;
				break;

			default:
				$default = 'default';
				break;
		}

		return $default;
	}
	
	/**
	 * Converts absolute paths in an OutputDirectory value to the relevant variables
	 * @param $value
	 * @return unknown_type
	 */
	function _processOutputDirectoryOnLoad( $value )
	{
		$map = $this->_getOutputDirectoryVariableMap();
		$temp = $value;
		foreach( $map as $key => $path )
		{
			$temp = str_replace($key, $path, $temp);
		}
		return $temp;
	}
	
	/**
	 * Converts OutputDirectory variables into real data
	 * @param $value
	 * @return unknown_type
	 */
	function _processOutputDirectoryOnSave( $value )
	{
		$map = $this->_getOutputDirectoryVariableMap();
		$temp = $value;
		foreach( $map as $key => $path )
		{
			if( ($path != DS) && (!empty($path)) )
			{
				$temp = str_replace($path, $key, $temp);
			}
		}
		return $temp;
	}
	
	/**
	 * Public alias to _processOutputDirectoryOnLoad, used by Browse view
	 * @param $var
	 * @return unknown_type
	 */
	function processOutputFolderVars( $var )
	{
		return $this->_processOutputDirectoryOnLoad($var);
	}
	
	/**
	 * Returns a map of valid variables for the output directory and their absolute directory mapping
	 * @return array
	 */
	function _getOutputDirectoryVariableMap()
	{
		static $map;
		
		if(empty($map))
		{
			$map = array();
			$map['[ROOT]'] = @realpath(JPATH_SITE);
			$map['[ROOTPARENT]'] = @realpath(JPATH_SITE.DS.'..');
		}
		
		return $map;
	}

	function applySettingsMode()
	{
		switch($this->get('settingsmode'))
		{
			case 'custom':
				// Nothing to do for the custom mode; it's fully controlled by an expert user
				break;

			case 'optimistic':
				// AJAX mode, ZIP, medium to high speed, normal time allowance, better compression rations
				$this->set('MySQLCompat',			'default');
				$this->set('listerengine',			'smart');
				$this->set('dbdumpengine',			'default');
				$this->set('packerengine',			'jpa');
				$this->set('InstallerPackage',		'jpi4.jpa');
				$this->set('backupMethod',			'ajax');
				$this->set('minexectime',			2000);
				$this->set('enableMySQLKeepalive',	0);
				$this->set('mnMaxFragmentSize',		1048576);
				$this->set('mnMaxFragmentFiles',	50);
				$this->set('mnArchiverChunk',		1048576);
				$this->set('mnZIPForceOpen',		0);
				$this->set('mnZIPDirReadChunk',		1048576);
				$this->set('mnMinimumExectime',		3);
				$this->set('mnMaxOpsPerStep',		1000);
				$this->set('splitpartsize',			0);
				

				$this->set('dbAlgorithm',				'smart');
				$this->set('packAlgorithm',				'smart');
				$this->set('mnRowsPerStep',				300);
				$this->set('mnZIPCompressionThreshold',	1048576);
				$this->set('mnMaxExecTimeAllowed',		14);
				$this->set('mnExectimeBiasPercent',		75);
				break;

			case 'normal':
				// JS Redirets mode, JPA, medium to slow speed, normal time allowance, balanced compression rations
				$this->set('MySQLCompat',			'default');
				$this->set('listerengine',			'smart');
				$this->set('dbdumpengine',			'default');
				$this->set('packerengine',			'jpa');
				$this->set('InstallerPackage',		'jpi4.jpa');
				$this->set('backupMethod',			'jsredirect');
				$this->set('minexectime',			3000);
				$this->set('enableMySQLKeepalive',	1);
				$this->set('mnMaxFragmentSize',		524288);
				$this->set('mnMaxFragmentFiles',	20);
				$this->set('mnArchiverChunk',		1048576);
				$this->set('mnZIPForceOpen',		0);
				$this->set('mnZIPDirReadChunk',		1048576);
				$this->set('mnMinimumExectime',		3);
				$this->set('mnMaxOpsPerStep',		20);
				$this->set('splitpartsize',			0);

				$this->set('dbAlgorithm',				'smart');
				$this->set('packAlgorithm',				'smart');
				$this->set('mnRowsPerStep',				100);
				$this->set('mnZIPCompressionThreshold',	1048576);
				$this->set('mnMaxExecTimeAllowed',		14);
				$this->set('mnExectimeBiasPercent',		75);
				break;

			case 'conservative':
				// JS Redirects mode, JPA, slow, single-stepping enabled, balanced compression rations
				$this->set('MySQLCompat',			'compat');
				$this->set('listerengine',			'smart');
				$this->set('dbdumpengine',			'default');
				$this->set('packerengine',			'jpa');
				$this->set('InstallerPackage',		'jpi4.jpa');
				$this->set('backupMethod',			'jsredirect');
				$this->set('minexectime',			5000);
				$this->set('enableMySQLKeepalive',	1);
				$this->set('mnMaxFragmentSize',		524288);
				$this->set('mnMaxFragmentFiles',	20);
				$this->set('mnArchiverChunk',		1048576);
				$this->set('mnZIPForceOpen',		0);
				$this->set('mnZIPDirReadChunk',		1048576);
				$this->set('mnMinimumExectime',		3);
				$this->set('mnMaxOpsPerStep',		5);
				$this->set('splitpartsize',			1048576);

				$this->set('dbAlgorithm',				'smart');
				$this->set('packAlgorithm',				'smart');
				$this->set('mnRowsPerStep',				50);
				$this->set('mnZIPCompressionThreshold',	524288);
				$this->set('mnMaxExecTimeAllowed',		7);
				$this->set('mnExectimeBiasPercent',		50);
				break;
		}
	}
}