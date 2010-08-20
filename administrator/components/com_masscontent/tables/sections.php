<?php
/*
* MassContent for Joomla 1.5.X
* @version 1.5
* @Date 04.10.2009
* @copyright (C) 2007-2009 Johann Eriksen
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* Official website: http://www.baticore.com
*/

defined('_JEXEC') or die('Restricted access');

class TableSections extends JTable
{

    var $id=null;
	var $alias=null;            
	var $title=null;    
    var $published=null;
    var $access=null;
    var $scope=null;
    var $ordering=null;
	
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	function TableSections(& $db) {
		parent::__construct( '#__sections', 'id', $db );
	}
}
?>
