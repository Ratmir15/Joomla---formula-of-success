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

jpimport('core.cube');
jpimport('helpers.sajax', true);
$cube =& JoomlapackCUBE::getInstance();
if($cube->getError())
{
	$action = 'error';
	$message = $cube->getError();
}
else
{
	$action = 'step';
	$message = '';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<title>JoomlaPack Backup Start Sequence</title>
</head>
<body>
<script type="text/javascript" language="Javascript">
	window.parent.handleRequest('<?php echo $action; ?>','<?php echo $message; ?>');
</script>
</body>
</html>
