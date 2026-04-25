<?php
if(!defined('AT_ROOT')) exit;

// must be logged in
$AT->user->requireLogin();

// check postkey
if(!$AT->validPostKey($AT->input->postkey, $AT->user->uid))
	$AT->output->error('Invalid request - the sent postkey is either incorrect, or has expired.  If you intentionally wanted to perform this action, try going back and try again.', 'Authorisation key mismatch', AT_Output::ERROR_FORBIDDEN);

// clear cookie
$AT->user->clearToken();

// redirect
$AT->output->redirect($AT->buildUrl('home'), 'You have successfully logged out.  You will be redirected to the home page.');
//$AT->output->addNotice('You have successfully logged out', AT_Output::NOTICE_SUCCESS);
?>