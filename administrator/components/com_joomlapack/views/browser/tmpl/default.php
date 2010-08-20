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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php echo JText::_('BROWSER_HTMLTITLE'); ?></title>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="-1" />
<link rel="stylesheet" type="text/css"
	href="<?php echo JURI::base(); ?>components/com_joomlapack/assets/css/browser.css" />
<script type="text/javascript">
		function getFolder()
		{
			document.getElementById('folder').value = window.parent.document.getElementById('outdir').value;
			document.getElementById('processfolder').value = 1; 
			document.forms['adminForm'].submit();
		}
		
		function useThis()
		{
			window.parent.document.getElementById('outdir').value = '<?php echo addslashes($this->folder) ?>';
			window.parent.document.getElementById('sbox-window').close();
		}
	</script>
</head>
<body <?php if(empty($this->folder)): ?> onload="getFolder();"
<?php endif; ?>>
	<?php if(empty($this->folder)): ?>
<form action="index.php" method="post" name="adminForm">
	<input type="hidden" name="option" value="com_joomlapack" />
	<input type="hidden" name="view" value="browser" />
	<input type="hidden" name="format" value="raw" />
	<input type="hidden" name="folder" id="folder" value="" />
	<input type="hidden" name="processfolder" id="processfolder" value="0" />
</form>
	<?php else: ?>
<div id="controls"><?php
$image = JURI::base().'components/com_joomlapack/assets/images/';
$image .= $this->writable ? 'ok_small.png' : 'error_small.png';
?> <img src="<?php echo $image; ?>"
	style="float: right; position: relative; right: 3px; top: 6px;"
	alt="<?php echo $this->writable ? JText::_('WRITABLE') : JText::_('UNWRITABLE'); ?>"
	title="<?php echo $this->writable ? JText::_('WRITABLE') : JText::_('UNWRITABLE'); ?>" />
<form action="index.php" method="post" name="adminForm"><input
	type="hidden" name="option" value="com_joomlapack" /> <input
	type="hidden" name="view" value="browser" /> <input type="hidden"
	name="format" value="raw" /> <input type="text" name="folder"
	id="folder" value="<?php echo $this->folder; ?>" /> <input
	type="submit" class="button"
	value="<?php echo JText::_('BROWSER_LABEL_GO'); ?>" /> <input
	type="button" class="button"
	value="<?php echo JText::_('BROWSER_LABEL_USE'); ?>"
	onclick="useThis();" /></form>
</div>

<div id="breadcrumbs"><?php if(count($this->breadcrumbs) > 0): ?> <?php $i = 0 ?>
<?php foreach($this->breadcrumbs as $crumb):
$link = JURI::base()."index.php?option=com_joomlapack&view=browser&format=raw&folder=".urlencode($crumb['folder']);
$label = htmlentities($crumb['label']);
$i++;
$bull = $i < count($this->breadcrumbs) ? '&bull;' : '';
?> <a href="<?php echo $link ?>"><?php echo $label ?></a><?php echo $bull ?>
<?php endforeach; ?> <?php endif; ?></div>

<div id="browser"><?php if(count($this->subfolders) > 0): ?> <?php $linkbase = JURI::base()."index.php?option=com_joomlapack&view=browser&format=raw&folder="; ?>
<a href="<?php echo $linkbase.urlencode($this->parent); ?>"><?php echo JText::_('BROWSER_LABEL_GOPARENT') ?></a>
<?php foreach($this->subfolders as $subfolder): ?> <a
	href="<?php echo $linkbase.urlencode($this->folder.DS.$subfolder); ?>"><?php echo htmlentities($subfolder) ?></a>
<?php endforeach; ?> <?php else: ?> <?php
if(!$this->exists) {
	echo JText::_('BROWSER_ERROR_NOTEXISTS');
} else if(!$this->inRoot) {
	echo JText::_('BROWSER_ERROR_NONROOT');
} else if($this->openbasedirRestricted) {
	echo JText::_('BROWSER_ERROR_BASEDIR');
} else {
	?> <?php $linkbase = JURI::base()."index.php?option=com_joomlapack&view=browser&format=raw&folder="; ?>
<a href="<?php echo $linkbase.urlencode($this->parent); ?>"><?php echo JText::_('BROWSER_LABEL_GOPARENT') ?></a>
	<?php
}
?> <?php endif; ?></div>
<?php endif; ?>
</body>
</html>
