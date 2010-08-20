<?php
/*
* MassContent for Joomla 1.5.X
* @version 1.4
* @Date 05.07.2009
* @copyright (C) 2007-2009 Johann Eriksen
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* Official website: http://www.baticore.com
*/

defined('_JEXEC') or die('Restricted access');

class TableCategories extends JTable
{
    var $id=null;
    var $parent_id=null;
	var $alias=null;            
	var $title=null;    
	var $section=null;        
    var $published=null;
    var $access=null;
    var $ordering=null;
 

	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	function TableCategories(& $db) {
		parent::__construct( '#__categories', 'id', $db );
	}
}
?>
