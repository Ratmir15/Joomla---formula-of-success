<?php
/**
 * mtwMigrator
 *
 * @author      Matias Aguirre
 * @email       maguirre@matware.com.ar
 * @url         http://www.matware.com.ar/
 * @license             GNU/GPL
 */
defined('_JEXEC') or die();

jimport( 'joomla.application.component.view' );

class mtwMigratorViewHelp extends JView {
	function display($tpl = null)
	{
		JToolBarHelper::title(   JText::_( 'Help' ), 'help_header.png' );

		parent::display($tpl);
	}
}
