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
 * Database Exclusion filter Model class
 *
 */
class JoomlapackModelDbef extends FilterModelParent
{
	/** @var string The filter class being read */
	var $_filterclass="dbef";

	/** @var string The filter type of this class, can be inclusion or exclusion **/
	var $_filtertype="exclusion";

	/**
	 * Cache of the DBEF list attached to the current profile
	 *
	 * @var array
	 */
	var $_filters = null;

	/**
	 * Queries the filter database to find out if a filter is set for a given table
	 *
	 * @param string $table Table name (NB! Use #__ instead of real prefix)
	 */
	function isSetFor($table)
	{
		if(!is_array($this->_filters))
		{
			$this->_loadFilters();
		}

		return in_array($table, $this->_filters);
	}

	/**
	 * Activate the DBEF for a given table
	 *
	 * @param string $table Table name (NB! Use #__ instead of current prefix)
	 */
	function enableFilter($table)
	{
		if(!is_array($this->_filters))
		{
			$this->_loadFilters();
		}

		// Only set if it's not already set
		if(!$this->isSetFor($table))
		{
			// Get active profile
			$session =& JFactory::getSession();
			$profile = $session->get('profile', null, 'joomlapack');
				
			$db =& $this->getDBO();
			$sql = "INSERT INTO ".$db->nameQuote('#__jp_exclusion').
				'('.$db->nameQuote('profile').', '.$db->nameQuote('class').', '
				.$db->nameQuote('value').') VALUES ('.
				$db->Quote($profile).', '.$db->Quote('dbef').', '.$db->Quote($table).')';
				$db->setQuery($sql);
				$db->query();
				if(JError::isError($db))
				{
					$this->setError($db->getError());
				}
		}
	}

	/**
	 * Disable (erase) a table filter from this profile's DBEF list
	 *
	 * @param string $table Table name (NB! Use #__ instead of current prefix)
	 */
	function disableFilter($table)
	{
		if(!is_array($this->_filters))
		{
			$this->_loadFilters();
		}

		// Only unset if it's already set
		if($this->isSetFor($table))
		{
			// Get active profile
			$session =& JFactory::getSession();
			$profile = $session->get('profile', null, 'joomlapack');
				
			$db =& $this->getDBO();
			$sql = "DELETE FROM ".$db->nameQuote('#__jp_exclusion').
				' WHERE '.$db->nameQuote('profile').' = '.$db->Quote($profile).
				' AND '.$db->nameQuote('class').' = '.$db->Quote('dbef').
				' AND '.$db->nameQuote('value').' = '.$db->Quote($table);
			$db->setQuery($sql);
			$db->query();
			if(JError::isError($db))
			{
				$this->setError($db->getError());
			}
		}
	}

	/**
	 * Toggles the DBEF status for a given folder
	 *
	 * @param string $table Table name (NB! Use #__ instead of current prefix)
	 */
	function toggleFilter($table)
	{
		if($this->isSetFor($table))
		{
			$this->disableFilter($table);
		}
		else
		{
			$this->enableFilter($table);
		}
	}

	/**
	 * Gets the list of tables for the current Joomla! database
	 *
	 */
	function getTableList()
	{
		$db =& $this->getDBO();
		$sql = 'SHOW TABLES';
		$db->setQuery($sql);
		$temp = $db->loadRowList();

		$ret = array();
		if(!empty($temp))
		{
			foreach($temp as $row)
			{
				$ret[] = $row[0];
			}
		}

		return $ret;
	}

	/**
	 * Reset all table filters
	 *
	 */
	function reset()
	{
		if(!is_array($this->_filters))
		{
			$this->_loadFilters();
		}

		if(!empty($this->_filters))
		{
			foreach($this->_filters as $filter)
			{
				$this->disableFilter($filter);
			}
		}
	}

	/**
	 * Filters all non-Joomla! tables
	 *
	 */
	function filterNonJoomla()
	{
		$tableList = $this->getTableList();

		if(!empty($tableList))
		{
			$prefix = JApplication::getCfg('dbprefix');
			foreach($tableList as $tableName)
			{
				$table = str_replace($prefix, '#__', $tableName); // Get abstract name
				if($table == $tableName)
				{
					$this->enableFilter($table);
				}
			}
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
			' AND '.$db->nameQuote('class').' = '.$db->Quote('dbef');
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
}