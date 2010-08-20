<?php
/*
 * ccNewsletter Default Controller
 * @author Chill Creations <extensions@chillcreations.com>
 * @link http://www.chillcreations.com
 * @license GNU/GPL
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

// Include the syndicate functions only once
require_once (dirname(__FILE__).DS.'helper.php');

global $mainframe;
$router =& $mainframe->getRouter();
/* Get Parameters details */
$parameters['style'] = $params->get('style', 'mootools');
$parameters['introduction'] = $params->get('introduction');
$parameters['name'] = $params->get('lname', 'Name');
$parameters['email'] = $params->get('lemail', 'Email');
$parameters['subscribe'] = $params->get('lsubscribe', 'Subscribe');
$parameters['unsubscribe'] = $params->get('lunsubscribe', 'Unsubscribe');
$parameters['move'] = $params->get('lmove', 'Move');
$parameters['close'] = $params->get('lclose', 'Close');
$parameters['emailwarning'] = $params->get('lclose', 'Close');
$parameters['namewarning'] = $params->get('namewarning');
$parameters['emailwarning'] = $params->get('emailwarning');
$parameters['unsubscribe_button'] = $params->get('unsubscribe_button', '0');
$parameters['article'] = $params->get('id', '0');
$parameters['showterm'] = $params->get('showterm', '0');
$parameters['terms_cond_warn'] = $params->get('terms_cond_warn', 'Check the Terms and condition!!');
$parameters['showterm_text'] = $params->get('showterm_text', 'Check the Terms and condition!!');

if(modccNewsletterHelper::isUserLogin())
{
	if(modccNewsletterHelper::isUserSubscribed())
	{
		$formname = "subscribeForm";
		$formaction = JRoute::_( 'index.php?option=com_ccnewsletter&view=unsubscribe', true, 0);
		$title = $params->get('lunsubscribe');
		$task = "removeSubscriberByEmail";
		$name = "";
		$email = modccNewsletterHelper::getUseremail() ;
		$subscribe_flag = 'u';
	}
	else
	{
		$formname = "subscribeFormModule";
		$formaction = JRoute::_( 'index.php?option=com_ccnewsletter&view=ccnewsletter', true, 0);
		$title = $params->get('lsubscribe');
		$task = "addSubscriber";
		$name = modccNewsletterHelper::getUsername();
		$email = modccNewsletterHelper::getUseremail() ;
		$subscribe_flag = 's';
	}
}
else
{
		$formname = "subscribeFormModule";
		$formaction = JRoute::_( 'index.php?option=com_ccnewsletter&view=ccnewsletter', true, 0);
		$title = $params->get('lsubscribe');
		$task = "addSubscriber";
		$name = "";
		$email = "" ;
		$subscribe_flag = 's';
}

require(JModuleHelper::getLayoutPath('mod_ccnewsletter'));