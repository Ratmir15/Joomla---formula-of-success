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
jimport('joomla.application.component.controller');

/**
 * The Configuration Editor (Easy Mode) controller class
 *
 */
class JoomlapackControllerConfigeasy extends JController
{
	/**
	 * Displays the editor page
	 *
	 */
	function display()
	{
		// Make sure we're on easy mode; otherwise redirect to Control Panel
		$registry =& JoomlapackModelRegistry::getInstance();
		if(!$registry->get('easymode',false))
		{
			$this->setRedirect(JURI::base().'index.php?option='.JRequest::getCmd('option').'&view=cpanel');
			$this->redirect();
		}

		parent::display();
	}

	/**
	 * Handle the apply task which saves settings and shows the editor again
	 *
	 */
	function apply()
	{
		$model =& $this->getModel('registry');
		$data = JRequest::getVar('var', array(), 'default', 'array');

		$model->bindFromData($data);
		$model->set('BackupType','full');
		$model->set('enableFrontend',false);
		$model->set('enableSizeQuotas',false);
		$model->set('enableCountQuotas',false);
		$model->set('authlevel',25);
		$model->set('nagscreen',true);

		$model->save();
		$this->setRedirect(JURI::base().'index.php?option='.JRequest::getCmd('option').'&view=configeasy', JText::_('CONFIG_SAVE_OK'));
	}

	/**
	 * Handle the save task which saves settings and returns to the cpanel
	 *
	 */
	function save()
	{
		$this->apply();
		$this->setRedirect(JURI::base().'index.php?option='.JRequest::getCmd('option'), JText::_('CONFIG_SAVE_OK'));
	}

	/**
	 * Handle the cancel task which doesn't save anything and returns to the cpanel
	 *
	 */
	function cancel()
	{
		$this->setRedirect(JURI::base().'index.php?option='.JRequest::getCmd('option').'&view=cpanel');
	}
}