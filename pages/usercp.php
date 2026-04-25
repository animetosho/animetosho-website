<?php
if(!defined('AT_ROOT')) exit;


// currently, just a basic CP


$AT->output->title = 'User Control Panel';
$AT->output->head_metas['robots'] = 'noindex';

$AT->output->printHeader(0);
$AT->output->printTitleAndDesc('User Control Panel', '');


require_once AT_ROOT.'includes/output_table.php';
$table = new AT_Output_Table_2ColFrm;


$form->start($AT->buildUrl('usercp'), $AT->postKey);
$table->start('Account Settings', 'Note that you need to supply your current password to authorize changes in your password or email.');
$table->row('Current Password:',
	$form->textboxS('password', '', true, 50, 25, null, 'if(t.length<5)return"Password too short.";'),
	'', true);
$table->row('New Password:',
	$form->textboxS('new_password', '', true, 50, 25, null, 'if(t && t.length<5)return"Password too short.";'),
	'If you wish to change your password, please enter a value here (and the field below).  If you do not wish to change your password, just leave this field blank.  Your password must be between 5 and 50 characters long.');
$table->row('Confirm Password:',
	$form->textboxS('new_password2', '', true, 50, 25, null, 'if(t && t.length<5)return"Password too short.";if($id("register_password").value && t!=$id("register_password").value)return"Passwords don\'t match.";'),
	'');
$table->row('Email:',
	$form->textboxS('email', $AT->user->data['email'], false, 220, 50, null, 'if(t && t.replace(/^[0-9a-zA-Z.\-_]+@[0-9a-zA-Z.\-_]+\.[a-zA-Z]+$/,""))return"Invalid email supplied.";'),
	($AT->user->data['email'] ? 'You may choose to remove your email address by making this field blank.  If you decide to change your email address here, please note that it will be verified before any other mail is sent to the address.  Your current email is '.($AT->user->data['emailverified'] ? '<span style="color: #00A000;">verified</span>':'<span style="color: #A00000;">not verified</span> [<a href="'.AT::buildUrl('usercp', '', array('postkey' => $AT->postKey, 'do' => 'sendactivation')).'">resend activation email</a>]').'.' : 'Note that this email address will be verified.'));

$table->end();

$table->printStdFooter($form, 'updateacc', 'Update Account Settings');
$form->end();

// timezone, dst, date/time format
$form->start($AT->buildUrl('usercp'), $AT->postKey);
$table->start('Account Options');
$table->row('Timezone:',
	$form->timezoneListS('timezone', $AT->user->data['timezone_offset']),
	'Specifying your timezone allows dates and times on '.SITE_NAME.' to be displayed correctly for you.');
$table->row('Tag Line:',
	$form->textboxS('tagline', $AT->user->data['tagline'], false, 500, 50),
	'Displayed next to your name in comments. Express yourself!');
$table->row('Display Picture:',
	($AT->user->data['avatar'] ? '<div>'.$form->checkboxS('use_avatar', (bool)$AT->user->data['avatar'], 'Show display picture in comments').'</div>' : '')
	.$form->fileS('avatar', 25),
	'Only PNG/JPEG images sized 6KB or less, up to 100x100 in dimension, are accepted. Upload a picture of your kids here!');
$table->row('Listing Blacklist:',
	$form->textareaS('disp_blacklist', $AT->user->data['disp_blacklist'], 3, 50),
	'You can choose to hide certain items from listings by specifying a list of blacklisted terms here. Put each term on its own line. Note that this affects all listings, is case insensitive and can partially match words - for example, &quot;[some group]&quot; will hide all items containing &quot;[Some Group]&quot; but will have no effect on items with &quot;[some_group]&quot;. Also note that terms must be at least 4 characters long, and you can have up to 30 terms.');

$table->end();
$table->printStdFooter($form, 'updateopt', 'Update Account Options');
$form->end();

$AT->output->printFooter();

?>