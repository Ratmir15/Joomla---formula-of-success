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
?>

<form action="index.php" method="post" name="adminForm">
<div id="editcell">

<table border="0" width="100%">
<tr>
	<td width="50%" valign="top">

	<table class="adminlist">
	<tr>
		<td colspan="2">
			<h3><?php echo JText::_( "Server Configuration" ); ?></h3>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo JText::_( "Hostname" ); ?>
		</td>
		<td>
			<?php 
				if ($this->items['hostname'] == "") {
					$this->items['hostname'] = "localhost";
				}
			?>
			<input type="text" name="hostname" value="<?php echo $this->items['hostname'];?>" />
		</td>
	</tr>
    <tr>
        <td align="right">
            <?php echo JText::_( "Database Name" ); ?>
        </td>
        <td>
			<input type="text" name="dbname" value="<?php echo $this->items['dbname'];?>" />
        </td>
    </tr>
    <tr>
        <td align="right">
            <?php echo JText::_( "UserName" ); ?>
        </td>
        <td>
            <input type="text" name="username" value="<?php echo $this->items['username'];?>" />
        </td>
    </tr>
    <tr>
        <td align="right">
            <?php echo JText::_( "Password" ); ?>
        </td>
        <td>
			<input type="password" name="password" value="<?php echo $this->items['password'];?>" />
        </td>
    </tr>
    <tr>
        <td align="right">
            <?php echo JText::_( "Prefix" ); ?>
        </td>
        <td>
			<?php 
				if ($this->items['prefix'] == "") {
					$this->items['prefix'] = "jos_";
				}
			?>
            <input type="text" name="prefix" value="<?php echo $this->items['prefix'];?>" />
        </td>
	</tr>
	<tr>
		<td colspan="2">
			<h3><?php echo JText::_( "Migration Configuration" ); ?></h3>
		</td>
	</tr>

	<tr>
		<td align="right">
			<?php echo JText::_( "Make Backup?" ); ?>
		</td>
		<td>
			<?php echo $this->lists['backup']; ?>
		</td>
	</tr>
    <tr>
        <td align="right">
            <?php echo JText::_( "Migrate Groups?" ); ?>
        </td>
		<td>
			<?php echo $this->lists['groups']; ?>
        </td>
    </tr>
    <tr>
        <td align="right">
            <?php echo JText::_( "Migrate Users?" ); ?>
        </td>
		<td>
			<?php echo $this->lists['users']; ?>
        </td>
    </tr>
    <tr>
        <td align="right">
            <?php echo JText::_( "Migrate Sections?" ); ?>
        </td>
		<td>
			<?php echo $this->lists['sections']; ?>
        </td>
    </tr>
    <tr>
        <td align="right">
            <?php echo JText::_( "Migrate Categories?" ); ?>
        </td>
		<td>
			<?php echo $this->lists['categories']; ?>
        </td>
    </tr>
    <tr>
        <td align="right">
            <?php echo JText::_( "Migrate Content?" ); ?>
        </td>
		<td>
			<?php echo $this->lists['content']; ?>
        </td>
	</tr>
	<tr>
		<td align="right">
			<?php echo JText::_( "Migrate Frontpage?" ); ?>
		</td>
		<td>
			<?php echo $this->lists['frontpage']; ?>
		</td>
	</tr>
    <tr>
        <td align="right">
            <?php echo JText::_( "Migrate Menus?" ); ?>
        </td>
		<td>
			<?php echo $this->lists['menus']; ?>
        </td>
    </tr>
    <tr>
        <td align="right">
            <?php echo JText::_( "Migrate Modules?" ); ?>
        </td>
		<td>
			<?php echo $this->lists['modules']; ?>
        </td>
    </tr>
    <tr>
        <td align="right">
            <?php echo JText::_( "Migrate Polls?" ); ?>
        </td>
		<td>
			<?php echo $this->lists['polls']; ?>
        </td>
    </tr>
    <tr>
        <td align="right">
            <?php echo JText::_( "Migrate Weblinks?" ); ?>
        </td>
		<td>
			<?php echo $this->lists['weblinks']; ?>
        </td>
    </tr>
    <tr>
        <td align="right">
            <?php echo JText::_( "Migrate Contacts?" ); ?>
        </td>
        <td>
            <?php echo $this->lists['contacts']; ?>
        </td>
    </tr>

	</table>

	</td>
	<td valign="top">

	<table class="adminlist">

		<tr>
			<td colspan="2">
				<h3><?php echo JText::_( "3rd Party Extensions" ); ?></h3>
			</td>
		</tr>
	    <tr>    
	        <td align="right">
	            <?php echo JText::_( "Artio JoomSEF" ); ?>
	        </td>   
	        <td>        
	            <?php
	                echo $this->lists['ext_aj'];

	                if ($this->ext['aj'] != "com_sef") {
	                    echo "<div style=\"float: right; color: red; \">" . JText::_( "Artio JoomSEF Uninstalled" ) . "</div>";
	                }else{
	                    echo "<div style=\"float: right; color: blue; \">" . JText::_( "Artio JoomSEF Installed" ) . "</div>";
	                }
	            ?>

	        </td>
	    </tr>
	    <tr>
	        <td align="right">
	            <?php echo JText::_( "Community Builder" ); ?>
	        </td>
			<td>
				<?php 
					echo $this->lists['ext_cb']; 

					if ($this->ext['cb'] != "com_comprofiler") {
						echo "<div style=\"float: right; color: red; \">" . JText::_( "CB Uninstalled" ) . "</div>";
					}else{
						echo "<div style=\"float: right; color: blue; \">" . JText::_( "CB Installed" ) . "</div>";
					}
				?>

	        </td>
		</tr>

	    <tr>
	        <td align="right">
	            <?php echo JText::_( "DocMan" ); ?>
	        </td>
	        <td>
	            <?php
	                echo $this->lists['ext_dm'];

	                if ($this->ext['dm'] != "com_docman") {
	                    echo "<div style=\"float: right; color: red; \">" . JText::_( "DocMan Uninstalled" ) . "</div>";
	                }else{
	                    echo "<div style=\"float: right; color: blue; \">" . JText::_( "DocMan Installed" ) . "</div>";
	                }
	            ?>

	        </td>
	    </tr>

	    <tr>
	        <td align="right">
	            <?php echo JText::_( "FacileForms" ); ?>
	        </td>
	        <td>
	            <?php
	                echo $this->lists['ext_ff'];

	                if ($this->ext['ff'] != "com_facileforms") {
	                    echo "<div style=\"float: right; color: red; \">" . JText::_( "FacileForms Uninstalled" ) . "</div>";
	                }else{
	                    echo "<div style=\"float: right; color: blue; \">" . JText::_( "FacileForms Installed" ) . "</div>";
	                }
	            ?>

	        </td>
	    </tr>
	    <tr>    
	        <td align="right">
	            <?php echo JText::_( "FireBoard" ); ?>
	        </td>   
	        <td>
	            <?php
	                echo $this->lists['ext_fb'];

	                if ($this->ext['fb'] != "com_fireboard") {
	                    echo "<div style=\"float: right; color: red; \">" . JText::_( "FireBoard Uninstalled" ) . "</div>";
	                }else{
	                    echo "<div style=\"float: right; color: blue; \">" . JText::_( "FireBoard Installed" ) . "</div>";
	                }
	            ?>

	        </td>
	    </tr>
	    <tr>    
	        <td align="right">
	            <?php echo JText::_( "FireBoard to Kunena" ); ?>
	        </td>   
	        <td>
	            <?php
	                echo $this->lists['ext_ku'];

	                if ($this->ext['ku'] != "com_kunena") {
	                    echo "<div style=\"float: right; color: red; \">" . JText::_( "Kunena Uninstalled" ) . "</div>";
	                }else{
	                    echo "<div style=\"float: right; color: blue; \">" . JText::_( "Kunena Installed" ) . "</div>";
	                }
	            ?>

	        </td>
	    </tr>	    
	    <tr>
	        <td align="right">
	            <?php echo JText::_( "JomComment" ); ?>
	        </td>
	        <td>
	            <?php
	                echo $this->lists['ext_jc'];

	                if ($this->ext['jc'] != "com_jomcomment") {
	                    echo "<div style=\"float: right; color: red; \">" . JText::_( "JomComment Uninstalled" ) . "</div>";
	                }else{
	                    echo "<div style=\"float: right; color: blue; \">" . JText::_( "JomComment Installed" ) . "</div>";
	                }
	            ?>

	        </td>
	    </tr>
	    <tr>    
	        <td align="right">
	            <?php echo JText::_( "Letterman" ); ?>
	        </td>   
	        <td>
	            <?php
	                echo $this->lists['ext_lm'];

	                if ($this->ext['lm'] != "com_letterman") {
	                    echo "<div style=\"float: right; color: red; \">" . JText::_( "Letterman Uninstalled" ) . "</div>";
	                }else{
	                    echo "<div style=\"float: right; color: blue; \">" . JText::_( "Letterman Installed" ) . "</div>";
	                }
	            ?>

	        </td>
	    </tr>
	    <tr>
	        <td align="right">
	            <?php echo JText::_( "Virtuemart" ); ?>
	        </td>
	        <td>
	            <?php
	                echo $this->lists['ext_vm'];

	                if ($this->ext['vm'] != "com_virtuemart") {
	                    echo "<div style=\"float: right; color: red; \">" . JText::_( "VM Uninstalled" ) . "</div>";
	                }else{
	                    echo "<div style=\"float: right; color: blue; \">" . JText::_( "VM Installed" ) . "</div>";
	                }
	            ?>

	        </td>
	    </tr>


	</table>


	</td>
</tr>
</table>



</div>

<input type="hidden" name="option" value="com_mtwmigrator" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="controller" value="config" />
<?
	if ($this->items['ext_cb'] == 1) {
		echo "<input type=\"hidden\" name=\"groups\" value=\"1\" />";
		echo "<input type=\"hidden\" name=\"users\" value=\"1\" />";
	}						
?>

</form>
