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

/**
 * JoomlaPack factory class. This class is responsible for instanciating the various
 * objects used throughout the component in a consistent and uniform fashion.
 *
 */
class JPFactory
{
	/**
	 * Tries to load a JoomlPack class, located under the backend 'classes' directory
	 *
	 * @param string $className Dotted class notation, e.g. 'cube.cube' for 'cube/cube.php'
	 * @param bool $searchAllBackend If set to true, the whole backend is being searched
	 *
	 * @static
	 */
	function jpimport( $className, $searchAllBackend = false )
	{
		$parts = explode('.', $className); // Break apart at dots
		$newClassName = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_joomlapack'.DS.( $searchAllBackend ? '' : 'classes'.DS );
		$newClassName .= implode(DS,$parts); // Glue the pieces with the directory separator
		$newClassName .= '.php';

		if(!JFile::exists($newClassName))
		{
			JError::raiseError(500,'Inexistent JoomlaPack Class '.$className,'JoomlaPack class ' . $className . ' does not exist.' );
		} else {
			require_once($newClassName);
		}
	}
}

function jpimport( $className, $searchAllBackend = false )
{
	return JPFactory::jpimport($className, $searchAllBackend);
}
