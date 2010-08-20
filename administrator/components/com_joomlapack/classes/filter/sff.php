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
defined('_JEXEC') or die('Restricted access');

class JoomlapackFilterSFF extends JoomlapackCUBEFilter
{

	var $_filterClass = 'sff';

	/**
	 * Loads the file filters off the database and stores them in the _singleFileFilters array
	 *
	 */
	function init()
	{
		$configuration =& JoomlapackModelRegistry::getInstance();

		$temp = $this->_loadFilters();

		$this->_singleFileFilters = array();

		if (DS == '\\')
		{
			if (is_array($temp)) foreach($temp as $filter)
			{
				$this->_singleFileFilters[] = JoomlapackHelperUtils::TranslateWinPath(JPATH_SITE.DS.$filter);
			}
		}
		else
		{
			if (is_array($temp)) foreach($temp as $filter)
			{
				$this->_singleFileFilters[] = JPATH_SITE.DS.$filter;
			}
		}
	}
}