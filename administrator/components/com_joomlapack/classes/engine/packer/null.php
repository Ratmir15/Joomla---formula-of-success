<?php
/**
 * @package		JoomlaPack
 * @copyright	Copyright (C) 2006-2009 JoomlaPack Developers. All rights reserved.
 * @version		$Id$
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @since		2.3
 *
 * JoomlaPack is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 **/
defined('_JEXEC') or die('Restricted access');

jpimport('core.utility.tempfiles');

/**
 * Null backup engine
 *
 * Does not perform a backup, it only logs the filenames which *should* be
 * backed up and if they are readable or not. It is designed for troubleshooting
 * only.
 * @author Nicholas K. Dionysopoulos
 *
 */
class JoomlapackPackerNull extends JoomlapackCUBEArchiver
{
	// ------------------------------------------------------------------------
	// Implementation of abstract methods
	// ------------------------------------------------------------------------

	/**
	 * Initialises the archiver class, seeding from an existent installer's JPA archive.
	 *
	 * @param string $sourceJPAPath Absolute path to an installer's JPA archive
	 * @param string $targetArchivePath Absolute path to the generated archive (ignored in this class)
	 * @param array $options A named key array of options (optional)
	 * @access public
	 */
	function initialize( $sourceJPAPath, $targetArchivePath, $options = array() )
	{
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, __CLASS__." :: new instance");

		// No setup!

		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, __CLASS__." :: Initializing with $sourceJPAPath");

		parent::initialize($sourceJPAPath, $targetArchivePath, $options);
	}

	function finalize()
	{
		// Really does nothing...
	}

	/**
	 * The most basic file transaction: add a single entry (file or directory) to
	 * the archive.
	 *
	 * @param bool $isVirtual If true, the next parameter contains file data instead of a file name
	 * @param string $sourceNameOrData Absolute file name to read data from or the file data itself is $isVirtual is true
	 * @param string $targetName The (relative) file name under which to store the file in the archive
	 * @return True on success, false otherwise
	 * @since 1.2.1
	 * @access protected
	 * @abstract
	 */
	function _addFile( $isVirtual, &$sourceNameOrData, $targetName )
	{
		/*
		$isReadable = $isVirtual ? true : @is_readable( $sourceNameOrData );
		$isDir = $isVirtual ? false : @is_dir($sourceNameOrData);

		if( $isVirtual )
		{
			$filesize = strlen($sourceNameOrData);
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "+ [VIRTUAL] $targetName ($filesize)");
		}
		else
		{
			$tag = $isDir ? '[  DIR  ]' : '[ FILE  ]';
			if(!$isReadable)
			{
				$cube =& JoomlapackCUBE::getInstance();
				$cube->addWarning("+ $tag $targetName (UNREADABLE!)");
			}
			elseif($isDir)
			{
				JoomlapackLogger::WriteLog(_JP_LOG_INFO, "+ $tag $targetName");
			}
			else
			{
				$filesize = @filesize($sourceNameOrData);
				JoomlapackLogger::WriteLog(_JP_LOG_INFO, "+ $tag $targetName ($filesize)");
			}
		}
		*/
	}
}