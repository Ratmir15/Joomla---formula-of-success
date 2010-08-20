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

if( empty($this->profile) )
{
	$id = 0;
	$description = '';
}
else
{
	$id = $this->profile->id;
	$description = $this->profile->description;
}
?>
<div id="jpcontainer">
<form action="<?php echo JURI::base(); ?>index.php" method="post"
	name="adminForm" id="adminForm"><input type="hidden" name="option"
	value="com_joomlapack" /> <input type="hidden" name="view"
	value="profiles" /> <input type="hidden" name="boxchecked"
	id="boxchecked" value="0" /> <input type="hidden" name="task" id="task"
	value="" /> <input type="hidden" name="id" id="id"
	value="<?php echo $id; ?>" />
<table>
	<tr>
		<td><?php echo JHTML::_('tooltip', JText::_('PROFILE_LABEL_DESCRIPTION_TOOLTIP'), '', '', JText::_('PROFILE_LABEL_DESCRIPTION')) ?></td>
		<td><input type="text" name="description" id="description"
			value="<?php echo $description; ?>" /></td>
	</tr>
</table>
</form>

<?php echo JoomlapackHelperUtils::getFooter(); ?></div>
