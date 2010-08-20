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

// Make sure we have a profile set throughout the component's lifetime
$session =& JFactory::getSession();
$profile_id = $session->get('profile', null, 'joomlapack');
if(is_null($profile_id))
{
	// No profile is set in the session; use default profile
	$session->set('profile', 1, 'joomlapack');
}

// Get the view and controller from the request, or set to default if they weren't set
JRequest::setVar('view', JRequest::getCmd('view','cpanel'));
JRequest::setVar('c', JRequest::getCmd('view','cpanel')); // Black magic: Get controller based on the selected view

// Black magic: if the AJAX request parameters exist, we force the format to Raw.
if( JRequest::getString('rs','') )
{
	JRequest::setVar('format','raw');
}

// Preload the JPFactory
require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'classes'.DS.'factory.php';
jimport('joomla.filesystem.file');

// Load the JoomlaPack configuration and check user access permission
jpimport('models.registry', true);
$registry =& JoomlapackModelRegistry::getInstance();
$authlevel = $registry->get('authlevel',25); // Default authlevel = Super Administrators only
$user =& JFactory::getUser();
$gid = $user->gid;
unset($registry);
unset($user);
switch($authlevel)
{
	case 25:
		// Super administrator access only

		if($gid != 25)
		{
			$mainframe->redirect('index.php', JText::_('ALERTNOTAUTH'));
		}
		break;

	case 24:
		// Administrator access allowed
		if( ($gid != 25) && ($gid != 24) )
		{
			$mainframe->redirect('index.php', JText::_('ALERTNOTAUTH'));
		}
		break;

	case 23:
		// Managers access allowed
		if( ($gid != 25) && ($gid != 24) && ($gid != 23) )
		{
			$mainframe->redirect('index.php', JText::_('ALERTNOTAUTH'));
		}
		break;
}

// Black Magic II: merge the default translation with the current translation, a la JoomlaPack 1.2.x
$jlang =& JFactory::getLanguage();
$jlang->load('com_joomlapack', JPATH_SITE, 'en-GB', true);
$jlang->load('com_joomlapack', JPATH_SITE, $jlang->getDefault(), true);
$jlang->load('com_joomlapack', JPATH_SITE, null, true);
$jlang->load('com_joomlapack', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_joomlapack', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_joomlapack', JPATH_ADMINISTRATOR, null, true);

// Load the utils helper library
jpimport('helpers.utils', true);
JoomlapackHelperUtils::getJoomlaPackVersion();

// Load the appropriate controller
$c = JRequest::getCmd('c','cpanel');
$path = JPATH_COMPONENT_ADMINISTRATOR.DS.'controllers'.DS.$c.'.php';
if(JFile::exists($path))
{
	// The requested controller exists and there you load it...
	require_once($path);
}
else
{
	// Hmm... an invalid controller was passed
	JError::raiseError('500',JText::_('Unknown controller'));
}

// Instanciate and execute the controller
jimport('joomla.utilities.string');
$c = 'JoomlapackController'.ucfirst($c);
$controller = new $c();
$controller->execute(JRequest::getCmd('task','display'));

// Redirect
$controller->redirect();