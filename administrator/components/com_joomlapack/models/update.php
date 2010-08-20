<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 2.2
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.model');

/**
 * The Live Update model
 *
 */
class JoomlapackModelUpdate extends JModel
{
	/* Two different update sources, depending if it's a stable or an SVN release */
	var $_update_stable = 'http://www.joomlapack.net/update.ini';
	var $_update_svn = 'http://dionysopoulos.me/en/software/bleedingedge/joomlapack/update.ini.raw';

	/**
	 * The URL to the update.ini, containing update information for the JoomlaPack component
	 * @var string
	 */
	var $_update_url = 'http://www.joomlapack.net/update.ini';

	/**
	 * Constructor; dummy for now
	 *
	 */
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Does the server support URL fopen() wrappers?
	 * @return bool
	 */
	function _hasURLfopen()
	{
		// If we are not allowed to use ini_get, we assume that URL fopen is
		// disabled.
		if(!function_exists('ini_get'))
		return false;
			
		if( !ini_get('allow_url_fopen') )
		return false;
			
		return true;
	}

	/**
	 * Does the server support the cURL extension?
	 * @return bool
	 */
	function _hascURL()
	{
		if(!function_exists('curl_exec'))
		{
			return false;
		}

		return true;
	}

	/**
	 * Returns the date and time when the last update check was made.
	 * @return JDate
	 */
	function _lastUpdateCheck()
	{
		jimport('joomla.utilities.date');
		$date = new JDate('2009-01-01');

		jpimport('core.utility.tables');
		if( JoomlapackCUBETables::CountVar('lastupdatecheck') < 1 )
		{
			return $date;
		}
		else
		{
			return JoomlapackCUBETables::UnserializeVar('lastupdatecheck', $date);
		}
	}

	/**
	 * Gets an object with the latest version information, taken from the update.ini data
	 * @return JObject|bool An object holding the data, or false on failure
	 */
	function _getLatestVersion($force = false)
	{
		jpimport('helpers.utils', true);
		JoomlapackHelperUtils::getJoomlaPackVersion();
		if( substr(_JP_VERSION,0,3) == 'svn' )
		{
			$this->_update_url = $this->_update_svn;
		}
		else
		{
			$this->_update_url = $this->_update_stable;
		}

		// Make sure we ask the server at most every 24 hrs (unless $force is true)
		$inidata = false;
		jimport('joomla.utilities.date');
		$curdate = new JDate();
		$lastdate = $this->_lastUpdateCheck();
		$difference = ($curdate->toUnix(false) - $lastdate->toUnix(false)) / 3600;

		if( ($difference < 24) && (JoomlapackCUBETables::CountVar('updateini') >= 1) && (!$force) )
		{
			$inidata = $this->_getUpdateINIcached();
		}

		// Prefer to use cURL if it exists and we don't have cached data
		if( ($inidata == false) && $this->_hascURL() )
		{
			$inidata = $this->_getUpdateINIcURL();
		}

		// If cURL doesn't exist, or if it returned an error, try URL fopen() wrappers
		if( ($inidata == false) && $this->_hasURLfopen() )
		{
			$inidata = $this->_getUpdateINIfopen();
		}

		// If we have a valid update.ini, update the cache and read the version information
		if($inidata != false)
		{
			$this->_setUpdateINIcached($inidata);
				
			$parsed=JoomlapackHelperUtils::_parse_ini_file($inidata,true,true);
			$version = null;
			foreach($parsed as $version => $data)
			{
				$status = isset($data['status']) ? $data['status'] : 'beta';
				$reldate_text = isset($data['reldate']) ? $data['reldate'] : 'now';
				$reldate = new JDate($reldate_text);
				$free = $data['free'];
				$special = $data['special'];
			}
				
			$ret = new JObject;
			if(empty($version))
			{
				return false;
			}
			else
			{
				$ret->version = $version;
				$ret->status = $status;
				$ret->reldate = $reldate;
				$ret->free = $free;
				$ret->special = $special;
			}
			return $ret;
		}

		return false;
	}

	/**
	 * Retrieves the update.ini data using URL fopen() wrappers
	 * @return string|bool The update.ini contents, or FALSE on failure
	 */
	function _getUpdateINIfopen()
	{
	    $options = array( 'http' => array(
	        'max_redirects' => 2,           // stop after 2 redirects
	        'timeout'       => 5,           // timeout on response
	    ) );
	    $context = stream_context_create( $options );
		return @file_get_contents($this->_update_url, false, $context);
	}

	/**
	 * Retrieves the update.ini data using cURL extention calls
	 * @return string|bool The update.ini contents, or FALSE on failure
	 */
	function _getUpdateINIcURL()
	{
		$process = curl_init($this->_update_url);
		curl_setopt($process, CURLOPT_HEADER, 0);
		// Pretend we are IE7, so that webservers play nice with us
		curl_setopt($process, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)');
		curl_setopt($process,CURLOPT_ENCODING , 'gzip');
		curl_setopt($process, CURLOPT_TIMEOUT, 5);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		// The @ sign allows the next line to fail if open_basedir is set or if safe mode is enabled
		@curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		@curl_setopt($process, CURLOPT_MAXREDIRS, 2);
		$inidata = curl_exec($process);
		return curl_close($process);
	}

	function _getUpdateINIcached()
	{
		return JoomlapackCUBETables::ReadVar('updateini');
	}

	/**
	 * Caches the update.ini contents to database
	 * @param $inidata string The update.ini data
	 */
	function _setUpdateINIcached($inidata)
	{
		JoomlapackCUBETables::WriteVar('updateini', $inidata);
		jimport('joomla.utilities.date');
		$date = new JDate();
		JoomlapackCUBETables::SerializeVar('lastupdatecheck', $date);
	}

	/**
	 * Is the Live Update supported on this server?
	 * @return bool
	 */
	function isLiveUpdateSupported()
	{
		return $this->_hasURLfopen() || $this->_hascURL();
	}

	/**
	 * Searches for updates and returns an object containing update information
	 * @return JObject An object with members: supported, update_available,
	 * 				   current_version, current_date, latest_version, latest_date,
	 * 				   package_url
	 */
	function &getUpdates($force = false)
	{
		jimport('joomla.utilities.date');
		$ret = new JObject();
		if(!$this->isLiveUpdateSupported())
		{
			$ret->supported = false;
			$ret->update_available = false;
			return $ret;
		}
		else
		{
			$ret->supported = true;
			$update = $this->_getLatestVersion($force);
			
			// FIX 2.3: Fail gracefully if the update data couldn't be retrieved
			if(!is_object($update) || ($update === false))
			{
				$ret->supported = false;
				$ret->update_available = false;
				return $ret;				
			}
			
			// Check if we need to upgrade, by release date
			jimport('joomla.utilities.date');
			jpimport('helpers.utils', true);
			JoomlapackHelperUtils::getJoomlaPackVersion();
			$curdate = new JDate(_JP_DATE);
			$curdate = $curdate->toUnix(false);
			if(is_object($update->reldate))
			{
				$reldate = $update->reldate->toUnix(false);
				$ret->latest_date = $update->reldate->toFormat('%Y-%m-%d');
			}
			else
			{
				$ret->latest_date = @date('Y-m-d',$update->reldate);
			}
			$ret->update_available = ($reldate > $curdate);
			$ret->current_version = _JP_VERSION;
			$ret->current_date = _JP_DATE;
			$ret->latest_version = $update->version;
			$ret->status = $update->status;
				
			$ret->package_url = JPSPECIALEDITION ? $update->special : $update->free;

			return $ret;
		}
	}
}