<?php
if(!defined('AT_ROOT')) exit;

$AT->output->title = 'Login';
$AT->output->head_metas['robots'] = 'noindex';

$AT->output->printHeader(0);
$AT->output->printTitleAndDesc('Login', 'Tired of being Anonymous?  The form below is your ticket to freedom.');


if($AT->input->get(AT_Input::TYPE_BOOL, 'loginfailed')) {
	$form->errors[0] = 'Username/password combination not found.  Login failed.  Please try again.';
}


require_once AT_ROOT.'includes/output_table.php';
$table = new AT_Output_Table_2ColFrm;

$form->start($AT->buildUrl('usercp'), $AT->postKey, 'loginform');
$table->start('Login Form');
$table->row('Username:',
	$form->textboxS('login_username', '', false, 25, 25, null, 'if(t.length<3)return"Username too short.";if(t.replace(/^[a-z0-9_\\-()]+$/i,"") || !t.replace(/^[0-9]+$/,""))return"Invalid username.";'), '', true);
$table->row('Password:',
	$form->textboxS('login_password', '', true, 50, 25, null, 'if(t.length<5)return"Password too short.";'),
	'', true);
$table->row('Remember:',
	$form->checkboxS('login_remember', false, 'Keep me logged in after browser close?'),
	'');
$table->end();

// if we arrived here via POST and there's an URL (ie, login error), stick it here too
if(isset($AT->input->login_url) && $AT->post_request)
	$ex_fields = array('login_url' => $AT->input->login_url);
else
	$ex_fields = array();

$table->printStdFooter($form, 'login', 'Login', $ex_fields);
$form->end();


$form->start($AT->buildUrl('login'), $AT->postKey, 'resetpasswordform');
$table->start('Reset Password Form', 'If you have forgotten your password, it\'s time to consider using your browser\'s remember password feature.  Note that password reset requests are <strong>only available to users who have attached an email address</strong> to their account.');
$table->row('Username or Email:',
	$form->textboxS('name', '', false, 220, 50, null, 'if(t.replace(/^[0-9a-zA-Z.\-_]+@[0-9a-zA-Z.\-_]+\.[a-zA-Z]+$/,"") && (t.length<3 || t.replace(/^[a-z0-9_\\-()]+$/i,"")))return"Invalid email or username supplied.";'),
	'Please enter your username (preferred) or email address here - instructions will be sent to the account\'s email address.', true);


$table->end();
$table->printStdFooter($form, 'resetpwd', 'Send Reset Password Email');
$form->end();

$AT->output->printFooter();

?>