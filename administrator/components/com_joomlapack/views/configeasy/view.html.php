<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 2.1
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

// Load framework base classes
jimport('joomla.application.component.view');

/**
 * JoomlaPack Configuration view class
 *
 */
class JoomlapackViewConfigeasy extends JView
{
	function display()
	{
		// Set the toolbar title; add a help button
		JToolBarHelper::title(JText::_('JOOMLAPACKEASY').':: <small><small>'.JText::_('CONFIGURATION').'</small></small>');
		JToolBarHelper::apply();
		JToolBarHelper::save();
		JToolBarHelper::cancel();
		JoomlapackHelperUtils::addLiveHelp('configeasy');
		$document =& JFactory::getDocument();
		$document->addStyleSheet(JURI::base().'components/com_joomlapack/assets/css/joomlapack.css');

		// Load the util helper
		$this->loadHelper('utils');

		// Load the model
		$model =& $this->getModel();
		// Pass on the lists
		$this->assign('actionlogginglist',	$model->getActionLoggingList() );
		$this->assign('settingsmodelist',		$model->getSettingsModeList() );

		// Let's pass the data
		$this->assign('OutputDirectory',			$model->getVar('OutputDirectoryConfig') );
		$this->assign('TarNameTemplate',			$model->getVar('TarNameTemplate') );
		$this->assign('settingsmode',				$model->getVar('settingsmode') );
		$this->assign('logLevel',					$model->getVar('logLevel') );

		// Also load the Configuration HTML helper
		$this->loadHelper('config');
		parent::display();
	}
}