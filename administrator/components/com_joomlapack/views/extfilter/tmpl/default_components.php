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
<h2><?php echo JText::_('EXTFILTER_COMPONENTS'); ?></h2>
<table class="adminlist" width="100%">
	<thead>
		<tr>
			<th width="50px"><?php echo JText::_('EXTFILTER_LABEL_STATE'); ?></th>
			<th><?php echo JText::_('EXTFILTER_LABEL_COMPONENT'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
	</tfoot>
	<tbody>
	<?php
	$i = 0;
	foreach($this->components as $component):
	$i++;
	$link = JURI::base().'index.php?option=com_joomlapack&view=extfilter&task=toggleComponent&item='.$component['option'];
	if($component['active'])
	{
		$image = JHTML::_('image.administrator', 'publish_x.png');
		$html = '<b>'.$component['name'].'</b>';
	}
	else
	{
		$image = JHTML::_('image.administrator', 'tick.png');
		$html = $component['name'];
	}

	?>
		<tr class="row<?php echo $i%2; ?>">
			<td style="text-align: center;"><a href="<?php echo $link ?>"><?php echo $image ?></a></td>
			<td><a href="<?php echo $link ?>"><?php echo $html ?></a></td>
		</tr>
	</tbody>
	<?php
	endforeach;
	?>
</table>
</div>
