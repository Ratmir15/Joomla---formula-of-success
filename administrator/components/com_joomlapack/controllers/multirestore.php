<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

// Load framework base classes
jimport('joomla.application.component.controller');

/**
 * The Multiple Databases restore page
 *
 */
class JoomlapackControllerMultirestore extends JController
{
	/**
	 */
	function display()
	{
		$task = JRequest::getCmd('task', 'extract');
		if($task == 'extract') $this->extract();
		parent::display();
	}

	/**
	 * Extracts the package holding the database dump files to the temporary directory
	 */
	function extract()
	{
		$id = JRequest::getInt('id'); // Backup record ID
		$offset = JRequest::getInt('offset', null); // Offset in backup file; null means we'll start reading it in this step

		// Check that we have a valid-looking ID
		if($id <= 0)
		{
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_INVALIDID'), 'error');
			parent::display();
			return;
		}

		// Get the backup record
		$model =& $this->getModel('statistics');
		$model->setId($id);
		$record =& $model->getStatistic();

		if(!is_object($record))
		{
			// The ID does not correspond to a backup record
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_INVALIDID'), 'error');
			parent::display();
			return;
		}

		// Check against valid ID's and that the file exists
		$validIDs =& $model->getValidLookingBackupFiles();
		jimport('joomla.filesystem.file');
		$isValid = in_array($id, $validIDs) && JFile::exists($record->absolute_path);

		if(!$isValid)
		{
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_INVALIDID'), 'error');
			parent::display();
			return;
		}

		// get archive's filename and the path to the configured temporary folder location
		$configuration =& JoomlapackModelRegistry::getInstance();
		$archiveFilename = $record->absolute_path;
		$addPrefix = $configuration->getTemporaryDirectory().DS;

		// Extract one more file
		$extension = strtolower(JFile::getExt($archiveFilename));
		switch($extension)
		{
			case 'zip': // A ZIP archive
				jpimport('misc.unarchiverparent');
				jpimport('misc.unzip');
				$config = array(
					'filename' => $archiveFilename,
					'add_path' => $addPrefix
				);
				$unarchiver = new CSimpleUnzip($config);
				$ret = $unarchiver->ExtractFile($offset);
				if($ret === false)
				{
					// An error occured
					$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_RESTOREDEPLOYARCHIVE'), 'error');
					parent::display();
					return;
				}
				$offset = $ret['offset'];
				$done = $ret['done'];
				break;

			case 'jpa': // A JPA archive
				jpimport('misc.unarchiverparent');
				jpimport('misc.unjpa');
				$config = array(
					'filename' => $archiveFilename,
					'add_path' => $addPrefix
				);
				$unarchiver = new CUnJPA($config);
				$ret = $unarchiver->Extract($offset);

				if($ret === false)
				{
					// An error occured
					$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_RESTOREDEPLOYARCHIVE'), 'error');
					parent::display();
					return;
				}
				$offset = $ret['offset'];
				$done = $ret['done'];
				break;

			default: // Other archive types are NOT supported!
				$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_RESTOREMULTIUNKNOWNEXT').' '.$extension, 'error');
				parent::display();
				return;

				break;
		}

		// Which is the next page? It depends in the $done value
		if(!$done)
		{
			// We are not done. Carry over the archive offset and go on.
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=multirestore&task=extract&id='.$id.'&offset='.$offset);
			parent::display();
			return;
		}
		else
		{
			// We are done. Proceed to the last step (create datarestore.php)
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=multirestore&task=ready&id='.$id);
			parent::display();
			return;
		}
	}

	/**
	 * We are ready to start the restoration. Generates a password protected datarestore.php
	 * and places it on the site's root. Then presents the user with the information page.
	 */
	function ready()
	{
		$filename = JPATH_COMPONENT_ADMINISTRATOR.DS.'assets'.DS.'scripts'.DS.'datarestore.jpa';
		$ret =& $this->_extract($filename);
		if($ret === false)
		{
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_RESTOREREADJPA'), 'error');
			parent::display();
			return;
		}

		// Read databases.ini from the disk and make it into an array
		$configuration =& JoomlapackModelRegistry::getInstance();
		jpimport('helpers.utils',true);
		$originalDBINI = JoomlapackHelperUtils::parse_ini_file($configuration->getTemporaryDirectory().DS.'databases.ini', true);
		$databaseArray = array();
		$counter = 0;
		foreach($originalDBINI as $dbDef)
		{
			$counter++;
			$newEntry = array(
				'host'		=> $dbDef['dbhost'],
				'username'	=> $dbDef['dbuser'],
				'password'	=> $dbDef['dbpass'],
				'database'	=> $dbDef['dbname'],
				'prefix'	=> $dbDef['prefix'],
				'dumpFile'	=> $configuration->getTemporaryDirectory().DS.$dbDef['sqlfile']
			);
			$databaseArray['db'.$counter] = $newEntry;
		}

		// Ask for a fresh password
		$bumodel =& $this->getModel('buadmin');
		$password = $bumodel->getRandomPassword();

		// Append password information and encrypted array to data
		jpimport('misc.cryptography');
		$serialized = serialize($databaseArray);
		$encryption = new cryptography();
		$encryption->set_key($password);
		$encrypted = $encryption->encrypt($serialized);
		$md5 = md5($password);
		$append = "<?php\ndefine('passwordHash', '$md5');\ndefine('encrypted', '$encrypted');\n?>\n";
		$ret['data'] = $append . $ret['data'];

		// Write datarestore.php
		$filename = JPATH_SITE.DS.'datarestore.php';
		if(!JFile::write($filename, $ret['data']))
		{
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_RESTOREDEPLOY'), 'error');
			parent::display();
			return;
		}

		// Set the linktarget
		$URIbase = JURI::base();
		$adminPos = strrpos($URIbase, '/administrator');
		$URIbase = substr($URIbase, 0, $adminPos);
		$linktarget = $URIbase .'/datarestore.php';
		JRequest::setVar('linktarget', $linktarget);
		parent::display();
	}

	/**
	 * Extracts the first file from the JPA archive and returns an in-memory array containing it
	 * and its file data. The data returned is an array, consisting of the following keys:
	 * "filename" => relative file path stored in the archive
	 * "data"     => file data
	 *
	 * @param string $filename The filename of the archive to read from
	 * @return array See description for more information
	 */
	function &_extract( $filename )
	{
		static $fp;

		$false = false; // Used to return false values in case an error occurs

		// Generate a return array
		$retArray = array(
			"filename"			=> '',		// File name extracted
			"data"				=> '',		// File data
		);

		$fp = @fopen($filename, 'rb');

		// If we can't open the file, return an error condition
		if( $fp === false ) return $false;

		// Go to the beggining of the file
		rewind( $fp );

		// Read the signature
		$sig = fread( $fp, 3 );

		if ($sig != 'JPA') return false; // Not a JoomlaPack Archive?

		// Read and parse header length
		$header_length_array = unpack( 'v', fread( $fp, 2 ) );
		$header_length = $header_length_array[1];

		// Read and parse the known portion of header data (14 bytes)
		$bin_data = fread($fp, 14);
		$header_data = unpack('Cmajor/Cminor/Vcount/Vuncsize/Vcsize', $bin_data);

		// Load any remaining header data (forward compatibility)
		$rest_length = $header_length - 19;
		if( $rest_length > 0 ) $junk = fread($fp, $rest_length);

		// Get and decode Entity Description Block
		$signature = fread($fp, 3);

		// Check signature
		if( $signature == 'JPF' )
		{
			// This a JPA Entity Block. Process the header.
				
			// Read length of EDB and of the Entity Path Data
			$length_array = unpack('vblocksize/vpathsize', fread($fp, 4));
			// Read the path data
			$file = fread( $fp, $length_array['pathsize'] );
			// Read and parse the known data portion
			$bin_data = fread( $fp, 14 );
			$header_data = unpack('Ctype/Ccompression/Vcompsize/Vuncompsize/Vperms', $bin_data);
			// Read any unknwon data
			$restBytes = $length_array['blocksize'] - (21 + $length_array['pathsize']);
			if( $restBytes > 0 ) $junk = fread($fp, $restBytes);
				
			$compressionType = $header_data['compression'];
				
			// Populate the return array
			$retArray['filename'] = $file;

			switch( $header_data['type'] )
			{
				case 0:
					// directory
					break;
						
				case 1:
					// file
					switch( $compressionType )
					{
						case 0: // No compression
							if( $header_data['compsize'] > 0 ) // 0 byte files do not have data to be read
							{
								$retArray['data'] = fread( $fp, $header_data['compsize'] );
							}
							break;
								
						case 1: // GZip compression
							$zipData = fread( $fp, $header_data['compsize'] );
							$retArray['data'] = gzinflate( $zipData );
							break;
								
						case 2: // BZip2 compression
							$zipData = fread( $fp, $header_data['compsize'] );
							$retArray['data'] = bzdecompress( $zipData );
							break;
					}
					break;
			}
			@fclose($fp);
			return $retArray;
		} else {
			// This is not a file header. This means we are done.
			@fclose($fp);
			return $retArray;
		}
	}

}
?>