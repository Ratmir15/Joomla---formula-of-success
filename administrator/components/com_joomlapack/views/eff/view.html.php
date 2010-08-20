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

/**
 * EFF View
 *
 */
class JoomlapackViewEff extends JView
{
	function display()
	{
		// Decide what to do; delegate data loading to private methods
		$task = JRequest::getCmd('task','display');
		$layout = JRequest::getCmd('layout','default');
		switch($layout)
		{
			case 'default_edit':
				switch($task)
				{
					case 'add':
						$helpfile = 'eff';
						JToolBarHelper::title(JText::_('JOOMLAPACK').': <small><small>'.JText::_('EFF_PAGETITLE_NEW').'</small></small>');
						$this->_add();
						break;

					case 'edit':
						$helpfile = 'eff';
						JToolBarHelper::title(JText::_('JOOMLAPACK').': <small><small>'.JText::_('EFF_PAGETITLE_EDIT').'</small></small>');
						$this->_edit();
						break;
				}
				break;
					
					default:
						$helpfile = 'eff';
						JToolBarHelper::title(JText::_('JOOMLAPACK').': <small><small>'.JText::_('EXTRADIRS').'</small></small>');
						$this->_default();
						break;
		}

		// Load the util helper
		$this->loadHelper('utils');

		$document =& JFactory::getDocument();
		$document->addStyleSheet(JURI::base().'components/com_joomlapack/assets/css/joomlapack.css');

		// Add a spacer, a help button and show the template
		JToolBarHelper::spacer();
		JoomlapackHelperUtils::addLiveHelp('$helpfile');
		parent::display();
	}

	function _default()
	{
		$model =& $this->getModel();

		// Load the object list of records
		$this->assign( 'records', $model->getEFFList() );
		$this->assignRef('pagination', $model->getPagination());

		// Add toolbar buttons
		JToolBarHelper::back('Back', 'index.php?option=com_joomlapack');
		JToolBarHelper::spacer();
		JToolBarHelper::addNew();
		JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'Copy', false);
		JToolBarHelper::deleteList();
	}

	function _add()
	{
		$model =& $this->getModel();

		// Add toolbar buttons
		JToolBarHelper::save();
		JToolBarHelper::cancel();
	}

	function _edit()
	{
		$model =& $this->getModel();

		$this->assign('eff', $model->getRecord());

		// Add toolbar buttons
		JToolBarHelper::save();
		JToolBarHelper::apply();
		JToolBarHelper::cancel();
	}
}