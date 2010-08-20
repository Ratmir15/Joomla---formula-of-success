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
sajax_export('toggle','tablepane');
$null = null;
sajax_handle_client_request( $null );

function toggle($table)
{
	jpimport('models.dbef', true);
	$model = new JoomlapackModelDbef();
	$model->toggleFilter($table);
	return true;
}

function tablepane()
{
	jpimport('helpers.dbef', true);
	return JoomlapackHelperDbef::getTablePane();
}
?>