<?php
// Protect from direct execution
defined('_JEXEC') or die('Restricted Access');

class CSimpleUnzip extends UnarchiverParent
{
	/**
	 * Extracts a file from the ZIP archive
	 *
	 * @param integer $offset The offset to start extracting from. If ommited, or set to null,
	 * it continues from the ZIP file's current location.
	 * @return array|boolean A return array or FALSE if an error occured
	 */
	
	function Extract( $offset = null )
	{
		global $ftp;

		// This flag is set to true when a "banned" filename is detected
		$isBannedFile = false;
		
		// Generate a return array
		$retArray = array(
			"file"				=> '',		// File name extracted
			"compressed"		=> 0,		// Compressed size
			"uncompressed"		=> 0,		// Uncompressed size
			"type"				=> "file",	// File type (file | dir | link)
			"offset"			=> 0,		// Offset in ZIP file
			"done"				=> false	// Are we done with extracting files?
		);
		
		if( is_null($offset) ) $offset = 0;
		$this->_skipToOffset($offset);
		
		// Get and decode Local File Header
		$headerBinary = fread($this->_fp, 30);
		$headerData = unpack('Vsig/C2ver/vbitflag/vcompmethod/vlastmodtime/vlastmoddate/Vcrc/Vcompsize/Vuncomp/vfnamelen/veflen', $headerBinary);
		
		// If the signature is 
		$multiPartSigs = array( 0x08074b50, 0x30304b50 );
		if( in_array($headerData['sig'], $multiPartSigs) )
		{
			// Skip four bytes ahead and re-read the header
			$this->_skipToOffset($offset + 4);
			$headerBinary = fread($this->_fp, 30);
			$headerData = unpack('Vsig/C2ver/vbitflag/vcompmethod/vlastmodtime/vlastmoddate/Vcrc/Vcompsize/Vuncomp/vfnamelen/veflen', $headerBinary);
		}
		
		if( $headerData['sig'] == 0x04034b50 )
		{
			// This is a file header. Get basic parameters.
			$retArray['compressed']		= $headerData['compsize'];
			$retArray['uncompressed']	= $headerData['uncomp'];
			$nameFieldLength			= $headerData['fnamelen'];
			$extraFieldLength			= $headerData['eflen'];
			
			// Read filename field
			$retArray['file']			= fread( $this->_fp, $nameFieldLength );

			// Handle file renaming
			if(is_array($this->_renameFiles) && (count($this->_renameFiles) > 0) )
			{
				if(array_key_exists($retArray['file'], $this->_renameFiles))
				{
					$retArray['file'] = $this->_renameFiles[$retArray['file']];
				}
			}
			
			// Read extra field if present
			if($extraFieldLength > 0) $extrafield = fread( $this->_fp, $extraFieldLength );

			// Decide filetype -- Check for directories
			if( strrpos($retArray['file'], '/') == strlen($retArray['file']) - 1 ) $retArray['type'] = 'dir';
			// Decide filetype -- Check for symbolic links
			
			if( ($headerData['ver1'] == 10) && ($headerData['ver2'] == 3) ) $retArray['type'] = 'link';
			
			// Do we need to create the directory?
			if(strpos($retArray['file'], '/') !== false) {
				$lastSlash = strrpos($retArray['file'], '/');
				$dirName = substr( $retArray['file'], 0, $lastSlash);
				if(!$this->_flagUseFTP)
				{
					if( $this->_createDirRecursive($dirName) == false ) {
						$this->_isError = true;
						if($this->_flagTranslate)
						{
							$this->_error = JText::sprintf('COULDNT_CREATE_DIR', $dirName);
						}
						else
						{
							$this->_error = 'Could not create directory '.$dirName;
						}
						return false;
					}
				}
				else
				{
					if( $this->_ftp->makeDirectory($dirName) == false ) {
						$this->_isError = true;
						if($this->_flagTranslate)
						{
							$this->_error = JText::sprintf('COULDNT_CREATE_DIR', $dirName);
						}
						else
						{
							$this->_error = 'Could not create directory '.$dirName;
						}
						return false;
					}
				}
			}

			// Find hard-coded banned files (. and .. MUST NOT be attempted to be restored!)
			if( (basename($retArray['file']) == ".") || (basename($retArray['file']) == "..") )
			{
				$isBannedFile = true;
			}
			
			// Also try to find banned files passed in class configuration
			if(count($this->_skipFiles) > 0)
			{
				if(in_array($retArray['file'], $this->_skipFiles))
				{
					$isBannedFile = true;
				}
			}
			
			// If we have a banned file, let's skip it
			if($isBannedFile)
			{
				$retArray['offset'] = $this->_getOffset() + $retArray['uncompressed'];
				return $retArray;
			}
			
			// Last chance to prepend a path to the filename
			if(!empty($this->_addPath))
			{
				$last_addpath_char = substr($this->_addPath, -1);
				if( ($last_addpath_char == '\\') || $last_addpath_char == '/' )
				{
					$this->_addPath = substr($this->_addPath, 0, -1);
				}
				$retArray['file'] = $this->_addPath.'/'.$retArray['file'];
			}
			
			if( $headerData['compmethod'] == 8 )
			{
				// DEFLATE compression
				$zipData = fread( $this->_fp, $retArray['compressed'] );
				while( strlen($zipData) < $retArray['compressed'] )
				{
					// End of local file before reading all data, but have more archive parts?
					if($this->_isEOF(true) && !$this->_isEOF(false))
					{
						// Yeap. Read from the next file
						$this->_getNextFile();
						$bytes_left = $retArray['compressed'] - strlen($zipData);
						$zipData .= fread( $this->_fp, $bytes_left );
					}
					else
					{
						// Crap... this archive is corrupt
						// @todo Translate!
						$this->_error = 'Corrupt archive detected; can\'t continue';
						return false;
					}
				}
				$unzipData = gzinflate( $zipData );
				unset($zipData);

				// Try writing to the output file
				if(!$this->_flagUseFTP)
				{
					$outfp = @fopen( $retArray['file'], 'w' );
				}
				else
				{
					$tmpLocal = tempnam(TEMPDIR,'jpks');
					$outfp = @fopen( $tmpLocal, 'w' );
				}
				
				if( $outfp === false ) {
					// An error occured
					$this->_isError = true;
					// @todo Translate!
					$this->_error = "Could not open " . $retArray['file'] . " for writing.";
					return false;
				} else {
					// No error occured. Write to the file.
					fwrite( $outfp, $unzipData, $retArray['uncompressed'] );
					fclose( $outfp );
					if($this->_flagUseFTP)
					{
						$this->_ftp->uploadAndDelete($retArray['file'], $tmpLocal);
					}
					else
					{
						// Try to change file permissions to 0755
						@chmod($retArray['file'], 0755);
					}
				}
				unset($unzipData);
			}
			else
			{
				// Uncompressed data. Action depends on what type it is
				if( $retArray['type'] == "file" )
				{
					// No compression
					if( $retArray['uncompressed'] > 0 )
					{
						if(!$this->_flagUseFTP)
						{
							$outfp = @fopen( $retArray['file'], 'w' );
						}
						else
						{
							$tmpLocal = tempnam(TEMPDIR,'jpks');
							$outfp = @fopen( $tmpLocal, 'w' );
						}
						if( $outfp === false ) {
							// An error occured
							$this->_isError = true;
							if($this->_flagTranslate)
							{
								$this->_error = JText::sprintf('COULDNT_WRITE_FILE', $retArray['file']);
							}
							else
							{
								$this->_error = 'Could not write to file '.$retArray['file'];
							}
							
							return false;
						} else {
							$readBytes = 0;
							$toReadBytes = 0;
							$leftBytes = $retArray['compressed'];

							while( $leftBytes > 0)
							{
								$toReadBytes = ($leftBytes > $this->_chunkSize) ? $this->_chunkSize : $leftBytes;
								$data = fread( $this->_fp, $toReadBytes );
								$reallyReadBytes = strlen($data);
								$leftBytes -= $reallyReadBytes;
								if($reallyReadBytes < $toReadBytes)
								{
									// We read less than requested! Why? Did we hit local EOF?
									if( $this->_isEOF(true) && !$this->_isEOF(false) )
									{
										// Yeap. Let's go to the next file
										$this->_getNextFile();
									}
									else
									{
										// Nope. The archive is corrupt
										// @todo Translate!
										$this->_error = 'Corrupt archive detected; can\'t continue';
										return false;
									}
								}
								
								fwrite( $outfp, $data );
							}
							fclose($outfp);
							if($this->_flagUseFTP)
							{
								$this->_ftp->uploadAndDelete($retArray['file'], $tmpLocal);
							}
							else
							{
								// Try to change file permissions to 0755
								@chmod($retArray['file'], 0755);
							}
						}

					} else {
						// 0 byte file, just touch it
						if(!$this->_flagUseFTP)
						{
							$outfp = @fopen( $retArray['file'], 'w' );
						}
						else
						{
							$tmpLocal = tempnam(TEMPDIR,'jpks');
							$outfp = @fopen( $tmpLocal, 'w' );
						}
						if( $outfp === false ) {
							// An error occured
							$this->_isError = true;
							if($this->_flagTranslate)
							{
								$this->_error = Text::sprintf('COULDNT_WRITE_FILE', $retArray['file']);
							}
							else
							{
								$this->_error = 'Could not write to file '.$retArray['file'];
							}
							return false;
						} else {
							fclose($outfp);
							if($this->_flagUseFTP)
							{
								$ftp->uploadAndDelete($retArray['file'], $tmpLocal);
							}
							else
							{
								// Try to change file permissions to 0755
								@chmod($retArray['file'], 0755);
							}
						}

					}
				} else if( $retArray['type'] == "dir" ) {
					// Directory entry
					if(!$this->_flagUseFTP)
					{
						$result = $this->_createDirRecursive($dirName);
					}
					else
					{
						$result = $this->_ftp->makeDirectory($dirName);
					}
					if( !$result ) {
						return false;
					}
				} else if( $retArray['type'] == "link" ) {
					// Symbolic link
					$readBytes = 0;
					$toReadBytes = 0;
					$leftBytes = $retArray['compressed'];
					$data = '';

					while( $leftBytes > 0)
					{
						$toReadBytes = ($leftBytes > $this->_chunkSize) ? $this->_chunkSize : $leftBytes;
						$data .= fread( $this->_fp, $toReadBytes );
						$reallyReadBytes = strlen($data);
						$leftBytes -= $reallyReadBytes;
						if($reallyReadBytes < $toReadBytes)
						{
							// We read less than requested! Why? Did we hit local EOF?
							if( $this->_isEOF(true) && !$this->_isEOF(false) )
							{
								// Yeap. Let's go to the next file
								$this->_getNextFile();
							}
							else
							{
								// Nope. The archive is corrupt
								// @todo Translate!
								$this->_error = 'Corrupt archive detected; can\'t continue';
								return false;
							}
						}
					}

					// Try to remove an existing file or directory by the same name
					if(file_exists($retArray['file'])) { @unlink($retArray['file']); @rmdir($retArray['file']); }
					// Remove any trailing slash
					if(substr($retArray['file'], -1) == '/') $retArray['file'] = substr($retArray['file'], 0, -1);
					// Create the symlink
					@symlink($data, $retArray['file']);
				}
			}
			$retArray['offset'] = $this->_getOffset();
			return $retArray;
		} else {
			// This is not a file header. This means we are done.
			$retArray['done'] = true;
			return $retArray;
		}
		
	}
}
?>