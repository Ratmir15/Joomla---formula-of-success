<?php
/**
 * @package JoomlaPack
 * @version $id$
 * @license GNU General Public License, version 2 or later
 * @author JoomlaPack Developers
 * @copyright Copyright 2006-2009 JoomlaPack Developers
 * @since 1.3
 */
defined('_JEXEC') or die('Restricted access');

jpimport('models.filtermodelparent', true);

/**
 * Directory Exclusion filter Model class
 *
 */
class JoomlapackModelDef extends FilterModelParent
{
	/** @var string The filter class being read */
	var $_filterclass="def";

	/** @var string The filter type of this class, can be inclusion or exclusion **/
	var $_filtertype="exclusion";

	/**
	 * Cache of the DEF list attached to the current profile
	 *
	 * @var array
	 */
	var $_filters = null;

	/**
	 * Queries the filter database to find out if a filter is set for a given folder
	 *
	 * @param string $filePath Relative path to a folder
	 */
	function isSetFor($filePath)
	{
		if(!is_array($this->_filters))
		{
			$this->_loadFilters();
		}

		$filePath = $this->sanitizeFilePath($filePath);

		return in_array($filePath, $this->_filters);
	}

	/**
	 * Activate the DEF for a given (relative) filepath
	 *
	 * @param string $filePath Relative path to folder being added to the DEF list
	 */
	function enableFilter($filePath)
	{
		if(!is_array($this->_filters))
		{
			$this->_loadFilters();
		}

		$filePath = $this->sanitizeFilePath($filePath);

		// Only set if it's not already set
		if(!$this->isSetFor($filePath))
		{
			// Get active profile
			$session =& JFactory::getSession();
			$profile = $session->get('profile', null, 'joomlapack');
				
			$db =& $this->getDBO();
			$sql = "INSERT INTO ".$db->nameQuote('#__jp_exclusion').
				'('.$db->nameQuote('profile').', '.$db->nameQuote('class').', '
				.$db->nameQuote('value').') VALUES ('.
				$db->Quote($profile).', '.$db->Quote('def').', '.$db->Quote($filePath).')';
				$db->setQuery($sql);
				$db->query();
				if(JError::isError($db))
				{
					$this->setError($db->getError());
				}
		}
	}

	/**
	 * Disable (erase) a Directory Exclusion filter from this profile's DEF list
	 *
	 * @param string $filePath The relative path to the folder being excluded from the DEF list of this profile
	 */
	function disableFilter($filePath)
	{
		if(!is_array($this->_filters))
		{
			$this->_loadFilters();
		}

		$filePath = $this->sanitizeFilePath($filePath);

		// Only unset if it's already set
		if($this->isSetFor($filePath))
		{
			// Get active profile
			$session =& JFactory::getSession();
			$profile = $session->get('profile', null, 'joomlapack');
				
			$db =& $this->getDBO();
			$sql = "DELETE FROM ".$db->nameQuote('#__jp_exclusion').
				' WHERE '.$db->nameQuote('profile').' = '.$db->Quote($profile).
				' AND '.$db->nameQuote('class').' = '.$db->Quote('def').
				' AND BINARY '.$db->nameQuote('value').' = '.$db->Quote($filePath);
			$db->setQuery($sql);
			$db->query();
			if(JError::isError($db))
			{
				$this->setError($db->getError());
			}
		}
	}

	/**
	 * Toggles the DEF status for a given folder
	 *
	 * @param string $filePath Relative path to the desired folder
	 */
	function toggleFilter($filePath)
	{
		if($this->isSetFor($filePath))
		{
			$this->disableFilter($filePath);
		}
		else
		{
			$this->enableFilter($filePath);
		}
	}

	/**
	 * Fetches the DEF off the database
	 *
	 */
	function _loadFilters()
	{
		// Get active profile
		$session =& JFactory::getSession();
		$profile = $session->get('profile', null, 'joomlapack');

		$db =& $this->getDBO();
		$sql = "SELECT * FROM ".$db->nameQuote('#__jp_exclusion').
			' WHERE '.$db->nameQuote('profile').' = '.$db->Quote($profile).
			' AND '.$db->nameQuote('class').' = '.$db->Quote('def');
		$db->setQuery($sql);
		$temp = $db->loadAssocList();

		$this->_filters = array();
		if(is_array($temp))
		{
			foreach($temp as $entry)
			{
				$this->_filters[] = $entry['value'];
			}
		}
	}

	/**
	 * Converts a potential Windows-style path to UNIX-style
	 *
	 * @param string $filePath The filepath
	 * @return string The sanitized filepath
	 */
	function sanitizeFilePath($filePath)
	{
		if(!class_exists('JoomlapackHelperUtils'))
		{
			jpimport('helpers.utils', true);
		}

		return JoomlapackHelperUtils::TranslateWinPath($filePath);
	}
}