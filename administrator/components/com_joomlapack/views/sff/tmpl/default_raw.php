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
sajax_export('toggle','filepane','folderpane');
$null = null;
sajax_handle_client_request( $null );

function toggle($filePath)
{
	jpimport('models.sff', true);
	$model = new JoomlapackModelSff();
	$model->toggleFilter($filePath);
	return true;
}

function filepane( $folder )
{
	jpimport('helpers.sff', true);
	return JoomlapackHelperSff::getFilePane($folder);
}

function folderpane( $folder )
{
	jpimport('helpers.sff', true);
	return JoomlapackHelperSff::getFolderPane($folder);
}