<?php
if(!defined('AT_ROOT')) exit;

// must be logged in
$AT->user->requireLogin();

// check postkey
if(!$AT->validPostKey($AT->input->postkey, $AT->user->uid))
	$AT->output->error('Invalid request - the sent postkey is either incorrect, or has expired.  If you intentionally wanted to perform this action, try going back and try again.', 'Authorisation key mismatch', AT_Output::ERROR_FORBIDDEN);

if($AT->user->data['emailverified'])
	$AT->output->error('Your email address has already been verified.', 'Activation already done', AT_Output::ERROR_FORBIDDEN);

if(!$AT->user->data['email'])
	$AT->output->error('You need to set an email address before an activation email can be sent out.', 'No email address set', AT_Output::ERROR_FORBIDDEN);

// all good?!
$AT->user->sendEmailVerification($AT->db);

$AT->output->addNotice('Your email activation message has been sent (it may take a few minutes to arrive in your inbox).', AT_Output::NOTICE_SUCCESS);
?>