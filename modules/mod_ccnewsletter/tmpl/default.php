<?php
/*
 * ccNewsletter Default Controller
 * @author Chill Creations <extensions@chillcreations.com>
 * @link http://www.chillcreations.com
 * @license GNU/GPL
*/
// no direct access
defined('_JEXEC') or die('Restricted access');
/* Import newsletter css */
JHTML::stylesheet('newsletter.css', JURI::base() . '/modules/mod_ccnewsletter/assets/');

$unsubscribe = $parameters['unsubscribe_button'];
$terms = $parameters['showterm_text'];


JHTML::_('behavior.modal', 'a.modal');
$id = $parameters['article'];
$cid='index.php?option=com_content&amp;view=article&id='.$id.'&tmpl=component';


if($subscribe_flag == 'u')
{
?>
	<div class="mainnewsletter">
	<p><?php echo $parameters['introduction']; ?></p>
	<br/>
	<form action="<?php echo JRoute::_( 'index.php?option=com_ccnewsletter&view=unsubscribe', true, 0); ?>" method="post" name="subscribeForm" id="subscribeForm">
		<input type="hidden" name="email" id="email"  value="<?php echo $email; ?>" />
		<center><input class="button" type="submit" name="submit" value="<?php echo $parameters['unsubscribe']; ?>" /></center>
		<div class="clr"></div>
		<input type="hidden" name="option" value="com_ccnewsletter" />
		<input type="hidden" name="task" value="removeSubscriberByEmail" />
	</form>
	<br/>
	</div>
<?php
}
else
{
?>
	<script type="text/javascript">
	function formsubmit(task)
	{
		var form = document.subscribeFormModule;
		if(task == "addSubscriber")
		{
			if(form.name.value== "")
			{
				alert('<?php echo $parameters['namewarning']; ?>');
			}
			else if(form.email.value == "")
			{
				alert('<?php echo $parameters['emailwarning']; ?>');
			}
			else if(( form.email.value.search("@") == -1) || ( form.email.value.search("[.*]" ) == -1 ))
			{
				alert('<?php echo $parameters['emailwarning']; ?>');
			}

			<?php if($parameters['showterm'])
			{
			?>
			else if(form.terms_condition_ch.checked == false)
			{
				alert ('<?php echo $parameters['terms_cond_warn']; ?>');

			}
			<?php
			}
			?>

			else
			{
				form.task.value = "addSubscriber";
				form.submit();
			}
		}
		else if(task == "removeSubscriberByEmail")
		{
			if(form.email.value == "")
			{
				alert('<?php echo $parameters['emailwarning']; ?>');
			}
			else if(( form.email.value.search("@") == -1) || ( form.email.value.search("[.*]" ) == -1 ))
			{
				alert('<?php echo $parameters['emailwarning']; ?>');
			}
			else
			{
				form.task.value = "removeSubscriberByEmail";
				form.submit();
			}
		}

	}
	</script>
<?php
	/* Import css and js for appropriate stye selected from parameter */
	if($parameters['style']== 'mootools')
	{
		JHTML::script('mootools.js', JURI::base() .'/media/system/js/');
?>
		<script type="text/javascript">
		window.addEvent('load', function(){
			var mySlide = new Fx.Slide('subscribelayout');

			mySlide.hide();

			$('toggle').addEvent('click', function(e){
				e = new Event(e);
				mySlide.toggle();
				e.stop();
			});
		});
		</script>
	    <div class="mainnewsletter">
		<p><?php echo $parameters['introduction'] ?></p>
		<div class="newsletterbutton">
			<p><a id="toggle" href="#"><?php echo $title; ?></a></p>
		</div>
		<div id="subscribelayout">
			<form action="<?php echo JURI::root();?>index.php?option=com_ccnewsletter&amp;view=ccnewsletter" method="post" name="subscribeFormModule" id="subscribeFormModule">
			<p><div>
				<?php echo $parameters['name']; ?>:

			<input type="text" name="name" id="name" size="15" maxlength="250" value="<?php echo $name; ?>" />
			</div>
			</p>
			<p>
			<div>
				<?php echo $parameters['email']; ?>:

			<input type="text" name="email" id="email" size="15" maxlength="250" value="<?php echo $email; ?>" />
			</div>
			</p>

			<?php if($parameters['showterm'])
			{
			?>
			<p>
			<input id="ccnewsletter" name="terms_condition_ch" class="inputbox" type="checkbox">
			<a style="font-size:14px;color:#4E4E51;margin:0px 0px 0px 5px;text-decoration:none;" href='<?php echo $cid; ?>' class="modal" rel="{handler: 'iframe', size: {x: 700, y: 375}}"><?php echo $terms;?></a>
			</p>
			<?php
			}
			 ?>

			<?php
			if($unsubscribe)
			{
			?>
			<p>
			<input class="button"  type="button" id="addSubscriber" name="addSubscriber" value="<?php echo $parameters['subscribe']; ?>"  onclick="formsubmit('addSubscriber');"/>
			<input class="button" type="button" id="removeSubscriberByEmail" name="removeSubscriberByEmail" value="<?php echo $parameters['unsubscribe']; ?>"  onclick="formsubmit('removeSubscriberByEmail');"/>
			</p>
			<?
			}
			else
			{
			?>
			<p><input class="button"  type="button" id="addSubscriber" name="addSubscriber" value="<?php echo $parameters['subscribe']; ?>"  onclick="formsubmit('addSubscriber');"/></p>
			<?php
			}
			?>
			<input type="hidden" name="option" value="com_ccnewsletter" />
			<!--<input type="hidden" name="task" value="addSubscriber" />-->
			<input type="hidden" name="task" value="" />
			</form>
		</div>
	</div>
<?php
	}
	else if($parameters['style']== 'highslide')
	{
		JHTML::script('highslide-with-html.js', JURI::base() .'/modules/mod_ccnewsletter/assets/');
		JHTML::stylesheet('highslide.css', JURI::base() . '/modules/mod_ccnewsletter/assets/');
?>
		<script type="text/javascript">
		    hs.graphicsDir = '<?php echo JURI::base() ?>/modules/mod_ccnewsletter/assets/graphics/';
		    hs.outlineType = 'rounded-white';
		    hs.outlineWhileAnimating = true;
		</script>

		<div class="mainnewsletter">
		<p><?php echo $parameters['introduction'] ?></p>
		<div class="newsletterbutton_highslide">
			<p><a href="#" onclick="return hs.htmlExpand(this, { contentId: 'highslide-html' } )" class="highslide"><?php echo $title; ?></a></p>
		</div>
		<br/>
		<div class="highslide-html-content" id="highslide-html">
			<div class="highslide-header">
		  	<table border="0" width="100%">
			<tr>
			<td width="50%"></td>
			  <td width="20%">
				<div class="highslide-move" >
				  <a href="#" onclick="return false"><?php echo $parameters['move']; ?></a>
				</div>
			  </td>
			  <td width="28%">
				<div class="highslide-close" style="float:left;" >
				 <a href="#" onclick="return hs.close(this)"><?php echo $parameters['close']; ?></a>
				</div>
			  </td>
            </tr>
           </table>
			</div>
			<div class="highslide-body">
			<form action="<?php echo JURI::root();?>index.php?option=com_ccnewsletter&amp;view=ccnewsletter" method="post" name="subscribeFormModule" id="subscribeFormModule">
				<p><div>
					<?php echo $parameters['name']; ?>:

				<input type="text" name="name" id="name" size="15" maxlength="250" value="<?php echo $name; ?>" />
				</div>
				</p>
				<p>
				<div>

					<?php echo $parameters['email']; ?>:

				<input type="text" name="email" id="email" size="15" maxlength="250" value="<?php echo $email; ?>" />
				</div>
				</p>

				<?php if($parameters['showterm'])
				{
				?>
				<p>
					<input id="ccnewsletter" name="terms_condition_ch" class="inputbox" type="checkbox">
					<a style="font-size:14px;color:#4E4E51;margin:0px 0px 0px 5px;text-decoration:none;" href='<?php echo $cid; ?>' class="modal" rel="{handler: 'iframe', size: {x: 700, y: 375}}"><?php echo $terms;?></a>
				</p>

				<?php
				}
				if($unsubscribe)
				{
				?>
				<p>
				<input class="button" type="button" id="addSubscriber" name="addSubscriber" value="<?php echo $parameters['subscribe']; ?>"  onclick="formsubmit('addSubscriber');"/>
				<input class="button" type="button" id="removeSubscriberByEmail" name="removeSubscriberByEmail" value="<?php echo $parameters['unsubscribe']; ?>"  onclick="formsubmit('removeSubscriberByEmail');"/>
				</p>
				<?php
				}
				else
				{
				?>
					<P>
					<center><input class="button" type="button" id="addSubscriber" name="addSubscriber" value="<?php echo $parameters['subscribe']; ?>"  onclick="formsubmit('addSubscriber');"/></center>
					</p>
				<?php
				}
				?>
				<input type="hidden" name="option" value="com_ccnewsletter" />
				<!--<input type="hidden" name="task" value="addSubscriber" />-->
				<input type="hidden" name="task" value="" />
				</form>
		</div>
	    <div class="highslide-footer">
	        <div>
	            <span class="highslide-resize" title="Resize">
	                <span></span>
	            </span>
	        </div>
	    </div>
	</div>
	</div>
<?php
	}
	else
	{
?>
	<div class="mainnewsletter">
		<p><?php echo $parameters['introduction'] ?></p>
		<div class="normalsublayout">
		<form action="<?php echo JURI::root();?>index.php?option=com_ccnewsletter&amp;view=ccnewsletter" method="post" name="subscribeFormModule" id="subscribeFormModule">
			<p><div>
				<?php echo $parameters['name']; ?>:

			<input type="text" name="name" id="name" size="15" maxlength="250" value="<?php echo $name; ?>" />
			</div>
			</p>
			<p>
			<div>
				<?php echo $parameters['email']; ?>:

			<input type="text" name="email" id="email" size="15" maxlength="250" value="<?php echo $email; ?>" />
			</div>
			</p>
			<?php if($parameters['showterm'])
			{
			?>
			<p>
			<input id="ccnewsletter" name="terms_condition_ch" class="inputbox" type="checkbox">
			<a style="font-size:14px;color:#4E4E51;margin:0px 0px 0px 5px;text-decoration:none;" href='<?php echo $cid; ?>' class="modal" rel="{handler: 'iframe', size: {x: 700, y: 375}}"><?php echo $terms;?></a>
			</p>
			<?php
			}
			if($unsubscribe)
			{
			?>
			<p>
				<input class="button" type="button" id="addSubscriber" name="addSubscriber" value="<?php echo $parameters['subscribe']; ?>"  onclick="formsubmit('addSubscriber');"/>
				<input class="button" type="button" id="removeSubscriberByEmail" name="removeSubscriberByEmail" value="<?php echo $parameters['unsubscribe']; ?>"  onclick="formsubmit('removeSubscriberByEmail');"/>
			</p>

			<?php
			}
			else
			{
			?>
				<p><center><input class="button" type="button" id="addSubscriber" name="addSubscriber" value="<?php echo $parameters['subscribe']; ?>"  onclick="formsubmit('addSubscriber');"/></center></p>
				<!--<p><center><input  type="image" src="images/back_f2.png" id="addSubscriber" name="addSubscriber" value="<?php echo $parameters['subscribe']; ?>"  onclick="formsubmit('addSubscriber');"/></center></p>-->
			<?php
			}
			?>
			<input type="hidden" name="option" value="com_ccnewsletter" />
			<!--<input type="hidden" name="task" value="addSubscriber" />-->
			<input type="hidden" name="task" value="" />
			</form>
		</div>
	</div>
<?php
	}
}
?>