<?php
if(!defined('AT_ROOT')) exit;

if(!$AT->post_request)
	$AT->output->error('Authorization mismatch.', 'Authorization error', AT_Output::ERROR_FORBIDDEN);

$AT->input->parse(array(
	'password' => AT_Input::TYPE_STR,
	'password2' => AT_Input::TYPE_STR,
));

// verify new incomming passwords
$form->errors = AT_Users::validateUser(array('pwd' => $AT->input->password, 'pwd2' => $AT->input->password2));

$form->errors['password'] = $form->errors['pwd'];
$form->errors['password2'] = $form->errors['pwd2'];
unset($form->errors['pwd'], $form->errors['pwd2']);


if(!AT::arrayContainsTrue($form->errors))
{
	// let's do the update!
	$user = AT_User::createFromUid($AT->db, $AT->input->uid);
	if(empty($user))
		$AT->output->error('Specified user not found!', 'Invalid user', AT_Output::ERROR_BADREQ);
	
	$user->updates['pwd'] = $AT->input->password;
	
	$user->commitUpdates($AT->db);
	// send new token over
	$user->sendToken(); // don't remember
	
	// remove all reset requests for this user
	$AT->db->delete('users_verify_pwdreset', '`uid`='.$user->uid); // .' AND `hash`='.$AT->db->escapeHexBin($AT->input->code)
	
	$AT->output->redirect($AT->buildUrl('usercp'), 'Your password has been changed successfully.  You are now logged in, and will be redirected to your UserCP.');
	AT_Shutdown();
}

?>