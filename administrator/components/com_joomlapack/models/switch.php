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
 * The Switch Mode model
 *
 */
class JoomlapackModelSwitch extends JModel
{
	/**
	 * Constructor; dummy for now
	 *
	 */
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Removes all inclusion filters from the default profile
	 */
	function clearInclusionFilters()
	{
		$db =& $this->getDBO();
		$sql = 'DELETE FROM '.$db->nameQuote('#__jp_inclusion').
				' WHERE '.$db->nameQuote('profile').' = '.$db->Quote(1);
		$db->setQuery($sql);
		$db->query();

	}

	/**
	 * Removes all exclusion filters from the default profile
	 */
	function clearExclusionFilters()
	{
		$db =& $this->getDBO();
		$sql = 'DELETE FROM '.$db->nameQuote('#__jp_exclusion').
				' WHERE '.$db->nameQuote('profile').' = '.$db->Quote(1);
		$db->setQuery($sql);
		$db->query();
	}

}