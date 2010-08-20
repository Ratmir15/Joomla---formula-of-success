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
<div id="jpcontainer">
<p><?php echo JText::_('STATS_LABEL_RESTOREINTRO'); ?></p>
<p
	style="background-color: #ffffcc; padding: 1em; margin: 1em; border: thin solid red">
<strong style="font-size: larger;"><?php echo JText::_('STATS_LABEL_RESTOREWARNING') ?></strong><br />
<?php echo JText::_('STATS_LABEL_RESTOREPASS1') ?><br />
<tt
	style="font-size: large; background-color: yellow; border: thin solid green;"><?php echo $this->password; ?></tt><br />
<?php echo JText::_('STATS_LABEL_RESTOREPASS2') ?></p>
<p><a href="<?php echo $this->link ?>"><?php echo JText::_('STATS_LABEL_RESTORELINK'); ?></a>
</p>
</div>
