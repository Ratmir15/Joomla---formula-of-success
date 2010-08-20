<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 */

defined('_JEXEC') or die('Restricted access');

// Delegate page rendering to the helper class
jpimport('helpers.dbef', true);
?>
<div id="jpcontainer"><?php JoomlapackHelperDbef::renderJavaScript(); ?>

<table border="0" cellspacing="10" width="100%">
	<tr>
		<td><a
			href="<?php echo JURI::base(); ?>index.php?option=com_joomlapack&view=dbef&task=reset">
			<?php echo JText::_('DBEF_LABEL_RESET'); ?> </a> - <a
			href="<?php echo JURI::base(); ?>index.php?option=com_joomlapack&view=dbef&task=filternonjoomla">
			<?php echo JText::_('DBEF_LABEL_FILTERNONJOOMLA'); ?> </a></td>
	</tr>
	<tr>
		<td id="tablepane" valign="top"><?php echo JoomlapackHelperDbef::getTablePane(); ?>
		</td>
	</tr>
</table>
</div>
