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
 * MVC controller class for Skip Directory Contents filter
 *
 */
class JoomlapackControllerSkip extends FilterControllerParent
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
	 * Handles the "toggle files" task, executed for non-AJAX operation of the DCS page.
	 * Upon completion, it returns to the directory listing of the folder.
	 *
	 */
	function togglefiles()
	{
		$folder = JRequest::getVar('folder','','default','none',1);
		$file = JRequest::getVar('item',null,'default','none',1);
		$filePath = empty($folder) ? $file : $folder.DS.$file;
		
		$url = JURI::base().'index.php?option=com_joomlapack&view=skip&folder='.JRequest::getVar('folder','','default','none',1);

		if(is_null($filePath))
		{
			$this->setRedirect($url, JText::_('SFF_ERROR_INVALIDFILENAME'), 'error');
		}
		else
		{
			$model =& $this->getModel('skip');
			$model->toggleFilesFilter($filePath);

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

	/**
	 * Handles the "toggle files" task, executed for non-AJAX operation of the DCS page.
	 * Upon completion, it returns to the directory listing of the folder.
	 *
	 */
	function toggledirs()
	{
		$folder = JRequest::getVar('folder','','default','none',1);
		$file = JRequest::getVar('item',null,'default','none',1);
		$filePath = empty($folder) ? $file : $folder.DS.$file;
		
		$url = JURI::base().'index.php?option=com_joomlapack&view=skip&folder='.JRequest::getVar('folder','','default','none',1);

		if(is_null($filePath))
		{
			$this->setRedirect($url, JText::_('SFF_ERROR_INVALIDFILENAME'), 'error');
		}
		else
		{
			$model =& $this->getModel('skip');
			$model->toggleDirectoriesFilter($filePath);

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

	function remove()
	{
		$cid = JRequest::getVar('cid',array(),'default','array');
		$id = JRequest::getString('id');
		if(empty($id))
		{
			if(!empty($cid) && is_array($cid))
			{
				foreach ($cid as $id)
				{
					$result = $this->_remove($id);
					if(!$result) {
						$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view='.JRequest::getCmd('view').'&tpl='.JRequest::getCmd('tpl'), JText::_('FILTER_ERROR_INVALIDID'), 'error');
						$this->redirect();
						return;
					}
				}
			}
			else
			{
				$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view='.JRequest::getCmd('view').'&tpl='.JRequest::getCmd('tpl'), JText::_('FILTER_ERROR_INVALIDID'), 'error');
				$this->redirect();
				return;
			}
		}
		else
		{
			$result = $this->_remove($id);
			if(!$result) {
				$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view='.JRequest::getCmd('view').'&tpl='.JRequest::getCmd('tpl'), $this->getError(), 'error');
				$this->redirect();
				return;
			}
		}

		$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view='.JRequest::getCmd('view').'&tpl='.JRequest::getCmd('tpl'), JText::_('FILTER_MSG_DELETED'));

		parent::display();
	}

	/**
	 * Removes the filter entry
	 *
	 * @return bool True on success
	 */
	function _remove($id)
	{
		// The string of $id must be at least two characters long (a letter and a number)
		if( strlen($id) < 2 )
		{
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view='.JRequest::getCmd('view').'&tpl='.JRequest::getCmd('tpl'), JText::_('FILTER_ERROR_INVALIDID'), 'error');
			return;
		}

		$class = substr($id,0,1);
		$id = substr($id, 1-strlen($id));

		// $id must now be a numeric entity
		if($id <= 0)
		{
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view='.JRequest::getCmd('view').'&tpl='.JRequest::getCmd('tpl'), JText::_('FILTER_ERROR_INVALIDID'), 'error');
			return;
		}

		// Process $class. It must be f for files or d for directories
		if( ($class != 'f') && ($class != 'd') )
		{
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view='.JRequest::getCmd('view').'&tpl='.JRequest::getCmd('tpl'), JText::_('FILTER_ERROR_INVALIDID'), 'error');
			return;
		}
		$class = $class == 'f' ? 'skipfiles' : 'skipdirs';

		$model =& $this->getModel('skip');
		$model->setId($id, $class);
		if($model->delete())
		{
			return true;
		}
		else
		{
			$this->setError($model->getError());
			return false;
		}
	}
}
