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
 * Handles the #__jp_inclusion entries
 *
 */
class TableInclusion extends JTable
{
	/** @var int Primary key */
	var $id;
	/** @var int Profile ID */
	var $profile;
	/** @var string Filter class */
	var $class;
	/** @var string Serialized value */
	var $value;

	/**
	 * Constructor
	 *
	 * @param JDatabase $db Joomla!'s database
	 */
	function __construct( &$db )
	{
		parent::__construct('#__jp_inclusion', 'id', $db);
	}

	/**
	 * Validation check
	 *
	 * @return bool True if the contents are valid
	 */
	function check()
	{
		if(empty($this->profile))
		{
			$this->setError(JText::_('TABLE_MISSING_PROFILE'));
			return false;
		}

		if(empty($this->class))
		{
			$this->setError(JText::_('TABLE_EXTRADB_MISSING_HOST'));
			return false;
		}

		if(empty($this->value))
		{
			$this->setError(JText::_('TABLE_MISSING_VALUE'));
			return false;
		}

		return true;
	}

}