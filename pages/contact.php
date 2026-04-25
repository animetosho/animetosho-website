<?php
if(!defined('AT_ROOT')) exit;

$AT->output->title = 'Contact Page';

$AT->output->head_extra_html = '<script type="text/javascript" src="'.$AT->output->staticFileUrl('view.js').'"></script>';
$AT->output->printHeader(0);


$AT->output->printTitleAndDesc('Contact Page', 'Use this form to send any love confessions to the site admins, or similar commentary that you are too shy to express publicly. We promise not to make anything submitted below visible to the whole world, unless we feel like doing so.<br /><b>Note</b>: if you would like to receive a response, you\'ll need to provide contact details in the form of an email address (or other contact method which does not have extraneous terms of service agreements or registration requirements).');

?>

<br />

<div style="width: 100%;">
<?php

echo '<div id="comment_reply_placeholder_0"><div id="comment_reply_form">'; // weird way to put it, but required for our form hack
$form->start(AT::buildUrl('contact'), $AT->postKey, 'newcomment');

echo '<div id="view_comments_replybox">';

require_once AT_ROOT.'includes/output_table.php';
$table = new AT_Output_Table_2ColFrm;
$table->start('Send message');
if($AT->user->isMember()) {
	$table->spanRow($form->editorS('message', '', 6, 70, 'style="width: 100%;"', 'if(!t)return"You must enter a message to send.";if(t.length<2)return"Sorry, message must be at least 2 characters long.";if(t.length>5000)return"Message is too long - please keep it under 5000 characters!";'));
} else {
	$table->row('Name:', $form->textboxS('displayname', 'Anonymous', false, 40, 25), '');
	$table->row('Message:', $form->editorS('message', '', 6, 70, 'style="width: 100%;"', 'if(!t)return"You must enter a message to send.";if(t.length<2)return"Sorry, message must be at least 2 characters long.";if(t.length>5000)return"Message is too long - please keep it under 5000 characters!";'), '', true);
	$table->captchaRow($form, false, 'comment_captcha_row');
	?>
	<script type="text/javascript">
	<!--
		(showCaptcha = function() {
			if(!$id("newcomment_message").value.length) return;
			comment_captcha_row_show();
		})();
		$id("newcomment_message").onchange = showCaptcha;
		$id("newcomment_message").onkeypress = showCaptcha;
		$id("newcomment_message").onkeydown = showCaptcha;
		$id("newcomment_message").onkeyup = showCaptcha;
	//-->
	</script>
	<?php
}
$table->end();
$table->printStdFooter($form, 'comment', 'Post Message', array(), true);

echo '</div>';

$form->end();
?>
</div></div>
</div>

<?php
$AT->output->printFooter();



?>