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
 * Multiple databases definition controller class
 *
 */
class JoomlapackControllerMultidb extends JController
{
	/**
	 * Display the list of MultiDB definitions
	 *
	 */
	function display()
	{
		parent::display();
	}

	/**
	 * Apply settings and return to edit view
	 *
	 */
	function apply()
	{
		$model =& $this->getModel('multidb');
		$model->save();
		$this->setRedirect('index.php?option=com_joomlapack&view='.JRequest::getCmd('view').'&task=edit&id='.JRequest::getInt('id'));
	}

	/**
	 * Save settings and return to default (list) view
	 *
	 */
	function save()
	{
		$this->apply(); // Delegate saving to the apply() method
		$this->setRedirect('index.php?option=com_joomlapack&view='.JRequest::getCmd('view'));
	}

	function remove()
	{
		$model =& $this->getModel('multidb');
		$model->remove();
		$this->setRedirect('index.php?option=com_joomlapack&view='.JRequest::getCmd('view'));
	}

	/**
	 * Adds a new record
	 *
	 */
	function add()
	{
		JRequest::setVar('id',0); // Force new entry
		$this->edit(); // Delegate task to the edit() method
	}

	function edit()
	{
		JRequest::setVar('hidemainmenu', 1);
		JRequest::setVar('layout', 'default_edit');
		parent::display();
	}

	function copy()
	{
		$model =& $this->getModel('multidb');
		$model->copy();
		$this->setRedirect('index.php?option=com_joomlapack&view='.JRequest::getCmd('view'));
	}

	function cancel()
	{
		$this->setRedirect('index.php?option=com_joomlapack&view='.JRequest::getCmd('view'));
	}
}