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

//echo"<pre>";var_dump($this->records);echo "</pre>";die();
?>
<div id="jpcontainer">
<form name="adminForm" id="adminForm"
	action="<?php echo JURI::base(); ?>index.php" method="post"><input
	type="hidden" name="boxchecked" value="0" /> <input type="hidden"
	name="task" value="" /> <input type="hidden" name="option"
	value="com_joomlapack" /> <input type="hidden" name="view"
	value="multidb" />

<table class="adminlist" width="100%">
	<thead>
		<tr>
			<th width="20"><input type="checkbox" name="toggle" value=""
				onclick="checkAll(<?php echo count($this->records); ?>);" /></th>
			<th width="20"><?php echo JText::_( 'Num' ); ?></th>
			<th><?php echo JText::_( 'MULTIDB_LABEL_DATABASE' ); ?></th>
			<th><?php echo JText::_( 'MULTIDB_LABEL_HOST' ); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="4"><?php if($this->pagination->total > 0) echo $this->pagination->getListFooter() ?>
			</td>
		</tr>
	</tfoot>
	<tbody>
	<?php
	$i = 0;
	if(count($this->records) > 0) foreach($this->records as $record):
	$id = JHTML::_('grid.id', ++$i, $record->id);
	$data = unserialize($record->value);
	$database = $data['database'];
	$host = $data['host'];
	$editURL = JURI::base().'index2.php?option=com_joomlapack&view=multidb&task=edit&id='.$record->id;
	?>
		<tr class="row<?php echo $i%2; ?>">
			<td><?php echo $id; ?></td>
			<td><?php echo $record->id; ?></td>
			<td><a href="<?php echo $editURL ?>"><?php echo $database; ?></a></td>
			<td><?php echo $host; ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
</form>

<?php echo JoomlapackHelperUtils::getFooter(); ?></div>