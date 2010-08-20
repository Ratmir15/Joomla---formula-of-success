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
 * HTML render class for the SFF page.
 *
 * The static methods take care of user's choice between AJAX or JSRedirects operation
 * (per profile setting!) and produce the JavaScript necessary.
 *
 */
class JoomlapackHelperSff extends JObject
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
		sajax_export('toggle','filepane','folderpane');
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

	var globRoot = '';
	
	function toggle( fileName )
	{
		x_toggle( fileName, toggle_cb );
	}
	
	function toggle_cb( myRet )
	{
		filepane();
	}
	
	function filepane()
	{
		x_filepane( globRoot, filepane_cb ); 
	}
	
	function filepane_cb( myRet )
	{
		document.getElementById('filepane').innerHTML = myRet;
	}
	
	function folderpane( path )
	{
		globRoot = path;
		document.getElementById('currentdirectory').innerHTML = globRoot;
		x_folderpane( globRoot, folderpane_cb );
	}
	
	function folderpane_cb( myRet )
	{
		document.getElementById('folderpane').innerHTML = myRet;
		filepane();
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
	 * Returns the HTML for the folder pane of the specified folder
	 *
	 * @param string $folder The folder for which to return a folder pane
	 */
	function getFolderPane($folder)
	{
		// Get the "backup method"
		jpimport('models.registry', true);

		$profile =& JoomlapackModelRegistry::getInstance();
		$method = $profile->get('backupMethod');

		// Import Joomla! folder utility functions
		jimport('joomla.filesystem.path');
		jimport('joomla.filesystem.folder');

		// Construct the fully qualified pathname
		$folder = trim($folder, DS);
		$folder = trim($folder, '/');
		$path = JPATH_ROOT.DS.$folder;
		
		//$path = JFolder::makeSafe($path);
		if(!JFolder::exists($path))
		{
			return "<p>".JText::_('SFF_ERROR_INVALIDFOLDER');
		}

		// Get the subfolders of this folder
		$folders = JFolder::folders($path,'.',false,false,array('.','..'));

		// Calculate parent folder
		//$thisPath = JPath::clean($path); // Cleanup current path
		$thisPath = $path;
		$thisPath = rtrim($thisPath, DS); // Trim trailing slashes
		$lastDS = strrpos($thisPath, DS); // Find last occurence of DS
		$upPath = substr($thisPath,0,$lastDS); // Copy the part up to the previous directory
		$aboveRoot = strlen($upPath) < strlen(JPath::clean(JPATH_ROOT)); // We shouldn't go ABOVE the root!
		$upPath = str_replace(JPath::clean(JPATH_ROOT),DS,$upPath);
		$upPath = DS.trim($upPath, DS);

		// Initialize output
		$txt_folder = JText::_('SFF_LABEL_SUBDIRECTORIES');
		$out = <<<ENDHTML
<table class="adminlist">
	<thead>
		<tr>
			<th>$txt_folder</th>
		</tr>
	</thead>
	<tbody>
ENDHTML;

		// Add a parent folder entry if it's not the root folder
		if(!$aboveRoot)
		{
			$urlbase = JURI::base().'/index.php?option=com_joomlapack&view=sff&folder=';
			if($method == 'ajax')
			{
				$href = 'javascript:folderpane(\''.addslashes($upPath).'\');';
			}
			else
			{
				$href = $urlbase.urlencode($upPath);
			}
			$htmlDir = JText::_('SFF_LABEL_GOUP');
			$out .= <<<ENDHTML
		<tr>
			<td>
				<a href="$href">$htmlDir</a>
			</td>
		</tr>
ENDHTML;
		}

		// Do we have subfolders?
		if(empty($folders) && $aboveRoot )
		{
			// No, warn user
			$out .= "<p>".JText::_('SFF_ERROR_NOSUBDIRECTORIES').'</p>';
		}
		elseif(!empty($folders))
		{
			jpimport('models.def',true);
			$defmodel = new JoomlapackModelDef();
				
			$urlbase = JURI::base().'/index.php?option=com_joomlapack&view=sff&folder=';
			foreach($folders as $dir)
			{
				if($method == 'ajax')
				{
					$href = 'javascript:folderpane(\''.addslashes($folder.DS.$dir).'\');';
				}
				else
				{
					$href = $urlbase.urlencode($folder.DS.$dir);
				}

				$htmlDir = htmlentities($dir);

				$style = '';
				$htmlExcluded = '';
				$myPath = trim($folder.DS.$dir, DS);
				if($defmodel->isSetFor($myPath))
				{
					$style = 'style="color: #333333; font-style: italic"';
					$href='#';
					$htmlExcluded='<span style="color: #663333; font-style: normal">'.JText::_('SFF_LABEL_EXCLUDED').'</span>';
					$html = '<span '.$style.'>'.$htmlDir.' '.$htmlExcluded.'</span>';
				}
				else
				{
					$html = '<a href="'.$href.'" '.$style.'>'.$htmlDir.' '.$htmlExcluded.'</a>';
				}

				$out .= <<<ENDHTML
		<tr>
			<td>
			$html
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
	 * Returns the HTML for the file pane of the specified folder
	 *
	 * @param string $folder The folder for which to return a file pane
	 */
	function getFilePane($folder)
	{
		// Get the "backup method"
		jpimport('models.registry', true);

		$profile =& JoomlapackModelRegistry::getInstance();
		$method = $profile->get('backupMethod');

		$folder = trim($folder, DS);
		$folder = trim($folder, '/');

		// Load the model
		if(!class_exists('JoomlapackModelSff'))
		{
			jpimport('models.sff',true);
		}
		$model = new JoomlapackModelSff();

		// Import Joomla! folder utility functions
		jimport('joomla.filesystem.folder');

		// Construct the fully qualified pathname
		$path = JPATH_ROOT.DS.$folder;
		//$path = JFolder::makeSafe($path);
		if(!JFolder::exists($path))
		{
			return "<p>".JText::_('SFF_ERROR_INVALIDFOLDER');
		}

		// Get the subfolders of this folder
		$files = JFolder::files($path);

		// Initialize output
		$txt_files = JText::_('SFF_LABEL_FILES');
		$out = <<<ENDHTML
<table class="adminlist">
	<thead>
		<tr>
			<th>$txt_files</th>
		</tr>
	</thead>
	<tbody>
ENDHTML;

		// Do we have files?
		if(empty($files))
		{
			// No, warn user
			$out .= "<p>".JText::_('SFF_ERROR_NOFILES').'</p>';
		}
		else
		{
			$urlbase = JURI::base().'/index.php?option=com_joomlapack&view=sff&folder='.urlencode($folder).'&task=toggle&file=' ;
			foreach($files as $file)
			{
				$myPath = trim($folder.DS.$file, DS);
				if($method == 'ajax')
				{
					$href = 'javascript:toggle(\''.addslashes($myPath).'\');';
				}
				else
				{
					$href = $urlbase.urlencode($file);
				}
				$htmlFile = htmlentities($file);
				// Make excluded files red and bold
				if($model->isSetFor($myPath))
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
				<a href="$href" $style>$htmlFile</a>
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
	 * Outputs the folder and file panes to the browser
	 *
	 */
	function renderPageArea()
	{
		// Get the "backup method"
		jpimport('models.registry', true);

		$profile =& JoomlapackModelRegistry::getInstance();
		$method = $profile->get('backupMethod');

		// Decide what the panes' initial content is going to be
		if($method == 'ajax')
		{
			$folderContent = "&nbsp;";
			$fileContent = "&nbsp;";
			$folder = '/';
		}
		else
		{
			$folder = JRequest::getVar('folder','','default','none',1);
			$folderContent = JoomlapackHelperSff::getFolderPane($folder);
			$fileContent = JoomlapackHelperSff::getFilePane($folder);
		}

		// Render the table
		?>
<h4><?php echo JText::_('SFF_LABEL_CURRENTDIRECTORY'); ?> <span
	id="currentdirectory"><?php echo $folder; ?></span></h4>
<table border="0" cellspacing="10" width="100%">
	<tr>
		<td id="folderpane" valign="top" width="40%"><?php echo $folderContent; ?>
		</td>
		<td id="filepane" valign="top"><?php echo $fileContent; ?></td>
	</tr>
</table>
		<?php
		// If we are on AJAX mode, include a startup script to populate the panes
		if($method == 'ajax')
		{
			?>
<script language="JavaScript" type="text/javascript">
	folderpane('/');
</script>
			<?php
		}
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
			JoomlapackHelperSff::_renderAJAXJavaScript();
		}
		else
		{
			JoomlapackHelperSff::_renderRedirectsJavaScript();
		}
	}
}
