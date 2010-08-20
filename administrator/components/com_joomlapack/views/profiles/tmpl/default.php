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
<form action="<?php echo JURI::base(); ?>index.php" method="post"
	name="adminForm" id="adminForm"><input type="hidden" name="option"
	value="com_joomlapack" /> <input type="hidden" name="view"
	value="profiles" /> <input type="hidden" name="boxchecked"
	id="boxchecked" value="0" /> <input type="hidden" name="task" id="task"
	value="" />
<table class="adminlist">
	<thead>
		<tr>
			<th width="20">#</th>
			<th><?php JText::_('PROFILE_COLLABEL_DESCRIPTION'); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
	$i = 0;

	foreach( $this->profiles as $profile ):
	$id = JHTML::_('grid.id', ++$i, $profile->id);
	$link = 'index.php?option='.JRequest::getCmd('option').'&amp;view='.JRequest::getCmd('view').'&amp;task=edit&amp;id='.$profile->id.'&amp;layout=default_edit';
	?>
		<tr class="row<?php echo $i%2; ?>">
			<td><?php echo $id; ?></td>
			<td><a href="<?php echo $link; ?>"><?php echo $profile->description; ?></a>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
</form>

		<?php echo JoomlapackHelperUtils::getFooter(); ?></div>
