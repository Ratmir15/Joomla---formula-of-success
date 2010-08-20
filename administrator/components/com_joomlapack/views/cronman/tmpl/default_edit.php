<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 2.3
 */

defined('_JEXEC') or die('Restricted access');

// Include tooltip support
jimport('joomla.html.html');
JHTML::_('behavior.tooltip');

if( is_null($this->definition) )
{
	$this->definition->id = 0;
	$this->definition->verbose = false;
	$this->definition->siteurl = JURI::root();
	$this->definition->profile = 1;
	$this->definition->secret = $this->secret;
	$this->definition->postop = 'none';
	$this->definition->email = '';
	$this->definition->ftphost = '';
	$this->definition->ftpport = '21';
	$this->definition->ftpuser = '';
	$this->definition->ftppass = '';
	$this->definition->ftpdir = 0;
}

$optprofiles = array();
foreach($this->profiles as $profile_id => $profile_description)
{
	$optprofiles[] = JHTML::_('select.option', $profile_id, $profile_description);
}

function echoLabel($labelkey, $tooltipkey = null)
{
	if(is_null($tooltipkey))
	{
		echo JText::_($labelkey);
	}
	else
	{
		echo JHTML::_('tooltip', JText::_($tooltipkey), '', '', JText::_($labelkey));
	}
}

// Load SAJAX library
jpimport('helpers.sajax', true);
sajax_init();
sajax_force_page_ajax();
sajax_export('getDefaultURL','getDefaultSecret', 'testFTP');

?>
<script type="text/javascript" language="JavaScript">
	/*
	 * (S)AJAX Library code
	 */
	 
	<?php sajax_show_javascript(); ?>
	 
	sajax_fail_handle = SAJAXTrap;
	
	function SAJAXTrap( myData ) {
		alert('Invalid AJAX reponse: ' + myData);
	}

	function getDefaultURL()
	{
		x_getDefaultURL(getDefaultURL_cb);
	}
	
	function getDefaultURL_cb(myRet)
	{
		document.getElementById('siteurl').value = myRet;
	}
	
	function getDefaultSecret()
	{
		x_getDefaultSecret(getDefaultSecret_cb);
	}
	
	function getDefaultSecret_cb(myRet)
	{
		document.getElementById('secret').value = myRet;
	}
	
	function testFTP()
	{
		var host	= document.getElementById("ftphost").value;
		var port	= document.getElementById("ftpport").value;
		var user	= document.getElementById("ftpuser").value;
		var pass	= document.getElementById("ftppass").value;
		var initdir	= document.getElementById("ftpdir").value;
		var passive	= '1';
		var usessl	= '0';
		
		x_testFTP(host, port, user, pass, initdir, usessl, passive, testFTP_cb);
	}
	
	function testFTP_cb(myRet)
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

<div id="jpcontainer">
<form action="<?php echo JURI::base(); ?>index.php" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_joomlapack" />
	<input type="hidden" name="view" value="cronman" />
	<input type="hidden" name="task" id="task" value="" />
	<input type="hidden" name="var[id]" id="id" value="<?php echo $this->definition->id; ?>" />
<table>
	<thead>
	<tr align="center" valign="middle">
		<th width="30%"><?php echo JText::_('CONFIG_OPTION'); ?></th>
		<th width="70%"><?php echo JText::_('CONFIG_CURSETTINGS'); ?></th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td><?php echolabel('CRON_LABEL_VERBOSE')?></td>
		<td>
			<?php echo JHTML::_('select.booleanlist',"var[verbose]",'',$this->definition->verbose); ?>
		</td>
	</tr>
	<tr>
		<td><?php echolabel('CRON_LABEL_SITEURL')?></td>
		<td>
			<input type="text" name="var[siteurl]" id="siteurl" value="<?php echo $this->definition->siteurl ?>" size="30" />
			<input type="button" name="defaulturl" id="defaulturl" value="<?php echo JText::_('CRON_BUTTON_USEDEFAULT') ?>" onclick="getDefaultURL();" />
		</td>
	</tr>
	<tr>
		<td><?php echolabel('CRON_LABEL_PROFILE')?></td>
		<td>
			<?php echo JHTML::_('select.genericlist', $optprofiles, "var[profile]",'', 'value', 'text', $this->definition->profile); ?>
		</td>
	</tr>
	<tr>
		<td><?php echolabel('CRON_LABEL_SECRETKEY')?></td>
		<td>
			<input type="password" name="var[secret]" id="secret" value="<?php echo $this->definition->secret ?>" size="30" />
			<input type="button" name="defaultsecret" id="defaultsecret" value="<?php echo JText::_('CRON_BUTTON_USEDEFAULT') ?>" onclick="getDefaultSecret();" />
		</td>
	</tr>
	<tr>
		<td><?php echolabel('CRON_LABEL_POSTOP')?></td>
		<td>
			<?php echo JHTML::_('select.genericlist', $this->postops, "var[postop]",'', 'value', 'text', $this->definition->postop); ?>
		</td>
	</tr>
	<tr>
		<td><?php echolabel('CRON_LABEL_EMAIL')?></td>
		<td>
			<input type="text" name="var[email]" id="email" value="<?php echo $this->definition->email ?>" size="30" />
		</td>
	</tr>
	<tr>
		<td><?php echolabel('CRON_LABEL_HOST')?></td>
		<td>
			<input type="text" name="var[ftphost]" id="ftphost" value="<?php echo $this->definition->ftphost ?>" size="30" />
		</td>
	</tr>
	<tr>
		<td><?php echolabel('CRON_LABEL_PORT')?></td>
		<td>
			<input type="text" name="var[ftpport]" id="ftpport" value="<?php echo $this->definition->ftpport ?>" size="30" />
		</td>
	</tr>
	<tr>
		<td><?php echolabel('CRON_LABEL_USER')?></td>
		<td>
			<input type="text" name="var[ftpuser]" id="ftpuser" value="<?php echo $this->definition->ftpuser ?>" size="30" />
		</td>
	</tr>
	<tr>
		<td><?php echolabel('CRON_LABEL_PASS')?></td>
		<td>
			<input type="password" name="var[ftppass]" id="ftppass" value="<?php echo $this->definition->ftppass ?>" size="30" />
		</td>
	</tr>
	<tr>
		<td><?php echolabel('CRON_LABEL_DIR')?></td>
		<td>
			<input type="text" name="var[ftpdir]" id="ftpdir" value="<?php echo $this->definition->ftpdir ?>" size="30" />
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<input type="button" name="testftp" value="<?php echo JText::_('CRON_BUTTON_TESTFTP'); ?>" onclick="testFTP()" />
		</td>
	</tr>
	</tbody>
</table>
</form>

<?php echo JoomlapackHelperUtils::getFooter(); ?></div>
