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
 * JoomlaPack Backup Administrator view class
 *
 */
class JoomlapackViewBuadmin extends JView
{
	function display()
	{
		$task = JRequest::getCmd('task','default');

		switch($task)
		{
			case 'showcomment':
				JToolBarHelper::title(JText::_('JOOMLAPACK').': <small><small>'.JText::_('BUADMIN').'</small></small>');
				JToolBarHelper::back('Back', 'index.php?option='.JRequest::getCmd('option').'&view=buadmin');
				JToolBarHelper::save();
				JToolBarHelper::cancel();
				JoomlapackHelperUtils::addLiveHelp('buadmin');
				$document =& JFactory::getDocument();
				$document->addStyleSheet(JURI::base().'components/com_joomlapack/assets/css/joomlapack.css');

				jpimport('models.statistics', true);
				$model =& JoomlapackModelStatistics::getInstance('Statistics','JoomlapackModel');

				$model->setId(JRequest::getInt('id'));
				$record =& $model->getStatistic();
				$this->assignRef('record', $record);

				JRequest::setVar('tpl','comment');
				break;

			case 'restore':
				JToolBarHelper::title(JText::_('JOOMLAPACK').': <small><small>'.JText::_('BUADMIN').'</small></small>');
				JRequest::setVar('tpl','restore');
				$document =& JFactory::getDocument();
				$document->addStyleSheet(JURI::base().'components/com_joomlapack/assets/css/joomlapack.css');

				$this->assign('password', JRequest::getVar('password') );
				$this->assign('link', JRequest::getVar('linktarget'));
				break;

			default:
				$registry =& JoomlapackModelRegistry::getInstance();
				$easy = $registry->get('easymode', false);

				if(!$easy) {
					JToolBarHelper::title(JText::_('JOOMLAPACK').': <small><small>'.JText::_('BUADMIN').'</small></small>');
				}
				else
				{
					JToolBarHelper::title(JText::_('JOOMLAPACKEASY').': <small><small>'.JText::_('BUADMIN').'</small></small>');
				}

				JToolBarHelper::back('Back', 'index.php?option='.JRequest::getCmd('option'));
				JToolBarHelper::spacer();
				JToolBarHelper::deleteList();
				JToolBarHelper::custom( 'deletefiles', 'delete.png', 'delete_f2.png', JText::_('STATS_LABEL_DELETEFILES'), true );

				// "Show warning first" download button. Joomlantastic!
				//JToolBarHelper::save('download',JText::_('STATS_LOG_DOWNLOAD'));
				$confirmationText = addcslashes( JText::_('STATS_LOG_DOWNLOAD_CONFIRM'), "\n'" );
				$baseURI = JURI::base();
				$js = <<<ENDSCRIPT
function confirmDownloadButton()
{
	var answer = confirm('$confirmationText');
	if(answer) submitbutton('download');
}

function confirmDownload(id, part)
{
	var answer = confirm('$confirmationText');
	var newURL = '$baseURI';
	if(answer) {
		newURL += 'index.php?option=com_joomlapack&view=buadmin&task=download&id='+id;
		if( part != '' ) newURL += '&part=' + part
		window.location = newURL;
	}
}

ENDSCRIPT;
				
				$document =& JFactory::getDocument();
				$document->addScriptDeclaration($js);
				$bar = & JToolBar::getInstance('toolbar');
				$bar->appendButton( 'link', 'save', JText::_('STATS_LOG_DOWNLOAD'), "javascript:confirmDownloadButton();" );
				
				if(!$easy) {
					JToolBarHelper::editList('showcomment', JText::_('STATS_LOG_EDITCOMMENT'));
					if(JPSPECIALEDITION) JToolBarHelper::publish('restore', JText::_('STATS_LOG_RESTORE'));
				}
				JToolBarHelper::spacer();
				if(!$easy)
				{
					JoomlapackHelperUtils::addLiveHelp('buadmin');
				}
				else
				{
					JoomlapackHelperUtils::addLiveHelp('buadmineasy');
				}

				$document =& JFactory::getDocument();
				$document->addStyleSheet(JURI::base().'components/com_joomlapack/assets/css/joomlapack.css');

				jpimport('models.statistics', true);
				$model =& JoomlapackModelStatistics::getInstance('Statistics','JoomlapackModel');

				$list =& $model->getStatisticsListWithMeta();
				$this->assignRef('list', $list);
				$this->assignRef('pagination', $model->getPagination());
				$this->assign('easy', $easy);
				break;
		}

		parent::display(JRequest::getVar('tpl'));
	}
}