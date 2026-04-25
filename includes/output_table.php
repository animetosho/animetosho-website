<?php

class AT_Output_Table_2ColFrm
{
	public static function start($title, $desc='')
	{
		?>
				<table class="twocolform_table">
					<thead>
						<tr><th colspan="2"><?php echo $title; ?></th></tr>
					</thead>
					<tbody>
						<?php if($desc) { ?><tr>
							<td class="form_cell_desc" colspan="2">
								<?php echo $desc; ?>
							</td>
						</tr><?php } ?>
		<?php
	}
	public static function end()
	{
		?>
					</tbody>
				</table>
		<?php
	}
	
	public static function row($title, $cell, $desc='', $required=false)
	{
		?>
						<tr>
							<td class="form_cell_left">
								<strong<?php echo ($required?' class="required_field"':''), '>', $title; ?></strong>
							</td>
							<td class="form_cell_right">
								<?php echo $cell; ?>
								<?php if($desc) echo '<small style="display: block;">', $desc, '</small>'; ?>
							</td>
						</tr>
		<?php
	}
	
	public static function spanRow($contents)
	{
		?>
						<tr>
							<td colspan="2">
								<?php echo $contents; ?>
							</td>
						</tr>
		<?php
	}
	
	public static function captchaRow(&$form, $autofill=false, $jshideid='')
	{
		global $AT;
		static $js_incl=false;
		assert('!isset($AT) || !$AT->user->isMember()'); // HHVM evaluates asserts in wrong scope
		$captcha = AT::generateCaptcha($AT->cache, $AT->db);
		if($autofill) {
			$form->hidden(array('captcha_hash' => $captcha[0], 'captcha' => $captcha[1]));
		} else {
			$AT->input->captcha = ''; // never remember captchas
			$salt = md5(uniqid(mt_rand(), true));
			
			if($jshideid) {
				?>
						<script type="text/javascript">
						<!--
							document.write('</tbody><tbody style="display: none;" id="<?php echo $jshideid; ?>">');
						//-->
						</script>
				<?php
			}
			?>
						<tr>
							<td class="form_cell_left">
<?php
			if(!$js_incl) {
				echo '		<script type="text/javascript" src="'.$AT->output->staticFileUrl('captcha.js').'"></script>';
				$js_incl = true;
			}
?>
								<strong class="required_field">Image Verification:</strong>
							</td>
							<td class="form_cell_right">
								<input type="hidden" id="<?php echo $form->id; ?>_captcha_hash" name="captcha_hash" value="<?php echo $captcha[0]; ?>" />
								<div id="<?php echo $form->id; ?>_captcha_container">
								<?php if($jshideid) echo '<noscript><div>'; ?>
									<img src="<?php echo INC_URL, '/captcha.php?h=', $captcha[0]; ?>" title="Captcha Image" alt="Verification image" id="<?php echo $form->id; ?>_captcha_image" />
								<?php if($jshideid) { ?>
									</div></noscript>
									<!-- hide the image by default to prevent Firefox and similar browsers loading the image -->
									<span id="<?php echo $jshideid; ?>_imgurl"></span>
								<?php } ?>
									<input type="button" value="Refresh Image" class="button" tabindex="999" onclick="AJS.x.refreshCaptcha('<?php echo $form->id; ?>');" style="vertical-align: top; margin-top: 10px; display: none;" id="<?php echo $form->id; ?>_captcha_refresh" />
									<br />
								</div>
								<?php echo $form->textboxS('captcha', '', false, 6, 10, 'onkeyup="if(this.value.length==6)this.onblur();"', 'if(t.length!=6)return"Code must be 6 characters long.";if(AJS.x.md5(t.toUpperCase(),50,this.md5salt)!=this.md5key)return"Invalid code.";this.readOnly=true;this.setAttribute("value",t);$id("'.$form->id.'_captcha_container").style.display=$id("'.$form->id.'_captcha_desc").style.display="none";$id("'.$form->id.'_captcha_passed").style.display="";'); ?>
								<script type="text/javascript">
								<!--
									$(document).ready(function(){
										$id('<?php echo $form->id; ?>_captcha').md5key="<?php echo AT::md5x($captcha[1], 50, $salt); ?>";
										$id('<?php echo $form->id; ?>_captcha').md5salt="<?php echo $salt; ?>";
									});
									$id('<?php echo $form->id; ?>_captcha_refresh').style.display = '';
									<?php if($jshideid) {
										echo 'function ', $jshideid, '_show() {
											if(!$id("',$jshideid,'").style.display) return;
											$id("',$jshideid,'").style.display = "";
											$id("',$jshideid,'_imgurl").innerHTML = \'<img src="', INC_URL, '/captcha.php?h=', $captcha[0],'" title="Captcha Image" alt="Verification image" id="',$form->id,'_captcha_image" />\';
										}';
									} ?>
								//-->
								</script>
								<small style="display: block;" id="<?php echo $form->id; ?>_captcha_desc">Our squiggly text game where the aim is to copy the image into the textbox.  All characters are upper case, and there are no zeros (0) and ones (1) in the above image.  Apparently bots aren't as good as humans at this game.</small>
								<div style="color: #00D000; font-weight: bold; font-style: italic; display: none;" id="<?php echo $form->id; ?>_captcha_passed">Image Verification Passed.</div>
							</td>
						</tr>
			<?php
			if($jshideid) {
				?>
						<script type="text/javascript">
						<!--
							document.write('</tbody><tbody>');
						//-->
						</script>
				<?php
			}
		}
	}
	
	public static function printStdFooter(&$form, $do, $submitlabel=null, $hiddenfields=array(), $resetButton=true)
	{
		$bm = ($resetButton ? 'endButtons':'submit');
		?>
				<div class="form_footer">
					<?php $form->$bm($submitlabel);
						$hiddenfields['do'] = $do;
						$form->hidden($hiddenfields);
					?>
				</div>
		<?php
	}
}

?>