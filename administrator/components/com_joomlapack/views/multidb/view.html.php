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
jimport('joomla.application.component.view');

/**
 * Multiple databases definition View
 *
 */
class JoomlapackViewMultidb extends JView
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
						$helpfile = 'multidb';
						JToolBarHelper::title(JText::_('JOOMLAPACK').': <small><small>'.JText::_('MULTIDB_PAGETITLE_NEW').'</small></small>');
						$this->_add();
						break;

					case 'edit':
						$helpfile = 'multidb';
						JToolBarHelper::title(JText::_('JOOMLAPACK').': <small><small>'.JText::_('MULTIDB_PAGETITLE_EDIT').'</small></small>');
						$this->_edit();
						break;
				}
				break;
					
					default:
						$helpfile = 'multidb';
						JToolBarHelper::title(JText::_('JOOMLAPACK').': <small><small>'.JText::_('MULTIDB').'</small></small>');
						$this->_default();
						break;
		}

		// Load the util helper
		$this->loadHelper('utils');

		// Add a spacer, a help button and show the template
		JToolBarHelper::spacer();
		JoomlapackHelperUtils::addLiveHelp($helpfile);
		$document =& JFactory::getDocument();
		$document->addStyleSheet(JURI::base().'components/com_joomlapack/assets/css/joomlapack.css');
		parent::display();
	}

	function _default()
	{
		$model =& $this->getModel();

		// Load the object list of records
		$this->assign( 'records', $model->getMultiDBList() );
		$this->assignRef('pagination', $model->getPagination());

		// Add toolbar buttons
		JToolBarHelper::back('Back', 'index.php?option='.JRequest::getCmd('option'));
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

		$this->assign('multidb', $model->getRecord());

		// Add toolbar buttons
		JToolBarHelper::save();
		JToolBarHelper::apply();
		JToolBarHelper::cancel();
	}

}