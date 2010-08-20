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
 * Handles the #__jp_stats entries
 *
 */
class TableStatistic extends JTable
{
	/** @var int Primary key */
	var $id;
	/** @var string Short backup description (max. 255 characters) */
	var $description;
	/** @var string Free text for commenting using WYSIWYG editor */
	var $comment;
	/** @var string MySQL timestamp of backup start time */
	var $backupstart;
	/** @var string MySQL timestamp of backup end time */
	var $backupend;
	/** @var string Enumeration field for status */
	var $status;
	/** @var string Enumeration field for backup origin */
	var $origin;
	/** @var string Enumeration field for backup type */
	var $type;
	/** @var int Profile ID used during backup */
	var $profile_id;
	/** @var string Archive name, expanded form with extension */
	var $archivename;
	/** @var string Absolute path to backup archive, in case the output directory has changed */
	var $absolute_path;
	/** @var int Count of parts */
	var $multipart;

	/**
	 * Allowed status enumerations
	 *
	 * @var array
	 */
	var $_allowedStatus = array('run', 'fail', 'complete');

	/**
	 * Allowed origin enumerations
	 *
	 * @var array
	 */
	var $_allowedOrigin = array('backend', 'frontend');

	/**
	 * Allowed type enumerations
	 *
	 * @var array
	 */
	var $_allowedType = array('full', 'dbonly', 'extradbonly');
	/**
	 * Constructor
	 *
	 * @param JDatabase $db Joomla!'s database
	 */
	function __construct( &$db )
	{
		parent::__construct('#__jp_stats', 'id', $db);
	}

	/**
	 * Validation check
	 *
	 * @return bool True if the contents are valid
	 */
	function check()
	{
		if(empty($this->description))
		{
			$this->setError(JText::_('TABLE_STATS_MISSING_DESCRIPTION'));
			return false;
		}

		if(empty($this->backupstart))
		{
			$this->setError(JText::_('TABLE_STATS_MISSING_BACKUPSTART'));
			return false;
		}

		if(!in_array($this->status, $this->_allowedStatus))
		{
			$this->setError(JText::_('TABLE_STATS_WRONG_STATUS'));
			return false;
		}

		if(!in_array($this->origin, $this->_allowedOrigin))
		{
			$this->setError(JText::_('TABLE_STATS_WRONG_ORIGIN'));
			return false;
		}

		if(!in_array($this->type, $this->_allowedType))
		{
			$this->setError(JText::_('TABLE_STATS_WRONG_TYPE').': '.$this->type);
			return false;
		}

		if(empty($this->profile_id))
		{
			$this->setError(JText::_('TABLE_STATS_MISSING_PROFILE'));
			return false;
		}

		return true;
	}
}