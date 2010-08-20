<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 *
 * The main page of the JoomlaPack component is where all the fun takes place :)
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

$lang =& JFactory::getLanguage();

?>
<div id="jpcontainer"><?php if($this->showtroubleshooter): ?>
<div class="jptroubleshooter" style="<?php echo $this->troubleshooterstyle ?>">
<p><?php echo JText::_('CPANEL_TROUBLESHOOTER_TITLE') ?> <?php echo $this->troubleshootertext; ?>
</p>
<p><a href="<?php echo $this->troubleshooterurl; ?>"><?php echo JText::_('CPANEL_TROUBLESHOOTER_APPLY') ?></a></p>
</div>
<?php endif; ?>

<div class="toprowcontainer">
<div class="feedrotator"><a target="_blank"
	href="http://feeds2.feedburner.com/~r/joomlapack/news/~6/1"><img
	src="http://feeds2.feedburner.com/joomlapack/news.1.gif"
	alt="The JoomlaPack News" style="border: 0"></a></div>
<?php if( (!$this->easymode) && JPSPECIALEDITION ): ?>
<div class="activeprofile">
<form action="<?php echo JURI::base(); ?>index.php" method="post"
	name="adminForm" id="adminForm"><input type="hidden" name="option"
	value="com_joomlapack" /> <input type="hidden" name="view"
	value="cpanel" /> <input type="hidden" name="task"
	value="switchprofile" />
<p><?php echo JText::_('PANEL_PROFILE_TITLE'); ?>: #<?php echo $this->profileid; ?>
<?php echo JHTML::_('select.genericlist', $this->profilelist, 'profileid', 'onchange="document.forms.adminForm.submit()"', 'value', 'text', $this->profileid); ?>
<input type="submit"
	value="<?php echo JText::_('PANEL_PROFILE_BUTTON'); ?>" /></p>
</form>
</div>
<?php endif; ?></div>

<div>
<div id="cpanel" style="width: 60%; float: left;"><?php foreach($this->icondefs as $icon): ?>
<div style="float:<?php echo ($lang->isRTL()) ? 'right' : 'left'; ?>;">
<div class="icon"><a
	href="<?php echo 'index.php?option='.JRequest::getCmd('option','com_joomlapack') . (is_null($icon['view']) ? '' : '&amp;view='.$icon['view']) . (is_null($icon['task']) ? '' : '&amp;task='.$icon['task']); ?>">
<img
	src="<?php echo JURI::base().'components/'.JRequest::getCmd('option','com_joomlapack').'/assets/images/'.$icon['icon']; ?>"
	border="0" alt="<?php echo $icon['label']; ?>" /> <span><?php echo $icon['label']; ?></span>
</a></div>
</div>
<?php endforeach; ?></div>
<div id="jpmodules" style="width: 40%; float: left;"><?php
jimport('joomla.html.pane');
JHTML::_('behavior.mootools');

$pane =& JPane::getInstance('Sliders');

echo $pane->startPane('jpsliders')."\n";

if(!$this->easymode)
{
	echo $pane->startPanel(jtext::_('JPSTATUSSUMMARY'),'jpstatussummary')."\n";
	echo $this->statuscell."\n";
	echo $pane->endPanel()."\n";

	echo $pane->startPanel(jtext::_('JPSTATUSDETAILS'),'jpstatusdetails')."\n";
	echo $this->detailscell."\n";
	echo $pane->endPanel()."\n";
}
else
{
	echo $pane->startPanel(jtext::_('JPSTATUSSUMMARY'),'jpstatussummary')."\n";
	echo $this->statuscell."\n";
	echo "<hr/>\n";
	echo $this->detailscell."\n";
	echo $pane->endPanel()."\n";
}

if(!$this->easymode)
{
	echo $pane->startPanel(jtext::_('BACKUP_STATS'),'jpbackupstats')."\n";
	echo $this->statscell."\n";
	echo $pane->endPanel()."\n";

	echo $pane->startPanel(jtext::_('NEWS_TITLE'),'jpnews')."\n";
	echo $this->newscell."\n";
	echo $pane->endPanel()."\n";
}

echo $pane->startPanel(jtext::_('TRANSLATION_CREDITS'),'jptranslationcredits')."\n";
?>
<p><strong><?php echo JText::_('TRANSLATION_LANGUAGE') ?></strong><br />
<a href="<?php echo JText::_('TRANSLATION_AUTHOR_URL') ?>"><?php echo JText::_('TRANSLATION_AUTHOR') ?></a>
</p>
<?php
echo $pane->endPanel()."\n";

echo $pane->endPane()."\n";
?></div>
</div>

<div style="clear: both;"></div>

<div style="text-align: center;">
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<p><input type="hidden" name="cmd" value="_s-xclick"> <input
	type="image"
	src="https://www.paypal.com/en_US/i/btn/x-click-butcc-donate.gif"
	border="0" name="submit"
	alt="Make payments with PayPal - it's fast, free and secure!"> <img
	alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif"
	width="1" height="1"> <input type="hidden" name="encrypted"
	value="-----BEGIN PKCS7-----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-----END PKCS7-----
		"></p>
</form>
</div>

<?php echo JoomlapackHelperUtils::getFooter(); ?></div>
