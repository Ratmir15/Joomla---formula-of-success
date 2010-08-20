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
jimport('joomla.application.component.view');

class JoomlapackViewJson extends JView
{
	function display($tpl = null)
	{
		// # Fix 2.4: Drop the output buffer
		if(function_exists('ob_clean')) @ob_clean();
		parent::display('raw');
	}
}
?>