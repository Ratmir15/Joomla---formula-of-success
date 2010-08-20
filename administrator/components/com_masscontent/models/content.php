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
jimport('joomla.application.component.controller');
require_once (JPATH_ADMINISTRATOR.DS.'components'.DS.'com_frontpage'.DS.'tables'.DS.'frontpage.php');
class MassContentModelContent extends JModel
{
    /** @var object JTable object */
    var $_table = null;
    
/**
     * Returns the internal table object
     * @return JTable
     */
/*    function &getTable()
    {
        if ($this->_table == null) {
            $this->_table = & JTable::getInstance('menuTypes');
            if ($id = JRequest::getVar('id', false, '', 'int')) {
                $this->_table->load($id);
            }
        }
        return $this->_table;
    }    */
    
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

    $link    = stripslashes( JFilterOutput::ampReplace($link) );

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
    $row->menutype         = $menu;
    $row->name             = $link;
    $row->alias             = str_replace(' ','-',$link);
    $row->parent         = ($parent==-1)?0:$parent;
    $row->type             = 'component';
    $row->link            = 'index.php?option=com_content&view='.$taskLink.'&id='. $id;    
    $row->published        = 1;
    $row->params        = "show_noauth=
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

    //$row->componentid    = $id;
    $row->componentid    = 20;
    $row->ordering        = 9999; 

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
    /**
     * Get a list of the menu records associated with the type
     * @param string The menu type
     * @return array An array of records as objects
     */
    function getMenuItems($menutype)
    {
    
        $table = & $this->getTable();
        if ($table->menutype == '') {
            $table->menutype = JRequest::getString('menutype');
        }

        $db = &$this->getDBO();
        $query = 'SELECT a.id, a.name' .
                ' FROM #__menu AS a' .
                 ' WHERE a.menutype = "' .$menutype .'"' .
                ' ORDER BY a.name';
        $db->setQuery( $query );
 
        return $db->loadObjectList();
        //return $db->loadResultArray();
    }

function &getData(){
    $database = & JFactory::getDBO();
    global  $mainframe,  $my;
    $contentSection=0;
    $lists=null;
    $sectioncategories=0;
    if (!isset($id))
    {
        $id=0;
    }
    if (!isset($store))
    {
        $store='';
    }
    $row =& $this->getTable();
    $row->load( $id );

    $javascript = "onchange=\"changeDynaList( 'catid', sectioncategories, document.adminForm.sectionid.options[document.adminForm.sectionid.selectedIndex].value, 0, 0);\"";
    $javascript2 = "onchange=\"changeDynaList( 'menuselect3', menulist, document.adminForm.menuselect.options[document.adminForm.menuselect.selectedIndex].value, 0, 0);\"";
     
    //section
    $query = "SELECT s.id, s.title"
    . "\n FROM #__sections AS s"
    . "\n ORDER BY s.ordering";
    $database->setQuery( $query );

        //$sections[] = JHTML::_('select.option', '-1', 'Static Content', 'id', 'title' );
        $sections[] = JHTML::_('select.option', $store, JText::_(ucfirst($store)), 'id', 'title' );
        $sections = array_merge( $sections, $database->loadObjectList() );
        $lists['sectionid'] = JHTMLSelect::genericList( $sections, 'sectionid', 'class="inputbox" size="1" '. $javascript, 'id', 'title' );

    $contentSection = '';
    foreach($sections as $section) {
        if(isset($section->id)){
            $section_list[] = $section->id;
            if(isset($sectionid)){
                if ( $section->id == $sectionid ) {
                    $contentSection = $section->title;
                }
            } 
        }    
    }
       $sectioncategories             = array();
    $sectioncategories[-1]         = array();
    $sectioncategories[-1][]     = JHTML::_('select.option', '-1', 'Select Category', 'id', 'name' );
    JArrayHelper::toInteger( $section_list );
    $section_list                 = 'section=' . implode( ' OR section=', $section_list );

    $query = "SELECT id, title, section"
    . "\n FROM #__categories"
    . "\n WHERE ( $section_list )"
    . "\n ORDER BY ordering"
    ;
    $database->setQuery( $query );
    $cat_list = $database->loadObjectList();
    foreach($sections as $section) {
        if (isset($section->id))
            $sectioncategories[$section->id] = array();
        $rows2 = array();
        foreach($cat_list as $cat) {
            if (isset($section->id))
                $str_id=$section->id;
            else
                $str_id="";
            if ($cat->section == $str_id) {
                $rows2[] = $cat;
            }
        }
        foreach($rows2 as $row2) {
            $sectioncategories[$section->id][] = JHTML::_('select.option', $row2->id, $row2->title, 'id', 'name' );
        }
    }

     // get list of categories
        if (!isset($row->sectionid))
    {
        $row->sectionid=0;
    }
        if (!isset($row->catid))
    {
        $row->catid=0;
    }
      if ( !$row->catid && !$row->sectionid ) {
         $categories[]         = JHTML::_('select.option', '0', 'Select Category', 'id', 'name' );
         $lists['catid']     = JHTMLSelect::genericList( $categories, 'catid', 'class="inputbox" size="1"', 'id', 'name' );

     
      } else {
        $categoriesA = array();
        if ( $sectionid == 0 ) {
            foreach($cat_list as $cat) {
                $categoriesA[] = $cat;
            }
        } else {
            //$where = "\n WHERE section = '$sectionid'";
            foreach($cat_list as $cat) {
                if ($cat->section == $sectionid) {
                    $categoriesA[] = $cat;
                }
            }
        }
        $categories[]         = JHTML::_('select.option', '0', 'Select Category', 'id', 'name' );
 
        $categories         = array_merge( $categories, $categoriesA );
         $lists['catid']     = JHTMLSelect::genericList( $categories, 'catid', 'class="inputbox" size="1"', 'id', 'name', intval( $row->catid ) );
      }

    // build the html select list for ordering
    $query = "SELECT ordering AS value, title AS text"
    . "\n FROM #__content"
    . "\n WHERE catid = " . (int) $row->catid
    . "\n AND state >= 0"
    . "\n ORDER BY ordering"
    ;
    $uid="";
    $row->ordering=null;
    $lists['ordering'] =  JHTML::_('list.specificordering', $row, $uid, $query, 1 );     
 
 
 /*
$error_ordering= array();
$error_ordering="";
$row = array_merge( $row, $error_ordering );
*/    
    // build the html select list for menu selection
 $menu3 = array();
 $menulist = array();
    $menuTypes     = $this->getMenuTypes(); 
    foreach ( $menuTypes as $menuType ) {
            $menu[] = JHTML::_('select.option',  $menuType, $menuType );
     }
     $stop_notice = array();   
            
            $lists['menuselect3'] = JHTML::_('select.genericlist',   $stop_notice, 'menuselect3', 'class="inputbox" size="10"', 'value', 'text', null );    
     
 
        $lists['menuselect'] = JHTML::_('select.genericlist',   $menu, 'menuselect', 'class="inputbox" size="10" '.  $javascript2, 'value', 'text', null   );
 

    // build the html select list for the group access
    $lists['access']             = JHTML::_('list.accesslevel',  $row );
    // build list of users
 
     $lists['created_by']         = JHTML::_('list.users',  'created_by', 1);
     
    //load params
    jimport('joomla.application.component.helper');
     $lists['sectioncategories']= $sectioncategories;
       $lists['menulist']=  $this->createSubMenu();
     

    return $lists;
}
     

    /**
     * Get a list of the menutypes
     * @return array An array of menu type names
     */
    function getMenuTypes()
    {
        $db = &JFactory::getDBO();
        /*$query = 'SELECT menutype' .
                ' FROM #__menu_types';*/
        $query = 'SELECT a.menutype' .
                ' FROM #__menu AS a' .
                ' WHERE a.published = 1' .
                ' GROUP BY a.menutype';                
        $db->setQuery( $query );
         return $db->loadResultArray();
     
    }

function saveMassContent( $option=null ) {
global $mainframe;
    jimport('joomla.utilities.date');
    $config =& JFactory::getConfig();
    $tzoffset = $config->getValue('config.offset');     
    $database = & JFactory::getDBO();
    $nullDate    = $database->getNullDate();
    $params = JComponentHelper::getParams('com_masscontent');
    
    $menu     = strval( JRequest::getVar(   'menuselect', '','POST' ) );
    $addMenu     = strval( JRequest::getVar(   'addMenu', '','POST' ) );
    $archived     = strval( JRequest::getVar(   'state2', '','POST' ) );
    $frontpage     = strval( JRequest::getVar(   'frontpage', '','POST' ) );
    $parent     = strval( JRequest::getVar(   'menuselect3', '','POST' ) );
    $created_by_alias=strval( JRequest::getVar(   'created_by_alias', '','POST' ) );
        
    $msg="";
    
    $row =& $this->getTable();
    if (!$row->bind(JRequest::get('post'))) { 
        echo "<script> alert('".$row->getError()."'); window.history.go(-1); </script>\n";
        exit();
    }
 
    $row->metadata="";
    if ($row->robots!="")
        $row->metadata="robots=".$row->robots."\n";
    if ($row->author!="")    
        $row->metadata.="author=".$row->author;
    if ($row->metadata=="")
         $row->metadata="robots=
author=";
        
    if ($row->created && strlen(trim( $row->created )) <= 10)
        $row->created     .= ' 00:00:00';
    $date = new JDate($row->created, $tzoffset);
    $row->created = $date->toMySQL();        
        
    if ($row->publish_up && strlen(trim( $row->publish_up )) <= 10)
        $row->publish_up     .= ' 00:00:00';
    $date = new JDate($row->publish_up, $tzoffset);
    $row->publish_up = $date->toMySQL();    
        
    // Handle never unpublish date
    if (trim($row->publish_down) == JText::_('Never') || trim( $row->publish_down ) == '')
        $row->publish_down = $nullDate;
    else
    {
        if (strlen(trim( $row->publish_down )) <= 10) {
            $row->publish_down .= ' 00:00:00';
        }
        $date = new JDate($row->publish_down, $tzoffset);
        $row->publish_down = $date->toMySQL();
    }
    
    //handle archived
    if ($archived)
        $row->state=-1;
    else $row->state=1;    
 
   //browse each title and insert it if it is not empty
    for ($i=0;$i<$params->get('nbMassContent');$i++){

        if ($row->title[$i]!='')
        {    
                 
                
             $row2 =& $this->getTable();          
         
               if (!$row2->bind( JRequest::get('post'))) {            
            echo "<script> alert('".$row->getError()."'); window.history.go(-1); </script>\n";
          return false;
            }

            $row2->created=$row->created;
            $row2->publish_up=$row->publish_up;            
            $row2->publish_down=$row->publish_down;
            $row2->title=$row->title[$i];
     //       $row2->alias=str_replace(' ','-',$row->alias[$i]);            
            $row2->alias=JFilterOutput::stringURLSafe($row->alias[$i]); 
            $row2->introtext = JRequest::getVar( "introtext_".($i+1), '', 'post','string', JREQUEST_ALLOWRAW );            
            $row2->fulltext = JRequest::getVar( "fulltext_".($i+1), '', 'post','string', JREQUEST_ALLOWRAW );        
            $row2->metadesc = $row->metadesc[$i];
            $row2->metakey = $row->metakey[$i];
            $row2->metadata = $row->metadata;
            $row2->state = $row->state;
            $row2->attribs=$attribs = "show_title=
link_titles=
show_intro=
show_section=
link_section=
show_category=
link_category=
show_vote=
show_author=
show_create_date=
show_modify_date=
show_pdf_icon=
show_print_icon=
show_email_icon=
language=
keyref=
readmore=";
            $db        = & JFactory::getDBO();    
            $fp = new TableFrontPage($db);

            if (!$row2->store()) {
                echo "<script> alert('".$row2->getError()."'); </script>";
           return false;
            }    
            $row2->checkin();                 
            $row2->reorder('catid = '.(int) $row2->catid.' AND state >= 0');            
                 


            if ($addMenu) {  
                if ($row2->sectionid<=0) //static content
                    $type="content_typed" ;
                else
                    $type="content_item_link" ;
                    $this->menuLink( $row2->id, $row2->title,$menu,$type,$parent );
            } 

            // Is the article viewable on the frontpage?
        if ($frontpage)
        {

            // Is the item already viewable on the frontpage?             
                // Insert the new entry
                $query = 'INSERT INTO #__content_frontpage' .
                        ' VALUES ( '. (int) $row2->id .', 1 )';
                $db->setQuery($query);
                if (!$db->query())
                {
                    JError::raiseError( 500, $db->stderr() );
                    return false;
                }
                $fp->ordering = 1;        
                $fp->reorder();                
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
    $menuTypes     = $this->getMenuTypes(); 
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
            foreach ($menuItems4 as $v) {    // iterate through the menu items
                $pt     = $v->parent;        // we use parent as our array index
        
                // if an array entry for the parent doesn't exist, we create a new array
                $list     = @$children[$pt] ? $children[$pt] : array();
         
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
