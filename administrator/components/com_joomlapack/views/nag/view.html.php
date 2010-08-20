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
 * JoomlaPack Multiple databases restoration view class
 *
 */
class JoomlapackViewNag extends JView
{
	function display()
	{
		JToolBarHelper::title(JText::_('JOOMLAPACK'));
		$document =& JFactory::getDocument();
		$document->addStyleSheet(JURI::base().'components/com_joomlapack/assets/css/joomlapack.css');
		parent::display();
	}

}