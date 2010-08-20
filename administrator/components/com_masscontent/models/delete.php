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

class MassContentModelDelete extends JModel
{

function &getData(){
    $database = & JFactory::getDBO();
    global  $my, $mainframe;
    $option 	= "com_masscontent";   
    $contentSection=0;
    $lists=null;
    $sectioncategories=0;
    $javascript = "onchange=\"changeDynaList( 'catid', sectioncategories, document.adminForm.sectionid.options[document.adminForm.sectionid.selectedIndex].value, 0, 0);\"";
    
    //section
    $query = "SELECT s.id, s.title"
	. "\n FROM #__sections AS s"
	. "\n ORDER BY s.ordering";
	$database->setQuery( $query );

		$sections[] = JHTML::_('select.option', '-1', 'Select section', 'id', 'title' );
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
   	$sectioncategories 			= array();
	$sectioncategories[-1] 		= array();
	$sectioncategories[-1][] 	= JHTML::_('select.option', '-1', 'Select Category', 'id', 'name' );
	JArrayHelper::toInteger( $section_list );
	$section_list 				= 'section=' . implode( ' OR section=', $section_list );

	$query = "SELECT id, title, section"
	. "\n FROM #__categories"
	. "\n WHERE ( $section_list )"
	. "\n ORDER BY ordering"
	;
	$database->setQuery( $query );
	$cat_list = $database->loadObjectList();
	/*foreach($sections as $section) {
		$sectioncategories[$section->id] = array();
		$rows2 = array();
		foreach($cat_list as $cat) {
			if ($cat->section == $section->id) {
				$rows2[] = $cat;
			}
		}
		foreach($rows2 as $row2) {
			$sectioncategories[$section->id][] = JHTML::_('select.option', $row2->id, $row2->name, 'id', 'name' );
		}
	}
*/
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
 		$categories[] 		= JHTML::_('select.option', '0', 'Select Category', 'id', 'name' );
 		$lists['catid'] 	= JHTMLSelect::genericList( $categories, 'catid', 'class="inputbox" size="1"', 'id', 'name' );
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
		$categories[] 		= JHTML::_('select.option', '0', 'Select Category', 'id', 'name' );
		$categories 		= array_merge( $categories, $categoriesA );
 		$lists['catid'] 	= JHTMLSelect::genericList( $categories, 'catid', 'class="inputbox" size="1"', 'id', 'name', intval( $row->catid ) );
  	}
        
        $lists['sectioncategories']= $sectioncategories;
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

function deleteMassContent( $option=null ) {
	global $mainframe;
    $database = & JFactory::getDBO();

    $sectionid = JRequest::getVar(   'sectionid', '' ,'POST');
    $deleteSection = JRequest::getVar(   'deleteSection', '','POST' );
    $catid = JRequest::getVar(   'catid', '','POST' );
    $deleteCategory = JRequest::getVar(   'deleteCategory', '' ,'POST');
    $allCat = JRequest::getVar(   'allCat', '','POST' );
    $deleteContentOnly = JRequest::getVar(   'deleteContentOnly', '','POST' );
    $where="";
    
    //a section has been selected
    if ( $sectionid != "-1" ){  
        
		if ($deleteSection){		
        
			//delete link menu-section		
	        $query = "DELETE m FROM #__menu m "              
	            . "\n WHERE m.componentid IN ( "       
					. "\n SELECT id FROM #__sections "
					. "\n WHERE id=$sectionid "                          
				. "\n )"       
	            . "\n AND LOCATE( \"section\", link ) >0"      
	            ;
		    $database->setQuery( $query );
			$database->query(); 
                      		
			//delete section
            $query = "DELETE FROM #__sections WHERE id=$sectionid";
            $database->setQuery( $query );
			$database->query();
            
        }
		
		if ($catid>0 || $allCat) //a cat is selected
		{
		
	        //when "all cats" is not selected
	        if (!$allCat && $catid>0) $where= "\n AND id=$catid ";
	       
	        if ($deleteCategory) {  		   
				//delete link menu-cat			
				$query = "DELETE m FROM #__menu m "              
	            . "\n WHERE m.componentid IN ( "       
					. "\n SELECT id FROM #__categories ca "
					. "\n WHERE ca.section=$sectionid "                  
					. $where            
				. "\n )"       
	            . "\n AND LOCATE( \"category\", link ) >0"       
	            ;
				$database->setQuery( $query );
				$database->query();      

				//delete cat
	            $query = "DELETE FROM #__categories"
	            . "\n WHERE section=$sectionid"
	            . $where;
	            ;
			    $database->setQuery( $query );
				$database->query();            
	        }
        }
		
         //when "all cats" is not selected
        if (!$allCat && $catid>0) $where= "\n AND catid=$catid ";		                  

        //delete link menu-content
        $query = "DELETE m FROM #__menu m "              
            . "\n WHERE m.componentid IN ( "       
				. "\n SELECT id FROM #__content co "
				. "\n WHERE co.sectionid=$sectionid "                  
				. $where            
			. "\n )"       
           . "\n AND LOCATE( \"article\", link ) >0"      
            ;
	    $database->setQuery( $query );
		$database->query();
		
        //delete content 
         if ($deleteContentOnly) { 
          $query = "UPDATE #__content SET `introtext`='', `fulltext`='' "   
            . "\n WHERE sectionid=$sectionid"       
            . $where ;         
         }
         else {          
        //delete full content (article)
        $query = "DELETE co FROM #__content co "   
            . "\n WHERE co.sectionid=$sectionid"       
            . $where ;
         }
        $database->setQuery( $query );
		$database->query();
    }
 return true;   
}

}
