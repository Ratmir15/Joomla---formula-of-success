<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 */

defined('_JEXEC') or die('Restricted access');

/**
 * A class with utility functions
 */
class JoomlapackHelperUtils extends JObject
{
	/**
	 * Parse an INI file and return an associative array. Since PHP versions before 5.1 are
	 * bitches with regards to INI parsing, I use a PHP-only solution to overcome this
	 * obstacle.
	 *
	 * @param string $file The file to process
	 * @param bool $process_sections True to also process INI sections
	 * @return array An associative array of sections, keys and values
	 */
	function parse_ini_file( $file, $process_sections )
	{
		if( version_compare(PHP_VERSION, '5.1.0', '>=') && function_exists('parse_ini_file') )
		{
			if( function_exists('parse_ini_file') )
			{
				return parse_ini_file($file, $process_sections);
			}
			else
			{
				return JoomlapackHelperUtils::_parse_ini_file($file, $process_sections);
			}
		} else {
			return JoomlapackHelperUtils::_parse_ini_file($file, $process_sections);
		}
	}

	/**
	 * A PHP based INI file parser.
	 *
	 * Thanks to asohn ~at~ aircanopy ~dot~ net for posting this handy function on
	 * the parse_ini_file page on http://gr.php.net/parse_ini_file
	 *
	 * @param string $file Filename to process
	 * @param bool $process_sections True to also process INI sections
	 * @param bool $rawdata If true, the $file contains raw INI data, not a filename
	 * @return array An associative array of sections, keys and values
	 * @access private
	 */
	function _parse_ini_file($file, $process_sections = false, $rawdata = false)
	{
		$process_sections = ($process_sections !== true) ? false : true;

		if(!$rawdata)
		{
			$ini = file($file);
		}
		else
		{
			$file = str_replace("\r","",$file);
			$ini = explode("\n", $file);
		}

		if (count($ini) == 0) {return array();}

		$sections = array();
		$values = array();
		$result = array();
		$globals = array();
		$i = 0;
		foreach ($ini as $line) {
			$line = trim($line);
			$line = str_replace("\t", " ", $line);

			// Comments
			if (!preg_match('/^[a-zA-Z0-9[]/', $line)) {continue;}

			// Sections
			if ($line{0} == '[') {
				$tmp = explode(']', $line);
				$sections[] = trim(substr($tmp[0], 1));
				$i++;
				continue;
			}

			// Key-value pair
			list($key, $value) = explode('=', $line, 2);
			$key = trim($key);
			$value = trim($value);
			if (strstr($value, ";")) {
				$tmp = explode(';', $value);
				if (count($tmp) == 2) {
					if ((($value{0} != '"') && ($value{0} != "'")) ||
					preg_match('/^".*"\s*;/', $value) || preg_match('/^".*;[^"]*$/', $value) ||
					preg_match("/^'.*'\s*;/", $value) || preg_match("/^'.*;[^']*$/", $value) ){
						$value = $tmp[0];
					}
				} else {
					if ($value{0} == '"') {
						$value = preg_replace('/^"(.*)".*/', '$1', $value);
					} elseif ($value{0} == "'") {
						$value = preg_replace("/^'(.*)'.*/", '$1', $value);
					} else {
						$value = $tmp[0];
					}
				}
			}
			$value = trim($value);
			$value = trim($value, "'\"");

			if ($i == 0) {
				if (substr($line, -1, 2) == '[]') {
					$globals[$key][] = $value;
				} else {
					$globals[$key] = $value;
				}
			} else {
				if (substr($line, -1, 2) == '[]') {
					$values[$i-1][$key][] = $value;
				} else {
					$values[$i-1][$key] = $value;
				}
			}
		}

		for($j = 0; $j < $i; $j++) {
			if ($process_sections === true) {
				$result[$sections[$j]] = $values[$j];
			} else {
				$result[] = $values[$j];
			}
		}

		return $result + $globals;
	}

	/**
	 * Reads the JoomlaPack version information out of joomlapack.xml and defines two constants
	 *
	 */
	function getJoomlaPackVersion()
	{
		if(file_exists(JPATH_COMPONENT_ADMINISTRATOR.DS.'version.php'))
		require_once(JPATH_COMPONENT_ADMINISTRATOR.DS.'version.php');

		if(!defined('_JP_VERSION')) define("_JP_VERSION", "svn");
		if(!defined('JPSPECIALEDITION')) define('JPSPECIALEDITION', false);
		if(!defined('_JP_DATE')) {
			jimport('joomla.utilities.date');
			$date = new JDate();
			define( "_JP_DATE", $date->toFormat('%Y-%m-%d') );
		}

		require_once(JPATH_COMPONENT_ADMINISTRATOR.DS.'version.php');
	}

	function getFooter()
	{
		$html = '<p>'.JText::sprintf('COPYRIGHT', date('y')) . '<br/>' .
		JText::_('LICENSE') . '</p>';
		return $html;
	}

	/**
	 * Makes a Windows path more UNIX-like, by turning backslashes to forward slashes.
	 * Since JP 2.0.b1 it takes into account UNC paths, e.g.
	 * \\myserver\some\folder becomes \\myserver/some/folder
	 *
	 * @param string $p_path The path to transform
	 * @return string
	 */
	function TranslateWinPath( $p_path )
	{
		static $is_windows;
		 
		if(empty($is_windows))
		{
			$is_windows =  (DS == '\\');
		}
		 
		$is_unc = false;
		 
		if ($is_windows)
		{
			// Is this a UNC path?
			$is_unc = (substr($p_path, 0, 2) == '//');
			// Change potential windows directory separator
			if ((strpos($p_path, '\\') > 0) || (substr($p_path, 0, 1) == '\\')){
				$p_path = strtr($p_path, '\\', '/');
			}
		}

		// FIX 2.1.b2: Remove multiple slashes
		$p_path = str_replace('///','/',$p_path);
		$p_path = str_replace('//','/',$p_path);

		// Fix UNC paths
		if($is_unc)
		{
			$p_path = '/'.$p_path;
		}

		return $p_path;
	}

	/**
	 * Removes trailing slash or backslash from a pathname
	 *
	 * @param string $path The path to treat
	 * @return string The path without the trailing slash/backslash
	 */
	function TrimTrailingSlash($path)
	{
		$newpath = $path;
		if( substr($path, strlen($path)-1, 1) == '\\' )
		{
			$newpath = substr($path, 0, strlen($path)-1);
		}
		if( substr($path, strlen($path)-1, 1) == '/' )
		{
			$newpath = substr($path, 0, strlen($path)-1);
		}
		return $newpath;
	}

	/**
	 * Expands the archive's template name and returns an absolute path
	 *
	 * @param string $extension The extension to append, defaults to '.zip'
	 * @return string The absolute filename of the archive file requested
	 * @static
	 */
	function getExpandedTarName( $extension = '.zip', $fullPath = true )
	{
		$configuration =& JoomlapackModelRegistry::getInstance();

		// Get the proper extension
		$templateName = $configuration->get('TarNameTemplate');
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Archive template name: $templateName");

		// Get current date/time and express it in user's timezone
		jimport('joomla.utilities.date');
		$user =& JFactory::getUser();
		$userTZ = $user->getParam('timezone',0);
		$dateNow = new JDate();
		$dateNow->setOffset($userTZ);

		// Parse [DATE] tag
		$dateExpanded = $dateNow->toFormat("%Y%m%d");
		$templateName = str_replace("[DATE]", $dateExpanded, $templateName);

		// Parse [TIME] tag
		$timeExpanded = $dateNow->toFormat("%H%M%S");
		$templateName = str_replace("[TIME]", $timeExpanded, $templateName);

		// Parse [HOST] tag
		$uri =& JURI::getInstance();
		$host = $uri->getHost();
		$host = empty($host) ? 'unknown_host' : $uri->getHost();
		$templateName = str_replace("[HOST]", $_SERVER['SERVER_NAME'], $templateName);

		// Parse [RANDOM] tag
		$templateName = str_replace("[RANDOM]", md5(microtime()) , $templateName);

		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Expanded template name: $templateName");

		if($fullPath)
		{
			$path = $configuration->get('OutputDirectory').DS.$templateName.$extension;
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Calculated archive absolute path: $path");
			return JoomlapackHelperUtils::TranslateWinPath( $path );
		}
		else
		{
			return $templateName.$extension;
		}
	}

	/**
	 * Adds a "Help" button on the toolbar. Clicking on it opens a modal popup which loads
	 * the relevant help page from JoomlaPack.net's documentation section. Live help, it is!
	 *
	 * @param string $term The help term (page name minus .html suffix) to load
	 */
	function addLiveHelp( $term )
	{
		$bar = & JToolBar::getInstance('toolbar');
		$bar->appendButton( 'Popup', 'help', 'help', 'http://www.joomlapack.net/help-support-documentation/joomlapack-2x-documentation/'.$term.'.html#maincol', 900, 500 );
	}
}
