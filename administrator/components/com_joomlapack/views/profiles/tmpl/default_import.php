<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 */

defined('_JEXEC') or die('Restricted access');

// Include tooltip support
jimport('joomla.html.html');
JHTML::_('behavior.tooltip');

?>
<div id="jpcontainer">
<form enctype="multipart/form-data"
	action="<?php echo JURI::base(); ?>index.php" method="post"
	name="adminForm" id="adminForm"><input type="hidden" name="option"
	value="com_joomlapack" /> <input type="hidden" name="view"
	value="profiles" /> <input type="hidden" name="task" id="task"
	value="doimport" />
<p><?php echo JText::_('PROFILE_TEXT_IMPORT'); ?></p>
<table>
	<tr>
		<td><?php echo JHTML::_('tooltip', JText::_('PROFILE_LABEL_DESCRIPTION_TOOLTIP'), '', '', JText::_('PROFILE_LABEL_DESCRIPTION')) ?></td>
		<td><input type="text" name="description" id="description"
			value="<?php echo $description; ?>" size="50" /></td>
	</tr>
	<tr>
		<td><?php echo JText::_('PROFILE_LABEL_XMLFILE') ?></td>
		<td><input name="userfile" type="file" /></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td><input type="submit" /></td>
	</tr>
</table>
</form>
</div>
