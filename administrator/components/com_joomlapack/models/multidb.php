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

jimport('joomla.application.component.model');

/**
 * Multiple databases definition View
 *
 */
class JoomlapackModelMultidb extends JModel
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
	 * Get the total number of MultiDB records
	 *
	 * @return integer The number of MultiDB entries
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
	 * Returns an array of (serialized) entries. The list is returned as an object list,
	 * so you have to use $entry->value to get the serialized data!
	 *
	 * @param bool $noLimits If set to true, overrides fetching the limits
	 *
	 * @return array Object list of entries
	 */
	function getMultiDBList($noLimits = false)
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
				' WHERE '.$db->nameQuote('class').' = '.$db->Quote('multidb').
				' AND '.$db->nameQuote('profile').' = '.$db->Quote($profile);
		return $sql;
	}

	/**
	 * Gets a MultiDB record based on the id set in the request. If it fails, it
	 * will return null. Since it returns an object, you have to use $data->value
	 * to access the serialized data.
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
				' WHERE '.$db->nameQuote('class').' = '.$db->Quote('multidb').
				' AND '.$db->nameQuote('profile').' = '.$db->Quote($profile) .
				' AND '.$db->nameQuote('id').' = '.$db->Quote($id);

		$db->setQuery($sql);
		return $db->loadObject();
	}

	/**
	 * Saves or updates a MultiDB entry
	 *
	 * @param object $fromObject If set, use data from this object, instead of the request
	 * @access public
	 * @return bool True on success
	 */
	function save($fromObject = null)
	{
		// Get active profile
		$session =& JFactory::getSession();
		$profile = $session->get('profile', null, 'joomlapack');

		if(!is_object($fromObject))
		{
			// Map from $_REQUEST
			$id			= JRequest::getVar('id',null);
			$value = array();
			$value['host']		= JRequest::getVar('host');
			$value['port']		= JRequest::getVar('port');
			$value['user']		= JRequest::getVar('user');
			$value['pass']		= JRequest::getVar('pass');
			$value['database']	= JRequest::getVar('database');
			$serializedValue = serialize($value);
				
			// Create a table
			$fromObject = array(
				'id'		=> $id,
				'profile'	=> $profile,
				'class'		=> 'multidb',
				'value'		=> $serializedValue
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
	 * Deletes the selected MultiDB entry/-ies
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
				' WHERE '.$db->nameQuote('class').' = '.$db->Quote('multidb').
				' AND '.$db->nameQuote('profile').' = '.$db->Quote($profile) .
				' AND '.$db->nameQuote('id').' = '.$db->Quote($id);
		return $sql;
	}

	/**
	 * Creates a new EFF entry by cloning an existing one
	 *
	 * @return integer The new record's ID
	 */
	function copy()
	{
		$fromObject = $this->getRecord();
		$fromObject->id = 0;
		return $this->save($fromObject);
	}

}