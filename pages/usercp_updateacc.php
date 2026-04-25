<?php
if(!defined('AT_ROOT')) exit;

if(!$AT->post_request)
	$AT->output->error('Authorization mismatch.', 'Authorization error', AT_Output::ERROR_FORBIDDEN);


$AT->input->parse(array(
	'password' => AT_Input::TYPE_STR,
	'new_password' => AT_Input::TYPE_STR,
	'new_password2' => AT_Input::TYPE_STR,
	'email' => AT_Input::TYPE_STR,
));



// validate inputs
$update_user = array();
if($AT->input->new_password) {
	$update_user['pwd'] = $AT->input->new_password;
	$update_user['pwd2'] = $AT->input->new_password2;
}
if($AT->input->got('email') && (!$AT->user->data['emailverified'] || strtolower($AT->input->email) != strtolower($AT->user->data['email'])))
	$update_user['email'] = $AT->input->email;

if(empty($update_user)) {
	$AT->output->error('You have selected to update nothing.  If you intended to change something, please go back and do so.', 'Nothing to do', AT_Output::ERROR_BADREQ);
}

$form->errors = AT_Users::validateUser($update_user);

if($AT->input->new_password) {
	$form->errors['new_password'] = $form->errors['pwd'];
	$form->errors['new_password2'] = $form->errors['pwd2'];
}
unset($form->errors['pwd'], $form->errors['pwd2']);

// validate current password here
if(!$AT->user->verifyPwd($AT->input->password)) {
	$form->errors['password'] = 'Incorrect password specified.';
}

if(!AT::arrayContainsTrue($form->errors))
{
	// all validated?
	unset($update_user['pwd2']);
	$AT->user->updates = $update_user;
	
	if(isset($update_user['email'])) {
		// set email to be invalid
		$AT->user->updates['emailverified'] = 0;
		// send verification email over
		$AT->user->data['email'] = $update_user['email'];
		$AT->user->sendEmailVerification($AT->db);
		// leave the last valid email in-tact, since password resets send emails regardless of it being validated
		if($AT->user->data['emailverified'])
			unset($AT->user->updates['email']);
		//else
		//	$AT->user->updates['email'] = '';
	}
	
	$AT->user->commitUpdates($AT->db);
	
	if(isset($update_user['pwd'])) // send new token
		$AT->user->sendToken(); // don't remember
	
	// mark success
	
	$AT->output->addNotice('Your settings have been successfully updated', AT_Output::NOTICE_SUCCESS);
}
else
{
	// unset a few things so that the $form object doesn't output what we've entered
	$AT->input->password = $AT->input->password2 = '';
	//unset($AT->input->password, $AT->input->password2);
}
?>