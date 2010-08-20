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

class JoomlapackControllerLight extends JController
{
	/**
	 * Controller for the default task (login & profile selection)
	 */
	function display()
	{
		// Enforce raw mode - I need to be in full control!
		$document =& JFactory::getDocument();
		if( $document->getType() != 'raw' )
		{
			$url = JURI::base().'index.php?option=com_joomlapack&view=light&format=raw';
			$this->setRedirect($url);
			$this->redirect();
			return;
		}
		$document->setType('raw');		
		parent::display(false);
	}

	/**
	 * Tries to authenticate the user and start the backup, or send him back to the default task
	 */
	function authenticate()
	{
		// Enforce raw mode - I need to be in full control!
		$document =& JFactory::getDocument();
		$document->setType('raw');		
		if(!$this->_checkPermissions())
		{
			parent::redirect();
		}
		else
		{
			$this->_setProfile();
			jimport('joomla.utilities.date');
			jpimport('core.cube');
			JoomlapackCUBE::reset();
			$cube =& JoomlapackCUBE::getInstance();
			$user =& JFactory::getUser();
			$userTZ = $user->getParam('timezone',0);
			$dateNow = new JDate();
			$dateNow->setOffset($userTZ);
			$cube->start(JText::_('BACKUP_DEFAULT_DESCRIPTION').' '.$dateNow->toFormat(JText::_('DATE_FORMAT_LC2'),''));
			$cube->save();
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=light&task=step&key='.JRequest::getVar('key').'&profile='.JRequest::getInt('profile').'&format=raw');
		}
	}

	/**
	 * Step through the backup, informing user of the progress
	 */
	function step()
	{
		// Enforce raw mode - I need to be in full control!
		$document =& JFactory::getDocument();
		$document->setType('raw');		

		JRequest::setVar('tpl','step');
			
		jpimport('core.cube');
		$cube =& JoomlapackCUBE::getInstance();
		$array = $cube->getCUBEArray();
			
		if($array['Error'] != '')
		{
			// An error occured
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=light&format=raw&task=error&error='.$array['Error']);
			parent::redirect();
		}
		elseif($array['HasRun'] == 1)
		{
			// All done
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=light&format=raw&task=done');
			parent::redirect();
		}
		else
		{
			$cube->tick();
			$cube->save();
			parent::display();
		}
	}

	/**
	 * Informs the user of an error condition (poor soul, he can't fix it w/out backend access)
	 */
	function error()
	{
		// Enforce raw mode - I need to be in full control!
		$document =& JFactory::getDocument();
		$document->setType('raw');		
		JRequest::setVar('tpl','error');
		parent::display();
	}

	/**
	 * Informs the user that all is done
	 */
	function done()
	{
		// Enforce raw mode - I need to be in full control!
		$document =& JFactory::getDocument();
		$document->setType('raw');		
		JRequest::setVar('tpl','done');
		parent::display();
	}

	/**
	 * Check that the user has sufficient permissions, or die in error
	 *
	 */
	function _checkPermissions()
	{
		jpimport('models.registry', true);
		$registry =& JoomlapackModelRegistry::getInstance();

		// Is frontend backup enabled?
		$febEnabled = $registry->get('enableFrontend');
		if(!$febEnabled)
		{
			$message = JText::_('ERROR_NOT_ENABLED');
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=light&format=raw', $message, 'error');
			return false;
		}

		// Is the key good?
		$key = JRequest::getVar('key');
		$validKey=$registry->get('secretWord');
		if($key != $validKey)
		{
			$message = JText::_('ERROR_INVALID_KEY');
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=light&format=raw', $message, 'error');
			return false;
		}

		return true;
	}

	function _setProfile()
	{
		// Set profile
		$profile = JRequest::getInt('profile', 1);
		if(!JPSPECIALEDITION) $profile = 1;
		if(!is_numeric($profile)) $profile = 1;
		$session =& JFactory::getSession();
		$session->set('profile', $profile, 'joomlapack');
		// Reload registry
		jpimport('models.registry', true);
		$registry =& JoomlapackModelRegistry::getInstance();
		$registry->reload();
	}
}