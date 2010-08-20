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

class MassContentControllerSections extends MassContentController
{

	function __construct()
	{
		parent::__construct();

		 //Register Extra tasks
		// $this->registerTask( 'create'  , 	'newMassSections' );
	}

	/**
	 * display the form
	 * @return void
	 */
	function display()
	{
		JRequest::setVar( 'view', 'sections' );
		parent::display();
	}

	/**
	 * save sections
	 */
	function save()
	{
		$model = $this->getModel('sections');
		if(!$model->saveMassSections()) {
			$msg = JText::_( "ERROR_SECTIONS" );
		} else {
			$msg = JText::_( "SUCCESS_SECTIONS");
		}

	$this->setRedirect( "index2.php?option=com_masscontent&controller=sections",$msg );
	}

}
?>
