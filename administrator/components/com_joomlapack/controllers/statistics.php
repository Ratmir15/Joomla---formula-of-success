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
 * MVC controller class for statistics
 *
 */
class JoomlapackControllerStatistics extends JController
{
	/**
	 * Displays statistics
	 *
	 */
	function display()
	{
		parent::display();
	}

	/**
	 * Cancel out
	 *
	 */
	function cancel()
	{
		$this->setRedirect('index.php?option='.JRequest::getCmd('option').'&view='.JRequest::getCmd('view'));
	}
}