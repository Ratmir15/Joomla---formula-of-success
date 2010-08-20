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
 * Multiple database backup engine.
 */
class JoomlapackCUBEDomainDBBackup extends JoomlapackCUBEParts
{
	/**
	 * A list of the databases to be packed
	 *
	 * @var array
	 */
	var $_databaseList = array();

	/**
	 * The current instance of JoomlapackDumperDefault used to backup tables
	 *
	 * @var JoomlapackDumperDefault
	 */
	var $_dumper = null;

	/**
	 * The current index of _databaseList
	 *
	 * @var integer
	 */
	var $_currentListIndex = null;

	/**
	 * Implements the constructor of the class
	 *
	 * @return JoomlapackCUBEDomainDBBackup
	 */
	function JoomlapackCUBEDomainDBBackup()
	{
		$this->_DomainName = "PackDB";
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainDBBackup :: New instance");
	}

	/**
	 * Implements the _prepare abstract method
	 *
	 */
	function _prepare()
	{
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainDBBackup :: Preparing instance");
		$this->_getDatabaseList();
		if($this->getError())
		{
			return false;
		}
		$this->_currentListIndex = 0;
		$this->setState('prepared');
	}

	/**
	 * Implements the _run() abstract method
	 */
	function _run()
	{
		if( $this->_getState() == 'postrun' )
		{
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainDBBackup :: Already finished");
			$this->_Step = '';
			$this->_Substep = '';
		} else {
			$this->setState('running');
			$this->_isRunning = true;
			$this->_hasRan = false;
		}


		// Make sure we have a JoomlapackDumperDefault instance loaded!
		if(is_null( $this->_dumper ))
		{
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainDBBackup :: Iterating next database");
			// Create a new instance
			$cube =& JoomlapackCUBE::getInstance();
			$provisioning =& $cube->getProvisioning();
			$this->_dumper =& $provisioning->getDBPackerEngine(true);
				
			// Configure the dumper instance
			$this->_dumper->setup( $this->_databaseList[$this->_currentListIndex] );
				
			// Error propagation
			if($this->_dumper->getError())
			{
				$this->setError($this->_dumper->getError());
				return false;
			}
		}

		// Try to step the instance
		$retArray = $this->_dumper->tick();
		// Error propagation
		if($this->_dumper->getError())
		{
			$this->setError($this->_dumper->getError());
			return false;
		}

		$this->_Step = $retArray['Step'];
		$this->_Substep = $retArray['Substep'];

		// Check if the instance has finished
		if(!$retArray['HasRun'])
		{
			// The instance has finished; go to the next entry in the list and dispose the old JoomlapackDumperDefault instance
			$this->_currentListIndex++;
			$this->_dumper = null;
				
			// Are we past the end of the list?
			if($this->_currentListIndex > (count($this->_databaseList)-1) )
			{
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainDBBackup :: No more databases left to iterate");
				$this->setState('postrun');
			}
		}
	}

	/**
	 * Implements the _finalize() abstract method
	 *
	 */
	function _finalize()
	{
		$this->setState('finished');

		// If we are in db backup mode, don't create a databases.ini
		$configuration =& JoomlapackModelRegistry::getInstance();
		$onlydb = ($configuration->get('BackupType') == 'dbonly');

		if ($onlydb) {
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainDBBackup :: Skipping databases.ini");
			return;
		}

		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainDBBackup :: Creating databases.ini");
		// Create a new string
		$databasesINI = '';

		// Loop through databases list
		foreach( $this->_databaseList as $definition )
		{
			// Joomla! core database comes with no parameters; we must retrieve them
			if( $definition['isJoomla'] )
			{
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainDBBackup :: Adding Joomla definition");
				$conf =& JFactory::getConfig();
				$definition['host']     = $conf->getValue('config.host');
				$definition['username'] = $conf->getValue('config.user');
				$definition['password'] = $conf->getValue('config.password');
				$definition['database'] = $conf->getValue('config.db');
				$definition['prefix']   = $conf->getValue('config.dbprefix');
				$definition['dbtype']	= $conf->getValue('config.dbtype');
				$definition['dumpFile'] = 'joomla.sql';
			} else {
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainDBBackup :: Adding extra database definition");
				$definition['prefix'] = '';
			}
				
			$section = basename($definition['dumpFile']);
				
			$databasesINI .= <<<ENDDEF
[$section]
dbname = "{$definition['database']}"
sqlfile = "{$definition['dumpFile']}"
dbhost = "{$definition['host']}"
dbuser = "{$definition['username']}"
dbpass = "{$definition['password']}"
prefix = "{$definition['prefix']}"

ENDDEF;
				
		}

		// BEGIN FIX 1.2 Stable -- databases.ini isn't written on disk
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainDBBackup :: Writing databases.ini contents");
		$cube =& JoomlapackCUBE::getInstance();
		$provisioning =& $cube->getProvisioning();
		$archiver =& $provisioning->getArchiverEngine();
		$virtualLocation = ($configuration->get('BackupType') == 'extradbonly') ? '' : 'installation/sql';
		$archiver->addVirtualFile('databases.ini',$virtualLocation,$databasesINI);

		// On extradbonly mode, we have to finalize the archive as well
		if( $configuration->get('BackupType') == 'extradbonly' )
		{
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Finalizing database dump archive");
			$archiver->finalize();
		}

		// Error propagation
		if($archiver->getError())
		{
			$this->setError($archiver->getError());
			return false;
		}

	}

	/**
	 * Populates _databaseList with the list of databases in the settings
	 *
	 */
	function _getDatabaseList()
	{
		/*
		 * Logic:
		 * Add an entry for the Joomla! database
		 * If we are in DB Only mode, return
		 * Otherwise, itterate the configured databases and add them if and only if all settings are populated
		 */

		// Cleanup the _databaseList array
		$this->_databaseList = array();

		// Add a new record for the core Joomla! database
		$entry = array(
			'isJoomla' => true,
			'useFilters' => true,
			'host' => '',
			'port' => '',
			'username' => '',
			'password' => '',
			'database' => '',
			'dumpFile' => ''
			);

			$this->_databaseList[] = $entry;

			$configuration =& JoomlapackModelRegistry::getInstance();
			$onlydb = ($configuration->get('BackupType') == 'dbonly');

			if ($onlydb) {
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainDBBackup :: Skipping extra databases definitions");
				return;
			}

			// Load multiple database definitions
			jpimport('models.multidb', true);
			$multidbmodel =& JoomlapackModelMultidb::getInstance('multidb','JoomlapackModel');

			$extraDefs = $multidbmodel->getMultiDBList(true);
			if( count($extraDefs) > 0 )
			{
				foreach( $extraDefs as $def )
				{
					$data = unserialize($def->value);
					$entry = array(
					'isJoomla' => false,
					'useFilters' => false,
					'host' => $data['host'],
					'port' => $data['port'],
					'username' => $data['user'],
					'password' => $data['pass'],
					'database' => $data['database'],
					'dumpFile' => $def->id.'-'.$data['database'].'.sql'
					);
					$this->_databaseList[] = $entry;
				}
			}
	}
}