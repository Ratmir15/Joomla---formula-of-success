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
sajax_export('toggleFiles', 'toggleDirectories', 'folderpane');
$null = null;
sajax_handle_client_request( $null );

function toggleFiles($filePath)
{
	jpimport('models.skip', true);
	$model = new JoomlapackModelSkip();
	$model->toggleFilesFilter($filePath);
	return true;
}

function toggleDirectories($filePath)
{
	jpimport('models.skip', true);
	$model = new JoomlapackModelSkip();
	$model->toggleDirectoriesFilter($filePath);
	return true;
}

function folderpane( $folder )
{
	jpimport('helpers.skip', true);
	return JoomlapackHelperSkip::getFolderPane($folder);
}