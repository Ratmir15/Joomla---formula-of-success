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

/**
 * The JTable child implementing #__jp_profiles data handling
 *
 */
class TableProfile extends JTable
{
	/** @var int Primary key */
	var $id;

	/** @var string Profile description */
	var $description;

	/**
	 * Constructor
	 *
	 * @param JDatabase $db Joomla!'s database
	 */
	function __construct( &$db )
	{
		parent::__construct('#__jp_profiles', 'id', $db);
	}

	/**
	 * Validation check
	 *
	 * @return bool True if the contents are valid
	 */
	function check()
	{
		if(!$this->description)
		{
			$this->setError(JText::_('TABLE_PROFILE_NODESCRIPTION'));
			return false;
		}

		return true;
	}

	/**
	 * Overloads the delete method to ensure we're not deleting the default profile
	 *
	 * @param int $id Optional; the record id
	 */
	function delete( $id=null )
	{
		if (($id==1) || ( is_null($id) && ($this->id == 1) ))
		{
			$this->setError(JText::_('TABLE_PROFILE_CANNOTDELETEDEFAULT'));
			return false;
		}
		else
		return parent::delete($id);
	}
}