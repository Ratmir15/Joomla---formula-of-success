<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 2.3
 * 
 * CRON Script Manager View - Default grid view
 */

defined('_JEXEC') or die('Restricted access');

$sample_script_path = $this->scriptdirectory.DS.'cron1.php  ';

//CRONMAN_CRON_INSTRUCTIONS
?>
<div id="jpcontainer">
<form action="<?php echo JURI::base(); ?>index.php" method="post"
	name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_joomlapack" />
	<input type="hidden" name="view" value="cronman" />
	<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
	<input type="hidden" name="task" id="task" value="" />
<table class="adminlist">
	<thead>
		<tr>
			<th width="20">
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->definitions ) + 1; ?>);" />
			</th>
			<th><?php echo JText::_('CRON_LABEL_SCRIPT'); ?></th>
			<th><?php echo JText::_('CRON_LABEL_PROFILE'); ?></th>
			<th><?php echo JText::_('CRON_LABEL_POSTOP'); ?></th>
			<th><?php echo JText::_('CRON_LABEL_EMAILORFTP'); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
	$i = 0;

	foreach( $this->definitions as $definition ):
	$id = JHTML::_('grid.id', ++$i, $definition->id);
	$link = 'index.php?option='.JRequest::getCmd('option').'&amp;view='.JRequest::getCmd('view').'&amp;task=edit&amp;id='.$definition->id.'&amp;layout=default_edit';
	$myid = $definition->profile;
	if(array_key_exists( (string)$myid, $this->profiles) )
	{
		$definition->profile = $this->profiles[$myid];
	}
	switch($definition->postop)
	{
		case 'upload':
			$postop = JText::_('CRON_OPT_POSTOP_UPLOAD');
			$action = $this->escape($definition->ftphost);
			break;
			
		case 'email':
			$postop = JText::_('CRON_OPT_POSTOP_EMAIL');
			$action = $this->escape($definition->email);
			break;
			
		default:
		case 'none':
			$postop = JText::_('CRON_OPT_POSTOP_NONE');
			$action = '&nbsp;';
			break;
	}
	?>
		<tr class="row<?php echo $i%2; ?>">
			<td><?php echo $id; ?></td>
			<td><a href="<?php echo $link; ?>">cron<?php echo $definition->id; ?>.php</a></td>
			<td><?php echo $definition->profile; ?></td>
			<td><?php echo $postop ?></td>
			<td><?php echo $action ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
</form>
<p class="jpinfo">
	<?php echo JText::sprintf('CRONMAN_CRON_INSTRUCTIONS',$sample_script_path);?>
</p>
<?php echo JoomlapackHelperUtils::getFooter(); ?></div>
