<?php
/**
 * mtwMigrator
 *
 * @author      Matias Aguirre
 * @email       maguirre@matware.com.ar
 * @url         http://www.matware.com.ar/
 * @license             GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');

$version = "v0.2.1";

?>
<style>
/* standard form style table */
table.cpanel_about {
	background-color: #F7F8F9;
	border: solid 1px #d5d5d5;
	width: 100%;
	padding: 10px;
	border-collapse: collapse;
}
table.cpanel_about tr.row0 {
	background-color: #F7F8F9;
}
table.cpanel_about tr.row1 {
	background-color: #eeeeee;
}
table.cpanel_about th {
	font-size: 15px;
	font-weight:normal;
	font-variant:small-caps;
	padding-top: 6px;
	padding-bottom: 2px;
	padding-left: 4px;
	padding-right: 4px;
	text-align: left;
	height: 25px;
	color: #666666;
	background: url(../images/background.gif);
	background-repeat: repeat;
}
table.cpanel_about td {
	padding: 3px;
	text-align: left;
	border: 1px;
	border-style:solid;
	border-bottom-color:#EFEFEF;
	border-right-color:#EFEFEF;
	border-left-color:#EFEFEF;
	border-top-color:#EFEFEF;	
}

table.cpanel_icon {
	background-color: #F7F8F9;
	border: solid 1px #d5d5d5;
	width: 100%;
	padding: 5px;
}
table.cpanel_icon td {
	padding: 5px;
	text-align: center;
	border: 1x;
	border-style: solid;
	border-bottom-color:#EFEFEF;
	border-right-color:#EFEFEF;
	border-left-color:#EFEFEF;
	border-top-color:#EFEFEF;
}
.cpanel_icon td:hover {
	background-color: #B5CDE8;
	border:	1px solid #30559C;
}
</style>
<table class="cpanel_about">
<tr class="cpanel_about">
	<td width="50%" valign="top" class="cpanel_about">
    <table width="100%" class="cpanel_icon">
    <tr class="cpanel_icon">
    	<td align="center" height="100px" width="33%" class="cpanel_icon" colspan="3">
        	<a href="index2.php?option=com_mtwmigrator&amp;controller=config" style="text-decoration:none;">
            	<img src="templates/khepri/images/header/icon-48-config.png" align="middle" border="0"/><br />
				<?php echo JText::_( 'Configuration' ); ?>
            </a>
        </td>
        <td align="center" height="100px" width="33%" class="cpanel_icon">
            <a href="index2.php?option=com_mtwmigrator&amp;controller=migrate" style="text-decoration:none;">
            	<img src="templates/khepri/images/header/icon-48-install.png" align="middle" border="0" onClick="alert('This process can be take time. Patience.');" />
            	<br />
            	<?php echo JText::_( "Start Migration!") ;?>
            	</a>
        </td>
        <td align="center" height="100px" width="33%" class="cpanel_icon">
            <a href="index2.php?option=com_mtwmigrator&amp;controller=help" style="text-decoration:none;">
                <img src="templates/khepri/images/header/icon-48-help_header.png" align="middle" border="0"/>
                <br />
                <?php echo JText::_( "Help" ) ;?>
                </a>
        </td>
	</tr>
         </table>
      </td>
      <td width="50%" valign="top" align="center">
      <table border="1" width="100%" class="cpanel_about">
         <tr class="cpanel_about">
            <th class="cpanel" colspan="2">mtwMigrator Component</th></td>
         </tr>
         <tr class="cpanel_about"><td bgcolor="#FFFFFF" colspan="2"><br />
      <div style="width=100%" align="center">
      <img src="components/com_mtwmigrator/images/logo.png" align="middle" alt="mtwMigrator Logo"/>
      <br /><br /></div>  
      </td></tr>       
         <tr class="cpanel_about">
            <td width="120" bgcolor="#FFFFFF">Installed version:</td>
            <td bgcolor="#FFFFFF"><?php echo $version;?></td>
         </tr>
         <tr class="cpanel_about">
            <td bgcolor="#FFFFFF">Copyright:</td>
            <td bgcolor="#FFFFFF">&copy; 2007 2008 2009 <a href="http://www.matware.com.ar/">http://www.matware.com.ar/</a></td>
         </tr>		  
         <tr class="cpanel_about">
            <td bgcolor="#FFFFFF">License:</td>
            <td bgcolor="#FFFFFF"><a href="http://www.gnu.org/copyleft/gpl.html" target="_blank">GNU GPL</a></td>
         </tr>
      </table>
      </td>
   </tr>
</table>
