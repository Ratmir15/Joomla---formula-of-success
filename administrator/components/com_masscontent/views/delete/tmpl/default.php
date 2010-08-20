<?php defined('_JEXEC') or die('Restricted access'); ?>
<script language="javascript" type="text/javascript">
     
    
		function submitbutton(pressbutton) {			
			if (pressbutton=='delete'){			
				if (confirm("<?php echo JText::_("DELETE_ALL");?>"))
					submitform( pressbutton );			
				else return;	
			}
			else 	submitform( pressbutton );			
		}
	  </script>
      <h1><?php echo JText::_("DELETE MASS CONTENT");?></h1>
	  <form action="index2.php?act=deleteMassContent" method="post" name="adminForm" id="adminForm" class="adminForm">
      
           <script language="javascript" type="text/javascript">   

        
    		var sectioncategories = new Array;
    		<?php
    		$i = 0;
    		foreach ($this->lists['sectioncategories'] as $k=>$items) {
    			foreach ($items as $v) {
    				echo "sectioncategories[".$i++."] = new Array( '$k','".addslashes( $v->id )."','".addslashes( $v->name )."' );\t";
    			}
    		}
    		?>

            </script>                
                <fieldset>      
                    <legend><?php echo JText::_("DELETE SECTIONS AND CATEGORIES");?></legend> 
                    <table border="0" cellpadding="3" cellspacing="0">
                        <tr>
                            <td colspan="3"><?php echo JText::_("DESTROY_ALL");?></td>
                        </tr>
                        <tr>                   
                            <td><?php echo  JText::_("SECTION")." ". $this->lists['sectionid']; ?></td>
                            <td><input type="checkbox"  id="deleteSection" name="deleteSection" ><?php echo JText::_("DELETE_SECTIONS");?></td>  
                            <td></td>
                            <td></td>
                        </tr>            
                        <tr>
                            <td><?php echo  JText::_("CATEGORY")." ".$this->lists['catid']; ?></td>
                            <td><input type="checkbox"  id="deleteCategory" name="deleteCategory"><?php echo JText::_("DELETE_CATEGORIES");?></td>  
                            <td><input type="checkbox"  id="allCat" name="allCat"><?php echo JText::_("SELECT_ALL_CAT");?></td>                           
                        </tr>   
                        <tr>
                            <td><?php echo JText::_("CONTENT");?></td>
                            <td><input type="checkbox"  id="deleteContentOnly" name="deleteContentOnly"><?php echo JText::_("DELETE_ONLY_CONTENT");?></td>                             
                        </tr> 
                    </table>
                </fieldset>                
            <!--    <fieldset>      
                    <legend>Delete content</legend> 
                    <table border="0" cellpadding="3" cellspacing="0">
                        <tr>
                        <td></td>
                        </tr>            
                    </table>
                </fieldset>         -->      
            <input type="hidden" name="task" value="" >
            <input type="hidden" name="id" value="" >
            <input type="hidden" name="controller" value="delete" >
            <input type="hidden" name="option" value="<?php echo $option; ?>" >                   
        </form>