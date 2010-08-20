<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

// Load framework base classes
jimport('joomla.application.component.view');

/**
 * MVC View for Log
 *
 */
class JoomlapackViewLog extends JView
{
	function display()
	{
		// Add toolbar buttons
		JToolBarHelper::title(JText::_('JOOMLAPACK').': <small><small>'.JText::_('VIEWLOG').'</small></small>');
		JToolBarHelper::back('Back', 'index.php?option='.JRequest::getCmd('option'));
		JToolBarHelper::spacer();
		$document =& JFactory::getDocument();
		$document->addStyleSheet(JURI::base().'components/com_joomlapack/assets/css/joomlapack.css');
		JoomlapackHelperUtils::addLiveHelp('log');

		parent::display();
	}
}