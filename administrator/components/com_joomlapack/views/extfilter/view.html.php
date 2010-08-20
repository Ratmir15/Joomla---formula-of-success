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
 * Extension Filter view class
 *
 */
class JoomlapackViewExtfilter extends JView
{
	function display()
	{
		$layout = JRequest::getCmd('layout','default');
		$task = JRequest::getCmd('task','components');

		// Add submenus (those nifty text links below the toolbar!)
		$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=components';
		JSubMenuHelper::addEntry(JText::_('EXTFILTER_COMPONENTS'), $link, ($task == 'components'));
		$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=modules';
		JSubMenuHelper::addEntry(JText::_('EXTFILTER_MODULES'), $link, ($task == 'modules'));
		$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=plugins';
		JSubMenuHelper::addEntry(JText::_('EXTFILTER_PLUGINS'), $link, ($task == 'plugins'));
		$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=languages';
		JSubMenuHelper::addEntry(JText::_('EXTFILTER_LANGUAGES'), $link, ($task == 'languages'));
		$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=templates';
		JSubMenuHelper::addEntry(JText::_('EXTFILTER_TEMPLATES'), $link, ($task == 'templates'));

		// Add toolbar buttons
		JToolBarHelper::title(JText::_('JOOMLAPACK').': <small><small>'.JText::_('EXTFILTER').'</small></small>');
		JToolBarHelper::back('Back', 'index.php?option='.JRequest::getCmd('option'));
		JToolBarHelper::spacer();

		$model =& $this->getModel();
		switch($task)
		{
			case 'components':
				// Add "re-apply" button
				$bar = & JToolBar::getInstance('toolbar');
				$href = 'index.php?option=com_joomlapack&view=extfilter&task=reapplyComponents';
				$bar->appendButton( 'Link', 'apply', JText::_('EXTFILTER_LABEL_REAPPLY'), $href );
				JToolBarHelper::spacer();

				// Pass along the list of components
				$this->assignRef('components', $model->getComponents());
				break;
					
			case 'modules':
				// Add "re-apply" button
				$bar = & JToolBar::getInstance('toolbar');
				$href = 'index.php?option=com_joomlapack&view=extfilter&task=reapplyModules';
				$bar->appendButton( 'Link', 'apply', JText::_('EXTFILTER_LABEL_REAPPLY'), $href );
				JToolBarHelper::spacer();

				// Pass along the list of components
				$this->assignRef('modules', $model->getModules());
				break;

			case 'plugins':
				// Add "re-apply" button
				$bar = & JToolBar::getInstance('toolbar');
				$href = 'index.php?option=com_joomlapack&view=extfilter&task=reapplyPlugins';
				$bar->appendButton( 'Link', 'apply', JText::_('EXTFILTER_LABEL_REAPPLY'), $href );
				JToolBarHelper::spacer();

				// Pass along the list of components
				$this->assignRef('plugins', $model->getPlugins());
				break;

			case 'templates':
				// Add "re-apply" button
				$bar = & JToolBar::getInstance('toolbar');
				$href = 'index.php?option=com_joomlapack&view=extfilter&task=reapplyTemplates';
				$bar->appendButton( 'Link', 'apply', JText::_('EXTFILTER_LABEL_REAPPLY'), $href );
				JToolBarHelper::spacer();

				// Pass along the list of components
				$this->assignRef('templates', $model->getTemplates());
				break;

			case 'languages':
				// Add "re-apply" button
				$bar = & JToolBar::getInstance('toolbar');
				$href = 'index.php?option=com_joomlapack&view=extfilter&task=reapplyLanguages';
				$bar->appendButton( 'Link', 'apply', JText::_('EXTFILTER_LABEL_REAPPLY'), $href );
				JToolBarHelper::spacer();

				// Pass along the list of components
				$this->assignRef('languages', $model->getLanguages());
				break;
		}

		$document =& JFactory::getDocument();
		$document->addStyleSheet(JURI::base().'components/com_joomlapack/assets/css/joomlapack.css');
		JoomlapackHelperUtils::addLiveHelp('extfilter');

		parent::display();
	}
}