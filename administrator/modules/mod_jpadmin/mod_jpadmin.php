<?php
/**
 * @package JoomlaPack
 * @subpackage BackupIconModule
 * @copyright Copyright (c)2009 JoomlaPack Developers
 * @license GNU General Public License version 3, or later
 * @since 2.2
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

// Make sure JoomlaPack is enabled
jimport('joomla.application.component.helper');
if (!JComponentHelper::isEnabled('com_joomlapack', true))
{
	JError::raiseError('E_JPNOTENABLED', JText('JP_NOT_ENABLED'));
	return;
}

// Set default parameters
$params->def('enablewarnings', 0); // Enable warnings
$params->def('warnfailed', 0); // Warn if backup is failed
$params->def('maxbackupperiod', 24); // Maximum time between backups, in hours

// Load custom CSS
$document =& JFactory::getDocument();
$document->addStyleSheet(JURI::base().'components/com_joomlapack/assets/css/mod_jpadmin.css');

// Initialize defaults
$lang =& JFactory::getLanguage();
$image = "joomlapack-48.png";
$label = JText::_('LBL_JOOMLAPACK');

if( $params->get('enablewarnings', 0) == 0 )
{
	// Process warnings
	$warning = false;

	// Get latest backup ID
	$db =& JFactory::getDBO();
	$query = 'SELECT max(id) FROM #__jp_stats';
	$db->setQuery($query);
	$id = $db->loadResult();
	unset($query);

	// Only proceed if there is a latest backup entry!
	if(!empty($id))
	{
		$query = "SELECT * FROM #__jp_stats WHERE `id`".
				" = ".$id;
		$db->setQuery($query);
		$db->query();
		$record = $db->loadObject();
	}
	else
	{
		$record = null;
	}
	unset($query, $db);

	// Process "failed backup" warnings, if specified
	if( $params->get('warnfailed', 0) == 0 )
	{
		if(!empty($id))
		{
			$warning = (($record->status == 'fail') || ($record->status == 'run'));
		}
	}

	// Process "stale backup" warnings, if specified
	if(empty($id))
	{
		$warning = true;
	}
	else
	{
		$maxperiod = $params->get('maxbackupperiod', 24);
		jimport('joomla.utilities.date');
		$lastBackupRaw = $record->backupstart;
		$lastBackupObject = new JDate($lastBackupRaw);
		$lastBackup = $lastBackupObject->toUnix(false);
		$maxBackup = time() - $maxperiod * 3600;
		if(!$warning) $warning = ($lastBackup < $maxBackup);
	}

	if($warning)
	{
		$image = 'joomlapack-warning-48.png';
		$label = JText::_('LBL_BACKUPREQUIRED');
	}
}

// Load the JoomlaPack configuration and check user access permission
require_once JPATH_ADMINISTRATOR.DS.'components'.DS.'com_joomlapack'.DS.'classes'.DS.'factory.php';
require_once JPATH_ADMINISTRATOR.DS.'components'.DS.'com_joomlapack'.DS.'models'.DS.'registry.php';
$registry =& JoomlapackModelRegistry::getInstance();
$authlevel = $registry->get('authlevel',25); // Default authlevel = Super Administrators only
$user =& JFactory::getUser();
$gid = $user->gid;
$showModule = true;
unset($registry);
unset($user);
switch($authlevel)
{
	case 25:
		// Super administrator access only

		if($gid != 25)
		{
			$showModule = false;
		}
		break;

	case 24:
		// Administrator access allowed
		if( ($gid != 25) && ($gid != 24) )
		{
			$showModule = false;
		}
		break;

	case 23:
		// Managers access allowed
		if( ($gid != 25) && ($gid != 24) && ($gid != 23) )
		{
			$showModule = false;
		}
		break;
}

if($showModule):
?>
<div class="jpcpanel">
<div style="float:<?php echo ($lang->isRTL()) ? 'right' : 'left'; ?>;">
<div class="icon"><a href="index.php?option=com_joomlapack&view=backup">
<img src="components/com_joomlapack/assets/images/<?php echo $image ?>" />
<span><?php echo $label; ?></span> </a></div>
</div>
</div>
<?php endif; ?>