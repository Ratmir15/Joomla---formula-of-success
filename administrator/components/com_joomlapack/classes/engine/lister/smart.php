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
 * A filesystem scanner which uses opendir() and is smart enough to make large directories
 * be scanned inside a step of their own.
 *
 * The idea is that if it's not the first operation of this step and the number of contained
 * directories AND files is more than double the number of allowed files per fragment, we should
 * break the step immediately.
 *
 */
class JoomlapackListerSmart extends JoomlapackCUBELister
{
	function &getFiles($folder)
	{
		// Was the breakflag set BEFORE starting? -- This workaround is required due to PHP5 defaulting to assigning variables by reference
		$breakflag_before_process = $this->BREAKFLAG ? true : false;
		// Reset break flag before continuing
		$this->BREAKFLAG = false;
		
		// Initialize variables
		$arr = array();
		$false = false;

		if(!is_dir($folder)) return $false;

		$counter = 0;
		$registry =& JoomlapackModelRegistry::getInstance();
		$maxCounter = $registry->get('mnMaxFragmentFiles',50) * 2;

		$cube =& JoomlapackCUBE::getInstance();
		$allowBreakflag = ($cube->operationCounter != 0) && !$breakflag_before_process;

		$handle = @opendir($folder);
		// If directory is not accessible, just return FALSE
		if ($handle === FALSE) {
			$cube->addWarning( 'Unreadable directory '.$folder);
			return $false;
		}

		while ( (($file = @readdir($handle)) !== false) && (!$this->BREAKFLAG) )
		{
			if (($file != '.') && ($file != '..'))
			{
				// # Fix 2.4.b1: Do not add DS if we are on the site's root and it's an empty string
				// # Fix 2.4.b2: Do not add DS is the last character _is_ DS
				$ds = ($folder == '') || ($folder == '/') || (@substr($folder, -1) == '/') || (@substr($folder, -1) == DS) ? '' : DS;
				$dir = $folder . $ds . $file;
				$isDir = is_dir($dir);
				if (!$isDir) {
					$data = JPISWINDOWS ? JoomlapackHelperUtils::TranslateWinPath($dir) : $dir;
					if($data) $arr[] = $data;
				}
			}
			$counter++;
			if($counter >= $maxCounter) $this->BREAKFLAG = $allowBreakflag;
		}
		@closedir($handle);

		return $arr;
	}

	function &getFolders($folder)
	{
		// Was the breakflag set BEFORE starting? -- This workaround is required due to PHP5 defaulting to assigning variables by reference
		$breakflag_before_process = $this->BREAKFLAG ? true : false;
		// Reset break flag before continuing
		$this->BREAKFLAG = false;
		
		// Initialize variables
		$arr = array();
		$false = false;

		if(!is_dir($folder)) return $false;

		$counter = 0;
		$registry =& JoomlapackModelRegistry::getInstance();
		$maxCounter = $registry->get('mnMaxFragmentFiles',50) * 2;

		$cube =& JoomlapackCUBE::getInstance();
		$allowBreakflag = ($cube->operationCounter != 0) && !$breakflag_before_process;

		$handle = @opendir($folder);
		// If directory is not accessible, just return FALSE
		if ($handle === FALSE) {
			$cube =& JoomlapackCUBE::getInstance();
			$cube->addWarning('Unreadable directory '.$folder);
			return $false;
		}

		while ( (($file = @readdir($handle)) !== false) && (!$this->BREAKFLAG) )
		{
			if (($file != '.') && ($file != '..'))
			{
				// # Fix 2.4: Do not add DS if we are on the site's root and it's an empty string
				$ds = ($folder == '') || ($folder == '/') || (@substr($folder, -1) == '/') || (@substr($folder, -1) == DS) ? '' : DS;
				$dir = $folder . $ds . $file;
				$isDir = is_dir($dir);
				if ($isDir) {
					$data = JPISWINDOWS ? JoomlapackHelperUtils::TranslateWinPath($dir) : $dir;
					if($data) $arr[] = $data;
				}
			}
			$counter++;
			if($counter >= $maxCounter) $this->BREAKFLAG = $allowBreakflag;
		}
		@closedir($handle);

		return $arr;
	}
}
