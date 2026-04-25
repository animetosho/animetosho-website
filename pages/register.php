<?php
if(!defined('AT_ROOT')) exit;

$AT->output->title = 'Register Account';
$AT->output->head_metas['robots'] = 'noindex';

$AT->output->printHeader(0);
$AT->output->printTitleAndDesc('Register New Account', 'Only a username and password is required - no email verification is necessary, but you might still want to put a legit address in so that we can send you <s>spam</s> quality information about enlarging certain body organs.');


require_once AT_ROOT.'includes/output_table.php';
$table = new AT_Output_Table_2ColFrm;

$form->start($AT->buildUrl('register'), $AT->postKey, 'register', 'if(!$id("register_agree").checked){alert("You must choose to agree with our ToS to proceed with registration."); return false;}');
$table->start('Required Fields');
$table->row('Username:',
	$form->textboxS('username', '', false, 25, 25, null, 'if(t.length<3)return"Username too short.";if(t.replace(/^[a-z0-9_\\-()]+$/i,"") || !t.replace(/^[0-9]+$/,""))return"Invalid username.";'),
	'Must be between 3 and 25 characters long, and may only contain letters, numbers and the underscore, hyphen and parentheses characters.  Your username cannot be a registered trademark unless you want to be sued.', true);
$table->row('Password:',
	$form->textboxS('password', '', true, 50, 25, null, 'if(t.length<5)return"Password too short.";'),
	'Your password must be between 5 and 50 characters long.  Note that a password reset is only available to members who opt to enter an email address (below)', true);
$table->row('Confirm Password:',
	$form->textboxS('password2', '', true, 50, 25, null, 'if(t.length<5)return"Password too short.";if(t!=$id("register_password").value)return"Passwords don\'t match.";'),
	'', true);
$table->row('Agreement:',
	$form->checkboxS('agree', false, ' I hereby grant my soul, monies, personal belongings, ideas, services, children and goldfish to the site owner.  Either that, or I promise to do so sometime in the future.'), '', true);

$hiddenfields = array();
if(empty($invite)) {
	$table->captchaRow($form, (isset($form->errors['captcha']) && !$form->errors['captcha']));
	
}
else
	$hiddenfields['invite'] = $AT->input->invite;

$table->end();

$table->printStdFooter($form, 'register', 'Submit Registration', $hiddenfields);


$table->start('Other Fields');
$table->row('Email:',
	$form->textboxS('email', (empty($invite) ? '' : $invite['email']), false, 220, 50, null, 'if(t && t.replace(/^[0-9a-zA-Z.\-_]+@[0-9a-zA-Z.\-_]+\.[a-zA-Z]+$/,""))return"Invalid email supplied.";'),
	'<b>Email functionality is currently disabled, so this info will not do anything.</b><br/><s>Note that this email address will be verified (if not already).</s>');
$table->row('Timezone:',
	$form->timezoneListS('timezone'),
	'Specifying your timezone allows dates and times on '.SITE_NAME.' to be displayed correctly for you.');

$table->end();

$form->end();
$AT->output->printFooter();

?>