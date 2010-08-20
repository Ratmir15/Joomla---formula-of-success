<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 2.1
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

// Populate values depending on whether this is a new record or an existing record
if(empty($this->eff))
{
	$id = 0;
	$fsdir = '';
}
else
{
	$id = $this->eff->id;
	$fsdir = $this->eff->value;
}

// Load SAJAX library
jpimport('helpers.sajax', true);
sajax_init();
sajax_force_page_ajax();
sajax_export('testdirectory');

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

function testdir()
{
	var fsdir = document.getElementById("fsdir").value;

	x_testdirectory( fsdir, testdir_cb ); 
}

function testdir_cb( myRet )
{
	alert(myRet);
}

function resetdir()
{
	document.getElementById("fsdir").value = "<?php echo addslashes(JPATH_SITE); ?>";
}
</script>

<form name="adminForm" id="adminForm" method="post"
	action="<?php echo JURI::base(); ?>index.php"><input type="hidden"
	name="boxchecked" value="0" /> <input type="hidden" name="task"
	value="<?php echo JRequest::getCmd('task','add'); ?>" /> <input
	type="hidden" name="option" value="com_joomlapack" /> <input
	type="hidden" name="view" value="eff" /> <input type="hidden" name="id"
	value="<?php echo $id; ?>" />

<table width="100%">
	<tr>
		<td><?php echo JHTML::_('tooltip', JText::_('EFF_TIP_FSDIR'), '', '', JText::_('EFF_LABEL_FSDIR')) ?></td>
		<td><input type="text" name="fsdir" id="fsdir"
			value="<?php echo $fsdir ?>" size="60" /></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td><input type="button"
			value="<?php echo JText::_('EFF_BUTTON_CHECK'); ?>"
			onclick="testdir();" /> <input type="button"
			value="<?php echo JText::_('EFF_BUTTON_RESET'); ?>"
			onclick="resetdir();" /></td>
	</tr>
</table>
</form>
</div>
