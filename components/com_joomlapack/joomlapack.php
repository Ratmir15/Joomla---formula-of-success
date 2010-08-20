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

require_once(JPATH_SITE.DS.'administrator'.DS.'components'.DS.'com_joomlapack'.DS.'version.php');

// Apply profile selection, if any
$profile = JRequest::getInt('profile',1);
if(!JPSPECIALEDITION) $profile = 1;
if(!is_numeric($profile)) $profile = 1;
$session =& JFactory::getSession();
$session->set('profile', $profile, 'joomlapack');
JRequest::setVar('profile', $profile);

// Get the view and controller from the request, or set to default if they weren't set
JRequest::setVar('view', JRequest::getCmd('view','backup'));
JRequest::setVar('c', JRequest::getCmd('view')); // Black magic: Get controller based on the selected view

// Black Magic II: merge the default translation with the current translation, a la JoomlaPack 1.2.x
$jlang =& JFactory::getLanguage();
$jlang->load('com_joomlapack', JPATH_SITE, 'en-GB', true);
$jlang->load('com_joomlapack', JPATH_SITE, $jlang->getDefault(), true);
$jlang->load('com_joomlapack', JPATH_SITE, null, true);
$jlang->load('com_joomlapack', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_joomlapack', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_joomlapack', JPATH_ADMINISTRATOR, null, true);

// Preload the JPFactory
jimport('joomla.filesystem.file');
require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'classes'.DS.'factory.php';

// Load the utils helper library
jpimport('helpers.utils', true);
JoomlapackHelperUtils::getJoomlaPackVersion();

// Load the appropriate controller
$c = JRequest::getCmd('c','cpanel');
$path = JPATH_COMPONENT.DS.'controllers'.DS.$c.'.php';
jimport('joomla.filesystem.file');
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