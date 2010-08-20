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
jimport('joomla.application.component.controller');

/**
 * The Switch Mode controller class
 *
 */
class JoomlapackControllerSwitch extends JController
{
	function display()
	{
		$registry =& JoomlapackModelRegistry::getInstance();
		if($registry->get('easymode', false))
		{
			// Easy mode on, switch to expert
			$registry->set('easymode', false);
		}
		else
		{
			// Expert mdoe on, switch to easy
				
			// Get the model
			$model =& $this->getModel('switch');
				
			// Remove all inclusions and exclusion filters
			$model->clearInclusionFilters();
			$model->clearExclusionFilters();
				
			// If we are on a custom settings level, switch to normal
			if($registry->get('settingsmode') == 'custom') $registry->set('settingsmode', 'normal');
				
			// Update mode selection
			$registry->set('easymode', true);
		}

		$registry->save();
		$this->setRedirect( JURI::base().'index.php?option=com_joomlapack&view=cpanel' );
		$this->redirect();
	}
}