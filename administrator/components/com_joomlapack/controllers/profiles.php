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
jimport('joomla.application.component.controller');

/**
 * MVC controller class for Profiles Administration page
 *
 */
class JoomlapackControllerProfiles extends JController
{
	/**
	 * Displays a list of profiles
	 *
	 */
	function display()
	{
		parent::display();
	}

	/**
	 * Handles applying the changes (versus merely saving them)
	 */
	function apply()
	{
		// Just delegate the task
		$this->save();
	}

	/**
	 * Processes saving an entry (new or old) and redirecting to the list view
	 *
	 */
	function save()
	{
		$data = JRequest::get('POST');
		$task = JRequest::getCmd('task','save');

		$model =& $this->getModel('profiles');
		if($model->save($data))
		{
			// Show a "SAVE OK" message
			$message = JText::_('PROFILE_SAVE_OK');
			$type = 'message';
			if($task == 'apply')
			{
				$mytable =& $model->getSavedTable();
				$insertid = $mytable->id;
				$this->_switchProfile($insertid);
			}
		}
		else
		{
			// Show message on failure
			$message = JText::_('PROFILE_SAVE_ERROR');
			$message .= ' ['.$model->getError().']';
			$type = 'error';
		}

		// Redirect, based on task
		switch($task)
		{
			case 'save':
				$this->setRedirect('index.php?option='.JRequest::getCmd('option').'&view='.JRequest::getCmd('view'), $message, $type);
				break;
					
			case 'apply':
				$this->setRedirect('index.php?option='.JRequest::getCmd('option').'&view='.JRequest::getCmd('view').'&task=edit&id='.$insertid, $message, $type);
				break;
		}
	}

	/**
	 * Processes removing an entry and redirecting to list view
	 *
	 */
	function remove()
	{
		$model =& $this->getModel('profiles');
		if($model->delete())
		{
			// Show a "SAVE OK" message
			$message = JText::_('PROFILE_DELETE_OK');
			$type = 'message';
		}
		else
		{
			// Show message on failure
			$message = JText::_('PROFILE_DELETE_ERROR');
			$message .= ' ['.$model->getError().']';
			$type = 'error';
		}

		// Redirect
		$this->setRedirect('index.php?option='.JRequest::getCmd('option').'&view='.JRequest::getCmd('view'), $message, $type);
	}

	/**
	 * Shows a view where you can add a new record. Actually, delegates to edit().
	 *
	 */
	function add()
	{
		$this->edit(); // Delegate execution
	}

	/**
	 * Shows the add/edit screen. Forces the layout, in order to show the correct form.
	 *
	 */
	function edit()
	{
		JRequest::setVar('hidemainmenu', 1);
		JRequest::setVar('layout', 'default_edit');
		parent::display();
	}

	/**
	 * Copies the selected profile into a new record at the end of the list
	 *
	 */
	function copy()
	{
		$model =& $this->getModel('profiles');
		if($model->copy())
		{
			// Show a "COPY OK" message
			$message = JText::_('PROFILE_COPY_OK');
			$type = 'message';
			$this->_switchProfile( $model->getId() );
		}
		else
		{
			// Show message on failure
			$message = JText::_('PROFILE_COPY_ERROR');
			$message .= ' ['.$model->getError().']';
			$type = 'error';
		}
		// Redirect
		$this->setRedirect('index.php?option='.JRequest::getCmd('option').'&view='.JRequest::getCmd('view'), $message, $type);
	}

	/**
	 * Cancel profile editing
	 *
	 */
	function cancel()
	{
		$this->setRedirect('index.php?option='.JRequest::getCmd('option').'&view='.JRequest::getCmd('view'));
	}

	function export()
	{
		// Do we have a valid profile ID? If not, warn user and return.
		$model =& $this->getModel('profiles');
		if(!$model->checkID())
		{
			$this->setRedirect('index.php?option=com_joomlapack&view=profiles', JText::_('PROFILE_INVALID_ID'), 'error');
			return;
		}

		// Set to raw format and tpl=export
		$document =& JFactory::getDocument();
		$document->setType('raw');
		JRequest::setVar('tpl','export');

		parent::display();
	}

	function doimport()
	{
		// Get uploaded file name
		$fileDescriptor = JRequest::getVar('userfile', '', 'FILES', 'array');

		// Handle no uploaded file error
		if( (!is_array($fileDescriptor)) || (!isset($fileDescriptor['name'])) )
		{
			$description = urlencode( JRequest::getString('description','') );
			$this->setRedirect('index.php?option=com_joomlapack&view=profiles&task=import&description='.$description, JText::_('PROFILE_ERROR_NOUPLOAD'), 'error');
			parent::display();
			return;
		}

		if($fileDescriptor['size'] < 1)
		{
			// Handle zero length file
			$this->setRedirect('index.php?option=com_joomlapack&view=profiles&task=import&description='.$description, JText::_('PROFILE_ERROR_UPLOADZERO'), 'error');
			parent::display();
			return;
		}

		if($fileDescriptor['error'] != 0)
		{
			// Handle error in upload
			$this->setRedirect('index.php?option=com_joomlapack&view=profiles&task=import&description='.$description, JText::_('PROFILE_ERROR_UPLOADERROR'), 'error');
			parent::display();
			return;
		}

		// Get the file data
		$fileData = file_get_contents($fileDescriptor['tmp_name']);

		// Try to get a DOM document instance
		require_once( JPATH_SITE.DS.'libraries'.DS.'domit'.DS.'xml_domit_lite_include.php' );
		$domDoc = new DOMIT_Lite_Document();
		$domDoc->resolveErrors( true );
		if ( !$domDoc->parseXML($fileData, false, true) ) {
			// Handle wrong file type
			$this->setRedirect('index.php?option=com_joomlapack&view=profiles&task=import&description='.$description, JText::_('PROFILE_ERROR_WRONGFILETYPE'), 'error');
			parent::display();
			return;
		}

		// Check we have a correct XML root node
		$root =& $domDoc->documentElement;
		if($root->nodeName != 'jpexport')
		{
			// Handle wrong XML format
			$this->setRedirect('index.php?option=com_joomlapack&view=profiles&task=import&description='.$description, JText::_('PROFILE_ERROR_WRONGFORMAT'), 'error');
			parent::display();
			return;
		}

		// Check export version
		$version = $root->attributes['version'];
		if(version_compare('1.3', $version) != 0)
		{
			// Handle wrong version
			$this->setRedirect('index.php?option=com_joomlapack&view=profiles&task=import&description='.$description, JText::_('PROFILE_ERROR_WRONGVERSION'), 'error');
			parent::display();
			return;
		}

		// Check for the existence of the config, incusion and exclusion nodes
		$config		=& $root->getElementsByPath('config', 1);
		$inclusion	=& $root->getElementsByPath('inclusion', 1);
		$exclusion	=& $root->getElementsByPath('exclusion', 1);

		if(is_null($config) || is_null($inclusion) || is_null($exclusion))
		{
			// Handle missing nodes
			$this->setRedirect('index.php?option=com_joomlapack&view=profiles&task=import&description='.$description, JText::_('PROFILE_ERROR_MISSINGNODES'), 'error');
			parent::display();
			return;
		}

		// Create a new, empty profile
		$model =& $this->getModel('profiles');
		$data = array(
			'id'			=> 0,
			'description'	=> JRequest::getString('description','')
		);

		if($model->save($data))
		{
			// Get a reference to the new profile
			$mytable =& $model->getSavedTable();
			$profileid = $mytable->id;
		}
		else
		{
			// Show message on failure
			$this->setRedirect('index.php?option=com_joomlapack&view=profiles&task=import&description='.$description, JText::_('PROFILE_ERROR_COULDNOTCREATEPROFILE'), 'error');
			parent::display();
			return;
		}

		jpimport('models.registry', true);
		$registry = new JoomlapackModelRegistry(array('id' => $profileid));

		if($config->hasChildNodes())
		{
			foreach($config->childNodes as $node)
			{
				$key = $node->nodeName;
				$value = unserialize($node->getText());
				$registry->set($key, $value);
			}
		}

		$registry->save();

		$db =& JFactory::getDBO();

		// Import inclusion filters
		if($inclusion->hasChildNodes())
		{
			foreach($inclusion->childNodes as $node)
			{
				$key = $node->nodeName;
				$data = $node->getText();
				$data = unserialize($data);
				$sql	= 'INSERT INTO #__jp_inclusion ('.
				$db->nameQuote('profile').','.
				$db->nameQuote('class').','.
				$db->nameQuote('value').') '.
							'VALUES ('.
				$db->Quote($profileid).', '.
				$db->Quote($data['class']).', '.
				$db->Quote($data['value']).')';
				$db->setQuery($sql);
				if(!$db->query())
				{
					// Show message on failure
					$errorMsg = $db->ErrorMsg();
					$this->setRedirect('index.php?option=com_joomlapack&view=profiles&task=import&description='.$description, JText::_('PROFILE_ERROR_DBERROR').$errorMsg, 'error');
					parent::display();
					return;
				}
			}
		}

		// Import inclusion filters
		if($exclusion->hasChildNodes())
		{
			foreach($exclusion->childNodes as $node)
			{
				$key = $node->nodeName;
				$data = $node->getText();
				$data = unserialize($data);
				$sql	= 'INSERT INTO #__jp_exclusion ('.
				$db->nameQuote('profile').','.
				$db->nameQuote('class').','.
				$db->nameQuote('value').') '.
							'VALUES ('.
				$db->Quote($profileid).', '.
				$db->Quote($data['class']).', '.
				$db->Quote($data['value']).')';
				$db->setQuery($sql);
				if(!$db->query())
				{
					// Show message on failure
					$errorMsg = $db->ErrorMsg();
					$this->setRedirect('index.php?option=com_joomlapack&view=profiles&task=import&description='.$description, JText::_('PROFILE_ERROR_DBERROR').$errorMsg, 'error');
					parent::display();
					return;
				}
			}
		}

		$this->setRedirect('index.php?option=com_joomlapack&view=profiles', JText::_('PROFILE_IMPORT_OK'));
	}

	function _switchProfile($id)
	{
		$session =& JFactory::getSession();
		$session->set('profile', $id, 'joomlapack');
	}
}