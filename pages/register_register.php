<?php
if(!defined('AT_ROOT')) exit;

if(!$AT->post_request)
	$AT->output->error('Authorization mismatch.', 'Authorization error', AT_Output::ERROR_FORBIDDEN);

if($_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.0')
	$AT->output->error('It appears that you may be trying to register through a proxy. To prevent spam, this has been blocked.', 'Spam prevention', AT_Output::ERROR_FORBIDDEN);

$AT->input->parse(array(
	'username' => AT_Input::TYPE_STR,
	'password' => AT_Input::TYPE_STR,
	'password2' => AT_Input::TYPE_STR,
	'agree' => AT_Input::TYPE_BOOL,
	
	'timezone' => AT_Input::TYPE_FLOAT,
	'email' => AT_Input::TYPE_STR,
	
	'captcha' => AT_Input::TYPE_STR,
	'captcha_hash' => AT_Input::TYPE_STR
));

$newuser = array(
	'username' => $AT->input->username,
	'pwd' => $AT->input->password,
	'pwd2' => $AT->input->password2,
	
	'timezone' => $AT->input->timezone,
	'email' => $AT->input->email
);

$form->errors = AT_Users::validateUser($newuser);
$form->errors['password'] = $form->errors['pwd'];
$form->errors['password2'] = $form->errors['pwd2'];
unset($form->errors['pwd'], $form->errors['pwd2']);

$form->errors['agree'] = ($AT->input->agree ? '' : 'Sorry, you must agree to our ToS to register an account.');
if(empty($invite) && !AT_Input::verifyCaptcha($AT->cache, $AT->db, $AT->input->captcha_hash, $AT->input->captcha))
	$form->errors['captcha'] = 'Image verification code incorrect - please try again.';
else
	$form->errors['captcha'] = '';

// prelim username availability check (may still have issues with multi-threading)
if(!$form->errors['username'])
{
	if(AT_Users::usernameExists($AT->db, $AT->input->username))
		$form->errors['username'] = 'Sorry, the selected username has already been taken.  Please try another username.';
}

if(!AT::arrayContainsTrue($form->errors))
{
	// all validated?  attempt an insert (will fail if there's a dupe username (note that this is the only way to stop issues with multi-threading))
	
	// adjust some things first
	$newuser['timezone_offset'] = $newuser['timezone'];
	unset($newuser['pwd2'], $newuser['timezone']);
	
	$email_verified = false;
	if(!empty($invite)) {
		$email_verified = ($newuser['email'] == $invite['email']);
		$newuser['emailverified'] = $email_verified;
		$newuser['accesslevel'] = $invite['accessgrant'];
		$newuser['invitedby'] = $invite['uid'];
	}
	
	$newuser = AT_User::createNew($AT->db, $newuser);
	
	if($newuser)
	{
		log_activity($newuser->uid);
		
		// log this person in
		$newuser->sendToken(false);
		
		
		// remove invitation
		if(!empty($invite)) {
			$invite['dateline_accepted'] = TIME_NOW;
			$invite['inviteduid'] = $newuser->uid;
			$AT->db->insert('invites_expired', $invite);
			$AT->db->delete('invites', '`code`='.$AT->db->escape($invite['code']));
		}
		
		
		// send email verification if necessary
		if(!$email_verified && $newuser->data['email']) {
			$newuser->sendEmailVerification($AT->db);
		}
		
		$AT->output->redirect($AT->buildUrl('usercp'), 'Thank you for registering at '.SITE_NAME.'!  You will be redirected to your new User Control pannel.');
		AT_Shutdown();
	}
	else
	{
		// probably a duplicate username
		// check this
		if(AT_Users::usernameExists($AT->db, $AT->input->username))
			$form->errors['username'] = 'Sorry, the selected username has already been taken.  Please try another username.';
		else
			$AT->output->error('Unknown error occurred!', 'Unknown error', AT_Output::ERROR_SERVER);
	}
}
else
{
	// unset a few things so that the $form object doesn't output what we've entered
	$AT->input->password = $AT->input->password2 = '';
	//unset($AT->input->password, $AT->input->password2, $AT->input->captcha);
}
?>