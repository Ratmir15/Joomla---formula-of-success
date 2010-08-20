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
 * The Backup controller class
 *
 */
class JoomlapackControllerBackup extends JController
{
	/**
	 * Default task; shows the initial page where the user selects a profile
	 * and enters description and comment
	 *
	 */
	function display()
	{
		$format = JRequest::getCmd('format','html');

		// For raw view with default task use the default_raw.php template file
		if($format == 'raw')
		{
			JRequest::setVar('tpl', 'raw');
		}

		$registry =& JoomlapackModelRegistry::getInstance();
		if( ($format != 'raw') && ($registry->get('easymode', false)) )
		{
			// Easy Mode enabled and the defaut backup view was requested. Skip to backup start.
			$model =& $this->getModel('backup');
			JRequest::setVar('description', $model->getDescription());
			JRequest::setVar('comment', $model->getComment());
			JRequest::setVar('profile', 1);
			JRequest::setVar('task', 'backup');
			$this->backup();
			return;
		}

		parent::display();
	}

	/**
	 * Shows the backup page, where the backup takes place
	 *
	 */
	function backup()
	{
		$document =& JFactory::getDocument();
		$document->addCustomTag('<meta http-equiv="PRAGMA" content="NO-CACHE" />');
		$document->addCustomTag('<meta http-equiv="CACHE-CONTROL" content="NO-CACHE" />');
		$document->addCustomTag('<meta http-equiv="EXPIRES" content="Mon, 22 Jul 2002 11:12:01 GMT" />');

		JRequest::setVar('tpl','backup');

		// On backup page deactivate the menus
		JRequest::setVar('hidemainmenu', 1);

		// Switch the active profile for this backup attempt
		$newProfile = JRequest::getInt('profile', -1);

		if(!is_numeric($newProfile) || ($newProfile <= 0))
		{
			$this->setRedirect(JURI::base().'index.php?option='.JRequest::getCmd('option'), JText::_('PANEL_PROFILE_SWITCH_ERROR'), 'error' );
			return;
		}

		$session =& JFactory::getSession();
		$session->set('profile', $newProfile, 'joomlapack');

		parent::display();
	}

	/**
	 * Show the step page, sitting in an iframe, to step over the backup process.
	 *
	 */
	function step()
	{
		$document =& JFactory::getDocument();

		JRequest::setVar('tpl','step');
		jpimport('core.cube');
		$cube =& JoomlapackCUBE::getInstance();
		$cube->tick();
		$cube->save();
		parent::display();
	}

	/**
	 * Starts a backup in JS redirects mode
	 *
	 */
	function start()
	{
		$document =& JFactory::getDocument();

		JRequest::setVar('tpl','start');
		$description = JRequest::getString('description');
		$comment = JRequest::getString('comment', '', 'default', 4);

		jpimport('core.cube');
		JoomlapackCUBE::reset();
		$cube =& JoomlapackCUBE::getInstance();
		$cube->start($description, $comment);
		$cube->save();
		parent::display();
	}

	/**
	 * Shows the backup finished page.
	 *
	 */
	function finished()
	{
		$document =& JFactory::getDocument();
		$document->addCustomTag('<meta http-equiv="PRAGMA" content="NO-CACHE" />');
		$document->addCustomTag('<meta http-equiv="CACHE-CONTROL" content="NO-CACHE" />');
		$document->addCustomTag('<meta http-equiv="EXPIRES" content="Mon, 22 Jul 2002 11:12:01 GMT" />');

		JRequest::setVar('tpl','finished');
		parent::display();
	}

	/**
	 * Displays an error page.
	 *
	 */
	function error()
	{
		$document =& JFactory::getDocument();
		$document->addCustomTag('<meta http-equiv="PRAGMA" content="NO-CACHE" />');
		$document->addCustomTag('<meta http-equiv="CACHE-CONTROL" content="NO-CACHE" />');
		$document->addCustomTag('<meta http-equiv="EXPIRES" content="Mon, 22 Jul 2002 11:12:01 GMT" />');

		JRequest::setVar('tpl','error');
		parent::display();
	}
}