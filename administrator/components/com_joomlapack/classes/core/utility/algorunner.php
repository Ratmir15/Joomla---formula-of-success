<?php
/**
 * @package JoomlaPack
 * @version $id$
 * @license GNU General Public License, version 2 or later
 * @author JoomlaPack Developers
 * @copyright Copyright 2006-2009 JoomlaPack Developers
 * @since 1.3
 */
defined('_JEXEC') or die('Restricted access');

/**
 * Algorithm runner
 *
 * Provides tick() functionality with different algorithms based on domain names
 *
 */
class JoomlapackCUBEAlgorunner extends JObject
{
	/**
	 * Current domain reported by part
	 *
	 * @var string
	 */
	var $currentDomain;

	/**
	 * Current step reported by part
	 *
	 * @var string
	 */
	var $currentStep;

	/**
	 * Current substep reported by part
	 *
	 * @var string
	 */
	var $currentSubstep;

	/**
	 * Allowed parameters for algorithm
	 *
	 * @var array
	 */
	var $_allowedAlgorithms = array('multi', 'smart');

	/** @var int Maximum execution time allowance per step, in seconds */
	var $_maxExecTime = 14;

	/** @vstartTimestartTimear float Timestamp when the current step began processing, in _decimal_ seconds */
	var $_startTime = 0;

	/**
	 * Provides a Singleton implementation
	 *
	 * @return	JoomlapackCUBEAlgorunner An object, or false on failure
	 */
	function &getInstance()
	{
		static $instance;

		if(!is_object($instance))
		{
			$instance = new JoomlapackCUBEAlgorunner();
		}

		return $instance;
	}

	/**
	 * Public constructor
	 *
	 */
	function __construct()
	{
		parent::__construct();

		// Import Smart algorithm magic numbers
		if(!class_exists('JoomlapackModelRegistry'))
		{
			jpimport('models.registry', true);
		}
		$configuration =& JoomlapackModelRegistry::getInstance();
		if(!defined('mnMaxExecTimeAllowed'))	define('mnMaxExecTimeAllowed',	$configuration->get('mnMaxExecTimeAllowed'));
		if(!defined('mnMinimumExectime'))		define('mnMinimumExectime',		$configuration->get('mnMinimumExectime'));
		if(!defined('mnExectimeBiasPercent'))	define('mnExectimeBiasPercent',	$configuration->get('mnExectimeBiasPercent')/100);
		if(!defined('mnMaxOpsPerStep'))			define('mnMaxOpsPerStep',		$configuration->get('mnMaxOpsPerStep'));
		unset($configuration);
	}

	/**
	 * Selects the algorithm to use based on the domain name
	 *
	 * @param string $domain The domain to return algorithm for
	 * @return string The algorithm to use
	 */
	function selectAlgorithm( $domain ){
		if(!class_exists('JoomlapackModelregistry'))
		{
			jpimport('models.registry', true);
		}
		$registry =& JoomlapackModelRegistry::getInstance();

		switch( $domain )
		{
			case "installer":
				switch ($registry->get('BackupType')) {
					case 'full':
						return 'smart';
						break;
							
					default:
						return '(null)';
						break;
				}
				break;

					case "PackDB":
						return $registry->get('dbAlgorithm');
						break;
							
					case "Packing":
						switch ($registry->get('BackupType')) {
							case 'full':
								return $registry->get('packAlgorithm');
								break;
									
							default:
								return '(null)';
								break;
						}
						break;
							
							default:
								return "(null)";
								break;

		}
	}

	/**
	 * Initialises the time limits code, i.e. the maximum allowed execution time and the
	 * start time.
	 */
	function _initTimeLimits()
	{
		// Get the starting time
		$this->_startTime = $this->_microtime_float();
		// Get the maximum execution time
		if(@function_exists('ini_get'))
		{
			$this->_maxExecTime = @ini_get("maximum_execution_time");
		}
		else
		{
			// If ini_get is not available, use a rough default
			$this->_maxExecTime = 14;
		}
		if ( ($this->_maxExecTime == "") || ($this->_maxExecTime == 0) ) {
			// If we have no time limit, set a hard limit of about 10 seconds
			// (safe for Apache and IIS timeouts, verbose enough for users)
			$this->_maxExecTime = 14;
		}

		$maxRunTime = ($this->_maxExecTime - 1) * mnExectimeBiasPercent;
		$this->_maxExecTime = max(array(mnMaxExecTimeAllowed, $maxRunTime));
	}

	/**
	 * Returns the avaiable time left before having to forcibly break this step. It calculates
	 * this by subtracting running time from the maximum execution time allowance.
	 *
	 * @return float
	 */
	function getTimeLeft()
	{
		return $this->_maxExecTime - $this->getRunningTime();
	}

	/**
	 * Returns the time elapsed since the start of this step in decimal seconds
	 * @return float Time elapsed, in decimal seconds
	 */
	function getRunningTime()
	{
		return $this->_microtime_float() - $this->_startTime;
	}

	/**
	 * Runs the user-selected algorithm for stepping a CUBE part
	 *
	 * @param string $algorithm multi|smart The selected algorithm
	 * @param JoomlapackCUBEParts $object The CUBE part to step
	 * @return integer 0 if more work is required, 1 if we finished, 2 on error
	 */
	function runAlgorithm( $algorithm, &$object ){
		if(!is_object($object) && (in_array($algorithm, $this->_allowedAlgorithms)))
		{
			$this->setError(__CLASS__.':: $object is not an object');
			return 2;
		}

		// Catch error conditions
		if($object->getError())
		{
			$this->setError($object->getError());
			return 2;
		}

		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Using $algorithm algorithm for ".get_class($object) );

		$this->_initTimeLimits();

		switch( $algorithm ){
			case "slow":
				// Multi-step algorithm - slow but most compatible
				return $this->_algoMultiStep( $object );
				break;
			case "smart":
				// SmartStep algorithm - best compromise between speed and compatibility
				return $this->_algoSmartStep( $object );
				break;
			default:
				// No algorithm (null algorithm) for "init" and "finale" domains. Always returns success.
				return 1;
		} // switch
	}

	/**
	 * Multi-step algorithm. Runs the tick() function of the $object once and returns.
	 *
	 * @param JoomlapackCUBEParts $object The CUBE part to step
	 * @return integer
	 * @see runAlgorithm()
	 */
	function _algoMultiStep( &$object )
	{
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Multiple Stepping (Slow algorithm)");

		// Catch potential errors
		if($object->getError())
		{
			$this->setError($object->getError());
			return 2;
		}

		$result =$object->tick();

		$this->currentDomain = $result['Domain'];
		$this->currentStep = $result['Step'];
		$this->currentSubstep = $result['Substep'];

		// Catch any errors

		if($object->getError())
		{
			$error = true;
			$this->setError($object->getError());
		}
		else
		{
			$error = false;
		}
		$finished = $error ? true : !($result['HasRun']);

		if (!$error) {
			JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Successful Slow algorithm on ".get_class($object));
		} else {
			JoomlapackLogger::WriteLog(_JP_LOG_ERROR, "Failed Slow algorithm on ".get_class($object));
		}

		return $error ? 2 : ( $finished ? 1 : 0 );
	}

	/**
	 * Smart step algorithm. Runs the tick() function until we have consumed 75%
	 * of the maximum_execution_time (minus 1 seconds) within this procedure. If
	 * the available time is less than 1 seconds, it defaults to multi-step.
	 * @param JoomlapackCUBEParts $object The CUBE part to step
	 * @return integer 0 if more work is to be done, 1 if we finished correctly,
	 * 2 if error eccured.
	 * @access private
	 */
	function _algoSmartStep( &$object )
	{
		JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Smart Stepping");

		if ( $this->_maxExecTime <= mnMinimumExectime ) {
			// If the available time is less than the trigger value, switch to
			// multi-step
			return $this->_algoMultiStep($object);
		} else {
			// All checks pass, this is a SmartStep-enabled case
			$runTime = 0;
			$finished = false;
			$error = false;
			$breakFlag = false; // BREAKFLAG is optionally passed by domains to force-break current operation

			$opsRemaining = max(1, mnMaxOpsPerStep); // Run at least one step, even if mnMaxOpsPerStep=0
				
			// Loop until time's up, we're done or an error occured, or BREAKFLAG is set
			while( ( $this->getTimeLeft() > 0 ) && (!$finished) && (!$error) && ($opsRemaining > 0) && (!$breakFlag) ){
				$opsRemaining--; // Decrease the number of possible available operations count
				$result = $object->tick();

				// Advance operation counter
				$cube =& JoomlapackCUBE::getInstance();
				$cube->operationCounter++;
				$currentOperationNumber = $cube->operationCounter;
				unset($cube);

				// Process return array
				$this->currentDomain = $result['Domain'];
				$this->currentStep = $result['Step'];
				$this->currentSubstep = $result['Substep'];

				// Check for BREAKFLAG
				if(isset($result['BREAKFLAG']))
				{
					$breakFlag = $result['BREAKFLAG'];
				}
				else
				{
					$breakFlag = false;
				}

				// Process errors
				$error = false;
				if($object->getError())
				{
					$error = true;
					$this->setError($object->getError());
					$result['Error'] = $this->getError();
				}

				// Check if the backup procedure should finish now
				$finished = $error ? true : !($result['HasRun']);

				// Log operation end
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG,'----- Finished operation '.$currentOperationNumber.' ------');
			} // while

			// Return the result
			if (!$error) {
				JoomlapackLogger::WriteLog(_JP_LOG_DEBUG, "Successful Smart algorithm on ".get_class($object));
			} else {
				JoomlapackLogger::WriteLog(_JP_LOG_ERROR, "Failed Smart algorithm on ".get_class($object));
			}
				
			return $error ? 2 : ( $finished ? 1 : 0 );
		}
	}

	/**
	 * Returns the current timestampt in decimal seconds
	 */
	function _microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
}