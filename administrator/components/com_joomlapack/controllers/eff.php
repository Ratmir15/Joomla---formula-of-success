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
jpimport('controllers.filtercontrollerparent',true);

/**
 * EFF controller class
 *
 */
class JoomlapackControllerEff extends FilterControllerParent
{
	/**
	 * Display the list of EFF definitions
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
		$model =& $this->getModel('eff');
		$model->save();
		$this->setRedirect('index.php?option=com_joomlapack&view=eff&task=edit&id='.JRequest::getInt('id'));
	}

	/**
	 * Save settings and return to default (list) view
	 *
	 */
	function save()
	{
		$this->apply(); // Delegate saving to the apply() method
		$this->setRedirect('index.php?option=com_joomlapack&view=eff');
	}

	function remove()
	{
		$model =& $this->getModel('eff');
		$model->remove();
		$this->setRedirect('index.php?option=com_joomlapack&view=eff');
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
		$model =& $this->getModel('eff');
		$model->copy();
		$this->setRedirect('index.php?option=com_joomlapack&view=eff');
	}

	function cancel()
	{
		$this->setRedirect('index.php?option=com_joomlapack&view=eff');
	}
}