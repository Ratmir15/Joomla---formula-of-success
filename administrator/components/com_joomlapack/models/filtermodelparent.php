<?php
/**
 * @package JoomlaPack
 * @version $id$
 * @license GNU General Public License, version 2 or later
 * @author JoomlaPack Developers
 * @copyright Copyright 2006-2009 JoomlaPack Developers
 * @since 2.2
 */
defined('_JEXEC') or die('Restricted access');

/**
 * Abstract class acting as parent class for all filters' model classes.
 * Contains the essential functionality to provide tabular view.
 *
 * @author Nicholas K. Dionysopoulos
 */
class FilterModelParent extends JModel
{
	/** @var integer Filter Record ID */
	var $_id;

	/** @var stdClass Record object */
	var $_record;

	/** @var The filters table being updated */
	var $_table;

	/** @var int Total number of filter records */
	var $_total;

	/** @var array A list of filter records */
	var $_list;

	/** @var string The filter class being read */
	var $_filterclass="";

	/** @var string The filter type of this class, can be inclusion or exclusion **/
	var $_filtertype="";

	/**
	 * Constructor. Sets the internal reference to Statistics ID.
	 *
	 */
	function __construct($id = 0)
	{
		global $mainframe;

		parent::__construct();

		// Get the pagination request variables
		$limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'));
		$limitstart = $mainframe->getUserStateFromRequest(JRequest::getCmd('option','com_joomlapack') .'profileslimitstart','limitstart',0);

		// Set the page pagination variables
		$this->setState('limit',$limit);
		$this->setState('limitstart',$limitstart);

		$this->setId($id);
	}

	/**
	 * Sets a Record ID and resets internal data
	 *
	 * @param int $id Record ID
	 */
	function setId($id=0)
	{
		$this->_id = $id;
		$this->_record = null;
	}

	/**
	 * Loads a single record from the database
	 * @return object
	 */
	function &getRecord()
	{
		if(empty($this->_stats))
		{
			$db =& $this->getDBO();
			$query = "SELECT * FROM ".$db->nameQuote('#__jp_'.$this->_filterclass).
			" WHERE ".$db->nameQuote('id')." = ".$this->_id;
			$db->setQuery($query);
			$db->query();
			$this->_record = $db->loadObject();
		}
		return $this->_record;
	}

	/**
	 * Returns a list of records, respecting the pagination
	 *
	 * @return array
	 */
	function &getRecordsList($overrideLimits = false)
	{
		if( empty($this->_list) )
		{
			$db =& $this->getDBO();
			$query = $this->_getListQuery();
			$limitstart = $this->getState('limitstart');
			$limit = $this->getState('limit');
			if($overrideLimits)
			$this->_list = $this->_getList($query);
			else
			$this->_list = $this->_getList($query, $limitstart, $limit);
		}

		return $this->_list;
	}

	/**
	 * Saves a filter record
	 *
	 * @param object|array $data The data to be bound and saved
	 * @return bool True on success
	 */
	function save(&$data)
	{
		jimport('joomla.utilities.string');

		// Get the table
		$this->_table =& $this->getTable(JString::ucfirst($this->_filtertype));
		// Try to save the data
		if(!$this->_table->save($data))
		{
			// Oops... Something wrong happened
			$this->setError($this->_table->getError());
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Returns the last saved table
	 *
	 * @return JTable
	 */
	function &getSavedTable()
	{
		return $this->_table;
	}

	/**
	 * Delete the filters record whose ID is set in the model
	 *
	 * @return bool True on success
	 */
	function delete()
	{
		$db =& $this->getDBO();

		if( (!is_numeric($this->_id)) || ($this->_id <= 0) )
		{
			$this->setError(JText::_('FILTERS_ERROR_INVALIDID'));
			return false;
		}

		// Delete record
		$sql = 'DELETE FROM '.$db->nameQuote('#__jp_'.$this->_filtertype).' WHERE '.
		$db->nameQuote('id').' = '.$this->_id;
		$db->setQuery($sql);
		if(!$db->query())
		{
			$this->setError($db->getError());
			return false;
		}

		return true;
	}

	/**
	 * Returns the query used to fetch a list of records
	 * @return unknown_type
	 */
	function _getListQuery()
	{
		static $query;

		if(is_null($query))
		{
			// Get active profile
			$session =& JFactory::getSession();
			$profile = $session->get('profile', null, 'joomlapack');
				
			$db =& $this->getDBO();
			$query = "SELECT * FROM ".$db->nameQuote('#__jp_'.$this->_filtertype).' WHERE '.
			$db->nameQuote('class') . ' = ' . $db->Quote($this->_filterclass).
				' AND '.$db->nameQuote('profile').' = '.$db->Quote($profile).
				' ORDER BY '.$db->nameQuote('value').' ASC';
		}

		return $query;
	}

	/**
	 * Get a pagination object
	 *
	 * @access public
	 * @return JPagination
	 *
	 */
	function &getPagination()
	{
		if( empty($this->_pagination) )
		{
			// Import the pagination library
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
	 * Get number of profile items
	 *
	 * @access public
	 * @return integer
	 */
	function getTotal()
	{
		if( empty($this->_total) )
		{
			$query = $this->_getListQuery();
			$this->_total = $this->_getListCount($query);
		}

		return $this->_total;
	}

}
