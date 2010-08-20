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

jimport('joomla.application.component.model');

/**
 * mtwMigratorModelMigrate
 *
 * @package    mtwMigrator
 * @subpackage Components
 */
class mtwMigratorModelMigrate extends JModel
{
	var $_config = null;
	var $_externalDB = null;
	var $_status = array();

	/**
	 * Constructor that retrieves the ID from the request
	 *
	 * @access	public
	 * @return	void
	 */
	function __construct() {
		parent::__construct();

		ini_set('max_execution_time','3600');

		$this->_config = $this->getConfig();
		$this->getConnectToExternalDB();

	}


	function getConfig() {

		$configFile = JPATH_COMPONENT.DS.'mtwmigrator_config.php';
		include( $configFile );

		$config = array();
        $config['driver']   = 'mysql';
		$config['host']     = $mtwCFG['hostname'];
        $config['user']     = $mtwCFG['username']; 
        $config['password'] = $mtwCFG['password'];
        $config['database'] = $mtwCFG['dbname'];  
        $config['prefix']   = $mtwCFG['prefix'];

		return $config;

	}


	function getConnectToExternalDB () {

		$this->_externalDB = JDatabase::getInstance( $this->_config );
		//echo "hasUTF(): " . $this->_externalDB->hasUTF() . "<br>";
		//print_r($this->_externalDB);

		//mb_detect_order("GB2312,ISO-8859-1,UTF-8");
		//echo implode(", ", mb_detect_order());

		if ( $this->_externalDB->message ) {
			//print_r($this->_externalDB);
			$this->setError($this->_externalDB->message);
			return false;
		}

		$query = "SET NAMES `utf8`";
        $this->_externalDB->setQuery( $query );
        $data = $this->_externalDB->query();

		return true;

	}

	function getMigration () {

		$configFile = JPATH_COMPONENT.DS.'mtwmigrator_config.php';
		include( $configFile );

		//print_r($this);

		if ( !$this->getError() ) {

			//echo "-10";

			if ($mtwCFG['backup'] == 1) 
				$this->_status['backup'] = $this->__startBackup();
			else
				$this->_status['backup'] = 9999;
/*
			if ($mtwCFG['groups'] == 1) 
				$this->_status['groups'] = $this->__migrateGroups();
			else
				$this->_status['groups'] = 9999;
 */

			if ($mtwCFG['users'] == 1) 
				$this->_status['users'] = $this->__migrateUsers();
			else
				$this->_status['users'] = 9999;
	
			if ($mtwCFG['sections'] == 1) 
	   	     	$this->_status['sections'] = $this->__migrateSections();
			else
				$this->_status['sections'] = 9999;

			if ($mtwCFG['categories'] == 1) 
	        	$this->_status['categories'] = $this->__migrateCategories();
			else
				$this->_status['categories'] = 9999;

			if ($mtwCFG['content'] == 1) 
    	    	$this->_status['content'] = $this->__migrateContent();
			else
				$this->_status['content'] = 9999;

			if ($mtwCFG['frontpage'] == 1) 
				$this->_status['frontpage'] = $this->__migrateContentFrontpage();
			else
				$this->_status['frontpage'] = 9999;

			if ($mtwCFG['menus'] == 1) { 
				$this->_status['menus'] = $this->__migrateModulesMenus();
				$this->_status['menus'] = $this->__migrateMenus();
			}else{
				$this->_status['menus'] = 9999;
			}

			if ($mtwCFG['modules'] == 1) 
				$this->_status['modules'] = $this->__migrateModules();
			else
				$this->_status['modules'] = "9999";
	
			if ($mtwCFG['polls'] == 1) 
				$this->_status['polls'] = $this->__migratePolls();
			else
				$this->_status['polls'] = "9999";

			if ($mtwCFG['weblinks'] == 1) 
				$this->_status['weblinks'] = $this->__migrateWeblinks();
			else
				$this->_status['weblinks'] = "9999";

            if ($mtwCFG['contacts'] == 1)
                $this->_status['contacts'] = $this->__migrateContacts();
            else
                $this->_status['contacts'] = "9999";

			/* External Extensions */
            if ($mtwCFG['ext_aj'] == 1)
                $this->_status['ext_aj'] = $this->__migrateAJ();
            else
                $this->_status['ext_aj'] = "9999";

			if ($mtwCFG['ext_cb'] == 1) 
				$this->_status['ext_cb'] = $this->__migrateCB();
			else
				$this->_status['ext_cb'] = "9999";
				
			if ($mtwCFG['ext_dm'] == 1) 
                $this->_status['ext_dm'] = $this->__migrateDM();
            else
                $this->_status['ext_dm'] = "9999";

            if ($mtwCFG['ext_fb'] == 1)
                $this->_status['ext_fb'] = $this->__migrateFB();
            else
                $this->_status['ext_fb'] = "9999";

            if ($mtwCFG['ext_ku'] == 1)
                $this->_status['ext_ku'] = $this->__migrateFB();
            else
                $this->_status['ext_ku'] = "9999";

			if ($mtwCFG['ext_ff'] == 1) 
                $this->_status['ext_ff'] = $this->__migrateFF();
            else
                $this->_status['ext_ff'] = "9999";

			if ($mtwCFG['ext_jc'] == 1) 
                $this->_status['ext_jc'] = $this->__migrateJC();
            else
                $this->_status['ext_jc'] = "9999";

			if ($mtwCFG['ext_lm'] == 1) 
                $this->_status['ext_lm'] = $this->__migrateLM();
            else
                $this->_status['ext_lm'] = "9999";

            if ($mtwCFG['ext_vm'] == 1) 
                $this->_status['ext_vm'] = $this->__migrateVM();
            else
                $this->_status['ext_vm'] = "9999";

	        //print_r($status);
			//print_r($this->_status);
			//echo "0<br>";

			return $this->_status;

		}

	}

	function __startBackup() {

        $backupFile = "components/com_mtwmigrator/backups/" . $this->_config['database'] . "-" . date("YmdHis") . '.gz';
        $command = "mysqldump --opt -h " . $this->_config['host'] . " -u " . $this->_config['user'] . " -p" .  $this->_config['password'] . " " . $this->_config['database'] . "| gzip > " . $backupFile;   
		//echo $command . "<br><br>";
        system($command);

		return 0;
	}		

    function __migrateGroups() {

		$query = "DROP TABLE " . $this->_config['prefix'] . "core_acl_aro_groups";
		$this->_externalDB->setQuery($query);
		$this->_externalDB->query();
		echo $this->_externalDB->getError();

		$query = "SELECT `group_id` AS `id` , `parent_id`, `name`, `lft`, `rgt`, `name` AS `value`"
				." FROM " . $this->_config['prefix'] . "core_acl_aro_groups"
				." WHERE `id` > 2";

        $this->_externalDB->setQuery( $query );
        $data = $this->_externalDB->loadObjectList();
        //echo $externalDB->ErrorMsg();
        //print_r($data);

        $ret = $this->insertObjectList('#__core_acl_aro_groups', $data);


		$query = "SELECT `id`, `name`"
				." FROM " . $this->_config['prefix'] . "groups"
				." WHERE `id` > 2";

        $this->_externalDB->setQuery( $query );
        $data = $this->_externalDB->loadObjectList();
        //echo $externalDB->ErrorMsg();
        //print_r($data);

        $ret = $this->insertObjectList('#__groups', $data);

		return $ret;
    }

    function __migrateUsers() {

		$query = "SELECT `id`, `name`, `username`, `email`, `password`, `usertype`, `block`,"
				." `sendEmail`, `gid`, `registerDate`, `lastvisitDate`, `activation`, `params`"
				." FROM " . $this->_config['prefix'] . "users"
				." WHERE id != 62";

		//echo $query;

		$this->_externalDB->setQuery( $query );
		$users = $this->_externalDB->loadObjectList();
		//print_r($users);
		$ret = $this->insertObjectList('#__users', $users);

	
		$query = "SELECT aro_id AS id, section_value, value, order_value, name, hidden"
				." FROM " . $this->_config['prefix'] . "core_acl_aro"
				." WHERE aro_id != 10";

		$this->_externalDB->setQuery( $query );
		$core_acl_aro = $this->_externalDB->loadObjectList();
		$ret = $this->insertObjectList('#__core_acl_aro', $core_acl_aro);	


		$query = "SELECT group_id , section_value, aro_id"
				." FROM " . $this->_config['prefix'] . "core_acl_groups_aro_map"
				." WHERE aro_id != 10";

        $this->_externalDB->setQuery( $query );
        $core_acl_groups_aro_map = $this->_externalDB->loadObjectList();
        $ret = $this->insertObjectList('#__core_acl_groups_aro_map', $core_acl_groups_aro_map);

		return $ret;
	}

    function __migrateSections() {

		$query = "SELECT `id`, `title`, `name`, NULL, `image`, `scope`, `image_position`,"
				." `description`, `published`, `checked_out`, `checked_out_time`, `ordering`, `access`, `count`, `params`"
			    ." FROM " . $this->_config['prefix'] . "sections";

        $this->_externalDB->setQuery( $query );
        $data = $this->_externalDB->loadObjectList();
        //echo $externalDB->ErrorMsg();
        //print_r($data);

        $ret = $this->insertObjectList('#__sections', $data);

		return $ret;
    }

    function __migrateCategories() {

        $query = "SELECT `id`, `parent_id`, `title`, `name`, NULL, `image`, `section`, `image_position`, `description`, `published`, `checked_out`, `checked_out_time`, `editor`, `ordering`, `access`, `count`, `params`"
                ." FROM " . $this->_config['prefix'] . "categories"
                ." WHERE section != 'com_docman'";

        $this->_externalDB->setQuery( $query );
        $data = $this->_externalDB->loadObjectList();
        //echo $externalDB->ErrorMsg();
        //print_r($data);

        $ret = $this->insertObjectList('#__categories', $data);

		return $ret;
    }

    function __migrateContent() {

		$query = "SELECT `id`, `title`, NULL AS `alias`, `title_alias`, `introtext`, `fulltext`, `state`, `sectionid`, `mask`, `catid`, `created`, `created_by`, `created_by_alias`, `modified`, `modified_by`, `checked_out`, `checked_out_time`, `publish_up`, `publish_down`, `images`, `urls`, `attribs`, `version`, `parentid`, `ordering`, `metakey`, `metadesc`, `access`, `hits`, NULL"
				." FROM " . $this->_config['prefix'] . "content"
				." ORDER BY id ASC";

        $this->_externalDB->setQuery( $query );
        $data = $this->_externalDB->loadObjectList();
		echo $this->_externalDB->errorMsg();


        $count = count($data);
		for ($i=0; $i<$count; $i++) {

			/* set alias */	
			$data[$i]->alias = JFilterOutput::stringURLSafe($data[$i]->title);

			/* UTF-8 */
/*
			iconv_set_encoding("internal_encoding", "UTF-8");
			iconv_set_encoding("output_encoding", "UTF-8");

			$cur_encoding = mb_detect_encoding($data[$i]->title) ;
		  	if($cur_encoding == "UTF-8" && mb_check_encoding($data[$i]->title,"UTF-8")){
		    	//$data[$i]->title = $data[$i]->title;
				//$data[$i]->title = mb_convert_encoding($data[$i]->title, "UTF-8", mb_detect_encoding($data[$i]->title));
				//$data[$i]->title = utf8_encode($data[$i]->title);
				echo "1: " . mb_detect_encoding($data[$i]->title) . " > ";
		  	}else{
				//$data[$i]->title = utf8_encode($data[$i]->title);
				//$data[$i]->title = mb_convert_encoding($data[$i]->title, "UTF-8", mb_detect_encoding($data[$i]->title));
				$data[$i]->title = iconv ( mb_detect_encoding($data[$i]->title), "UTF-8", $data[$i]->title );
				echo "2: " . mb_detect_encoding($data[$i]->title) . " > ";
			}

			//echo $title = utf8_encode($data[$i]->title) . "<br>";
			echo $data[$i]->title . "<br>";
*/
			//echo iconv ( "ISO-8859-1", "UTF-8", $data[$i]->title ); 
			//iconv_set_encoding("internal_encoding", "UTF-8");
			//iconv_set_encoding("output_encoding", "UTF-8");
			//print_r(iconv_get_encoding('all'));
	
			//echo "<br>";
			//echo mb_detect_encoding($title) . "<br>";
			//$data[$i]->title = utf8_decode($data[$i]->title);
			//$data[$i]->introtext = utf8_decode($data[$i]->introtext);
			//$data[$i]->fulltext = utf8_decode($data[$i]->fulltext);

			/* {mosimage} */

			if ($data[$i]->images) {
				$images = explode ("\n", $data[$i]->images);
				$count_images = count($images);
				//print_r($images);
				
				for ($y=0; $y<$count_images; $y++) {
						$params = explode ("|", $images[$y]);
						//print_r($params);

						if ($params[1] == "left"){
							$style = "style=\"float: left;\"";
						}else if ($params[1] == "right"){
							$style = "style=\"float: right;\"";
						}
						
						$img =	"<img src=\"images/stories/" . $params[0] . "\" hspace=\"6\" alt=\"" . $params[2] . "\" title=\"" . $params[2] . "\" border=\"0\" $style />";
						//echo "> " . strpos($data[$i]->introtext, "{mosimage}") . " < == <br>";

						$intro = strpos($data[$i]->introtext, "{mosimage}");

						/* DEBUG
						if ($data[$i]->id ==1468){
							//echo $intro. "<br>";
							//echo "<br><b>1></b><br>" . htmlentities($data[$i]->introtext) . "<br>";
							//echo "<br>2><b>" . htmlentities($data[$i]->fulltext) . "</b><br>";
						}
						*/

						if ($intro !== false) {
								$data[$i]->introtext = preg_replace("/{mosimage}/", $img, $data[$i]->introtext, 1);
								//echo "1<br>" ;
						}else{
								$data[$i]->fulltext = preg_replace("/{mosimage}/", $img, $data[$i]->fulltext, 1);
								//echo "2<br>";
						}
						
						unset($intro);
				}
			}
		}

        $ret = $this->insertObjectList('#__content', $data);

		return $ret;
    }

    function __migrateContentFrontpage() {

		$query = "SELECT `content_id`, `ordering` FROM " . $this->_config['prefix'] . "content_frontpage";

        $this->_externalDB->setQuery( $query );
        $data = $this->_externalDB->loadObjectList();
        //print_r($data);

        $ret = $this->insertObjectList('#__content_frontpage', $data);

		return $ret;
    }

    function __migrateModulesMenus() {

		$db =& JFactory::getDBO();

		$query = "UPDATE #__modules SET id=id+10000";
		
		$db->setQuery($query);
		$db->query();
		echo $db->getError();


        $query = "SELECT `id`, `title`, `content`, `ordering`, `position`, `checked_out`,"
				." `checked_out_time`, `published`, `module`, `numnews`, `access`, `showtitle`, `params`, `iscore`, `client_id`"
				." FROM " . $this->_config['prefix'] . "modules"
				." WHERE (module = 'mod_mainmenu' || module = 'mod_exmenu')"
				." AND published = 1";

        $this->_externalDB->setQuery( $query );
        $data = $this->_externalDB->loadObjectList();
        //print_r($data);

		$this->insertObjectList("#__modules", $data);

		/* Modules Menu (#__modules_menu) */

        $query = "UPDATE #__modules_menu SET moduleid=moduleid+10000";
        $db->setQuery($query);
		$db->query();
		echo $db->getError();

        $query = "SELECT moduleid, menuid FROM " . $this->_config['prefix'] . "modules_menu";
        $this->_externalDB->setQuery( $query );
        $assignment = $this->_externalDB->loadObjectList();

        $ret = $this->insertObjectList("#__modules_menu", $assignment);

        return $ret;

    }

    function __migrateMenus() {

        $db =& JFactory::getDBO();

        $query = "UPDATE #__menu SET id=id+100000";

        $db->setQuery($query);
        if (!$db->query()) {
            return JError::raiseWarning( 500, $db->getError() );
        }


        $query = "SELECT DISTINCT(`menutype`), menutype AS title"
                ." FROM " . $this->_config['prefix'] . "menu";

        $this->_externalDB->setQuery( $query );
        $data = $this->_externalDB->loadObjectList();

        //print_r($data);

        $this->insertObjectList('#__menu_types', $data);

		$ret = $this->__insertMenus();

		return $ret;

	}

    /**
     * Inserts the menus
     */

	function __insertMenus () {

		//echo $id, $oldID;

		$query = "SELECT `id`, `menutype`, `name`, REPLACE(LOWER(`name`), ' ' , '-') AS alias, `link`, `type`, `published`, `parent`,"
				." `componentid`, `sublevel`, `ordering`, `checked_out`, `checked_out_time`, `pollid`, `browserNav`, `access`, `utaccess`, `params`"
				." FROM " . $this->_config['prefix'] . "menu"
				." ORDER by id ASC";
	
		$this->_externalDB->setQuery( $query );
		$data = $this->_externalDB->loadObjectList();

		//print_r($data);

        $count = count($data);

        for ($i=0; $i<$count; $i++) {

			$linkTmp = explode("?", $data[$i]->link);
			$linkTmp = $linkTmp[1];
			
			$linkTmp = explode("&", $linkTmp);

			//print_r($linkTmp);
			//echo "<br>";

			$link = array();

			foreach ($linkTmp as $key => $value) {
				$valueTmp = explode("=", $value);
	
				$link[$valueTmp[0]] = $valueTmp[1];

 				//echo "Key: $key; Value: $value<br />\n";
				//print_r($link);
				//echo "<br>";
			}

			//print_r($link);
			//echo "<br>";

			if ($link['option'] == 'com_frontpage') {
				$data[$i]->link = "index.php?option=com_content&view=frontpage";
				$data[$i]->type = "component";
			}else if ($link['option'] == 'com_content') {
	
				$data[$i]->type = "component";	
				$data[$i]->componentid = 20;				

				//echo $link['task'];
	
				if ($link['task'] == 'blogsection') {
					$data[$i]->link = "index.php?option=com_content&view=section&layout=blog&id=" . $link['id'];
				}else if ($link['task'] == 'blogcategory') {
					$data[$i]->link = "index.php?option=com_content&view=category&layout=blog&id=" . $link['id'];
				}else if ($link['task'] == 'view') {
                    $data[$i]->link = "index.php?option=com_content&view=article&id=" . $link['id'];
                }else if ($link['task'] == 'section') {
                    $data[$i]->link = "index.php?option=com_content&view=section&id=" . $link['id'];
                }else if ($link['task'] == 'category') {
                    $data[$i]->link = "index.php?option=com_content&view=category&id=" . $link['id'];
                }
			}
		
			$params = $this->getNewParams($data[$i]->params);

			$data[$i]->params = $params->toString(); 

            //print_r($data[$i]);
            //echo "<br>";

			unset($link);
			unset($linkTmp);
        }

        $ret = $this->insertObjectList('#__menu', $data);

        return $ret;

	}

    function __migrateModules() {

        $query = "SELECT `title`, `content`, `ordering`, `position`, `checked_out`,"
                ." `checked_out_time`, `published`, `module`, `numnews`, `access`, `showtitle`, `params`, `iscore`, `client_id`"
                ." FROM " . $this->_config['prefix'] . "modules"
                ." WHERE module = '' && module = 'mod_poll'";

        $this->_externalDB->setQuery( $query );
        $data = $this->_externalDB->loadObjectList();
        //echo $externalDB->errorMsg();
        //print_r($data);

        $ret = $this->insertObjectList('#__modules', $data);

        return $ret;
    }

    function __migratePolls() {

        $query = "SELECT `id`, `title`, NULL, `voters`, `checked_out`,"
                ." `checked_out_time`, `published`, `access`, `lag`"
                ." FROM " . $this->_config['prefix'] . "polls";

        $this->_externalDB->setQuery( $query );
        $polls = $this->_externalDB->loadObjectList();
        //echo $externalDB->errorMsg();
        //print_r($data);

        $ret = $this->insertObjectList('#__polls', $polls);

		/* */
        $query = "SELECT `id`, `pollid`, `text`, `hits`"
                ." FROM " . $this->_config['prefix'] . "poll_data";

        $this->_externalDB->setQuery( $query );
        $poll_data = $this->_externalDB->loadObjectList();
        //echo $externalDB->errorMsg();
        //print_r($data);

        $ret = $this->insertObjectList('#__poll_data', $poll_data);

        /* */
        $query = "SELECT `id`, `date`, `vote_id`, `poll_id`"
                ." FROM " . $this->_config['prefix'] . "poll_date";
    
        $this->_externalDB->setQuery( $query );
        $poll_date = $this->_externalDB->loadObjectList();
        //echo $externalDB->errorMsg();
        //print_r($data);

        $ret = $this->insertObjectList('#__poll_date', $poll_date);

        /* */
        $query = "SELECT `pollid`, `menuid`"
                ." FROM " . $this->_config['prefix'] . "poll_menu";

        $this->_externalDB->setQuery( $query );
        $poll_menu = $this->_externalDB->loadObjectList();
        //echo $externalDB->errorMsg();
        //print_r($data);

        $ret = $this->insertObjectList('#__poll_menu', $poll_menu);

        return $ret;
    }

    function __migrateWeblinks() {

		$query = "SELECT `id`, `catid`, `sid`, `title`,  NULL AS `alias`, `url`, `description`, `date`, `hits`, `published`, `checked_out`, `checked_out_time`, `ordering`, `archived`, `approved`, `params`"
				." FROM " . $this->_config['prefix'] . "weblinks";

        $this->_externalDB->setQuery( $query );
        $data = $this->_externalDB->loadObjectList();
        //print_r($data);

		/* set alias */
        $count = count($data);
        for ($i=0; $i<$count; $i++) {
			$data[$i]->alias = JFilterOutput::stringURLSafe($data[$i]->title);
        }

        $ret = $this->insertObjectList('#__weblinks', $data);

		return $ret;
    }

    function __migrateContacts() {

        $query = "SELECT `id`, `name`, NULL AS `alias`, `con_position`, `address`, `suburb`, `state`, `country`, `postcode`, `telephone`, `fax`, `misc`, `image`, `imagepos`"
				." `email_to`, `default_con`, `published`, `checked_out`, `checked_out_time`, `ordering`, `params`, `user_id`, `catid`, `access`"
                ." FROM " . $this->_config['prefix'] . "contact_details";

        $this->_externalDB->setQuery( $query );
        $data = $this->_externalDB->loadObjectList();
        //print_r($data);

        /* set alias */
        $count = count($data);
        for ($i=0; $i<$count; $i++) {
            $data[$i]->alias = JFilterOutput::stringURLSafe($data[$i]->name);
        }

        $ret = $this->insertObjectList('#__contact_details', $data);

        return $ret;
    }

    function __migrateAJ() {

        $ret = $this->migrateTable('#__redirection');
        $this->migrateTable('#__sefexts');
        $this->migrateTable('#__sefexttexts');
        $this->migrateTable('#__sefmoved');

        return $ret;
    }

    function __migrateCB() {

		$ret = $this->migrateTable('#__comprofiler');
		$this->migrateTable('#__comprofiler_fields');
		$this->migrateTable('#__comprofiler_field_values');
		$this->migrateTable('#__comprofiler_lists');
		$this->migrateTable('#__comprofiler_members');
		//$this->migrateTable('#__comprofiler_plugin');
		$this->migrateTable('#__comprofiler_tabs');
		$this->migrateTable('#__comprofiler_userreports');
		$this->migrateTable('#__comprofiler_views');


		return $ret;
    }

    function __migrateDM() {

		$ret = $this->migrateTable('#__docman');
		$this->migrateTable('#__docman_groups');
		$this->migrateTable('#__docman_history');
		$this->migrateTable('#__docman_licenses');
		$this->migrateTable('#__docman_log');

        $query = "SELECT `id`, `parent_id`, `title`, `name`, NULL, `image`, `section`, `image_position`, `description`, `published`, `checked_out`, `checked_out_time`, `editor`, `ordering`, `access`, `count`, `params`"
                ." FROM " . $this->_config['prefix'] . "categories"
                ." WHERE section = 'com_docman'";

        $this->_externalDB->setQuery( $query );
        $data = $this->_externalDB->loadObjectList();
        //print_r($data);

        $ret = $this->insertObjectList('#__categories', $data);

		return $ret;
    }

    function __migrateFF() {

		$ret = $this->migrateTable('#__facileforms_compmenus');
		$this->migrateTable('#__facileforms_config');
		$this->migrateTable('#__facileforms_elements');
		$this->migrateTable('#__facileforms_forms');
		$this->migrateTable('#__facileforms_packages');
		$this->migrateTable('#__facileforms_pieces');
		$this->migrateTable('#__facileforms_records');
		$this->migrateTable('#__facileforms_scripts');
		$this->migrateTable('#__facileforms_subrecords');


		return $ret;
    }

    function __migrateFB() {

        $ret = $this->migrateTable('#__fb_announcement');
        $this->migrateTable('#__fb_attachments');
        $this->migrateTable('#__fb_categories');
        $this->migrateTable('#__fb_favorites');
        $this->migrateTable('#__fb_groups');
        $this->migrateTable('#__fb_messages');
        $this->migrateTable('#__fb_messages_text');
        $this->migrateTable('#__fb_moderation');
        $this->migrateTable('#__fb_ranks');
        $this->migrateTable('#__fb_sessions');
        $this->migrateTable('#__fb_smileys');
        $this->migrateTable('#__fb_subscriptions');
        $this->migrateTable('#__fb_users');
        $this->migrateTable('#__fb_whoisonline');
        
        return $ret;
    }
 
    function __migrateJC() {

		// BUG -> remove 'referrer' from jomcomment table

		$ret = $this->migrateTable('#__jomcomment');
		$this->migrateTable('#__jomcomment_admin');
		$this->migrateTable('#__jomcomment_config');
		$this->migrateTable('#__jomcomment_fav');
		$this->migrateTable('#__jomcomment_mailq');
		$this->migrateTable('#__jomcomment_reported');
		$this->migrateTable('#__jomcomment_reports');
		$this->migrateTable('#__jomcomment_subs');
		$this->migrateTable('#__jomcomment_tb');
		$this->migrateTable('#__jomcomment_tb_sent');
		$this->migrateTable('#__jomcomment_votes');

		return $ret;
    }

    function __migrateLM() {

		$ret = $this->migrateTable('#__letterman');
		$this->migrateTable('#__letterman_subscribers');

		return $ret;
    }

    function __migrateVM() {
            
        $ret = $this->migrateTable('#__vm_affiliate');
        $this->migrateTable('#__vm_affiliate_sale');
        $this->migrateTable('#__vm_auth_user_vendor');
        $this->migrateTable('#__vm_category');
        $this->migrateTable('#__vm_category_xref');
        $this->migrateTable('#__vm_country');
        $this->migrateTable('#__vm_coupons');
        $this->migrateTable('#__vm_creditcard');
        $this->migrateTable('#__vm_csv');

        $this->migrateTable('#__vm_currency');
        $this->migrateTable('#__vm_function');
        $this->migrateTable('#__vm_manufacturer');
        $this->migrateTable('#__vm_manufacturer_category');
        $this->migrateTable('#__vm_module');
        $this->migrateTable('#__vm_orders');
        $this->migrateTable('#__vm_order_history');
        $this->migrateTable('#__vm_order_item');

        $this->migrateTable('#__vm_order_payment');
        $this->migrateTable('#__vm_order_status');
        $this->migrateTable('#__vm_order_user_info');
        $this->migrateTable('#__vm_payment_method');
        $this->migrateTable('#__vm_product');
        $this->migrateTable('#__vm_product_attribute');
        $this->migrateTable('#__vm_product_attribute_sku');
        $this->migrateTable('#__vm_product_category_xref');

        $this->migrateTable('#__vm_product_discount');
        $this->migrateTable('#__vm_product_download');
        $this->migrateTable('#__vm_product_files');
        $this->migrateTable('#__vm_product_mf_xref');
        $this->migrateTable('#__vm_product_price');
        $this->migrateTable('#__vm_product_product_type_xref');
        $this->migrateTable('#__vm_product_relations');
        $this->migrateTable('#__vm_product_reviews');
        $this->migrateTable('#__vm_product_type');
        $this->migrateTable('#__vm_product_type_parameter');
        $this->migrateTable('#__vm_product_votes');

        $this->migrateTable('#__vm_shipping_carrier');
        $this->migrateTable('#__vm_shipping_rate');
        $this->migrateTable('#__vm_shopper_group');
        $this->migrateTable('#__vm_shopper_vendor_xref');
        $this->migrateTable('#__vm_state');
        $this->migrateTable('#__vm_tax_rate');
        $this->migrateTable('#__vm_user_info');
        $this->migrateTable('#__vm_vendor');
        $this->migrateTable('#__vm_vendor_category');
        $this->migrateTable('#__vm_visit');

        return $ret;
    }


    /**
     * Migrate table from old db to new
     *
     * @access  public
     * @param   string  The name of the table
     */
    function migrateTable( $table ) {

        $query = "SELECT * FROM " . $table;

        $this->_externalDB->setQuery( $query );
        $object = $this->_externalDB->loadObjectList();

		$db =& JFactory::getDBO();
        $count = count($object);

        for ($i=0; $i<$count; $i++) {
            $db->insertObject($table, $object[$i]);
			//echo $db->errorMsg();
        }

		$ret =  $db->getErrorNum();

        return $ret;
    }



    /**
     * Inserts a list of rows into a table based on an objects properties
     *
     * @access  public
     * @param   string  The name of the table
     * @param   object  An object whose properties match table fields
     * @param   string  The name of the primary key. If provided the object property is updated.
     */
    function insertObjectList( $table, &$object, $keyName = NULL ) {

		$db =& JFactory::getDBO();
        $count = count($object);

        //print_r($object);

        for ($i=0; $i<$count; $i++) {
            $db->insertObject($table, $object[$i]);
			//echo $db->errorMsg();
        }

		$ret =  $db->getErrorNum();

        return $ret;
    }

    function getNewParams( $oldParams ) {    
 
		$params = new JParameter('');
		$oldParams = new JParameter( $oldParams );

		$params->set('show_headings', $oldParams->get('leading'));
		$params->set('show_date', $oldParams->get('date'));
        $params->set('date_format', $oldParams->get('date_format'));
        $params->set('filter', 1);
        $params->set('filter_type', $oldParams->get('orderby_pri'));
	
        $params->set('orderby_sec', $oldParams->get('orderby_sec'));
        $params->set('show_pagination', $oldParams->get('pagination'));
        $params->set('show_pagination_limit', $oldParams->get('pagination_results'));
        $params->set('show_feed_link', '');
        $params->set('show_noauth', '');

        $params->set('show_title', $oldParams->get('page_title'));
        $params->set('link_titles', $oldParams->get('link_titles'));
        $params->set('show_intro', $oldParams->get('intro'));
        $params->set('show_section', '');
        $params->set('link_section', $oldParams->get('sectionid'));

        $params->set('show_category', $oldParams->get('category'));
        $params->set('link_category', $oldParams->get('category_link'));
        $params->set('show_author', $oldParams->get('author'));
        $params->set('show_create_date', $oldParams->get('createdate'));
        $params->set('show_modify_date', $oldParams->get('modifydate'));

        $params->set('show_item_navigation', '');
        $params->set('show_readmore', $oldParams->get('readmore'));
        $params->set('show_vote', '');
        $params->set('show_icons', '');
        $params->set('show_pdf_icon', $oldParams->get('pdf'));

        $params->set('show_print_icon', $oldParams->get('print'));
        $params->set('show_email_icon', $oldParams->get('email'));
        $params->set('show_hits', '');
        $params->set('feed_summary', '');
        $params->set('page_title', '');

        $params->set('show_page_title', $oldParams->get('leading'));
        $params->set('pageclass_sfx', $oldParams->get('pageclass_sfx'));
        $params->set('menu_image', $oldParams->get('menu_image'));
        $params->set('secure', '0');

	
		//print_r($params);

        return $params;
    }  

}
?>
