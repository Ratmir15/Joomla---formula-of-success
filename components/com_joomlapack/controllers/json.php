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

class JoomlapackControllerJson extends JController
{
	/**
	 * Starts a backup
	 * @return 
	 */
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
		// Get the description, if present; otherwise use the default description
		jimport('joomla.utilities.date');
		$user =& JFactory::getUser();
		$userTZ = $user->getParam('timezone',0);
		$dateNow = new JDate();
		$dateNow->setOffset($userTZ);
		$default_description = JText::_('BACKUP_DEFAULT_DESCRIPTION').' '.$dateNow->toFormat(JText::_('DATE_FORMAT_LC2'));
		$description = JRequest::getString('description', $default_description);
		// Start the backup (CUBE Operation)
		jpimport('core.cube');
		JoomlapackCUBE::reset();
		$cube =& JoomlapackCUBE::getInstance();
		$cube->start($description,'');
		$cube->save();

		// Return the JSON output
		parent::display(false);
	}

	/**
	 * task=start is an alias for task=display or lack of task definition altogether
	 */
	function start()
	{
		$this->display();
	}

	/**
	 * Step through the backup
	 * @return 
	 */
	function step()
	{
		// Check permissions
		$this->_checkPermissions();
		// Set the profile
		$this->_setProfile();

		jpimport('core.cube');
		$cube =& JoomlapackCUBE::getInstance();
		$array = $cube->getCUBEArray();

		if( ($array['Error'] == '') && ($array['HasRun'] != 1) )
		{
			$cube->tick();
			$cube->save();
		}

		// Return the JSON output
		parent::display(false);
	}

	/**
	 * Return the absolute path to the output directory
	 * @return 
	 */
	function getdirectory()
	{
		// Check permissions
		$this->_checkPermissions();

		parent::display(false);		
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

		// Is the key good? Check using 'keyhash'.
		$keyhash = JRequest::getVar('keyhash');
		$hashparts = explode(':', $keyhash, 2);
		$validKey=$registry->get('secretWord');

		$hash_to_check = $hashparts[0];
		$salt= $hashparts[1];
		$valid_hash = md5($validKey.$salt);

		if($hash_to_check != $valid_hash)
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

