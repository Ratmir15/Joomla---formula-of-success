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
 * HTML render class for the DBEF page.
 *
 * The static methods take care of user's choice between AJAX or JSRedirects operation
 * (per profile setting!) and produce the JavaScript necessary.
 *
 */
class JoomlapackHelperDbef extends JObject
{

	/**
	 * Outputs the AJAX-powered JavaScript to the browser. It is meant to be used
	 * by renderJavaScript()
	 *
	 * @access private
	 */
	function _renderAJAXJavaScript()
	{
		jpimport('helpers.sajax', true);
		sajax_init();
		sajax_force_page_ajax();
		sajax_export('toggle','tablepane');
		?>
<script language="JavaScript" type="text/javascript">
	/*
	 * (S)AJAX Library code
	 */
	 <?php sajax_show_javascript(); ?>
 	sajax_fail_handle = SAJAXTrap;

	function SAJAXTrap( myData ) {
		alert('Invalid AJAX reponse: ' + myData);
	}

	function toggle( table )
	{
		x_toggle( table, toggle_cb );
	}
	
	function toggle_cb( myRet )
	{
		tablepane();
	}
		
	function tablepane()
	{
		x_tablepane( tablepane_cb );
	}
	
	function tablepane_cb( myRet )
	{
		document.getElementById('tablepane').innerHTML = myRet;
	}
</script>
	 <?php
	}

	/**
	 * Output the non-AJAX version of JavaScript to the browser. It is meant to be used
	 * by renderJavaScript()
	 *
	 * @access private
	 */
	function _renderRedirectsJavaScript()
	{
		// At the moment, no JavaScript is necessary
	}

	/**
	 * Returns the HTML for the table pane
	 *
	 * @return string The HTML
	 */
	function getTablePane()
	{
		// Get the "backup method"
		jpimport('models.registry', true);

		$profile =& JoomlapackModelRegistry::getInstance();
		$method = $profile->get('backupMethod');

		// Load the model
		if(!class_exists('JoomlapackModelDbef'))
		{
			jpimport('models.dbef',true);
		}
		$model = new JoomlapackModelDbef();

		// Get tables of the current database
		$tables = $model->getTableList();

		$prefix = JApplication::getCfg('dbprefix');

		// Initialize output
		$txt_tables = JText::_('DBEF_LABEL_TABLES');
		$out = <<<ENDHTML
<table class="adminlist">
	<thead>
		<tr>
			<th>$txt_tables</th>
		</tr>
	</thead>
	<tbody>
ENDHTML;

		// Do we have tables?
		if(empty($tables))
		{
			// No, warn user
			$out .= "<tr><td><p>".JText::_('DBEF_ERROR_NOTABLES').'</p></td></tr>';
		}
		else
		{
			$urlbase = JURI::base().'/index.php?option=com_joomlapack&view=dbef&task=toggle&table=' ;
			foreach($tables as $tableName)
			{
				$table = str_replace($prefix, '#__', $tableName); // Get abstract name

				if($method == 'ajax')
				{
					$href = 'javascript:toggle(\''.addslashes($table).'\');';
				}
				else
				{
					$href = $urlbase.urlencode($table);
				}
				$htmlTable = htmlentities($tableName);

				// Make excluded table red and bold
				if($excluded = $model->isSetFor($table))
				{
					$style = 'style="color:red; font-weight: bold; text-decoration: none"';
				}
				else
				{
					$style = 'style="text-decoration: none"';
				}
				$out .= <<<ENDHTML
		<tr>
			<td>
				<a href="$href" $style>$htmlTable</a>
			</td>
		</tr>
ENDHTML;

			}
		}

		$out .= <<<ENDHTML
	</tbody>
</table>
ENDHTML;

		return $out;
	}

	/**
	 * Outputs the necessary JavaScript to the browser
	 *
	 */
	function renderJavaScript()
	{
		jpimport('models.registry', true);

		$profile =& JoomlapackModelRegistry::getInstance();
		$method = $profile->get('backupMethod');
		if($method == 'ajax')
		{
			JoomlapackHelperDbef::_renderAJAXJavaScript();
		}
		else
		{
			JoomlapackHelperDbef::_renderRedirectsJavaScript();
		}
	}
}