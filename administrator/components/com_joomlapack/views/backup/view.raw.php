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

/**
 * The RAW mode view class for the backup page. It serves a double purpose. On one hand,
 * it processes AJAX requests. On the other hand (when task=step), it displays the iframe
 * contents.
 *
 */
class JoomlapackViewBackup extends JView
{
	function display()
	{
		parent::display(JRequest::getCmd('tpl',null));
	}
}
?>