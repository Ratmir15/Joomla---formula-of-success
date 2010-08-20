<?php
/**
 * @package		JoomlaPack
 * @copyright	Copyright (C) 2006-2009 JoomlaPack Developers. All rights reserved.
 * @version		$Id$
 * @license 		http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @since		1.2.1
 *
 * JoomlaPack is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 **/
defined('_JEXEC') or die('Restricted access');

/**
 * A class for managing the filters collection. Please note that unless you call the getFilters()
 * method, none of the filter classes is actually loaded or instanciated, in order to conserve
 * memory and time required for including all those files.
 */
class JoomlapackCUBEFiltermanager
{
	/**
	 * The list of classes and their methods for retreiving the filter lists. Each
	 * array element is an indexed filter with th following keys:
	 * - "file": filename to include, relative to the 'includes' directory
	 * - "class": class to be instanciated
	 *
	 * @var array
	 * @access private
	 */
	var $_filters;

	/**
	 * Adds a $_filters to the collection
	 *
	 * @param string $file Filename required for inclusion, relative to the 'includes' directory
	 * @param string $class Filter class to be instanciated
	 */
	function addFilter( $file, $class )
	{
		if( is_null($file) || is_null($class) )
		{
			return false;
		} else {
			$newFilter = array(
				"file" => $file,
				"class" => $class
			);
			$this->_filters[] = $newFilter;
		}
	}

	/**
	 * Returns an aggregate of all filters gathered by applying the getFilters() method on
	 * each separate object defined in $_filters with the given $predicate. In simple English,
	 * calling getFilters("folder") will return an array of all folder filters gathered from
	 * all filter objects.
	 *
	 * @param string $predicate
	 * @return array
	 */
	function getFilters( $predicate )
	{
		$returnedFilterList = array();

		foreach( $this->_filters as $filterDescriptor )
		{
			// Try loading the file
			jpimport( $filterDescriptor['file'] );
			// Instanciate filter class
			$filterClass = $filterDescriptor['class'];
			$filterObject = new $filterClass;
			$filterObject->init();
			// Get the filters array and append it to the lot
			$newFilters = $filterObject->getFilters( $predicate );
			if( !is_array( $newFilters ) ) $newFilters = array();
			$returnedFilterList = array_merge( $returnedFilterList, $newFilters );
			// Cleanup memory hogging objects
			unset( $newFilters );
			unset( $filterObject );
		}

		// Return the lot of filters
		return $returnedFilterList;
	}

	/**
	 * Initializes the array of filters. Reads the data of the filter.ini in the
	 * classes/filter directory.
	 */
	function init()
	{
		// Load the filter.ini
		jpimport('helpers.utils',true);

		// Find and parse all ini files in the given directory
		$ini_path = JPATH_COMPONENT_ADMINISTRATOR.DS.'classes'.DS.'filter';
		jimport('joomla.filesystem.folder');
		$ini_files = JFolder::files($ini_path,'\.ini$');
		$filterArray = array();
		if(count($ini_files))
		{
			foreach($ini_files as $sourceINI)
			{
				$myData = JoomlapackHelperUtils::parse_ini_file($ini_path.DS.$sourceINI, true);
				foreach($myData as $key => $value)
					$filterArray[$key] = $value;
			}
		}

		// Walk through INI file entries and add them to the filter list
		foreach($filterArray as $filter){
			$this->addFilter('filter.'.$filter['include'], $filter['class']);
		}
	}
}