<?php
/**
 * @package		JoomlaPack
 * @copyright	Copyright (C) 2006-2009 JoomlaPack Developers. All rights reserved.
 * @version		$Id$
 * @license 		http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @since		2.1
 *
 * JoomlaPack is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 **/
defined('_JEXEC') or die('Restricted access');

class JoomlapackFilterSkipdirs extends JoomlapackCUBEFilter
{
	var $_filterClass = 'Skipdirs';

	/**
	 * Implements the init method of JoomlapackFilter
	 *
	 */
	function init()
	{
		$jreg =& JFactory::getConfig();
		$configuration =& JoomlapackModelRegistry::getInstance();
		$this->_skipContainedDirectoriesFilter = array();

		$temp = $this->_loadFilters();

		if (DS == '\\')
		{
			if (is_array($temp)) foreach($temp as $filter)
			{
				$this->_skipContainedDirectoriesFilter[] = JoomlapackHelperUtils::TranslateWinPath(JPATH_SITE.DS.$filter);
			}
		}
		else
		{
			if (is_array($temp)) foreach($temp as $filter)
			{
				$this->_skipContainedDirectoriesFilter[] = JPATH_SITE.DS.$filter;
			}
		}

		// Add temporary and output directories
		$this->_skipContainedDirectoriesFilter[] = JoomlapackHelperUtils::TrimTrailingSlash(JoomlapackHelperUtils::TranslateWinPath($configuration->get('OutputDirectory')));
		$this->_skipContainedDirectoriesFilter[] = JoomlapackHelperUtils::TrimTrailingSlash(JoomlapackHelperUtils::TranslateWinPath($configuration->getTemporaryDirectory()));
		$this->_skipContainedDirectoriesFilter[] = JoomlapackHelperUtils::TrimTrailingSlash(JoomlapackHelperUtils::TranslateWinPath($jreg->getValue('config.tmp_path')));
		$this->_skipContainedDirectoriesFilter[] = JoomlapackHelperUtils::TrimTrailingSlash(JoomlapackHelperUtils::TranslateWinPath(JPATH_CACHE));
		$this->_skipContainedDirectoriesFilter[] = JoomlapackHelperUtils::TrimTrailingSlash(JoomlapackHelperUtils::TranslateWinPath(JPATH_ADMINISTRATOR.DS.'cache'));
		$this->_skipContainedDirectoriesFilter[] = JoomlapackHelperUtils::TrimTrailingSlash(JoomlapackHelperUtils::TranslateWinPath(JPATH_ROOT.DS.'cache'));
		// Hack: add the same paths untranslated
		$this->_skipContainedDirectoriesFilter[] = JoomlapackHelperUtils::TrimTrailingSlash($configuration->get('OutputDirectory'));
		$this->_skipContainedDirectoriesFilter[] = JoomlapackHelperUtils::TrimTrailingSlash($configuration->getTemporaryDirectory());
		$this->_skipContainedDirectoriesFilter[] = JoomlapackHelperUtils::TrimTrailingSlash($jreg->getValue('config.tmp_path'));
		$this->_skipContainedDirectoriesFilter[] = JoomlapackHelperUtils::TrimTrailingSlash(JPATH_CACHE);
		$this->_skipContainedDirectoriesFilter[] = JoomlapackHelperUtils::TrimTrailingSlash(JPATH_ADMINISTRATOR.DS.'cache');
		$this->_skipContainedDirectoriesFilter[] = JoomlapackHelperUtils::TrimTrailingSlash(JPATH_ROOT.DS.'cache');
	}
}
?>