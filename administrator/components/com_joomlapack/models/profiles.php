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
 * The Profiles MVC model class
 *
 */
class JoomlapackModelProfiles extends JModel
{
	/** @var int Profile ID */
	var $_id;

	/** @var stdClass Profile object */
	var $_profile;

	/** @var JTable The profiles table being updated */
	var $_table;

	/**
	 * Constructor. Sets the internal reference to Profile ID based on the request parameters.
	 *
	 */
	function __construct()
	{
		global $mainframe;

		parent::__construct();

		// Get the pagination request variables
		$limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'));
		$limitstart = $mainframe->getUserStateFromRequest(JRequest::getCmd('option','com_joomlapack').'profileslimitstart','limitstart',0);

		// Set the page pagination variables
		$this->setState('limit',$limit);
		$this->setState('limitstart',$limitstart);

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

		if(!JPSPECIALEDITION)
		{
			$id = 1;
		}

		$this->setId($id);
	}

	/**
	 * Sets a Profile ID and resets internal data
	 *
	 * @param int $id Profile ID
	 */
	function setId($id=0)
	{
		if(!JPSPECIALEDITION) $id = 1;
		$this->_id = $id;
		$this->_profile = null;
	}

	/**
	 * Returns the currently set profile ID
	 * @return int
	 */
	function getId()
	{
		return $this->_id;
	}

	/**
	 * Returns the entry for the profile whose ID is loaded in the model
	 *
	 * @return stdClass An object representing the profile
	 */
	function &getProfile()
	{
		if(empty($this->_profile))
		{
			$db =& $this->getDBO();
			$query = "SELECT * FROM ".$db->nameQuote('#__jp_profiles')." WHERE ".
			$db->nameQuote('id')." = ".$this->_id;
			$db->setQuery($query);
			$this->_profile = $db->loadObject();
		}
		return $this->_profile;
	}

	/**
	 * Gets a list of all the profiles as an array of objects
	 *
	 * @param bool $overrideLimits If set, it will list all entries, without applying limits
	 * @return array List of profiles
	 */
	function getProfilesList($overrideLimits = false)
	{
		if( empty($this->_list) )
		{
			$db =& $this->getDBO();
			$query = "SELECT * FROM ".$db->nameQuote('#__jp_profiles');
			if(!JPSPECIALEDITION)
			{
				$query .= ' WHERE '.$db->nameQuote('id').'=1';
			}
			$limitstart = $this->getState('limitstart');
			$limit = $this->getState('limit');
			if(!$overrideLimits)
			$this->_list = $this->_getList($query, $limitstart, $limit);
			else
			$this->_list = $this->_getList($query);
		}

		return $this->_list;
	}

	/**
	 * Saves a profile
	 *
	 * @param object|array $data The data to be bound and saved
	 * @return bool True on success
	 */
	function save($data)
	{
		// Get the table
		$this->_table =& $this->getTable('Profile');
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
	 * Attempts to delete the record whose ID is set in the model. Fails upon detecting
	 * an attempt to delete the default profile.
	 *
	 * @return bool True on success
	 */
	function delete()
	{
		// Do not delete the default profile
		if($this->_id == 1)
		{
			$this->setError(JText::_('PROFILE_CANNOT_DELETE_DEFAULT'));
			return false;
		}
		// Check for invalid id's (not numeric, or <= 0)
		elseif( (!is_numeric($this->_id)) || ($this->_id <= 0) )
		{
			$this->setError(JText::_('PROFILE_INVALID_ID'));
			return false;
		}
		$db =& $this->getDBO();

		// 1. Delete incusion filters
		$sql = 'DELETE FROM '.$db->nameQuote('#__jp_inclusion').' WHERE '.
		$db->nameQuote('profile').' = '.$this->_id;
		$db->setQuery($sql);
		if(!$db->query())
		{
			$this->setError($db->getError());
			return false;
		}

		// 2. Delete exclusion filters
		$sql = 'DELETE FROM '.$db->nameQuote('#__jp_exclusion').' WHERE '.
		$db->nameQuote('profile').' = '.$this->_id;
		$db->setQuery($sql);
		if(!$db->query())
		{
			$this->setError($db->getError());
			return false;
		}

		// 3. Delete JoomlaPack Registry values
		$sql = 'DELETE FROM '.$db->nameQuote('#__jp_registry').' WHERE '.
		$db->nameQuote('profile').' = '.$this->_id;
		$db->setQuery($sql);
		if(!$db->query())
		{
			$this->setError($db->getError());
			return false;
		}

		// 4. Delete the profile itself
		$sql = 'DELETE FROM '.$db->nameQuote('#__jp_profiles').' WHERE '.
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
	 * Tries to copy the profile whose ID is set in the model to a new record
	 *
	 * @return bool True on success
	 */
	function copy()
	{
		// Check for invalid id's (not numeric, or <= 0)
		if( (!is_numeric($this->_id)) || ($this->_id <= 0) )
		{
			$this->setError(JText::_('PROFILE_INVALID_ID'));
			return false;
		}

		$db =& $this->getDBO();

		// 1. Copy the profile itself
		// -- Load the profile using the TableProfile class
		$profileTable = $this->getTable('profile');
		if(!$profileTable->load($this->_id))
		{
			$this->setError($profileTable->getError());
			return false;
		}
		// Force creating a new record
		//$profileTable->setVar('id', 0);
		$profileTable->id = 0;
		// Try to save the new record
		if($profileTable->check())
		{
			if(!$profileTable->store(true))
			{
				$this->setError($profileTable->getError());
				return false;
			}
		}
		else
		{
			$this->setError($profileTable->getError());
			return false;
		}
		// Get the new Profile ID
		$newProfileID = $profileTable->id;

		// 2. Copy inclusion filters
		if(!$this->_copyToNewProfile('inclusion', '#__jp_inclusion', $newProfileID))
		{
			return false;
		}

		// 3. Copy exclusion filters
		if(!$this->_copyToNewProfile('exclusion', '#__jp_exclusion', $newProfileID))
		{
			return false;
		}

		// 4. Copy JoomlaPack Registry values
		if(!$this->_copyToNewProfile('registry', '#__jp_registry', $newProfileID))
		{
			return false;
		}

		$this->setId($newProfileID);

		return true;
	}

	/**
	 * Ensures that the user passed on a valid ID.
	 *
	 * @return bool True if the ID belongs to a valid profile, false otherwise
	 */
	function checkID()
	{
		// Check for invalid id's (not numeric, or <= 0)
		if( (!is_numeric($this->_id)) || ($this->_id <= 0) ) return false;

		// Check for existing ID, or return false
		$myProfile =& $this->getProfile();
		return is_object($myProfile);
	}

	/**
	 * Returns an XML file with current profile's settings (configuration + filter options)
	 *
	 * @return string The XML export data
	 */
	function &export()
	{
		static $_xml; // Static variable holding export data

		if(!$_xml)
		{
			// Load JoomlaPack registry class and get a copy of the DBO
			jpimport('models.registry', true);
			$registry = new JoomlapackModelRegistry(array('id'=>$this->_id));
			$db =& JFactory::getDBO();
				
			// Create XML header and dump configuration
			if(!defined('JPCR')) define('JPCR', "\n");
			$_xml = '<?xml version="1.0" encoding="utf-8"?>'.JPCR;
			$_xml .= '<jpexport version="1.3">'.JPCR;
			$_xml .= "\t".'<config>'.JPCR;
			$reg =& $registry->getRawRegistryArray();
			foreach($reg as $key => $value)
			{
				$_xml .= "\t\t<$key><![CDATA[" . serialize($value) . "]]></$key>\n";
			}
			$_xml .= "\t".'</config>'.JPCR;

			// Dump inclusion filters
			$_xml .= "\t".'<inclusion>'.JPCR;
			$sql = 'SELECT * FROM #__jp_inclusion WHERE '.$db->nameQuote('profile').' = '.$db->Quote($this->_id);
			$db->setQuery($sql);
			$data = $db->loadAssocList();
			foreach($data as $entry)
			{
				$_xml .= "\t\t<entry><![CDATA[" . serialize($entry) . "]]></entry>\n";
			}
			$_xml .= "\t".'</inclusion>'.JPCR;

			// Dump exclusion filters
			$_xml .= "\t".'<exclusion>'.JPCR;
			$sql = 'SELECT * FROM #__jp_exclusion WHERE '.$db->nameQuote('profile').' = '.$db->Quote($this->_id);
			$db->setQuery($sql);
			$data = $db->loadAssocList();
			foreach($data as $entry)
			{
				$_xml .= "\t\t<entry><![CDATA[" . serialize($entry) . "]]></entry>\n";
			}
			$_xml .= "\t".'</exclusion>'.JPCR;
				
			// Finish up the document
			$_xml .= '</jpexport>'.JPCR;
		}

		return $_xml;
	}

	/**
	 * Copies data from profile-bound tables to new records bound to a new Profile ID
	 *
	 * @param string $className The JTable descendant class name part
	 * @param string $tableName The abstract name of the database table
	 * @param int $newProfileID The new Profile's ID
	 * @return bool True on success
	 * @access private
	 */
	function _copyToNewProfile($className, $tableName, $newProfileID)
	{
		$db =& $this->getDBO();
		$table =& $this->getTable($className);

		$sql = 'SELECT '.$db->nameQuote('id').' FROM '.$db->nameQuote($tableName).' WHERE '.
		$db->nameQuote('profile').' = '.$this->_id;
		$db->setQuery($sql);
		$ids = $db->loadRowList();

		if(JError::isError($db))
		{
			// Fail on DB error
			$this->setError($db->getError());
			return false;
		}
		// Loop only if we've got records
		if( count($ids) > 0 )
		{
				
			foreach($ids as $thisIdArray)
			{
				$thisId = $thisIdArray[0];
				$table->reset();
				if(!$table->load($thisId))
				{
					$this->setError($table->getError());
					return false;
				}
				$table->id = 0;
				$table->profile = $newProfileID;
				if($table->check())
				{
					if(!$table->store(true))
					{
						$this->setError($table->getError());
						return false;
					}
				}
				else
				{
					$this->setError($table->getError());
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Get a pagination object
	 *
	 * @access public
	 * @return JPagination
	 *
	 */
	function getPagination()
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
			$db =& $this->getDBO();
			$query = "SELECT ".$db->nameQuote('*')." FROM ".$db->nameQuote('#__jp_profiles');
			if(!JPSPECIALEDITION) $query .= ' WHERE '.$db->nameQuote('id').'=1';
			$this->_total = $this->_getListCount($query);
		}

		return $this->_total;
	}


}