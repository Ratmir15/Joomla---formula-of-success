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
 * TAR Creation Module
 *
 * Creates a TAR file (uncompressed) using the PEAR library and the Archive_Tar module
 */
defined('_JEXEC') or die('Restricted access');

jpimport('misc.Archive_Tar'); // Load the modified Archive_tar library

class JoomlapackPackerTAR extends JoomlapackCUBEArchiver {
	/**
	 * The name of the file holding the ZIP's data, which becomes the final archive
	 *
	 * @var string
	 */
	var $_dataFileName;

	/**
	 * The Archive_Tar object
	 * @var Archive_Tar
	 */
	var $_tarObject;

	// ------------------------------------------------------------------------
	// Implementation of abstract methods
	// ------------------------------------------------------------------------

	/**
	 * Class constructor - initializes internal operating parameters
	 *
	 * @return JoomlapackPackerTAR The class instance
	 */
	function JoomlapackPackerTAR()
	{
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackPackerTAR :: New instance");
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
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackPackerTAR :: initialize - archive $targetArchivePath");

		// Get names of temporary files
		$configuration =& JoomlapackModelRegistry::getInstance();
		$this->_dataFileName = $targetArchivePath;

		// Try to kill the archive if it exists
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackPackerTAR :: Killing old archive");
		$fp = fopen( $this->_dataFileName, "wb" );
		if (!($fp === false)) {
			ftruncate( $fp,0 );
			fclose( $fp );
		} else {
			@unlink( $this->_dataFileName );
		}
		if(!@touch( $this->_dataFileName ))
		{
			$this->setError(JText::_('CUBE_ARCHIVER_CANTWRITE'));
			return false;
		}

		$this->_tarObject = new Archive_Tar($targetArchivePath);
		$this->_tarObject->owningObject =& $this;

		parent::initialize($sourceJPAPath, $targetArchivePath, $options);
	}

	/**
	 *
	 * @return boolean TRUE on success, FALSE on failure
	 */
	function finalize()
	{
		return true;
	}

	/**
	 * The most basic file transaction: add a single entry (file or directory) to
	 * the archive.
	 *
	 * @param bool $isVirtual If true, the next parameter contains file data instead of a file name
	 * @param string $sourceNameOrData Absolute file name to read data from or the file data itself is $isVirtual is true
	 * @param string $targetName The (relative) file name under which to store the file in the archive
	 * @return True on success, false otherwise
	 * @since 2.1
	 * @access protected
	 * @abstract
	 */
	function _addFile( $isVirtual, &$sourceNameOrData, $targetName )
	{
		if ($isVirtual)
		{
			// VIRTUAL FILES
				
			// Create and register temp file with the virtual contents
			$tempFileName = JoomlapackCUBETempfiles::registerTempFile( basename($targetName) );
			if(function_exists('file_put_contents'))
			file_put_contents($tempFileName, $sourceNameOrData); // PHP5 way to do it
			else
			{
				$tempHandler = fopen($tempFileName, 'wb');
				$this->_fwrite($tempHandler, $sourceNameOrData);
				fclose($tempHandler);
			}
				
			// Calculate add / remove paths
			$removePath = dirname($tempFileName);
			$addPath = dirname($targetName);
				
			// Add the file
			$this->_tarObject->addModify($tempFileName, $addPath, $removePath, $tempFileName);
				
			// Remove the temporary file
			JoomlapackCUBETempfiles::unregisterAndDeleteTempFile(basename($targetName));
		}
		else
		{
			// REGULAR FILES
			if($targetName == '') $targetName = $sourceNameOrData;
			$this->_tarObject->addModify($sourceNameOrData, '', JPATH_SITE, $targetName);
		}
	}
}