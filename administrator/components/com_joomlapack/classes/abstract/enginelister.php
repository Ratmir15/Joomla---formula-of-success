<?php
/**
 * @package		JoomlaPack
 * @copyright	Copyright (C) 2006-2009 JoomlaPack Developers. All rights reserved.
 * @version		$Id$
 * @license 		http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @since		1.3
 **/

// Ensure this file is being included by a parent file - Joomla! 1.0.x and 1.5 compatible
defined('_JEXEC') or die('Restricted access');

/**
 * Filesystem lsiter engine abstract class
 *
 * @abstract
 */
class JoomlapackCUBELister extends JObject
{
	/**
	 * State of the BREAKFLAG (force break step flag)
	 * @var bool
	 */
	var $BREAKFLAG = false;

	/**
	 * Gets all the files of a given folder
	 *
	 * @param string $folder The absolute path to the folder to scan for files
	 */
	function &getFiles($folder)
	{
		// ABSTRACT
	}

	/**
	 * Gets all the folders (subdirectories) of a given folder
	 *
	 * @param string $folder The absolute path to the folder to scan for subdirectories
	 */
	function &getFolders($folder)
	{
		// ABSTRACT
	}
}