<?php
/**
 * @package JoomlaPack
 * @version $id$
 * @license GNU General Public License, version 2 or later
 * @author JoomlaPack Developers
 * @copyright Copyright 2006-2009 JoomlaPack Developers
 * @since 2.1
 */
defined('_JEXEC') or die('Restricted access');

jpimport('models.filtermodelparent', true);

/**
 * Skip Contained Files / SubDirectories Filters
 *
 */
class JoomlapackModelSkip extends FilterModelParent
{
	/** @var string The filter class being read */
	var $_filterclass="skipfiles";

	/** @var string The filter type of this class, can be inclusion or exclusion **/
	var $_filtertype="exclusion";

	/**
	 * Cache of the SkipFiles list attached to the current profile
	 *
	 * @var array
	 */
	var $_skipFiles = null;

	/**
	 * Cache of the SkipDirectories list attached to the current profile
	 *
	 * @var array
	 */
	var $_skipDirectories = null;

	/**
	 * Sets the ID of the record to fetch, overrides parent's method
	 * @see component/backend/models/FilterModelParent#setId($id)
	 */
	function setId($id=0, $class = 'skipfiles')
	{
		$this->_filterclass = $class;
		$this->_id = $id;
		$this->_record = null;
	}


	/**
	 * Returns the query used to fetch a list of records
	 * @return unknown_type
	 */
	function _getListQuery()
	{
		static $query;

		if(is_null($query))
		{
			// Get active profile
			$session =& JFactory::getSession();
			$profile = $session->get('profile', null, 'joomlapack');
				
			$db =& $this->getDBO();
			$query = "SELECT * FROM ".$db->nameQuote('#__jp_'.$this->_filtertype).' WHERE '.
			$db->nameQuote('class') . ' = ' . $db->Quote('Skipfiles').' OR '.
			$db->nameQuote('class') . ' = ' . $db->Quote('Skipdirs').
				' AND '.$db->nameQuote('profile').' = '.$db->Quote($profile).
				' ORDER BY '.$db->nameQuote('value').' ASC';
		}

		return $query;
	}

	/**
	 * Loads a single record from the database
	 * @return object
	 */
	function &getRecord()
	{
		if(empty($this->_stats))
		{
			$db =& $this->getDBO();
			$query = "SELECT * FROM ".$db->nameQuote('#__jp_'.$this->_filterclass).
			" WHERE ".$db->nameQuote('class') . ' = ' . $db->Quote('Skipfiles').' OR '.
			$db->nameQuote('class') . ' = ' . $db->Quote('Skipdirs');
			$db->setQuery($query);
			$db->query();
			$this->_record = $db->loadObject();
		}
		return $this->_record;
	}

	/**
	 * Queries the filter database to find out if a files filter is set for a given folder
	 *
	 * @param string $filePath Relative path to a folder
	 */
	function isFilesSetFor($filePath)
	{
		if(!is_array($this->_skipFiles))
		{
			$this->_loadFilters();
		}

		$filePath = $this->sanitizeFilePath($filePath);

		return in_array($filePath, $this->_skipFiles);
	}

	/**
	 * Queries the filter database to find out if a directories filter is set for a given folder
	 *
	 * @param string $filePath Relative path to a folder
	 */
	function isDirectoriesSetFor($filePath)
	{
		if(!is_array($this->_skipDirectories))
		{
			$this->_loadFilters();
		}

		$filePath = $this->sanitizeFilePath($filePath);

		return in_array($filePath, $this->_skipDirectories);
	}

	function enableFilesFilter($filePath)
	{
		if(!is_array($this->_skipFiles))
		{
			$this->_loadFilters();
		}

		$filePath = $this->sanitizeFilePath($filePath);

		// Only set if it's not already set
		if(!$this->isFilesSetFor($filePath))
		{
			// Get active profile
			$session =& JFactory::getSession();
			$profile = $session->get('profile', null, 'joomlapack');
				
			$db =& $this->getDBO();
			$sql = "INSERT INTO ".$db->nameQuote('#__jp_exclusion').
				'('.$db->nameQuote('profile').', '.$db->nameQuote('class').', '
				.$db->nameQuote('value').') VALUES ('.
				$db->Quote($profile).', '.$db->Quote('Skipfiles').', '.$db->Quote($filePath).')';
				$db->setQuery($sql);
				$db->query();
				if(JError::isError($db))
				{
					$this->setError($db->getError());
				}
		}
	}

	function enableDirectoriesFilter($filePath)
	{
		if(!is_array($this->_skipDirectories))
		{
			$this->_loadFilters();
		}

		$filePath = $this->sanitizeFilePath($filePath);

		// Only set if it's not already set
		if(!$this->isDirectoriesSetFor($filePath))
		{
			// Get active profile
			$session =& JFactory::getSession();
			$profile = $session->get('profile', null, 'joomlapack');
				
			$db =& $this->getDBO();
			$sql = "INSERT INTO ".$db->nameQuote('#__jp_exclusion').
				'('.$db->nameQuote('profile').', '.$db->nameQuote('class').', '
				.$db->nameQuote('value').') VALUES ('.
				$db->Quote($profile).', '.$db->Quote('Skipdirs').', '.$db->Quote($filePath).')';
				$db->setQuery($sql);
				$db->query();
				if(JError::isError($db))
				{
					$this->setError($db->getError());
				}
		}
	}

	function disableFilesFilter($filePath)
	{
		if(!is_array($this->_skipFiles))
		{
			$this->_loadFilters();
		}

		$filePath = $this->sanitizeFilePath($filePath);

		// Only unset if it's already set
		if($this->isFilesSetFor($filePath))
		{
			// Get active profile
			$session =& JFactory::getSession();
			$profile = $session->get('profile', null, 'joomlapack');
				
			$db =& $this->getDBO();
			$sql = "DELETE FROM ".$db->nameQuote('#__jp_exclusion').
				' WHERE '.$db->nameQuote('profile').' = '.$db->Quote($profile).
				' AND '.$db->nameQuote('class').' = '.$db->Quote('Skipfiles').
				' AND BINARY '.$db->nameQuote('value').' = '.$db->Quote($filePath);
			$db->setQuery($sql);
			$db->query();
			if(JError::isError($db))
			{
				$this->setError($db->getError());
			}
		}
	}

	function disableDirectoriesFilter($filePath)
	{
		if(!is_array($this->_skipDirectories))
		{
			$this->_loadFilters();
		}

		$filePath = $this->sanitizeFilePath($filePath);

		// Only unset if it's already set
		if($this->isDirectoriesSetFor($filePath))
		{
			// Get active profile
			$session =& JFactory::getSession();
			$profile = $session->get('profile', null, 'joomlapack');
				
			$db =& $this->getDBO();
			$sql = "DELETE FROM ".$db->nameQuote('#__jp_exclusion').
				' WHERE '.$db->nameQuote('profile').' = '.$db->Quote($profile).
				' AND '.$db->nameQuote('class').' = '.$db->Quote('Skipdirs').
				' AND BINARY '.$db->nameQuote('value').' = '.$db->Quote($filePath);
			$db->setQuery($sql);
			$db->query();
			if(JError::isError($db))
			{
				$this->setError($db->getError());
			}
		}
	}

	function toggleFilesFilter($filePath)
	{
		if($this->isFilesSetFor($filePath))
		{
			$this->disableFilesFilter($filePath);
		}
		else
		{
			$this->enableFilesFilter($filePath);
		}
	}

	function toggleDirectoriesFilter($filePath)
	{
		if($this->isDirectoriesSetFor($filePath))
		{
			$this->disableDirectoriesFilter($filePath);
		}
		else
		{
			$this->enableDirectoriesFilter($filePath);
		}
	}

	/**
	 * Loads all relative filters off the database
	 *
	 */
	function _loadFilters()
	{
		$this->_loadFileFilters();
		$this->_loadDirectoriesFilters();
	}

	/**
	 * Fetches the Skip Contained Files Filters off the database
	 *
	 */
	function _loadFileFilters()
	{
		// Get active profile
		$session =& JFactory::getSession();
		$profile = $session->get('profile', null, 'joomlapack');

		$db =& $this->getDBO();
		$sql = "SELECT * FROM ".$db->nameQuote('#__jp_exclusion').
			' WHERE '.$db->nameQuote('profile').' = '.$db->Quote($profile).
			' AND '.$db->nameQuote('class').' = '.$db->Quote('Skipfiles');
		$db->setQuery($sql);
		$temp = $db->loadAssocList();

		$this->_skipFiles = array();
		if(is_array($temp))
		{
			foreach($temp as $entry)
			{
				$this->_skipFiles[] = $entry['value'];
			}
		}
	}

	/**
	 * Fetches the Skip Contained Files Filters off the database
	 *
	 */
	function _loadDirectoriesFilters()
	{
		// Get active profile
		$session =& JFactory::getSession();
		$profile = $session->get('profile', null, 'joomlapack');

		$db =& $this->getDBO();
		$sql = "SELECT * FROM ".$db->nameQuote('#__jp_exclusion').
			' WHERE '.$db->nameQuote('profile').' = '.$db->Quote($profile).
			' AND '.$db->nameQuote('class').' = '.$db->Quote('Skipdirs');
		$db->setQuery($sql);
		$temp = $db->loadAssocList();

		$this->_skipDirectories = array();
		if(is_array($temp))
		{
			foreach($temp as $entry)
			{
				$this->_skipDirectories[] = $entry['value'];
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