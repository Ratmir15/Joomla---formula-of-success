<?php
/**
 * @package		JoomlaPack
 * @copyright	Copyright (C) 2006-2009 JoomlaPack Developers. All rights reserved.
 * @version		$Id$
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @since		1.2.1
 *
 * JoomlaPack is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 **/
defined('_JEXEC') or die('Restricted access');

/**
 * Installer deployment
 */
class JoomlapackCUBEDomainInstaller extends JoomlapackCUBEParts
{

	var $_offset;

	/**
	 * Implements the constructor of the class
	 *
	 * @return JoomlapackCUBEDomainDBBackup
	 */
	function JoomlapackCUBEDomainInstaller()
	{
		$this->_DomainName = "installer";
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainInstaller :: New instance");
	}

	/**
	 * Implements the _prepare abstract method
	 *
	 */
	function _prepare()
	{
		// Nothing to do
		$this->setState('prepared');
	}

	/**
	 * Implements the _run() abstract method
	 */
	function _run()
	{
		if( $this->_getState() == 'postrun' )
		{
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, __CLASS__." :: Already finished");
			$this->_Step = '';
			$this->_Substep = '';
		} else {
			$this->setState('running');
			$this->_isRunning = true;
			$this->_hasRan = false;
		}

		// Try to step the archiver
		$cube =& JoomlapackCUBE::getInstance();
		$archive =& $cube->provisioning->getArchiverEngine();
		$ret = $archive->transformJPA($this->_offset);
		// Error propagation
		if(($ret === false) || ($archive->getError() != ''))
		{
			$this->setError($archive->getError());
		}
		else
		{
			$this->_offset = $ret['offset'];
			$this->_Step = $ret['filename'];
		}

		// Check for completion
		if($ret['done'])
		{
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, __CLASS__.":: archive is initialized");
			$this->_hasRan = true;
			$this->_isRunning = false;
		}
	}

	/**
	 * Implements the _finalize() abstract method
	 *
	 */
	function _finalize()
	{
		$this->setState('finished');
	}

}