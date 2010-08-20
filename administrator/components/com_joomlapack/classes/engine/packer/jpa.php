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
defined('_JEXEC') or die('Restricted access');

$config =& JoomlapackModelRegistry::getInstance();
define("_JoomlapackPackerZIP_FORCE_FOPEN", $config->get('mnZIPForceOpen')); // Don't force use of fopen() to read uncompressed data in memory
define("_JoomlapackPackerZIP_COMPRESSION_THRESHOLD", $config->get('mnZIPCompressionThreshold')); // Don't compress files over this size
define("_JoomlapackPackerZIP_DIRECTORY_READ_CHUNK", $config->get('mnZIPDirReadChunk')); // How much data to read at once when finalizing ZIP archives

define( '_JPA_MAJOR', 1 ); // JPA Format major version number
define( '_JPA_MINOR', 1 ); // JPA Format minor version number

/**
 * JoomlaPack Archive creation class
 *
 * JPA Format 1.0 implemented, minus BZip2 compression support
 */
class JoomlapackPackerJPA extends JoomlapackCUBEArchiver
{
	/**
	 * How many files are contained in the archive
	 *
	 * @var integer
	 */
	var $_fileCount = 0;

	/**
	 * The total size of files contained in the archive as they are stored (it is smaller than the
	 * archive's file size due to the existence of header data)
	 *
	 * @var integer
	 */
	var $_compressedSize = 0;

	/**
	 * The total size of files contained in the archive when they are extracted to disk.
	 *
	 * @var integer
	 */
	var $_uncompressedSize = 0;

	/**
	 * The name of the file holding the ZIP's data, which becomes the final archive
	 *
	 * @var string
	 */
	var $_dataFileName;

	/**
	 * Beginning of central directory record.
	 *
	 * @var string
	 */
	var $_ctrlDirHeader = "\x4A\x50\x41";	// Standard Header signature

	/**
	 * Beginning of file contents.
	 *
	 * @var string
	 */
	var $_fileHeader = "\x4A\x50\x46";	// Entity Block signature
	
	/**
	 * Marks the Split Archive header
	 *
	 * @var string
	 */
	var $_extraHeaderSplit = "\x4A\x50\x01\x01"; // Split archive's extra header

	// Variables for split ZIP
	/** @var bool Should I use Split ZIP? */
	var $_useSplitZIP = false;

	/** @var int Maximum fragment size, in bytes */
	var $_fragmentSize = 0;
	
	/** @var int Current fragment number */
	var $_currentFragment = 1;
	
	/** @var int Total number of fragments */
	var $_totalFragments = 1;
	
	/** @var string Archive full path without extension */
	var $_dataFileNameBase = '';
	
	// Variables for symlink target storage

	/** @var bool Should I store symlinks as such (no dereferencing?) */
	var $_symlink_store_target = false;
	
	// ------------------------------------------------------------------------
	// Implementation of abstract methods
	// ------------------------------------------------------------------------

	/**
	 * Initialises the archiver class, creating the archive from an existent
	 * installer's JPA archive.
	 *
	 * @param string $sourceJPAPath Absolute path to an installer's JPA archive
	 * @param string $targetArchivePath Absolute path to the generated archive
	 * @param array $options A named key array of options (optional)
	 * @access public
	 */
	function initialize( $sourceJPAPath, $targetArchivePath, $options = array() )
	{
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackPackerJPA :: new instance - archive $targetArchivePath");
		$this->_dataFileName = $targetArchivePath;

		// NEW 2.3: Should we enable Split ZIP feature?
		$registry =& JoomlapackModelRegistry::getInstance();
		$fragmentsize = $registry->get('splitpartsize', 0);
		if($fragmentsize >= 65536)
		{
			// If the fragment size is AT LEAST 64Kb, enable Split ZIP
			$this->_useSplitZIP = true;
			$this->_fragmentSize = $fragmentsize;
			// Enable CUBE that we have a multipart archive
			$cube =& JoomlapackCUBE::getInstance();
			$cube->updateMultipart(1); // Indicate that we have at least 1 part
			$this->_totalFragments = 1;
			
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "JoomlapackPackerJPA :: Spanned JPA creation enabled");
			$this->_dataFileNameBase = dirname($targetArchivePath).DS.basename($targetArchivePath,'.jpa');
			$this->_dataFileName = $this->_dataFileNameBase.'.j01'; 
		}
		
		// NEW 2.3: Should I use Symlink Target Storage?
		$dereferencesymlinks = $registry->get('dereferencesymlinks', true);
		if(!$dereferencesymlinks)
		{
			// We are told not to dereference symlinks. Are we on Windows?
			if (function_exists('php_uname'))
			{
				$isWindows = stristr(php_uname(), 'windows');
			}
			else
			{
				$isWindows = (DS == '\\');
			}
			// If we are not on Windows, enable symlink target storage
			$this->_symlink_store_target = !$isWindows;
		}

		// Try to kill the archive if it exists
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackPackerJPA :: Killing old archive");
		$fp = @fopen( $this->_dataFileName, "wb" );
		if (!($fp === false)) {
			@ftruncate( $fp,0 );
			@fclose( $fp );
		} else {
			if( file_exists($this->_dataFileName) ) @unlink( $this->_dataFileName );
			@touch( $this->_dataFileName );
		}
		
		// Write the initial instance of the archive header
		$this->_writeArchiveHeader();
		if($this->getError()) return;

		parent::initialize($sourceJPAPath, $targetArchivePath, $options);
	}

	/**
	 * Updates the Standard Header with current information
	 */
	function finalize()
	{
		// If Spanned JPA and there is no .jpa file, rename the last fragment to .jpa
		if($this->_useSplitZIP)
		{
			$extension = substr($this->_dataFileName, -3);
			if($extension != '.jpa')
			{
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, 'Renaming last JPA part to .JPA extension');
				$newName = $this->_dataFileNameBase.'.jpa';
				if(!@rename($this->_dataFileName, $newName))
				{
					$this->setError('Could not rename last JPA part to .JPA extension.');
					return false;
				}
				$this->_dataFileName = $newName;
			}
			
			// Finally, point to the first part so that we can re-write the correct header information
			if($this->_totalFragments > 1)
			{
				$this->_dataFileName = $this->_dataFileNameBase.'.j01';
			}
		}

		// Re-write the archive header
		$this->_writeArchiveHeader();
		
		if($this->getError()) return;
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
		static $configuration;
		
		$cube =& JoomlapackCUBE::getInstance();

		if(!$configuration)
		{
			jpimport('models.registry', true);
			$configuration =& JoomlapackModelRegistry::getInstance();
		}

		// See if it's a directory
		$isDir = $isVirtual ? false : is_dir($sourceNameOrData);
		// See if it's a symlink (w/out dereference)
		$isSymlink = false;
		if($this->_symlink_store_target)
		{
			$isSymlink = is_link($sourceNameOrData);
		}
		
		// Get real size before compression
		if($isVirtual)
		{
			$fileSize = strlen($sourceNameOrData);
		}
		else
		{
			if($isSymlink)
			{
				$fileSize = strlen( @readlink($sourceNameOrData) );
			}
			else
			{
				$fileSize = $isDir ? 0 : @filesize($sourceNameOrData);
			}
		}

		// Decide if we will compress
		if ($isDir || $isSymlink) {
			$compressionMethod = 0; // don't compress directories...
		} else {
			// Do we have plenty of memory left?
			$memLimit = ini_get("memory_limit");
			if( is_numeric($memLimit) && ($memLimit < 0) ) $memLimit = ""; // 1.2a3 -- Rare case with memory_limit < 0, e.g. -1Mb!
			if (($memLimit == "") || ($fileSize >= _JoomlapackPackerZIP_COMPRESSION_THRESHOLD)) {
				// No memory limit, or over 1Mb files => always compress up to 1Mb files (otherwise it times out)
				$compressionMethod = ($fileSize <= _JoomlapackPackerZIP_COMPRESSION_THRESHOLD) ? 1 : 0;
			} elseif ( function_exists("memory_get_usage") ) {
				// PHP can report memory usage, see if there's enough available memory; Joomla! alone eats about 5-6Mb! This code is called on files <= 1Mb
				$memLimit = $this->_return_bytes( $memLimit );
				$availableRAM = $memLimit - memory_get_usage();
				$compressionMethod = (($availableRAM / 2.5) >= $fileSize) ? 1 : 0;
			} else {
				// PHP can't report memory usage, compress only files up to 512Kb (conservative approach) and hope it doesn't break
				$compressionMethod = ($fileSize <= 524288) ? 1 : 0;
			}
		}

		$compressionMethod = function_exists("gzcompress") ? $compressionMethod : 0;

		$storedName = $targetName;

		/* "Entity Description BLock" segment. */
		$unc_len = &$fileSize; // File size
		$storedName .= ($isDir) ? "/" : "";

		if ($compressionMethod == 1) {
			if($isVirtual)
			{
				$udata =& $sourceNameOrData;
			}
			else
			{
				// Get uncompressed data
				if( function_exists("file_get_contents") && (_JoomlapackPackerZIP_FORCE_FOPEN == false) ) {
					$udata = @file_get_contents( $sourceNameOrData ); // PHP > 4.3.0 saves us the trouble
				} else {
					// Argh... the hard way!
					$udatafp = @fopen( $sourceNameOrData, "rb" );
					if( !($udatafp === false) ) {
						$udata = "";
						while( !feof($udatafp) ) {
							// Keep-alive on file reading
							if($configuration->get("enableMySQLKeepalive", false))
							{
								list($usec, $sec) = explode(" ", microtime());
								$endTime = ((float)$usec + (float)$sec);

								if($endTime - $this->startTime > 0.5)
								{
									$this->startTime = $endTime;
									JoomlapackCUBETables::WriteVar('dummy', 1);
								}
							}
							$udata .= fread($udatafp, JPPACK_CHUNK);
						}
						fclose( $udatafp );
					} else {
						$udata = false;
					}
				}
			}
				
			if ($udata === FALSE) {
				// Unreadable file, skip it.
				$cube->addWarning( JText::sprintf('CUBE_WARN_UNREADABLEFILE', $sourceNameOrData));
				return false;
			} else {
				// Proceed with compression
				$zdata   = @gzcompress($udata);
				if ($zdata === false) {
					// If compression fails, let it behave like no compression was available
					$c_len = &$unc_len;
					$compressionMethod = 0;
				} else {
					unset( $udata );
					$zdata   = substr(substr($zdata, 0, strlen($zdata) - 4), 2);
					$c_len   = strlen($zdata);
				}
			}
		} else {
			$c_len = $unc_len;
		}

		$this->_compressedSize += $c_len; // Update global data
		$this->_uncompressedSize += $fileSize; // Update global data
		$this->_fileCount++;

		// Get file permissions
		$perms = $isVirtual ? 0777 : @fileperms( $sourceNameOrData );

		// Calculate Entity Description Block length
		$blockLength = 21 + strlen($storedName) ;

		// Get file type
		if( (!$isDir) && (!$isSymlink) ) { $fileType = 1; }
			elseif($isSymlink) { $fileType = 2;	}
			elseif($isDir) { $fileType = 0;	}
		
		// If it's a split ZIP file, we've got to make sure that the header can fit in the part
		if($this->_useSplitZIP)
		{
			// Compare to free part space
			clearstatcache();
			$current_part_size = @filesize($this->_dataFileName);
			$free_space = $this->_fragmentSize - ($current_part_size === false ? 0 : $current_part_size);
			if($free_space <= $blockLength)
			{
				// Not enough space on current part, create new part
				if(!$this->_createNewPart())
				{
					$this->setError('Could not create new JPA part file '.basename($this->_dataFileName));
					return false;
				}
			}
		}
			
		// Open data file for output
		$fp = @fopen( $this->_dataFileName, "ab");
		if ($fp === false)
		{
			$this->setError("Could not open archive file '{$this->_dataFileName}' for append!");
			return;
		}
		$this->_fwrite( $fp, $this->_fileHeader ); // Entity Description Block header
		if($this->getError()) return;
		$this->_fwrite( $fp, pack('v', $blockLength) ); // Entity Description Block header length
		$this->_fwrite( $fp, pack('v', strlen($storedName) ) ); // Length of entity path
		$this->_fwrite( $fp, $storedName ); // Entity path
		$this->_fwrite( $fp, pack('C', $fileType ) ); // Entity type
		$this->_fwrite( $fp, pack('C', $compressionMethod ) ); // Compression method
		$this->_fwrite( $fp, pack('V', $c_len ) ); // Compressed size
		$this->_fwrite( $fp, pack('V', $unc_len ) ); // Uncompressed size
		$this->_fwrite( $fp, pack('V', $perms ) ); // Entity permissions

		/* "File data" segment. */
		if ($compressionMethod == 1) {
			if(!$this->_useSplitZIP)
			{
				// Just dump the compressed data
				$this->_fwrite( $fp, $zdata );
				if($this->getError()) return;
			}
			else
			{
				// Split ZIP. Check if we need to split the part in the middle of the data.
				clearstatcache();
				$current_part_size = @filesize($this->_dataFileName);
				$free_space = $this->_fragmentSize - ($current_part_size === false ? 0 : $current_part_size);
				if($free_space >= strlen($zdata) )
				{
					// Write in one part
					$this->_fwrite( $fp, $zdata );
					if($this->getError()) return;
				}
				else
				{
					$bytes_left = strlen($zdata);
					
					while($bytes_left > 0)
					{
						clearstatcache();
						$current_part_size = @filesize($this->_dataFileName);
						$free_space = $this->_fragmentSize - ($current_part_size === false ? 0 : $current_part_size);			

						// Split between parts - Write first part
						$this->_fwrite( $fp, $zdata, $free_space );
						if($this->getError()) return;
						
						// Get the rest of the data
						$bytes_left = strlen($zdata) - $free_space;
						
						if($bytes_left > 0)
						{
							// Create new part
							if(!$this->_createNewPart())
							{
								// Die if we couldn't create the new part
								$this->setError('Could not create new JPA part file '.basename($this->_dataFileName));
								return false;
							}
							else
							{
								// Close the old data file
								fclose($fp);
								// Open data file for output
								$fp = @fopen( $this->_dataFileName, "ab");
								if ($fp === false)
								{
									$this->setError("Could not open archive file {$this->_dataFileName} for append!");
									return false;
								}
							}
							$zdata = substr($zdata, -$bytes_left);
						}
					}
				}
			}
			unset( $zdata );
		} elseif ( (!$isDir) && (!$isSymlink) ) {
			if($isVirtual)
			{
				if(!$this->_useSplitZIP)
				{
					// Just dump the data
					$this->_fwrite( $fp, $sourceNameOrData );
					if($this->getError()) return;
				}
				else
				{
					// Split ZIP. Check if we need to split the part in the middle of the data.
					clearstatcache();
					$current_part_size = @filesize($this->_dataFileName);
					$free_space = $this->_fragmentSize - ($current_part_size === false ? 0 : $current_part_size);
					if($free_space >= strlen($sourceNameOrData) )
					{
						// Write in one part
						$this->_fwrite( $fp, $sourceNameOrData );
						if($this->getError()) return;
					}
					else
					{
						$bytes_left = strlen($sourceNameOrData);
						
						while($bytes_left > 0)
						{
							clearstatcache();
							$current_part_size = @filesize($this->_dataFileName);
							$free_space = $this->_fragmentSize - ($current_part_size === false ? 0 : $current_part_size);
							
							// Split between parts - Write first part
							$this->_fwrite( $fp, $sourceNameOrData, $free_space );
							if($this->getError()) return;

							// Get the rest of the data
							$rest_size = strlen($sourceNameOrData) - $free_space;
							if($rest_size > 0)
							{
								// Create new part
								if(!$this->_createNewPart())
								{
									// Die if we couldn't create the new part
									$this->setError('Could not create new JPA part file '.basename($this->_dataFileName));
									return false;
								}
								else
								{
									// Close the old data file
									fclose($fp);
									// Open data file for output
									$fp = @fopen( $this->_dataFileName, "ab");
									if ($fp === false)
									{
										$this->setError("Could not open archive file {$this->_dataFileName} for append!");
										return false;
									}
								}
								$zdata = substr($sourceNameOrData, -$rest_size);
							}
							$bytes_left = $rest_size;
						} // end while
					}
				}
			}
			else
			{
				// Copy the file contents, ignore directories
				$zdatafp = @fopen( $sourceNameOrData, "rb" );
				if( $zdatafp === FALSE )
				{
					$cube->addWarning(JText::sprintf('CUBE_WARN_UNREADABLEFILE', $sourceNameOrData));
					return false;
				}
				else
				{
					if(!$this->_useSplitZIP)
					{
						while( !feof($zdatafp) ) {
							$zdata = fread($zdatafp, JPPACK_CHUNK);
							$this->_fwrite( $fp, $zdata );
							if($this->getError()) return;
						}
					}
					else
					{
						// Split ZIP - Do we have enough space to host the whole file?
						clearstatcache();
						$current_part_size = @filesize($this->_dataFileName);
						$free_space = $this->_fragmentSize - ($current_part_size === false ? 0 : $current_part_size);
						if($free_space >= $unc_len)
						{
							// Yes, it will fit inside this part, do quick copy
							while( !feof($zdatafp) ) {
								$zdata = fread($zdatafp, JPPACK_CHUNK);
								$this->_fwrite( $fp, $zdata );
								if($this->getError()) return;
							}
						}
						else
						{
							// No, we'll have to split between parts. We'll loop until we run
							// out of space.
							
							while( !feof($zdatafp) )
							{
								clearstatcache();
								$current_part_size = @filesize($this->_dataFileName);
								$free_space = $this->_fragmentSize - ($current_part_size === false ? 0 : $current_part_size);
								// Find optimal chunk size
								$chunk_size_primary = min(JPPACK_CHUNK, $free_space);
								if($chunk_size_primary <= 0) $chunk_size_primary = max(JPPACK_CHUNK, $free_space);
								// Calculate if we have to read some more data (smaller chunk size)
								// and how many times we must read w/ the primary chunk size
								$chunk_size_secondary = $free_space % $chunk_size_primary;
								$loop_times = ($free_space - $chunk_size_secondary) / $chunk_size_primary;
								// Read and write with the primary chunk size
								for( $i = 1; $i <= $loop_times; $i++ )
								{
									$zdata = fread($zdatafp, $chunk_size_primary);
									$this->_fwrite( $fp, $zdata );
									if($this->getError()) return;
								}
								// Read and write w/ secondary chunk size, if non-zero
								if($chunk_size_secondary > 0)
								{
									$zdata = fread($zdatafp, $chunk_size_secondary);
									$this->_fwrite( $fp, $zdata );
									if($this->getError()) return;
								}
								// Create new JPA part, but only if we'll have more data to write
								if(!feof($zdatafp))
								{
									if(!$this->_createNewPart())
									{
										// Die if we couldn't create the new part
										$this->setError('Could not create new JPA part file '.basename($this->_dataFileName));
										return false;
									}
									else
									{
										// Close the old data file
										fclose($fp);
										// Open data file for output
										$fp = @fopen( $this->_dataFileName, "ab");
										if ($fp === false)
										{
											$this->setError("Could not open archive file {$this->_dataFileName} for append!");
											return false;
										}
									}
								}
							} // end while							
						}
					}
					fclose( $zdatafp );
				}				
			}
		}
		elseif($isSymlink)
		{
			$this->_fwrite($fp, @readlink($sourceNameOrData) );
		}
		
		fclose( $fp );

		// ... and return TRUE = success
		return TRUE;
	}


	// ------------------------------------------------------------------------
	// Archiver-specific utility functions
	// ------------------------------------------------------------------------
	/**
	* Outputs a Standard Header at the top of the file
	*
	*/
	function _writeArchiveHeader()
	{
		$fp = @fopen( $this->_dataFileName, 'r+' );
		if($fp === false)
		{
			$this->setError('Could not open '.$this->_dataFileName.' for writing. Check permissions and open_basedir restrictions.');
			return;
		}
		
		// Calculate total header size
		$headerSize = 19; // Standard Header
		if($this->_useSplitZIP) $headerSize += 8; // Spanned JPA header
		
		$this->_fwrite( $fp, $this->_ctrlDirHeader );					// ID string (JPA)
		if($this->getError()) return;
		$this->_fwrite( $fp, pack('v', $headerSize) );					// Header length; fixed to 19 bytes
		$this->_fwrite( $fp, pack('C', _JPA_MAJOR ) );					// Major version
		$this->_fwrite( $fp, pack('C', _JPA_MINOR ) );					// Minor version
		$this->_fwrite( $fp, pack('V', $this->_fileCount ) );			// File count
		$this->_fwrite( $fp, pack('V', $this->_uncompressedSize ) );	// Size of files when extracted
		$this->_fwrite( $fp, pack('V', $this->_compressedSize ) );		// Size of files when stored
		
		// Do I need to add a split archive's header too?
		if($this->_useSplitZIP)
		{
			$this->_fwrite( $fp, $this->_extraHeaderSplit);				// Signature
			$this->_fwrite( $fp, pack('v', 4) );						// Extra field length 
			$this->_fwrite( $fp, pack('v', $this->_totalFragments) );	// Number of parts
		}
		
		@fclose( $fp );
		if( function_exists('chmod') )
		{
			@chmod($this->_dataFileName, 0755);
		}
	}
	
	function _createNewPart($finalPart = false)
	{
		$this->_totalFragments++;
		$this->_currentFragment = $this->_totalFragments;
		if($finalPart)
		{
			$this->_dataFileName = $this->_dataFileNameBase.'.jpa';
		}
		else
		{
			$this->_dataFileName = $this->_dataFileNameBase.'.j'.sprintf('%02d', $this->_currentFragment);
		}
		JoomlapackLogger::WriteLog(_JP_LOG_INFO, 'Creating new JPA part #'.$this->_currentFragment.', file '.$this->_dataFileName);
		// Inform CUBE that we have chenged the multipart number
		$cube =& JoomlapackCUBE::getInstance();
		$cube->updateMultipart($this->_totalFragments);
		// Try to remove any existing file
		@unlink($this->_dataFileName);
		// Touch the new file
		return @touch($this->_dataFileName);
	}

}