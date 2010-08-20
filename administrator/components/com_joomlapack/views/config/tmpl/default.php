<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 *
 * The main page of the JoomlaPack component is where all the fun takes place :)
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.html.pane');
$tabs =& JPane::getInstance('sliders');

jpimport('helpers.sajax', true);
sajax_init();
sajax_force_page_ajax();
sajax_export('getDefaultOutputDirectory','testFTPConnection');

// Modal dialog setup
$params = array('size'=>array('x'=>600, 'y'=>380));
JHTML::_('behavior.modal', 'a.modal', $params);

?>
<div id="jpcontainer"><script language="JavaScript"
	type="text/javascript">
/*
 * (S)AJAX Library code
 */
 
<?php sajax_show_javascript(); ?>
 
sajax_fail_handle = SAJAXTrap;

function SAJAXTrap( myData ) {
	alert('Invalid AJAX reponse: ' + myData);
}

function getDefaultOutputDirectory()
{
	x_getDefaultOutputDirectory( getDefaultOutputDirectory_cb );
}

function getDefaultOutputDirectory_cb( myRet )
{
	document.getElementById("outdir").value = myRet;
}

function testFTPConnection()
{
	var host	= document.getElementById("df_host").value;
	var port	= document.getElementById("df_port").value;
	var user	= document.getElementById("df_user").value;
	var pass	= document.getElementById("df_pass").value;
	var initdir	= document.getElementById("df_initdir").value;
	var passive	= document.getElementById("var[df_passive]1").checked ? '1' : '0';
	var usessl	= document.getElementById("var[df_usessl]1").checked ? '1' : '0';
	
	x_testFTPConnection(host, port, user, pass, initdir, usessl, passive, testFTPConnection_cb);
}

function testFTPConnection_cb( myRet )
{
	if(myRet == true)
	{
		alert('<?php echo JText::_('MULTIDB_CHECK_OK') ?>');
	}
	else
	{
		alert(myRet);
	}
}
</script>

<form name="adminForm" id="adminForm" method="post"
	action="<?php echo JURI::base(); ?>index.php"><input type="hidden"
	name="option" value="com_joomlapack" /> <input type="hidden"
	name="view" value="config" /> <input type="hidden" name="task" value="" />
<input type="hidden" name="var[settingsmode]" id="settingsmode"
	value="custom" />

<h1><?php echo JText::_('CONFIG_GROUP_COMMON') ?></h1>

<?php echo $tabs->startPane('jpconfigcommon'); ?> <?php echo $tabs->startPanel( JText::_('CONFIG_HEADER_BASIC_COMMON'), 'jpconfigcommonbasic' ); ?>
<table cellpadding="4" cellspacing="0" border="0" width="95%"
	class="adminform">
	<tr align="center" valign="middle">
		<th width="20%">&nbsp;</th>
		<th width="20%"><?php echo JText::_('CONFIG_OPTION'); ?></th>
		<th width="60%"><?php echo JText::_('CONFIG_CURSETTINGS'); ?></th>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td><?php echo JText::_('CONFIG_LABEL_OUTPUTDIRECTORY'); ?></td>
		<td><input type="text" name="var[OutputDirectory]" id="outdir"
			size="40" value="<?php echo $this->OutputDirectory; ?>" /> <input
			type="button"
			value="<?php echo JText::_('CONFIG_ACTION_DEFAULTDIR'); ?>"
			onclick="getDefaultOutputDirectory();" /> <span
			style="margin: 2px; padding: 2px 4px; height: 18px; border: thin solid silver; background-color: #eeeeee; font-size: smaller;">
		<a style="color: black; text-decoration: none" class="modal"
			title="<?php echo JText::_('CONFIG_LABEL_BROWSE') ?>"
			href="<?php echo JURI::base() ?>index.php?option=com_joomlapack&amp;view=browser&amp;format=raw"
			rel="{handler: 'iframe', size: {x: 620, y: 400}}"><?php echo JText::_('CONFIG_LABEL_BROWSE') ?></a>
		</span></td>
	</tr>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_MINAUTHLEVEL', 'authlevel', $this->authlevel, $this->authlist); ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_ALTCUBESTORAGE', 'cubeinfile', $this->cubeinfile); ?>
</table>
<?php echo $tabs->endPanel(); ?> <?php echo $tabs->startPanel( JText::_('CONFIG_HEADER_FRONTEND'), 'jpconfigcommonfeb' ); ?>
<table cellpadding="4" cellspacing="0" border="0" width="95%"
	class="adminform">
	<tr align="center" valign="middle">
		<th width="20%">&nbsp;</th>
		<th width="20%"><?php echo JText::_('CONFIG_OPTION'); ?></th>
		<th width="60%"><?php echo JText::_('CONFIG_CURSETTINGS'); ?></th>
	</tr>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_FEBENABLE', 'enableFrontend', $this->enableFrontend); ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_FRONTENDEMAIL2', 'frontendemail', $this->frontendemail); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_ARBITRARYFEEMAIL', 'arbitraryfeemail', $this->arbitraryfeemail); ?>
	<?php JoomlapackHelperConfig::renderPasswordBoxRow('CONFIG_LABEL_SECRETWORD', 'secretWord', $this->secretWord); ?>
	<tr align="center" valign="middle">
		<td>&nbsp;</td>
		<td colspan="2"><?php echo JText::_('CONFIG_INFO_FEBURL'); ?><br />
		<tt><?php echo JURI::root().'index2.php?option=com_joomlapack&view=backup&key=<u>secret_key</u>&profile=<u>profile_id</u>&format=raw' ?></tt>
		</td>
	</tr>
</table>
<?php echo $tabs->endPanel(); ?> <?php echo $tabs->endPane(); ?>

<h1><?php echo JText::_('CONFIG_GROUP_PROFILE') ?></h1>
<?php echo $tabs->startPane('jpconfigprofile'); ?> <?php echo $tabs->startPanel( JText::_('CONFIG_HEADER_BASIC'), 'jpconfigprofbasic' ); ?>
<table cellpadding="4" cellspacing="0" border="0" width="95%"
	class="adminform">
	<tr align="center" valign="middle">
		<th width="20%">&nbsp;</th>
		<th width="20%"><?php echo JText::_('CONFIG_OPTION'); ?></th>
		<th width="60%"><?php echo JText::_('CONFIG_CURSETTINGS'); ?></th>
	</tr>
	<?php if(JPSPECIALEDITION): ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_BACKUPTYPE', 'BackupType', $this->BackupType, $this->backuptypelist); ?>
	<?php else: ?>
	<input type="hidden" name="var[BackupType]" id="varBackupType"
		value="full" />
		<?php endif; ?>
		<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_ARCHIVENAME', 'TarNameTemplate', $this->TarNameTemplate); ?>
		<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_LOGLEVEL', 'logLevel', $this->logLevel, $this->loglevellist); ?>
</table>
<?php echo $tabs->endPanel(); ?> <?php echo $tabs->startPanel( JText::_('CONFIG_HEADER_ADVANCED'), 'jpconfigprofadvanced' ); ?>
<table cellpadding="4" cellspacing="0" border="0" width="95%"
	class="adminform">
	<tr align="center" valign="middle">
		<th width="20%">&nbsp;</th>
		<th width="20%"><?php echo JText::_('CONFIG_OPTION'); ?></th>
		<th width="60%"><?php echo JText::_('CONFIG_CURSETTINGS'); ?></th>
	</tr>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_SQLCOMPAT', 'MySQLCompat', $this->MySQLCompat, $this->sqlcompatlist); ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_DBALGO', 'dbAlgorithm', $this->dbAlgorithm, $this->algolist); ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_PACKALGO', 'packAlgorithm', $this->packAlgorithm, $this->algolist); ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_LISTERENGINE', 'listerengine', $this->listerengine, $this->listerlist); ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_DBDUMPENGINE', 'dbdumpengine', $this->dbdumpengine, $this->dumperlist); ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_PACKERENGINE', 'packerengine', $this->packerengine, $this->archiverlist); ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_INSTALLER', 'InstallerPackage', $this->InstallerPackage, $this->installerlist); ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_BACKUPMETHOD', 'backupMethod', $this->backupMethod, $this->backupmethodlist); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_MINEXECTIME', 'minexectime', $this->minexectime); ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_SIZEQUOTAS', 'enableSizeQuotas', $this->enableSizeQuotas); ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_COUNTQUOTAS', 'enableCountQuotas', $this->enableCountQuotas); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_SIZEQUOTA', 'sizeQuota', $this->sizeQuota); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_COUNTQUOTA', 'countQuota', $this->countQuota); ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_KEEPALIVE', 'enableMySQLKeepalive', $this->enableMySQLKeepalive); ?>
	<?php if(JPSPECIALEDITION) JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_GZIPBINARY', 'gzipbinary', $this->gzipbinary); ?>
	<?php if(JPSPECIALEDITION) JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_EFFVFOLDER', 'effvfolder', $this->effvfolder); ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_DEREFERENCESYMLINKS', 'dereferencesymlinks', $this->dereferencesymlinks); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_SPLITPARTSIZE', 'splitpartsize', $this->splitpartsize); ?>
</table>
<?php echo $tabs->endPanel(); ?> <?php echo $tabs->startPanel( JText::_('CONFIG_HEADER_MAGIC'), 'jpconfigprofmagic' ); ?>
<table cellpadding="4" cellspacing="0" border="0" width="95%"
	class="adminform">
	<tr align="center" valign="middle">
		<th width="20%">&nbsp;</th>
		<th width="20%"><?php echo JText::_('CONFIG_OPTION'); ?></th>
		<th width="60%"><?php echo JText::_('CONFIG_CURSETTINGS'); ?></th>
	</tr>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_MNROWSPERSTEP', 'mnRowsPerStep', $this->mnRowsPerStep); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_MNMAXFRAGMENTSIZE', 'mnMaxFragmentSize', $this->mnMaxFragmentSize); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_MNMAXFRAGMENTFILES', 'mnMaxFragmentFiles', $this->mnMaxFragmentFiles); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_MNARCHIVERCHUNK', 'mnArchiverChunk', $this->mnArchiverChunk); ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_MNZIPFORCEOPEN', 'mnZIPForceOpen', $this->mnZIPForceOpen); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_MNZIPCOMPRESSIONTHRESHOLD', 'mnZIPCompressionThreshold', $this->mnZIPCompressionThreshold); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_MNZIPDIRREADCHUNK', 'mnZIPDirReadChunk', $this->mnZIPDirReadChunk); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_MNMAXEXECTIMEALLOWED', 'mnMaxExecTimeAllowed', $this->mnMaxExecTimeAllowed); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_MNMINIMUMEXECTIME', 'mnMinimumExectime', $this->mnMinimumExectime); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_MNEXECTIMEBIASPERCENT', 'mnExectimeBiasPercent', $this->mnExectimeBiasPercent); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_MNMAXOPSPERSTEP', 'mnMaxOpsPerStep', $this->mnMaxOpsPerStep); ?>
</table>
	<?php echo $tabs->endPanel(); ?> <?php if(JPSPECIALEDITION): ?> <?php echo $tabs->startPanel( JText::_('CONFIG_MYSQLDUMP'), 'jpconfigprofmsd' ); ?>
<table cellpadding="4" cellspacing="0" border="0" width="95%"
	class="adminform">
	<tr align="center" valign="middle">
		<th width="20%">&nbsp;</th>
		<th width="20%"><?php echo JText::_('CONFIG_OPTION'); ?></th>
		<th width="60%"><?php echo JText::_('CONFIG_CURSETTINGS'); ?></th>
	</tr>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_MYSQLDUMP_PATH', 'mysqldumpPath', $this->mysqldumpPath); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_MYSQLDUMP_DATACHUNK', 'mnMSDDataChunk', $this->mnMSDDataChunk); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_MYSQLDUMP_MAXQUERYLINES', 'mnMSDMaxQueryLines', $this->mnMSDMaxQueryLines); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_MYSQLDUMP_LINESPERSESSION', 'mnMSDLinesPerSession', $this->mnMSDLinesPerSession); ?>
</table>
	<?php echo $tabs->endPanel(); ?> <?php endif; ?> <?php if(JPSPECIALEDITION): ?>
	<?php echo $tabs->startPanel( JText::_('CONFIG_DIRECTFTP'), 'jpconfigprodirectftp' ); ?>
<table cellpadding="4" cellspacing="0" border="0" width="95%"
	class="adminform">
	<tr align="center" valign="middle">
		<th width="20%">&nbsp;</th>
		<th width="20%"><?php echo JText::_('CONFIG_OPTION'); ?></th>
		<th width="60%"><?php echo JText::_('CONFIG_CURSETTINGS'); ?></th>
	</tr>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_DIRECTFTP_HOST', 'df_host', $this->df_host); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_DIRECTFTP_PORT', 'df_port', $this->df_port); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_DIRECTFTP_USER', 'df_user', $this->df_user); ?>
	<?php JoomlapackHelperConfig::renderPasswordBoxRow('CONFIG_LABEL_DIRECTFTP_PASS', 'df_pass', $this->df_pass); ?>
	<?php JoomlapackHelperConfig::renderEditBoxRow('CONFIG_LABEL_DIRECTFTP_INITDIR', 'df_initdir', $this->df_initdir); ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_DIRECTFTP_PASSIVE', 'df_passive', $this->df_passive); ?>
	<?php JoomlapackHelperConfig::renderSelectionBoxRow('CONFIG_LABEL_DIRECTFTP_USESSL', 'df_usessl', $this->df_usessl); ?>
	<tr>
		<td>&nbsp;</td>
		<td><input type="button" value="<?php echo JText::_('CONFIG_LABEL_DIRECTFTP_TEST') ?>" onclick="testFTPConnection();" /></td>		
	</tr>
</table>
	<?php echo $tabs->endPanel(); ?> <?php endif; ?> <?php echo $tabs->endPane(); ?>

</form>

	<?php echo JoomlapackHelperUtils::getFooter(); ?></div>
