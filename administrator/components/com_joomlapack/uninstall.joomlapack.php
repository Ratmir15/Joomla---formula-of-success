<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (C) 2009 JoomlaPack Developer. All rights reserved.
 *
 * Modelled after the work done by RocketWerx
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class Status {
	var $STATUS_FAIL = 'Failed';
	var $STATUS_SUCCESS = 'Success';
	var $infomsg = array();
	var $errmsg = array();
	var $status;
}

$db =& JFactory::getDBO();
$install_status = array();

// Uninstall mod_jpadmin
$db->setQuery("SELECT ".$db->nameQuote('id')." FROM ".$db->nameQuote('#__modules').
	"WHERE ".$db->nameQuote('module')." = ".$db->Quote('mod_jpadmin'));
$jpmodule = $db->loadObject();
$module_installer = new JInstaller;
$status = new Status();
$status->status = $status->STATUS_FAIL;
if($module_installer->uninstall('module', $jpmodule->id))
{
	$status->status = $status->STATUS_SUCCESS;
}
$install_status['mod_jpadmin'] = $status;

function com_uninstall()
{
	echo( "JoomlaPack has been successfully uninstalled." );
}

?>
<h1>JoomlaPack Uninstallation</h1>
<table class="adminlist">
	<thead>
		<tr>
			<th class="title"><?php echo JText::_('Sub Component'); ?></th>
			<th width="60%"><?php echo JText::_('Status'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
	</tfoot>
	<tbody>
	<?php
	$i=0;
	foreach ( $install_status as $component => $status ) {?>
		<tr class="row<?php echo $i; ?>">
			<td class="key"><?php echo $component; ?></td>
			<td><?php echo ($status->status == $status->STATUS_SUCCESS)? '<strong>'.JText::_('Uninstalled').'</strong>' : '<em>'.JText::_('NOT Uninstalled').'</em>'?>
			<?php if (count($status->errmsg) > 0 ) {
				foreach ( $status->errmsg as $errmsg ) {
					echo '<br/>Error: ' . $errmsg;
				}
			} ?> <?php if (count($status->infomsg) > 0 ) {
				foreach ( $status->infomsg as $infomsg ) {
					echo '<br/>Info: ' . $infomsg;
				}
			} ?></td>
		</tr>
		<?php
		if ($i=0){ $i=1;} else {$i = 0;};
	}?>

	</tbody>
</table>
