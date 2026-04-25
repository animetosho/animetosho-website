<?php
if(!defined('AT_ROOT')) exit;

if(!$AT->post_request)
	$AT->output->error('Authorization mismatch.', 'Authorization error', AT_Output::ERROR_FORBIDDEN);


$AT->input->parse(array(
	'timezone' => AT_Input::TYPE_FLOAT,
	'tagline' => AT_Input::TYPE_STR,
	'use_avatar' => AT_Input::TYPE_BOOL,
	'disp_blacklist' => AT_Input::TYPE_STR,
));

// validate inputs
if($AT->input->disp_blacklist) {
	$AT->input->disp_blacklist = array_unique(explode("\n", str_replace("\r", '', $AT->input->disp_blacklist)));
	foreach($AT->input->disp_blacklist as $k => &$v) {
		if(mb_strlen($v) < 4)
			unset($AT->input->disp_blacklist[$k]);
	}
	if(count($AT->input->disp_blacklist) > 30) $AT->input->disp_blacklist = array_slice($AT->input->disp_blacklist, 0, 30);
	$AT->input->disp_blacklist = implode("\n", $AT->input->disp_blacklist);
} else
	$AT->input->disp_blacklist = '';

$AT->input->tagline = strtr($AT->input->tagline, array("\n"=>'', "\r"=>''));


$update_user = array('timezone' => $AT->input->timezone, 'tagline' => $AT->input->tagline, 'disp_blacklist' => $AT->input->disp_blacklist);
if(!empty($_FILES['avatar']) && @$_FILES['avatar']['name']) {
	$update_user['avatar'] = @file_get_contents($_FILES['avatar']['tmp_name'], false, null, 0, 6145); // read 6KB+1 to error out if too large
	@unlink($_FILES['avatar']['tmp_name']);
}
elseif(!$AT->input->use_avatar) $update_user['avatar'] = '';
$form->errors = AT_Users::validateUser($update_user);


if(!AT::arrayContainsTrue($form->errors))
{
	$update_user['timezone_offset'] = $update_user['timezone'];
	unset($update_user['timezone']);
	$AT->user->updates = $update_user;
	$AT->user->commitUpdates($AT->db);
	
	// mark success
	
	//$AT->output->redirect($AT->buildUrl('usercp'), 'Your settings have been successfully updated - you will be redirected back to your User CP.');
	//AT_Shutdown();
	$AT->output->addNotice('Your settings have been successfully updated', AT_Output::NOTICE_SUCCESS);
}
?>