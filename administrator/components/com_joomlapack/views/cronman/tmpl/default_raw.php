<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 */

defined('_JEXEC') or die('Restricted access');

jpimport('helpers.sajax', true);

sajax_init();
sajax_export('getDefaultURL','getDefaultSecret', 'testFTP');
$null = null;
sajax_handle_client_request( $null );

function getDefaultURL()
{
	return JURI::root();
}

function getDefaultSecret()
{
	$registry =& JoomlapackModelRegistry::getInstance();
	return $registry->get('secret_key');
}

function testFTP($host, $port, $user, $pass, $initdir, $usessl, $passive)
{
	jpimport('abstract.enginearchiver');
	jpimport('engine.packer.directftp');
	jpimport('core.utility.logger');
	
	$config = array(
		'host' => $host,
		'port' => $port,
		'user' => $user,
		'pass' => $pass,
		'initdir' => $initdir,
		'usessl' => $usessl,
		'passive' => $passive
	);
	
	$test = new JoomlapackPackerDirectftp();
	$test->initialize('', '', $config);
	$errors = $test->getError(); 
	if(empty($errors))
	{
		return true;
	}
	else
	{
		return $errors;
	}
}	
?>