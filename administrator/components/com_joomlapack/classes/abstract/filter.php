<?php
/**
 * @package		JoomlaPack
 * @copyright	Copyright (C) 2006-2009 JoomlaPack Developers. All rights reserved.
 * @version		$Id$
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @since		1.2.1
 *
 * JoomlaPack is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 **/

// Ensure this file is being included by a parent file - Joomla! 1.0.x and 1.5 compatible
defined('_JEXEC') or die('Restricted access');

/**
 * Represent a class which returns a filter, inclusion or exclusion, for
 * any domain required (be it directories, database or mixed)
 * @abstract
 */
class JoomlapackCUBEFilter extends JObject
{
	/**
	 * A simple array of the folder names to exclude (absolute paths)
	 *
	 * @var array
	 * @access protected
	 */
	var $_folderFilters;

	/**
	 * A simple array of single files to be excluded (absolute paths)
	 *
	 * @var array
	 * @access protected
	 */
	var $_singleFileFilters;

	/**
	 * A simple array of database table names to exclude
	 *
	 * @var array
	 * @access protected
	 */
	var $_databaseFilters;

	/**
	 * A simple array of folders to forcibly include in the backup (absolute paths)
	 *
	 * @var array
	 * @access protected
	 */
	var $_includeFolderFilters;

	/**
	 * A simple array of folders to skip including their files (absolute paths).
	 * NOTE: It forces exclusion of FILES, not SUBDIRECTORIES!
	 *
	 * @var array
	 * @access protected
	 */
	var $_skipContainedFilesFilter;

	/**
	 * A simple array of folders to skip including their subdirectories (absolute paths).
	 * NOTE: It forces exclusion of SUBDIRECTORIES, not FILES!
	 *
	 * @var array
	 * @access protected
	 */
	var $_skipContainedDirectoriesFilter;

	/**
	 * Initializes the filters arrays, e.g. load settings of the database or a file
	 * @abstract
	 */
	function init()
	{
	}

	/**
	 * Loads the filters off the database
	 *
	 * @access protected
	 * @return array
	 *
	 */
	function _loadFilters()
	{
		// Get active profile
		$session =& JFactory::getSession();
		$profile = $session->get('profile', null, 'joomlapack');

		$db =& JFactory::getDBO();
		$sql = 'SELECT '.$db->nameQuote('value').' FROM #__jp_exclusion WHERE '.
		$db->nameQuote('class') . ' = ' . $db->Quote($this->_filterClass).
				' AND '.$db->nameQuote('profile').' = '.$db->Quote($profile);
		$db->setQuery($sql);
		$temp = $db->loadRowList();
		$out = array();
		if(count($temp) > 0)
		{
			foreach($temp as $entry)
			{
				$out[] = $entry[0];
			}
		}
		return $out;
	}

	/**
	 * Gets the filters of the relevant category
	 *
	 * @param string $predicate It's "singlefile", "folder", "includefolder" or
	 * 				 "database", depending on the filter type you want to retreive
	 * @return array
	 * @final
	 */
	function getFilters( $predicate )
	{
		switch( $predicate )
		{
			case "singlefile": // Single File Filters
				return $this->_singleFileFilters;
				break;
			case "folder": // Directory Exclusion Filters
				return $this->_folderFilters;
				break;
			case "database": // Database Table Filters
				return $this->_databaseFilters;
				break;
			case "includefolder": // (reserved for future use)
				return $this->_includeFolderFilters;
				break;
			case "containedfiles":
				return $this->_skipContainedFilesFilter;
				break;
			case "containeddirectories":
				return $this->_skipContainedDirectoriesFilter;
				break;
			default:
				return array();
				break;
		}
	}
}