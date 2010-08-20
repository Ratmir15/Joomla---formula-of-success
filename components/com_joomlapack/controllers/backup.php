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

class JoomlapackControllerBackup extends JController
{
	function display()
	{
		// Check permissions
		$this->_checkPermissions();
		// Set the profile
		$this->_setProfile();
		// Force the output to be of the raw format type
		JRequest::setVar('format', 'raw');
		$document =& JFactory::getDocument();
		$document->setType('raw');

		// Start the backup
		jimport('joomla.utilities.date');
		jpimport('core.cube');
		JoomlapackCUBE::reset();
		$cube =& JoomlapackCUBE::getInstance();
		$jconfig =& JFactory::getConfig();
		$userTZ = $jconfig->get('config.offset');
		/*
		$user =& JFactory::getUser();
		$userTZ = $user->getParam('timezone',0);
		*/
		$dateNow = new JDate();
		$dateNow->setOffset($userTZ);
		$cube->start(JText::_('BACKUP_DEFAULT_DESCRIPTION').' '.$dateNow->toFormat(JText::_('DATE_FORMAT_LC2'),''));
		$cube->save();
		$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=backup&task=step&key='.JRequest::getVar('key').'&profile='.JRequest::getInt('profile',1).'&format=raw');
		parent::display();
	}

	function step()
	{
		jpimport('core.cube');
		$cube =& JoomlapackCUBE::getInstance();
		$array = $cube->getCUBEArray();

		if($array['Error'] != '')
		{
			// An error occured
			die('500 ERROR -- '.$array['Error']);
		}
		elseif($array['HasRun'] == 1)
		{
			// All done
			die('200 OK');
		}
		else
		{
			$cube->tick();
			$cube->save();
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=backup&task=step&key='.JRequest::getVar('key').'&profile='.JRequest::getInt('profile',1).'&format=raw');
		}
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
			die('403 '.JText::_('ERROR_NOT_ENABLED'));
		}

		// Is the key good?
		$key = JRequest::getVar('key');
		$validKey=$registry->get('secretWord');
		if($key != $validKey)
		{
			die('403 '.JText::_('ERROR_INVALID_KEY'));
		}
	}

	function _setProfile()
	{
		// Set profile
		$profile = JRequest::getInt('profile',1);
		if(!JPSPECIALEDITION) $profile = 1;
		if(!is_numeric($profile)) $profile = 1;
		$session =& JFactory::getSession();
		$session->set('profile', $profile, 'joomlapack');
	}
}