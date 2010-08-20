<?php

class CUnJPA extends UnarchiverParent
{
	/**
	 * Data read from archive's header
	 * @var array
	 */
	var $headerData = array();

	/**
	 * Extracts a file from the JPA archive
	 *
	 * @param integer $offset The offset to start extracting from. If ommited, or set to null,
	 * it continues from the JPA file's current location.
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
			"type"				=> "file",	// File type (file | dir)
			"compression"		=> "none",	// Compression type (none | gzip | bzip2)
			"offset"			=> 0,			// Offset in JPA file
			"permissions"		=> 0,		// UNIX permissions stored in the archive
			"done"				=> false	// Are we done with extracting files?
		);
		
		$offset = is_null($offset) ? 0 : $offset;
		
		$this->_skipToOffset($offset);
		if($offset == 0) $this->_ReadHeader();
		
		// Get and decode Entity Description Block
		$signature = fread($this->_fp, 3);

		// Check signature
		if( $signature == 'JPF' )
		{
			// This a JPA Entity Block. Process the header.
				
			// Read length of EDB and of the Entity Path Data
			$length_array = unpack('vblocksize/vpathsize', fread($this->_fp, 4));
			// Read the path data
			$file = fread( $this->_fp, $length_array['pathsize'] );
				
			// Handle file renaming
			if(is_array($this->_renameFiles) && (count($this->_renameFiles) > 0) )
			{
				if(array_key_exists($file, $this->_renameFiles))
				{
					$file = $this->_renameFiles[$file];
				}
			}
			
			// Read and parse the known data portion
			$bin_data = fread( $this->_fp, 14 );
			$header_data = unpack('Ctype/Ccompression/Vcompsize/Vuncompsize/Vperms', $bin_data);
			// Read any unknwon data
			$restBytes = $length_array['blocksize'] - (21 + $length_array['pathsize']);
			if( $restBytes > 0 ) $junk = fread($this->_fp, $restBytes);
				
			$compressionType = $header_data['compression'];
				
			// Populate the return array
			$retArray['file'] = $file;
			$retArray['compressed'] = $header_data['compsize'];
			$retArray['uncompressed'] = $header_data['uncompsize'];
			switch($header_data['type'])
			{
				case 0:
					$retArray['type'] = 'dir';
					break;
		
				case 1:
					$retArray['type'] = 'file';
					break;		

				case 2:
					$retArray['type'] = 'link';
					break;		
			}
			switch( $compressionType )
			{
				case 0:
					$retArray['compression'] = 'none';
					break;
				case 1:
					$retArray['compression'] = 'gzip';
					break;
				case 2:
					$retArray['compression'] = 'bzip2';
					break;
			}
			$retArray['permissions'] = $header_data['perms'];
				
			// Find hard-coded banned files
			if( (basename($file) == ".") || (basename($file) == "..") )
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
			
			// Do we need to create the directory?
			if(strpos($retArray['file'], '/') !== false) {
				$lastSlash = strrpos($retArray['file'], '/');
				$dirName = substr( $retArray['file'], 0, $lastSlash);
				if(!$this->_flagUseFTP)
				{
					$perms = $this->_flagRestorePermissions ? $retArray['permissions'] : 0755;
					if( $this->_createDirRecursive($dirName, $perms) == false ) {
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

			switch( $retArray['type'] )
			{
				case "dir":
					if(!$this->_flagUseFTP)
					{
						$perms = $this->_flagRestorePermissions ? $retArray['permissions'] : 0755;
						$result = $this->_createDirRecursive($dirName, $perms);
					}
					else
					{
						$perms = $this->_flagRestorePermissions ? $retArray['permissions'] : 0755;
						$result = $this->_ftp->makeDirectory($dirName, $perms);
					}
					if( !$result ) {
						return false;
					}
					break;
						
				case "file":
					switch( $compressionType )
					{
						case 0: // No compression
							if(!$this->_flagUseFTP)
							{
								$outfp = @fopen( $retArray['file'], 'w' );
							}
							else
							{
								$tmpLocal = tempnam(TEMPDIR,'jpks');
								$outfp = @fopen( $tmpLocal, 'w' );
							}
							// Magic permissions handling attempt
							if( ($outfp === false) )
							{
								if(!$this->_flagUseFTP)
								{
									$this->_setCorrectPermissions($retArray['file']);
									$outfp = @fopen( $retArray['file'], 'w' );
								}
								else
								{
									$this->_setCorrectPermissions($tmpLocal);
									$outfp = @fopen( $tmpLocal, 'w' );
								}
							}
							// Re-test
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
							}
								
							if( $retArray['uncompressed'] > 0 )
							{
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
							}
								
							fclose($outfp);
							if($this->_flagUseFTP)
							{
								$perms = $this->_flagRestorePermissions ? $retArray['permissions'] : 0755;
								$this->_ftp->uploadAndDelete($retArray['file'], $tmpLocal, $perms);
							}
							else
							{
								// Try to change file permissions to 0755
								$perms = $this->_flagRestorePermissions ? $retArray['permissions'] : 0755;
								@chmod($retArray['file'], $perms);
							}
								
							break;
								
						case 1: // GZip compression
						case 2: // BZip compression
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
							if($compressionType == 1)
							{
								$unzipData = gzinflate( $zipData );
							}
							elseif($compressionType == 2)
							{
								$unzipData = bzdecompress( $zipData );
							}
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
							// Magic permissions handling attempt
							if( ($outfp === false) )
							{
								if(!$this->_flagUseFTP)
								{
									$this->_setCorrectPermissions($retArray['file']);
									$outfp = @fopen( $retArray['file'], 'w' );
								}
								else
								{
									$this->_setCorrectPermissions($tmpLocal);
									$outfp = @fopen( $tmpLocal, 'w' );
								}
							}
							// Re-test
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
								// No error occured. Write to the file.
								fwrite( $outfp, $unzipData, $retArray['uncompressed'] );
								fclose( $outfp );
								if($this->_flagUseFTP)
								{
									$perms = $this->_flagRestorePermissions ? $retArray['permissions'] : 0755;
									$ftp->uploadAndDelete($retArray['file'], $tmpLocal, $perms);
								}
								else
								{
									// Try to change file permissions to 0755
									$perms = $this->_flagRestorePermissions ? $retArray['permissions'] : 0755;
									@chmod($retArray['file'], $perms);
								}
							}
							unset($unzipData);
							break;
					}
					break;
					
				case 'link':
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
					break; 
			}

			$retArray['offset'] = $this->_getOffset();
			return $retArray;
		} else {
			// This is not a file header. This means we are done.
			$retArray['done'] = true;
			return $retArray;
		}
	}
	
	/**
	 * Reads the files header
	 * @access private
	 * @return boolean TRUE on success
	 */
	function _ReadHeader()
	{
		// Initialize header data array
		$this->headerData = array();
		
		// Fail for unreadable files
		if( $this->_fp === false ) return false;

		// Read the signature
		$sig = fread( $this->_fp, 3 );

		if ($sig != 'JPA') return false; // Not a JoomlaPack Archive?

		// Read and parse header length
		$header_length_array = unpack( 'v', fread( $this->_fp, 2 ) );
		$header_length = $header_length_array[1];

		// Read and parse the known portion of header data (14 bytes)
		$bin_data = fread($this->_fp, 14);
		$header_data = unpack('Cmajor/Cminor/Vcount/Vuncsize/Vcsize', $bin_data);

		// Load any remaining header data (forward compatibility)
		$rest_length = $header_length - 19;
		if( $rest_length > 0 )
		$junk = fread($this->_fp, $rest_length);
		else
		$junk = '';

		$this->headerData = array(
			'signature' => 			$sig,
			'length' => 			$header_length,
			'major' => 				$header_data['major'],
			'minor' => 				$header_data['minor'],
			'filecount' => 			$header_data['count'],
			'uncompressedsize' => 	$header_data['uncsize'],
			'compressedsize' => 	$header_data['csize'],
			'unknowndata' => 		$junk
		);

		return true;
	}
	
}
?>