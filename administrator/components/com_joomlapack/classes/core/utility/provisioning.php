<?php
/**
 * @package		JoomlaPack
 * @copyright	Copyright (C) 2006-2009 JoomlaPack Developers. All rights reserved.
 * @version		$Id$
 * @license 		http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @since		1.3
 */

// ensure this file is being included by a parent file - Joomla! 1.0.x and 1.5 compatible
defined('_JEXEC') or die('Restricted access');

/**
 * CUBE Engine provisioning class
 *
 */
class JoomlapackCUBEProvisioning extends JObject
{
	/**
	 * The required archive extension, set by the archiver class if it's loaded
	 *
	 * @var string
	 */
	var $archiveExtension;

	/**
	 * Archiver engine instance
	 *
	 * @var JoomlapackCUBEArchiver
	 */
	var $_archiverInstance;

	/**
	 * Filesystem lister engine instance
	 *
	 * @var JoomlapackCUBELister
	 */
	var $_listerInstance;

	/**
	 * Database dumper engine
	 *
	 * @var JoomlapackCUBEParts
	 */
	var $_dumperInstance;

	/**
	 * Singleton pattern
	 *
	 * @return JoomlapackCUBEProvisioning
	 */
	function &getInstance()
	{
		static $instance;

		if(!is_object($instance))
		{
			$instance = new JoomlapackCUBEProvisioning();
		}

		if(!class_exists('JoomlapackModelRegistry'))
		{
			jpimport('models.registry', true);
		}

		return $instance;
	}

	/**
	 * Returns the archiver engine object instance
	 *
	 * @param bool $forceNew Force creation of a fresh instance, overriding the stored one
	 * @return JoomlapackCUBEArchiver The archiver engine object instance
	 */
	function & getArchiverEngine($forceNew = false, $forcedtype = null)
	{
		if((!is_object($this->_archiverInstance)) || $forceNew)
		{
			$configuration =& JoomlapackModelRegistry::getInstance();
			$engine = $configuration->get('packerengine');
			if(!is_null($forcedtype)) $engine = $forcedtype;
			$this->_archiverInstance =& $this->_getAnEngine('packer', $engine);
		}

		return $this->_archiverInstance;
	}

	/**
	 * Returns the file lister object instance
	 *
	 * @param bool $forceNew Force creation of a fresh instance, overriding the stored one
	 * @return JoomlapackCUBELister The file lister object instance
	 * @todo Create an abstract class for file lister classes
	 */
	function & getListerEngine($forceNew = false)
	{
		if((!is_object($this->_listerInstance)) || $forceNew)
		{
			$configuration =& JoomlapackModelRegistry::getInstance();
			$engine = $configuration->get('listerengine');
			$this->_listerInstance =& $this->_getAnEngine('lister', $engine);
		}

		return $this->_listerInstance;
	}

	/**
	 * Returns the database packer object instance
	 *
	 * @param bool $forceNew Force creation of a fresh instance, overriding the stored one
	 * @return JoomlapackEngineParts The database packer object instance
	 * @todo Create an abstract class for database packer classes
	 */
	function & getDBPackerEngine($forceNew = false)
	{
		if((!is_object($this->_dumperInstance)) || $forceNew)
		{
			$configuration =& JoomlapackModelRegistry::getInstance();
			$engine = $configuration->get('dbdumpengine');
			$this->_dumperInstance =& $this->_getAnEngine('dumper', $engine);
		}

		return $this->_dumperInstance;
	}

	/**
	 * Fetches the required includes and actually includes them, too!
	 *
	 * @static
	 */
	function retrieveEngineIncludes()
	{
		if(JoomlapackCUBETables::CountVar('CUBEEngineIncludes') >= 1)
		{
			// There is a db entry. Load, and unserialize
			$includes = JoomlapackCUBETables::UnserializeVar('CUBEEngineIncludes');
			foreach($includes as $dotted)
			{
				jpimport($dotted);
			}
		}
	}

	/**********************************************************************************************
	 * ENGINE PROVISIONING (Private Methods)
	 **********************************************************************************************/
	/**
	 * Adds an entry to the engine includes table
	 *
	 * @param string $dottedNotation Dotted notation of class file to add to the list
	 */
	function _addEngineInclude($dottedNotation)
	{
		if(JoomlapackCUBETables::CountVar('CUBEEngineIncludes') >= 1)
		{
			// There is a db entry. Load, and unserialize
			$includes = JoomlapackCUBETables::UnserializeVar('CUBEEngineIncludes');
		}
		else
		{
			// Start a new array
			$includes = array();
		}

		// Append to the array
		$includes[] = $dottedNotation;

		// Serialize and save
		JoomlapackCUBETables::SerializeVar('CUBEEngineIncludes', $includes);
	}

	/**
	 * Retrieves an object for the specified engine. It reads the engine.ini in order to do that.
	 * It will also call the _addEngineInclude to make sure the included file persists during
	 * the backup session.
	 *
	 * @param string $engine The engine type (lister, dumper, packer)
	 * @param string $item The engine class file name (e.g. deafault, jpa, etc)
	 */
	function & _getAnEngine($engine, $item)
	{
		// Load engine definitions
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Creating $engine engine of type $item");
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Trying to read engine setup data for $engine");
		
		// Find and parse all ini files in the given directory
		$ini_path = JPATH_COMPONENT_ADMINISTRATOR.DS.'classes'.DS.'engine'.DS.$engine;
		jimport('joomla.filesystem.folder');
		$ini_files = JFolder::files($ini_path,'\.ini$');
		$engineArray = array();
		if(count($ini_files))
		{
			foreach($ini_files as $sourceINI)
			{
				$myData = JoomlapackHelperUtils::parse_ini_file($ini_path.DS.$sourceINI, true);
				foreach($myData as $key => $value)
					$engineArray[$key] = $value;
			}
		}
		
		if(isset($engineArray[$item]))
		{
			$engineDescriptor = $engineArray[$item];
			$dotted = 'engine.'.$engine.'.'.$engineDescriptor['include'];
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Trying to include engine file $dotted");
			$this->_addEngineInclude($dotted);
			jpimport($dotted);
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Instanciating ".$engineDescriptor['class']);
			$instance = new $engineDescriptor['class'];
			// If we are getting an archiver class, also populate the _archiveExtension field
			if($engine == 'packer')
			{
				$this->archiveExtension = $engineDescriptor['extension'];
			}
			return $instance;
		}
		else
		{
			$this->setError(JText::sprintf('CUBE_PROVISIONING_ENGINENOTFOUND', $engine.'.'.$item));
			return false;
		}
	}
}