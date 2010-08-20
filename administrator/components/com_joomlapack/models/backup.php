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
 * The Backup page model
 *
 */
class JoomlapackModelBackup extends JModel
{
	/**
	 * Returns the backup description
	 *
	 * @param bool $noDefault Set to true to avoid setting a default
	 * @return string
	 */
	function getDescription($noDefault = false)
	{
		$description = JRequest::getString('description');

		if(empty($description) && (!$noDefault))
		{
			jimport('joomla.utilities.date');
			$user =& JFactory::getUser();
			$userTZ = $user->getParam('timezone',0);
			$dateNow = new JDate();
			$dateNow->setOffset($userTZ);
			return JText::_('BACKUP_DEFAULT_DESCRIPTION').' '.$dateNow->toFormat(JText::_('DATE_FORMAT_LC2')) ;
		}

		return $description;
	}

	/**
	 * Returns the backup comment
	 *
	 * @param bool $noDefault Set to true to avoid setting a default
	 * @return string
	 */
	function getComment($noDefault = false)
	{
		//function getVar($name, $default = null, $hash = 'default', $type = 'none', $mask = 0)
		$comment = JRequest::getVar('comment','','default','none',4);

		if(empty($comment) && (!$noDefault))
		{
			return '';
		}

		$comment = str_replace("'", "\\"."'", $comment);
		$comment = str_replace("\n", "\\"."n", $comment);
		$comment = str_replace("\r", "\\"."r", $comment);
		$comment = str_replace("\t", "\\"."t", $comment);

		return $comment;
	}

	function getProfileID()
	{
		$session =& JFactory::getSession();
		$default = $session->get('profile', null, 'joomlapack');

		return JRequest::getInt('profile', $default);
	}

	/**
	 * Returns a list of available backup profiles, to be consumed by JHTML in order to build
	 * a drop-down
	 *
	 * @return array
	 */
	function getProfilesList()
	{
		$db =& $this->getDBO();
		$query = "SELECT ".$db->nameQuote('id').", ".$db->nameQuote('description').
				" FROM ".$db->nameQuote('#__jp_profiles');
		$db->setQuery($query);
		$rawList = $db->loadAssocList();

		$options = array();
		if(!is_array($rawList)) return $options;

		foreach($rawList as $row)
		{
			$options[] = JHTML::_('select.option', $row['id'], $row['description']);
		}

		return $options;
	}

}
?>