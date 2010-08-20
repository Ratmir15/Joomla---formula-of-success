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

$editor =& JFactory::getEditor();
$getText = $editor->getContent('comment');

?>
<?php if($this->haserrors): ?>
<div class="errorframe">
<h3><?php echo JText::_('BACKUP_LABEL_DETECTEDERRORS') ?></h3>
<p><?php echo JText::_('BACKUP_LABEL_ERRORSLIST') ?></p>
<?php echo $this->quirks; ?></div>
<?php else: ?>

<script type="text/javascript" language="Javascript">
function submitButton(pressbutton)
{
	var form = document.adminForm;
	<?php echo $editor->save('comment'); ?>
	submitform( pressbutton );
}
</script>

<h1><?php echo JText::_('BACKUP_HEADER_STARTNEW') ?></h1>
	<?php if ($this->hasquirks): ?>
<div class="warningframe">
<h3><?php echo JText::_('BACKUP_LABEL_DETECTEDQUIRKS') ?></h3>
<p><?php echo JText::_('BACKUP_LABEL_QUIRKSLIST') ?></p>
	<?php echo $this->quirks; ?></div>
	<?php endif; ?>
<form name="adminForm" id="adminForm"
	action="<?php echo JURI::base(); ?>index.php" method="post"><input
	type="hidden" name="task" value="backup" /> <input type="hidden"
	name="option" value="com_joomlapack" /> <input type="hidden"
	name="view" value="backup" />

<table class="adminlist" width="100%">
<?php if(JPSPECIALEDITION): ?>
	<tr>
		<td><?php echo JText::_('BACKUP_LABEL_PROFILE'); ?></td>
		<td><?php echo JHTML::_('select.genericlist', $this->profilelist, 'profile', null, 'value', 'text', $this->profile); ?>
		</td>
	</tr>
	<?php else: ?>
	<input type="hidden" name="profile" id="profile" value="1" />
	<?php endif; ?>
	<tr>
		<td><?php echo JText::_('BACKUP_LABEL_DESCRIPTION'); ?></td>
		<td><input type="text" name="description"
			value="<?php echo $this->description; ?>" maxlength="255" size="60" />
		</td>
	</tr>
	<tr>
		<td><?php echo JText::_('BACKUP_LABEL_COMMENT'); ?></td>
		<td><?php echo $editor->display( 'comment',  $this->comment, '550', '200', '60', '20', array() ) ; ?>
		</td>
	</tr>
	<tr>
		<td colspan="2"><input type="button"
			value="<?php echo JText::_('BACKUP_LABEL_START') ?>"
			onclick="submitButton('backup');" /></td>
	</tr>
</table>
</form>
	<?php endif; ?>