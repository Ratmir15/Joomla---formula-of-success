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

?>
<h1><?php echo JText::_('BACKUP_HEADER_BACKUPFINISHED') ?></h1>
<div style="height: 32px"><img
	src="<?php JURI::base() ?>components/com_joomlapack/assets/images/ok_big.png"
	align="left" style="margin-right: 1em" />
<p><?php echo JText::_('BACKUP_TEXT_CONGRATS') ?></p>
</div>

<table border="0">
	<tr>
		<td><a
			href="<?php echo JURI::base() ?>index.php?option=com_joomlapack&view=buadmin"
			class="jpbutton"> <img
			src="<?php JURI::base() ?>components/com_joomlapack/assets/images/bufa.png"
			border="0" /> <span><?php echo JText::_('BUADMIN'); ?></span> </a></td>
		<td><a
			href="<?php echo JURI::base() ?>index.php?option=com_joomlapack&view=log"
			class="jpbutton"> <img
			src="<?php JURI::base() ?>components/com_joomlapack/assets/images/log.png"
			border="0" /> <span><?php echo JText::_('VIEWLOG'); ?></span> </a></td>
	</tr>
</table>
