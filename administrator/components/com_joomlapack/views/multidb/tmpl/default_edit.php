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

// Populate values depending on whether this is a new record or an existing record
if(empty($this->multidb))
{
	$id = 0;
	$host = '';
	$port = '';
	$user = '';
	$pass = '';
	$database = '';
}
else
{
	$id = $this->multidb->id;
	$data = unserialize($this->multidb->value);
	$host		= $data['host'];
	$port		= $data['port'];
	$user		= $data['user'];
	$pass		= $data['pass'];
	$database	= $data['database'];
}

// Load SAJAX library
jpimport('helpers.sajax', true);
sajax_init();
sajax_force_page_ajax();
sajax_export('testdatabase');

?>
<div id="jpcontainer"><script type="text/javascript">
/*
 * (S)AJAX Library code
 */
 
<?php sajax_show_javascript(); ?>
 
sajax_fail_handle = SAJAXTrap;

function SAJAXTrap( myData ) {
	alert('Invalid AJAX reponse: ' + myData);
}

function testdb()
{
	var host = document.getElementById("host").value;
	var port = document.getElementById("port").value;
	var username = document.getElementById("user").value;
	var password = document.getElementById("pass").value;
	var database = document.getElementById("database").value;

	x_testdatabase( host, port, username, password, database, testdb_cb ); 
}

function testdb_cb( myRet )
{
	if( myRet == true )
	{
		alert('<?php echo JText::_('MULTIDB_CHECK_OK'); ?>');
	} else {
		alert('<?php echo JText::_('MULTIDB_CHECK_NOTOK'); ?>');
	}
}
</script>

<form name="adminForm" id="adminForm" method="post"
	action="<?php echo JURI::base(); ?>index.php"><input type="hidden"
	name="boxchecked" value="0" /> <input type="hidden" name="task"
	value="<?php echo JRequest::getCmd('task','add'); ?>" /> <input
	type="hidden" name="option" value="com_joomlapack" /> <input
	type="hidden" name="view" value="multidb" /> <input type="hidden"
	name="id" value="<?php echo $id; ?>" />

<table width="100%">
	<tr>
		<td><?php echo JHTML::_('tooltip', JText::_('MULTIDB_TIP_HOST'), '', '', JText::_('MULTIDB_LABEL_HOST')) ?></td>
		<td><input type="text" name="host" id="host"
			value="<?php echo $host ?>" size="60" /></td>
	</tr>
	<tr>
		<td><?php echo JHTML::_('tooltip', JText::_('MULTIDB_TIP_PORT'), '', '', JText::_('MULTIDB_LABEL_PORT')) ?></td>
		<td><input type="text" name="port" id="port"
			value="<?php echo $port ?>" size="5" /></td>
	</tr>
	<tr>
		<td><?php echo JHTML::_('tooltip', JText::_('MULTIDB_TIP_USERNAME'), '', '', JText::_('MULTIDB_LABEL_USERNAME')) ?></td>
		<td><input type="text" name="user" id="user"
			value="<?php echo $user ?>" size="40" /></td>
	</tr>
	<tr>
		<td><?php echo JHTML::_('tooltip', JText::_('MULTIDB_TIP_PASSWORD'), '', '', JText::_('MULTIDB_LABEL_PASSWORD')) ?></td>
		<td><input type="text" name="pass" id="pass"
			value="<?php echo $pass ?>" size="40" /></td>
	</tr>
	<tr>
		<td><?php echo JHTML::_('tooltip', JText::_('MULTIDB_TIP_DATABASE'), '', '', JText::_('MULTIDB_LABEL_DATABASE')) ?></td>
		<td><input type="text" name="database" id="database"
			value="<?php echo $database ?>" size="40" /></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td><input type="button"
			value="<?php echo JText::_('MULTIDB_BUTTON_CHECK'); ?>"
			onclick="testdb();" /></td>
	</tr>
</table>
</form>
</div>
