<?php
/**
 * @package		JoomlaPack
 * @copyright	Copyright (C) 2006-2009 JoomlaPack Developers. All rights reserved.
 * @version		$Id$
 * @license 		http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @since		1.2.1
 *
 * JoomlaPack is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 **/

// ensure this file is being included by a parent file - Joomla! 1.0.x and 1.5 compatible
defined('_JEXEC') or die('Restricted access');

if (function_exists('php_uname'))
define('JPISWINDOWS', stristr(php_uname(), 'windows'));
else
define('JPISWINDOWS', DS == '\\');

/**
 * Default File Lister Engine, a.k.a. "Pure PHP File Lister Engine"
 * Formerly known as the Filesystem Abstraction Module. Provides pure PHP filesystem scanner
 * functionality in a compatible manner, depending on server's capabilities.
 */
class JoomlapackListerAbstraction extends JoomlapackCUBELister {

	/**
	 * Should we use glob() ?
	 * @var boolean
	 */
	var $_globEnable;

	/**
	 * Holds the name of the current directory whose contents are loaded
	 *
	 * @var string
	 */
	var $_currentDir;

	/**
	 * Holds the entire contents (files and folders) of the current directory
	 *
	 * @var array
	 */
	var $_currentDirList;

	/**
	 * Public constructor for JoomlapackListerAbstraction class. Does some heuristics to figure out the
	 * server capabilities and setup internal variables
	 */
	function JoomlapackListerAbstraction()
	{
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Initializing ".__CLASS__);
		// Don't use glob if it's disabled or if opendir is available
		$this->_globEnable = function_exists('glob');
		if( function_exists('opendir') && function_exists('readdir') && function_exists('closedir') )
		$this->_globEnable = false;
		$this->_currentDir = null;
		$this->_currentDirList = null;
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, __CLASS__." is initialized.");
	}

	/**
	 * Get the list of files for a given folder
	 *
	 * @param string $folder
	 * @return array
	 */
	function &getFiles($folder)
	{
		$this->_conditionalReload($folder);

		// Post process
		$ret = array();
		if(!empty($this->_currentDirList))
		{
			foreach($this->_currentDirList as $file)
			{
				// Filter out . and .. directories
				$addme = true;
				$lastslash = strrpos($file,'/'); // NOTE: I don't use DS because I translate Windows filenames to UNIX notation first!
				if($lastslash!==false)
				{
					$foldername = substr($file,++$lastslash,strlen($file) - $lastslash);
					$addme = $addme && !($foldername=='.');
					$addme = $addme && !($foldername=='..');
				}
				if($addme)
				{
					if(is_file($file))
					{
						$ret[] = $file;
					}
				}
			}
		}
		elseif($this->_currentDirList === false)
		{
			$ret = false;
		}
			
		return $ret;
	}

	/**
	 * Get the list of subdirectories for a given folder
	 *
	 * @param string $folder
	 * @return array
	 */
	function &getFolders($folder)
	{
		$this->_conditionalReload($folder);

		// Post process
		$ret = array();
		if(!empty($this->_currentDirList))
		{
			foreach($this->_currentDirList as $file)
			{
				// Filter out . and .. directories
				$addme = true;
				$lastslash = strrpos($file,'/'); // NOTE: I don't use DS because I translate Windows filenames to UNIX notation first!
				if($lastslash!==false)
				{
					$foldername = substr($file,++$lastslash,strlen($file) - $lastslash);
					$addme = $addme && !($foldername=='.');
					$addme = $addme && !($foldername=='..');
				}
				if($addme)
				{
					if(is_dir($file))
					{
						$ret[] = $file;
					}
				}
			}
		}
		elseif($this->_currentDirList === false)
		{
			$ret = false;
		}

		return $ret;
	}

	/**
	 * Reload the contents of a folder if necessary
	 *
	 * @param string $folder
	 * @access private
	 */
	function _conditionalReload($folder)
	{
		// Determine if we have to load the dir contents again
		if(is_null($this->_currentDir))
		{
			$this->_currentDirList = null;
		}
		elseif( $this->_currentDir != $folder )
		{
			$this->_currentDir = $folder;
			$this->_currentDirList = null;
		}
			
		// Load the directory contents if necessary
		if(is_null($this->_currentDirList))
		{
			$this->_currentDirList =& $this->_getDirContents($folder);
		}
	}

	/**
	 * Searches the given directory $dirName for files and folders and returns a multidimensional array.
	 * If the directory is not accessible, returns FALSE
	 *
	 * @param string $dirName
	 * @param string $shellFilter
	 * @return array See function description for details
	 */
	function &_getDirContents( $dirName, $shellFilter = null )
	{
		if ($this->_globEnable) {
			$ret = $this->_getDirContents_glob( $dirName, $shellFilter );
		} else {
			$ret = $this->_getDirContents_opendir( $dirName, $shellFilter );
		}

		return $ret;
	}

	// ============================================================================
	// PRIVATE SECTION
	// ============================================================================

	/**
	 * Searches the given directory $dirName for files and folders and returns a multidimensional array.
	 * If the directory is not accessible, returns FALSE. This function uses the PHP glob() function.
	 * @return array See function description for details
	 */
	function _getDirContents_glob( $dirName, $shellFilter = null )
	{
		// # Fix 2.4.b1: Do not add DS if we are on the site's root and it's an empty string
		$dirName = ($this->_dirName == '') || ($this->_dirName == '/') ? '' : $dirName;
		// Fix 2.4.b2: Do not add double slash
		$ds = ($dirName == '') || ($dirName == '/') ||
			(@substr($dirName, -1) == '/') || (@substr($dirName, -1) == DS) ? '' : '/';

		if (is_null($shellFilter)) {
			// Get folder contents
			$allFilesAndDirs1 = @glob($dirName.$ds."*"); // regular files
			$allFilesAndDirs2 = @glob($dirName.$ds.".*"); // *nix hidden files

			// Try to merge the arrays
			if ($allFilesAndDirs1 === false) {
				if ($allFilesAndDirs2 === false) {
					$allFilesAndDirs = false;
				} else {
					$allFilesAndDirs = $allFilesAndDirs2;
				}
			} elseif ($allFilesAndDirs2 === false) {
				$allFilesAndDirs = $allFilesAndDirs1;
			} else {
				$allFilesAndDirs = @array_merge($allFilesAndDirs1, $allFilesAndDirs2);
			}

			// Free unused arrays
			unset($allFilesAndDirs1);
			unset($allFilesAndDirs2);

		} else {
			$allFilesAndDirs = @glob($dirName.$ds.$shellFilter); // filtered files
		}

		// Check for unreadable directories
		if ( $allFilesAndDirs === FALSE ) {
			$false = false;
			return $false;
		}

		// Populate return array
		$retArray = array();

		// FIX 2.0: Run TranslateWinPath only when it's necessary
		if (JPISWINDOWS)
		{
			foreach($allFilesAndDirs as $filename)
			$retArray[] = JoomlapackHelperUtils::TranslateWinPath( $filename );
		}
		else
		{
			$retArray =& $allFilesAndDirs;
		}

		return $retArray;
	}

	function _getDirContents_opendir( $dirName, $shellFilter = null )
	{
		$handle = @opendir( $dirName );

		// If directory is not accessible, just return FALSE
		if ($handle === FALSE) {
			$cube =& JoomlapackCUBE::getInstance();
			$cube->addWarning( 'Unreadable directory '.$dirName);
			$false = false;
			return $false;
		}

		// Initialize return array
		$retArray = array();

		// FIX 1.2.1 -- Remove trailing slash -- Rolled back as of 2.4.b2
		# if( (substr($dirName,-1,1) == '/') || (substr($dirName,-1,1) == '\\')) $dirName = substr($dirName,0,strlen($dirName)-1);

		// Fix 2.4.b2: Do not add double slash
		$ds = ($dirName == '') || ($dirName == '/') ||
			(@substr($dirName, -1) == '/') || (@substr($dirName, -1) == DS) ? '' : '/';

		while( !( ( $filename = readdir($handle) ) === false) ) {
			$match = is_null( $shellFilter );
			$match = (!$match) ? fnmatch($shellFilter, $filename) : true;
			if ($match) {
				$retArray[] = JPISWINDOWS ? JoomlapackHelperUtils::TranslateWinPath( $dirName . $ds . $filename ) : $dirName . $ds . $filename;
			}
		}

		@closedir($handle);
		return $retArray;
	}
}

// FIX 1.1.0 -- fnmatch not available on non-POSIX systems
// Thanks to soywiz@php.net for this usefull alternative function [http://gr2.php.net/fnmatch]
if (!function_exists('fnmatch')) {
	function fnmatch($pattern, $string) {
		return @preg_match(
			'/^' . strtr(addcslashes($pattern, '/\\.+^$(){}=!<>|'),
		array('*' => '.*', '?' => '.?')) . '$/i', $string
		);
	}
}