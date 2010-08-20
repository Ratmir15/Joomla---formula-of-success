<?php
/*
* MassContent for Joomla 1.5.X
* @version 1.5
* @Date 04.10.2009
* @copyright (C) 2007-2009 Johann Eriksen
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* Official website: http://www.baticore.com
*/

defined('_JEXEC') or die();

class MassContentControllerContent extends MassContentController
{

	function __construct()
	{
		parent::__construct();
		
		 //Register Extra tasks
		// $this->registerTask( 'create'  , 	'newMassContent' );
	}

	/**
	 * display the form
	 * @return void
	 */
	function display()
	{
		JRequest::setVar( 'view', 'content' );
		parent::display();
	}

	/**
	 * save categories
	 */
	function save()
	{
		$model = $this->getModel('content');
		if(!$model->saveMassContent()) {
			$msg = JText::_("ERROR_CONTENT" );
		} else {
			$msg = JText::_( "SUCCESS_CONTENT" );
		}

	$this->setRedirect( "index2.php?option=com_masscontent&controller=content",$msg );
	}

}
?>
