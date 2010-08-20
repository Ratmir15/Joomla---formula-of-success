<?php defined('_JEXEC') or die('Restricted access'); 


		
    ?>

	  <script language="javascript" type="text/javascript">
                function copyTitle(){
            
            if (document.getElementById("duplicateTitle").checked){                            
                for (i=1;i<<?php echo $this->params->get('nbMassCategories')+1; ?>;i++){
                    if (document.getElementById("alias_"+i).value==""){
                        document.getElementById("alias_"+i).value= document.getElementById("title_"+i).value;
                    }
                }
            }
            else {                               
                for (i=1;i<<?php echo $this->params->get('nbMassCategories')+1; ?>;i++){
                    if (document.getElementById("alias_"+i).value==document.getElementById("title_"+i).value){
                        document.getElementById("alias_"+i).value= "";
                    }
                }
            }            
        }  
       
		function submitbutton(pressbutton) {
			var form = document.adminForm;

            if ((form.addMenu.checked) && (form.menuselect.value == '')) {
				alert(  "<?php  echo  JText::_("PLEASE SELECT A MENU TYPE");?>" );
                return;
             }
            else if ((form.addMenu.checked) && (form.link_type.value == '')) {
				alert( "<?php  echo  JText::_("PLEASE SELECT A MENU");?>" );
                return;                
            }
            else{  
                submitform( pressbutton );
            }    
		}
	  </script>
      <h1><?php  echo  JText::_("MASS CATEGORIES");?></h1>
	  <form action="index2.php?act=createMassCategories" method="post" name="adminForm" id="adminForm" class="adminForm">
      
               <script language="javascript" type="text/javascript">   

        
    		
    		var menulist = new Array;
    		<?php
			
			//sub menus
			 $i = 0;	
			 $top=0;
			foreach ( $this->lists['menulist']  as $k=>$items) {   
  				$top=0; 
    			foreach ($items as $v) {
					if ($top==0)
					{
						echo "menulist[".$i++."] = new Array( '".addslashes( $v->menutype)."','-1','Top' );\t";
						$top=1;
					}
    				echo "menulist[".$i++."] = new Array( '".addslashes( $v->menutype )."','".addslashes( $v->id )."','".str_replace('&nbsp;',' ',addslashes( $v->treename ))."' );\t";
			}		
			}			
    		?>

            </script>        
        
            <table border="0" cellpadding="3" cellspacing="0" >
            <tr>
                <td>
                <fieldset>      
                    <legend><?php echo  JText::_("CREATE UP TO")." ".$this->params->get('nbMassCategories')." ".  JText::_("CATEGORIES IN A ROW"); ?></legend> 
                    <table border="0" cellpadding="3" cellspacing="0">

                    <?php 
					$k = 0;
					for ($i=1;$i< $this->params->get('nbMassCategories')+1;$i++) { ?>
                        <tr bgcolor="<?php echo($k==0)?"#f9f9f9":"#eeeeee";?>">
                            <td><?php echo JText::_("CATEGORY")." ".$i; ?>: <?php echo JText::_("TITLE");?></td>
                            <td><input class="inputbox" type="text" size="25" maxlength="255" id="title_<?php echo $i; ?>" name="title[]" value="" ></td>
                            <td><?php echo JText::_("ALIAS");?></td>
                            <td><input class="inputbox" type="text" size="25" maxlength="255" id="alias_<?php echo $i; ?>" name="alias[]" value="" ></td>
                       </tr>
                    <?php $k = 1 - $k;
						} ?>             
        
                    </table>
                </fieldset>
                </td>        
                <td valign="top">
                <fieldset>
                   <legend><?php echo JText::_("OPTIONS");?></legend>
                    <table border="0" cellpadding="3" cellspacing="0">
                        <tr>
                            <td><?php echo JText::_("COPY TITLE TO ALIAS");?></td>
                            <td><input type="checkbox"  id="duplicateTitle" name="duplicateTitle" onClick="javascript:copyTitle()" ></td>
                        </tr>         				
                        <tr>
        					<td><?php echo JText::_("SECTION");?></td>
        					<td colspan="2"><?php echo $this->lists['section']; ?></td>
        				</tr>                    
                        <tr>
                            <td><?php echo JText::_("ACCESS LEVEL");?></td>
                            <td><?php echo $this->lists['access']; ?>             
                        <tr>
        				<tr>
        					<td><?php echo JText::_("PUBLISHED");?></td>
        					<td><?php echo $this->lists['published']; ?></td>
        				</tr>                      
                        <tr>
                            <td><input type="checkbox" name="addMenu" ><?php echo JText::_("LINK TO MENU");?></td>
                            <td><?php echo $this->lists['menuselect']; ?><?php echo $this->lists['menuselect3']; ?></td>
                        </tr>                         
                        <tr>
                             <td ><?php echo JText::_("SELECT MENU TYPE");?></td>
						<td><?php echo $this->lists['link_type']; ?></td>
					<tr>
                    </table>
                </fieldset>
                </td>
            </tr>
            </table>
            <input type="hidden" name="task" value="" >
            <input type="hidden" name="id" value="" >
            <input type="hidden" name="option" value="<?php echo $option;?>" >
            <input type="hidden" name="scope" value="<?php echo $row->scope; ?>" >                  
			<input type="hidden" name="controller" value="categories" />
        </form>