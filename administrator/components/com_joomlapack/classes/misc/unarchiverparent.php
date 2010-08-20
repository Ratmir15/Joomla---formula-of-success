<?php

// Protect from direct execution
defined('_JEXEC') or die('Restricted Access');

/**
 * The parent class of JoomlaPack's archive extraction classes
 * @author Nicholas K. Dionysopoulos
 */
class UnarchiverParent
{
	// Variables set in the configuration
	
	/** @var string The archive's filename */
	var $_filename;
	
	/** @var bool Should we try to restore permissions? */
	var $_flagRestorePermissions = false;
	
	/** @var bool Should we attempt to use FTP mode? If so, we need the FTP class to be set as well */
	var $_flagUseFTP = false;
	
	/** @var string A path to prepend to all stored names */
	var $_addPath = '';
	
	/** @var object An FTP class used for FTP operations */
	var $_ftp = null;
	
	/** @var Array An associative array holding mappings of filenames which should be renamed */
	var $_renameFiles = array();
	
	/** @var Array An indexed array of files which should be skipped during extraction */
	var $_skipFiles = array();
	
	/** @var bool Should I use the JText translation class? */
	var $_flagTranslate = false;
	
	/** @var int Chunk size for uncompressed file copying operations */
	var $_chunkSize = 524288;
	
	// Internal variables
	
	/** @var int The current part number we are reading from */
	var $_currentPart = '';
	
	/** @var bool Did we encounter an error? */
	var $_isError = false;
	
	/** @var string The latest error message */
	var $_error = '';
	
	/** @var Array A hash array of archive parts and their sizes */
	var $_archiveList = array();
	
	/** @var int Total size of archive's parts */
	var $_totalSize = 0;
	
	/** @var resource File pointer to the current part we are reading from */
	var $_fp;
	
	/**
	 * Object constructor for PHP 4 installations
	 * @return UnarchiverAbstract
	 */
	function UnarchiverParent()
	{
		$args = func_get_args();
		call_user_func_array(array(&$this, '__construct'), $args);		
	}
	
	function Extract( $offset = null )
	{
		// Placeholder; implemented in descending classes
		return false;
	}
	
	function getError()
	{
		if(!empty($this->_error))
		{
			return $this->_error;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Public constructor. Takes an associative array of parameters as input.
	 * @param $options An array of options
	 * @return UnarchiverAbstract
	 */
	function __construct( $options = array() )
	{
		if( count($options) > 0 )
		{
			foreach($options as $key => $value)
			{
				switch($key)
				{
					case 'filename': // Archive's absolute filename
						$this->_filename = $value;
						break;
						
					case 'restore_permissions': // Should I restore permissions?
						$this->_flagRestorePermissions = $value;
						break;
						
					case 'use_ftp': // Should I use FTP?
						$this->_flagUseFTP = $value;
						break;
						
					case 'add_path': // Path to prepend
						$this->_addPath = $value;
						break;

					case 'ftp': // The FTP obejct
						$this->_ftp = $value;
						break;
						
					case 'rename_files': // Which files to rename (hash array)
						$this->_renameFiles = $value;
						break;

					case 'skip_files': // Which files to skip (indexed array)
						$this->_skipFiles = $value;
						break;
						
					case 'translate': // SHould I use the JText translation class?
						$this->_flagTranslate = $value;
						break;
				}				
			}
		}
		
		// Get information about the parts
		$this->_scanArchives();
	}
	
	/**
	 * Returns the base extension of the archive file
	 * @return string The base extension, e.g. '.zip'
	 */
	function _getBaseExtension()
	{
		static $baseextension;
		
		if(empty($baseextension))
		{
			$basename = basename($this->_filename);
			$lastdot = strrpos($basename,'.');
			$baseextension = substr($basename, $lastdot);
		}
		
		return $baseextension;
	}
	
	/**
	 * Scans for multiple parts of an archive set
	 */
	function _scanArchives()
	{
		// Get the components of the archive filename
		$dirname = dirname($this->_filename);
		$base_extension = $this->_getBaseExtension();
		$basename = basename($this->_filename, $base_extension);

		// Scan for multiple parts until we don't find any more of them
		$count = 0;
		$found = true;
		$this->_archiveList = array();
		$totalsize = -1;
		while($found)
		{
			$count++;
			$extension = substr($base_extension, 0, 2).sprintf('%02d', $count);
			$filename = $dirname.DIRECTORY_SEPARATOR.$basename.$extension;
			$found = file_exists($filename);
			if($found)
			{
				// Add yet another part, with a numeric-appended filename
				$rec['name'] = $filename;
				$rec['size'] = @filesize($filename);
			}
			else
			{
				// Add the last part, with the regular extension
				$rec['name'] = $this->_filename;
				$rec['size'] = @filesize($this->_filename);
			}
			$rec['start'] = $totalsize + 1;
			$rec['end'] = $rec['start'] + $rec['size'] - 1;
			$totalsize = $rec['end'];
			$this->_archiveList[$count] = $rec;
		}
		
		$this->_totalSize = $totalsize;
		$this->_currentPart = 1; // Default to start reading from the first part
	}
	
	/**
	 * Makes sure that the _fp variable points to the correct file for a given
	 * offset (relative to the beginning of the first file in a split archive set)
	 * and sets the _currentPart accordingly and skips to the correct offset
	 * @return bool True if we could skip to the offset, false otherwise
	 */
	function _skipToOffset( $offset )
	{
		// Let's find in which archive this offset starts
		$found = false;
		$count = 0;
		while( (!$found) && ($count < count($this->_archiveList)) )
		{
			$count++;
			$found = ($this->_archiveList[$count]['start'] <= $offset) &&
					 ($this->_archiveList[$count]['end'] >= $offset);
		}
		
		// Do we have the correct part set?
		if($this->_currentPart != $count)
		{
			// No, set it and mark that we should open the file pointer
			$this->_currentPart = $count;
			$mustOpen = true;	
		}
		else
		{
			// We are on the correct part. Is it open yet?
			$mustOpen = !is_resource($this->_fp);
		}
		
		// Open the part if we have to
		if($mustOpen)
		{
			if(is_resource($this->_fp)) @fclose($this->_fp);
			$this->_fp = @fopen($this->_archiveList[$this->_currentPart]['name'], 'rb');
			if($this->_fp === false) return false;
		}
		
		// Calculate the relative offset
		$relative_offset = $offset - $this->_archiveList[$this->_currentPart]['start'];
		@fseek($this->_fp, $relative_offset);
		
		return true;
	}
	
	/**
	 * Returns the absolute offset (relative to the start of the first part) or
	 * false if this value is not recoverable
	 * @return int|bool The absolute offset, or false if it failed
	 */
	function _getOffset()
	{
		if(is_resource($this->_fp))
		{
			clearstatcache();
			$relative_offset = @ftell($this->_fp);
			if($relative_offset === false) return false;
			return $relative_offset + $this->_archiveList[$this->_currentPart]['start'];
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Sets the _fp file pointer reference to the next file of a split archive
	 * set and updates the _currentPart accordingly
	 * 
	 * @return bool True if successful, false otherwise
	 */
	function _getNextFile()
	{
		if($this->_currentPart < count($this->_archiveList) )
		{
			return $this->_skipToOffset( $this->_archiveList[$this->_currentPart]['end'] + 1 );
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Returns true if we have reached the end of file
	 * @param $local bool True to return EOF of the local file, false (default) to return if we have reached the end of the archive set
	 * @return bool True if we have reached End Of File
	 */
	function _isEOF($local = false)
	{
		$eof = @feof($this->_fp);
		if($local)
		{
			return $eof;
		}
		else
		{
			return $eof && ($this->_currentPart == count($this->_archiveList) );
		}
	}
	
	/**
	 * Tries to make a directory user-writable so that we can write a file to it
	 * @param $path string A path to a file
	 */
	function _setCorrectPermissions($path)
	{
		$directory = dirname($path);

		if(!$this->_flagUseFTP)
		{
			// Direct file writes mode
			if(!is_dir($directory)) return; // Catch not-a-directory cases
			// Get directories and modify owner permissions to read-write-execute
			$perms = decoct(@fileperms($directory));
			$digit = strlen($perms) == 3 ? 0 : (strlen($perms) == 4 ? 1 : 2);
			$perms = substr_replace($perms, '7', $digit, 1);
			@chmod( $directory, octdec($perms) );
			// Also try to chmod the file itself to 0777 if it exists (in order to allow overwriting)
			@chmod( $path, 0777 );
		}
		else
		{
			// FTP mode
			// As a crappy workaround, I default to 0755 permissions. Oh, well...
			$this->_ftp->chmod($directory, 0755);
			$this->_ftp->chmod($path, 0777);
		}
	}
	
	/**
	 * Tries to recursively create the directory $dirName
	 *
	 * @param string $dirName The directory to create
	 * @return boolean TRUE on success, FALSE on failure
	 * @access private
	 */
	function _createDirRecursive( $dirName )
	{
		$dirArray = explode('/', $dirName);
		$path = '';
		foreach( $dirArray as $dir )
		{
			$path .= $dir . '/';
			$ret = is_dir($path) ? true : @mkdir($path);
			if( !$ret ) {
				$this->_isError = true;
				if($this->_flagTranslate)
				{
					$this->_error = JText::sprintf('COULDNT_CREATE_DIR',$path);
				}
				else
				{
					$this->_error = 'Could not create directory '.$path;
				}
				return false;
			}
			// Try to set new directory permissions to 0755
			@chmod($path, 0755);
		}
		return true;
	}
}
?>