<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.model');

/**
 * The Backup Administrator page model
 *
 */
class JoomlapackModelBuadmin extends JModel
{
	/** @var string Stored random password */
	var $password;

	/** @var string The URI which will be displayed in the backend GUI to start restoration */
	var $linktarget;

	/**
	 * Returns the stored 14 character long random password
	 *
	 * @return string
	 */
	function getRandomPassword()
	{
		if(!$this->password)
		{
			if(!is_null( JRequest::getVar('password') ))
			{
				$this->password = JRequest::getVar('password');
			}
			else
			{
				$this->password = $this->_makeRandomPassword();
				JRequest::setVar('password', $this->password);
			}
		}

		return $this->password;
	}

	/**
	 * Generates a new random password
	 *
	 * @return string
	 */
	function _makeRandomPassword() {
		$chars = "abcdefghijkmnopqrstuvwxyz023456789!@#$%&*";
		srand((double)microtime()*1000000);
		$i = 0;
		$pass = '' ;

		while ($i <= 14) {
			$num = rand() % 40;
			$tmp = substr($chars, $num, 1);
			$pass = $pass . $tmp;
			$i++;
		}

		return $pass;
	}

}