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

jpimport('helpers.sajax',true);
sajax_init();
sajax_force_page_ajax('backup');
sajax_export('start', 'tick', 'renderProgress', 'addWarning');
$null = null;
sajax_handle_client_request( $null );

function start($description, $comment)
{
	jpimport('core.cube');
	JoomlapackCUBE::reset();
	$cube =& JoomlapackCUBE::getInstance();
	$cube->start($description, $comment);
	$cube->save();
	$array = $cube->getCUBEArray();
	return _processCUBE($array);
}

function tick()
{
	jpimport('core.cube');
	JoomlapackLogger::WriteLog(_JP_LOG_DEBUG,'--- Preparing to get instance');
	$cube =& JoomlapackCUBE::getInstance();
	JoomlapackLogger::WriteLog(_JP_LOG_DEBUG,'--- Got Instance, ready to tick()');
	$cube->tick();
	JoomlapackLogger::WriteLog(_JP_LOG_DEBUG,'--- tick() is finished');
	$cube->save();
	JoomlapackLogger::WriteLog(_JP_LOG_DEBUG,'--- CUBE Saved');
	$array = $cube->getCUBEArray();
	
	return _processCUBE($array);
}

function renderProgress()
{
	jpimport('core.cube');
	jpimport('helpers.backup', true);

	JoomlapackLogger::WriteLog(_JP_LOG_DEBUG,'--- Entering renderProgress()');
	$cube =& JoomlapackCUBE::getInstance();
	JoomlapackLogger::WriteLog(_JP_LOG_DEBUG,'--- renderProgress() got CUBE Instance');
	$array = $cube->getCUBEArray();
	JoomlapackLogger::WriteLog(_JP_LOG_DEBUG,'--- renderProgress() got CUBE Array');
	return JoomlapackHelperBackup::getBackupProcessHTML($array);
}

function addWarning($warning)
{
	jpimport('core.cube');
	jpimport('helpers.backup', true);
	$cube =& JoomlapackCUBE::getInstance();
	$cube->addWarning($warning);
	$cube->save();
	return;
}

/**
 * Processes the CUBE array
 * @param array $array The CUBE Array to process
 * @return  array The action and message
 */
function _processCUBE($array)
{
	if($array['Error'] != '')
	{
		$action = 'error';
		$message = $array['Error'];
	}
	elseif($array['HasRun'] == 1)
	{
		$action = 'finished';
		$message = '';
	}
	else
	{
		$action = 'step';
		$message = '';
	}

	return array($action, $message);
}