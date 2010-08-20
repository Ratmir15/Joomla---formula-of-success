<?php
/**
 * @package		JoomlaPack
 * @copyright	Copyright (C) 2006-2009 JoomlaPack Developers. All rights reserved.
 * @version		$Id$
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @since		2.4
 *
 * JoomlaPack is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 **/
defined('_JEXEC') or die('Restricted access');

$config =& JoomlapackModelRegistry::getInstance();
define('JPROWSPERSTEP', $config->get('mnRowsPerStep') ); // Default is dumping 100 rows per step

/**
 * A generic MySQL database dump class, using Joomla!'s JDatabase class for handling the connection.
 * Now supports views; merge, in-memory, federated, blackhole, etc tables
 * Configuration parameters:
 * isJoomla		<boolean>	True to use the existing Joomla! DB connection, false to create connection to another db
 * useFilters	<string> 	Should I use db table exclusion filters? Default equals the isJoomla setting above
 * host			<string>	MySQL database server host name or IP address
 * port			<string>	MySQL database server port (optional)
 * username		<string>	MySQL user name, for authentication
 * password		<string>	MySQL password, for authentication
 * database		<string>	MySQL database
 * dumpFile		<string>	Absolute path to dump file; must be writable (optional; if left blank it is automatically calculated)
 */
class JoomlapackDumperMysqlnew extends JoomlapackCUBEParts
{
	// **********************************************************************
	// Configuration parameters
	// **********************************************************************

	/**
	 * True to use the existing Joomla! DB connection, false to create connection to another db
	 *
	 * @var boolean
	 */
	var $_isJoomla = true;

	/**
	 * Should I use db table exclusion filters? Default equals the isJoomla setting above
	 *
	 * @var string
	 */
	var $_useFilters = true;

	/**
	 * MySQL database server host name or IP address
	 *
	 * @var string
	 */
	var $_host = '';

	/**
	 * MySQL database server port (optional)
	 *
	 * @var string
	 */
	var $_port = '';

	/**
	 * MySQL user name, for authentication
	 *
	 * @var string
	 */
	var $_username = '';

	/**
	 * MySQL password, for authentication
	 *
	 * @var string
	 */
	var $_password = '';

	/**
	 * MySQL database
	 *
	 * @var string
	 */
	var $_database = '';

	/**
	 * Absolute path to dump file; must be writable (optional; if left blank it is automatically calculated)
	 *
	 * @var string
	 */
	var $_dumpFile = '';

	// **********************************************************************
	// Private fields
	// **********************************************************************

	/**
	 * Is this a database only backup? Assigned from JoomlapackCUBE settings.
	 * @var boolean
	 */
	var $_DBOnly = false;

	/**
	 * The database exclusion filters, as a simple array
	 * @var array
	 */
	var $_exclusionFilters = array();

	/** @var array Contains the sorted (by dependencies) list of tables/views to backup */
	var $_tables = array();

	/** @var array Contains the configuration data of the tables */
	var $_tables_data = array();

	/** @var array Maps database table names to their abstracted format */
	var $_table_name_map = array();
	
	/** @var array Contains the dependencies of tables and views (temporary) */
	var $_dependencies = array();
	
	/**
	 * Is JoomFish installed? If it is, we have to cope for this and modify our
	 * database calls
	 *
	 * @var boolean
	 */
	var $_hasJoomFish = false;

	/**
	 * Absolute path to the temp file
	 *
	 * @var string
	 */
	var $_tempFile = '';

	/**
	 * Relative path of how the file should be saved in the archive
	 *
	 * @var string
	 */
	var $_saveAsName = '';

	/**
	 * The next table to backup
	 *
	 * @var string
	 */
	var $_nextTable;

	/**
	 * The next row of the table to start backing up from
	 *
	 * @var integer
	 */
	var $_nextRange;

	/**
	 * Current table's row count
	 *
	 * @var integer
	 */
	var $_maxRange;

	/**
	 * Implements the constructor of the class
	 *
	 * @return JoomlapackDumperMysqlnew
	 */
	function JoomlapackDumperMysqlnew()
	{
		$this->_DomainName = "PackDB";
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperMysqlnew :: New instance");
	}

	/**
	 * Implements the _prepare abstract method
	 *
	 */
	function _prepare()
	{
		// Process parameters, passed to us using the setup() public method
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperMysqlnew :: Processing parameters");
		if( is_array($this->_parametersArray) ) {
			$this->_isJoomla = array_key_exists('isJoomla', $this->_parametersArray) ? $this->_parametersArray['isJoomla'] : $this->_isJoomla;
			$this->_useFilters = array_key_exists('isJoomla', $this->_parametersArray) ? $this->_parametersArray['useFilters'] : $this->_isJoomla;
			$this->_host = array_key_exists('host', $this->_parametersArray) ? $this->_parametersArray['host'] : $this->_host;
			$this->_port = array_key_exists('port', $this->_parametersArray) ? $this->_parametersArray['port'] : $this->_port;
			$this->_username = array_key_exists('username', $this->_parametersArray) ? $this->_parametersArray['username'] : $this->_username;
			$this->_password = array_key_exists('password', $this->_parametersArray) ? $this->_parametersArray['password'] : $this->_password;
			$this->_dumpFile = array_key_exists('dumpFile', $this->_parametersArray) ? $this->_parametersArray['dumpFile'] : $this->_dumpFile;
			$this->_database = array_key_exists('database', $this->_parametersArray) ? $this->_parametersArray['database'] : $this->_dumpFile;
		}

		// Get DB backup only mode
		$configuration =& JoomlapackModelRegistry::getInstance();
		$this->_DBOnly = !($configuration->get('BackupType') == 'full');

		// Detect JoomFish
		$this->_hasJoomFish = file_exists(JPATH_SITE . '/administrator/components/com_joomfish/config.joomfish.php');

		// Fetch the database exlusion filters
		$this->_getExclusionFilters();
		if($this->getError()) return;

		// Find tables to be included and put them in the $_tables variable
		$this->_getTablesToBackup();
		if($this->getError()) return;

		// Find where to store the database backup files
		$this->_getBackupFilePaths();

		// Remove any leftovers
		$this->_removeOldFiles();

		// Initialize the algorithm
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperMysqlnew :: Initializing algorithm for first run");
		$this->_nextTable = array_shift( $this->_tables );
		$this->_nextRange = 0;

		// FIX 2.2: First table of extra databases was not being written to disk.
		// This deserved a place in the Bug Fix Hall Of Fame. In subsequent calls to _init, the $fp in
		// _writeline() was not nullified. Therefore, the first dump chunk (that is, the first table's
		// definition and first chunk of its data) were not written to disk. This call causes $fp to be
		// nullified, causing it to be recreated, pointing to the correct file. Holly crap, it took me
		// half an hour to get it!
		$null = null;
		$this->_writeline($null);

		// Finally, mark ourselves "prepared".
		$this->_isPrepared = true;
	}

	/**
	 * Implements the _run() abstract method
	 */
	function _run()
	{
		// Check if we are already done
		if ($this->_getState() == 'postrun') {
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperMysqlnew :: Already finished");
			$this->_Step = "";
			$this->_Substep = "";
			return;
		}

		// Mark ourselves as still running (we will test if we actually do towards the end ;) )
		$this->setState('running');

		// Initialize local variables
		$db =& $this->_getDB();
		if($this->getError()) return;

		if( !is_object($db) || ($db === false) )
		{
			$this->setError(__CLASS__.'::_run() Could not connect to database?!');
			return;
		}

		$outCreate	= ''; // Used for outputting CREATE TABLE commands
		$outData	= ''; // Used for outputting INSERT INTO commands

		$this->_enforceSQLCompatibility(); // Apply MySQL compatibility option
		if($this->getError()) return;

		// Touch SQL dump file
		$nada = "";
		$this->_writeline($nada);

		// Get this table's information
		$tableName = $this->_nextTable;
		$tableAbstract = trim( $this->_table_name_map[$tableName] );
		$dump_records = $this->_tables_data[$tableName]['dump_records'];
		
		// If it is the first run, find number of rows and get the CREATE TABLE command
		if( $this->_nextRange == 0 )
		{
			if($this->getError()) return;
			$outCreate = $this->_tables_data[$tableName]['create'];
			if( $dump_records )
			{
				// We are dumping data from a table, get the row count
				$this->_getRowCount( $tableAbstract );
			}
			else
			{
				// We should not dump any data
				JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Skipping dumping data of " . $this->_nextTable);
				$this->_maxRange = 0;
				$this->_nextRange = 1;
				$outData = '';
				$numRows = 0;
			}
		}
		
		// Ugly hack to make JoomlaPack skip over #__jp_temp
		// @todo Apply this in skip database table contents filters instead of here
		if( $tableAbstract == '#__jp_temp' )
		{
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Skipping contents of table " . $this->_nextTable);
			$this->_nextRange = $this->_maxRange + 1;
			$numRows = 0;
		}

		// Check if we have more work to do on this table
		if( ($this->_nextRange < $this->_maxRange) )
		{
			// Get the number of rows left to dump from the current table
			$sql = "select * from `$tableAbstract`";
			$db->setQuery( $sql, $this->_nextRange, JPROWSPERSTEP );
			$dataDump = $db->loadAssocList();
			$numTheseRows = count($dataDump);
			if($numTheseRows == 0)
			{
				JoomlapackLogger::WriteLog(_JP_LOG_ERROR, "Error while dumping " . $this->_nextTable . "; no records were returned!");
			}
			else
			{
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Dumping $numTheseRows rows of " . $this->_nextTable);
			}
				
			// Only dump if we have more than 0 rows to dump
			if ($numTheseRows > 0)
			{
				$numRows = 0;
				foreach( $dataDump as $myRow ) {
					$numRows++;
					$numOfFields = count( $myRow );
						
					if( $numOfFields > 0 ) $outData .= "INSERT INTO `" . ($this->_DBOnly ? $tableName : $tableAbstract) . "` VALUES (";
						
					// Step through each of the row's values
					$fieldID = 0;
						
					// Used in running backup fix
					$isCurrentBackupEntry = false;

					// Fix 1.2a - NULL values were being skipped
					if( $numOfFields > 0 ) foreach( $myRow as $value )
					{
						// The ID of the field, used to determine placement of commas
						$fieldID++;

						// Fix 2.0: Mark currently running backup as succesfull in the DB snapshot
						if($tableAbstract == '#__jp_stats')
						{
							if($fieldID == 1)
							{
								// Compare the ID to the currently running
								$cube =& JoomlapackCUBE::getInstance();
								$isCurrentBackupEntry = ($cube->_statID == $value);
							}
							elseif ($fieldID == 6)
							{
								// Treat the status field
								$value = $isCurrentBackupEntry ? 'complete' : $value;
							}
						}

						// Post-process the value
						if( is_null($value) )
						{
							$outData .= "NULL"; // Cope with null values
						} else {
							// Accomodate if runtime magic quotes are present
							$value = get_magic_quotes_runtime() ? stripslashes( $value ) : $value;
							$outData .= $db->Quote($value);
						}
						if( $fieldID < $numOfFields ) $outData .= ', ';
					} // foreach
					if( $numOfFields ) $outData .=");\n";
				} // for (all rows left)
			} // if numRows > 0...
				
			// Advance the _nextRange pointer
			$this->_nextRange += ($numRows != 0) ? $numRows : 1;
				
			$this->_Step = $tableName;
			$this->_Substep = $this->_nextRange . ' / ' . $this->_maxRange;
		} // if more work on the table
		else
		{
			// Tell the user we are done with the table
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Done with " . $this->_nextTable);
			
			if(count($this->_tables) == 0)
			{
				// We have finished dumping the database!
				JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Database has been successfully dumped to SQL file(s)");
				$this->setState('postrun');
				$this->_Step = '';
				$this->_Substep = '';
				$this->_nextTable = '';
				$this->_nextRange = 0;
			} elseif(count($this->_tables) != 0) {
				// Switch tables
				$this->_nextTable = array_shift( $this->_tables );
				$this->_nextRange = 0;
				$this->_Step = $this->_nextTable;
				$this->_Substep = '';
			}
		}

		$this->_writeDump( $outCreate, $outData, $tableAbstract );
		if($this->getError()) return;
		$null = null;
		$this->_writeline($null);
	}

	/**
	 * Implements the _finalize() abstract method
	 *
	 */
	function _finalize()
	{
		// Add Extension Filter SQL statements (if any), only for the MAIN DATABASE!!!
		if($this->_isJoomla)
		{
			jpimport('models.extfilter',true);
			$extModel = new JoomlapackModelExtfilter;
			$extraSQL =& $extModel->getExtraSQL();
			$this->_writeline($extraSQL);
			unset($extraSQL);
			unset($extModel);
		}

		// If we are not just doing a main db only backup, add the SQL file to the archive
		$configuration =& JoomlapackModelRegistry::getInstance();
		if( $configuration->get('BackupType') != 'dbonly' )
		{
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Adding the SQL dump to the archive");
				
			$cube =& JoomlapackCUBE::getInstance();
			$provisioning =& $cube->getProvisioning();
			$archiver =& $provisioning->getArchiverEngine();
			$archiver->addFileRenamed( $this->_tempFile, $this->_saveAsName );
			unset($archiver);
			if($this->getError()) return;
				
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Removing temporary file");
			JoomlapackCUBETempfiles::unregisterAndDeleteTempFile( $this->_tempFile, true );
			if($this->getError()) return;
		}
		$this->_isFinished = true;
	}

	/**
	 * Gets the database exclusion filters through the Filter Manager class
	 */
	function _getExclusionFilters()
	{
		if( $this->_useFilters )
		{
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperMysqlnew :: Retrieving db exclusion filters");
			jpimport('core.utility.filtermanager');
			$filterManager = new JoomlapackCUBEFiltermanager();
			if(!is_object($filterManager))
			{
				$this->setError(__CLASS__.'::_getExclusionFilters() FilterManager is not an object');
				return false;
			}
			$filterManager->init();
			$this->_exclusionFilters = $filterManager->getFilters('database');
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperMysqlnew :: Retrieved db exclusion filters, OK.");
		} else {
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperMysqlnew :: Skipping filters");
			$this->_exclusionFilters = array();
		}
	}

	/**
	 * Find where to store the backup files
	 */
	function _getBackupFilePaths()
	{
		$configuration =& JoomlapackModelRegistry::getInstance();

		switch($configuration->get('BackupType'))
		{
			case 'dbonly':
				// On DB Only backups we use different naming, no matter what's the setting
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperMysqlnew :: Only dump database mode detected");
				// Fix 2.0: Backup file name MUST be taken from the statitics record!
				$cube =& JoomlapackCUBE::getInstance();
				$statID = $cube->_statID;
				$statModel = new JoomlapackModelStatistics($statID);
				$statModel->setId($statID);
				$statRecord =& $statModel->getStatistic();
				$this->_tempFile = $statRecord->absolute_path;
				$this->_saveAsName = '';
				break;

			case 'full':
				if( $this->_dumpFile != '' )
				{
					JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperMysqlnew :: Forced filename using dumpFile found.");
					// If the dumpFile was set, forcibly use this value
					$this->_tempFile = JoomlapackCUBETempfiles::registerTempFile( dechex(crc32(microtime().$this->_dumpFile)) );
					$this->_saveAsName = 'installation/sql/'.$this->_dumpFile;
				} else {
					if( $this->_isJoomla )
					{
						// Joomla! Core Database, use the JoomlaPack way of figuring out the filenames
						JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperMysqlnew :: Core database");
						$this->_tempFile = JoomlapackCUBETempfiles::registerTempFile( dechex(crc32(microtime().'joomla.sql')) );
						$this->_saveAsName = 'installation/sql/joomla.sql';
					} else {
						// External databases, we use the database's name
						JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperMysqlnew :: External database");
						$this->_tempFile = JoomlapackCUBETempfiles::registerTempFile( dechex(crc32(microtime().$this->_database.'.sql')) );
						$this->_saveAsName = 'installation/sql/'.$this->_database.'.sql';
					}
				}
				break;

			case 'extradbonly':
				if( $this->_dumpFile != '' )
				{
					JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperMysqlnew :: Forced filename using dumpFile found.");
					// If the dumpFile was set, forcibly use this value
					$this->_tempFile = JoomlapackCUBETempfiles::registerTempFile( dechex(crc32(microtime().$this->_dumpFile)) );
					$this->_saveAsName = $this->_dumpFile;
				} else {
					if( $this->_isJoomla )
					{
						// Joomla! Core Database, use the JoomlaPack way of figuring out the filenames
						JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperMysqlnew :: Core database");
						$this->_tempFile = JoomlapackCUBETempfiles::registerTempFile( dechex(crc32(microtime().'joomla.sql')) );
						$this->_saveAsName = 'joomla.sql';
					} else {
						// External databases, we use the database's name
						JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperMysqlnew :: External database");
						$this->_tempFile = JoomlapackCUBETempfiles::registerTempFile( dechex(crc32(microtime().$this->_database.'.sql')) );
						$this->_saveAsName = $this->_database.'.sql';
					}
				}
				break;
		}

		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDomainDBBackup :: SQL temp file is " . $this->_tempFile);
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDomainDBBackup :: SQL file location in archive is " . $this->_saveAsName);
	}

	/**
	 * Deletes any leftover files from previous backup attempts
	 *
	 */
	function _removeOldFiles()
	{
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDomainDBBackup :: Deleting leftover files, if any");
		if( file_exists( $this->_tempFile ) ) @unlink( $this->_tempFile );
	}

	/**
	 * Applies the SQL compatibility setting
	 */
	function _enforceSQLCompatibility()
	{
		$configuration =& JoomlapackModelRegistry::getInstance();
		$db =& $this->_getDB();
		if($this->getError()) return;

		switch( $configuration->get('MySQLCompat') )
		{
			case 'compat':
				$sql = "SET SESSION sql_mode='HIGH_NOT_PRECEDENCE,NO_TABLE_OPTIONS'";
				break;
					
			default:
				$sql = "SET SESSION sql_mode=''";
				break;
		}

		$db->setQuery( $sql );
		$db->query();
	}

	/**
	 * Returns a table's abstract name (replacing the prefix with the magic #__ string)
	 *
	 * @param string $tableName The canonical name, e.g. 'jos_content'
	 * @return string The abstract name, e.g. '#__content'
	 */
	function _getAbstract( $tableName )
	{
		// FIX 2.0.b1 - Don't return abstract names for non-CMS tables
		if(!$this->_isJoomla) return $tableName;

		// FIX 1.2 Stable - Handle (very rare) cases with an empty db prefix
		$jregistry =& JFactory::getConfig();
		$prefix = $jregistry->getValue('config.dbprefix');

		switch( $prefix )
		{
			case '':
				// This is more of a hack; it assumes all tables are Joomla! tables if the prefix is empty.
				return '#__' . $tableName;
				break;

			default:
				// Normal behaviour for 99% of sites
				// Fix 2.4 : Abstracting the prefix only if it's found in the beginning of the table name
				$tableAbstract = $tableName;
				if(!empty($prefix)) {
					if( substr($tableName, 0, strlen($prefix)) == $prefix ) {
						$tableAbstract = '#__' . substr($tableName, strlen($prefix));
					} else {
						// FIX 2.4: If there is no prefix, it's a non-Joomla! table.
						$tableAbstract = $tableName;
					}
				}
				
				return $tableAbstract;
				break;
		}
	}

	/**
	 * Gets the row count for table $tableAbstract. Also updates the $this->_maxRange variable.
	 *
	 * @param string $tableAbstract The abstract name of the table (works with canonical names too, though)
	 * @return integer Row count of the table
	 */
	function _getRowCount( $tableAbstract )
	{
		$db =& $this->_getDB();
		if($this->getError()) return;

		$sql = "SELECT COUNT(*) FROM `$tableAbstract`";
		$db->setQuery( $sql );
		$this->_maxRange = $this->_hasJoomFish ? $db->loadResult(false) : $db->loadResult();
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Rows on " . $this->_nextTable . " : " . $this->_maxRange);

		return $this->_maxRange;
	}

	/**
	 * Writes the SQL dump into the output files. If it fails, it sets the error
	 *
	 * @param string $outCreate Any CREATE TABLE / DROP TABLE commands
	 * @param string $outData Any INSERT INTO commands
	 * @param string $tableAbstract The current table's abstract name
	 * @return boolean TRUE on successful write, FALSE otherwise
	 */
	function _writeDump( &$outCreate, &$outData, $tableAbstract )
	{
		$result = ($outCreate != '') ? $this->_writeline( $outCreate ) : true;
		if( !$result )
		{
			$errorMessage = 'Writing to ' . $this->_tempFile . ' has failed. Check permissions!';
			$this->setError($errorMessage);
			return false;
		}

		$result = ($outData != '') ? $this->_writeline( $outData ) : true;
		if( !$result )
		{
			$errorMessage = 'Writing to ' . $this->_tempFile . ' has failed. Check permissions!';
			$this->setError($errorMessage);
			return false;
		}

		return true;
	}

	/**
	 * Saves the string in $fileData to the file $backupfile. Returns TRUE. If saving
	 * failed, return value is FALSE.
	 * @param string $fileData Data to write. Set to null to close the file handle.
	 * @return boolean TRUE is saving to the file succeeded
	 */
	function _writeline(&$fileData) {
		static $fp;

		if(!$fp)
		{
			$fp = @fopen($this->_tempFile, 'a');
			if($fp === false)
			{
				$this->setError('Could not open '.$this->_tempFile.' for append, in DB dump.');
				return;
			}
		}

		if(is_null($fileData))
		{
			if($fp) @fclose($fp);
			$fp = null;
			return true;
		}
		else
		{
			if ($fp) {
				fwrite($fp, $fileData);
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * Return an instance of JDatabase
	 *
	 * @return JDatabase
	 */
	function &_getDB()
	{
		if( $this->_isJoomla )
		{
			// Core Joomla! database, get the existing instance
			$db =& JFactory::getDBO();
			return $db;
		}
		else
		{
			// Joomla! 1.5.x
			jimport('joomla.database.database');
			jimport('joomla.database.table');
				
			$conf =& JFactory::getConfig();
				
			$host 		= $this->_host . ($this->_port != '' ? ':' . $this->_port : '');
			$user 		= $this->_username;
			$password 	= $this->_password;
			$database	= $this->_database;
				
			$prefix 	= '';
			$driver 	= $conf->getValue('config.dbtype');
			$debug 		= $conf->getValue('config.debug');
				
			$options	= array ( 'driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'prefix' => $prefix );
				
			$db =& JDatabase::getInstance( $options );
				
			if ( JError::isError($db) ) {
				$errorMessage = "JoomlapackDumperMysqlnew :: Database Error:" . $db->toString();
				$this->setError($errorMessage);
				return false;
			}
				
			if ($db->getErrorNum() > 0) {
				$errorMessage = 'JDatabase::getInstance: Could not connect to database <br/>' . 'joomla.library:'.$db->getErrorNum().' - '.$db->getErrorMsg();
				$this->setError($errorMessage);
				return false;
			}
				
			$db->debug( $debug );
			return $db;
		}
	}

// =============================================================================
// Dependency processing - the Twilight Zone starts here
// =============================================================================

	/**
	 * Scans the database for tables to be backed up and sorts them according to
	 * their dependencies on one another.
	 */
	function _getTablesToBackup()
	{
		// First, get a map of table names <--> abstract names
		$this->_get_tables_mapping();
		if($this->getError()) return;
		
		// Find the type and CREATE command of each table/view in the database
		$this->_get_tables_data();
		if($this->getError()) return;
		
		// Process dependencies and rearrange tables respecting them
		$this->_process_dependencies();
		if($this->getError()) return;
		
		// Remove dependencies array
		$this->_dependencies = array();
		
		// Drop filters in order to conserve memory and storage space
		$this->_exclusionFilters = array();
	}
	
	/**
	 * Generates a mapping between table names as they're stored in the database
	 * and their abstract representation.
	 */
	function _get_tables_mapping()
	{
		// Get a database connection
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperMysqlnew :: Finding tables to include in the backup set");
		$db =& $this->_getDB();
		if($this->getError()) return;

		// Reset internal tables
		$this->_table_name_map = array();

		// Get the list of all database tables
		$sql = "SHOW TABLES";
		$db->setQuery( $sql );
		$all_tables = $db->loadResultArray();
		
		// If we have filters, make sure the tables pass the filtering
		foreach( $all_tables as $table_name )
		{
			$table_abstract = $this->_getAbstract($table_name);
			if(!(substr($table_abstract,0,4) == 'bak_')) // Skip backup tables
			{
				// Apply exclusion filters if set
				if( count($this->_exclusionFilters) > 0 ) {
					if( !in_array( $table_abstract, $this->_exclusionFilters ) ) $this->_table_name_map[$table_name] = $table_abstract;
				} else {
					$this->_table_name_map[$table_name] = $table_abstract;
				}
			}
		}
		
		// If we have MySQL > 5.0 add the list of stored procedures, stored functions
		// and triggers
		$verParts = explode( '.', $db->getVersion() );
		if ($verParts[0] == 5)
		{
			
			// Cache the database name if this is the main site's database
			if($this->_isJoomla)
			{
				$jconfig =& JFactory::getConfig();
				$this->_database = $jconfig->getValue('config.db');
			}

			// 1. Stored procedures
			$sql = "SHOW PROCEDURE STATUS WHERE `Db`=".$db->Quote($this->_database);			
			$db->setQuery( $sql );
			$all_entries = $db->loadResultArray(1);
			// If we have filters, make sure the tables pass the filtering
			if(is_array($all_entries))
			if(count($all_entries))
			foreach( $all_entries as $entity_name )
			{
				$entity_abstract = $this->_getAbstract($entity_name);
				if(!(substr($entity_abstract,0,4) == 'bak_')) // Skip backup entities
				{
					// Apply exclusion filters if set
					if( count($this->_exclusionFilters) > 0 ) {
						if( !in_array( $entity_abstract, $this->_exclusionFilters ) ) $this->_table_name_map[$entity_name] = $entity_abstract;
					} else {
						$this->_table_name_map[$entity_name] = $entity_abstract;
					}
				}
			}

			// 2. Stored functions
			$sql = "SHOW FUNCTION STATUS WHERE `Db`=".$db->Quote($this->_database);			
			$db->setQuery( $sql );
			$all_entries = $db->loadResultArray(1);
			// If we have filters, make sure the tables pass the filtering
			if(is_array($all_entries))
			if(count($all_entries))
			foreach( $all_entries as $entity_name )
			{
				$entity_abstract = $this->_getAbstract($entity_name);
				if(!(substr($entity_abstract,0,4) == 'bak_')) // Skip backup entities
				{
					// Apply exclusion filters if set
					if( count($this->_exclusionFilters) > 0 ) {
						if( !in_array( $entity_abstract, $this->_exclusionFilters ) ) $this->_table_name_map[$entity_name] = $entity_abstract;
					} else {
						$this->_table_name_map[$entity_name] = $entity_abstract;
					}
				}
			}

			// 3. Triggers
			$sql = "SHOW TRIGGERS";			
			$db->setQuery( $sql );
			$all_entries = $db->loadResultArray();
			// If we have filters, make sure the tables pass the filtering
			if(is_array($all_entries))
			if(count($all_entries))
			foreach( $all_entries as $entity_name )
			{
				$entity_abstract = $this->_getAbstract($entity_name);
				if(!(substr($entity_abstract,0,4) == 'bak_')) // Skip backup entities
				{
					// Apply exclusion filters if set
					if( count($this->_exclusionFilters) > 0 ) {
						if( !in_array( $entity_abstract, $this->_exclusionFilters ) ) $this->_table_name_map[$entity_name] = $entity_abstract;
					} else {
						$this->_table_name_map[$entity_name] = $entity_abstract;
					}
				}
			}
			
		} // if MySQL 5
	}
	
	/**
	 * Populates the _tables array with the metadata of each table and generates
	 * dependency information for views and merge tables
	 */
	function _get_tables_data()
	{
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperMysqlnew :: Starting CREATE TABLE and dependency scanning");

		// Get a database connection
		$db =& $this->_getDB();
		if($this->getError()) return;

		// Reset internal tables
		$this->_tables_data = array();
		$this->_dependencies = array();
		
		// Get a list of tables where their engine type is shown
		$sql = 'SHOW TABLE STATUS';
		$db->setQuery( $sql );
		$metadata_list = $db->loadRowList();
		
		foreach($metadata_list as $table_metadata)
		{
			// Skip over tables not included in the backup set
			if(!array_key_exists($table_metadata[0], $this->_table_name_map)) continue;

			// Basic information
			$table_name = $table_metadata[0];
			$table_abstract = $this->_table_name_map[$table_metadata[0]];
			$new_entry = array(
				'type'			=> 'table',
				'dump_records'	=> true
			);
			
			switch($table_metadata[1])
			{
				// Views
				case null:
					$new_entry['type'] = 'view';
					$new_entry['dump_records'] = false;
					break;
				
				// Merge tables
				case 'MRG_MYISAM':
					$new_entry['type'] = 'merge';
					$new_entry['dump_records'] = false;
					break;
				
				// Tables whose data we do not back up (memory, federated and can-have-no-data tables)
				case 'MEMORY':
				case 'EXAMPLE':
				case 'BLACKHOLE':
				case 'FEDERATED':
					$new_entry['dump_records'] = false;
					break;
				
				// Normal tables
				default:
					// @todo Table Data Filter - set dump_records to FALSE if array belongs to those filters
					break;
			} // switch
			
			$dependencies = array();
			$new_entry['create'] = $this->_get_create($table_abstract, $table_name, $new_entry['type'], $dependencies);
			$new_entry['dependencies'] = $dependencies;
			$this->_tables_data[$table_name] = $new_entry;
		} // foreach

		// If we have MySQL > 5.0 add stored procedures, stored functions and triggers
		$verParts = explode( '.', $db->getVersion() );
		if ($verParts[0] == 5)
		{
			// Get a list of procedures
			$sql = 'SHOW PROCEDURE STATUS WHERE `Db`='.$db->Quote($this->_database);
			$db->setQuery( $sql );
			$metadata_list = $db->loadRowList();
			
			if(is_array($metadata_list))
			if(count($metadata_list))
			foreach($metadata_list as $entity_metadata)
			{
				// Skip over entities not included in the backup set
				if(!array_key_exists($entity_metadata[1], $this->_table_name_map)) continue;
	
				// Basic information
				$entity_name = $entity_metadata[1];
				$entity_abstract = $this->_table_name_map[$entity_metadata[1]];
				$new_entry = array(
					'type'			=> 'procedure',
					'dump_records'	=> false
				);
				
				// There's no point trying to add a non-procedure entity
				if($entity_metadata[2] != 'PROCEDURE') continue;
				
				$dependencies = array();
				$new_entry['create'] = $this->_get_create($entity_abstract, $entity_name, $new_entry['type'], $dependencies);
				$new_entry['dependencies'] = $dependencies;
				$this->_tables_data[$entity_name] = $new_entry;
			} // foreach
			
			// Get a list of functions
			$sql = 'SHOW FUNCTION STATUS WHERE `Db`='.$db->Quote($this->_database);
			$db->setQuery( $sql );
			$metadata_list = $db->loadRowList();
			
			if(is_array($metadata_list))
			if(count($metadata_list))
			foreach($metadata_list as $entity_metadata)
			{
				// Skip over entities not included in the backup set
				if(!array_key_exists($entity_metadata[1], $this->_table_name_map)) continue;
	
				// Basic information
				$entity_name = $entity_metadata[1];
				$entity_abstract = $this->_table_name_map[$entity_metadata[1]];
				$new_entry = array(
					'type'			=> 'function',
					'dump_records'	=> false
				);
				
				// There's no point trying to add a non-function entity
				if($entity_metadata[2] != 'FUNCTION') continue;
				
				$dependencies = array();
				$new_entry['create'] = $this->_get_create($entity_abstract, $entity_name, $new_entry['type'], $dependencies);
				$new_entry['dependencies'] = $dependencies;
				$this->_tables_data[$entity_name] = $new_entry;
			} // foreach
						
			// Get a list of triggers
			$sql = 'SHOW TRIGGERS';
			$db->setQuery( $sql );
			$metadata_list = $db->loadRowList();
			
			if(is_array($metadata_list))
			if(count($metadata_list))
			foreach($metadata_list as $entity_metadata)
			{
				// Skip over entities not included in the backup set
				if(!array_key_exists($entity_metadata[0], $this->_table_name_map)) continue;
	
				// Basic information
				$entity_name = $entity_metadata[0];
				$entity_abstract = $this->_table_name_map[$entity_metadata[0]];
				$new_entry = array(
					'type'			=> 'trigger',
					'dump_records'	=> false
				);
				
				$dependencies = array();
				$new_entry['create'] = $this->_get_create($entity_abstract, $entity_name, $new_entry['type'], $dependencies);
				$new_entry['dependencies'] = $dependencies;
				$this->_tables_data[$entity_name] = $new_entry;
			} // foreach
		}
		
		// Only store unique values
		if(count($dependencies) > 0)
			$dependencies = array_unique($dependencies);
	}
	
	/**
	 * Gets the CREATE TABLE command for a given table/view/procedure/function/trigger
	 * @return string The CREATE command, w/out newlines
	 */
	function _get_create( $table_abstract, $table_name, $type, &$dependencies )
	{
		$db =& $this->_getDB();
		if($this->getError()) return;

		switch($type)
		{
			case 'table':
			case 'merge':
			case 'view':
				$sql = "SHOW CREATE TABLE `$table_abstract`";
				break;
				
			case 'procedure':
				$sql = "SHOW CREATE PROCEDURE `$table_abstract`";
				break;

			case 'function':
				$sql = "SHOW CREATE FUNCTION `$table_abstract`";
				break;

			case 'trigger':
				$sql = "SHOW CREATE TRIGGER `$table_abstract`";
				break;
		}
		
		$db->setQuery( $sql );
		$temp = $db->loadRowList();
		if( in_array($type, array('procedure','function','trigger')) )
		{
			$table_sql = $temp[0][2];
		}
		else
		{
			$table_sql = $temp[0][1];
		}
		unset( $temp );

		// Replace table name and names of referenced tables with their abstracted
		// forms and populate dependency tables at the same time
		
		// On DB only backup we don't want any replacing to take place, do we?
		if($this->_DBOnly) $old_table_sql = $table_sql;

		// Even on simple tables, we may have foreign key references.
		// As a result, we need to replace those referenced table names
		// as well. On views and merge arrays, we have referenced tables
		// by definition. 
		$dependencies = array();
		// First, the table/view/merge table name itself:
		$table_sql = str_replace( $table_name , $table_abstract, $table_sql );
		// Now, loop for all table entries
		foreach($this->_table_name_map as $ref_normal => $ref_abstract)
		{
			if( $pos = strpos($table_sql, "`$ref_normal`") )
			{
				// Add a reference hit
				$this->_dependencies[$ref_normal][] = $table_name;
				// Add the dependency to this table's metadata
				$dependencies[] = $ref_normal;
				// Do the replacement
				$table_sql = str_replace("`$ref_normal`", "`$ref_abstract`", $table_sql);
			}
		}

		// On DB only backup we don't want any replacing to take place, do we?
		if($this->_DBOnly) $table_sql = $old_table_sql;

		// Replace newlines with spaces
		$table_sql = str_replace( "\n", " ", $table_sql ) . ";\n";
		$table_sql = str_replace( "\r", " ", $table_sql );
		$table_sql = str_replace( "\t", " ", $table_sql );
		
		// Post-process CREATE VIEW
		if($type == 'view')
		{
			$pos_view = strpos($table_sql, ' VIEW ');
			
			if($pos_view > 7 )
			{
				// Only post process if there are view properties between the CREATE and VIEW keywords
				$propstring = substr($table_sql, 7, $pos_view - 7); // Properties string
				// Fetch the ALGORITHM={UNDEFINED | MERGE | TEMPTABLE} keyword
				$algostring = '';
				$algo_start = strpos($propstring, 'ALGORITHM=');
				if($algo_start !== false)
				{
					$algo_end = strpos($propstring, ' ', $algo_start);
					$algostring = substr($propstring, $algo_start, $algo_end - $algo_start + 1);
				}
				// Create our modified create statement
				$table_sql = 'CREATE OR REPLACE '.$algostring.substr($table_sql, $pos_view);
			}
		}
		elseif($type == 'procedure')
		{
			$pos_entity = strpos($table_sql, ' PROCEDURE ');
			$table_sql = 'CREATE'.substr($table_sql, $pos_entity);
		}
		elseif($type == 'function')
		{
			$pos_entity = strpos($table_sql, ' FUNCTION ');
			$table_sql = 'CREATE'.substr($table_sql, $pos_entity);
		}
		elseif($type == 'trigger')
		{
			$pos_entity = strpos($table_sql, ' TRIGGER ');
			$table_sql = 'CREATE'.substr($table_sql, $pos_entity);
		}

		// Add DROP statements for DB only backup
		if( $this->_DBOnly )
		{
			if( ($type == 'table') || ($type == 'merge') )
			{
				// Table or merge tables, get a DROP TABLE statement
				$drop = "DROP TABLE IF EXISTS `$table_name`;\n";
			}
			elseif($type == 'view')
			{
				// Views get a DROP VIEW statement
				$drop = "DROP VIEW IF EXISTS `$table_name`;\n";
			}
			elseif($type == 'procedure')
			{
				// Procedures get a DROP PROCEDURE statement and proper delimiter strings
				$drop = "DROP PROCEDURE IF EXISTS `$table_name`;\n";
				$drop .= "DELIMITER // ";
				$table_sql = str_replace( "\r", " ", $table_sql );
				$table_sql = str_replace( "\t", " ", $table_sql );
				$table_sql = rtrim($table_sql,";\n")." // DELIMITER ;\n";
			}
			elseif($type == 'function')
			{
				// Procedures get a DROP FUNCTION statement and proper delimiter strings
				$drop = "DROP FUNCTION IF EXISTS `$table_name`;\n";
				$drop .= "DELIMITER // ";
				$table_sql = str_replace( "\r", " ", $table_sql );
				$table_sql = rtrim($table_sql,";\n")."// DELIMITER ;\n";
			}
			elseif($type == 'trigger')
			{
				// Procedures get a DROP TRIGGER statement and proper delimiter strings
				$drop = "DROP TRIGGER IF EXISTS `$table_name`;\n";
				$drop .= "DELIMITER // ";
				$table_sql = str_replace( "\r", " ", $table_sql );
				$table_sql = str_replace( "\t", " ", $table_sql );
				$table_sql = rtrim($table_sql,";\n")."// DELIMITER ;\n";
			}
			$table_sql = $drop . $table_sql;
		}

		return $table_sql;
	}
	
	function _process_dependencies()
	{
		if(count($this->_table_name_map) > 0)
			foreach($this->_table_name_map as $table_name => $table_abstract)
				$this->_push_table($table_name);
	}
	
	/**
	 * Pushes a table in the _tables stack, making sure it will appear after
	 * its dependencies and other tables/views depending on it will eventually
	 * appear after it. It's a complicated chicken-and-egg problem. Just make
	 * sure you don't have any bloody circular references!!
	 * @param string $table_name Canonical name of the table to push
	 * @param array $stack When called recursive, other views/tables previously processed in order to detect *ahem* dependency loops...
	 */
	function _push_table($table_name, $stack = array())
	{
		// Load information
		$table_data = $this->_tables_data[$table_name];
		$table_abstract = $this->_table_name_map[$table_name]; 
		$referenced = $table_data['dependencies'];
		unset($table_data);
		
		// Try to find the minimum insert position, so as to appear after the last referenced table
		$insertpos = false;
		foreach($referenced as $referenced_table)
		{
			if(count($this->_tables))
			{
				$newpos = array_search($referenced_table, $this->_tables);
				if($newpos !== false) {
					if($insertpos === false)
					{
						$insertpos = $newpos;
					}
					else
					{
						$insertpos = max($insertpos, $newpos);
					}
				}
			}
		}
		
		// Add to the _tables array
		if(count($this->_tables) && !($insertpos === false)) {
			array_splice($this->_tables, $insertpos+1, 0, $table_name);
		}
		else
		{
			$this->_tables[] = $table_name;
		}
		
		// Here's what... Some other table/view might depend on us, so we must appear
		// before it (actually, it must appear after us). So, we scan for such
		// tables/views and relocate them
		if(count($this->_dependencies))
		{
			if(array_key_exists($table_name, $this->_dependencies))
			{
				foreach($this->_dependencies[$table_name] as $depended_table)
				{
					// First, make sure that either there is no stack, or the
					// depended table doesn't belong it. In any other case, we
					// were fooled to follow an endless dependency loop and we
					// will simply bail out and let the user sort things out.
					if(count($stack) > 0)
						if(in_array($depended_table, $stack)) continue;
					
					$my_position = array_search($table_name, $this->_tables);
					$remove_position = array_search($depended_table, $this->_tables);
					if( ($remove_position !== false) && ($remove_position < $my_position) )
					{
						$stack[] = $table_name;
						array_splice($this->_tables, $remove_position, 1);
						
						// Where should I put the other table/view now? Don't tell me. 
						// I have to recurse...
						$this->_push_table($depended_table);
					} // if remove_position
				} // foreach
			} // if in dependencies
		} // if there are dependencies		
	}

}