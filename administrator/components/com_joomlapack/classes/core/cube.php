<?php
/**
 * @package		JoomlaPack
 * @copyright	Copyright (C) 2006-2009 JoomlaPack Developers. All rights reserved.
 * @version		$Id$
 * @license 		http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @since		1.3
 */
defined('_JEXEC') or die('Restricted access');

// Include required classes
if(!class_exists('JoomlapackModelStatistics'))
{
	jpimport('models.statistics', true);
}

if(!class_exists('JoomlapackModelRegistry'))
{
	jpimport('models.registry', true);
}

if(!class_exists('JoomlapackHelperUtils'))
{
	jpimport('helpers.utils', true);
}

jpimport('core.utility.logger');

jpimport('abstract.enginearchiver');
jpimport('abstract.enginelister');
jpimport('abstract.engineparts');
jpimport('abstract.filter');

jpimport('core.domain.installer');
jpimport('core.domain.dbbackup');
jpimport('core.domain.pack');

jpimport('core.utility.tables');
jpimport('core.utility.filtermanager');
jpimport('core.utility.algorunner');
jpimport('core.utility.provisioning');
jpimport('core.utility.tempfiles');


/**
 * Componentized Universal Backup Engine, version 2.1
 *
 * CUBE is the heart and brains of JoomlaPack. It takes care of all the magic for
 * producing a backup, delegating specialised tasks to CUBE Part and Engine classes.
 * This class is the API exposed to the component and is kept as simple as possible.
 *
 */
class JoomlapackCUBE extends JObject
{
	/*
	 * ======================================================================
	 * Public fields
	 * ======================================================================
	 */

	/**
	 * CUBE engine provisioning object
	 *
	 * @var JoomlapackCUBEProvisioning
	 */
	var $provisioning;

	/**
	 * Backup statistics model object
	 *
	 * @var JoomlapackModelStatistics
	 */
	var $statmodel;

	/**
	 * Statistics ID; it's needed to finalize the backup
	 *
	 * @var int
	 */
	var $_statID;

	/**
	 * Current step number, over the whole backup procedure
	 *
	 * @var int
	 */
	var $stepCounter;

	/**
	 * Current operation number, over the current step
	 *
	 * @var int
	 */
	var $operationCounter;
	
	/**
	 * @var string The relative path to the archive's name
	 */
	var $relativeArchiveName;
	
	/**
	 * @var array The warnings LIFO stack, holds the last 10 warnings, w/ their
	 * timestamp. Each element is an object w/ properties datetime, warning.
	 */
	var $_warnings;
	
	/*
	 * ======================================================================
	 * Private fields
	 * ======================================================================
	 */

	/** @var bool Indicates whether CUBE has been initialised in order to take a backup */
	var $_initialised = false;

	/** @var JoomlapackCUBEPart Part object being used in execution */
	var $_object = null;

	/** @var string Active execution domain */
	var $_activeDomain = null;

	/** @var bool Set to true upon finishing, or failing with an error */
	var $_isFinished = false;

	/** @var string Current part's step */
	var $_currentStep;

	/** @var string Current part's substep */
	var $_currentSubstep;
	
	/** @var bool used to block multipart updating while CUBE is being created */
	var $_lockMultiPartUpdate = true;

	/*
	 * ======================================================================
	 * CUBE object loading
	 * ======================================================================
	 */

	/**
	 * Singleton pattern. It tries to load the CUBE off the database if it's not
	 * found in memory. If it fails too, it creates a new object.
	 *
	 * @return JoomlapackCUBE
	 */
	function &getInstance()
	{
		static $instance;

		if(!is_object($instance))
		{
			// Try loading from database
			if(JoomlapackCUBETables::CountVar('CUBEObject') == 1)
			{
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBE::getInstance - Loading from database");
				// Get the includes first!
				JoomlapackCUBEProvisioning::retrieveEngineIncludes();
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, " -- Loaded requirements");
				// Then load the object
				$instance = JoomlapackCUBETables::UnserializeVar('CUBEObject');
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, " -- Loaded instance");

				if(!is_object($instance))
				{
					// Damn! We have some broken crap in the db. Gotta retry :(
					JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBE::getInstance - Broken in database, creating new instance");
					$instance = new JoomlapackCUBE();
				}
			}
			else
			{
				// No stored object, we are forced to create a fresh object or something really
				// odd is going on with MySQL!
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBE::getInstance - Not found in database, creating new instance");
				$instance = new JoomlapackCUBE();
			}
		}

		return $instance;
	}

	function JoomlapackCUBE()
	{
	}

	/*
	 * ======================================================================
	 * CUBE API
	 * ======================================================================
	 */

	/**
	 * Static function to reset older backups, removing their temporary files
	 * and marking them as failed.
	 *
	 * @static
	 */
	function reset($do_not_log = false)
	{
		// Pause logging if so desired
		if($do_not_log) JoomlapackLogger::WriteLog(false,'');
		
		// 1. Detect failed backups
		if(!class_exists('JoomlapackModelStatistics'))
		{
			jpimport('models.statistics', true);
		}

		$model =& JoomlapackModelStatistics::getInstance();
		$runningList = $model->getRunningStatisticsList();

		if(is_array($runningList))
		{
			if(!empty($runningList))
			{
				jimport('joomla.filesystem.file');
				// Mark running backups as failed
				foreach($runningList as $running)
				{
					$filenames = $model->getAllFilenames($running->id, false);
					// Process if there are files to delete...
					if(!is_null($filenames))
					{
						// Delete the failed backup's archive, if exists
						foreach($filenames as $failedArchive)
						{
							if(JFile::exists($failedArchive))
							{
								if(!JFile::delete($failedArchive))
								{
									// fallback; if Joomla! used FTP to unlink the file and it failed, let's try the old-fashioned way
									@unlink($failedArchive);
								}
							}
						}
					}
					// Mark the backup failed
					$running->status = 'fail';
					$model->save($running);
				}
				unset($model);
			}
		}

		// 2. Delete any stale CUBE db entry / file
		JoomlapackCUBETables::DeleteVar('CUBEObject');

		// 3. Remove temporary files
		JoomlapackCUBETempfiles::deleteTempFiles();

		// 4. Remove temporary data from #__jp_temp
		JoomlapackCUBETables::DeleteMultipleVars('%CUBE%');
		
		// Unpause logging if it was previously paused
		if($do_not_log) JoomlapackLogger::WriteLog(true,'');
	}

	/**
	 * Initialises CUBE to start a new backup
	 *
	 */
	function start($description = '', $comment = '')
	{		
		global $mainframe;
		
		$this->_enforce_minexectime(true); // Start the timing
		
		// Initialize warnings
		$this->_warnings = array();

		// Initialize counters
		$this->stepCounter = 0;
		$this->operationCounter = 0;

		// Initialize CUBEObject file storage (in case we need it)
		JoomlapackCUBETables::DeleteMultipleVars('%CUBE%');

		// Load registry
		$configuration =& JoomlapackModelRegistry::getInstance();
		$configuration->reload();

		// Load engine provisioning object
		$this->provisioning =& JoomlapackCUBEProvisioning::getInstance();

		// Initialize statistics model
		$this->statmodel =& JoomlapackModelStatistics::getInstance();

		// Initialize private fields
		$this->_activeDomain = 'init';
		$this->_object = null;
		$this->_isFinished = false;

		// Load JP version
		JoomlapackHelperUtils::getJoomlaPackVersion();

		// Initialise log file
		JoomlapackLogger::ResetLog();
		JoomlapackLogger::WriteLog(_JP_LOG_INFO, "--------------------------------------------------------------------------------");
		JoomlapackLogger::WriteLog(_JP_LOG_INFO, "JoomlaPack "._JP_VERSION.' ('._JP_DATE.')');
		JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Got backup?");
		JoomlapackLogger::WriteLog(_JP_LOG_INFO, "--------------------------------------------------------------------------------");
		// PHP configuration variables are tried to be logged only for debug and info log levels
		if ($configuration->get('logLevel') >= 2) {
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "--- System Information ---" );
			if( function_exists('phpversion'))
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "PHP Version        :" . phpversion() );
			if(function_exists('php_uname'))
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "OS Version         :" . php_uname('s') );
			$db =& JFactory::getDBO();
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "DB Version         :" . $db->getVersion() );
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "DB Collation       :" . $db->getCollation() );
			if (isset($_SERVER['SERVER_SOFTWARE'])) {
				$server = $_SERVER['SERVER_SOFTWARE'];
			} else if (($sf = getenv('SERVER_SOFTWARE'))) {
				$server = $sf;
			} else {
				$server = JText::_( 'n/a' );
			}
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Web Server         :" . $server );
			if(function_exists('php_sapi_name'))
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "PHP Interface      :" . php_sapi_name() );
			$version = new JVersion();
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Joomla! version    :" . $version->getLongVersion() );
			if(isset($_SERVER['HTTP_USER_AGENT']))
				JoomlapackLogger::WriteLog(_JP_LOG_INFO, "User agent         :" . phpversion() <= "4.2.1" ? getenv( "HTTP_USER_AGENT" ) : $_SERVER['HTTP_USER_AGENT'] );
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Safe mode          :" . ini_get("safe_mode") );
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Display errors     :" . ini_get("display_errors") );
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Error reporting    :" . error2string() );
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Disabled functions :" . ini_get("disable_functions") );
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "open_basedir restr.:" . ini_get('open_basedir') );
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Max. exec. time    :" . ini_get("max_execution_time") );
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Memory limit       :" . ini_get("memory_limit") );
			if(function_exists("memory_get_usage"))
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Current mem. usage :" . memory_get_usage() );
			if(function_exists("gzcompress")) {
				JoomlapackLogger::WriteLog(_JP_LOG_INFO, "GZIP Compression   : available (good)" );
			} else {
				JoomlapackLogger::WriteLog(_JP_LOG_INFO, "GZIP Compression   : n/a (no compression)" );
			}
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "JPATH_BASE         :" . JPATH_BASE );
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "JPATH_SITE         :" . JPATH_SITE );
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "JPATH_ROOT         :" . JPATH_ROOT );
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "JPATH_CACHE        :" . JPATH_CACHE );
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Output directory   :" . $configuration->get('OutputDirectory') );
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Temporary directory:" . $configuration->getTemporaryDirectory() );
			$temp = JoomlapackCUBETables::_getBase64() ? 'Available; will be used for temp vars' : 'Not available';
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "BASE64 Encoding    :" . $temp );
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "CUBEObject Storage :" . (JoomlapackCUBETables::_isSetCUBEInFile() ? 'Temporary file' : 'Database') );
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "--------------------------------------------------------------------------------");
		}

		jpimport('helpers.status',true);
		$statushelper =& JoomlapackHelperStatus::getInstance();
		if($statushelper->hasQuirks())
		{
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "JoomlaPack has detected the following potential problems:" );
			foreach($statushelper->quirks as $q)
			{
				JoomlapackLogger::WriteLog(_JP_LOG_INFO, '- '.$q['code'].' '.$q['description'].' ('.$q['severity'].')' );
			}
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "You probably do not have to worry about them, but you should be aware of them." );
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "--------------------------------------------------------------------------------");
		}

		// Get current profile ID
		$session =& JFactory::getSession();
		$profile_id = $session->get('profile', null, 'joomlapack');
		JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Loading profile #$profile_id");

		// Get archive name
		switch($configuration->get('BackupType'))
		{
			case 'dbonly':
				JoomlapackLogger::WriteLog(_JP_LOG_INFO, "JoomlaPack is starting a new database backup");
				$extension = '.sql';
				break;

			case 'full':
				JoomlapackLogger::WriteLog(_JP_LOG_INFO, "JoomlaPack is starting a new full site backup");
				// Instanciate archiver, only if not in DB only mode
				$archiver =& $this->provisioning->getArchiverEngine(true);
				if($this->provisioning->getError())
				{
					$this->setError($this->provisioning->getError());
					return;
				}
				$extension = $this->provisioning->archiveExtension;
				break;

			case 'extradbonly':
				JoomlapackLogger::WriteLog(_JP_LOG_INFO, "JoomlaPack is starting a new extra databases only backup");
				// Instanciate archiver, only if not in DB only mode
				$archiver =& $this->provisioning->getArchiverEngine(true);
				if($this->provisioning->getError())
				{
					$this->setError($this->provisioning->getError());
					return;
				}
				$extension = $this->provisioning->archiveExtension;
				break;
		}

		$relativeArchiveName = JoomlapackHelperUtils::getExpandedTarName($extension, false);
		$this->relativeArchiveName = $relativeArchiveName; 
		$absoluteArchiveName = JoomlapackHelperUtils::getExpandedTarName($extension, true);

		// ==== Stats initialisation ===
		$this->statmodel->setId(0);
		// Detect backup origin
		if($mainframe->isAdmin())
		{
			$origin = 'backend';
		}
		else
		{
			$origin = 'frontend';
		}

		// Get profile
		$session =& JFactory::getSession();
		$profile_id = $session->get('profile', null, 'joomlapack');
		unset($session);

		// Create an initial stats entry
		jimport('joomla.utilities.date');
		$jdate = new JDate();
		switch($configuration->get('BackupType'))
		{
			case 'full':
				$backupType = 'full';
				break;

			case 'dbonly':
				$backupType = 'dbonly';
				break;

			case 'extradbonly':
				$backupType = 'extradbonly';
				break;
		}
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Backup type is now set to '" . $backupType . "'");
		$temp = array(
			'description'	=> $description,
			'comment'		=> $comment,
			'backupstart'	=> $jdate->toMySQL(),
			'status'		=> 'run',
			'origin'		=> $origin,
			'type'			=> $backupType,
			'profile_id'	=> $profile_id,
			'archivename'	=> $relativeArchiveName,
			'absolute_path'	=> $absoluteArchiveName,
			'multipart'		=> 0
		);

		// Save the entry
		$this->statmodel->save($temp);
		if($this->statmodel->getError())
		{
			$this->setError($this->statmodel->getError());
			return;
		}
		unset($temp);

		// Get the ID!
		$temp = $this->statmodel->getSavedTable();
		$this->_statID = $temp->id;
		$this->_lockMultiPartUpdate = false;

		// Initialize the archive.
		if ($configuration->get('BackupType') != 'dbonly')
		{
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Expanded archive file name: " . $absoluteArchiveName);
				
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Seeding archive with installer");
			$installerPackage = JPATH_COMPONENT_ADMINISTRATOR.DS.'assets'.DS."installers".DS.$configuration->get('InstallerPackage');
			$archiver->initialize($installerPackage, $absoluteArchiveName);
			$archiver->setComment($comment); // Add the comment to the archive itself.
			if($archiver->getError())
			{
				$this->setError($archiver->getError());
				return;
			}
		}

		$this->_initialised = true;
		$this->_enforce_minexectime(false);
	}

	/**
	 * Updates the multipart property of the statistic record. When this value is
	 * greater than 1, it indicates that we have a multi-part (split) archive file.
	 * @param int $multipart How many parts we have
	 */
	function updateMultipart( $multipart )
	{
		if($this->_lockMultiPartUpdate) return;
		
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG,'Updating multipart status to '.$multipart);
		
		$db =& JFactory::getDBO();
		
		$sql = 'UPDATE '.$db->nameQuote('#__jp_stats').' SET '.$db->nameQuote('multipart').
			' = '.$db->Quote($multipart).' WHERE '.$db->nameQuote('id').' = '.
			$db->Quote($this->_statID);
		$db->setQuery($sql);
		$db->query();
	}

	/**
	 * Steps the CUBE, performing yet another small chunk of backup work necessary
	 *
	 */
	function tick()
	{
		$this->_enforce_minexectime(true);
		if(!$this->_initialised)
		{
			$this->setError(JText::_('CUBE_NOTINIT'));
		}
		elseif( (!$this->getError()) && (!$this->_isFinished))
		{
			// Initialize operation counter
			$this->operationCounter = 0;
				
			// Advance step counter
			$this->stepCounter++;
				
			// Log step start number
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG,'====== Starting Step number '.$this->stepCounter.' ======');
				
			$algorithmRunner =& JoomlapackCUBEAlgorunner::getInstance();
			$algo = $algorithmRunner->selectAlgorithm($this->_activeDomain);
				
			switch($this->_activeDomain)
			{
				case 'init':
				case 'finale':
					$ret = 1;
					break;
						
				default:
					if(!is_object($this->_object))
					{
						$algorithmRunner->setError('Current object is not an object on '.$this->_activeDomain);
						$ret = 2;
					}
					else
					{
						$ret = $algorithmRunner->runAlgorithm($algo, $this->_object);
						$this->_currentStep = $algorithmRunner->currentStep;
						$this->_currentSubstep = $algorithmRunner->currentSubstep;
					}
					break;
			}
				
			switch($ret)
			{
				case 0:
					// more work to do, return OK
					JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "CUBE :: More work required in domain '" . $this->_activeDomain);
					break;
				case 1:
					// Engine part finished
					JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "CUBE :: Domain '" . $this->_activeDomain . "' has finished");
					$this->_getNextObject();
					if ($this->_activeDomain == "finale") {
						// We have finished the whole process.
						JoomlapackCUBE::_finalise();
						JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "CUBE :: Just finished");
					}
					break;
				case 2:
					// An error occured...
					JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "CUBE :: Error occured in domain '" . $this->_activeDomain);
					$this->setError($algorithmRunner->getError());
					$this->reset();
					//$this->_isFinished = true;
					break;
			}
		}

		// Log step end
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG,'====== Finished Step number '.$this->stepCounter.' ======');

		$this->_enforce_minexectime(false);
		return $this->_makeCUBEArray();
	}

	/**
	 * Saves the CUBE object and all of its attached objects
	 *
	 */
	function save()
	{
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Saving CUBEObject instance" );
		JoomlapackCUBETables::SerializeVar('CUBEObject', $this);
	}

	function getCUBEArray()
	{
		return $this->_makeCUBEArray();
	}

	/**
	 * Get the CUBE engine provisioning object
	 *
	 * @return JoomlapackCUBEProvisioning
	 */
	function &getProvisioning()
	{
		return $this->provisioning;
	}
	
	/**
	 * Adds a warning to the stack and the log file
	 * @param string $message The warning message to store
	 * @access public
	 */
	function addWarning($message)
	{
		// Add to JoomlaPack log
		JoomlapackLogger::WriteLog(_JP_LOG_WARNING, $message);
		
		// Crop the LIFO buffer if there are more than 10 warnings
		if( count($this->_warnings) == 10 )
		{
			array_pop($this->_warnings);
		}
		
		// Get actual date/time stamp
		jimport('joomla.utilities.date');
		$date = new JDate();
		
		// Create and add the warning object
		$warning = new stdClass;
		$warning->datetime = $date->toUnix(false);
		$warning->warning = $message;
		
		array_unshift($this->_warnings, $warning);		
	}
	
	/**
	 * Gets the whole list of warnings
	 * @return array
	 */
	function getWarnings()
	{
		return $this->_warnings;
	}

	/*
	 * ======================================================================
	 * Private methods
	 * ======================================================================
	 */

	function _finalise()
	{
		global $mainframe;
		
		$this->_enforce_minexectime(true);

		// The backup is over. Did we encounter any errors?
		if($this->getError())
		{
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "CUBE - Can't finalise due to errors");
			// Notify Super Administrators if it's a front-end backup
			if(!$mainframe->isAdmin())
			{
				$this->_mailToSuperAdministrators($this->getError());
			}
			// Oops! Have to reset() because of the error
			$this->reset();
		}
		else
		{
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "CUBE - Finalising backup started");
				
			// Notify Super Administrators if it's a front-end backup
			if(!$mainframe->isAdmin())
			{
				$this->_mailToSuperAdministrators();
			}
				
			// Remove temp files
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Removing temporary files" );
			JoomlapackCUBETempfiles::deleteTempFiles();

			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Updating statistics" );
			// We finished normally. Fetch the stats record
			$this->statmodel = null;
			$this->statmodel = new JoomlapackModelStatistics($this->_statID);
			$this->statmodel->setId($this->_statID);
			$statRecord =& $this->statmodel->getStatistic();
			jimport('joomla.utilities.date');
			$jdate = new JDate();
			$statRecord->backupend = $jdate->toMySQL();
			$statRecord->status = 'complete';
			$this->statmodel->save($statRecord);
				
			// Apply quotas
			$errorReporting = @error_reporting(0);
			$quotaFiles = $this->statmodel->getOldBackupsToDelete();
			if(count($quotaFiles) > 0)
			{
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Applying quotas" );
				jimport('joomla.filesystem.file');
				foreach($quotaFiles as $file)
				{
					if(!@unlink($file))
					{
						// FIX 2.0: Using JFile::delete raised warnings which messed up XMLRPC output. After all, we write backup files from PHP, using FTP should not be necessary to delete them!
						$this->addWarning("Failed to remove old backup file ".$file );
					}
				}
			}
			@error_reporting($errorReporting);
				
			// Set internal variables and go to bed... er... I mean, return control to the user
			$this->_isFinished = true;
			$this->_activeDomain = 'finale';
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "CUBE - Finalising backup ended");
		}
		
		$this->_enforce_minexectime(false);
	}

	/**
	 * Overrides JObject's setError() method to cater for logging
	 *
	 * @param string $message The error message
	 */
	function setError($message)
	{
		parent::setError($message);
		JoomlapackLogger::WriteLog(_JP_LOG_ERROR, $message);
	}

	/**
	 * Creates the CUBE return array
	 * @return array A CUBE return array with timestamp data
	 * @access private
	 */
	function _makeCUBEArray(){
		$ret['HasRun'] = (int)$this->_isFinished;
		$ret['Domain'] = $this->_activeDomain;
		$ret['Step'] = htmlentities( $this->_currentStep );
		$ret['Substep'] = htmlentities( $this->_currentSubstep );
		$ret['Error'] = htmlentities( $this->getError() );
		// + JP 2.4 : Warnings propagation
		if(count($this->_warnings) > 0)
		{
			// We have warnings, construct an array of warnings...
			$ret['Warnings'] = array();
			foreach($this->_warnings as $warn_obj)
			{
				$ret['Warnings'][] = $warn_obj->warning;
			}
		} else {
			// No warnings, return an empty table
			$ret['Warnings'] = array();			
		}
		$ret['Archive'] = $this->relativeArchiveName;
		return $ret;
	}

	/**
	 * Creates the next engine object based on the current execution domain
	 * @return integer 0 = success, 1 = all done, 2 = error
	 * @access private
	 */
	function _getNextObject(){
		// Handle errors
		if(is_object($this->_object))
		{
			if($this->_object->getError())
			{
				$this->setError($this->_object->getError());
				return 2;
			}
		}

		// Kill existing object
		$this->_object = null;

		// Try to figure out what object to spawn next
		switch( $this->_activeDomain )
		{
			case "init":
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Next domain --> Installer deployment");
				$this->_object = new JoomlapackCUBEDomainInstaller();
				if(!is_object($this->_object))
				{
					$this->setError('Failed to instanciate JoomlapackCUBEDomainInstaller');
					return 2;
				}
				$this->_activeDomain = "installer";
				return 0;
				break;
					
			case "installer":
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Next domain --> Database backup");
				$this->_object = new JoomlapackCUBEDomainDBBackup();
				if(!is_object($this->_object))
				{
					$this->setError('Failed to instanciate JoomlapackCUBEDomainDBBackup');
					return 2;
				}
				$this->_activeDomain = "PackDB";
				return 0;
				break;

			case "PackDB":
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Next domain --> Packing");
				$this->_object = new JoomlapackCUBEDomainPack();
				if(!is_object($this->_object))
				{
					$this->setError('Failed to instanciate JoomlapackCUBEDomainPack');
					return 2;
				}
				$this->_activeDomain = "Packing";
				return 0;
				break;
					
			case "Packing":
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Next domain --> finale");
				$this->_activeDomain = "finale";
				$this->_object = null;
				return 1;
				break;
					
			case "finale":
			default:
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Next domain not applicable; already on 'finale'");
				$this->_activeDomain = "finale";
				$this->_object = null;
				$this->_isFinished = true;
				return 1;
				break;
		}
	}

	/**
	 * Sends an email to Super Administrators for successful or failed front-end
	 * backups. As of JP 2.2 you can override this and send an email to any arbitrary
	 * email address, so as not to bother all Super Admins.
	 *
	 * @param string $error If null, indicates successful backup. Otherwise, it's the last error message.
	 */
	function _mailToSuperAdministrators($error = null)
	{
		// If the mail option is disabled, do not proceed
		$registry =& JoomlapackModelRegistry::getInstance();
		if(!$registry->get('frontendemail')) return;

		if( trim($registry->get('arbitraryfeemail')) !== '' )
		{
			// Send email to an arbitrary email address
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Preparing to send e-mail to specified email address");
				
			// Fake a Super Administrators list to avoid code duplication
			$dummy = new stdClass();
			$dummy->email = trim($registry->get('arbitraryfeemail'));
			$superAdmins[] = $dummy;
		}
		else
		{
			// Send email to all Super adminsitrators
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Preparing to send e-mail to Super Administrators");

			// Get a list of administrators
			$db =& JFactory::getDBO();
			$query = 'SELECT name, email FROM #__users'.
					' WHERE usertype = \'Super Administrator\' ';
			$db->setQuery($query);
			$superAdmins =& $db->loadObjectList();
		}

		// Proceed if there are users to mail to
		if( count($superAdmins) > 0 )
		{
			// Get the mailer
			$mailer =& JFactory::getMailer();
				
			// Add recipients (super administrators)
			$recipient = array();
			foreach($superAdmins as $sa)
			{
				$recipient[] = $sa->email;
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Adding address ".$sa->email);
			}
			$mailer->addRecipient($recipient);
				
			// Set message
			if(is_null($error))
			{
				// Success email
				$mailer->setSubject(JText::_('EMAIL_SUBJECT_OK'));
				$mailer->setBody(JText::_('EMAIL_BODY_OK'));
			}
			else
			{
				// Failure email
				$mailer->setSubject(JText::_('EMAIL_SUBJECT_ERROR'));
				$mailer->setBody(JText::_('EMAIL_BODY_ERROR').' '.$error);
			}
				
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "I will send an email with the following subject:");
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, $mailer->Subject);
			
			// Send message
			ob_start();
			$mailer->Send();
			ob_clean();
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "E-mail sent");
		}
		else
		{
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Oops! No Super Administrators found?!");
		}

	}
	
	/**
	 * Enforces the minimum execution time per step, if such a thing is set up
	 * @param bool $starting True when starting timing the script, false otherwise
	 */
	function _enforce_minexectime($starting)
	{
		static $start_time, $end_time;

		if($starting)
		{		
			list($usec, $sec) = explode(" ", microtime());
			$start_time = ((float)$usec + (float)$sec);
		}
		else
		{
			// Try to get a sane value for PHP's maximum_execution_time INI parameter
			if(@function_exists('ini_get'))
			{
				$php_max_exec = @ini_get("maximum_execution_time");
			}
			else
			{
				$php_max_exec = 10;
			}
			if ( ($php_max_exec == "") || ($php_max_exec == 0) ) {
				$php_max_exec = 10;
			}
			// Decrease $php_max_exec time by 500 msec we need (approx.) to tear down
			// the application, as well as another 500msec added for rounding
			// error purposes. Also make sure this is never gonna be less than 0.
			$php_max_exec = max($php_max_exec * 1000 - 1000, 0);

			// Get the "minimum execution time per step" JoomlaPack configuration variable
			$configuration =& JoomlapackModelRegistry::getInstance();
			$minexectime = $configuration->get('minexectime',0);
			if(!is_numeric($minexectime)) $minexectime = 0;
			
			// Make sure we are not over PHP's time limit! 
			if($minexectime > $php_max_exec) $minexectime = $php_max_exec;

			// Get current timestamp and calculate how much time has passed
			list($usec, $sec) = explode(" ", microtime());
			$end_time = ((float)$usec + (float)$sec);
			$elapsed_time = 1000 * ($end_time - $start_time);
			
			// Only run a sleep delay if we haven't reached the minexectime execution time
			if( ($minexectime > $elapsed_time) && ($elapsed_time > 0) )
			{
				$sleep_msec = $minexectime - $elapsed_time;
				if(function_exists('usleep'))
				{
					JoomlapackLogger::WriteLog( _JP_LOG_DEBUG, "Sleeping for $sleep_msec msec, using usleep()" );
					usleep(1000 * $sleep_msec);
				}
				elseif(function_exists('time_nanosleep'))
				{
					JoomlapackLogger::WriteLog( _JP_LOG_DEBUG, "Sleeping for $sleep_msec msec, using time_nanosleep()" );
					$sleep_sec = round($sleep_msec / 1000);
					$sleep_nsec = 1000000 * ($sleep_msec - ($sleep_sec * 1000));
					time_nanosleep($sleep_sec, $sleep_nsec);
				}
				elseif(function_exists('time_sleep_until'))
				{
					JoomlapackLogger::WriteLog( _JP_LOG_DEBUG, "Sleeping for $sleep_msec msec, using time_sleep_until()" );
					$until_timestamp = time() + $sleep_msec / 1000;
					time_sleep_until($until_timestamp);
				}
				elseif(function_exists('sleep'))
				{
					$sleep_sec = ceil($sleep_msec/1000);
					JoomlapackLogger::WriteLog( _JP_LOG_DEBUG, "Sleeping for $sleep_sec seconds, using sleep()" );
					sleep( $sleep_sec );	
				}
			}
			elseif( $elapsed_time > 0 )
			{
				// No sleep required, even if user configured us to be able to do so.
				JoomlapackLogger::WriteLog( _JP_LOG_DEBUG, "No need to sleep; execution time: $elapsed_time msec; min. exec. time: $minexectime msec" );
			}
		}
	}
}

function error2string()
{
	if(function_exists('error_reporting'))
	{
		$value = error_reporting();
	} else {
		return "Not applicable; host too restrictive";
	}
	$level_names = array(
	E_ERROR => 'E_ERROR', E_WARNING => 'E_WARNING',
	E_PARSE => 'E_PARSE', E_NOTICE => 'E_NOTICE',
	E_CORE_ERROR => 'E_CORE_ERROR', E_CORE_WARNING => 'E_CORE_WARNING',
	E_COMPILE_ERROR => 'E_COMPILE_ERROR', E_COMPILE_WARNING => 'E_COMPILE_WARNING',
	E_USER_ERROR => 'E_USER_ERROR', E_USER_WARNING => 'E_USER_WARNING',
	E_USER_NOTICE => 'E_USER_NOTICE' );
	if(defined('E_STRICT')) $level_names[E_STRICT]='E_STRICT';
	$levels=array();
	if(($value&E_ALL)==E_ALL)
	{
		$levels[]='E_ALL';
		$value&=~E_ALL;
	}
	foreach($level_names as $level=>$name)
	if(($value&$level)==$level) $levels[]=$name;
	return implode(' | ',$levels);
}

/**
 * Timeout error handler
 */
function deadOnTimeOut()
{
	if( connection_status() >= 2 ) {
		JoomlapackLogger::WriteLog(_JP_LOG_ERROR, JText::_('CUBE_TIMEOUT') );
	}
}
register_shutdown_function("deadOnTimeOut");