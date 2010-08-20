<?php
/**
* @author Guillermo Vargas, http://joomla.vargas.co.cr
* @email guille@vargas.co.cr
* @version $Id: com_joomgallery.php 68 2009-12-10 22:00:15Z guilleva $
* @package Xmap
* @license GNU/GPL
* @description Xmap plugin for Kunena Forum Component.
*/

class xmap_com_joomgallery {

	/*
	* This function is called before a menu item is printed. We use it to set the
	* proper uniqueid for the item
	*/
	function prepareMenuItem(&$node) {
		$link_query = parse_url( $node->link );
		parse_str( html_entity_decode($link_query['query']), $link_vars);
		$id = intval(JArrayHelper::getValue($link_vars,'id',0));
		$func = JArrayHelper::getValue( $link_vars, 'func', '', '' );
		if ( $func =='detail' && !$id ) {
			$node->uid = 'com_joomgalleryp'.$id;
			$node->expandible = true;
		} elseif ($func =='viewcategory' && $id) {
			$node->uid = 'com_joomgalleryg'.$id;
			$node->expandible = false;
		}
	}

	function getTree ( &$xmap, &$parent, &$params ) {
		$catid=0;
		$link_query = parse_url( $parent->link );
		parse_str( html_entity_decode($link_query['query']), $link_vars);
		$func = JArrayHelper::getValue( $link_vars, 'func', '', '' );
		$catid = JArrayHelper::getValue($link_vars,'catid',0);

		if ($func && $func != 'viewcategory') {
			return;
		}

		$include_pics = JArrayHelper::getValue( $params, 'include_pics',1 );
		$include_pics = ( $include_pics == 1
                                  || ( $include_pics == 2 && $xmap->view == 'xml')
                                  || ( $include_pics == 3 && $xmap->view == 'html'));
		$params['include_pics'] = $include_pics;

		$priority = JArrayHelper::getValue($params,'cat_priority',$parent->priority);
		$changefreq = JArrayHelper::getValue($params,'cat_changefreq',$parent->changefreq);
		if ($priority  == '-1')
			$priority = $parent->priority;
		if ($changefreq  == '-1')
			$changefreq = $parent->changefreq;

		$params['cat_priority'] = $priority;
		$params['cat_changefreq'] = $changefreq;

		$priority = JArrayHelper::getValue($params,'pictures_priority',$parent->priority);
		$changefreq = JArrayHelper::getValue($params,'pictures_changefreq',$parent->changefreq);
		if ($priority  == '-1')
			$priority = $parent->priority;

		if ($changefreq  == '-1')
			$changefreq = $parent->changefreq;

		$params['picture_priority'] = $priority;
		$params['picture_changefreq'] = $changefreq;

		if ( $include_pics ) {
			$ordering = JArrayHelper::getValue($params,'picture_order','ordering');
			if ( !in_array($ordering,array('ordering','time','subjet','hits')) )
				$ordering = 'ordering';
			$params['picture_order'] = $ordering = $ordering;

			$params['limit'] = '';
			$params['days'] = '';
			$limit = JArrayHelper::getValue($params,'max_pictures','');
			if ( intval($limit) )
				$params['limit'] = ' LIMIT '.$limit;

			$days = JArrayHelper::getValue($params,'max_age','');
			if ( intval($days) )
				$params['days'] = ' AND time >='.($xmap->now - ($days*86400)) ." ";
		}

		xmap_com_joomgallery::getGallery($xmap, $parent, $params, $catid);
	}


	function getGallery ( &$xmap, &$parent, &$params, $catid )
	{
		$db =& JFactory::getDBO();
		$list = array();
		$db->setQuery("select cid, name, parent from #__joomgallery_catg where parent=$catid and published = '1' and access <=".$xmap->gid." order by ordering");
		$cats = $db->loadObjectList();
		$xmap->changeLevel(1);

		foreach($cats as $cat) {
			$node = new stdclass;
			$node->id   = $parent->id;
			$node->uid  = $parent->uid.'g'.$cat->cid;   // Uniq ID for the category
			$node->pid  = $cat->parent;
			$node->name = $cat->name? $cat->name : $cat->name;
			$node->priority   = $params['cat_priority'];
			$node->changefreq = $params['cat_changefreq'];
			$node->link = 'index.php?option=com_joomgallery&amp;func=viewcategory&amp;catid='.$cat->cid;
			$node->expandible = true;

			if ($xmap->printNode($node) !== FALSE ) {
				xmap_com_joomgallery::getGallery($xmap, $parent, $params, $cat->cid);
			}
		}

		if ( $params['include_pics'] ) {
			$db->setQuery ("select id, imgtitle, catid from #__joomgallery where catid IN ($catid) and published = '1' order by ordering " . $params['limit']);
			$cats = $db->loadObjectList();
			foreach($cats as $file) {
				$node = new stdclass;
				$node->id   = $parent->id;  // Itemid
				$node->uid  = $parent->uid .'p'.$file->id; // Uniq ID for the picture
				$node->name = ($file->imgtitle? $file->imgtitle : $file->imgtitle);
				$node->priority   = $params['pictures_priority'];
				$node->changefreq = $params['pictures_changefreq'];
				$node->link = 'index.php?option=com_joomgallery&amp;func=detail&amp;id='.$file->id;
				$node->expandible = false;
				$xmap->printNode($node);
			}
		}
		$xmap->changeLevel(-1);
	}

}