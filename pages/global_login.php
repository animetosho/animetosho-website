<?php
if(!defined('AT_ROOT')) exit;

if(!$AT->post_request)
	$AT->output->error('Authorization mismatch.', 'Authorization error', AT_Output::ERROR_FORBIDDEN);

if(!$AT->user->isMember())
{
	// login failed
	if($AT->input->action == 'login') {
		// form error
		// hack: create form object here as it's included too early in prepage
		require_once AT_ROOT.'includes/output_form.php';
		$form = new AT_Output_Form($AT->input);
		$form->errors[0] = 'Username/password combination not found.  Login failed.  Please try again.';
	} else {
		$AT->output->redirect(AT::buildUrl('login', '', array('loginfailed' => 1), false));
	}
}
else
{
	if(!isset($AT->user->logged_in_now) || !$AT->user->logged_in_now)
	{
		// someone trying to trick us...  well, lets trick them :P
		$AT->output->error('You are already logged in.', '', AT_Output::ERROR_FORBIDDEN);
	}
	else
	{
		// login success
		$AT->output->addNotice('You have successfully logged in.', AT_Output::NOTICE_SUCCESS);
	}
}
?>