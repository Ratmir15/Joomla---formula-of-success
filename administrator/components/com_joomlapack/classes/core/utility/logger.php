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
(defined( '_VALID_MOS' ) || defined('_JEXEC')) or die( 'Direct Access to this location is not allowed.' );

class JoomlapackLogger
{
	/**
	 * Clears the logfile
	 */
	function ResetLog() {
		$logName = JoomlapackLogger::logName();
		jimport('joomla.filesystem.file');
		if(JFile::exists($logName))
		{
			JFile::delete($logName);
		}
		@touch($logName);
	}

	/**
	 * Writes a line to the log, if the log level is high enough
	 *
	 * @param int|bool $level The log level (_JP_LOG_XX constants). Use FALSE to pause logging, TRUE to resume logging
	 * @param string $message The message to write to the log
	 */
	function WriteLog( $level, $message )
	{
		static $configuredLoglevel;
		static $site_root;
		
		if(empty($site_root))
		{
			if(!class_exists('JoomlapackHelperUtils'))
				jpimport('helpers.utils', true);
			$site_root = JoomlapackHelperUtils::TranslateWinPath(JPATH_SITE);
		}
		
		if(empty($configuredLoglevel) or ($level === true))
		{
			// Load the registry
			if(!class_exists('JoomlapackModelRegistry'))
			{
				jpimport('models.registry', true);
			}
			$registry =& JoomlapackModelRegistry::getInstance();
	
			// Fetch log level
			$configuredLoglevel = $registry->get('logLevel');			
		}
		
		if($level === false)
		{
			// Pause logging
			$configuredLogLevel = false;
			return;
		}
		
		// Catch paused logging
		if($configuredLoglevel === false) return;

		if( ($configuredLoglevel >= $level) && ($configuredLoglevel != 0))
		{
			$logName = JoomlapackLogger::logName();
			$message = str_replace( JPATH_SITE, "<root>", $message );
			$message = str_replace( $site_root, "<root>", $message ); // Fix 2.4 - Also replace the translated path on Windows machines
			$message = str_replace( "\n", ' \n ', $message ); // Fix 1.1.1 - Handle (error) messages containing newlines (by nicholas)
			switch( $level )
			{
				case _JP_LOG_ERROR:
					$string = "ERROR   |";
					break;
				case _JP_LOG_WARNING:
					$string = "WARNING |";
					break;
				case _JP_LOG_INFO:
					$string = "INFO    |";
					break;
				default:
					$string = "DEBUG   |";
					break;
			}
			$string .= @strftime( "%y%m%d %T" ) . "|$message\r\n";
			$fp = @fopen( $logName, "at" );
			if (!($fp === FALSE))
			{
				@fwrite( $fp, $string );
				@fclose( $fp );
			}
		}
	}

	/**
	 * Calculates the absolute path to the log file
	 */
	function logName()
	{
		if(!class_exists('JoomlapackModelLog'))	jpimport('models.log', true);
		return JoomlapackModelLog::getLogFilename();
	}

}