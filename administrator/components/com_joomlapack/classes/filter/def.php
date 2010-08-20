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

class JoomlapackFilterDEF extends JoomlapackCUBEFilter
{

	var $_filterClass = 'def';

	/**
	 * Implements the init method of JoomlapackFilter
	 *
	 */
	function init()
	{
		$configuration =& JoomlapackModelRegistry::getInstance();

		$temp = $this->_loadFilters();

		$this->_folderFilters = array();

		if (DS == '\\') {
			if (is_array($temp)) foreach($temp as $filter)
			{
				$this->_folderFilters[] = JoomlapackHelperUtils::TranslateWinPath(JPATH_SITE.DS.$filter);
			}
		}
		else
		{
			if (is_array($temp)) foreach($temp as $filter)
			{
				$this->_folderFilters[] = JPATH_SITE.DS.$filter;
			}
		}

		// Add any leftover installation directory to exclusion filters. THIS SHOULD NEVER BE NECESSARY ON A REAL SITE!
		$this->_folderFilters[] = JoomlapackHelperUtils::TranslateWinPath(JPATH_SITE.DS.'installation');
	}
}