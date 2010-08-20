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

// Get throttling information
$registry =& JoomlapackModelRegistry::getInstance();

$description = JRequest::getVar('description');
$comment = JRequest::getVar('comment', '', 'default', 'string', JREQUEST_ALLOWHTML);
$comment = str_replace("\n", "", $comment);
$comment = str_replace("\r", "", $comment);
$comment = str_replace("\t", "", $comment);

?>
<h1><?php echo JText::_('BACKUP_HEADER_BACKINGUP'); ?></h1>
<p><?php echo JText::_('BACKUP_TEXT_BACKINGUP') ?></p>
<?php if($this->backupMethod == 'ajax'): ?>
<?php
jpimport('helpers.sajax', true);
sajax_init();
sajax_force_page_ajax('backup');
sajax_export('start', 'tick', 'renderProgress', 'addWarning');

$comment = str_replace("'", "\\"."'", $comment);

$baseURL = str_replace('\\','%5c', JURI::base().'index.php?option=com_joomlapack&view=backup&task=');
?>
<!-- AJAX-powered backup method -->
<script type="text/javascript" language="Javascript">
	/**
	 * (S)AJAX JavaScript
	 */
	<?php sajax_show_javascript(); ?>

	//sajax_debug_mode = 1;
	sajax_profiling = false;
	sajax_fail_handle = onInvalidData;
	sajax_junk_handle = onJunkData;

	var ojd_callback;
	var ojd_value;
	var ojd_extra_data;
		 
	/**
	 * JoomlaPack Backup Logic
	 */

	function onInvalidData(data)
	{
		error("Invalid AJAX Response:\n"+data);		
	}
	
	function onJunkData(junk_data, callback, value, extra_data)
	{
		ojd_callback = callback;
		ojd_value = value;
		ojd_extra_data = extra_data;
		x_addWarning(junk_data, onJunkData_callback);
	}
	
	function onJunkData_callback()
	{
		ojd_callback(ojd_value, ojd_extra_data);
	}

	function start()
	{
		x_start('<?php echo $description ?>', '<?php echo $comment ?>', route );
	}
	
	function tick()
	{
		x_tick( route );
	}
		
	function route( myRet )
	{
		var action = myRet[0];
		var message = myRet[1];
		
		if(action == 'step')
		{
			renderProgress();
		}
		
		if (action == 'error')
		{
			error(message);
		}
		
		if (action == 'finished')
		{
			finished(message);
		}
	}
	
	function renderProgress()
	{
		if(sajax_profiling) alert('Profiling data ready; review and press OK to continue with next backup step');
		x_renderProgress( renderProgress_callback );
	}
	
	function renderProgress_callback( myHTML )
	{
		document.getElementById('progress').innerHTML = myHTML;
		tick();
	}
	
	function finished(message)
	{
		document.location = '<?php echo $baseURL ?>finished';
	}
	
	function error(message)
	{
		// # Fix 2.4: POST data to the error page, instead of passing them as GET parameters
		document.getElementById('joomlapack_error_message').value = message;
		document.forms.errorform.submit();
	}
</script>

<form id="errorform" action="<?php echo JURI::base(); ?>index.php" method="post">
	<input type="hidden" name="option" value="com_joomlapack" />
	<input type="hidden" name="view" value="backup" />
	<input type="hidden" name="task" value="error" />
	<input type="hidden" name="message" value="" id="joomlapack_error_message" />
</form>

<div id="progress"></div>

<script type="text/javascript" language="Javascript">
	start();
</script>
	<?php else: ?>
<!-- Javascript Redirects backup method -->
	<?php
		$IFrameURL = str_replace('\\','%5c', JURI::base().'index.php?option=com_joomlapack&view=backup&format=raw&task=step&junk=');
		$comment = str_replace('"', "&quot;", $comment);
	?>
	<?php $baseURL = str_replace('\\','%5c', JURI::base().'index.php?option=com_joomlapack&view=backup&task='); ?>
<iframe id="RSIframe" name="RSIframe" width="100%"
	style="width: 100%; height: 350px; border: 1px"> </iframe>

<form id="errorform" action="<?php echo JURI::base(); ?>index.php" method="post">
	<input type="hidden" name="option" value="com_joomlapack" />
	<input type="hidden" name="view" value="backup" />
	<input type="hidden" name="task" value="error" />
	<input type="hidden" name="message" value="" id="joomlapack_error_message" />
</form>

<form name="start" id="start"
	action="<?php echo JURI::base(); ?>index.php" target="RSIframe"><input
	type="hidden" name="option" value="com_joomlapack" /> <input
	type="hidden" name="view" value="backup" /> <input type="hidden"
	name="task" value="start" /> <input type="hidden" name="format"
	value="raw" /> <input type="hidden" name="description"
	value="<?php echo $description; ?>" /> <input type="hidden"
	name="comment" value="<?php echo $comment; ?>" /></form>

<script type="text/javascript" language="Javascript">

	/*
	 * Makes a request through the IFrame
	 */
	function makeRequest(URL)
	{
		IFrameObj = document.getElementById('RSIframe');
		
		if (IFrameObj.contentDocument) {
			// For NS6
			IFrameDoc = IFrameObj.contentDocument; 
		} else if (IFrameObj.contentWindow) {
			// For IE5.5 and IE6
			IFrameDoc = IFrameObj.contentWindow.document;
		} else if (IFrameObj.document) {
			// For IE5
			IFrameDoc = IFrameObj.document;
		}
		
		IFrameDoc.location.replace(URL);
	}

	/*
	 * Handles the data passed through Javascript from the iFrame. It accepts 'step', 'error' and 'finished'
	 */
	function handleRequest(action, message)
	{
		if(action == 'step')
		{
			tick();
		}
		
		if (action == 'error')
		{
			error(message);
		}
		
		if (action == 'finished')
		{
			finished(message);
		}
	}
	
	function tick()
	{
		makeRequest('<?php echo $IFrameURL ?>'+Math.floor(Math.random()*32000));
	}
	
	function error(message)
	{
		//document.location = '<?php echo $baseURL ?>error&message=' + message;
		// # Fix 2.4: POST data to the error page, instead of passing them as GET parameters
		document.getElementById('joomlapack_error_message').value = message;
		document.forms.errorform.submit();
	}
	
	function finished()
	{
		document.location = '<?php echo $baseURL ?>finished';
	}
	
	function start()
	{
		document.forms.start.submit();
	}
	
	start();
</script>

	<?php endif; ?>