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
sajax_export('toggle','folderpane');
$null = null;
sajax_handle_client_request( $null );

function toggle($filePath)
{
	jpimport('models.def', true);
	$model = new JoomlapackModelDef();
	$model->toggleFilter($filePath);
	return true;
}

function folderpane( $folder )
{
	jpimport('helpers.def', true);
	return JoomlapackHelperDef::getFolderPane($folder);
}
?>