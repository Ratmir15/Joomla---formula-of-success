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

class JoomlapackViewBackup extends JView
{
	function display()
	{
		$task = JRequest::getCmd('task','default');
		$act = JRequest::getCmd('act','start');

		// Set the toolbar title
		JToolBarHelper::title(JText::_('JOOMLAPACK').':: <small><small>'.JText::_('BACKUP').'</small></small>');

		// Load the util helper
		$this->loadHelper('utils');

		switch($task)
		{
			case 'backup':
				if(!class_exists('JoomlapackModelRegistry'))
				{
					jpimport('models.registry',true);
				}
				$registry =& JoomlapackModelRegistry::getInstance();
				$this->assign('backupMethod', $registry->get('backupMethod'));
				break;

			case 'error':
				$this->assign('errormessage', JRequest::getString('message'));
				JToolBarHelper::back('Back', 'index.php?option='.JRequest::getCmd('option'));
				break;

			case 'finised':
				JToolBarHelper::back('Back', 'index.php?option='.JRequest::getCmd('option'));
				JToolBarHelper::spacer();
				JoomlapackHelperUtils::addLiveHelp('backup');
				break;

			default:
				// Add some buttons
				JToolBarHelper::back('Back', 'index.php?option='.JRequest::getCmd('option'));
				JToolBarHelper::spacer();
				JoomlapackHelperUtils::addLiveHelp('backup');

				// Load model
				$model =& $this->getModel('backup');

				// Load the Status Helper
				jpimport('helpers.status', true);
				$helper =& JoomlapackHelperStatus::getInstance();

				// Pass on data
				$this->assign('haserrors', !$helper->status);
				$this->assign('hasquirks', $helper->hasQuirks());
				$this->assign('quirks', $helper->getQuirksCell(!$helper->status));
				$this->assign('description', $model->getDescription() );
				$this->assign('comment', $model->getComment() );
				$this->assign('profile', $model->getProfileID() );
				$this->assign('profilelist', $model->getProfilesList() );

				break;
		}

		$css = JURI::base().'components/com_joomlapack/assets/css/joomlapack.css';
		$document =& JFactory::getDocument();
		$document->addStyleSheet($css);

		parent::display(JRequest::getCmd('tpl',null));
	}
}