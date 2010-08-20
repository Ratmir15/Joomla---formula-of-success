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
 * Hello Hello Model
 *
 * @package    Joomla.Tutorials
 * @subpackage Components
 */
class mtwMigratorModelConfig extends JModel
{
	/**
	 * Constructor that retrieves the ID from the request
	 *
	 * @access	public
	 * @return	void
	 */
	function __construct()
	{
		parent::__construct();

		//$array = JRequest::getVar('cid',  0, '', 'array');
		//$this->setId((int)$array[0]);
	}


	function saveConfig( $post ) {

		$configText = "<?php\n";
		$configText .= "\$mtwCFG['hostname'] = \"" . $post['hostname'] . "\";\n";
		$configText .= "\$mtwCFG['dbname'] = \"" . $post['dbname'] . "\";\n";
		$configText .= "\$mtwCFG['username'] = \"" . $post['username'] . "\";\n";
		$configText .= "\$mtwCFG['password'] = \"" . $post['password'] . "\";\n";
		$configText .= "\$mtwCFG['prefix'] = \"" . $post['prefix'] . "\";\n";
		$configText .= "\$mtwCFG['ext_cb'] = \"" . $post['ext_cb'] . "\";\n";
		$configText .= "\$mtwCFG['ext_vm'] = \"" . $post['ext_vm'] . "\";\n";
		$configText .= "\$mtwCFG['ext_jc'] = \"" . $post['ext_jc'] . "\";\n";
		$configText .= "\$mtwCFG['ext_dm'] = \"" . $post['ext_dm'] . "\";\n";
		$configText .= "\$mtwCFG['ext_ff'] = \"" . $post['ext_ff'] . "\";\n";
		$configText .= "\$mtwCFG['ext_aj'] = \"" . $post['ext_aj'] . "\";\n";
		$configText .= "\$mtwCFG['ext_fb'] = \"" . $post['ext_fb'] . "\";\n";
		$configText .= "\$mtwCFG['ext_ku'] = \"" . $post['ext_ku'] . "\";\n";
		$configText .= "\$mtwCFG['ext_lm'] = \"" . $post['ext_lm'] . "\";\n";
		$configText .= "\$mtwCFG['backup'] = \"" . $post['backup'] . "\";\n";
		$configText .= "\$mtwCFG['groups'] = \"" . $post['groups'] . "\";\n";
		$configText .= "\$mtwCFG['users'] = \"" . $post['users'] . "\";\n";
		$configText .= "\$mtwCFG['sections'] = \"" . $post['sections'] . "\";\n";
		$configText .= "\$mtwCFG['categories'] = \"" . $post['categories'] . "\";\n";
		$configText .= "\$mtwCFG['content'] = \"" . $post['content'] . "\";\n";
		$configText .= "\$mtwCFG['frontpage'] = \"" . $post['frontpage'] . "\";\n";
		$configText .= "\$mtwCFG['menus'] = \"" . $post['menus'] . "\";\n";
		$configText .= "\$mtwCFG['modules'] = \"" . $post['modules'] . "\";\n";
		$configText .= "\$mtwCFG['polls'] = \"" . $post['polls'] . "\";\n";
		$configText .= "\$mtwCFG['weblinks'] = \"" . $post['weblinks'] . "\";\n";
		$configText .= "\$mtwCFG['contacts'] = \"" . $post['contacts'] . "\";\n";
		$configText .= "?>\n";

		jimport('joomla.filesystem.file');

        $configFile = JPATH_COMPONENT.DS.'mtwmigrator_config.php';

        if (JFile::exists( $configFile )) {
			require_once( $configFile );
        }else{
			JFile::copy( $configFile . '.orig', $configFile );
		}

		$return = JFile::write($configFile, $configText);

		return $return;

	}


}
?>
