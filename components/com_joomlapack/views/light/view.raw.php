<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 2.1
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

// Load framework base classes
jimport('joomla.application.component.view');

class JoomlapackViewLight extends JView
{
	function display($tpl = null)
	{
		$task = JRequest::getCmd('task','default');

		switch($task)
		{
			case 'step':
				$cube =& JoomlapackCUBE::getInstance();
				$array = $cube->getCUBEArray();
				$this->assign('cube', $array);
				break;

			case 'error':
				$this->assign('errormessage', JRequest::getVar('error',''));
				break;

			case 'done':
				break;

			case 'default':
			default:
				$model =& $this->getModel();
				$this->assignRef('profilelist', $model->getProfiles());
				break;
		}

		parent::display(JRequest::getCmd('tpl',null));
	}
}
