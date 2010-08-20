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
 * MVC controller class for Single File filters
 *
 */
class JoomlapackControllerSff extends FilterControllerParent
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
	 * Handles the "toggle" task, executed for non-AJAX operation of the SFF page.
	 * Upon completion, it returns to the directory listing of the folder.
	 *
	 */
	function toggle()
	{
		$folder = JRequest::getVar('folder','','default','none',1);
		$file = JRequest::getVar('file',null,'default','none',1);
		$filePath = empty($folder) ? $file : $folder.DS.$file;

		$url = JURI::base().'/index.php?option=com_joomlapack&view=sff&folder='.JRequest::getVar('folder','','default','none',1);

		if(is_null($filePath))
		{
			$this->setRedirect($url, JText::_('SFF_ERROR_INVALIDFILENAME'), 'error');
		}
		else
		{
			$model =& $this->getModel('sff');
			$model->toggleFilter($filePath);

			if(JError::isError($model))
			{
				$this->setRedirect($url, JText::_('SFF_ERROR_INVALIDFILENAME'), 'error');
			}
			else
			{
				$this->setRedirect($url);
			}
		}


	}
}