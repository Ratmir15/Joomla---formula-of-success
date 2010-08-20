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

// Schema modification -- BEGIN

// Add "multipart" column to stats table
$db =& JFactory::getDBO();
$sql = "ALTER TABLE ".$db->nameQuote('#__jp_stats')." ADD ".
	$db->nameQuote('multipart')." INT NOT NULL DEFAULT 0 AFTER ".$db->nameQuote('absolute_path');
$db->setQuery($sql);
$db->query(); // This might fail with error 1060, if the column already exists, but I don't really care!

// Schema modification -- END

class Status {
	var $STATUS_FAIL = 'Failed';
	var $STATUS_SUCCESS = 'Success';
	var $infomsg = array();
	var $errmsg = array();
	var $status;
}

$db =& JFactory::getDBO();
$install_status = array();

// Install mod_jpadmin
$module_installer = new JInstaller;
$status = new Status();
$status->status = $status->STATUS_FAIL;
if($module_installer->install(dirname(__FILE__).DS.'mod_jpadmin') )
{
	$status->status = $status->STATUS_SUCCESS;
}
$install_status['mod_jpadmin'] = $status;

// Enable mod_jpadmin
$db->setQuery("UPDATE #__modules SET published = 1, position = 'icon' WHERE ".
	"module = 'mod_jpadmin' ");
$db->query();


function com_install() {
	return component_install();
}

function component_install() {
	global $install_status;

	$status = new Status();
	$status->status = $status->STATUS_SUCCESS;
	$status->component = "com_joomlapack";

	$install_status["com_joomlapack"] = $status;
	return true;
}

?>
<h1>JoomlaPack Installation</h1>
<p>Welcome to JoomlaPack!<br />
Before doing anything more, please read the manual, available on-line on
our official site's <a
	href="http://www.joomlapack.net/help-support-documentation/joomlapack-2x-documentation/">Documentation
section</a>. Should you have any questions, comments or need some help,
do not hesitate to post on our <a href="http://forum.joomlapack.net">support
forum</a>.</p>
<p>The next step after installation is taking a look at the component's
<a
	href="<?php echo JURI::base() ?>index.php?option=com_joomlapack&view=configeasy">simplified
configuration</a> or, if you are an advanced user, the <a
	href="<?php echo JURI::base() ?>index.php?option=com_joomlapack&view=config">thorough
(advanced) configuration</a> pages. Once you have checked your
configuration, go ahead and <a
	href="<?php echo JURI::base() ?>index.php?option=com_joomlapack&view=cpanel">apply
inclusion and exclusion filters</a> or skip right through to <a
	href="<?php echo JURI::base() ?>index.php?option=com_joomlapack&view=backup">taking
your first site backup</a>. Remember, you can always get on-line help
for the JoomlaPack page you are currently viewing by clicking on the
help icon in the top left corner of that page.</p>
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
			<td><?php echo ($status->status == $status->STATUS_SUCCESS)? '<strong>'.JText::_('Installed').'</strong>' : '<em>'.JText::_('NOT Installed').'</em>'?>
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
