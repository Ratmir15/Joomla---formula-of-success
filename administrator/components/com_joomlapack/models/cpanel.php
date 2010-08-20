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
 * The Control Panel model
 *
 */
class JoomlapackModelCpanel extends JModel
{
	/**
	 * Contructor; dummy for now
	 *
	 */
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Get an array of icon definitions for the Control Panel
	 *
	 * @return array
	 */
	function getIconDefinitions()
	{
		jpimport('helpers.utils', true);
		JoomlapackHelperUtils::getJoomlaPackVersion();

		$ret = array();

		$registry =& JoomlapackModelRegistry::getInstance();
		if($registry->get('easymode', false))
		{
			$ret[] = $this->_makeIconDefinition( 'reset.png', JText::_('SWITCHTOPRO'), 'switch' );
			$ret[] = $this->_makeIconDefinition( 'config.png', JText::_('CONFIGURATION'), 'configeasy' );
			$ret[] = $this->_makeIconDefinition( 'backup.png', JText::_('BACKUP'), 'backup' );
			$ret[] = $this->_makeIconDefinition( 'bufa.png', JText::_('BUADMIN'), 'buadmin' );
			$ret[] = $this->_makeIconDefinition( 'log.png', JText::_('VIEWLOG'), 'log' );
		}
		else
		{
			$ret[] = $this->_makeIconDefinition( 'reset.png', JText::_('SWITCHTOEASY'), 'switch' );
				
			if(JPSPECIALEDITION) $ret[] = $this->_makeIconDefinition( 'profiles.png', JText::_('PROFILES'), 'profiles' );
			$ret[] = $this->_makeIconDefinition( 'config.png', JText::_('CONFIGURATION'), 'config' );
			$ret[] = $this->_makeIconDefinition( 'backup.png', JText::_('BACKUP'), 'backup' );
			$ret[] = $this->_makeIconDefinition( 'bufa.png', JText::_('BUADMIN'), 'buadmin' );
			$ret[] = $this->_makeIconDefinition( 'log.png', JText::_('VIEWLOG'), 'log' );
				
			if(JPSPECIALEDITION) $ret[] = $this->_makeIconDefinition( 'multidb.png', $this->_labelInclusion(JText::_('MULTIDB'),'multidb') , 'multidb' );
			if(JPSPECIALEDITION) $ret[] = $this->_makeIconDefinition( 'dif.png', $this->_labelInclusion(JText::_('EXTRADIRS'),'eff'), 'eff' );
			$ret[] = $this->_makeIconDefinition( 'sff.png', $this->_labelExclusion(JText::_('SFF'), 'sff'), 'sff' );
			$ret[] = $this->_makeIconDefinition( 'def.png', $this->_labelExclusion(JText::_('DEF'), 'def'), 'def' );
			if(JPSPECIALEDITION) $ret[] = $this->_makeIconDefinition( 'full.png', $this->_labelExclusion(JText::_('DCS'), array('Skipfiles', 'Skipdirs')), 'skip' );
			if(JPSPECIALEDITION) $ret[] = $this->_makeIconDefinition( 'dbef.png', $this->_labelExclusion(JText::_('DBEF'), 'dbef'), 'dbef' );
			if(JPSPECIALEDITION) $ret[] = $this->_makeIconDefinition( 'extfilter.png', $this->_labelExclusion(JText::_('EXTFILTER'), array('components', 'modules', 'plugins', 'languages', 'templates')), 'extfilter' );
			if(JPSPECIALEDITION) $ret[] = $this->_makeIconDefinition( 'cronman.png', JText::_('CRONMAN'), 'cronman' );

			//$ret[] = $this->_makeIconDefinition( 'ftp.png', JText::_('FTPXFERWIZARD'), 'ftpx' );
		}

		// Add Live Update button if it is supported on this server
		jpimport('models.update', true);
		$updatemodel =& JoomlapackModelUpdate::getInstance('update','JoomlapackModel');
		if($updatemodel->isLiveUpdateSupported())
		{
			$updates =& $updatemodel->getUpdates();
			if($updates->update_available)
			{
				$ret[] = $this->_makeIconDefinition( 'error_big.png', JText::_('CPANEL_UPGRADE_NOW'), 'update' );
			}
			else
			{
				$ret[] = $this->_makeIconDefinition( 'ok_big.png', JText::_('CPANEL_UPGRADE_UPTODATE'), 'update' );
			}
		}

		return $ret;
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

	/**
	 * Returns the active Profile ID
	 *
	 * @return int The active profile ID
	 */
	function getProfileID()
	{
		$session =& JFactory::getSession();
		return $session->get('profile', null, 'joomlapack');
	}

	/**
	 * Creates an icon definition entry
	 *
	 * @param string $iconFile The filename of the icon on the GUI button
	 * @param string $label The label below the GUI button
	 * @param string $view The view to fire up when the button is clicked
	 * @return array The icon definition array
	 */
	function _makeIconDefinition($iconFile, $label, $view = null, $task = null )
	{
		return array(
			'icon'	=> $iconFile,
			'label'	=> $label,
			'view'	=> $view,
			'task'	=> $task
		);
	}

	/**
	 * Was the last backup a failed one? Used to apply magic settings as a means of
	 * troubleshooting.
	 *
	 * @return bool
	 */
	function isLastBackupFailed()
	{
		$db =& $this->getDBO();
		$query = 'SELECT max(id) FROM #__jp_stats';
		$db->setQuery($query);
		$id = $db->loadResult();

		if(empty($id)) return false;

		jpimport('models.statistics', true);
		$statmodel =& JoomlapackModelStatistics::getInstance();

		$statmodel->setId($id);
		$record =& $statmodel->getStatistic();

		return ($record->status == 'fail');
	}

	/**
	 * Returns the directly more conservative "Settings Mode" setting
	 * which is then proposed to be applied in order to attempt overcoming
	 * backup problems.
	 *
	 * @return string|null The next Settings Mode, or null if we're already on conservative mode
	 */
	function nextSettingsMode()
	{
		$registry =& JoomlapackModelRegistry::getInstance();
		$currentMode = $registry->get('settingsmode');

		switch($currentMode)
		{
			case 'custom':
			case 'optimistic':
				return 'normal';
				break;

			case 'normal':
				return 'conservative';
				break;

			case 'conservative':
				return null;
				break;
		}
	}

	/**
	 * Returns the number of active exclusion filter items for a specific filter class
	 * in the current profile.
	 * @param string $class The filter class to look for
	 * @return int The number of active items
	 */
	function _getExclusionFilterCount($class)
	{
		// Get active profile
		$session =& JFactory::getSession();
		$profile = $session->get('profile', null, 'joomlapack');

		$db =& $this->getDBO();
		$query = 'SELECT COUNT(*) FROM #__jp_exclusion WHERE '.
		$db->nameQuote('class') . ' = ' . $db->Quote($class).
				' AND '.$db->nameQuote('profile').' = '.$db->Quote($profile);
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Returns the number of active inclusion filter items for a specific filter class
	 * in the current profile.
	 * @param string $class The filter class to look for
	 * @return int The number of active items
	 */
	function _getInclusionFilterCount($class)
	{
		// Get active profile
		$session =& JFactory::getSession();
		$profile = $session->get('profile', null, 'joomlapack');

		$db =& $this->getDBO();
		$query = 'SELECT COUNT(*) FROM #__jp_inclusion WHERE '.
		$db->nameQuote('class') . ' = ' . $db->Quote($class).
				' AND '.$db->nameQuote('profile').' = '.$db->Quote($profile);
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Post-process an inclusion filter's label and add the number of active items,
	 * if any are present.
	 * @param string $label The localized label
	 * @param string $class The filter class
	 * @return string The post-processed label
	 */
	function _labelInclusion($label, $class)
	{
		$count = $this->_getInclusionFilterCount($class);
		if($count > 0)
		{
			$l = $label.' ('.$count.')';
			return $l;
		}
		return $label;
	}

	/**
	 * Post-process an exclusion filter's label and add the number of active items,
	 * if any are present.
	 * @param string $label The localized label
	 * @param string $class The filter class
	 * @return string The post-processed label
	 */
	function _labelExclusion($label, $class)
	{
		if(is_array($class))
		{
			$count = 0;
			foreach($class as $c)
			{
				$count += $this->_getExclusionFilterCount($c);
			}
		}
		else
		{
			$count = $this->_getExclusionFilterCount($class);
		}

		if($count > 0)
		{
			$l = $label.' ('.$count.')';
			return $l;
		}
		return $label;
	}
}