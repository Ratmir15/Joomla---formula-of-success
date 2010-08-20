<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 */

defined('_JEXEC') or die('Restricted access');

// Delegate page rendering to the helper class
jpimport('helpers.def', true);
JoomlapackHelperDef::renderJavaScript();
echo "\n";
JoomlapackHelperDef::renderPageArea();
?>