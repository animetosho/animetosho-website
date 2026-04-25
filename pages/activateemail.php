<?php
if(!defined('AT_ROOT')) exit;

$AT->input->parse(array(
	'uid' => AT_Input::TYPE_INT,
	'code' => AT_Input::TYPE_STR,
));

if(!$AT->input->uid)
	$AT->output->error('Invalid UserID supplied.', 'Invalid user', AT_Output::ERROR_BADREQ);
if(!ctype_xdigit($AT->input->code) || strlen($AT->input->code) != 32)
	$AT->output->error('Invalid code supplied.', 'Invalid code', AT_Output::ERROR_BADREQ);

$newemail = $AT->db->selectGetField('users_verify_email', 'email', '`uid`='.$AT->input->uid.' AND `hash`='.$AT->db->escapeHexBin($AT->input->code).' AND `dateline`>'.(TIME_NOW-AT::EMAILVERIFY_EXPIRE));
if(!$newemail)
	$AT->output->error('Invalid user/code supplied, or verification link is out of date.  If you intended to verify this email address, please resend the activation email.', 'Verification failed', AT_Output::ERROR_FORBIDDEN);

// verification found
$AT->db->delete('users_verify_email', 'uid='.$AT->input->uid);

if($AT->input->uid == $AT->user->uid)
{
	if($AT->user->data['emailverified'])
		$AT->output->error('Your email address has already been verified.', 'Already verified', AT_Output::ERROR_FORBIDDEN);
	$AT->user->updates['emailverified'] = 1;
	$AT->user->updates['email'] = $newemail;
	$AT->user->commitUpdates($AT->db);
	$AT->output->redirect($AT->buildUrl('usercp'), 'Your email has successfully been verified - you may now receive notifications from '.SITE_NAME.' via email!  You will be redirected to your UserCP.');
}
else
{
	$AT->db->update('users', array('emailverified' => 1, 'email' => $newemail), 'uid='.$AT->input->uid);
	$AT->output->redirect($AT->buildUrl('login'), 'The email has been verified, however you are not logged in.  You will be redirected to the login page.');
}
?>