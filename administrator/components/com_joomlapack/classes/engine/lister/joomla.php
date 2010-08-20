<?php
/**
 * @package		JoomlaPack
 * @copyright	Copyright (C) 2006-2009 JoomlaPack Developers. All rights reserved.
 * @version		$Id$
 * @license 		http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @since		1.3
 **/
defined('_JEXEC') or die('Restricted access');

if (function_exists('php_uname'))
define('JPISWINDOWS', stristr(php_uname(), 'windows'));
else
define('JPISWINDOWS', DS == '\\');

/**
 * A filesystem scanner which uses the Joomla! Framework filesystem classes
 *
 */
class JoomlapackListerJoomla extends JoomlapackCUBELister
{
	function &getFiles($folder)
	{
		//JoomlapackLogger::WriteLog(_JP_LOG_DEBUG,'Getting files list for '.$folder);
		jimport('joomla.filesystem.folder');
		$ds = ($folder == '') || ($folder == '/') || (@substr($folder, -1) == '/') || (@substr($folder, -1) == DS) ? '' : DS;
		$temp = JFolder::files($folder,'.',false,false);
		$ret = array();
		if(!empty($temp))
		{
			foreach($temp as $file)
			{
				$file = $folder.$ds.$file;
				$ret[] = JPISWINDOWS ? JoomlapackHelperUtils::TranslateWinPath($file) : $file;
			}
		}
		return $ret;
	}

	function &getFolders($folder)
	{
		//JoomlapackLogger::WriteLog(_JP_LOG_DEBUG,'Getting folder list for '.($folder));
		jimport('joomla.filesystem.folder');
		$ds = ($folder == '') || ($folder == '/') || (@substr($folder, -1) == '/') || (@substr($folder, -1) == DS) ? '' : DS;
		$temp = JFolder::folders($folder,'.',false,false,array('.','..'));
		$ret = array();
		if(!empty($temp))
		{
			foreach($temp as $file)
			{
				$file = $folder.$ds.$file;
				$ret[] = JPISWINDOWS ? JoomlapackHelperUtils::TranslateWinPath($file) : $file;
			}
		}
		return $ret;
	}
}