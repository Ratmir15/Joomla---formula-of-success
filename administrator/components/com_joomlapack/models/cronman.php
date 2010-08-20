<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 2.3
 *
 * CRON Script Manager Model
 */

// Protect from direct access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.model');

class JoomlapackModelCronman extends JModel
{
	/** @var int CRON Script ID */
	var $_id;

	/** @var stdClass CRON script configuration object */
	var $_cron;

	/** @var JPagination The pagination object */
	var $_pagination;

	/** @var int Total CRON definition ID's */
	var $_total;

	/**
	 * @var string Where the CRON scripts are stored. Maybe in the future I can make this a
	 * user defined parameter?
	 */
	var $scriptdirectory;

    /**
     * Public contructor
     * @return JoomlapackModelCronman
     */
    function __construct($config = array())
    {
    	// Call JModel constructor
        parent::__construct($config);
		// Assign default script storage directory
		$this->scriptdirectory = JPATH_COMPONENT_ADMINISTRATOR.DS.'assets'.DS.'scripts';
		// If there is a 'scriptdirectory' element in the $config array, override the default
		if(array_key_exists('scriptdirectory', $config))
		{
			$this->scriptdirectory = $config['scriptdirectory'];
		}
    }

	/**
	 * Sets a CRON ID and resets internal data
	 *
	 * @param int $id Profile ID
	 */
	function setId($id=0)
	{
		$this->_id = $id;
		$this->_cron = null;
	}

	/**
	 * Returns the currently set CRON ID
	 * @return int
	 */
	function getId()
	{
		return $this->_id;
	}

	/**
	 * Returns a list of the ID's used by CRON files (the numeric part of, e.g. cron1234.php)
	 * @return array Array of ID's, or empty array if no cron configuration file is found
	 */
	function getCronIDs()
	{
		$ret = array();
		// Find CRON configuration scripts, like cron1.php, cron2.php, cron1520.php, etc.
		jimport('joomla.filesystem.folder');
		$files = JFolder::files( $this->scriptdirectory, '^cron\d+\.php$' );
		if( count($files) <= 0 )
		{
			return $ret;
		}
		// Loop all files
		foreach($files as $file)
		{
			if(substr($file,0,4) != 'cron') continue;
			$file = rtrim($file,'.php'); // Remove extension
			$file = ltrim($file,'cron'); // Remove prefix
			if(empty($file)) continue;
			$ret[] = (int)$file; // Convert to integer
		}
		return $ret;
	}

	/**
	 * Loads a CRON configuration file, isolates the $config array and parses it
	 * @return object|null An object with configuration data, or null if parsing failed
	 * @param int $id The CRON configuration id, e.g. 1 for cron1.php.
	 */
	function getConfiguration($id)
	{
		$filename = $this->scriptdirectory.DS."cron$id.php";

		jimport('joomla.filesystem.file');
		if(!file_exists($filename))
		{
			return null;
		}
		else
		{
			$ret = new stdClass;
			// Load in an array
			$lines = @file($filename);
			if($lines === false) return null; // In case it's unreadable
			$flag = false; // When we find the start of the array we will start processing
			$cache = ''; // The cached PHP
			foreach($lines as $line)
			{
				$line = rtrim(trim($line),"\n");
				// Search for the start of the array if we haven't found it yet
				if( ($flag == false) && (strpos($line, '$config = array(') !== false) ) $flag = true;
				// If we are processing the array...
				if($flag)
				{
					// ... but if eval() is not available, try the hard way around...
					if( strpos($line, '=>') !== false ) // We must be inside an array item
					{
						list($key, $value) = explode('=>', $line);
						$key = trim($key); // Remove whitespace from key
						$key = trim($key, substr($key,0,1)); // Remove surrounding quotes from key
						$value = rtrim(trim($value), ','); // Remove whitespace and trailing comma from the value
						// If there are no quotes surrounding the value, try to parse as boolean, else it's a number (raw data)
						if( (substr($value,0,1) == '"') || (substr($value,0,1) == "'") )
						{
							// Remove quotes
							$value = trim($value, substr($value,0,1));
						}
						else
						{
							// If there are no quotes, check if it's a boolean, otherwise leave it alone
							if($value == 'false')
							{
								$value = false;
							}
							elseif($value == 'true')
							{
								$value = true;
							}
						}
						// Add the key / value pair to the object
						$ret->$key = $value;
					}
				}

				// Search for the end of the array if we are still processing the array elements
				if( $flag && ( substr($line,-2) == ');' ) ) $flag = false;
			}

			$ret->id = $id;
		}
		return $ret;
	}

	/**
	 * Returns a list of CRON script definitions objects.
	 * @param bool $overrideLimits If true, overrides view limits and returns everything
	 * @return array
	 */
	function &getCronDefinitions($overrideLimits = false)
	{
		// Get all CRON script definitions
		$ret = array();
		$ids = $this->getCronIDs();
		if(!is_null($ids))
		{
			foreach($ids as $id)
			{
				$object = $this->getConfiguration($id);
				if( count(get_object_vars($object)) > 0 )
				{
					// A configuration was read, add it
					$ret[] = $object;
				}
			}
		}

		// If limits override is not turned on, process limits
		if( !$overrideLimits )
		{
			// Get limits
			$limitstart = $this->getState('limitstart');
			$limit = $this->getState('limit');

			if($limitstart > count($ret))
			{
				// Return empty array if asked for limitstart beyond end of table...
				$ret = array();
			}
			else
			{
				// ...otherwise, slice the array
				if( !is_null($limitstart) && !is_null($limit) )
					$ret = array_slice($ret, $limitstart, $limit);
			}
		}
		return $ret;
	}

	/**
	 * Generates and returns the contents of a CRON helper PHP file, based on the
	 * information of $this->_cron
	 * @return
	 */
	function _makeCRONScript()
	{
		// Creates a CRON script based on the information in $this->_cron
		$data = "<?php\n\$config = array(";
		foreach( get_object_vars($this->_cron) as $key => $value )
		{
			$data .= "\n\t'$key' => ";
			if(is_bool($value))
			{
				// Boolean values get converted to textual representation
				$value = ($value === true) ? 'true' : 'false';
			}
			else
			{
				// String values get their single quotes and special characters escaped
				$value = addcslashes($value, "'");
			}
			$data .= "'$value',";
		}

		$data = substr($data, 0, - 1); // Remove the last comma

		$data .= "\n);\nrequire_once('croninclude.php');";

		return $data;
	}

	/**
	 * Ensures that the user passed on a valid ID.
	 *
	 * @return bool True if the ID exists
	 */
	function checkID()
	{
		$ids = $this->getCronIDs();
		return in_array($this->_id, $ids);
	}

	/**
	 * Fetches the next available ID
	 * @return int An unused CRON helper script ID
	 */
	function getNextId()
	{
		$ids = $this->getCronIDs();
		if(count($ids) == 0) return 1; // If there are no scripts, use #1
		asort($ids);
		return array_pop($ids)+1;
	}

	/**
	 * Creates or overwrites a CRON helper script
	 * @return bool True on success
	 * @param array $data The POST'ed configuration data
	 */
	function save($data)
	{
		// If we have no ID we can't continue!
		if(!isset($data['id']))
		{
			return false;
		}

		// Get the CRON script's ID
		if($data['id'] == 0)
		{
			$id = $this->getNextId();
		}
		else
		{
			$id = $data['id'];
		}
		unset($data['id']);
		$this->setId($id);

		// Push the configuration array's elements to the _cron object
		if(count($data) == 0)
		{
			// If there are no remaining configuration keys, we can't proceed!
			return false;
		}
		else
		{
			$this->_cron = new stdClass;
			foreach($data as $key => $value)
			{
				$this->_cron->$key = $value;
			}
		}

		// Make the script's contents
		$file_data = $this->_makeCRONScript();

		// Try to write it
		$filename = $this->scriptdirectory.DS."cron$id.php";
		$status = @file_put_contents($filename, $file_data);
		if($status === false)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Creates a copy of a CRON helper script
	 * @return bool
	 * @param int $id The CRON helper script ID to copy
	 */
	function copy($id)
	{
		$newid = $this->getNextId();
		$source = $this->scriptdirectory.DS."cron$id.php";
		$destination = $this->scriptdirectory.DS."cron$newid.php";

		// Make sure the source exists
		if(!@file_exists($source)) return false;

		// Try to copy the file
		return @copy($source, $destination);
	}

	function remove($id)
	{
		$source = $this->scriptdirectory.DS."cron$id.php";
		// Make sure the source exists
		if(!@file_exists($source)) return false;
		// Try to remove the file
		return @unlink($source);
 	}

	/**
	 * Returns a list of post processing options, for JHTML use
	 * @return array
	 */
	function getPostOpList()
	{
		$options = array();
		$options[] = JHTML::_('select.option', 'none', JText::_('CRON_OPT_POSTOP_NONE'));
		$options[] = JHTML::_('select.option', 'upload', JText::_('CRON_OPT_POSTOP_UPLOAD'));
		$options[] = JHTML::_('select.option', 'email', JText::_('CRON_OPT_POSTOP_EMAIL'));
		return $options;
	}

	/**
	 * Get a pagination object
	 *
	 * @access public
	 * @return JPagination
	 *
	 */
	function getPagination()
	{
		if( empty($this->_pagination) )
		{
			// Import the pagination library
			jimport('joomla.html.pagination');

			// Prepare pagination values
			$total = $this->getTotal();
			$limitstart = $this->getState('limitstart');
			$limit = $this->getState('limit');

			// Create the pagination object
			$this->_pagination = new JPagination($total, $limitstart, $limit);
		}

		return $this->_pagination;
	}

	/**
	 * Get number of CRON configuration items
	 *
	 * @access public
	 * @return integer
	 */
	function getTotal()
	{
		if( empty($this->_total) )
		{
			$ids = $this->getCronIDs();
			$this->_total = count($ids);
		}

		return $this->_total;
	}

}