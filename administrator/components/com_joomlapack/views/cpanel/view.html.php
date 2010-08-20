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
 * JoomlaPack Control Panel view class
 *
 */
class JoomlapackViewCpanel extends JView
{
	function display()
	{
		$registry =& JoomlapackModelRegistry::getInstance();
		if($registry->get('easymode', false))
		// Easy mode
		{
			// Set the toolbar title; add a help button
			JToolBarHelper::title(JText::_('JOOMLAPACKEASY'));
			JoomlapackHelperUtils::addLiveHelp('cpanel');
				
			// Add submenus (those nifty text links below the toolbar!)
			// -- Configuration
			$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=configeasy';
			JSubMenuHelper::addEntry(JText::_('CONFIGURATION'), $link);
		}
		else
		// Advanced Mode
		{
			// Set the toolbar title; add a help button
			JToolBarHelper::title(JText::_('JOOMLAPACK'));
			JoomlapackHelperUtils::addLiveHelp('cpanel');
				
			// Add submenus (those nifty text links below the toolbar!)
			// -- Configuration
			$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=config';
			JSubMenuHelper::addEntry(JText::_('CONFIGURATION'), $link);
		}

		// -- Backup Now
		$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=backup';
		JSubMenuHelper::addEntry(JText::_('BACKUP'), $link);
		// -- Administer Backup Files
		$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=buadmin';
		JSubMenuHelper::addEntry(JText::_('BUADMIN'), $link);
		// -- View log
		$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=log';
		JSubMenuHelper::addEntry(JText::_('VIEWLOG'), $link);

		// Load the helper classes
		$this->loadHelper('utils');
		$this->loadHelper('status');
		$statusHelper = JoomlapackHelperStatus::getInstance();

		// Load the model
		jpimport('models.statistics', true);
		$model =& $this->getModel();
		$statmodel = new JoomlapackModelStatistics();

		$this->assign('icondefs', $model->getIconDefinitions()); // Icon definitions
		$this->assign('profileid', $model->getProfileID()); // Active profile ID
		$this->assign('profilelist', $model->getProfilesList()); // List of available profiles
		$this->assign('statuscell', $statusHelper->getStatusCell() ); // Backup status
		$this->assign('newscell', $statusHelper->getNewsCell() ); // News
		$this->assign('detailscell', $statusHelper->getQuirksCell() ); // Details (warnings)
		$this->assign('statscell', $statmodel->getLatestBackupDetails() );
		$this->assign('easymode', $registry->get('easymode', false));

		if($model->isLastBackupFailed())
		{
			$this->assign('troubleshooterstyle', 'display: block' );
			$this->assign('showtroubleshooter', true );
			$mode = $model->nextSettingsMode();
			if(is_null($mode))
			{
				$this->assign('troubleshootertext', JText::_('CPANEL_TROUBLESHOOTER_NOACTION') );
				$this->assign('troubleshooterurl', 'http://forum.joomlapack.net');
			}
			else
			{
				switch($mode)
				{
					case 'optimistic':
						$modetext = JText::_('CONFIGEZ_OPT_SMOPTIMISTIC');
						break;

					case 'normal':
						$modetext = JText::_('CONFIGEZ_OPT_SMNORMAL');
						break;

					case 'conservative':
						$modetext = JText::_('CONFIGEZ_OPT_SMCONSERVATIVE');
						break;
				}
				$this->assign('troubleshootertext', JText::sprintf('CPANEL_TROUBLESHOOTER_SETTING', $modetext) );
				$this->assign('troubleshooterurl', JURI::base().'index.php?option=com_joomlapack&view=cpanel&task=troubleshooter');
			}
		}
		else
		{
			$this->assign('showtroubleshooter', false );
			$this->assign('troubleshooterstyle', 'display: none' );
			$this->assign('troubleshootertext', '' );
			$this->assign('troubleshooterurl', '' );
		}

		$css = JURI::base().'components/com_joomlapack/assets/css/joomlapack.css';
		$document =& JFactory::getDocument();
		$document->addStyleSheet($css);

		parent::display();
	}
}