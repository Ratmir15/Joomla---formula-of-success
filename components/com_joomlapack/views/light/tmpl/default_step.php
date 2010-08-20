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

$array = $this->cube;

switch($array['Domain'])
{
	case 'init':
		$domain = JText::_('LIGHT_DOMAIN_INIT');
		break;
	case 'installer':
		$domain = JText::_('LIGHT_DOMAIN_INSTALLER');
		break;
	case 'PackDB':
		$domain = JText::_('LIGHT_DOMAIN_PACKDB');
		break;
	case 'Packing':
		$domain = JText::_('LIGHT_DOMAIN_PACKING');
		break;
	case 'finale':
	default:
		$domain = JText::_('LIGHT_DOMAIN_FINALE');
		break;
}

?>
<html>
<head>
<title><?php echo JText::_('LIGHT_HEADER');?></title>
</head>
<body>
<h1><?php echo JText::_('LIGHT_HEADER');?></h1>
<form action="index.php" method="post" name="adminForm" id="adminForm">
<input type="hidden" name="option" value="com_joomlapack" /> <input
	type="hidden" name="view" value="light" /> <input type="hidden"
	name="format" value="raw" /> <input type="hidden" name="task"
	value="step" />
<table border="0"
	style="border: thin solid black; background-color: #eeeeff;"
	width="100%">
	<tr>
		<td><b><?php echo $domain ?></b></td>
	</tr>
	<tr>
		<td><?php echo $array['Step']; ?></td>
	</tr>
	<tr>
		<td style="color: gray;"><?php echo $array['Substep']; ?></td>
	</tr>
</table>
</form>
<script type="text/javascript" language="javascript">
	document.getElementById('adminForm').submit();
</script>
</body>
</html>
