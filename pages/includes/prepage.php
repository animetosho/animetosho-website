<?php
if(!defined('AT_ROOT')) exit;



if($AT->ajax_request)
	header('Content-Type: text/plain; charset=UTF-8');


/********** WORK AROUND FOR LOGINS (force login to be executed before prepage) *************/
if($inline_do == 'global_login') {
	require AT_ROOT.'pages/'.$inline_do.'.php';
	$inline_do = '';
}


/********** GENERIC HANDLERS *************/

$unreg = array(
	'register' => 1,
	'login' => 1
);
// pages which cannot be accessed when logged in
if(isset($unreg[$AT->input->action]))
{
	if($AT->input->action != 'login' || $AT->input->do != 'login') // special case for login_login
	{
		if($AT->user->isMember()) {
			// temporary hack
			if($AT->input->action == 'login')
				$AT->input->action = 'home';
			else
				$AT->output->error('You are already logged in.', '', AT_Output::ERROR_FORBIDDEN);
		}
	}
}

$reg = array(
	'usercp' => 1,
	'invite' => 1,
	'browse' => 1,
	'resolveq' => 1,
	'resolvedit' => 1,
);
if(isset($reg[$AT->input->action]))
{
	$AT->user->requireLogin();
}


$frm = array(
	'register' => 1,
	'login' => 1,
	'usercp' => 1,
	'invite' => 1,
	'resetpwd' => 1,
	'contact' => 1,
	'resolveq' => 1,
	'resolvedit' => 1,
);
// form pages
if(isset($frm[$AT->input->action]) && !isset($form))
{
	require_once AT_ROOT.'includes/output_form.php';
	$form = new AT_Output_Form($AT->input);
}




unset($unreg, $reg, $frm);


// common functions
require AT_ROOT.'pages/includes/funcs.php';


/********** SPECIFIC HANDLERS *************/

switch($AT->input->action)
{
	case 'register':
		// check num registrations from this IP
		
		
		
		
	break;
	case 'login':
		// check login attempts from this IP
		
		// also prevent password-reset abuse
		
		
		
		
	break;
	case 'resetpwd':
		$AT->input->parse(array(
			'uid' => AT_Input::TYPE_INT,
			'code' => AT_Input::TYPE_STR,
		));
		
		if(!$AT->input->uid)
			$AT->output->error('Invalid uid supplied.', 'Invalid user', AT_Output::ERROR_BADREQ);
		if(!ctype_xdigit($AT->input->code) || strlen($AT->input->code) != 32)
			$AT->output->error('Invalid code supplied.', 'Invalid code', AT_Output::ERROR_BADREQ);
		
		$data = $AT->db->selectGetArray('users_verify_pwdreset', '`uid`='.$AT->input->uid.' AND `hash`='.$AT->db->escapeHexBin($AT->input->code).' AND `dateline`>'.(TIME_NOW-AT::PWDRESET_EXPIRE));
		if(empty($data))
			$AT->output->error('Invalid user/code supplied, or verification link is out of date.  If you intended to reset your password, please resend the password reset request and follow up on the link within 24 hours.', 'Verification failed', AT_Output::ERROR_FORBIDDEN);
		
		
		
	break;
	case 'viewnyaa':
	case 'view':
		$sa = $AT->input->get(AT_Input::TYPE_STR, 'subaction');
		$tid_type = 't';
		if(($p=strpos($sa, '.')) !== false) {
			if(ctype_alpha(@$sa[$p+1])) {
				$tid = intval(substr($sa, $p+2));
				$tid_type = $sa[$p+1];
			} else
				$tid = intval(substr($sa, $p+1));
		}
		elseif(preg_match('~^[a-fA-F0-9]{40}$~', $sa)) {
			// BTIH
			$tid = strtolower($sa);
			$tid_type = 'btih';
		}
		elseif(is_numeric($sa) || (ctype_alpha(@$sa[0]) && ($tid_type = @$sa[0] /*dummy condition*/) && is_numeric($sa = substr($sa, 1))))
			$tid = intval($sa);
		unset($sa);
		if(!isset($tid) || !$tid)
			$AT->output->error('Invalid tid supplied.', 'Invalid item', AT_Output::ERROR_BADREQ);
		
		switch($tid_type) {
			case 't':
				$view_where = '`toto`.`tosho_id`='.$tid;
				break;
			case 'n':
				$view_where = '`toto`.`nyaa_id`='.$tid.' AND `toto`.`nyaa_subdom`=""';
				break;
			case 's':
				$view_where = '`toto`.`nyaa_id`='.$tid.' AND `toto`.`nyaa_subdom`="sukebei"';
				break;
			case 'd':
				$view_where = '`toto`.`anidex_id`='.$tid;
				break;
			case 'k':
				$view_where = '`toto`.`nekobt_id`='.$tid;
				break;
			case 'a':
				$view_where = '`toto`.`id`='.$tid;
				break;
			case 'btih':
				$view_where = '`toto`.`btih`=X\''.$tid.'\' AND `toto`.`isdupe`=0';
				break;
			default:
				$AT->output->error('Invalid tid supplied.', 'Invalid item', AT_Output::ERROR_BADREQ);
		}
		
		$tfile = $AT->db->selectGetArray('toto', $view_where, 'toto.*,toto_meta.comments,toto_meta.comments_top,toto_meta.comments_locked', array('joins' => array(
			array('left', 'toto_meta', 'id')
		))); // ' AND `ulcomplete`!=0'
		if(empty($tfile))
			$AT->output->error('Invalid tid supplied.', 'Invalid item', AT_Output::ERROR_NOTFOUND);
		unset($tid);
		
		
	break;
	
	case 'resolvedit':
		$tfile = $AT->db->selectGetArray('toto', '`tosho_id`='.$AT->input->get(AT_Input::TYPE_INT, 'subaction')); // ' AND `ulcomplete`!=0'
		if(empty($tfile))
			$AT->output->error('Invalid tid supplied.', 'Invalid item', AT_Output::ERROR_NOTFOUND);
		// fall through
	case 'resolveq':
		if(!$AT->user->isMod())
			$AT->output->error('Sorry, you do not have permission to view this page.', 'Access Denied', AT_Output::ERROR_FORBIDDEN);
		
	break;
}





?>