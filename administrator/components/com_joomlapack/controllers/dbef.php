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

jpimport('controllers.filtercontrollerparent',true);

/**
 * MVC controller class for Database Exclusion filters
 *
 */
class JoomlapackControllerDbef extends FilterControllerParent
{
	/**
	 * Handles the "display" task, which displays a folder and file list
	 *
	 */
	function display()
	{
		parent::display();
	}

	/**
	 * Handles the "toggle" task, executed for non-AJAX operation of the DBEF page.
	 * Upon completion, it returns to the tables listing of the database.
	 *
	 */
	function toggle()
	{
		$table = JRequest::getVar('table');

		$url = JURI::base().'/index.php?option=com_joomlapack&view=dbef';

		if(is_null($table))
		{
			$this->setRedirect($url, JText::_('DBEF_ERROR_INVALIDTABLE'), 'error');
		}
		else
		{
			$model =& $this->getModel('dbef');
			$model->toggleFilter($table);

			if(JError::isError($model))
			{
				$this->setRedirect($url, JText::_('DBEF_ERROR_INVALIDTABLE'), 'error');
			}
			else
			{
				$this->setRedirect($url);
			}
		}

	}

	/**
	 * Resets database filters
	 *
	 */
	function reset()
	{
		$model =& $this->getModel('dbef');
		$model->reset();
		$url = JURI::base().'/index.php?option=com_joomlapack&view=dbef';
		$this->setRedirect($url, JText::_('DBEF_MSG_RESET'));
	}

	/**
	 * Filters out non-Joomla! tables
	 *
	 */
	function filternonjoomla()
	{
		$model =& $this->getModel('dbef');
		$model->filterNonJoomla();
		$url = JURI::base().'/index.php?option=com_joomlapack&view=dbef';
		$this->setRedirect($url, JText::_('DBEF_MSG_FILTERNONJOOMLA'));
	}

}