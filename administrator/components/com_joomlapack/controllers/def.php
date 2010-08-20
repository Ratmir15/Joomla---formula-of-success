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
jpimport('controllers.filtercontrollerparent',true);

/**
 * MVC controller class for Directory Exclusion filters
 *
 */
class JoomlapackControllerDef extends FilterControllerParent
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
	 * Handles the "toggle" task, executed for non-AJAX operation of the DEF page.
	 * Upon completion, it returns to the directory listing of the folder.
	 *
	 */
	function toggle()
	{
		$item = JRequest::getVar('item',null,'default','none',1);
		$folder = JRequest::getVar('folder');

		$filePath=trim($folder.DS.$item, DS);

		$url = JURI::base().'index.php?option=com_joomlapack&view=def&folder='.JRequest::getVar('folder','');

		if(is_null($filePath))
		{
			$this->setRedirect($url, JText::_('DEF_ERROR_INVALIDDIRECTORY'), 'error');
		}
		else
		{
			$model =& $this->getModel('def');
			$model->toggleFilter($filePath);

			if(JError::isError($model))
			{
				$this->setRedirect($url, JText::_('DEF_ERROR_INVALIDDIRECTORY'), 'error');
			}
			else
			{
				$this->setRedirect($url);
			}
		}


	}
}