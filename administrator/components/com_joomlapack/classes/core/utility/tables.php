<?php
/**
 * @package		JoomlaPack
 * @copyright	Copyright (C) 2006-2009 JoomlaPack Developers. All rights reserved.
 * @version		$Id$
 * @license 		http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @since		1.2.1
 * @version		1.3
 *
 * JoomlaPack is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 **/

// Ensure this file is being included by a parent file - Joomla! 1.0.x and 1.5 compatible
(defined( '_VALID_MOS' ) || defined('_JEXEC')) or die( 'Direct Access to this location is not allowed.' );

/**
 * Provides static functions to reading / writing data to & from JoomlaPack's database table
 * (the #__jp_temp table)
 */
class JoomlapackCUBETables
{
	/**
	 * Writes a variable to the database (#__jp_temp)
	 *
	 * @param string $varName The name of the variable (must be unique; if it exists it gets overwritten)
	 * @param string $value The value to store
	 * @static
	 */
	function WriteVar( $varName, $value ){
		$db =& JFactory::getDBO();

		// Hopefully fixes the 'MySQL server has gone away' errors by testing MySQL
		// connection status and reconnecting if necessary.
		//$db->connected();

		// Kill exisiting variable (if any)
		JoomlapackCUBETables::DeleteVar( $varName );

		// Base64-encode the value
		$value2 = JoomlapackCUBETables::_getBase64() ? base64_encode($value) : $value;

		// Create variable
		// OMG! $db-Quote DOES NOT encode 'key' into `key`, thus causing havoc. I have
		// to use backticks manually. BUMMER! AAARGH!
		$sql = 'INSERT INTO #__jp_temp (`key`,`value`)'.
				' VALUES ('.$db->Quote( $varName ).', '.$db->Quote( $value2 ).')';
		$db->setQuery( $sql );
		if($db->query() === false)
		{
			JoomlapackLogger::WriteLog(_JP_LOG_ERROR,JText::sprintf('CUBE_TABLES_FAILEDSTORE', $varName));
			JoomlapackLogger::WriteLog(_JP_LOG_ERROR,'Error: '.$db->getErrorMsg());
			JoomlapackLogger::WriteLog(_JP_LOG_ERROR,'Value: '.$value);
			return false;
		}

		return true;
	}

	/**
	 * Reads a variable out of #__jp_packvars
	 *
	 * @param string $key The name of the variable to read
	 * @param bool $boolLongText True if you want to store a large string; deprecated since 1.1.2b
	 * @return string
	 * @static
	 */
	function ReadVar( $key, $boolLongText = false ) {
		$db =& JFactory::getDBO();

		$sql = 'SELECT `value` FROM '.$db->nameQuote('#__jp_temp').
				' WHERE `key` = '.$db->Quote($key);
		$db->setQuery( $sql );
		$db->query();
		$value2 = $db->loadResult();
		return JoomlapackCUBETables::_getBase64() ? base64_decode($value2) : $value;
	}

	/**
	 * Removes a variable from #__jp_packvars
	 *
	 * @param string $varName The variable to remove
	 * @static
	 */
	function DeleteVar( $varName )
	{
		$db = JFactory::getDBO();
		$sql = 'DELETE FROM '.$db->nameQuote('#__jp_temp').' WHERE '.
				'`key` = '.$db->Quote($varName);
		$db->setQuery($sql);
		$db->query();

		if($varName == 'CUBEObject')
		{
			if( JoomlapackCUBETables::_isSetCUBEInFile())
			JoomlapackCUBETables::_deleteCUBEObject();
		}
	}

	/**
	 * Removes all variables matching a pattern from #__jp_packvars
	 *
	 * @param string $keyPattern The name pattern the variables to be removed must follow
	 * @static
	 */
	function DeleteMultipleVars( $keyPattern )
	{
		$db = JFactory::getDBO();
		$sql = 'DELETE FROM '.$db->nameQuote('#__jp_temp').' WHERE '.
				'`key` LIKE '.$db->Quote($keyPattern);
		$db->setQuery($sql);
		$db->query();

		if($keyPattern == '%CUBE%')
		{
			if( JoomlapackCUBETables::_isSetCUBEInFile())
			JoomlapackCUBETables::_deleteCUBEObject();
		}
	}

	/**
	 * Counts the number of instances for a specific variable
	 *
	 * @param string $key The varaible's name
	 * @return string
	 */
	function CountVar( $key )
	{
		if($key == 'CUBEObject')
		{
			if(JoomlapackCUBETables::_isSetCUBEInFile())
			{
				$cubeTmpFile = JoomlapackCUBETables::ReadVar('cubetmpfile');
				return file_exists($cubeTmpFile) ? 1 : 0;
			}
		}

		$db =& JFactory::getDBO();
		$sql = 'SELECT `key` FROM '.$db->nameQuote('#__jp_temp').
				' WHERE `key` = '.$db->Quote($key);
		$db->setQuery( $sql );
		$db->query();
		$numRows = $db->getNumRows();
		return $numRows;
	}

	/**
	 * Reads and unserializes a packvar variable (combo function)
	 *
	 * @param string $varName The variable name to read
	 * @param mixed $default The default unserialized data to return if the $varName doesn't exist
	 * @return mixed The unserialized value read from database
	 */
	function UnserializeVar( $varName, $default = null )
	{
		if($varName == 'CUBEObject')
		{
			if(JoomlapackCUBETables::_isSetCUBEInFile())
			{
				return JoomlapackCUBETables::_loadCUBEObjectFromFile();
			}
		}

		$count = JoomlapackCUBETables::CountVar($varName);
		if( $count >=1 )
		{
			$serialized = JoomlapackCUBETables::ReadVar($varName);
			return unserialize($serialized);
		}
		else
		{
			return $default;
		}
	}

	/**
	 * Writes a serialized copy of the $contentVariable to the database, under the packvar
	 * variable name of $varName.
	 *
	 * @param string $varName The packvar to create
	 * @param mixed $contentVariable Any variable to serialize (e.g. object, array, other variables, etc)
	 */
	function SerializeVar( $varName, &$contentVariable )
	{
		if($varName == 'CUBEObject')
		{
			if(JoomlapackCUBETables::_isSetCUBEInFile())
			{
				JoomlapackCUBETables::_saveCUBEObjectToFile($contentVariable);
				return;
			}
		}

		$serialized = serialize($contentVariable);
		JoomlapackCUBETables::WriteVar($varName, $serialized);
	}

	/**
	 * Is BASE64 encoding available?
	 *
	 * @return bool
	 */
	function _getBase64()
	{
		static $_hasBase64 = null;

		if(is_null($_hasBase64))
		{
			$_hasBase64 = function_exists('base64_encode') && function_exists('base64_decode');
		}

		return $_hasBase64;
	}

	/**
	 * Checks if the option to store the CUBE file in Joomla's tmp directory is enabled
	 * @return bool
	 */
	function _isSetCUBEInFile()
	{
		static $isSetCUBEInFile;

		if(is_null($isSetCUBEInFile))
		{
			$registry =& JoomlapackModelRegistry::getInstance();
			$isSetCUBEInFile = $registry->get('cubeinfile', true);
		}

		return $isSetCUBEInFile;
	}

	/**
	 * Returns the stored path to the temporary CUBEObject file, or creates a new one
	 * @param int $createNew 0 = create if it doesn't exist, 1 = do not create new, 2 = force create new
	 * @return unknown_type
	 */
	function _cubeTmpFile($createNew = 0)
	{
		$cubeTmpFile = JoomlapackCUBETables::ReadVar('cubetmpfile');
		$createNew = false;

		// 1. Search if the filename is not empty
		if( ($cubeTmpFile == '') && ($createNew == 0) )
		{
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "No CUBEObject storage file set; I will create a new one.");
			$createNew = true;
		}
		else
		{
			// 2. If the filename is not empty, make sure it exists
			$createNew = !file_exists($cubeTmpFile);
			if($createNew) JoomlapackLogger::WriteLog(_JP_LOG_INFO, "The CUBEObject storage file doesn't exist on disk; I will create a new one.");
			$createNew = $createNew && ($createNew == 0);
			$createNew = $createNew || ($createNew == 2);
		}

		// 3. If the filename was empty or the file doesn't exist, create a fresh copy
		if($createNew)
		{
			$registry =& JoomlapackModelRegistry::getInstance();
			$cubeTmpFile = tempnam($registry->getTemporaryDirectory(),'jp');
			JoomlapackCUBETables::WriteVar('cubetmpfile',$cubeTmpFile);
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Creating new CUBEObject storage file: " . $cubeTmpFile );
		}

		return $cubeTmpFile;
	}

	/**
	 * Saves CUBE dump to a temporary file
	 *
	 * @param string $contentVariable
	 */
	function _saveCUBEObjectToFile(&$contentVariable)
	{
		$tmpfile = JoomlapackCUBETables::_cubeTmpFile();
		file_put_contents($tmpfile,serialize($contentVariable));
		JoomlapackCUBETables::WriteVar('cubetmpfile',$tmpfile);
	}

	function _loadCUBEObjectFromFile()
	{
		$tmpfile = JoomlapackCUBETables::_cubeTmpFile();
		return unserialize(file_get_contents($tmpfile));
	}

	function _deleteCUBEObject()
	{
		$tmpfile = JoomlapackCUBETables::_cubeTmpFile(1);
		if(!empty($tmpfile))
		{
			JoomlapackLogger::WriteLog(_JP_LOG_INFO, "Removing CUBE temporary file: " . $tmpfile );
			@unlink($tmpfile);
		}
		JoomlapackCUBETables::DeleteVar('cubetmpfile');
	}
}