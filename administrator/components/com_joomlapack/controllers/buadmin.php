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
 * The Backup Administrator class
 *
 */
class JoomlapackControllerBuadmin extends JController
{
	/**
	 * Show a list of backup attempts
	 *
	 */
	function display()
	{
		parent::display();
	}

	/**
	 * Downloads the backup file of a specific backup attempt,
	 * if it's available
	 *
	 */
	function download()
	{
		$cid = JRequest::getVar('cid',array(),'default','array');
		$id = JRequest::getInt('id');
		$part = JRequest::getInt('part',999);

		if(empty($id))
		{
			if(is_array($cid) && !empty($cid))
			{
				$id = $cid[0];
			}
			else
			{
				$id = -1;
			}
		}

		if($id <= 0)
		{
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_INVALIDID'), 'error');
			parent::display();
			return;
		}

		$model =& $this->getModel('statistics');
		$model->setId($id);
		$statentry =& $model->getStatistic();

		if($statentry->multipart == 0)
		{
			// Single part backup
			$filename = $model->getFilename($id);
		}
		else
		{
			// Multi-part backup
			$allFilenames = $model->getAllFilenames($id);
			if(!(@count($allFilenames) > 0))
			{
				$filename = null;
			}
			else
			{
				if($part <= count($allFilenames))
				{
					$filename = $allFilenames[$part];
				}
				else
				{
					$filename = null;
				}
			}
		}

		jimport('joomla.filesystem.file');

		if(is_null($filename) || empty($filename) || !JFile::exists($filename) )
		{
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_INVALIDDOWNLOAD'), 'error');
			parent::display();
			return;
		}
		else
		{
			$basename = @basename($filename);

			JRequest::setVar('format','raw');
			@ob_end_clean();
			@clearstatcache();
			header('Content-Disposition: attachment; filename='.$basename);
			header('MIME-Version: 1.0');
			header('Content-Transfer-Encoding: binary');
			header('Content-Type: application/zip');
			header('Content-Length: '.filesize($filename));
			header('Cache-Control: no-cache');
			@readfile($filename); die();
		}

	}

	/**
	 * Deletes one or several backup statistics records and their associated backup files
	 */
	function remove()
	{
		$cid = JRequest::getVar('cid',array(),'default','array');
		$id = JRequest::getInt('id');
		if(empty($id))
		{
			if(!empty($cid) && is_array($cid))
			{
				foreach ($cid as $id)
				{
					$result = $this->_remove($id);
					if(!$result) $this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_INVALIDID'), 'error');
				}
			}
			else
			{
				$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_INVALIDID'), 'error');
				return;
			}
		}
		else
		{
			$result = $this->_remove($id);
			if(!$result) $this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_INVALIDID'), 'error');
		}

		$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_MSG_DELETED'));

		parent::display();
	}

	/**
	 * Deletes backup files associated to one or several backup statistics records
	 */
	function deletefiles()
	{
		$cid = JRequest::getVar('cid',array(),'default','array');
		$id = JRequest::getInt('id');
		if(empty($id))
		{
			if(!empty($cid) && is_array($cid))
			{
				foreach ($cid as $id)
				{
					$result = $this->_removeFiles($id);
					if(!$result) $this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_INVALIDID'), 'error');
				}
			}
			else
			{
				$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_INVALIDID'), 'error');
				return;
			}
		}
		else
		{
			$result = $this->_remove($id);
			if(!$result) $this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_INVALIDID'), 'error');
		}

		$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_MSG_DELETEDFILE'));

		parent::display();
	}

	/**
	 * Removes the backup file linked to a statistics entry and the entry itself
	 *
	 * @return bool True on success
	 */
	function _remove($id)
	{
		if($id <= 0)
		{
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_INVALIDID'), 'error');
			return;
		}

		$model =& $this->getModel('statistics');
		$model->setId($id);
		if($model->delete())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Removes only the backup file linked to a statistics entry
	 *
	 * @return bool True on success
	 */
	function _removeFiles($id)
	{
		if($id <= 0)
		{
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_INVALIDID'), 'error');
			return;
		}

		$model =& $this->getModel('statistics');
		$model->setId($id);
		if($model->deleteFile())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function showcomment()
	{
		$cid = JRequest::getVar('cid',array(),'default','array');
		$id = JRequest::getInt('id');

		if(empty($id))
		{
			if(is_array($cid) && !empty($cid))
			{
				$id = $cid[0];
			}
			else
			{
				$id = -1;
			}
		}

		if($id <= 0)
		{
			$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_INVALIDID'), 'error');
			parent::display();
			return;
		}

		JRequest::setVar('id', $id);

		parent::display();
	}

	function restore()
	{
		// Get the ID
		$cid = JRequest::getVar('cid',array(),'default','array');
		$id = JRequest::getInt('id');

		if(empty($id))
		{
			if(is_array($cid) && !empty($cid))
			{
				$id = $cid[0];
			}
			else
			{
				$id = -1;
			}
		}

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

		// Decide what to do, based on backup type
		switch($record->type)
		{
			case 'full': // Full backup; deploy Kickstart and backup file
				// Read kickstart.php from the archive
				$filename = JPATH_COMPONENT_ADMINISTRATOR.DS.'assets'.DS.'scripts'.DS.'kickstart.jpa';
				$ret =& $this->_extract($filename);
				if($ret === false)
				{
					$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_RESTOREREADJPA'), 'error');
					parent::display();
					return;
				}

				// Ask for a fresh password
				$bumodel =& $this->getModel('buadmin');
				$password = $bumodel->getRandomPassword();

				// Append password information to data
				$append = '<?php define(\'PASSWORD\', \''.$password.'\'); ?>';
				$ret['data'] = $append."\n".$ret['data'];

				// Write kickstart.php
				$filename = JPATH_SITE.DS.'kickstart.php';
				if(!JFile::write($filename, $ret['data']))
				{
					$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_RESTOREDEPLOY'), 'error');
					parent::display();
					return;
				}

				// Copy the archive; if we failed, remove kickstart as well
				$archiveParts = $model->getAllFilenames($id);
				if( !empty($archiveParts) ) foreach($archiveParts as $from)
				{
					$to = JPATH_SITE.DS.basename($from);
					if(!JFile::copy($from, $to))
					{
						JFile::delete(JPATH_SITE.DS.'kickstart.php');
						$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_RESTOREBACKUPDEPLOY'), 'error');
						parent::display();
						return;
					}
				}

				// Set the linktarget
				$URIbase = JURI::base();
				$adminPos = strrpos($URIbase, '/administrator');
				$URIbase = substr($URIbase, 0, $adminPos);
				$linktarget = $URIbase .'/kickstart.php';
				JRequest::setVar('linktarget', $linktarget);

				break;

			case 'dbonly': // Database only; deploy DataRestore and point to backup file
				// Read datarestore.php from the archive
				$filename = JPATH_COMPONENT_ADMINISTRATOR.DS.'assets'.DS.'scripts'.DS.'datarestore.jpa';
				$ret =& $this->_extract($filename);
				if($ret === false)
				{
					$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_RESTOREREADJPA'), 'error');
					parent::display();
					return;
				}

				// Construct the databases.ini-style array for core database
				$conf =& JFactory::getConfig();
				$databaseArray = array(
					'joomla' => array(
						'host'		=> $conf->getValue('config.host'),
						'username'	=> $conf->getValue('config.user'),
						'password'	=> $conf->getValue('config.password'),
						'database'	=> $conf->getValue('config.db'),
						'prefix'	=> $conf->getValue('config.dbprefix'),
						'dumpFile'	=> $record->absolute_path
				)
				);

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

				break;

			case 'extradbonly': // Multiple databases; delegate execution to the multirestore view
				$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=multirestore&id='.$id);
				parent::display();
				return;
				break;

			default:
				// Fail for unknown backup types
				$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', JText::_('STATS_ERROR_RESTOREUNKNOWNTYPE'), 'error');
				parent::display();
				return;

				break;
		}

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

	function save()
	{
		$id = JRequest::getInt('id');
		$description = JRequest::getString('description');
		$comment = JRequest::getVar('comment',null,'default','string',4);

		jpimport('models.statistics', true);
		$model =& JoomlapackModelStatistics::getInstance('Statistics','JoomlapackModel');

		$model->setId(JRequest::getInt('id'));
		$record =& $model->getStatistic();

		$record->description = $description;
		$record->comment = $comment;

		$ok = $model->save($record);
		if($ok) {
			$message = JText::_('STATS_LOG_SAVEDOK');
			$type = 'message';
		} else {
			$message = JText::_('STATS_LOG_SAVEERROR');
			$type = 'error';
		}
		$this->setRedirect(JURI::base().'index.php?option=com_joomlapack&view=buadmin', $message, $type);
	}
}