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

$configuration =& JoomlapackModelRegistry::getInstance();
define('DATA_CHUNK_LENGTH',	$configuration->get('mnMSDDataChunk') );		// How many bytes to read per step
define('MAX_QUERY_LINES',	$configuration->get('mnMSDMaxQueryLines'));		// How many lines may be considered to be one query (except text lines)
define('LINESPERSESSION',	$configuration->get('mnMSDLinesPerSession'));	// Lines to be parsed per step
define('MYSQLDUMPPATH',		$configuration->get('mysqldumpPath'));			// Absolute path to mysqldump

/**
 * Hybrid database dumper, using mysqldump to produce the raw data dump and PHP functions
 * to post-process it in order to comply to the user's choices and to the requirements of
 * the JoomlaPack Installer.
 */
class JoomlapackDumperMysqldump extends JoomlapackCUBEParts
{
	/**
	 * True to turn table names into abstract form (e.g. #__content instead of jos_content)
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
	 *
	 * @var boolean
	 */
	var $_DBOnly = false;

	/**
	 * The database exclusion filters, as a simple array
	 *
	 * @var array
	 */
	var $_exclusionFilters = array();

	/**
	 * Absolute path to the temp file
	 *
	 * @var string
	 */
	var $_tempFile = '';

	/**
	 * Temporary mysqldump file
	 *
	 * @var string
	 */
	var $_tempMSDfile = '';

	/**
	 * Relative path of how the file should be saved in the archive
	 *
	 * @var string
	 */
	var $_saveAsName = '';

	/**
	 * Set to true after mysqldump has ran, letting us post-process its file
	 *
	 * @var unknown_type
	 */
	var $_mysqldumpHasRan = false;

	/**
	 * Implements the constructor of the class
	 *
	 * @return JoomlapackDumperMysqldump
	 */

	/**
	 * SQL parser start
	 *
	 * @var integer
	 */
	var $_start = 0;

	/**
	 * SQL parser file offset
	 *
	 * @var integer
	 */
	var $_foffset = 0;

	function JoomlapackDumperMysqldump()
	{
		$this->_DomainName = "PackDB";
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, __CLASS__." :: New instance");
	}

	/**
	 * Implements the _prepare abstract method
	 *
	 */
	function _prepare()
	{
		// Process parameters, passed to us using the setup() public method
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, __CLASS__." :: Processing parameters");
		if( is_array($this->_parametersArray) ) {
			$this->_isJoomla = array_key_exists('isJoomla', $this->_parametersArray) ? $this->_parametersArray['isJoomla'] : $this->_isJoomla;
			$this->_useFilters = array_key_exists('useFilters', $this->_parametersArray) ? $this->_parametersArray['useFilters'] : $this->_isJoomla;
			$this->_host = array_key_exists('host', $this->_parametersArray) ? $this->_parametersArray['host'] : $this->_host;
			$this->_port = array_key_exists('port', $this->_parametersArray) ? $this->_parametersArray['port'] : $this->_port;
			$this->_username = array_key_exists('username', $this->_parametersArray) ? $this->_parametersArray['username'] : $this->_username;
			$this->_password = array_key_exists('password', $this->_parametersArray) ? $this->_parametersArray['password'] : $this->_password;
			$this->_dumpFile = array_key_exists('dumpFile', $this->_parametersArray) ? $this->_parametersArray['dumpFile'] : $this->_dumpFile;
			$this->_database = array_key_exists('database', $this->_parametersArray) ? $this->_parametersArray['database'] : $this->_database;
		}

		// If we have the Joomla! core database, connection and authentication parameters
		// are not passed along. We have to fix this!
		if($this->_isJoomla)
		{
			$conf =& JFactory::getConfig();
				
			$this->_host		= $conf->getValue('config.host');
			$this->_username	= $conf->getValue('config.user');
			$this->_password	= $conf->getValue('config.password');
			$this->_database	= $conf->getValue('config.db');
		}

		// Get DB backup only mode
		// Get DB backup only mode
		$configuration =& JoomlapackModelRegistry::getInstance();
		$this->_DBOnly = !($configuration->get('BackupType') == 'full');

		// If we are in DB only mode, we reset _isJoomla to false so as not to substitute
		// table names with their abstract form
		if($this->_DBOnly) $this->_isJoomla = false;

		// Fetch the database exlusion filters
		$this->_getExclusionFilters();
		if($this->getError()) return;

		// Find where to store the database backup files
		$this->_getBackupFilePaths();

		// Remove any leftovers
		$this->_removeOldFiles();

		// Initialize the algorithm
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, __CLASS__." :: Initializing algorithm for first run");

		$this->setState('prepared');
	}

	/**
	 * Implements the _run() abstract method
	 */
	function _run()
	{
		// Check if we are already done
		if ($this->_getState() == 'postrun') {
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, __CLASS__." :: Already finished");
			$this->_Step = "";
			$this->_Substep = "";
			return;
		}

		// Mark ourselves as still running (we will test if we actually do towards the end ;) )
		$this->setState('running');

		// Has MySQLDump run yet? If not, run it. If it has, post process the result.
		if(!$this->_mysqldumpHasRan)
		{
			$this->_doMySQLDump();
		}
		else
		{
			$this->_doPostProcess();
		}
	}

	/**
	 * Implements the _finalize() abstract method
	 *
	 */
	function _finalize()
	{
		$cube =& JoomlapackCUBE::getInstance();
		$provisioning =& $cube->getProvisioning();

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

		// If we are not just doing a db only backup, add the SQL file to the archive
		if( !$this->_DBOnly )
		{
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Adding the SQL dump to the archive");
				
			$archiver =& $provisioning->getArchiverEngine();
			$archiver->addFileRenamed( $this->_tempFile, $this->_saveAsName );
			if($archiver->getError())
			{
				$this->setError($archiver->getError());
				return;
			}
			unset($archiver);
			if($this->getError()) return;
				
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Removing temporary files");
			JoomlapackCUBETempfiles::unregisterAndDeleteTempFile( $this->_tempFile, true );
			JoomlapackCUBETempfiles::unregisterAndDeleteTempFile( $this->_tempMSDfile, true );
			if($this->getError()) return;
		}
		else
		{
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Removing temporary file");
			JoomlapackCUBETempfiles::unregisterAndDeleteTempFile( $this->_tempMSDfile, true );
		}

		$this->setState('finished');
	}

	/******************************************************************************
	 * Actual data processing code
	 ******************************************************************************/

	/**
	 * Runs mysqldump
	 *
	 * @access private
	 *
	 */
	function _doMySQLDump()
	{
		JoomlapackLogger::WriteLog(_JP_LOG_INFO,'Starting mysqldump to dump database '.$this->_database);
		$this->_Step = "Performing mysqldump on ".$this->_database;

		// Get absolute path to the utility
		$shellCommand = MYSQLDUMPPATH;

		// Add authentication parameters
		$shellCommand .= ' --host='.$this->_host . ((trim($this->_port) != '') ? ':'.$this->_port : '');
		$shellCommand .= ' --user='.$this->_username;
		$shellCommand .= $this->_password != '' ? ' --pass='.$this->_password : '';

		// Add our secret ingredient... the correct command line options :p
		$shellCommand .= ' -c --no-create-db -q -Q --compact --skip-set-charset --skip-extended-insert';
		// If MySQL4 compatibility is required, let's add another secret ingredient
		$configuration =& JoomlapackModelRegistry::getInstance();
		if($configuration->get('MySQLCompat') != 'default')
		{
			$shellCommand .= ' --compatible=mysql4';
		}

		// Get db prefix
		$jregistry =& JFactory::getConfig();
		$prefix = $jregistry->getValue('config.dbprefix');

		// Next up, table filters!
		if(is_array($this->_exclusionFilters) && (count($this->_exclusionFilters) > 0))
		{
			foreach($this->_exclusionFilters as $skiptable)
			{
				$skiptable = str_replace('#__', $prefix, $skiptable);
				$shellCommand .= ' --ignore-table='.$this->_database.'.'.$skiptable;
			}
		}

		// Add the file path to dump to
		$registry =& JoomlapackModelRegistry::getInstance();
		$this->_tempMSDfile = basename(tempnam($registry->getTemporaryDirectory(),'jpmd'));
		JoomlapackCUBETempfiles::registerTempFile($this->_tempMSDfile);
		$shellCommand .= ' --result-file='.$this->_tempMSDfile;

		// Finally, the database name itself!
		$shellCommand .= ' '.$this->_database.'';

		// Execute the shell command
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG,'mysqldump command line: '.$shellCommand);
		exec(escapeshellcmd($shellCommand), $result, $errNo);
		$result = implode(" \n",$result);

		// Normally, mysqldump should be silent as a fish. If anything was sput out,
		// there must have been an error.
		if(strlen(trim($result)) > 0 || $errNo != 0 )
		{
			$result .= "System error number $errNo";
			$errorMessage = "Error calling mysqldump: ".$result." \n Command line was: \n ".$shellCommand." \n Please check the path to mysqldump and that mysqldump is able to communicate with your database.";
			$this->setError($errorMessage);
		}
		else
		{
			// No errors, mark this step as complete
			$this->_mysqldumpHasRan = true;
			JoomlapackLogger::WriteLog(_JP_LOG_INFO,'mysqldump on '.$this->_database.' is complete.');
		}
	}

	/**
	 * Executes post processing. This method parses the SQL sump file produced by
	 * mysqldump, identifies individual queries and sends them to _queryPostProcessing
	 * to be sorted out.
	 *
	 * The SQL file parsing logic is based on bigdump.php by Alexey Ozerov, www.ozerov.de
	 * Please donate to this guy, his script is a life saver. I just donated :)
	 *
	 * @access private
	 *
	 */
	function _doPostProcess()
	{
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG,'Post-processing mysqldump data. {start,foffset}={'.$this->_start.','.$this->_foffset.'}');

		// Try opening the dump file
		$file = @fopen($this->_tempMSDfile, 'r');
		if($file === false)
		{
			$errorMessage = __CLASS__." :: Could not open mysqldump temporary file ".$this->_tempMSDfile;
			$this->setError($errorMessage);
			return;
		}

		// ------------------------------ Initialization ------------------------------
		// Allowed comment delimiters: lines starting with these strings will be dropped by BigDump
		$comment[] = '#'; // Standard comment lines are dropped by default
		$comment[] = '-- ';

		// Get the file size
		if (fseek($file, 0, SEEK_END) == 0) {
			$filesize = ftell($file);
		} else {
			$errorMessage = __CLASS__." :: Could not determine the size of mysqldump temporary file ".$this->_tempMSDfile;
			$this->setError($errorMessage);
			return;
		}

		// Check offset upon $filesize
		if($this->_foffset > $filesize)
		{
			$errorMessage = __CLASS__." :: Attempt to read past EOF of mysqldump temporary file ".$this->_tempMSDfile;
			$this->setError($errorMessage);
			return;
		}

		// Set file pointer to $this->_foffset
		if (fseek($file, $this->_foffset) != 0) {
			$errorMessage = __CLASS__." :: Could not set file pointer of mysqldump temporary file ".$this->_tempMSDfile." to ".$this->_foffset;
			$this->setError($errorMessage);
			return;
		}

		// Start processing queries from $file
		$query = "";
		$queries = 0;
		$linenumber = $this->_start;
		$querylines = 0;
		$inparents = false;

		// Stay processing as long as the LINESPERSESSION is not reached or the query is still incomplete
		while ($linenumber < $this->_start + LINESPERSESSION || $query != "") {
			// Read the whole next line
			$dumpline = "";
			while (!feof($file) && substr($dumpline, -1) != "\n") {
				$dumpline .= fgets($file, DATA_CHUNK_LENGTH);
			}
				
			if ($dumpline === "")
			{
				break;
			}
				
			// Handle DOS and Mac encoded linebreaks (I don't know if it will work on Win32 or Mac Servers)
			$dumpline = str_replace("\r\n", "\n", $dumpline);
			$dumpline = str_replace("\r", "\n", $dumpline);
				
			// Skip comments and blank lines only if NOT in parents
			if (!$inparents) {
				$skipline = false;
				reset($comment);
				foreach ($comment as $comment_value) {
					if (!$inparents && (trim($dumpline) == "" || strpos($dumpline, $comment_value) === 0)) {
						$skipline = true;
						break;
					}
				}
				if ($skipline) {
					$linenumber++;
					continue;
				}
			}
				
			// Remove double back-slashes from the dumpline prior to count the quotes ('\\' can only be within strings)
			$dumpline_deslashed = str_replace("\\\\", "", $dumpline);
				
			// Count ' and \' in the dumpline to avoid query break within a text field ending by ;
			// Please don't use double quotes ('"')to surround strings, it wont work
			$parents = substr_count($dumpline_deslashed, "'") - substr_count($dumpline_deslashed, "\\'");
			if ($parents % 2 != 0)
			{
				$inparents = !$inparents;
			}

			// Add the line to query
			$query .= $dumpline;
				
			// Don't count the line if in parents (text fields may include unlimited linebreaks)
			if (!$inparents)
			{
				$querylines++;
			}
				
			// Stop if query contains more lines as defined by MAX_QUERY_LINES
			if ($querylines > MAX_QUERY_LINES) {
				$errorMessage = __CLASS__." :: Stopped processing mysqldump file at line $linenumber. Maximum number of lines perquery exceeded.";
				$this->setError($errorMessage);
				return;
			}
				
			// Process query if end of query detected (; as last character) AND NOT in parents
			if (ereg(";$", trim($dumpline)) && !$inparents) {
				$this->_queryPostProcessing($query);
				$queries++;
				$query = "";
				$querylines = 0;
				JoomlapackLogger::WriteLog(_JP_LOG_INFO,'Processing line '.$linenumber);
			}
				
			$linenumber++;
		}

		// Get the current file position
		$foffset = ftell($file);
		if (!$foffset) {
			$errorMessage = __CLASS__." :: Can not determine the file pointer of the mysqldump dump file";
			$this->setError($errorMessage);
			return;
		}

		// Flush the data output file
		$null = null;
		$this->_writeline($null);

		// Test for completion
		if($linenumber < ($this->_start + LINESPERSESSION - 1))
		{
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG,'Post-processing done. Line number:'.$linenumber.' < '.($this->_start + LINESPERSESSION - 1));
			// Mark state as post-run (do finalize next)
			$this->setState('postrun');
		}

		// Update internal variables
		$this->_foffset = $foffset;
		$this->_start = $linenumber;
		$this->_Step = 'Post processing';
		$this->_Substep = ($this->_foffset / 1024).' of '.($filesize / 1024).' kB processed';
	}

	/**
	 * Handles the post processing of the query at hand and writes the result to the
	 * temporary file set in $this->_tempFile;
	 *
	 * @param string $query The SQL query to post process
	 */
	function _queryPostProcessing(&$query)
	{
		// Drop anything except CREATE TABLE and INSERT INTO commands; the installer
		// caters for DROP TABLE commands where necessary
		$query = trim($query);
		$command = trim(substr($query, 0, 12));
		$command = strtoupper($command);
		$doProcess = ($command == 'CREATE TABLE') || ($command == 'INSERT INTO');
		
		// Catch view creation
		if( substr($query, 0, 13) == '/*!50001 VIEW' )
		{
			// Change the MySQL version statement to CREATE
			$query = 'CREATE OR REPLACE VIEW'.substr($query,13);
			// Remove the final '*/;' string
			if( substr($query, -3) == '*/;' )
				$query = substr($query, 0, strlen($query) - 3);
		}
		
		if(!$doProcess)
		{
			return;
		}

		// Remove newlines, for compatibility with JPI and JPI2 (only if not _DBOnly)
		if(!$this->_DBOnly)
		{
			$query = str_replace("\n", " ", $query);
		}

		// Transform table names to abstract form (only if _isJoomla)
		if($this->_isJoomla && !$this->_DBOnly)
		{
			// Triangulate table's name by finding the surrounding backticks
			$firstTick	= strpos($query, "`"); // First backtick
			$nextTick	= strpos($query, "`", $firstTick+1); // Next backtick
			// Get the name
			$tableName	= substr($query, $firstTick+1, $nextTick-$firstTick-1);
			// Make it into an abstract name
			$abstract	= $this->_getAbstract($tableName);
			// Replace the name ONCE!
			substr_replace($query, $tableName, $firstTick+1, strlen($tableName));
			
			// If we have a view, try an aggressive search and replace
			if(substr($query,0,22) == 'CREATE OR REPLACE VIEW')
			{
				$tablesql = str_replace($prefix, '#__', $tablesql);
			}
		}

		$query .= "\n";
		// Write the result
		$this->_writeline($query);
	}

	/******************************************************************************
	 * Utilitarian functions
	 * @FIXME Dumper engines' utility functions should be placed in a common ancestor class
	 ******************************************************************************/

	/**
	 * Gets the database exclusion filters through the Filter Manager class
	 */
	function _getExclusionFilters()
	{
		if( $this->_useFilters )
		{
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, __CLASS__." :: Retrieving db exclusion filters");
			jpimport('core.utility.filtermanager');
			$filterManager = new JoomlapackCUBEFiltermanager();
			if(!is_object($filterManager))
			{
				$this->setError(__CLASS__.'::_getExclusionFilters() FilterManager is not an object');
				return false;
			}
			$filterManager->init();
			$this->_exclusionFilters = $filterManager->getFilters('database');
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, __CLASS__." :: Retrieved db exclusion filters, OK.");
		} else {
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, __CLASS__." :: Skipping filters");
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
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperDefault :: Only dump database mode detected");
				$this->_tempFile = JoomlapackHelperUtils::getExpandedTarName( '.sql' );
				$this->_saveAsName = '';
				break;

			case 'full':
				if( $this->_dumpFile != '' )
				{
					JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperDefault :: Forced filename using dumpFile found.");
					// If the dumpFile was set, forcibly use this value
					$this->_tempFile = JoomlapackCUBETempfiles::registerTempFile( dechex(crc32(microtime().$this->_dumpFile)) );
					$this->_saveAsName = 'installation/sql/'.$this->_dumpFile;
				} else {
					if( $this->_isJoomla )
					{
						// Joomla! Core Database, use the JoomlaPack way of figuring out the filenames
						JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperDefault :: Core database");
						$this->_tempFile = JoomlapackCUBETempfiles::registerTempFile( dechex(crc32(microtime().'joomla.sql')) );
						$this->_saveAsName = 'installation/sql/joomla.sql';
					} else {
						// External databases, we use the database's name
						JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperDefault :: External database");
						$this->_tempFile = JoomlapackCUBETempfiles::registerTempFile( dechex(crc32(microtime().$this->_database.'.sql')) );
						$this->_saveAsName = 'installation/sql/'.$this->_database.'.sql';
					}
				}
				break;

			case 'extradbonly':
				if( $this->_dumpFile != '' )
				{
					JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperDefault :: Forced filename using dumpFile found.");
					// If the dumpFile was set, forcibly use this value
					$this->_tempFile = JoomlapackCUBETempfiles::registerTempFile( dechex(crc32(microtime().$this->_dumpFile)) );
					$this->_saveAsName = $this->_dumpFile;
				} else {
					if( $this->_isJoomla )
					{
						// Joomla! Core Database, use the JoomlaPack way of figuring out the filenames
						JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperDefault :: Core database");
						$this->_tempFile = JoomlapackCUBETempfiles::registerTempFile( dechex(crc32(microtime().'joomla.sql')) );
						$this->_saveAsName = 'joomla.sql';
					} else {
						// External databases, we use the database's name
						JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackDumperDefault :: External database");
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
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, __CLASS__." :: Deleting leftover files, if any");
		if( file_exists( $this->_tempFile ) ) @unlink( $this->_tempFile );
		if( file_exists( $this->_tempMSDfile ) ) @unlink( $this->_tempMSDfile );
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
						// This is applied in case the prefix is null (the site works? Ah, who knows...)
						$tableAbstract = '#__' . $tableName;
					}
				}
				
				return $tableAbstract;
				break;
		}
	}


}