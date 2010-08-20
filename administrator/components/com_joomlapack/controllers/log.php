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
 * Log view controller class
 *
 */
class JoomlapackControllerLog extends JController
{
	/**
	 * Display the log page
	 *
	 */
	function display()
	{
		parent::display();
	}

	// Renders the contents of the log's iframe
	function iframe()
	{
		parent::display();
	}

	function download()
	{
		$model =& $this->getModel('log');
		$filename = $model->getLogFilename();

		@ob_end_clean(); // In case some braindead plugin spits its own HTML
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		header("Content-Description: File Transfer");
		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename="JoomlaPack Debug Log.txt"');
		echo "WARNING: Do not copy and paste lines from this file!\r\n";
		echo "You are supposed to ZIP and attach it in your support forum post.\r\n";
		echo "If you fail to do so, your support request will receive minimal priority.\r\n";
		echo "\r\n";
		echo "--- START OF RAW LOG --\r\n";
		@readfile($filename); // The at sign is necessary to skip showing PHP errors if the file doesn't exist or isn't readable for some reason
		echo "--- END OF RAW LOG ---\r\n";
	}
}