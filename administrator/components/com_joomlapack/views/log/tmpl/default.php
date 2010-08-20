<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 */

defined('_JEXEC') or die('Restricted access');
?>
<div id="jpcontainer">
<p style="font-size: large;"><blink
	style="color: red; font-weight: bold; font-size: x-large;">&rArr;</blink>
<a
	href="<?php echo JURI::base(); ?>index.php?option=com_joomlapack&view=log&task=download&format=raw">
<?php echo JText::_('LOG_LABEL_DOWNLOAD'); ?> </a> <blink
	style="color: red; font-weight: bold; font-size: x-large;">&lArr;</blink>
</p>
<iframe
	src="<?php echo JURI::base(); ?>index.php?option=com_joomlapack&view=log&task=iframe&format=raw"
	width="90%" height="400px"> </iframe></div>
