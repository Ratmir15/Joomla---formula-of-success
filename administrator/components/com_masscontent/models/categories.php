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

jimport( 'joomla.application.component.model' );

class MassContentModelCategories extends JModel
{

/**
* Link the content to the menu
* @param id The id of the content to insert
* @param title: The  title of the menu element
* @param menuselect: The menu where to create the link
* @param contentType:  to know the kind of content (static content or not)
*/ 
 function menuLink( $id, $title,$menuselect,$contentType,$parent  ) {
 global $mainframe;
	$database = & JFactory::getDBO();


	$menu = strval( $menuselect );
	$link = strval( $title );

	$link	= stripslashes( JFilterOutput::ampReplace($link) );

    //find what kind of link needs to be created in $row->link
    switch ($contentType){
        case "content_section":
            $taskLink = "section";
            break;
        case "content_blog_section":
            $taskLink = "section&layout=blog";
            break;            ;    
        case "content_category":
            $taskLink = "category";
            break;
        case "content_blog_category":
            $taskLink = "category&layout=blog";
            break;                         
        default:
        $taskLink = "article";
    }


	$row  =& JTable::getInstance('menu');
	$row->menutype 		= $menu;
	$row->name 			= $link;
    $row->alias         = JFilterOutput::stringURLSafe($link); 
	$row->parent 		= ($parent==-1)?0:$parent;
	$row->type 			= 'component';
	$row->link			= 'index.php?option=com_content&view='.$taskLink.'&id='. $id;	
	$row->published		= 1;

	//$row->componentid	= $id;
	$row->componentid	= 20;
    $row->ordering =    9999;
	$row->params		= "display_num=10
show_headings=1
show_date=0
date_format=
filter=1
filter_type=title
orderby_sec=
show_pagination=1
show_pagination_limit=1
show_feed_link=1
show_noauth=
show_title=
link_titles=
show_intro=
show_section=
link_section=
show_category=
link_category=
show_author=
show_create_date=
show_modify_date=
show_item_navigation=
show_readmore=
show_vote=
show_icons=
show_pdf_icon=
show_print_icon=
show_email_icon=
show_hits=
feed_summary=
page_title=
show_page_title=1
pageclass_sfx=
menu_image=-1
secure=0

"; 

    
	if (!$row->check()) {
		echo "<script> alert('".$row->getError()."'); window.history.go(-1); </script>\n";
		exit();
	}
	if (!$row->store()) {
		echo "<script> alert('".$row->getError()."'); window.history.go(-1); </script>\n";
		exit();
	}
	
	$row->reorder( "menutype = " . $database->Quote( $row->menutype ) . " AND parent = " . (int) $row->parent );

	// clean any existing cache files
	//mosCache::cleanCache( 'com_content' );	
}


function &getData(){
	global  $my, $mainframe;
	$database = & JFactory::getDBO();
	
    $uid=0;
    $scope 		= "content";
    $option 	= "com_masscontent";
    
	$row =& $this->getTable();
	// load the row from the db table
	$row->load( (int)$uid );
	
    $row->scope 		= $scope;
    $row->published 	= 1;
    $menus 				= array();
 
    $javascript2 = "onchange=\"changeDynaList( 'menuselect3', menulist, document.adminForm.menuselect.options[document.adminForm.menuselect.selectedIndex].value, 0, 0);\"";
     
	
	// build the html select list for section types
	$types[] = JHTML::_('select.option', '', 'Select Type' );
    $types[] = JHTML::_('select.option', 'content_category', 'Category List Layout' );
    $types[] = JHTML::_('select.option', 'content_blog_category', 'Category Blog Layout' );
	$lists['link_type'] 		= JHTMLSelect::genericList( $types, 'link_type', 'class="inputbox" size="1"', 'value', 'text' );

    // build the html select list for sections
	 
		$query = "SELECT s.id AS value, s.title AS text"
		. "\n FROM #__sections AS s"
		. "\n ORDER BY s.ordering"
		;
		$database->setQuery( $query );
		$sections = $database->loadObjectList();
		$lists['section'] = JHTMLSelect::genericList(  $sections, 'section', 'class="inputbox" size="1"', 'value', 'text' );
 
		$menuTypes 	= $this->getMenuTypes(); 
		foreach ( $menuTypes as $menuType ) {
			$menu[] = JHTML::_('select.option',  $menuType, $menuType );
		}
 
 
	// build the html select list for the group access
	$lists['access'] 			= JHTML::_('list.accesslevel', $row );
	// build the html radio buttons for published
	$lists['published'] 		= JHTML::_('select.booleanlist', 'published', 'class="inputbox"', $row->published );
	
		$stop_notice = array();
$lists['menuselect3'] = JHTML::_('select.genericlist',   $stop_notice, 'menuselect3', 'class="inputbox" size="10"', 'value', 'text', null );
 
 	 $lists['menulist']=  $this->createSubMenu();
	  
	 $lists['menuselect'] = JHTML::_('select.genericlist',   $menu, 'menuselect', 'class="inputbox" size="10" '.  $javascript2, 'value', 'text', null );
	return $lists;
	 
}
	/**
	 * Get a list of the menutypes
	 * @return array An array of menu type names
	 */
	function getMenuTypes()
	{
		$db = &JFactory::getDBO();
		$query = 'SELECT menutype' .
				' FROM #__menu_types';
		$db->setQuery( $query );
		return $db->loadResultArray();
	}

function saveMassCategories( $option=null ) {
	global $mainframe;
	$database = & JFactory::getDBO();
	$params = JComponentHelper::getParams('com_masscontent');
	$menuid		= intval( JRequest::getVar( 'menuid', 0 ,'POST') );
    $type 	= strval( JRequest::getVar(   'link_type', '' ,'POST') );
	//$menu 		= stripslashes( strval( JRequest::getVar(   'menu', 'mainmenu' ,'POST') ) );
    $menu 	= strval( JRequest::getVar(   'menuselect', '','POST' ) );
    $addMenu 	= strval( JRequest::getVar(   'addMenu', '' ,'POST') );
	$parent 	= strval( JRequest::getVar(   'menuselect3', '','POST' ) );
    
	//$row = new mosDBMassCategories( $database );
	$row =& $this->getTable();
	
	if (!$row->bind( $_POST )) {
		echo "<script> alert('".$row->getError()."'); window.history.go(-1); </script>\n";
		return false;
	}
 //browse each title and insert it if it is not empty
    for ($i=0;$i<$params->get('nbMassCategories');$i++){
        if ($row->title[$i]!='')
        {    
            //$row2  = new mosDBMassCategories( $database );
			$row2 =& $this->getTable();
            //$row2 = clone $row; //for php5
            if (!$row2->bind( $_POST )) {
                echo "<script> alert('".$row->getError()."'); window.history.go(-1); </script>\n";
                return false;
            }   
            $row2->title=$row->title[$i];
            $row2->alias=JFilterOutput::stringURLSafe($row->alias[$i]);  
            $row2->name=$row->name[$i];
            
// new item: order last in appropriate group
            $where = "section = " . $database->Quote($row->section);
            $row2->ordering = $row2->getNextOrder( $where );
            if (!$row2->store()) {
                echo "<script> alert('".$row2->getError()."'); </script>";                
            return false;
            }
       
            $row2->checkin();
           
            if ($addMenu) {  
                    $this->menuLink( $row2->id, $row2->title,$menu,$type,$parent );
            } 
            
        }
    }	
     return true; 
}

function createSubMenu ()
{
	 // build the html select list for menu selection
 
	$menulist = array();
	$database = & JFactory::getDBO();
	$menuTypes 	= $this->getMenuTypes(); 
	foreach ( $menuTypes as $menuType ) {
		//$menu = JHTML::_('select.option',  $menuType, $menuType );
		///////////////////////////////////////////////////
		//Create tje tree of menus
		//http://dev.joomla.org/component/option,com_jd-wiki/Itemid,/id,references:joomla.framework:html:jhtmlmenu-treerecurse/
		$query = 'SELECT id, parent, name, menutype' .
			' FROM #__menu' .
			' WHERE menutype = "'.$menuType .'"'.
			' ORDER BY menutype, parent, ordering'
			;

		$database->setQuery($query);
		$menuItems4 = $database->loadObjectList();

		$children = array();
		
		if ($menuItems4) {
			// first pass - collect children
			foreach ($menuItems4 as $v) {	// iterate through the menu items
				$pt 	= $v->parent;		// we use parent as our array index
		
				// if an array entry for the parent doesn't exist, we create a new array
				$list 	= @$children[$pt] ? $children[$pt] : array();
		 
				// we push our item onto the array (either the existing one for the specified parent or the new one
				array_push( $list, $v );
				// We put out updated list into the array
				$children[$pt] = $list;
			}
	} 
	// second pass - get an indent list of the items
	$list = JHTML::_('menu.treerecurse', 0, '-', array(), $children, 9999, 0, 0 );
	$menulist[] = $list ;
 
 
	}
 
	return $menulist;
///////////////////////////////////////////////////
}

}
