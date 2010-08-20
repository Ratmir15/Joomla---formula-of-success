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

/**
 * The Control Panel model
 *
 */
class JoomlapackModelConfig extends JModel
{
	var $_registry;

	/**
	 * Constructor; dummy for now
	 *
	 */
	function __construct()
	{
		parent::__construct();

		jpimport('models.registry', true);
		$this->_registry =& JoomlapackModelRegistry::getInstance();
	}

	/**
	 * A simple proxy to the registry model get() method
	 *
	 * @param string $key The variable name
	 * @return mixed The variable value
	 */
	function getVar($key)
	{
		return $this->_registry->get($key);
	}

	function getBackupTypeList()
	{
		$options = array();
		$options[] = JHTML::_('select.option', 'full', JText::_('CONFIG_OPT_BACKUPFULL'));
		$options[] = JHTML::_('select.option', 'dbonly', JText::_('CONFIG_OPT_BACKUPDBONLY'));
		$options[] = JHTML::_('select.option', 'extradbonly', JText::_('CONFIG_OPT_EXTRADBONLY'));
		return $options;
	}

	function getLogLevelList()
	{
		$options = array();
		$options[] = JHTML::_('select.option', _JP_LOG_ERROR , JText::_('CONFIG_OPT_LLERROR'));
		$options[] = JHTML::_('select.option', _JP_LOG_WARNING , JText::_('CONFIG_OPT_LLWARNING'));
		$options[] = JHTML::_('select.option', _JP_LOG_INFO , JText::_('CONFIG_OPT_LLINFO'));
		$options[] = JHTML::_('select.option', _JP_LOG_DEBUG , JText::_('CONFIG_OPT_LLDEBUG'));
		$options[] = JHTML::_('select.option', _JP_LOG_NONE , JText::_('CONFIG_OPT_LLNONE'));
		return $options;
	}

	function getSqlCompatList()
	{
		$options = array();
		$options[] = JHTML::_('select.option', 'default' , JText::_('CONFIG_OPT_SQLDEFAULT'));
		$options[] = JHTML::_('select.option', 'compat' , JText::_('CONFIG_OPT_SQLCOMPAT'));
		return $options;
	}

	function getAlgoList()
	{
		$options = array();
		$options[] = JHTML::_('select.option', 'slow' , JText::_('CONFIG_OPT_MULTIALGO'));
		$options[] = JHTML::_('select.option', 'smart' , JText::_('CONFIG_OPT_SMARTALGO'));
		return $options;
	}

	function getFilelistEngineList()
	{
		return $this->_getEngineList('lister');
	}

	function getDatabaseEngineList()
	{
		return $this->_getEngineList('dumper');
	}

	function getArchiverEngineList()
	{
		return $this->_getEngineList('packer');
	}

	function getInstallerList()
	{
		jpimport('helpers.utils', true);

		$ini_path = JPATH_COMPONENT_ADMINISTRATOR.DS.'assets'.DS.'installers';
		jimport('joomla.filesystem.folder');
		$ini_files = JFolder::files($ini_path,'\.ini$');
		$installerArray = array();
		if(count($ini_files))
		{
			foreach($ini_files as $sourceINI)
			{
				$myData = JoomlapackHelperUtils::parse_ini_file($ini_path.DS.$sourceINI, true);
				foreach($myData as $key => $value)
					$installerArray[$key] = $value;
			}
		}
		
		$options = array();
		foreach($installerArray as $sectionKey => $installerItem)
		{
			// @todo Use translation keys for installers
			$options[] = JHTML::_('select.option', $installerItem['package'], $installerItem['name']);
		}

		return $options;
	}

	function getBackupMethodList()
	{
		$options = array();
		$options[] = JHTML::_('select.option', 'ajax', JText::_('CONFIG_OPT_METHODAJAX'));
		$options[] = JHTML::_('select.option', 'jsredirect', JText::_('CONFIG_OPT_METHODJSREDIRECT'));
		return $options;
	}

	/**
	 * Returns an option list for authentication level
	 *
	 * @return array
	 */
	function getAuthLevelList()
	{
		$options = array();
		$options[] = JHTML::_('select.option', '25', JText::_('CONFIG_OPT_AUTHSUPER'));
		$options[] = JHTML::_('select.option', '24', JText::_('CONFIG_OPT_AUTHADMIN'));
		$options[] = JHTML::_('select.option', '23', JText::_('CONFIG_OPT_AUTHMANAGER'));
		return $options;
	}

	function _getEngineList( $engine )
	{
		jpimport('helpers.utils', true);
		// Load engine definitions
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
		
		// Create selection list array
		$options = array();
		foreach($engineArray as $sectionKey => $engineItem)
		{
			// Use translation keys for engine names
			$description = JText::_($engineItem['translationkey']);
			$options[] = JHTML::_('select.option', $sectionKey, $description );
		}

		return $options;
	}
}