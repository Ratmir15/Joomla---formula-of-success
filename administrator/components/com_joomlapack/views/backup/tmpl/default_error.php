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
<h1><?php echo JText::_('BACKUP_HEADER_BACKUPFAILED'); ?></h1>
<div class="errorframe">
<p><?php echo JText::_('BACKUP_TEXT_BACKUPFAILED') ?></p>
<p class="errormessage"><?php echo $this->errormessage; ?></p>
<p><?php echo JText::_('BACKUP_TEXT_READLOGFAIL') ?></p>
<p><?php echo JText::sprintf('BACKUP_TEXT_RTFMFIRST', 'http://www.joomlapack.net/forum') ?>


<p>&nbsp;</p>
<a
	href="<?php echo JURI::base() ?>index.php?option=com_joomlapack&view=log"
	class="jpbutton"> <img
	src="<?php JURI::base() ?>components/com_joomlapack/assets/images/log.png"
	border="0" /> <span><?php echo JText::_('VIEWLOG'); ?></span> </a></div>
