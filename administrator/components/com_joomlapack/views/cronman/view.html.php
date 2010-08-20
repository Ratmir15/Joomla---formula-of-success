<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 2.3
 * 
 * CRON Script Manager View
 */

// Protect from direct access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.view');

class JoomlapackViewCronman extends JView
{
	
	function display()
	{
		// Decide what to do; delegate data loading to private methods
		$task = JRequest::getCmd('task','display');
		$layout = JRequest::getCmd('layout','default');

		JToolBarHelper::title(JText::_('JOOMLAPACK').': <small><small>'.JText::_('CRONMAN').'</small></small>');

		switch($layout)
		{
			case 'default_edit':
				// Get the CRON configuration definition
				if($task == 'add')
				{
					$definition = null;
					$registry =& JoomlapackModelRegistry::getInstance();
					$secret = $registry->get('secret_key');
					$this->assign('secret', $secret);
				}
				else
				{
					$id = JRequest::getInt('id', 0);
					$model =& $this->getModel('cronman');
					$definition = $model->getConfiguration($id);
				}
				$this->assign('definition', $definition);
				// Get some lists and pass them on
				$model =& $this->getModel('cronman');
				$postops = $model->getPostOpList();
				$this->assign('postops', $postops);
				
				// Add the buttons
				JToolBarHelper::save();
				JToolBarHelper::apply();
				JToolBarHelper::cancel();
				
				break;

			case 'default':
			default:
				$this->_default();
				break;
		}

		// Load the util helper
		$this->loadHelper('utils');

		// Add a spacer, a help button and show the template
		JToolBarHelper::spacer();
		JoomlapackHelperUtils::addLiveHelp('profiles');

		// Load a list of profiles
		$model =& $this->getModel('cronman');
		jpimport('models.profiles', true);
		$profilesmodel = new JoomlapackModelProfiles();
		
		$profiles_objects = $profilesmodel->getProfilesList(true);
		$profiles = array();
		foreach($profiles_objects as $profile)
		{
			$id = $profile->id;
			$profiles[(string)$id] = $profile->description; 
		}
		unset($profiles_objects); unset($profilesmodel);
		$this->assign('profiles', $profiles);		

		// Add JoomlaPack CSS
		$document =& JFactory::getDocument();
		$document->addStyleSheet(JURI::base().'/components/com_joomlapack/assets/css/joomlapack.css');

		// Show the view
		parent::display();
	}
	
	function _default()
	{
		$model =& $this->getModel('cronman');

		// Load list of profiles
		$definitions =& $model->getCronDefinitions();
		$scriptdirectory = $model->scriptdirectory;

		$this->assign('scriptdirectory', $scriptdirectory);
		$this->assign('definitions', $definitions);

		// Add toolbar buttons
		JToolBarHelper::back('Back', 'index.php?option='.JRequest::getCmd('option'));
		JToolBarHelper::spacer();
		JToolBarHelper::addNew();
		JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'Copy', false);
		JToolBarHelper::spacer();
		JToolBarHelper::deleteList();
	}
}