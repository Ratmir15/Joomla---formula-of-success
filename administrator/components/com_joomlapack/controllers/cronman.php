<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 2.3
 * 
 * CRON Script Manager Controller
 */

// Protect from direct access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.controller');


class JoomlapackControllerCronman extends JController
{
	
	function display()
	{
		parent::display();
	}

	/**
	 * Handles applying the changes (versus merely saving them)
	 */
	function apply()
	{
		// Just delegate the task
		$this->save();
	}

	/**
	 * Processes saving an entry (new or old) and redirecting to the list view
	 *
	 */
	function save()
	{
		$data = JRequest::getVar('var',array(),'POST','array');
		$task = JRequest::getCmd('task','save');

		$model =& $this->getModel('cronman');
		if($model->save($data))
		{
			// Show a "SAVE OK" message
			$message = JText::_('CRONMAN_SAVE_OK');
			$type = 'message';
		}
		else
		{
			// Show message on failure
			$message = JText::_('CRONMAN_SAVE_ERROR');
			$type = 'error';
		}

		// Redirect, based on task
		switch($task)
		{
			case 'save':
				$this->setRedirect('index.php?option=com_joomlapack&view=cronman', $message, $type);
				break;
					
			case 'apply':
				$insertid = $model->getId();
				$this->setRedirect('index.php?option=com_joomlapack&view=cronman&task=edit&id='.$insertid, $message, $type);
				break;
		}
	}
	
	/**
	 * Duplicates a CRON helper script
	 */
	function copy()
	{
		$model =& $this->getModel('cronman');
		$cid = JRequest::getVar('cid',array(),'default','array');
		$id = JRequest::getInt('id', null);
		if(empty($id))
		{
			if(!empty($cid) && is_array($cid))
			{
				$id = array_shift($cid);
			}
			else
			{
				$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=cronman', JText::_('CRONMAN_ERROR_INVALIDID'), 'error');
				return;
			}
		}

		if($model->copy($id))
		{
			// Show a "COPY OK" message
			$message = JText::_('CRONMAN_COPY_OK');
			$type = 'message';
		}
		else
		{
			// Show message on failure
			$message = JText::_('CRONMAN_COPY_ERROR');
			$type = 'error';
		}
		// Redirect
		$this->setRedirect('index.php?option=com_joomlapack&view=cronman', $message, $type);		
	}
	
	/**
	 * Processes removing an entry and redirecting to list view
	 *
	 */
	function remove()
	{
		$model =& $this->getModel('cronman');
		$cid = JRequest::getVar('cid',array(),'default','array');
		$id = JRequest::getInt('id', null);
		if(empty($id))
		{
			if(empty($cid) && is_array($cid))
			{
				$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=cronman', JText::_('CRONMAN_ERROR_INVALIDID'), 'error');
				return;
			}
		}
		else
		{
			$cid = array($id);
		}

		foreach($cid as $id)
		{
			if($model->remove($id))
			{
				// Show an OK message
				$message = JText::_('CRONMAN_REMOVE_OK');
				$type = 'message';
			}
			else
			{
				// Show message on failure
				$message = JText::_('CRONMAN_REMOVE_ERROR');
				$type = 'error';
				break;
			}
		}
		// Redirect
		$this->setRedirect('index.php?option=com_joomlapack&view=cronman', $message, $type);
}
	
	/**
	 * Shows a view where you can add a new record. Actually, delegates to edit().
	 *
	 */
	function add()
	{
		$this->edit(); // Delegate execution
	}

	/**
	 * Shows the add/edit screen. Forces the layout, in order to show the correct form.
	 *
	 */
	function edit()
	{
		JRequest::setVar('hidemainmenu', 1);
		JRequest::setVar('layout', 'default_edit');
		parent::display();
	}

	/**
	 * Cancel profile editing
	 *
	 */
	function cancel()
	{
		$this->setRedirect('index.php?option=com_joomlapack&view=cronman');
	}

	
}