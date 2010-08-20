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
jimport('joomla.application.component.controller');

/**
 * Folder bowser controller
 *
 */
class JoomlapackControllerBrowser extends JController
{
	function display()
	{
		JRequest::setVar('format','raw');
		parent::display();
	}
}