<?php
if(!defined('AT_ROOT')) exit;

if(!$AT->post_request)
	$AT->output->error('Authorization mismatch.', 'Authorization error', AT_Output::ERROR_FORBIDDEN);

$find = $AT->input->get(AT_Input::TYPE_STR, 'name');
if(!$find)
	$form->errors['name'] = 'Please enter a name.';
else
{
	if(strpos($find, '@')) {
		if(!AT::isValidEmail($find))
			$form->errors['name'] = 'Invalid email address supplied.';
		else {
			// query DB
			// this will only find ONE account linked to the email address (will be the case in most circumstances)
			$user = AT_User::createFromDBArray($AT->db->selectGetArray('users', '`email`='.$AT->db->escape($find)));
			if(empty($user))
				$form->errors['name'] = 'Supplied email cannot be found.';
		}
	}
	else {
		if(AT_Users::findUsernameError($find))
			$form->errors['name'] = 'Invalid username supplied.';
		else {
			// get user
			$user = AT_User::createFromUsername($AT->db, $find);
			if(empty($user))
				$form->errors['name'] = 'Supplied username cannot be found.';
		}
	}
	
	if(!empty($user)) {
		// okay, we've found something
		//if(!$user->data['emailverified']) // we'll allow pwd resets to go thru even if the email isn't verified
		if(!AT::isValidEmail($user->data['email']))
			$AT->output->error('Sorry, this account doesn\'t have a valid email address attached to it, thus a reset password request cannot be sent.  If you have lost this account, you may try creating another one (be to remember your password and supply an email address in case you forget it).', 'Unable to send mail', AT_Output::ERROR_FORBIDDEN);
		else {
			// send a pwd reset email
			// but first, check table to see number of requests sent to prevent abuse
			if($AT->db->selectGetField('users_verify_pwdreset', 'COUNT(*)', '`uid`='.$user->uid.' AND `dateline`>'.(TIME_NOW-AT::PWDRESET_MAXTIME)) > AT::PWDRESET_MAX)
				$AT->output->error('Sorry, you have exceeded the maximum number of times you may send a password reset request.  Please try again at a later date.', 'Request Limit Exceeded', AT_Output::ERROR_FORBIDDEN);
			
			$hash = md5(uniqid(mt_rand(), true));
			$AT->db->insert('users_verify_pwdreset', array('uid' => $user->uid, 'hash' => pack('H*', $hash), 'dateline' => TIME_NOW), true);
			
			// delete old requests
			if(!mt_rand(0,9)) {
				$AT->db->suppressNotices(true);
				$AT->db->delete('users_verify_pwdreset', 'dateline<='.(TIME_NOW - AT::PWDRESET_MAXTIME));
				$AT->db->suppressNotices(true);
			}
			
			$reset_url = AT::buildUrl('resetpwd', '', array('uid' => $user->uid, 'code' => $hash));
			$user->sendEmail('Password Reset Request', 'You have been sent this message as you have requested to reset your password.  If you were not intending to receive this email, you may safely ignore it.  If you did intend to reset your password, please follow the link below:'."<br />\r\n<br />\r\n".'<a href="'.$reset_url.'">'.$reset_url.'</a>', true);
			
			$AT->output->redirect($AT->buildUrl('login'), 'Reset password request email has successfully been sent.  Please check your inbox for the reset email (it may take a few minutes to arrive).  You will be redirected to the home page.');
			
			AT_Shutdown();
		}
	}
}
?>