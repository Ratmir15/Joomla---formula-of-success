<?php
/**
 * mtwMigrator
 *
 * @author      Matias Aguirre
 * @email       maguirre@matware.com.ar
 * @url         http://www.matware.com.ar/
 * @license             GNU/GPL
 */

// no direct access
defined('_JEXEC') or die('Restricted access');


// Require the base controller
require_once( JPATH_COMPONENT.DS.'controller.php' );

// Require specific controller if requested
$controller = JRequest::getVar('controller', 'cpanel');

//echo $controller;

if($controller) {
    $path = JPATH_COMPONENT.DS.'controllers'.DS.$controller.'.php';
	//echo $path;
    if (file_exists($path)) {
        require_once $path;
    } else {
//		echo "dsdsdsdsdds";
        $controller = 'Cpanel';
    }
}

//echo $controller;
//echo JRequest::getVar('controller');
//echo JRequest::getVar('task');
//echo "dsds";
//print_r($controller->getView('cpanel'));
//echo $classname;
//print_r($controller);


// Create the controller
$classname	= 'mtwMigratorController'.$controller;
//echo $classname;
$controller = new $classname( );
//$controller->registerDefaultTask('cpanel');

// Perform the Request task
$controller->execute( JRequest::getVar('task'));

// Redirect if set by the controller
$controller->redirect();

?>
