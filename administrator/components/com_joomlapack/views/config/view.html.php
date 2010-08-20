<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

// Load framework base classes
jimport('joomla.application.component.view');

/**
 * JoomlaPack Configuration view class
 *
 */
class JoomlapackViewConfig extends JView
{
	function display()
	{
		// Set the toolbar title; add a help button
		JToolBarHelper::title(JText::_('JOOMLAPACK').':: <small><small>'.JText::_('CONFIGURATION').'</small></small>');
		JToolBarHelper::apply();
		JToolBarHelper::save();
		JToolBarHelper::cancel();
		JoomlapackHelperUtils::addLiveHelp('config');
		$document =& JFactory::getDocument();
		$document->addStyleSheet(JURI::base().'components/com_joomlapack/assets/css/joomlapack.css');

		// Load the util helper
		$this->loadHelper('utils');

		// Load the model
		$model =& $this->getModel();

		// Pass on the lists
		$this->assign('backuptypelist',				$model->getBackupTypeList());
		$this->assign('loglevellist',				$model->getLogLevelList() );
		$this->assign('sqlcompatlist',				$model->getSqlCompatList() );
		$this->assign('algolist',					$model->getAlgoList() );
		$this->assign('listerlist',					$model->getFilelistEngineList() );
		$this->assign('dumperlist',					$model->getDatabaseEngineList() );
		$this->assign('archiverlist',				$model->getArchiverEngineList() );
		$this->assign('installerlist',				$model->getInstallerList() );
		$this->assign('backupmethodlist',			$model->getBackupMethodList() );
		$this->assign('authlist',					$model->getAuthLevelList() );

		// Let's pass the data
		// -- Common Basic
		$this->assign('OutputDirectory',			$model->getVar('OutputDirectoryConfig') );
		// -- Common Frontend
		$this->assign('enableFrontend',				$model->getVar('enableFrontend') );
		$this->assign('secretWord',					$model->getVar('secretWord') );
		$this->assign('frontendemail',				$model->getVar('frontendemail') );
		$this->assign('arbitraryfeemail',			$model->getVar('arbitraryfeemail'));
		// -- Basic
		$this->assign('BackupType',					$model->getVar('BackupType') );
		$this->assign('TarNameTemplate',			$model->getVar('TarNameTemplate') );
		$this->assign('logLevel',					$model->getVar('logLevel') );
		$this->assign('authlevel',					$model->getVar('authlevel') );
		$this->assign('cubeinfile',					$model->getVar('cubeinfile') );
		// -- Advanced
		$this->assign('MySQLCompat', 				$model->getVar('MySQLCompat') );
		$this->assign('dbAlgorithm',				$model->getVar('dbAlgorithm') );
		$this->assign('packAlgorithm', 				$model->getVar('packAlgorithm') );
		$this->assign('listerengine', 				$model->getVar('listerengine') );
		$this->assign('dbdumpengine', 				$model->getVar('dbdumpengine') );
		$this->assign('packerengine', 				$model->getVar('packerengine') );
		$this->assign('InstallerPackage',			$model->getVar('InstallerPackage') );
		$this->assign('backupMethod', 				$model->getVar('backupMethod') );
		$this->assign('minexectime',				$model->getVar('minexectime'));
		$this->assign('enableSizeQuotas',			$model->getVar('enableSizeQuotas') );
		$this->assign('enableCountQuotas',			$model->getVar('enableCountQuotas') );
		$this->assign('sizeQuota',					$model->getVar('sizeQuota') );
		$this->assign('countQuota',					$model->getVar('countQuota') );
		$this->assign('enableMySQLKeepalive',		$model->getVar('enableMySQLKeepalive') );
		$this->assign('gzipbinary',					$model->getVar('gzipbinary'));
		$this->assign('effvfolder',					$model->getVar('effvfolder'));
		$this->assign('dereferencesymlinks',		$model->getVar('dereferencesymlinks'));
		$this->assign('splitpartsize',				$model->getVar('splitpartsize'));
		// -- Magic numbers
		$this->assign('mnRowsPerStep', 				$model->getVar('mnRowsPerStep') );
		$this->assign('mnMaxFragmentSize',			$model->getVar('mnMaxFragmentSize') );
		$this->assign('mnMaxFragmentFiles', 		$model->getVar('mnMaxFragmentFiles') );
		$this->assign('mnZIPForceOpen',				$model->getVar('mnZIPForceOpen') );
		$this->assign('mnZIPCompressionThreshold',	$model->getVar('mnZIPCompressionThreshold') );
		$this->assign('mnZIPDirReadChunk',			$model->getVar('mnZIPDirReadChunk') );
		$this->assign('mnMaxExecTimeAllowed',		$model->getVar('mnMaxExecTimeAllowed') );
		$this->assign('mnMinimumExectime',			$model->getVar('mnMinimumExectime') );
		$this->assign('mnExectimeBiasPercent',		$model->getVar('mnExectimeBiasPercent') );
		$this->assign('mnMaxOpsPerStep',			$model->getVar('mnMaxOpsPerStep') );
		$this->assign('mnArchiverChunk',			$model->getVar('mnArchiverChunk') );
		// -- MySQLDump
		$this->assign('mysqldumpPath',				$model->getVar('mysqldumpPath') );
		$this->assign('mnMSDDataChunk',				$model->getVar('mnMSDDataChunk') );
		$this->assign('mnMSDMaxQueryLines',			$model->getVar('mnMSDMaxQueryLines') );
		$this->assign('mnMSDLinesPerSession',		$model->getVar('mnMSDLinesPerSession') );
		// -- DirectFTP
		$this->assign('df_host',					$model->getVar('df_host') );
		$this->assign('df_port',					$model->getVar('df_port') );
		$this->assign('df_user',					$model->getVar('df_user') );
		$this->assign('df_pass',					$model->getVar('df_pass') );
		$this->assign('df_initdir',					$model->getVar('df_initdir') );
		$this->assign('df_usessl',					$model->getVar('df_usessl') );
		$this->assign('df_passive',					$model->getVar('df_passive') );

		// Also load the Configuration HTML helper
		$this->loadHelper('config');
		parent::display();
	}
}