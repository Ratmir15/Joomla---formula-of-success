<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 2.2
 */

defined('_JEXEC') or die('Restricted access');

?>
<div id="jpcontainer">
<form action="<?php echo JURI::base(true); ?>/index.php" method="post"
	name="adminForm" id="adminForm"><input type="hidden" name="option"
	value="com_joomlapack" /> <input type="hidden" name="view"
	value="<?php echo JRequest::getCmd('view'); ?>" /> <input type="hidden"
	name="tpl" id="tpl" value="<?php echo JRequest::getCmd('tpl'); ?>" /> <input
	type="hidden" name="filterclass" id="filterclass"
	value="<?php echo $this->class; ?>" /> <input type="hidden"
	name="boxchecked" id="boxchecked" value="0" /> <input type="hidden"
	name="task" id="task" value="" />
<table class="adminlist">
	<thead>
		<tr>
			<th width="20px"><input type="checkbox" name="toggle" value=""
				onclick="checkAll(<?php echo count( $this->list ) + 1; ?>);" /></th>
			<th><?php echo JText::_('FILTERITEM'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="2"><?php echo $this->pagination->getListFooter(); ?></td>
		</tr>
	</tfoot>
	<tbody>
	<?php $id = 1; $i = 0;?>
	<?php foreach($this->list as $record): ?>
	<?php $id = 1 - $id; ?>
		<tr class="row<?php echo $id; ?>">
			<td><?php echo JHTML::_('grid.id', ++$i, $record->id); ?></td>
			<td><?php echo $record->value; ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
</form>
</div>
