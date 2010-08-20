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

class TableContent extends JTable
{
    var $id=null;
	var $alias=null;            
	var $title=null;            
	var $created_by = null;   
	var $created = null;   		
    var $catid=null;
    var $sectionid=null;
    var $state=null;
    var $publish_up=null;
    var $publish_down=null;
    var $introtext=null;
    var $fulltext=null;
    var $access=null;
    var $metakey=null;
    var $metadesc=null;
    var $created_by_alias=null;
    var $attribs=null;
    var $ordering=null;
 


	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	function TableContent(& $db) {
		parent::__construct( '#__content', 'id', $db );
	}
}
?>
