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
 * MVC View for Profiles management
 *
 */
class JoomlapackViewProfiles extends JView
{
	function display($tpl = null)
	{
		$model = $this->getModel('profiles');
		$xml =& $model->export();
		$this->assign('xml', $xml);

		$tpl = JRequest::getVar('tpl');

		parent::display($tpl);
	}
}
?>