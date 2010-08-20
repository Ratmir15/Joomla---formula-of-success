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

//print_r($this->status);
?>
<div id="tablecell">
    <table class="adminform">
    <tr>
        <th class="title">
            <h3><?php echo JText::_( 'Information' ); ?></h3>
        </th>
        <th class="title">
            <h3><?php echo JText::_( 'Status' ); ?></h3>
        </th>
    </tr>

    <tr class="row0">
        <td align="right">
            <?php echo JText::_( 'Backup old table' ); ?>
        </td>
        <td>
            <?php echo $this->errors['backup']; ?> 
        </td>
	</tr>
    <tr class="row1">
        <td align="right">
            <?php echo JText::_( 'Migrate Groups' ); ?>
        </td>
        <td>
			<?php echo $this->errors['groups']; ?>
        </td>
    </tr>
    <tr class="row0">
        <td align="right">
            <?php echo JText::_( 'Migrate Users' ); ?>
        </td>
        <td>
			<?php echo $this->errors['users']; ?>
        </td>
    </tr>
    <tr class="row1"> 
        <td align="right">
            <?php echo JText::_( 'Migrate Sections' ); ?>
        </td>   
        <td>
            <?php echo $this->errors['sections']; ?>    
        </td>
    </tr>
    <tr class="row0">
        <td align="right">
            <?php echo JText::_( 'Migrate Categories' ); ?>
        </td>
        <td>
            <?php echo $this->errors['categories']; ?>
        </td>
    </tr>
    <tr class="row1"> 
        <td align="right">
            <?php echo JText::_( 'Migrate Content' ); ?>
        </td>   
        <td>
            <?php echo $this->errors['content']; ?>    
        </td>
    </tr>
    <tr class="row0">
        <td align="right">
            <?php echo JText::_( 'Migrate Content Frontpage' ); ?>
        </td>
        <td>
            <?php echo $this->errors['frontpage']; ?>
        </td>
    </tr>
    <tr class="row1">
        <td align="right">
            <?php echo JText::_( 'Migrate Menus' ); ?>
        </td>
        <td>
            <?php echo $this->errors['menus']; ?> 
        </td>
    </tr>
    <tr class="row0">
        <td align="right">
            <?php echo JText::_( 'Migrate User Modules' ); ?>
        </td>
        <td>
            <?php echo $this->errors['modules']; ?>
        </td>
    </tr>
    <tr class="row1">
        <td align="right">
            <?php echo JText::_( 'Migrate Polls' ); ?>
        </td>
        <td>
            <?php echo $this->errors['polls']; ?>
        </td>
    </tr>
    <tr class="row0">
        <td align="right">
            <?php echo JText::_( 'Migrate Weblinks' ); ?>
        </td>
        <td>
            <?php echo $this->errors['weblinks']; ?>
        </td>
    </tr>
    <tr class="row1">
        <td align="right">
            <?php echo JText::_( 'Migrate Contacts' ); ?>
        </td>
        <td>
            <?php echo $this->errors['contacts']; ?>
        </td>
    </tr>
    <tr>
        <th class="title">
            <h3><?php echo JText::_( '3rd Party Extensions' ); ?></h3>
        </th>
        <th class="title">
            <h3><?php echo JText::_( 'Status' ); ?></h3>
        </th>
    </tr>
    <tr class="row0">
        <td align="right">
            <?php echo JText::_( 'Migrate Community Builder' ); ?>
        </td>
        <td>
            <?php echo $this->errors['ext_cb']; ?>
        </td>
	</tr>
    <tr class="row1">
        <td align="right">
            <?php echo JText::_( 'Migrate Virtuemart' ); ?>
        </td>
        <td>
            <?php echo $this->errors['ext_vm']; ?>
        </td>
    </tr>
    <tr class="row0">
        <td align="right">
            <?php echo JText::_( 'Migrate JomComment' ); ?>
        </td>
        <td>
            <?php echo $this->errors['ext_jc']; ?>
        </td>
    </tr>
    <tr class="row1">
        <td align="right">
            <?php echo JText::_( 'Migrate Docman' ); ?>
        </td>
        <td>
            <?php echo $this->errors['ext_dm']; ?>
        </td>
    </tr>
    <tr class="row0">
        <td align="right">
            <?php echo JText::_( 'Migrate FacileForms' ); ?>
        </td>
        <td>
            <?php echo $this->errors['ext_ff']; ?>
        </td>
    </tr>
    <tr class="row1">
        <td align="right">
            <?php echo JText::_( 'Migrate Artio JoomSEF' ); ?>
        </td>
        <td>
            <?php echo $this->errors['ext_aj']; ?>
        </td>
    </tr>
    <tr class="row0">
        <td align="right">
            <?php echo JText::_( 'Migrate FireBoard' ); ?>
        </td>
        <td>
            <?php echo $this->errors['ext_fb']; ?>
        </td>
    </tr>
    <tr class="row1">
        <td align="right">
            <?php echo JText::_( 'Migrate Kunena' ); ?>
        </td>
        <td>
            <?php echo $this->errors['ext_fb']; ?>
        </td>
    </tr>

    </table>

</div>


