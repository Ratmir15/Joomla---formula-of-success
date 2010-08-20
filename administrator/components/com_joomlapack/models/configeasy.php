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
 * The Control Panel (Easy Mode) model
 *
 */
class JoomlapackModelConfigeasy extends JModel
{
	var $_registry;

	/**
	 * Constructor; dummy for now
	 *
	 */
	function __construct()
	{
		parent::__construct();

		jpimport('models.registry', true);
		$this->_registry =& JoomlapackModelRegistry::getInstance();
	}

	/**
	 * A simple proxy to the registry model get() method
	 *
	 * @param string $key The variable name
	 * @return mixed The variable value
	 */
	function getVar($key)
	{
		return $this->_registry->get($key);
	}

	function getActionLoggingList()
	{
		$options = array();
		$options[] = JHTML::_('select.option', _JP_LOG_NONE , JText::_('CONFIGEZ_OPT_ALNONE'));
		$options[] = JHTML::_('select.option', _JP_LOG_WARNING , JText::_('CONFIGEZ_OPT_ALSIMPLE'));
		$options[] = JHTML::_('select.option', _JP_LOG_DEBUG , JText::_('CONFIGEZ_OPT_ALFULL'));
		return $options;
	}

	/**
	 * Returns an option list for the settings mode
	 *
	 * @return array
	 */
	function getSettingsModeList()
	{
		$options = array();
		$options[] = JHTML::_('select.option', 'optimistic',	JText::_('CONFIGEZ_OPT_SMOPTIMISTIC'));
		$options[] = JHTML::_('select.option', 'normal',		JText::_('CONFIGEZ_OPT_SMNORMAL'));
		$options[] = JHTML::_('select.option', 'conservative',	JText::_('CONFIGEZ_OPT_SMCONSERVATIVE'));
		return $options;
	}
}