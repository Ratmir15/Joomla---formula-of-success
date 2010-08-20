<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 */

defined('_JEXEC') or die('Restricted access');

@ob_end_clean(); // In case some braindead mambot spits its own HTML despite no_html=1
header('Content-type: application/xml');
header('Content-Disposition: attachment; filename="joomlapack_configuration.xml"');
echo $this->xml;
die();