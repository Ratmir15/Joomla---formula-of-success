<?php
/**
 * mtwMigrator
 *
 * @author      Matias Aguirre
 * @email       maguirre@matware.com.ar
 * @url         http://www.matware.com.ar/
 * @license             GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * mtwMigratorControllerMigrate
 *
 * @package    mtwMigrator
 * @subpackage Components
 */
class mtwMigratorControllerMigrate extends mtwMigratorController
{

	/**
	 * constructor (registers additional tasks to methods)
	 * @return void
	 */
	function __construct()
	{
		parent::__construct();
		parent::registerDefaultTask('migrate');

		// Register Extra tasks
		//$this->registerTask( 'add'  , 	'edit' );
	}


    function migrate(){

		$model =& $this->getModel('migrate');

		//print_r($model);

		if ( $model->_errors[0] ) {
            $msg = JText::_( $model->_errors[0] );
        
            $link = 'index.php?option=com_mtwmigrator';
            $this->setRedirect($link, $msg);
		}

		JRequest::setVar( 'view', 'migrate' );
		parent::display();
    }




    function display() {
        
        JRequest::setVar( 'view', 'migrate' );
        
        parent::display();
    }


}
?>
