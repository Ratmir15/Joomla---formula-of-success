<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 2.1
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.html.html');
?>
<div id="jpcontainer">
<h2><?php echo JText::_('EXTFILTER_PLUGINS'); ?></h2>
<table class="adminlist" width="100%">
	<thead>
		<tr>
			<th width="50px"><?php echo JText::_('EXTFILTER_LABEL_STATE'); ?></th>
			<th><?php echo JText::_('EXTFILTER_LABEL_PLUGIN'); ?></th>
			<th><?php echo JText::_('EXTFILTER_LABEL_TYPE'); ?></th>
			<th><?php echo JText::_('EXTFILTER_LABEL_AREA'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="4">&nbsp;</td>
		</tr>
	</tfoot>
	<tbody>
	<?php
	$i = 0;
	foreach($this->plugins as $plugin):
	$i++;
	$link = JURI::base().'index.php?option=com_joomlapack&view=extfilter&task=togglePlugin&item='.$plugin['id'];
	$area = $plugin['frontend'] ? JText::_('EXTFILTER_LABEL_FRONTEND') : JText::_('EXTFILTER_LABEL_BACKEND');
	$type = $plugin['type'];
	if($plugin['active'])
	{
		$image = JHTML::_('image.administrator', 'publish_x.png');
		$html = '<b>'.$plugin['name'].'</b>';
	}
	else
	{
		$image = JHTML::_('image.administrator', 'tick.png');
		$html = $plugin['name'];
	}

	?>
		<tr class="row<?php echo $i%2; ?>">
			<td style="text-align: center;"><a href="<?php echo $link ?>"><?php echo $image ?></a></td>
			<td><a href="<?php echo $link ?>"><?php echo $html ?></a></td>
			<td><?php echo $type ?></td>
			<td><?php echo $area ?></td>
		</tr>
	</tbody>
	<?php
	endforeach;
	?>
</table>
</div>
