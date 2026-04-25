<?php
if(!defined('AT_ROOT')) exit;


// display output page
$AT->output->title = 'Reset Password';
$AT->output->head_metas['robots'] = 'noindex';

$AT->output->printHeader(0);
$AT->output->printTitleAndDesc('Reset Password', 'Your request to reset your password has been received and successfully verified.');


require_once AT_ROOT.'includes/output_table.php';
$table = new AT_Output_Table_2ColFrm;

$form->start($AT->buildUrl('resetpwd'), $AT->postKey, 'resetform');
$table->start('New Password');
$table->row('Password:',
	$form->textboxS('password', '', true, 50, 25, null, 'if(t.length<5)return"Password too short.";'),
	'Your password must be between 5 and 50 characters long.  Please make sure you choose a secure password which includes numbers and letters (note that passwords are CaSe SeNsItIvE).', true);
$table->row('Confirm Password:',
	$form->textboxS('password2', '', true, 50, 25, null, 'if(t.length<5)return"Password too short.";if(t!=$id("resetform_password").value)return"Passwords don\'t match.";'),
	'', true);
$table->end();

$form->hidden(array('uid' => $AT->input->uid, 'code' => $AT->input->code));
$table->printStdFooter($form, 'resetpwd', 'Reset Password');
$form->end();

$AT->output->printFooter();
?>