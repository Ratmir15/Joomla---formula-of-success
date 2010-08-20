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
 *
 * ZIP Creation Module
 *
 * Creates a ZIP file based on the contents of a given file list.
 * Based upon the Compress library of the Horde Project [http://www.horde.org],
 * modified to fit JoomlaPack needs. This class is safe to serialize and deserialize
 * between subsequent calls.
 *
 * JoomlaPack modifications : eficiently read data from files, selective compression,
 * defensive memory management to avoid memory exhaustion errors, separated central
 * directory and data section creation, split archives, symlink storage
 *
 * ----------------------------------------------------------------------------
 *
 * Original code credits, from Horde library:
 *
 * The ZIP compression code is partially based on code from:
 *   Eric Mueller <eric@themepark.com>
 *   http://www.zend.com/codex.php?id=535&single=1
 *
 *   Deins125 <webmaster@atlant.ru>
 *   http://www.zend.com/codex.php?id=470&single=1
 *
 * The ZIP compression date code is partially based on code from
 *   Peter Listiak <mlady@users.sourceforge.net>
 *
 * Copyright 2000-2006 Chuck Hagenbuch <chuck@horde.org>
 * Copyright 2002-2006 Michael Cochrane <mike@graftonhall.co.nz>
 * Copyright 2003-2006 Michael Slusarz <slusarz@horde.org>
 *
 * Additional Credits:
 *
 * Contains code from pclZip library [http://www.phpconcept.net/pclzip/index.en.php]
 *
 * Modifications for JoomlaPack:
 * Copyright 2007-2009 Nicholas K. Dionysopoulos <nikosdion@gmail.com>
 */
defined('_JEXEC') or die('Restricted access');

$config =& JoomlapackModelRegistry::getInstance();
define("_JoomlapackPackerZIP_FORCE_FOPEN", $config->get('mnZIPForceOpen')); // Don't force use of fopen() to read uncompressed data in memory
define("_JoomlapackPackerZIP_COMPRESSION_THRESHOLD", $config->get('mnZIPCompressionThreshold')); // Don't compress files over this size
define("_JoomlapackPackerZIP_DIRECTORY_READ_CHUNK", $config->get('mnZIPDirReadChunk')); // How much data to read at once when finalizing ZIP archives

class JoomlapackPackerZIP extends JoomlapackCUBEArchiver {
	/**
	 * ZIP compression methods. JoomlaPack supports 0x0 (none) and 0x8 (deflated)
	 *
	 * @var array
	 */
	var $_methods = array(
	0x0 => 'None',
	0x1 => 'Shrunk',
	0x2 => 'Super Fast',
	0x3 => 'Fast',
	0x4 => 'Normal',
	0x5 => 'Maximum',
	0x6 => 'Imploded',
	0x8 => 'Deflated'
	);

	/**
	 * Beginning of central directory record.
	 *
	 * @var string
	 */
	var $_ctrlDirHeader = "\x50\x4b\x01\x02";

	/**
	 * End of central directory record.
	 *
	 * @var string
	 */
	var $_ctrlDirEnd = "\x50\x4b\x05\x06";

	/**
	 * Beginning of file contents.
	 *
	 * @var string
	 */
	var $_fileHeader = "\x50\x4b\x03\x04";

	/**
	 * The name of the temporary file holding the ZIP's Central Directory
	 *
	 * @var string
	 */
	var $_ctrlDirFileName;

	/**
	 * The name of the file holding the ZIP's data, which becomes the final archive
	 *
	 * @var string
	 */
	var $_dataFileName;

	/**
	 * The total number of files and directories stored in the ZIP archive
	 *
	 * @var integer
	 */
	var $_totalFileEntries;

	/**
	 * The chunk size for CRC32 calculations
	 *
	 * @var integer
	 */
	var $JoomlapackPackerZIP_CHUNK_SIZE;

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
	 * Class constructor - initializes internal operating parameters
	 *
	 * @return JoomlapackPackerZIP The class instance
	 */
	function JoomlapackPackerZIP()
	{
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackPackerZIP :: New instance");

		// Get chunk override
		$registry =& JoomlapackModelRegistry::getInstance();
		if( $registry->get('mnArchiverChunk', 0) > 0 )
		{
			$this->JoomlapackPackerZIP_CHUNK_SIZE = JPPACK_CHUNK;
		}
		else
		{
			// Try to use as much memory as it's possible for CRC32 calculation
			$memLimit = ini_get("memory_limit");
			if( is_numeric($memLimit) && ($memLimit < 0) ) $memLimit = ""; // 1.2a3 -- Rare case with memory_limit < 0, e.g. -1Mb!
			if ( ($memLimit == "") ) {
				// No memory limit, use 2Mb chunks (fairly large, right?)
				$this->JoomlapackPackerZIP_CHUNK_SIZE = 2097152;
			} elseif ( function_exists("memory_get_usage") ) {
				// PHP can report memory usage, see if there's enough available memory; Joomla! alone eats about 5-6Mb! This code is called on files <= 1Mb
				$memLimit = $this->_return_bytes( $memLimit );
				$availableRAM = $memLimit - memory_get_usage();

				if ($availableRAM <= 0) {
					// Some PHP implemenations also return the size of the httpd footprint!
					if ( ($memLimit - 6291456) > 0 ) {
						$this->JoomlapackPackerZIP_CHUNK_SIZE = $memLimit - 6291456;
					} else {
						$this->JoomlapackPackerZIP_CHUNK_SIZE = 2097152;
					}
				} else {
					$this->JoomlapackPackerZIP_CHUNK_SIZE = $availableRAM * 0.5;
				}
			} else {
				// PHP can't report memory usage, use a conservative 512Kb
				$this->JoomlapackPackerZIP_CHUNK_SIZE = 524288;
			}
		}

		// NEW 2.3: Should we enable Split ZIP feature?
		$fragmentsize = $registry->get('splitpartsize', 0);
		if($fragmentsize >= 65536)
		{
			// If the fragment size is AT LEAST 64Kb, enable Split ZIP
			$this->_useSplitZIP = true;
			$this->_fragmentSize = $fragmentsize;
			// Enable CUBE that we have a multipart archive
			$cube =& JoomlapackCUBE::getInstance();
			$cube->updateMultipart(1); // Indicate that we have at least 1 part
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


		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Chunk size for CRC is now " . $this->JoomlapackPackerZIP_CHUNK_SIZE . " bytes");
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
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackPackerZIP :: initialize - archive $targetArchivePath");

		// Get names of temporary files
		$configuration =& JoomlapackModelRegistry::getInstance();
		$this->_ctrlDirFileName = tempnam( $configuration->getTemporaryDirectory(), 'jpzcd' );
		$this->_dataFileName = $targetArchivePath;

		// If we use splitting, initialize
		if($this->_useSplitZIP)
		{
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "JoomlapackPackerZIP :: Split ZIP creation enabled");
			$this->_dataFileNameBase = dirname($targetArchivePath).DS.basename($targetArchivePath,'.zip');
			$this->_dataFileName = $this->_dataFileNameBase.'.z01';
		}

		jimport('joomla.filesystem.file');
		$tempname = JFile::getName($this->_ctrlDirFileName);
		JoomlapackCUBETempfiles::registerTempFile($tempname);

		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackPackerZIP :: CntDir Tempfile = " . $this->_ctrlDirFileName);

		// Create temporary file
		if(!@touch( $this->_ctrlDirFileName ))
		{
			$this->setError(JText::_('CUBE_ZIPARCHIVER_CANTWRITETEMP'));
			return false;
		}

		// Try to kill the archive if it exists
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "JoomlapackPackerZIP :: Killing old archive");
		$fp = fopen( $this->_dataFileName, "wb" );
		if (!($fp === false)) {
			ftruncate( $fp,0 );
			fclose( $fp );
		} else {
			@unlink( $this->_dataFileName );
		}
		if(!@touch( $this->_dataFileName ))
		{
			$this->setError(JText::_('CUBE_ZIPARCHIVER_CANTWRITEZIP'));
			return false;
		}

		// On split archives, include the "Split ZIP" header, for PKZIP 2.50+ compatibility
		if($this->_useSplitZIP)
		{
			file_put_contents($this->_dataFileName, "\x50\x4b\x07\x08");
			// Also update the statistics table that we are a multipart archive...
			$cube =& JoomlapackCUBE::getInstance();
			$cube->updateMultipart(1);
		}

		parent::initialize($sourceJPAPath, $targetArchivePath, $options);
	}

	/**
	 * Creates the ZIP file out of its pieces.
	 * Official ZIP file format: http://www.pkware.com/appnote.txt
	 *
	 * @return boolean TRUE on success, FALSE on failure
	 */
	function finalize()
	{
		// 1. Get size of central directory
		clearstatcache();
		$cdSize = @filesize( $this->_ctrlDirFileName );

		// 2. Append Central Directory to data file and remove the CD temp file afterwards
		$dataFP = fopen( $this->_dataFileName, "ab" );
		$cdFP = fopen( $this->_ctrlDirFileName, "rb" );

		if( $dataFP === false )
		{
			$this->setError('Could not open ZIP data file '.$this->_dataFileName.' for reading');
			return false;
		}

		if ( $cdFP === false ) {
			// Already glued, return
			fclose( $dataFP );
			return false;
		}

		if(!$this->_useSplitZIP)
		{
			while( !feof($cdFP) )
			{
				$chunk = fread( $cdFP, _JoomlapackPackerZIP_DIRECTORY_READ_CHUNK );
				$this->_fwrite( $dataFP, $chunk );
				if($this->getError()) return;
			}
			unset( $chunk );
			fclose( $cdFP );
		}
		else
		// Special considerations for Split ZIP
		{
			// Calcuate size of Central Directory + EOCD records
			$total_cd_eocd_size = $cdSize + 22 + strlen($this->_comment);
			// Free space on the part
			clearstatcache();
			$current_part_size = @filesize($this->_dataFileName);
			$free_space = $this->_fragmentSize - ($current_part_size === false ? 0 : $current_part_size);
			if( ($free_space < $total_cd_eocd_size) && ($total_cd_eocd_size > 65536) )
			{
				// Not enough space on archive for CD + EOCD, will go on separate part
				// Create new final part
				if(!$this->_createNewPart(true))
				{
					// Die if we couldn't create the new part
					$this->setError('Could not create new ZIP part file '.basename($this->_dataFileName));
					return false;
				}
				else
				{
					// Close the old data file
					fclose($dataFP);
					// Open data file for output
					$dataFP = @fopen( $this->_dataFileName, "ab");
					if ($dataFP === false)
					{
						$this->setError("Could not open archive file {$this->_dataFileName} for append!");
						return false;
					}
					// Write the CD record
					while( !feof($cdFP) )
					{
						$chunk = fread( $cdFP, _JoomlapackPackerZIP_DIRECTORY_READ_CHUNK );
						$this->_fwrite( $dataFP, $chunk );
						if($this->getError()) return;
					}
					unset( $chunk );
					fclose( $cdFP );
				}
			}
			else
			{
				// Glue the CD + EOCD on the same part if they fit, or anyway if they are less than 64Kb.
				// NOTE: WE *MUST NOT* CREATE FRAGMENTS SMALLER THAN 64Kb!!!!
				while( !feof($cdFP) )
				{
					$chunk = fread( $cdFP, _JoomlapackPackerZIP_DIRECTORY_READ_CHUNK );
					$this->_fwrite( $dataFP, $chunk );
					if($this->getError()) return;
				}
				unset( $chunk );
				fclose( $cdFP );
			}
		}

		JoomlapackCUBETempfiles::unregisterAndDeleteTempFile($this->_ctrlDirFileName);

		// 3. Write the rest of headers to the end of the ZIP file
		fclose( $dataFP );
		clearstatcache();
		$dataSize = @filesize( $this->_dataFileName ) - $cdSize;
		$dataFP = fopen( $this->_dataFileName, "ab" );
		if($dataFP === false)
		{
			$this->setError('Could not open '.$this->_dataFileName.' for append');
			return false;
		}
		$this->_fwrite( $dataFP, $this->_ctrlDirEnd );
		if($this->getError()) return;
		if($this->_useSplitZIP)
		{
			// Split ZIP files, enter relevant disk number information
			$this->_fwrite( $dataFP, pack('v', $this->_totalFragments - 1) ); /* Number of this disk. */
			$this->_fwrite( $dataFP, pack('v', $this->_totalFragments - 1) ); /* Disk with central directory start. */
		}
		else
		{
			// Non-split ZIP files, the disk numbers MUST be 0
			$this->_fwrite( $dataFP, pack('V', 0) );
		}
		$this->_fwrite( $dataFP, pack('v', $this->_totalFileEntries) ); /* Total # of entries "on this disk". */
		$this->_fwrite( $dataFP, pack('v', $this->_totalFileEntries) ); /* Total # of entries overall. */
		$this->_fwrite( $dataFP, pack('V', $cdSize) ); /* Size of central directory. */
		$this->_fwrite( $dataFP, pack('V', $dataSize) ); /* Offset to start of central dir. */
		$sizeOfComment = strlen($this->_comment);
		// 2.0.b2 -- Write a ZIP file comment
		$this->_fwrite( $dataFP, pack('v', $sizeOfComment) ); /* ZIP file comment length. */
		$this->_fwrite( $dataFP, $this->_comment );
		fclose( $dataFP );
		//sleep(2);

		// If Split ZIP and there is no .zip file, rename the last fragment to .ZIP
		if($this->_useSplitZIP)
		{
			$extension = substr($this->_dataFileName, -3);
			if($extension != '.zip')
			{
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, 'Renaming last ZIP part to .ZIP extension');
				$newName = $this->_dataFileNameBase.'.zip';
				if(!@rename($this->_dataFileName, $newName))
				{
					$this->setError('Could not rename last ZIP part to .ZIP extension.');
					return false;
				}
				$this->_dataFileName = $newName;
			}
		}
		// If Split ZIP and only one fragment, change the signature
		if($this->_useSplitZIP && ($this->_totalFragments == 1) )
		{
			$fp = fopen($this->_dataFileName, 'r+b');
			$this->_fwrite($fp, "\x50\x4b\x30\x30" );
		}
		// @todo If Split ZIP, update CUBE with total number of fragments

		if( function_exists('chmod') )
		{
			@chmod($this->_dataFileName, 0755);
		}
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
	 * @since 1.2.1
	 * @access protected
	 * @abstract
	 */
	function _addFile( $isVirtual, &$sourceNameOrData, $targetName )
	{
		static $configuration;
		$cube =& JoomlapackCUBE::getInstance();

		// Note down the starting disk number for Split ZIP archives
		if($this->_useSplitZIP)
		{
			$starting_disk_number_for_this_file = $this->_currentFragment - 1;
		}
		else
		{
			$starting_disk_number_for_this_file = 0;
		}

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
		} else {
			if($isSymlink)
			{
				$fileSize = strlen( @readlink($sourceNameOrData) );
			}
			else
			{
				$fileSize = $isDir ? 0 : @filesize($sourceNameOrData);
			}
		}

		// Get last modification time to store in archive
		$ftime = $isVirtual ? time() : @filemtime( $sourceNameOrData );

		// Decide if we will compress
		if ($isDir || $isSymlink) {
			$compressionMethod = 0; // don't compress directories...
		} else {
			// Do we have plenty of memory left?
			$memLimit = ini_get("memory_limit");
			if (($memLimit == "") || ($fileSize >= _JoomlapackPackerZIP_COMPRESSION_THRESHOLD)) {
				// No memory limit, or over 1Mb files => always compress up to 1Mb files (otherwise it times out)
				$compressionMethod = ($fileSize <= _JoomlapackPackerZIP_COMPRESSION_THRESHOLD) ? 8 : 0;
			} elseif ( function_exists("memory_get_usage") ) {
				// PHP can report memory usage, see if there's enough available memory; Joomla! alone eats about 5-6Mb! This code is called on files <= 1Mb
				$memLimit = $this->_return_bytes( $memLimit );
				$availableRAM = $memLimit - memory_get_usage();
				$compressionMethod = (($availableRAM / 2.5) >= $fileSize) ? 8 : 0;
			} else {
				// PHP can't report memory usage, compress only files up to 512Kb (conservative approach) and hope it doesn't break
				$compressionMethod = ($fileSize <= 524288) ? 8 : 0;;
			}
		}

		$compressionMethod = function_exists("gzcompress") ? $compressionMethod : 0;

		$storedName = $targetName;

		if($isVirtual) JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, '  Virtual add:'.$storedName.' ('.$fileSize.') - '.$compressionMethod);

		/* "Local file header" segment. */
		$unc_len = &$fileSize; // File size

		if (!$isDir) {
			// Get CRC for regular files, not dirs
			if($isVirtual)
			{
				$crc = crc32($sourceNameOrData);
			}
			else
			{
				$crcCalculator = new CRC32CalcClass;
				$crc     = $crcCalculator->crc32_file( $sourceNameOrData, $this->JoomlapackPackerZIP_CHUNK_SIZE ); // This is supposed to be the fast way to calculate CRC32 of a (large) file.
				unset( $crcCalculator );

				// If the file was unreadable, $crc will be false, so we skip the file
				if ($crc === false) {
					$cube->addWarning( 'Could not calculate CRC32 for '.$sourceNameOrData);
					return false;
				}
			}
		} else if($isSymlink) {
			$crc = crc32( @readlink($sourceNameOrData) );
		} else {
			// Dummy CRC for dirs
			$crc = 0;
			$storedName .= "/";
			$unc_len = 0;
		}


		// If we have to compress, read the data in memory and compress it
		if ($compressionMethod == 8) {
			// Get uncompressed data
			if( $isVirtual )
			{
				$udata =& $sourceNameOrData;
			}
			else
			{
				if( function_exists("file_get_contents") && (_JoomlapackPackerZIP_FORCE_FOPEN == false) ) {
					$udata = @file_get_contents( $sourceNameOrData ); // PHP > 4.3.0 saves us the trouble
				} else {
					// Argh... the hard way!
					$udatafp = @fopen( $sourceNameOrData, "rb" );
					if( !($udatafp === false) ) {
						$udata = "";
						while( !feof($udatafp) ) {
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
				// Unreadable file, skip it. Normally, we should have exited on CRC code above
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

		/* Get the hex time. */
		$dtime    = dechex($this->_unix2DosTime($ftime));
		if( strlen($dtime) < 8 ) $dtime = "00000000";
		$hexdtime = chr(hexdec($dtime[6] . $dtime[7])) .
		chr(hexdec($dtime[4] . $dtime[5])) .
		chr(hexdec($dtime[2] . $dtime[3])) .
		chr(hexdec($dtime[0] . $dtime[1]));

		// Get current data file size
		clearstatcache();
		$old_offset = @filesize( $this->_dataFileName );

		// If it's a split ZIP file, we've got to make sure that the header can fit in the part
		if($this->_useSplitZIP)
		{
			// Get header size, taking into account any extra header necessary
			$header_size = 30 + strlen($storedName);
			// Compare to free part space
			clearstatcache();
			$current_part_size = @filesize($this->_dataFileName);
			$free_space = $this->_fragmentSize - ($current_part_size === false ? 0 : $current_part_size);
			if($free_space <= $header_size)
			{
				// Not enough space on current part, create new part
				if(!$this->_createNewPart())
				{
					$this->setError('Could not create new ZIP part file '.basename($this->_dataFileName));
					return false;
				}
			}
		}
		// Open data file for output
		$fp = @fopen( $this->_dataFileName, "ab");
		if ($fp === false)
		{
			$this->setError("Could not open archive file {$this->_dataFileName} for append!");
			return false;
		}
		$this->_fwrite( $fp, $this->_fileHeader );									/* Begin creating the ZIP data. */
		if(!$isSymlink)
		{
			$this->_fwrite( $fp, "\x14\x00" );					/* Version needed to extract. */
		}
		else
		{
			$this->_fwrite( $fp, "\x0a\x03" );					/* Version needed to extract. */
		}
		$this->_fwrite( $fp, "\x00\x00" ); 											/* General purpose bit flag. */
		$this->_fwrite( $fp, ($compressionMethod == 8) ? "\x08\x00" : "\x00\x00" );	/* Compression method. */
		$this->_fwrite( $fp, $hexdtime );											/* Last modification time/date. */
		$this->_fwrite( $fp, pack('V', $crc) );            /* CRC 32 information. */
		$this->_fwrite( $fp, pack('V', $c_len) );          /* Compressed filesize. */
		$this->_fwrite( $fp, pack('V', $unc_len) );        /* Uncompressed filesize. */
		$this->_fwrite( $fp, pack('v', strlen($storedName)) );   /* Length of filename. */
		$this->_fwrite( $fp, pack('v', 0) );	/* Extra field length. */
		$this->_fwrite( $fp, $storedName );                      /* File name. */

		/* "File data" segment. */
		if ($compressionMethod == 8) {
			// Just dump the compressed data
			if(!$this->_useSplitZIP)
			{
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

						// Split between parts - Write a part
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
								$this->setError('Could not create new ZIP part file '.basename($this->_dataFileName));
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
		} elseif ( !($isDir || $isSymlink) ) {
			// Virtual file, just write the data!
			if( $isVirtual )
			{
				// Just dump the data
				if(!$this->_useSplitZIP)
				{
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
								// Create new part if required
								if(!$this->_createNewPart())
								{
									// Die if we couldn't create the new part
									$this->setError('Could not create new ZIP part file '.basename($this->_dataFileName));
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
								// Get the rest of the compressed data
								$zdata = substr($sourceNameOrData, -$rest_size);
							}
							$bytes_left = $rest_size;
						}
					}
				}
			}
			else
			{
				// Copy the file contents, ignore directories
				$zdatafp = @fopen( $sourceNameOrData, "rb" );
				if( $zdatafp === FALSE )
				{
					$cube->addWarning( JText::sprintf('CUBE_WARN_UNREADABLEFILE', $sourceNameOrData));
					return false;
				}
				else
				{
					if(!$this->_useSplitZIP)
					{
						// For non Split ZIP, just dump the file very fast
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
							$bytes_left = $unc_len;

							while( ($bytes_left > 0) && (!feof($zdatafp)) )
							{
								// No, we'll have to split between parts. Write the first part
								// Find optimal chunk size
								clearstatcache();
								$current_part_size = @filesize($this->_dataFileName);
								$free_space = $this->_fragmentSize - ($current_part_size === false ? 0 : $current_part_size);

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

								// Create new ZIP part, but only if we'll have more data to write
								if(!feof($zdatafp))
								{
																	// Create new ZIP part
									if(!$this->_createNewPart())
									{
										// Die if we couldn't create the new part
										$this->setError('Could not create new ZIP part file '.basename($this->_dataFileName));
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

		// Done with data file.
		fclose( $fp );

		// Open the central directory file for append
		$fp = @fopen( $this->_ctrlDirFileName, "ab");
		if ($fp === false)
		{
			$this->setError("Could not open Central Directory temporary file for append!");
			return false;
		}
		$this->_fwrite( $fp, $this->_ctrlDirHeader );
		if(!$isSymlink)
		{
			$this->_fwrite( $fp, "\x00\x00" );                /* Version made by. */
			$this->_fwrite( $fp, "\x14\x00" );					/* Version needed to extract */
			$this->_fwrite( $fp, "\x00\x00" );                /* General purpose bit flag */
			$this->_fwrite( $fp, ($compressionMethod == 8) ? "\x08\x00" : "\x00\x00" );	/* Compression method. */
		}
		else
		{
			// Symlinks get special treatment
			$this->_fwrite( $fp, "\x14\x03" );                /* Version made by. */
			$this->_fwrite( $fp, "\x0a\x03" );					/* Version needed to extract */
			$this->_fwrite( $fp, "\x00\x00" );                /* General purpose bit flag */
			$this->_fwrite( $fp, "\x00\x00" );	/* Compression method. */
		}

		$this->_fwrite( $fp, $hexdtime );                 /* Last mod time/date. */
		$this->_fwrite( $fp, pack('V', $crc) );           /* CRC 32 information. */
		$this->_fwrite( $fp, pack('V', $c_len) );         /* Compressed filesize. */
		$this->_fwrite( $fp, pack('V', $unc_len) );       /* Uncompressed filesize. */
		$this->_fwrite( $fp, pack('v', strlen($storedName)) );  /* Length of filename. */
		$this->_fwrite( $fp, pack('v', 0 ) ); /* Extra field length. */
		$this->_fwrite( $fp, pack('v', 0 ) );             /* File comment length. */
		$this->_fwrite( $fp, pack('v', $starting_disk_number_for_this_file ) ); /* Disk number start. */
		$this->_fwrite( $fp, pack('v', 0 ) );             /* Internal file attributes. */
		if(!$isSymlink)
		{
			$this->_fwrite( $fp, pack('V', $isDir ? 0x41FF0010 : 0xFE49FFE0) ); /* External file attributes -   'archive' bit set. */
		}
		else
		{
			// For SymLinks we store UNIX file attributes
			$this->_fwrite( $fp, "\x20\x80\xFF\xA1" ); /* External file attributes for Symlink. */
		}
		$this->_fwrite( $fp, pack('V', $old_offset) );    /* Relative offset of local header. */
		$this->_fwrite( $fp, $storedName );                     /* File name. */
		/* Optional extra field, file comment goes here. */

		// Finished with Central Directory
		fclose( $fp );

		// Finaly, increase the file counter by one
		$this->_totalFileEntries++;

		// ... and return TRUE = success
		return TRUE;
	}

	// ------------------------------------------------------------------------
	// Archiver-specific utility functions
	// ------------------------------------------------------------------------

	/**
	 * Converts a UNIX timestamp to a 4-byte DOS date and time format
	 * (date in high 2-bytes, time in low 2-bytes allowing magnitude
	 * comparison).
	 *
	 * @access private
	 *
	 * @param integer $unixtime  The current UNIX timestamp.
	 *
	 * @return integer  The current date in a 4-byte DOS format.
	 */
	function _unix2DOSTime($unixtime = null)
	{
		$timearray = (is_null($unixtime)) ? getdate() : getdate($unixtime);

		if ($timearray['year'] < 1980) {
			$timearray['year']    = 1980;
			$timearray['mon']     = 1;
			$timearray['mday']    = 1;
			$timearray['hours']   = 0;
			$timearray['minutes'] = 0;
			$timearray['seconds'] = 0;
		}

		return (($timearray['year'] - 1980) << 25) |
		($timearray['mon'] << 21) |
		($timearray['mday'] << 16) |
		($timearray['hours'] << 11) |
		($timearray['minutes'] << 5) |
		($timearray['seconds'] >> 1);
	}

	function _createNewPart($finalPart = false)
	{
		$this->_totalFragments++;
		$this->_currentFragment = $this->_totalFragments;
		if($finalPart)
		{
			$this->_dataFileName = $this->_dataFileNameBase.'.zip';
		}
		else
		{
			$this->_dataFileName = $this->_dataFileNameBase.'.z'.sprintf('%02d', $this->_currentFragment);
		}
		JoomlapackLogger::WriteLog(_JP_LOG_INFO, 'Creating new ZIP part #'.$this->_currentFragment.', file '.$this->_dataFileName);
		// Inform CUBE that we have chenged the multipart number
		$cube =& JoomlapackCUBE::getInstance();
		$cube->updateMultipart($this->_totalFragments);
		// Try to remove any existing file
		@unlink($this->_dataFileName);
		// Touch the new file
		return @touch($this->_dataFileName);
	}
}

// ===================================================================================================

/**
 * A handy class to abstract the calculation of CRC32 of files under various
 * server conditions and versions of PHP.
 * @access private
 */
class CRC32CalcClass
{
	var $startTime = 0;

	/**
	 * Returns the CRC32 of a file, selecting the more appropriate algorithm.
	 *
	 * @param string $filename Absolute path to the file being processed
	 * @param integer $JoomlapackPackerZIP_CHUNK_SIZE Obsoleted
	 * @return integer The CRC32 in numerical form
	 */
	function crc32_file( $filename, $JoomlapackPackerZIP_CHUNK_SIZE )
	{
		static $configuration;

		if(!$configuration)
		{
			jpimport('models.registry', true);
			$configuration =& JoomlapackModelRegistry::getInstance();
		}

		// Keep-alive before CRC32 calculation
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

		if( function_exists("hash_file") )
		{
			$res = $this->crc32_file_php512( $filename );
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "File $filename - CRC32 = " . dechex($res) . " [PHP512]" );
		}
		else if ( function_exists("file_get_contents") && ( @filesize($filename) <= $JoomlapackPackerZIP_CHUNK_SIZE ) ) {
			$res = $this->crc32_file_getcontents( $filename );
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "File $filename - CRC32 = " . dechex($res) . " [GETCONTENTS]" );
		} else {
			$res = $this->crc32_file_php4($filename, $JoomlapackPackerZIP_CHUNK_SIZE);
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "File $filename - CRC32 = " . dechex($res) . " [PHP4]" );
		}

		if ($res === FALSE) {
			$cube =& JoomlapackCUBE::getInstance();
			$cube->addWarning( "File $filename - NOT READABLE: CRC32 IS WRONG!" );
		}
		return $res;
	}

	/**
	 * Very efficient CRC32 calculation for PHP 5.1.2 and greater, requiring
	 * the 'hash' PECL extension
	 *
	 * @param string $filename Absolute filepath
	 * @return integer The CRC32
	 * @access private
	 */
	function crc32_file_php512($filename)
	{
		// Detection of buggy PHP hosts
		static $mustInvert = null;
		if(is_null($mustInvert))
		{
			$test_crc = @hash('crc32b', 'test', false);
			$mustInvert = ($test_crc == '0c7e7fD8'); // Normally, it's D87F7E0C :)
			JoomlapackLogger::WriteLog(_JP_LOG_WARNING,'Your server has a buggy PHP version which produces inverted CRC32 values. Attempting a workaround. ZIP files may appear as corrupt.');
		}

		$res = @hash_file('crc32b', $filename, false );
		if($mustInvert)
		{
			// Workaround for buggy PHP versions (I think before 5.1.8) which produce inverted CRC32 sums
			$res = substr($res,6,2) . substr($res,4,2) . substr($res,2,2) . substr($res,0,2);
		}
		$res = hexdec( $res );
		return $res;
	}

	/**
	 * A compatible CRC32 calculation using file_get_contents, utilizing immense
	 * ammounts of RAM
	 *
	 * @param string $filename
	 * @return integer
	 * @access private
	 */
	function crc32_file_getcontents($filename)
	{
		return crc32( @file_get_contents($filename) );
	}

	/**
	 * There used to be a workaround for large files under PHP4. However, it never
	 * really worked, so it is removed and a warning is posted instead.
	 *
	 * @param string $filename
	 * @param integer $JoomlapackPackerZIP_CHUNK_SIZE
	 * @return integer
	 */
	function crc32_file_php4($filename, $JoomlapackPackerZIP_CHUNK_SIZE)
	{
		$cube =& JoomlapackCUBE::getInstance();
		$cube->addWarning( "Function hash_file not detected processing the 'large'' file $filename; it will appear as seemingly corrupt in the archive. Only the CRC32 is invalid, though. Please read our forum announcement for explanation of this message." );
		return 0;
	}
}