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

jimport('joomla.application.component.view');
jimport('joomla.filesystem.file');

/**
 * Hellos View
 *
 * @package    Joomla.Tutorials
 * @subpackage Components
 */
class mtwMigratorViewConfig extends JView
{
	/**
	 * Hellos view display method
	 * @return void
	 **/
	function display($tpl = null)
	{
		JToolBarHelper::title(   JText::_( 'Configuration' ), 'config.png' );
		//JToolBarHelper::deleteList();
		//JToolBarHelper::editListX();
		//JToolBarHelper::addNewX();
		JToolBarHelper::apply();
		JToolBarHelper::save();
		JToolBarHelper::cancel();
		JToolBarHelper::spacer();

		$configFile = JPATH_COMPONENT.DS.'mtwmigrator_config.php';
		if (JFile::exists( $configFile )) {
			include( $configFile );
		}

		$db =& JFactory::getDBO();

		// Community Builder
		$query = "SELECT `option` FROM #__components WHERE `option` = 'com_comprofiler' LIMIT 1";
		$db->setQuery( $query );
		$ext['cb'] = $db->loadResult();

		if ($ext['cb'] == "com_comprofiler") {
			$lists['ext_cb'] = JHTML::_('select.booleanlist', 'ext_cb', '', $mtwCFG['ext_cb']);
		}else{
			$lists['ext_cb'] = JHTML::_('select.booleanlist', 'ext_cb', 'disabled', 0);
		}

		if ($mtwCFG['ext_cb'] == 1) {
			$lists['groups'] = JHTML::_('select.booleanlist', 'groupsD', 'disabled', 1);
			$lists['users'] = JHTML::_('select.booleanlist', 'usersD', 'disabled', 1);	
		}else{
			$lists['groups'] = JHTML::_('select.booleanlist', 'groups', '', $mtwCFG['groups']);
			$lists['users'] = JHTML::_('select.booleanlist', 'users', '', $mtwCFG['users']);
		}

        // Virtuemart
        $query = "SELECT `option` FROM #__components WHERE `option` = 'com_virtuemart' LIMIT 1";
        $db->setQuery( $query );
        $ext['vm'] = $db->loadResult();

        if ($ext['vm'] == "com_virtuemart") {
            $lists['ext_vm'] = JHTML::_('select.booleanlist', 'ext_vm', '', $mtwCFG['ext_vm']);
        }else{
            $lists['ext_vm'] = JHTML::_('select.booleanlist', 'ext_vm', 'disabled', 0);
        }

        // JosComment
        $query = "SELECT `option` FROM #__components WHERE `option` = 'com_jomcomment' LIMIT 1";
        $db->setQuery( $query );
        $ext['jc'] = $db->loadResult();

        if ($ext['jc'] == "com_jomcomment") {
            $lists['ext_jc'] = JHTML::_('select.booleanlist', 'ext_jc', '', $mtwCFG['ext_jc']);
        }else{
            $lists['ext_jc'] = JHTML::_('select.booleanlist', 'ext_jc', 'disabled', 0);
        }

        // DocMan
        $query = "SELECT `option` FROM #__components WHERE `option` = 'com_docman' LIMIT 1";
        $db->setQuery( $query );
        $ext['dm'] = $db->loadResult();

        if ($ext['dm'] == "com_docman") {
            $lists['ext_dm'] = JHTML::_('select.booleanlist', 'ext_dm', '', $mtwCFG['ext_dm']);
        }else{
            $lists['ext_dm'] = JHTML::_('select.booleanlist', 'ext_dm', 'disabled', 0);
        }

        // FacileForms
        $query = "SELECT `option` FROM #__components WHERE `option` = 'com_facileforms' LIMIT 1";
        $db->setQuery( $query );
        $ext['ff'] = $db->loadResult();

        if ($ext['ff'] == "com_facileforms") {
            $lists['ext_ff'] = JHTML::_('select.booleanlist', 'ext_ff', '', $mtwCFG['ext_ff']);
        }else{
            $lists['ext_ff'] = JHTML::_('select.booleanlist', 'ext_ff', 'disabled', 0);
        }		

        // Artio JoomSEF
        $query = "SELECT `option` FROM #__components WHERE `option` = 'com_sef' LIMIT 1";
        $db->setQuery( $query );
        $ext['aj'] = $db->loadResult();

        if ($ext['aj'] == "com_sef") {
            $lists['ext_aj'] = JHTML::_('select.booleanlist', 'ext_aj', '', $mtwCFG['ext_aj']);
        }else{
            $lists['ext_aj'] = JHTML::_('select.booleanlist', 'ext_aj', 'disabled', 0);
        }

        // Fireboard
        $query = "SELECT `option` FROM #__components WHERE `option` = 'com_fireboard' LIMIT 1";
        $db->setQuery( $query );
        $ext['fb'] = $db->loadResult();

        if ($ext['fb'] == "com_fireboard") {
            $lists['ext_fb'] = JHTML::_('select.booleanlist', 'ext_fb', '', $mtwCFG['ext_fb']);
        }else{
            $lists['ext_fb'] = JHTML::_('select.booleanlist', 'ext_fb', 'disabled', 0);
        }

        // Kunena
        $query = "SELECT `option` FROM #__components WHERE `option` = 'com_kunena' LIMIT 1";
        $db->setQuery( $query );
        $ext['ku'] = $db->loadResult();

        if ($ext['ku'] == "com_kunena") {
            $lists['ext_ku'] = JHTML::_('select.booleanlist', 'ext_ku', '', $mtwCFG['ext_ku']);
        }else{
            $lists['ext_ku'] = JHTML::_('select.booleanlist', 'ext_ku', 'disabled', 0);
        }

        // Letterman
        $query = "SELECT `option` FROM #__components WHERE `option` = 'com_letterman' LIMIT 1";
        $db->setQuery( $query );
        $ext['lm'] = $db->loadResult();

        if ($ext['lm'] == "com_letterman") {
            $lists['ext_lm'] = JHTML::_('select.booleanlist', 'ext_lm', '', $mtwCFG['ext_lm']);
        }else{
            $lists['ext_lm'] = JHTML::_('select.booleanlist', 'ext_lm', 'disabled', 0);
        }

		$lists['backup'] = JHTML::_('select.booleanlist', 'backup', '', $mtwCFG['backup']);
		$lists['sections'] = JHTML::_('select.booleanlist', 'sections', '', $mtwCFG['sections']);
		$lists['categories'] = JHTML::_('select.booleanlist', 'categories', '', $mtwCFG['categories']);
		$lists['content'] = JHTML::_('select.booleanlist', 'content', '', $mtwCFG['content']);
		$lists['frontpage'] = JHTML::_('select.booleanlist', 'frontpage', '', $mtwCFG['frontpage']);
		$lists['menus'] = JHTML::_('select.booleanlist', 'menus', '', $mtwCFG['menus']);
		$lists['modules'] = JHTML::_('select.booleanlist', 'modules', '', $mtwCFG['modules']);
		$lists['polls'] = JHTML::_('select.booleanlist', 'polls', '', $mtwCFG['polls']);
		$lists['weblinks'] = JHTML::_('select.booleanlist', 'weblinks', '', $mtwCFG['weblinks']);
		$lists['contacts'] = JHTML::_('select.booleanlist', 'contacts', '', $mtwCFG['contacts']);

		$this->assignRef('lists', $lists);
		$this->assignRef('items', $mtwCFG);
		$this->assignRef('ext', $ext);

		parent::display($tpl);
	}
}
