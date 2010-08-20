<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 2.3
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

// Load framework base classes
jimport('joomla.application.component.view');

/**
 * MVC View for CRON Script Manager's AJAX
 *
 */
class JoomlapackViewCronman extends JView
{
	function display()
	{
		parent::display('raw');
	}
}