<?php
/*
 * ccNewsletter Default Controller
 * @author Chill Creations <extensions@chillcreations.com>
 * @link http://www.chillcreations.com
 * @license GNU/GPL
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once (JPATH_SITE . '/components/com_content/helpers/route.php');

class modccnewsletterHelper
{
	// get the text to dispaly on the top of the module from the params
	function isUserLogin()
	{
		$user =& JFactory::getUser();
		
		return $user->id?true:false;
	}
	
	function isUserSubscribed()
	{
		$user	=&	JFactory::getUser();
		$db		=&	JFactory::getDBO();
		
		$query = "SELECT count(*) FROM #__ccnewsletter_subscribers WHERE email = '". $user->email . "' and enabled=1";
		$db->setQuery( $query );
		$total = $db->loadResult();
		
		return $total?true:false;
	}

	function getUsername()
	{
		$user =& JFactory::getUser();
		
		return $user->name;
	}
	
	function getUseremail()
	{
		$user =& JFactory::getUser();
		
		return $user->email;
	}
}
