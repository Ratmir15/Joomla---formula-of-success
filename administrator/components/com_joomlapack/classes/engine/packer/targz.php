<?php
/**
 * @package		JoomlaPack
 * @copyright	Copyright (C) 2006-2009 JoomlaPack Developers. All rights reserved.
 * @version		$Id$
 * @license 		http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @since		2.1
 *
 * JoomlaPack is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 *
 * GZipped TAR Creation Module
 *
 * Creates a TAR file (originally uncompressed) using the PEAR library and the
 * Archive_Tar module, then compresses it using the gzip system command
 */

defined('_JEXEC') or die('Restricted access');

jpimport('engine.packer.tar'); // Load the ancestor class

class JoomlapackPackerTARGZ extends JoomlapackPackerTAR {
	/**
	 * The final compressed archive's location
	 * @var string
	 */
	var $_archiveFilename;

	/**
	 * The temporary uncompressed archive's location
	 * @var string
	 */
	var $_tempFilename;

	/**
	 * Class constructor - initializes internal operating parameters
	 *
	 * @return JoomlapackPackerTARGZ The class instance
	 */
	function JoomlapackPackerTARGZ()
	{
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackPackerTARGZ :: New instance");
	}

	/**
	 * Initialises the archiver class, creating the archive from an existent
	 * installer's JPA archive.
	 *
	 * @param string $sourceJPAPath Absolute path to an installer's JPA archive
	 * @param string $targetArchivePath Absolute path to the generated archive
	 * @param array $options A named key array of options (optional). This is currently not supported
	 * @access public
	 */
	function initialize( $sourceJPAPath, $targetArchivePath, $options = array() )
	{
		// Store final archive's location
		$this->_archiveFilename = $targetArchivePath;
		// Register temporary file
		$baseTempName = basename($targetArchivePath); // Get filename minus path
		$baseTempName = substr($baseTempName, 0, strlen($baseTempName) - 3); // Remove .gz extension
		$this->_tempFilename = JoomlapackCUBETempfiles::registerTempFile($baseTempName);
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackPackerTARGZ :: Registered temporary uncompressed archive ".$this->_tempFilename);
		// Call JoomlapackPackerTAR's initialise() method...
		parent::initialize($sourceJPAPath, $this->_tempFilename, $options);
	}

	/**
	 * Finalises the archive by compressing it. Overrides parent's method
	 * @return boolean TRUE on success, FALSE on failure
	 */
	function finalize()
	{
		// Get gzip's binary location
		$registry = JoomlapackModelRegistry::getInstance();
		$gzip = escapeshellcmd($registry->get('gzipbinary'));
		 
		// Construct and run command line
		$command = "$gzip ".escapeshellcmd($this->_tempFilename);
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackPackerTARGZ :: Calling gzip. The command line is:");
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, $command);
		$result = shell_exec($command);
		// Normally, gzip should be silent as a fish. If anything was sput out,
		// there must have been an error.
		if( (strlen(trim($result)) > 0) )
		{
			$errorMessage = "Error calling gzip: ".$result." \n Command line was: \n ".$command." \n Please check file permissions and examine the result message for any hints regarding the problem tar faced archiving your files.";
			$this->setError($errorMessage);
			return false;
		}

		// Now, unregister the temp file (which no longer exists), register the gzipped file as
		// a new temp file and try to move it
		JoomlapackCUBETempfiles::unregisterAndDeleteTempFile($this->_tempFilename);
		$this->_tempFilename = JoomlapackCUBETempfiles::registerTempFile(basename($this->_archiveFilename));
		copy($this->_tempFilename, $this->_archiveFilename);
		JoomlapackCUBETempfiles::unregisterAndDeleteTempFile($this->_tempFilename);

		// If no errors occured, return true
		return true;
	}


}