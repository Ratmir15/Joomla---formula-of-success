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
 * Base class for controller classes of filter views
 */
class FilterControllerParent extends JController
{
	function remove()
	{
		$cid = JRequest::getVar('cid',array(),'default','array');
		$id = JRequest::getInt('id');
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
		if($id <= 0)
		{
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view='.JRequest::getCmd('view').'&tpl='.JRequest::getCmd('tpl'), JText::_('FILTER_ERROR_INVALIDID'), 'error');
			return;
		}

		$model =& $this->getModel(JRequest::getCmd('filterclass'));
		$model->setId($id);
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
?>