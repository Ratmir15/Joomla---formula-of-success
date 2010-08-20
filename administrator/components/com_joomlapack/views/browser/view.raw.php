<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 2.2
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

// Load framework base classes
jimport('joomla.application.component.view');

class JoomlapackViewBrowser extends JView
{
	function display()
	{
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.path');

		// Get the folder to browse
		$folder = JRequest::getString('folder', '');
		$processfolder = JRequest::getInt('processfolder', 0);

		if(empty($folder))
		{
			$folder = '';
			$isFolderThere = false;
			$isInRoot = false;
			$isOpenbasedirRestricted = false;
		}
		else
		{
			if($processfolder == 1)
			{
				jpimport('models.registry', true);
				$registry =& JoomlapackModelRegistry::getInstance();
				$folder = $registry->processOutputFolderVars($folder);
			}
			// Normalise name
			$folder = @realpath($folder);
			if(JFolder::exists($folder))
			{
				$isFolderThere = true;
			}
			else
			{
				$isFolderThere = false;
			}
			JRequest::setVar('folder', $folder);
				
			// Check if it's a subdirectory of the site's root
			$isInRoot = (strpos($folder, JPATH_SITE) === 0);
				
			// Check if it's restricted by open_basedir restrictions
			jpimport('helpers.status', true);
			$statusHelper =& JoomlapackHelperStatus::getInstance();
			$isOpenbasedirRestricted = $statusHelper->_checkOpenBasedirs($folder);
		}

		// Writable check and contents listing if it's in site root and not restricted
		if($isFolderThere && !$isOpenbasedirRestricted)
		{
			// Get writability status
			$isWritable = is_writable($folder);
				
			// Get contained folders
			$subfolders = JFolder::folders($folder);
		}
		else
		{
			if($isFolderThere && !$isOpenbasedirRestricted)
			{
				$isWritable = is_writable($folder);
			}
			else
			{
				$isWritable = false;
			}

			$subfolders = array();
		}

		// Get parent directory
		$pathparts = explode(DS, $folder);
		if(is_array($pathparts))
		{
			$path = '';
			foreach($pathparts as $part)
			{
				$path .= empty($path) ? $part : DS.$part;
				if(empty($part)) {
					if( DS != '\\' ) $path = DS;
					$part = DS;
				}
				$crumb['label'] = $part;
				$crumb['folder'] = $path;
				$breadcrumbs[]=$crumb;
			}
				
			$junk = array_pop($pathparts);
			$parent = implode(DS, $pathparts);
		}
		else
		{
			// Can't indetify parent dir, use ourselves.
			$parent = $folder;
			$breadcrumbs = array();
		}

		$this->assign('folder',					$folder);
		$this->assign('parent',					$parent);
		$this->assign('exists',					$isFolderThere);
		$this->assign('inRoot',					$isInRoot);
		$this->assign('openbasedirRestricted',	$isOpenbasedirRestricted);
		$this->assign('writable',				$isWritable);
		$this->assign('subfolders',				$subfolders);
		$this->assign('breadcrumbs',			$breadcrumbs);

		parent::display();
	}
}
?>
