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
 * The nag screen controller class
 *
 */
class JoomlapackControllerNag extends JController
{
	function display()
	{
		parent::display();
	}

	function agree()
	{
		$registry =& JoomlapackModelRegistry::getInstance();
		$registry->set('nagscreen', true);
		$registry->save();
		$registry->reload();
		$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=cpanel' );
		parent::redirect();
	}
}