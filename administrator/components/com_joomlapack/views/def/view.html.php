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
 * MVC View for DEF
 *
 */
class JoomlapackViewDef extends JView
{
	function display()
	{
		$tpl = JRequest::getVar('tpl');

		// Add toolbar buttons
		JToolBarHelper::title(JText::_('JOOMLAPACK').': <small><small>'.JText::_('DEF').'</small></small>');
		JToolBarHelper::back('Back', 'index.php?option='.JRequest::getCmd('option'));

		$bar = & JToolBar::getInstance('toolbar');
		switch($tpl)
		{
			case 'tab':
				JToolBarHelper::deleteList();
				$bar->appendButton( 'Link', 'preview', JText::_('NORMALVIEW') , 'index.php?option=com_joomlapack&view='.JRequest::getCmd('view') );
				break;

			case '':
			default:
				$bar->appendButton( 'Link', 'preview', JText::_('TABULARVIEW') , 'index.php?option=com_joomlapack&view='.JRequest::getCmd('view').'&tpl=tab' );
				break;
		}

		JToolBarHelper::spacer();
		JoomlapackHelperUtils::addLiveHelp('def');

		if($tpl == 'tab')
		{
			$model =& $this->getModel('Def');
			$task = JRequest::getCmd('task','default');
			$list =& $model->getRecordsList();
			$this->assignRef('list', $list);
			$this->assignRef('pagination', $model->getPagination());
			$this->assignRef('class', $model->_filterclass);
		}

		$document =& JFactory::getDocument();
		$document->addStyleSheet(JURI::base().'components/com_joomlapack/assets/css/joomlapack.css');

		parent::display($tpl);
	}
}