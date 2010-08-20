<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 *
 * The main page of the JoomlaPack component is where all the fun takes place :)
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jpimport('helpers.sajax', true);
sajax_init();
sajax_export('testdatabase');
$null = null;
sajax_handle_client_request( $null );

function testdatabase($host, $port, $user, $pass, $database)
{
	$JP_Error_Reporting = @error_reporting(E_ERROR | E_PARSE);

	$host = $host . ($port != '' ? ":$port" : '');

	jimport('joomla.database.database');
	jimport('joomla.database.table');
	$conf =& JFactory::getConfig();
	$driver 	= $conf->getValue('config.dbtype');
	$options	= array ( 'driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $pass, 'database' => $database, 'prefix' => '' );

	$database =& JDatabase::getInstance( $options );

	$result = true;
	if ( JError::isError($database) ) $result = false;
	//if ($database->getErrorNum() > 0) $result = false;

	@error_reporting($JP_Error_Reporting);
	
	return $result;
}
