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
jimport('joomla.application.component.controller');

/**
 * The Control Panel controller class
 *
 */
class JoomlapackControllerCpanel extends JController
{
	/**
	 * Displays the Control Panel (main page)
	 * Accessible at index.php?option=com_joomlapack
	 *
	 */
	function display()
	{
		$registry =& JoomlapackModelRegistry::getInstance();

		// FIX 2.1.b2 - Disabled the nag screen because of incompatibility with some servers
		/*
		// Make sure the user has seen the license nag screen
		$nagscreen = $registry->get('nagscreen',false);
		if(!$nagscreen)
		{
		$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=nag' );
		return;
		}
		*/

		// Invalidate stale backups
		jpimport('core.cube');
		JoomlapackCUBE::reset(true);

		// If this is the Easy Mode, force switch to profile #1 (default profile)
		if($registry->get('easymode', false))
		{
			$session =& JFactory::getSession();
			$session->set('profile', 1, 'joomlapack');
		}

		// Display the panel
		parent::display();
	}

	function switchprofile()
	{
		$newProfile = JRequest::getInt('profileid', -10);

		if(!is_numeric($newProfile) || ($newProfile <= 0))
		{
			$this->setRedirect(JURI::base().'index.php?option='.JRequest::getCmd('option'), JText::_('PANEL_PROFILE_SWITCH_ERROR'), 'error' );
			return;
		}

		$session =& JFactory::getSession();
		$session->set('profile', $newProfile, 'joomlapack');
		$this->setRedirect(JURI::base().'index.php?option='.JRequest::getCmd('option'), JText::_('PANEL_PROFILE_SWITCH_OK'));
	}

	/**
	 * Applies the troubleshooter's suggestion and moves on to the backup page
	 *
	 */
	function troubleshooter()
	{
		$model =& $this->getModel('cpanel');
		$registry =& JoomlapackModelRegistry::getInstance();

		$nextStep = $model->nextSettingsMode();

		if(is_null($nextStep))
		{
			// Oops! No further action to take, brother!
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=cpanel', JText::_('CPANEL_TROUBLESHOOTER_ERROR'), 'error');
			return;
		}
		else
		{
			$registry->set('settingsmode',$nextStep);
			$registry->save();
			$registry->reload();
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=backup');
		}
	}
}