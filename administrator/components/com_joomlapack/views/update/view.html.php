<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 2.2
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

// Load framework base classes
jimport('joomla.application.component.view');

/**
 * MVC View for Live Update
 *
 */
class JoomlapackViewUpdate extends JView
{
	function display()
	{
		$task = JRequest::getCmd('task');
		$force = ($task == 'force');

		// Set the toolbar title; add a help button
		JToolBarHelper::title(JText::_('JOOMLAPACK').':: <small><small>'.JText::_('LIVEUPDATE')).'</small></small>';
		JToolBarHelper::back('Back', 'index.php?option='.JRequest::getCmd('option'));
		JoomlapackHelperUtils::addLiveHelp('liveupdate');

		// Load the model
		$model =& $this->getModel();
		$updates =& $model->getUpdates($force);
		$this->assignRef('updates', $updates);

		$document =& JFactory::getDocument();
		$document->addStyleSheet(JURI::base().'components/com_joomlapack/assets/css/joomlapack.css');
		parent::display();
	}
}