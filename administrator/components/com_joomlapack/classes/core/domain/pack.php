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

$config =& JoomlapackModelRegistry::getInstance();
define('JPMaxFragmentSize', $config->get('mnMaxFragmentSize'));		// Maximum bytes a fragment can have (default: 1Mb)
define('JPMaxFragmentFiles', $config->get('mnMaxFragmentFiles'));	// Maximum number of files a fragment can have (default: 50 files)

/**
 * Packing engine. Takes care of putting gathered files (the file list) into
 * an archive.
 */
class JoomlapackCUBEDomainPack extends JoomlapackCUBEParts {
	/**
	 * @var array Directories to exclude
	 */
	var $_ExcludeDirs;

	/**
	 * @var array Files to exclude
	 */
	var $_ExcludeFiles;

	/**
	 * Directories to exclude their files from the backup
	 *
	 * @var array
	 */
	var $_skipContainedFiles;

	/**
	 * Directories to exclude their subdirectories from the backup
	 *
	 * @var array
	 */
	var $_skipContainedDirectories;

	/**
	 * @var array Directories left to be scanned
	 */
	var $_directoryList;

	/**
	 * @var array Files left to be put into the archive
	 */
	var $_fileList;

	/**
	 * Operation toggle. When it is true, files are added in the archive. When it is off, the
	 * directories are scanned for files and directories.
	 *
	 * @var bool
	 */
	var $_doneScanning = false;

	/**
	 * Operation toggle #2. Scanning is separated in two sub-operations: scanning for
	 * subdirectories (when this flag is false) and scanning for files (when this flag is
	 * true).
	 *
	 * @var bool
	 */
	var $_doneSubdirectoryScanning = false;

	/**
	 * Operation toggle #3. Since the scanning of a folder for files might be interrupted
	 * for some reason, when this variable is false the algorithm is forced NOT to skip
	 * to the next item of the directory list.
	 *
	 * @var bool
	 */
	var $_doneFileScanning = true;

	/**
	 * Path to add to scanned files
	 *
	 * @var string
	 */
	var $_addPath;

	/**
	 * Path to remove from scanned files
	 *
	 * @var string
	 */
	var $_removePath;

	/**
	 * An array of EFF-defined directories
	 *
	 * @var array
	 */
	var $_extraDirs = array();

	var $_processedFiles;
	var $_dirName;

	// ============================================================================================
	// IMPLEMENTATION OF JoomlapackEngineParts METHODS
	// ============================================================================================
	/**
	 * Public constructor of the class
	 *
	 * @return JoomlapackCUBEDomainPack
	 */
	function JoomlapackCUBEDomainPack(){
		$this->_DomainName = "Packing";
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainPack :: new instance");
	}

	/**
	 * Implements the _prepare() abstract method
	 *
	 */
	function _prepare()
	{
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainPack :: Starting _prepare()");

		$cube =& JoomlapackCUBE::getInstance();
		
		// Grab the EFF filters
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainPack :: Getting off-site directory inclusion filters (EFF)");
		jpimport('models.eff', true);
		$effModel = new JoomlapackModelEff();
		$this->_extraDirs =& $effModel->getMapping();

		// Add the mapping text file if there are EFFs defined!
		if(count($this->_extraDirs) > 0)
		{
			// We add a README.txt file in our virtual directory...
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Creating README.txt in the EFF virtual folder");
			$virtualContents = JText::_('EFF_MAPTEXT_INTRO')."\n\n";
			foreach($this->_extraDirs as $dir)
			{
				$virtualContents .= JText::sprintf('EFF_MAPTEXT_LINE', $dir['vdir'], $dir['fsdir'])."\n";
			}
			// Add the file to our archive
			$registry =& JoomlapackModelRegistry::getInstance();
			$provisioning =& $cube->getProvisioning();
			$archiver =& $provisioning->getArchiverEngine();
			$archiver->addVirtualFile('README.txt', $registry->get('effvfolder'), $virtualContents);
		}


		// Get the directory exclusion filters - this only needs to be done once
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainPack :: Getting exclusion filters");
		$this->_loadAndCacheFilters();
		if($this->getError()) return false;

		// FIX 1.1.0 $mosConfig_absolute_path may contain trailing slashes or backslashes incompatible with exclusion filters
		// FIX 1.2.2 Some hosts yield an empty string on realpath(JPATH_SITE)
		// FIX 2.2 On Windows, realpath might fail
		jpimport('helpers.utils', true);
		// FIX 2.4: Make an assumption (wild guess...)
		if(JPATH_BASE == '/administrator')
		{
			$cube->addWarning("Your site's root is an empty string. I am trying a workaround.");
			$jpath_site_real = '/';
		}
		else
		{
			// Fix 2.4: Make sure that $jpath_site_real contains something even if realpath fails
			$jpath_site_real = @realpath(trim(JPATH_SITE));
			$jpath_site_real = ($jpath_site_real === false) ? trim(JPATH_SITE) : $jpath_site_real;
			$jpath_site_real = JoomlapackHelperUtils::TranslateWinPath($jpath_site_real);			
		}
		
		if( $jpath_site_real == '' )
		{
			// The JPATH_SITE is resolved to an empty string; attempt a workaround
			
			// Windows hosts
			if(DIRECTORY_SEPARATOR == '\\')
			{
				if( (trim(JPATH_SITE) != '') && (trim(JPATH_SITE) != '\\') && (trim(JPATH_SITE) != '/'))
				{
					$cube->addWarning("The site's root couldn't be normalized on a Windows host. Attempting workaround (filters might not work)");
					$jpath_site_real = JPATH_SITE; // Forcibly use the configured JPATH_SITE
				}
				else
				{
					$cube->addWarning("The normalized path to your site's root seems to be an empty string; I will attempt a workaround (Windows host)");
					$jpath_site_real = '/'; // Start scanning from filesystem root (workaround mode)
				}
			}
			// *NIX hosts
			else
			{
				$cube->addWarning("The normalized path to your site's root seems to be an empty string; I will attempt a workaround (*NIX host)");
				# Fix 2.1 Since JPATH_SITE is an empty string, shouldn't I begin scanning from the FS root, for crying out loud? What was I thinking putting JPATH_SITE there?
				$jpath_site_real = '/'; // Start scanning from filesystem root (workaround mode)
			}
		}

		// Fix 2.4.b1 : Add the trailing slash
		if( (substr($jpath_site_real,-1) != '/') && !empty($jpath_site_real) )
		{
			$jpath_site_real .= '/';
		}
		$this->_directoryList[] = $jpath_site_real; // Start scanning from Joomla! root, as decided above
		$this->_doneScanning = false; // Instruct the class to scan for files and directories
		$this->_doneSubdirectoryScanning = true;
		$this->_doneFileScanning = true;
		$this->_addPath = ''; // No added path for main site
		// Fix 2.4.b1 -- Since JPATH_SITE might have been post-processed, used the post-processed variable instead
		$this->_removePath = $jpath_site_real; // Remove absolute path to site's root for main site

		$this->setState('prepared');

		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainPack :: prepared");
	}

	function _run()
	{
		if ($this->_getState() == 'postrun') {
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainPack :: Already finished");
			$this->_Step = "-";
			$this->_Substep = "";
		}
		else
		{
			if($this->_doneScanning)
			{
				$this->_packSomeFiles();
				if($this->getError()) return false;
			}
			else
			{
				$result = $this->_scanNextDirectory();
				if($this->getError()) return false;
				if(!$result)
				{
					// We have finished with our directory list. Hmm... Do we have extra directories?
					if(count($this->_extraDirs) > 0)
					{
						JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "More EFF definitions detected");
						$registry =& JoomlapackModelRegistry::getInstance();
						// Whack filters (not applicable for off-site directories)
						$this->_ExcludeDirs = array();
						$this->_ExcludeFiles = array();
						$this->_skipContainedDirectories = array();
						$this->_skipContainedFiles = array();
						// Calculate add/remove paths
						$myEntry = array_shift($this->_extraDirs);
						$this->_removePath = $myEntry['fsdir'];
						$this->_addPath = $registry->get('effvfolder').DS.$myEntry['vdir'];
						// Start the filelist building!
						$this->_directoryList[] = $this->_removePath;
						$this->_doneScanning = false; // Make sure we process this file list!
						JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Including new off-site directory to ".$myEntry['vdir']);
					}
					else
					// Nope, we are completely done!
					$this->setState('postrun');
				}
			}
		}
	}

	/**
	 * Implements the _finalize() abstract method
	 *
	 */
	function _finalize()
	{
		JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Finalizing archive");
		$cube =& JoomlapackCUBE::getInstance();
		$provisioning =& $cube->getProvisioning();
		$archive =& $provisioning->getArchiverEngine();
		$archive->finalize();
		// Error propagation
		if($archive->getError())
		{
			$this->setError($archive->getError());
			return false;
		}

		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Archive is finalized");

		$this->setState('finished');
	}

	// ============================================================================================
	// PRIVATE METHODS
	// ============================================================================================

	/**
	 * Loads the exclusion filters off the db and caches them inside the object
	 */
	function _loadAndCacheFilters() {
		jpimport('core.utility.filtermanager');

		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainPack :: Initializing filter manager");

		$filterManager = new JoomlapackCUBEFilterManager();
		$filterManager->init();
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainPack :: Getting Directory Exclusion Filters");
		$this->_ExcludeDirs = $filterManager->getFilters('folder');
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainPack :: Getting Single File Filters");
		$this->_ExcludeFiles = $filterManager->getFilters('singlefile');
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainPack :: Getting Contained Files Filters");
		$this->_skipContainedFiles = $filterManager->getFilters('containedfiles');
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainPack :: Getting Contained Directories Filters");
		$this->_skipContainedDirectories = $filterManager->getFilters('containeddirectories');
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackCUBEDomainPack :: Done with filter manager");
		unset($filterManager);
	}

	/**
	 * Scans a directory for files and directories, updating the _directoryList and _fileList
	 * private fields
	 *
	 * @return bool True if more work has to be done, false if the dirextory stack is empty
	 */
	function _scanNextDirectory( )
	{
		// Are we supposed to scan for more files?
		if( $this->_doneScanning ) return true;

		// Get the next directory to scan, if the folders and files of the last directory
		// have been scanned.
		if($this->_doneSubdirectoryScanning && $this->_doneFileScanning)
		{
			if( count($this->_directoryList) == 0 )
			{
				// No directories left to scan
				return false;
			}
			else
			{
				// Get and remove the last entry from the $_directoryList array
				$this->_dirName = array_pop($this->_directoryList);
				$this->_Step = $this->_dirName;
				$this->_doneSubdirectoryScanning = false;
				$this->_doneFileScanning = false;
				$this->_processedFiles = 0;
			}
		}

		$cube =& JoomlapackCUBE::getInstance();
		$provisioning =& $cube->getProvisioning();
		$engine =& $provisioning->getListerEngine();

		// Scan subdirectories, if they have not yet been scanned.
		if(!$this->_doneSubdirectoryScanning)
		{
			// Apply DEF (directory exclusion filters)
			if (in_array( $this->_dirName, $this->_ExcludeDirs )) {
				JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Skipping directory ".$this->_dirName);
				$this->_doneSubdirectoryScanning = true;
				$this->_doneFileScanning = true;
				return true;
			}
				
			// Apply Skip Contained Directories Filters
			if (in_array( $this->_dirName, $this->_skipContainedDirectories )) {
				JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Skipping subdirectories of directory ".$this->_dirName);
				$this->_doneSubdirectoryScanning = true;
			}
			else
			{
				JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Scanning directories of ".$this->_dirName);
				// Get subdirectories
				$subdirs = $engine->getFolders($this->_dirName);

				// If the list contains "too many" items, please break this step!
				if($engine->BREAKFLAG)
				{
					// Unset the BREAKFLAG of the engine
					$engine->BREAKFLAG = false;
					// Set the BREAKFLAG of our class
					$this->setBreakFlag();
					// Log this decision, for debugging reasons
					JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Large directory ".$this->_dirName." while scanning for subdirectories; I will resume scanning in next step.");
					// Return immediately, marking that we are not done yet!
					return true;
				}

				// Error propagation
				if($engine->getError())
				{
					$this->setError($engine->getError());
					return false;
				}

				if(!empty($subdirs) && is_array($subdirs))
				{
					$registry =& JoomlapackModelRegistry::getInstance();
					$dereferencesymlinks = $registry->get('dereferencesymlinks');
					if($dereferencesymlinks)
					{
						// Treat symlinks to directories as actual directories
						foreach($subdirs as $subdir)
						{
							$this->_directoryList[] = $subdir;
						}
					}
					else
					{
						// Treat symlinks to directories as simple symlink files (ONLY WORKS WITH CERTAIN ARCHIVERS!)
						foreach($subdirs as $subdir)
						{
							if(is_link($subdir))
							{
								JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, 'Symlink found: '.$subdir);
								$this->_fileList[] = $subdir;
							}
							else
							{
								$this->_directoryList[] = $subdir;
							}
						}
					}
				}
			}
				
			$this->_doneSubdirectoryScanning = true;
			return true; // Break operation
		}

		// If we are here, we have not yet scanned the directory for files, so there
		// is no need to test for _doneFileScanning (saves a tiny amount of CPU time)

		// Apply Skipfiles, a.k.a. CFF (Contained Files Filter)
		if (in_array( $this->_dirName, $this->_skipContainedFiles )) {
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Skipping files of directory ".$this->_dirName);
			// Try to find and include .htaccess and index.htm(l) files
			jimport('joomla.filesystem.file');
			// # Fix 2.4: Do not add DS if we are on the site's root and it's an empty string
			$ds = ($this->_dirName == '') || ($this->_dirName == '/') ? '' : DS;
			$checkForTheseFiles = array(
				$this->_dirName.$ds.'.htaccess',
				$this->_dirName.$ds.'index.html',
				$this->_dirName.$ds.'index.htm',
				$this->_dirName.$ds.'robots.txt'
			);
			$this->_processedFiles = 0;
			foreach($checkForTheseFiles as $fileName)
			{
				if(JFile::exists($fileName))
				{
					$this->_fileList[] = $fileName;
					$this->_processedFiles++;
				}
			}
			$this->_doneFileScanning = true;
		}
		else
		{
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Scanning files of ".$this->_dirName);
			// Get file listing
			$fileList =& $engine->getFiles( $this->_dirName );
				
			// If the list contains "too many" items, please break this step!
			if($engine->BREAKFLAG)
			{
				// Unset the BREAKFLAG of the engine
				$engine->BREAKFLAG = false;
				// Set the BREAKFLAG of our class
				$this->setBreakFlag();
				// Log this decision, for debugging reasons
				JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Large directory ".$this->_dirName." while scanning for files; I will resume scanning in next step.");
				// Return immediately, marking that we are not done yet!
				return true;
			}

			// Error propagation
			if($engine->getError())
			{
				$this->setError($engine->getError());
				return false;
			}
				
			$this->_processedFiles = 0;
				
			if (($fileList === false)) {
				// A non-browsable directory; however, it seems that I never get FALSE reported here?!
				$cube->addWarning(JText::sprintf('CUBE_WARN_UNREADABLEDIR', $this->_dirName));
			}
			else
			{
				if(is_array($fileList) && !empty($fileList))
				{
					// Scan all directory entries
					foreach($fileList as $fileName) {
						$skipThisFile = is_array($this->_ExcludeFiles) ? in_array( $fileName, $this->_ExcludeFiles ) : false;
						if ($skipThisFile) {
							JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Skipping file $fileName");
						} else {
							$this->_fileList[] = $fileName;
							$this->_processedFiles++;
						}
					} // end foreach
				} // end if
			} // end filelist not false
				
			$this->_doneFileScanning = true;
		}

		// Check to see if there were no contents of this directory added to our search list
		if ( $this->_processedFiles == 0 ) {
			$archiver =& $provisioning->getArchiverEngine();
			$archiver->addFile($this->_dirName, $this->_removePath, $this->_addPath);
				
			if($archiver->getError())
			{
				$this->setError($archiver->getError());
				return false;
			}
				
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Empty directory ".$this->_dirName);
			unset($archiver);
				
			$this->_doneScanning = false; // Because it was an empty dir $_fileList is empty and we have to scan for more files
		}
		else
		{
			// Next up, add the files to the archive!
			$this->_doneScanning = true;
		}

		// We're done listing the contents of this directory
		unset($engine);
		unset($provisioning);
		unset($cube);

		return true;
	}

	/**
	 * Try to pack some files in the $_fileList, restraining ourselves not to reach the max
	 * number of files or max fragment size while doing so. If this process is over and we are
	 * left without any more files, reset $_doneScanning to false in order to instruct the class
	 * to scan for more files.
	 *
	 * @return bool True if there were files packed, false otherwise (empty filelist)
	 */
	function _packSomeFiles()
	{
		if( count($this->_fileList) == 0 )
		{
			// No files left to pack -- This should never happen! We catch this condition at the end of this method!
			$this->_doneScanning = false;
			return false;
		}
		else
		{
			$packedSize = 0;
			$numberOfFiles = 0;

			$cube =& JoomlapackCUBE::getInstance();
			$provisioning =& $cube->getProvisioning();
			$archiver =& $provisioning->getArchiverEngine();
			$algoRunner =& JoomlapackCUBEAlgorunner::getInstance();
				
			list($usec, $sec) = explode(" ", microtime());
			$opStartTime = ((float)$usec + (float)$sec);
				
			while( (count($this->_fileList) > 0) && ($packedSize <= JPMaxFragmentSize) && ($numberOfFiles <= JPMaxFragmentFiles) )
			{
				$file = @array_shift($this->_fileList);
				$size = @filesize($file);
				// JoomlaPack 2.2: Anticipatory fragment size algorithm
				if( ($packedSize + $size > JPMaxFragmentSize) && ($numberOfFiles > 0) )
				{
					// Adding this file exceeds the fragment's capacity. Furthermore, it's NOT
					// the first file we tried to pack. Therefore, push it back to the list.
					array_unshift($this->_fileList, $file);
					// If the file is bigger than a whole fragment's allowed size, break the step
					// to avoid potential timeouts
					if($size > JPMaxFragmentSize)
					{
						JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Breaking step _before_ large file: ".$file." - size: ".$size);
						$this->setBreakFlag();
					}
					// Mark that we are not done packing files
					$this->_doneScanning = true;
					return true;
				}

				// JoomlaPack 2.2: Proactive potential timeout detection
				// Rough estimation of packing speed in bytes per second
				list($usec, $sec) = explode(" ", microtime());
				$opEndTime = ((float)$usec + (float)$sec);
				if( ($opEndTime - $opStartTime) == 0 )
				{
					$_packSpeed = 0;
				}
				else
				{
					$_packSpeed = $packedSize / ($opEndTime - $opStartTime);
				}
				// Estimate required time to pack next file. If it's the first file of this operation,
				// do not impose any limitations.
				$_reqTime = ($_packSpeed - 0.01) <= 0 ? 0 : $size / $_packSpeed;
				// Do we have enough time?
				if($algoRunner->getTimeLeft() < $_reqTime )
				{
					array_unshift($this->_fileList, $file);
					JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Proactive step break - file: ".$file." - size: ".$size);
					$this->setBreakFlag();
					$this->_doneScanning = true;
					return true;
				}

				$packedSize += $size;
				$numberOfFiles++;
				$archiver->addFile($file, $this->_removePath, $this->_addPath);
				// Error propagation
				if($archiver->getError())
				{
					$this->setError($archiver->getError());
					return false;
				}

				// If this was the first file of the fragment and it exceeded the fragment's capacity,
				// break the step. Continuing with more operations after packing such a big file is
				// increasing the risk to hit a timeout.
				if( ($packedSize > JPMaxFragmentSize) && ($numberOfFiles == 1) )
				{
					JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Breaking step *after* large file: ".$file." - size: ".$size);
					$this->setBreakFlag();
					return true;
				}
			}
				
			$this->_doneScanning = count($this->_fileList) > 0;
			return true;
		}
	}

}