<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 */

defined('_JEXEC') or die('Restricted access');

/**
 * HTML rendering helper for the Configuration page
 *
 */
class JoomlapackHelperConfig extends JObject
{
	/**
	 * Outputs a row with an edit box
	 *
	 * @param string $translationKey Translation key for the label
	 * @param string $varName Variable name
	 * @param string $varValue Variable default value
	 * @param int $textLength Textbox size in characters, default = 40 characters
	 */
	function renderEditBoxRow($translationKey, $varName, $varValue, $textLength = 40)
	{
		$label = JText::_($translationKey);
		echo <<<ENDOFHTML
	<tr>
		<td>&nbsp;</td>
		<td>$label</td>
		<td><input type="text" name="var[$varName]" id="$varName" size="$textLength" value="$varValue" />
		</td>
	</tr>
ENDOFHTML;
	}

	/**
	 * Outputs a row with a password box
	 *
	 * @param string $translationKey Translation key for the label
	 * @param string $varName Variable name
	 * @param string $varValue Variable default value
	 * @param int $textLength Textbox size in characters, default = 40 characters
	 */
	function renderPasswordBoxRow($translationKey, $varName, $varValue, $textLength = 40)
	{
		$label = JText::_($translationKey);
		echo <<<ENDOFHTML
	<tr>
		<td>&nbsp;</td>
		<td>$label</td>
		<td><input type="password" name="var[$varName]" id="$varName" size="$textLength" value="$varValue" />
		</td>
	</tr>
ENDOFHTML;
	}
	/**
	 * Outputs a row with an combo box
	 *
	 * @param string $translationKey Translation key for the label
	 * @param string $varName Variable name
	 * @param string $varValue Variable default value
	 * @param array $optionsList The JHTML option values array to render; if ommited uses a Yes/No list
	 */
	function renderSelectionBoxRow($translationKey, $varName, $varValue, $optionsList = null)
	{
		$label = JText::_($translationKey);
		if(is_null($optionsList))
		{
			$combo = JHTML::_('select.booleanlist',"var[$varName]",'',$varValue);
		}
		else
		{
			$combo = JHTML::_('select.genericlist', $optionsList, "var[$varName]", '', 'value', 'text', $varValue);
		}
		echo <<<ENDOFHTML
	<tr>
		<td>&nbsp;</td>
		<td>$label</td>
		<td>$combo</td>
	</tr>
ENDOFHTML;
	}
}