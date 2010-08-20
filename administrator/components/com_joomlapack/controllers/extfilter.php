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
 * Extension Filter controller class
 *
 */
class JoomlapackControllerExtfilter extends JController
{
	/**
	 * Default viewing template, passes along execution to components template
	 *
	 */
	function display()
	{
		JResponse::setHeader('Cache-Control','no-cache, must-revalidate',true); // HTTP 1.1 - Cache control
		JResponse::setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT',true); // HTTP 1.0 - Date in the past
		$this->components();
	}

	/**
	 * Components task, shows all non-core components
	 */
	function components()
	{
		JResponse::setHeader('Cache-Control','no-cache, must-revalidate',true); // HTTP 1.1 - Cache control
		JResponse::setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT',true); // HTTP 1.0 - Date in the past
		JRequest::setVar('layout', 'default_components');
		parent::display();
	}

	/**
	 * Languages task, shows all languages except the default
	 */
	function languages()
	{
		JResponse::setHeader('Cache-Control','no-cache, must-revalidate',true); // HTTP 1.1 - Cache control
		JResponse::setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT',true); // HTTP 1.0 - Date in the past
		JRequest::setVar('layout', 'default_languages');
		parent::display();
	}

	/**
	 * Modules task, shows all non-core modules
	 */
	function modules()
	{
		JResponse::setHeader('Cache-Control','no-cache, must-revalidate',true); // HTTP 1.1 - Cache control
		JResponse::setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT',true); // HTTP 1.0 - Date in the past
		JRequest::setVar('layout', 'default_modules');
		parent::display();
	}

	/**
	 * Plugins task, shows all non-core plugins
	 */
	function plugins()
	{
		JResponse::setHeader('Cache-Control','no-cache, must-revalidate',true); // HTTP 1.1 - Cache control
		JResponse::setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT',true); // HTTP 1.0 - Date in the past
		JRequest::setVar('layout', 'default_plugins');
		parent::display();
	}

	/**
	 * Templates task, shows all non-core templates
	 */
	function templates()
	{
		JResponse::setHeader('Cache-Control','no-cache, must-revalidate',true); // HTTP 1.1 - Cache control
		JResponse::setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT',true); // HTTP 1.0 - Date in the past
		JRequest::setVar('layout', 'default_templates');
		parent::display();
	}

	/**
	 * Toggles the exclusion of a component
	 *
	 */
	function toggleComponent()
	{	
		JResponse::setHeader('Cache-Control','no-cache, must-revalidate',true); // HTTP 1.1 - Cache control
		JResponse::setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT',true); // HTTP 1.0 - Date in the past

		// Get the option passed along
		$item = JRequest::getString('item', '');

		// Try to figure out if this component is allowed to be excluded (exists and is non-Core)
		$model =& $this->getModel('extfilter');
		$components =& $model->getComponents();

		$found = false;
		$numRows = count($components);
		for($i=0;$i < $numRows; $i++)
		{
			$row =& $components[$i];
			if($row['option'] == $item) {
				$found = true;
				$name = $row['name'];
			}
		}

		if(!$found)
		{
			$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=components';
			$msg = JText::sprintf('EXTFILTER_ERROR_INVALIDCOMPONENT', $item);
			$this->setRedirect( $link, $msg, 'error' );
		}
		else
		{
			$model->toggleComponentFilter($item);
			$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=components';
			$msg = JText::sprintf('EXTFILTER_MSG_TOGGLEDCOMPONENT', $name);
			$this->setRedirect( $link, $msg );
		}

		parent::redirect();
	}

	function reapplyComponents()
	{
		JResponse::setHeader('Cache-Control','no-cache, must-revalidate',true); // HTTP 1.1 - Cache control
		JResponse::setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT',true); // HTTP 1.0 - Date in the past

		$model =& $this->getModel('extfilter');
		$model->reapplyComponentsFilter();
		$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=components';
		$msg = JText::sprintf('EXTFILTER_MSG_REAPPLIEDCOMPONENT', $name);
		$this->setRedirect( $link, $msg );
		parent::redirect();
	}

	/**
	 * Toggles the exclusion of a language
	 *
	 */
	function toggleLanguage()
	{
		JResponse::setHeader('Cache-Control','no-cache, must-revalidate',true); // HTTP 1.1 - Cache control
		JResponse::setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT',true); // HTTP 1.0 - Date in the past

		// Get the option passed along
		$item = JRequest::getString('item', '');

		// Try to figure out if this component is allowed to be excluded (exists and is non-Core)
		$model =& $this->getModel('extfilter');
		$languages =& $model->getLanguages();

		if(!isset($languages[$item]))
		{
			$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=languages';
			$msg = JText::sprintf('EXTFILTER_ERROR_INVALIDLANGUAGE', $item);
			$this->setRedirect( $link, $msg, 'error' );
		}
		else
		{
			$model->toggleLanguageFilter($item);
			$name = $languages[$item]['name'];
			$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=languages';
			$msg = JText::sprintf('EXTFILTER_MSG_TOGGLEDLANGUAGE', $name);
			$this->setRedirect( $link, $msg );
		}

		parent::redirect();
	}

	function reapplyLanguages()
	{
		JResponse::setHeader('Cache-Control','no-cache, must-revalidate',true); // HTTP 1.1 - Cache control
		JResponse::setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT',true); // HTTP 1.0 - Date in the past

		$model =& $this->getModel('extfilter');
		$model->reapplyLanguagesFilters();
		$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=languages';
		$msg = JText::sprintf('EXTFILTER_MSG_REAPPLIEDLANGUAGES', $name);
		$this->setRedirect( $link, $msg );
		parent::redirect();
	}

	/**
	 * Toggles the exclusion of a module
	 *
	 */
	function toggleModule()
	{
		JResponse::setHeader('Cache-Control','no-cache, must-revalidate',true); // HTTP 1.1 - Cache control
		JResponse::setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT',true); // HTTP 1.0 - Date in the past

		// Get the option passed along
		$item = JRequest::getString('item', '');

		// Try to figure out if this component is allowed to be excluded (exists and is non-Core)
		$model =& $this->getModel('extfilter');
		$modules =& $model->getModules();

		if(!isset($modules[$item]))
		{
			$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=modules';
			$msg = JText::sprintf('EXTFILTER_ERROR_INVALIDMODULE', $item);
			$this->setRedirect( $link, $msg, 'error' );
		}
		else
		{
			$model->toggleModuleFilter($item);
			$name = $modules[$item]['name'];
			$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=modules';
			$msg = JText::sprintf('EXTFILTER_MSG_TOGGLEDMODULE', $name);
			$this->setRedirect( $link, $msg );
		}

		parent::redirect();
	}

	function reapplyModules()
	{
		JResponse::setHeader('Cache-Control','no-cache, must-revalidate',true); // HTTP 1.1 - Cache control
		JResponse::setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT',true); // HTTP 1.0 - Date in the past

		$model =& $this->getModel('extfilter');
		$model->reapplyModulesFilters();
		$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=modules';
		$msg = JText::sprintf('EXTFILTER_MSG_REAPPLIEDMODULES', $name);
		$this->setRedirect( $link, $msg );
		parent::redirect();
	}

	/**
	 * Toggles the exclusion of a plugin
	 *
	 */
	function togglePlugin()
	{
		JResponse::setHeader('Cache-Control','no-cache, must-revalidate',true); // HTTP 1.1 - Cache control
		JResponse::setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT',true); // HTTP 1.0 - Date in the past

		// Get the option passed along
		$item = JRequest::getString('item', '');

		// Try to figure out if this component is allowed to be excluded (exists and is non-Core)
		$model =& $this->getModel('extfilter');
		$plugins =& $model->getPlugins();

		if(!isset($plugins[$item]))
		{
			$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=plugins';
			$msg = JText::sprintf('EXTFILTER_ERROR_INVALIDPLUGIN', $item);
			$this->setRedirect( $link, $msg, 'error' );
		}
		else
		{
			$model->togglePluginFilter($item);
			$name = $plugins[$item]['name'];
			$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=plugins';
			$msg = JText::sprintf('EXTFILTER_MSG_TOGGLEDPLUGIN', $name);
			$this->setRedirect( $link, $msg );
		}

		parent::redirect();
	}

	function reapplyPlugins()
	{
		JResponse::setHeader('Cache-Control','no-cache, must-revalidate',true); // HTTP 1.1 - Cache control
		JResponse::setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT',true); // HTTP 1.0 - Date in the past

		$model =& $this->getModel('extfilter');
		$model->reapplyPluginsFilters();
		$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=plugins';
		$msg = JText::sprintf('EXTFILTER_MSG_REAPPLIEDPLUGINS', $name);
		$this->setRedirect( $link, $msg );
		parent::redirect();
	}

	/**
	 * Toggles the exclusion of a template
	 *
	 */
	function toggleTemplate()
	{
		JResponse::setHeader('Cache-Control','no-cache, must-revalidate',true); // HTTP 1.1 - Cache control
		JResponse::setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT',true); // HTTP 1.0 - Date in the past

		// Get the option passed along
		$item = JRequest::getString('item', '');

		// Try to figure out if this component is allowed to be excluded (exists and is non-Core)
		$model =& $this->getModel('extfilter');
		$templates =& $model->getTemplates();

		if(!isset($templates[$item]))
		{
			$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=templates';
			$msg = JText::sprintf('EXTFILTER_ERROR_INVALIDTEMPLATE', $item);
			$this->setRedirect( $link, $msg, 'error' );
		}
		else
		{
			$model->toggleTemplateFilter($item);
			$name = $templates[$item]['name'];
			$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=templates';
			$msg = JText::sprintf('EXTFILTER_MSG_TOGGLEDTEMPLATE', $name);
			$this->setRedirect( $link, $msg );
		}

		parent::redirect();
	}

	function reapplyTemplates()
	{
		JResponse::setHeader('Cache-Control','no-cache, must-revalidate',true); // HTTP 1.1 - Cache control
		JResponse::setHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT',true); // HTTP 1.0 - Date in the past

		$model =& $this->getModel('extfilter');
		$model->reapplyTemplatesFilters();
		$link = JURI::base().'?option=com_joomlapack&view=extfilter&task=templates';
		$msg = JText::sprintf('EXTFILTER_MSG_REAPPLIEDTEMPLATES', $name);
		$this->setRedirect( $link, $msg );
		parent::redirect();
	}
}