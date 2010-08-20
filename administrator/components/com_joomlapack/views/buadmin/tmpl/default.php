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

JHTML::_('behavior.mootools');

// Filesize formatting function by eregon at msn dot com
// Published at: http://www.php.net/manual/en/function.number-format.php
function format_filesize($number, $decimals = 2, $force_unit = false, $dec_char = '.', $thousands_char = '')
{
	$units = array('b', 'Kb', 'Mb', 'Gb', 'Tb');
	if($force_unit === false)
	$unit = floor(log($number, 2) / 10);
	else
	$unit = $force_unit;
	if($unit == 0)
	$decimals = 0;
	return number_format($number / pow(1024, $unit), $decimals, $dec_char, $thousands_char).' '.$units[$unit];
}

?>
<div id="jpcontainer">
<form action="<?php echo JURI::base(); ?>index.php" method="post"
	name="adminForm" id="adminForm"><input type="hidden" name="option"
	id="option" value="com_joomlapack" /> <input type="hidden" name="view"
	id="view" value="buadmin" /> <input type="hidden" name="boxchecked"
	id="boxchecked" value="0" /> <input type="hidden" name="task" id="task"
	value="" />
<table class="adminlist">
	<thead>
		<tr>
			<th width="20"><input type="checkbox" name="toggle" value=""
				onclick="checkAll(<?php echo count( $this->list ) + 1; ?>);" /></th>

				<?php if($this->easy): ?>
			<th><?php echo JText::_('STATS_LABEL_START'); ?></th>
			<th><?php echo JText::_('STATS_LABEL_STATUS'); ?></th>
			<th><?php echo JText::_('STATS_LABEL_SIZE'); ?></th>
			<th><?php echo JText::_('STATS_LABEL_ARCHIVE'); ?></th>
			<?php else: ?>
			<th><?php echo JText::_('STATS_LABEL_DESCRIPTION'); ?></th>
			<th><?php echo JText::_('STATS_LABEL_START'); ?></th>
			<th><?php echo JText::_('STATS_LABEL_DURATION'); ?></th>
			<th><?php echo JText::_('STATS_LABEL_STATUS'); ?></th>
			<th><?php echo JText::_('STATS_LABEL_ORIGIN'); ?></th>
			<?php if(JPSPECIALEDITION): ?>
			<th><?php echo JText::_('STATS_LABEL_TYPE'); ?></th>
			<th><?php echo JText::_('STATS_LABEL_PROFILEID'); ?></th>
			<?php endif; ?>
			<th><?php echo JText::_('STATS_LABEL_SIZE'); ?></th>
			<th><?php echo JText::_('STATS_LABEL_ARCHIVE'); ?></th>
			<?php endif; ?>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="10"><?php echo $this->pagination->getListFooter(); ?></td>
		</tr>
	</tfoot>
	<tbody>
	<?php if(!empty($this->list)): ?>
	<?php $id = 1; $i = 0;?>
	<?php foreach($this->list as $record): ?>
	<?php
	$id = 1 - $id;
	$check = JHTML::_('grid.id', ++$i, $record->id);
	switch($record->meta)
	{
		case 'ok':
			$status = JText::_('STATS_LABEL_STATUS_OK');
			break;

		case 'obsolete':
			$status = JText::_('STATS_LABEL_STATUS_OBSOLETE');
			break;

		case 'fail':
			$status = JText::_('STATS_LABEL_STATUS_FAIL');
			break;

		case 'pending':
			$status = JText::_('STATS_LABEL_STATUS_PENDING');
			break;
	}

	switch($record->origin)
	{
		case 'frontend':
			$origin = JText::_('STATS_LABEL_ORIGIN_FRONTEND');
			break;

		case 'backend':
			$origin = JText::_('STATS_LABEL_ORIGIN_BACKEND');
			break;
	}

	switch($record->type)
	{
		case 'full':
			$type = JText::_('STATS_LABEL_TYPE_FULL');
			break;

		case 'dbonly':
			$type = JText::_('STATS_LABEL_TYPE_DBONLY');
			break;

		case 'extradbonly':
			$type = JText::_('STATS_LABEL_TYPE_EXTRADBONLY');
			break;
	}

	jimport('joomla.utilities.date');
	$startTime = new JDate($record->backupstart);
	$endTime = new JDate($record->backupend);

	$duration = $endTime->toUnix() - $startTime->toUnix();
	if($duration > 0)
	{
		$seconds = $duration % 60;
		$duration = $duration - $seconds;
			
		$minutes = ($duration % 3600) / 60;
		$duration = $duration - $minutes * 60;
			
		$hours = $duration / 3600;
		$duration = sprintf('%02d',$hours).':'.sprintf('%02d',$minutes).':'.sprintf('%02d',$seconds);
	}
	else
	{
		$duration = '-';
	}
	$user =& JFactory::getUser();
	$userTZ = $user->getParam('timezone',0);
	$startTime->setOffset($userTZ);
	
	if($record->meta == 'ok')
	{
		// Get the download links for downloads for completed, valid backups
		$filename_col = '';
		$thisPart = '';
		$thisID = urlencode($record->id);
		if($record->multipart == 0)
		{
			// Single part file -- Create a simple link
			$filename_col = "<a href=\"javascript:confirmDownload('$thisID', '$thisPart');\">".$record->archivename."</a>";
		}
		else
		{
			$filename_col = $record->archivename."<br/>";
			for($count = 0; $count < $record->multipart; $count++)
			{
				$thisPart = urlencode($count);
				$label = JText::sprintf('STATS_LABEL_PART', $count);
				$filename_col .= ($count > 0) ? ' &bull; ' : '';
				$filename_col .= "<a href=\"javascript:confirmDownload('$thisID', '$thisPart');\">$label</a>";
			}
		}
	}
	else
	{
		// If the backup is not complete, just show dashes
		$filename_col = '&mdash;';
	}
	
	// Link for Show Comments lightbox
	$info_link = "";
	if(!empty($record->comment))
	{
		$info_link = JHTML::_('tooltip', strip_tags($record->comment) ) . '&ensp;';
	}

	$edit_link = JURI::base() . 'index.php?option=com_joomlapack&view=buadmin&task=showcomment&id='.$record->id;
	
	if(empty($record->description)) $record->description = JText::_('STATS_LABEL_NODESCRIPTION');
	?>
		<tr class="row<?php echo $id; ?>">
		<?php if($this->easy): ?>
			<td><?php echo $check; ?></td>
			<td><?php echo $startTime->toFormat(JText::_('DATE_FORMAT_LC4')); ?></td>
			<td class="bufa-<?php echo $record->meta; ?>"><?php echo $status ?></td>
			<td><?php echo ($record->meta == 'ok') ? format_filesize($record->size) : '-' ?></td>
			<td><?php echo $record->archivename ?></td>
			<?php else: ?>
			<td><?php echo $check; ?></td>
			<td><?php echo $info_link ?>
				<a href="<?php echo $edit_link; ?>"><?php echo $record->description ?></a></td>
			<td><?php echo $startTime->toFormat(JText::_('DATE_FORMAT_LC4')); ?></td>
			<td><?php echo $duration; ?></td>
			<td class="bufa-<?php echo $record->meta; ?>"><?php echo $status ?></td>
			<td><?php echo $origin ?></td>
			<?php if(JPSPECIALEDITION): ?>
			<td><?php echo $type ?></td>
			<td><?php echo $record->profile_id ?></td>
			<?php endif; ?>
			<td><?php echo ($record->meta == 'ok') ? format_filesize($record->size) : '-' ?></td>
			<td><?php echo $filename_col; ?></td>
			<?php endif; ?>
		</tr>
		<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>
</form>
</div>
