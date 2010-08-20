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
 * MVC View for Profiles management
 *
 */
class JoomlapackViewProfiles extends JView
{
	function display($tpl = null)
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
						JToolBarHelper::title(JText::_('JOOMLAPACK').': <small><small>'.JText::_('PROFILE_PAGETITLE_NEW').'</small></small>');
						$this->_add();
						break;

					case 'edit':
						JToolBarHelper::title(JText::_('JOOMLAPACK').': <small><small>'.JText::_('PROFILE_PAGETITLE_EDIT').'</small></small>');
						$this->_edit();
						break;
				}
				break;
					
				default:
					switch($task)
					{
						case "import":
							JToolBarHelper::back('Back', 'index.php?option=com_joomlapack&view=profiles');
							JToolBarHelper::spacer();
							JRequest::setVar('tpl', 'import');
							$tpl = 'import';
							$this->assign( 'description', JRequest::getString('description','') );
							break;

						default:
							$this->_default();
							JToolBarHelper::custom('export', 'export', 'export', JText::_('PROFILE_ICON_EXPORT'), true, false);
							JToolBarHelper::custom('import', 'import', 'import', JText::_('PROFILE_ICON_IMPORT'), false, false);
							break;
					}
					JToolBarHelper::title(JText::_('JOOMLAPACK').': <small><small>'.JText::_('PROFILES').'</small></small>');
					break;
		}

		// Load the util helper
		$this->loadHelper('utils');

		// Add a spacer, a help button and show the template
		JToolBarHelper::spacer();
		JoomlapackHelperUtils::addLiveHelp('profiles');

		$document =& JFactory::getDocument();
		$document->addStyleSheet(JURI::base().'/components/com_joomlapack/assets/css/joomlapack.css');

		parent::display($tpl);
	}

	/**
	 * The default layout, shows a list of profiles
	 *
	 */
	function _default()
	{
		// Get reference to profiles model
		$model =& $this->getModel('profiles');

		// Load list of profiles
		$profiles = $model->getProfilesList();

		$this->assign('profiles', $profiles);

		// Add toolbar buttons
		JToolBarHelper::back('Back', 'index.php?option='.JRequest::getCmd('option'));
		JToolBarHelper::spacer();
		JToolBarHelper::addNew();
		JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'Copy', false);
		JToolBarHelper::spacer();
		JToolBarHelper::deleteList();
		JToolBarHelper::spacer();
	}

	/**
	 * The edit layout on an edit task, lets the user edit an existing record
	 *
	 */
	function _edit()
	{
		// Load data for Edit, using model
		$model =& $this->getModel();
		$profile =& $model->getProfile();

		// Assign data to template
		$this->assignRef('profile', $profile);

		// Add toolbar buttons
		JToolBarHelper::save();
		JToolBarHelper::apply();
		JToolBarHelper::cancel();
	}

	/**
	 * The edit layout on an add task, lets the user add a new record
	 *
	 */
	function _add()
	{
		// Load data for Add New
		require_once(JPATH_COMPONENT_ADMINISTRATOR.DS.'tables'.DS.'profile.php');
		$model =& $this->getModel();
		$db =& JFactory::getDBO();
		$profile = new TableProfile($db);

		// Add toolbar buttons
		JToolBarHelper::save();
		JToolBarHelper::cancel();
	}
}