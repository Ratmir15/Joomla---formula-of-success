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

jimport('joomla.application.component.model');

/**
 * External Files inclusion model
 *
 */
class JoomlapackModelEff extends JModel
{

	/**
	 * The pagination object for the display view
	 *
	 * @var JPagination
	 */
	var $_pagination;

	function __construct()
	{
		global $mainframe;

		parent::__construct();

		// Get the pagination parameters
		$limit = $mainframe->getUserStateFromRequest('global.list.limit','limit',$mainframe->getCfg('list_limit'));
		$limitstart = $mainframe->getUserStateFromRequest(JRequest::getCmd('option','com_joomlapack').'limitstart','limitstart',0);

		// Set the pagination parameters
		$this->setState('limit', $limit);
		$this->setState('limitstart', $limitstart);
	}

	/**
	 * Get a pagination object
	 *
	 * @access public
	 * @return JPagination
	 */
	function getPagination()
	{
		if(empty($this->_pagination))
		{
			jimport('joomla.html.pagination');
				
			// Prepare pagination values
			$total = $this->getTotal();
			$limitstart = $this->getState('limitstart');
			$limit = $this->getState('limit');
				
			// Create the pagination object
			$this->_pagination = new JPagination($total, $limitstart, $limit);
		}

		return $this->_pagination;
	}

	/**
	 * Get the total number of EFF records
	 *
	 * @return integer The number of EFF entries
	 */
	function getTotal()
	{
		static $_total;

		if(empty($_total))
		{
			$sql = $this->_buildListQuery();
			$_total = $this->_getListCount($sql);
		}

		return $_total;
	}

	/**
	 * Returns an array of entries. The list is returned as an object list,
	 * so you have to use $entry->value to get the folder path!
	 *
	 * @param bool $noLimits If set to true, overrides fetching the limits
	 *
	 * @return array Object list of entries
	 */
	function getEFFList($noLimits = false)
	{
		static $_data;

		if(empty($_data) || $noLimits)
		{
			$sql = $this->_buildListQuery();
			if(!$noLimits)
			{
				$limitstart = $this->getState('limitstart');
				$limit = $this->getState('limit');
			}
			else
			{
				$limitstart = 0;
				$limit = 0;
			}
				
			$_data = $this->_getList($sql, $limitstart, $limit);
		}

		return $_data;
	}

	/**
	 * Creates the SQL query for obtaining a paginated list of records
	 *
	 * @return string The SQL query
	 */
	function _buildListQuery()
	{
		$db =& $this->getDBO();
		$session =& JFactory::getSession();
		$profile = $session->get('profile', null, 'joomlapack');
		$sql = 'SELECT * FROM #__jp_inclusion'.
				' WHERE '.$db->nameQuote('class').' = '.$db->Quote('eff').
				' AND '.$db->nameQuote('profile').' = '.$db->Quote($profile);
		return $sql;
	}

	/**
	 * Gets an EFF record based on the id set in the request. If it fails, it
	 * will return null. Since it returns an object, you have to use $data->value
	 * to access the folder path.
	 *
	 * @return object|null An object holding the data, or null
	 */
	function getRecord()
	{
		// Get the ID from the request
		$cid = JRequest::getVar('cid', false);
		if($cid)
		{
			$cid = JRequest::getVar('cid', false, 'DEFAULT', 'array');
			$id = $cid[0];
		}
		else
		{
			$id = JRequest::getInt('id', 0);
		}

		$session =& JFactory::getSession();
		$profile = $session->get('profile', null, 'joomlapack');

		$db =& $this->getDBO();
		$sql = 'SELECT * FROM '.$db->nameQuote('#__jp_inclusion').
				' WHERE '.$db->nameQuote('class').' = '.$db->Quote('eff').
				' AND '.$db->nameQuote('profile').' = '.$db->Quote($profile) .
				' AND '.$db->nameQuote('id').' = '.$db->Quote($id);

		$db->setQuery($sql);
		return $db->loadObject();
	}

	/**
	 * Saves or updates an EFF entry
	 *
	 * @param object $fromObject If set, use data from this object, instead of the request
	 * @access public
	 * @return bool True on success
	 */
	function save($fromObject = null)
	{
		jpimport('helpers.utils', true);
		// Get active profile
		$session =& JFactory::getSession();
		$profile = $session->get('profile', null, 'joomlapack');

		if(!is_object($fromObject))
		{
			// Map from $_REQUEST
			$id			= JRequest::getVar('id',null);
				
			// Create a table
			$fromObject = array(
				'id'		=> $id,
				'profile'	=> $profile,
				'class'		=> 'eff',
				'value'		=> JoomlapackHelperUtils::TranslateWinPath(JRequest::getVar('fsdir'))
			);
		}

		// Load table class
		$table =& $this->getTable('Inclusion');
		// Assign from data and save the inclusion filter record
		if(!$table->save($fromObject))
		{
			$this->setError($table->getError());
			return false;
		}

		return true;
	}

	/**
	 * Deletes the selected EFF entry/-ies
	 *
	 * @return bool True on success
	 */
	function remove()
	{
		// Get the ID from the request
		$cid = JRequest::getVar('cid', false);
		if($cid)
		{
			$cid = JRequest::getVar('cid', false, 'DEFAULT', 'array');
		}
		else
		{
			$id = JRequest::getInt('id', 0);
			$cid = array();
			$cid[] = $id;
		}

		$session =& JFactory::getSession();
		$profile = $session->get('profile', null, 'joomlapack');

		$db =& $this->getDBO();
		foreach($cid as $id)
		{
			$sql = $this->_buildRemoveSQL($id, $profile);
			$db->setQuery($sql);
			$db->query();
		}

		return true;
	}

	/**
	 * Generates the DELETE query
	 *
	 * @param int $id Record ID
	 * @param int $profile Current profile
	 * @return string SQL for the DELETE query
	 * @access private
	 */
	function _buildRemoveSQL($id, $profile)
	{
		$db =& $this->getDBO();
		$sql = 'DELETE FROM '.$db->nameQuote('#__jp_inclusion').
				' WHERE '.$db->nameQuote('class').' = '.$db->Quote('eff').
				' AND '.$db->nameQuote('profile').' = '.$db->Quote($profile) .
				' AND '.$db->nameQuote('id').' = '.$db->Quote($id);
		return $sql;
	}

	function copy()
	{
		$fromObject = $this->getRecord();
		$fromObject->id = 0;
		return $this->save($fromObject);
	}

	/**
	 * Returns a mapping array. Each array element is a hash array with these keys:
	 * 'id' (unique ID), 'fsdir' (Filesystem directory = source), 'vdir' (Virtual directory = target).
	 *
	 * @return unknown
	 */
	function &getMapping()
	{
		$objectList = $this->getEFFList(true);
		$ret = array();

		if(is_array($objectList))
		{
			foreach($objectList as $effObject)
			{
				$newRecord = array();
				$newRecord['id'] = $effObject->id;
				
				// Process [ROOTPARENT]
				$fsdir = $effObject->value;
				if(substr($fsdir, 0, 12) == '[ROOTPARENT]')
				{
					$rootparent = @realpath(JPATH_SITE.DS.'..');
					if(!empty($rootparent) && (substr($rootparent, -1) == DS) ) $rootparent = substr($rootparent, 0, -1);
					$fsdir = $rootparent . substr($fsdir, 12);
				}
				// Change all slashes to UNIX-style
				jpimport('helpers.utils', true);
				$fsdir = JoomlapackHelperUtils::TranslateWinPath($fsdir);

				// Add the absolute path to the record
				$newRecord['fsdir'] = $fsdir;

				// Get the directory name				
				$lastSlash = strrpos($fsdir, '/');
				$dirName = substr($fsdir, $lastSlash+1, strlen($fsdir) - $lastSlash);
				
				$newRecord['vdir'] = $newRecord['id']."-$dirName";
				$ret[] = $newRecord;
			}
		}

		return $ret;
	}

	/**
	 * Generates a human-readable text file which contains mapping information
	 *
	 */
	function getMappingText()
	{
		$ret = JText::_('EFF_MAPTEXT_INTRO')."\n\n";
		$map =& $this->getMapping();
		if(is_array($map))
		if(count($map) > 0)
		foreach($map as $element)
		{
			$ret.=JText::sprintf('EFF_MAPTEXT_LINE',$map['vdir'],$map['fsdir']);
		}
		return $map;
	}

}