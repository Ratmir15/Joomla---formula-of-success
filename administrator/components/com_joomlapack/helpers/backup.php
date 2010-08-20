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
 * HTML rendering helper for the Backup page
 *
 */
class JoomlapackHelperBackup extends JObject
{
	/**
	 * Gets the HTML for the pretty backup process pane
	 *
	 * @param array $array The CUBE Array
	 */
	function getBackupProcessHTML( $array )
	{
		if( count($array) <= 0 ) {
			$domain = 'finale';
		} else {
			$domain = $array['Domain'];
			$step = $array['Step'];
			$substep = $array['Substep'];
		}

		// Find current domain's index
		switch( $domain )
		{
			case 'init':
				$currentDomainIndex = 0;
				break;
			case 'installer':
				$currentDomainIndex = 1;
				break;
			case 'PackDB':
				$currentDomainIndex = 3;
				break;
			case 'Packing':
				$currentDomainIndex = 4;
				break;
			case 'finale':
			default:
				$currentDomainIndex = 6;
				break;
		}

		// Now, make an array indicating in what state each domain is
		$domainDisplayArray = array();
		$domainDisplayArray[] = JoomlapackHelperBackup::_makeStepArrayEntry(JText::_('BACKUP_LABEL_DOMAIN_PACKDB'), 3, $currentDomainIndex, false);
		$domainDisplayArray[] = JoomlapackHelperBackup::_makeStepArrayEntry(JText::_('BACKUP_LABEL_DOMAIN_PACKING'), 4, $currentDomainIndex, false);
		$domainDisplayArray[] = JoomlapackHelperBackup::_makeStepArrayEntry(JText::_('BACKUP_LABEL_DOMAIN_FINISHED'), 5, $currentDomainIndex, false);
		$gridHTML = '';

		foreach( $domainDisplayArray as $dispArray )
		{
			$class = ($dispArray['class'] == '') ? '' : 'class="' . $dispArray['class'] . '"';
			$imageLink = ($dispArray['pic'] == '') ? '' : '<img src="'.JURI::base().'components/com_joomlapack/assets/images/' . $dispArray['pic'] . '" />';
			$gridHTML .= "\t\t\t<tr $class>\n";
			$gridHTML .= "\t\t\t\t<td>$imageLink</td>\n";
			$gridHTML .= "\t\t\t\t<td>" . $dispArray['label'] . "</td>\n";
			$gridHTML .= "\t\t\t</tr>\n";
		}

		// Create the last response information
		jimport('joomla.utilities.date');
		$dateNow = new JDate('now');
		$user =& JFactory::getUser();
		$userTZ = $user->getParam('timezone',0);
		$dateNow->setOffset($userTZ);
		$lastResponseLabel = JText::_('BACKUP_TEXT_LASTRESPONSE');
		$lastResponseStamp = $dateNow->toFormat('%H:%M:%S').' GMT'.($userTZ == 0 ? '' : ($userTZ > 0 ? '+'.$userTZ : $userTZ) );
		
		// Populate the warnings area
		$warningsHTML = '';
		if( count($array['Warnings']) > 0 )
		{
			$warningsHTML = "\t\t<div class=\"warnings\">\n\t\t\t" .
				"<span>".JText::_('BACKUP_LABEL_WARNINGS')."</span>\n".
				"\t\t\t<ul>\n";
			foreach($array['Warnings'] as $warning) {
				$warningsHTML .= "\t\t\t\t<li>$warning</li>\n";
			}
			$warningsHTML .= "\t\t\t</ul>\n\t\t</div>";
		}
		
		$out = <<<ENDXXX1
	<div class="sitePack">
		<table class="stepstable" align="center">
			<thead>
				<tr>
					<th width="16"></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
		$gridHTML
			</tbody>
		</table>
		
		<div class="status">
			<p id="JPStep">$step</p>
			<p id="JPSubstep">$substep</p>
		</div>
$warningsHTML		
		<div class="jplastresponse">
			<p>$lastResponseLabel $lastResponseStamp</p>
		</div>
	</div>
ENDXXX1;

		return $out;
	}

	/**
	 * A helper function to populate the $domainDisplayArray which ultimately generated the progress
	 * grid output. This is called once for each domain displayed in the grid.
	 *
	 * @param string $label Text label of the domain
	 * @param int $domainID The unique numeric ID of the domain
	 * @param int $activeDomainID The unique numeric ID of the currently active domain
	 * @param bool $isError Set to true if this domain has failed
	 * @return array
	 * @access private
	 * @since 1.2.b1
	 */
	function _makeStepArrayEntry($label, $domainID, $activeDomainID, $isError = false)
	{
		$ret = array();

		// Get the class name and picture for the domain
		if($domainID < $activeDomainID) {
			$ret['pic'] = 'ok_small.png';
			$ret['class'] = 'ok';
		} elseif( $domainID == $activeDomainID ) {
			$ret['pic'] = 'arrow_small.png';
			$ret['class'] = 'active';
		} else {
			$ret['pic'] = '';
			$ret['class'] = '';
		}

		if($isError) {
			$ret['pic'] = 'error_small';
			$ret['class'] = 'error';
		}

		$ret['label'] = $label;

		return $ret;
	}
}