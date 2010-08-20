<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 */

defined('_JEXEC') or die('Restricted access');

/**
 * HTML render class for the Log page
 *
 */
class JoomlapackHelperLog extends JObject
{
	function renderFormattedLog()
	{
		// -- Get the log's file name
			
		// Make sure the model is loaded
		if(!class_exists('JoomlapackModelLog'))
		{
			jpimport('models.log',true);
		}

		// Get log's file name
		$model = new JoomlapackModelLog();
		$logName = $model->getLogFilename();

		// Load JFile class
		jimport('joomla.filesystem.file');

		if(!JFile::exists($logName))
		{
			// Oops! The log doesn't exist!
			echo '<p>'.JText::_('LOG_ERROR_LOGFILENOTEXISTS').'</p>';
			return;
		}
		else
		{
			// Allright, let's load and render it
			$fp = fopen( $logName, "rt" );
			if ($fp === FALSE)
			{
				// Oops! The log isn't readable?!
				echo '<p>'.JText::_('LOG_ERROR_UNREADABLE').'</p>';
				return;
			}
				
			while( !feof($fp) )
			{
				$line = fgets( $fp );
				if(!$line) return;
				$exploded = explode( "|", $line, 3 );
				unset( $line );
				switch( trim($exploded[0]) )
				{
					case "ERROR":
						$fmtString = "<span style=\"color: red; font-weight: bold;\">[";
						break;
					case "WARNING":
						$fmtString = "<span style=\"color: #D8AD00; font-weight: bold;\">[";
						break;
					case "INFO":
						$fmtString = "<span style=\"color: black;\">[";
						break;
					case "DEBUG":
						$fmtString = "<span style=\"color: #666666; font-size: small;\">[";
						break;
					default:
						$fmtString = "<span style=\"font-size: small;\">[";
						break;
				}
				$fmtString .= $exploded[1] . "] " . htmlspecialchars($exploded[2]) . "</span><br/>\n";
				unset( $exploded );
				echo $fmtString;
				unset( $fmtString );
				ob_flush();
			}
		}
	}
}