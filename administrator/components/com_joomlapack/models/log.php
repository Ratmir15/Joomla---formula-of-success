<?php
/**
 * @package JoomlaPack
 * @version $id$
 * @license GNU General Public License, version 2 or later
 * @author JoomlaPack Developers
 * @copyright Copyright 2006-2009 JoomlaPack Developers
 * @since 1.3
 */
defined('_JEXEC') or die('Restricted access');

/**
 * Log Model class
 *
 */
class JoomlapackModelLog extends JModel
{
	function getLogFilename()
	{
		// Make sure the registry model is loaded
		if(!class_exists('JoomlapackModelRegistry'))
		{
			jpimport('models.registry',true);
		}

		// Get output directory
		$registry =& JoomlapackModelRegistry::getInstance();
		$outdir = $registry->get('OutputDirectory');

		// Get log's file name
		$logName = $outdir.DS.'joomlapack.log';

		// Tidy up the path to the file
		jimport('joomla.filesystem.file');
		return $logName;
	}
}