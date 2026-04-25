<?php

if(!defined('AT_ROOT'))
	define('AT_ROOT', dirname(__FILE__).'/');
require AT_ROOT.'includes/webinit.php';

require AT_ROOT.'includes/core_toto.php';

// stick _everything_ else into a big function to limit scope
function AT_Main()
{
	global $AT;
	$AT->loadInput();
	$AT->loadDb();
	$AT->loadCache();
	$AT->loadOutput();
	
	/************************************************************************/
	/*       Handle some request stuff here                                 */
	/************************************************************************/
	
	$AT->input->parse(array(
		'ajax' => AT_Input::TYPE_BOOL,
		'do' => AT_Input::TYPE_STR,
		'action' => AT_Input::TYPE_STR,
		'subaction' => AT_Input::TYPE_STR,
		'postkey' => AT_Input::TYPE_STR,
		'theme' => AT_Input::TYPE_INT,
		
		'login_username' => AT_Input::TYPE_STR,
		'login_password' => AT_Input::TYPE_STR,
		'login_remember' => AT_Input::TYPE_BOOL,
		'login_notoken' => AT_Input::TYPE_BOOL
	));
	
	$AT->ajax_request = $AT->input->ajax;
	
	// do we have a valid action?
	if(!$AT->input->action || !ctype_alnum($AT->input->action))
		$AT->input->action = 'home';
	elseif(!file_exists('./pages/'.$AT->input->action.'.php'))
		$AT->output->error('Requested page not found.', 'Invalid page', AT_Output::ERROR_NOTFOUND);
		//$AT->input->action = 'home';
	
	// do we have valid inline action?
	$inline_do = '';
	if($AT->input->do)
	{
		if(!ctype_alnum($AT->input->do))
			$AT->input->do = '';
		elseif(file_exists('./pages/global_'.$AT->input->do.'.php'))
			$inline_do = 'global_'.$AT->input->do;
		elseif(!file_exists('./pages/'.$AT->input->action.'_'.$AT->input->do.'.php'))
			$AT->input->do = '';
		else
			$inline_do = $AT->input->action.'_'.$AT->input->do;
	}
	
	// we need to defer postkey generation till after the user is loaded
	
	
	/************************************************************************/
	/*       Load user and session                                          */
	/************************************************************************/
	
	///// load user
	require_once AT_ROOT.'includes/user.php';
	// perform inline login if necessary
	if($AT->post_request && $AT->input->login_username && $AT->input->login_password && $AT->input->postkey)
	{
		// validate our postkey against that for a guest
		if($AT->validPostKey($AT->input->postkey))
		{
			// find user
			$user = AT_User::createFromUsername($AT->db, $AT->input->login_username);
			if(!empty($user))
			{
				if($user->verifyPwd($AT->input->login_password))
				{
					// login success
					if(!$AT->input->login_notoken)
						$user->sendToken($AT->input->login_remember);
					
					$AT->user = $user;
					// leave a mark for our global_login script
					$AT->user->logged_in_now = true;
				}
			}
		}
	}
	if(empty($AT->user))
	{
		$user = AT_User::createFromCookie($AT->db, $AT->cookies->get(AT_Input::TYPE_STR, 'usertoken'));
		if(empty($user))
			$AT->user = AT_User::createGuest();
		else
			$AT->user = $user;
	}
	unset($user);
	
	///// check POST key requests
	$AT->postKey = $AT->makePostKey($AT->user->uid);
	if($AT->post_request && $AT->input->postkey)
	{
		// check key
		if(!isset($AT->user->logged_in_now) && !$AT->validPostKey($AT->input->postkey, $AT->user->uid)) {
			if($AT->ajax_request) // refresh postkey for AJAX requests
				$AT->output->error('newPostKey:'.$AT->postKey, 'Authorisation key mismatch', AT_Output::ERROR_FORBIDDEN);
			else
				$AT->output->error('Invalid request - the sent session postkey is either incorrect, or has expired.  If you intentionally wanted to perform this action, try going back, reload the page, and try again.', 'Authorisation key mismatch', AT_Output::ERROR_FORBIDDEN);
		}
	}
	else
		$AT->post_request = false;
	
	
	// theme
	if($AT->input->theme) {
		$AT->output->setTheme($AT->input->theme);
		$month = floor(( (int)date('n') +1)/3)*3 +2;
		$y = (int)date('Y');
		if($month>12) {
			++$y;
			$month -= 12;
		}
		$AT->output->setCookie('theme', $AT->input->theme, mktime(0,0,0, $month, 1, $y));
	}
	elseif($theme = $AT->cookies->get(AT_Input::TYPE_INT, 'theme')) {
		$AT->output->setTheme($theme);
	}
	
	/************************************************************************/
	/*       Now, process pages                                             */
	/************************************************************************/
	
	/// pre-process special cases
	require AT_ROOT.'pages/includes/prepage.php';
	
	// do we have inline actions?
	if($inline_do) {
		//ignore_user_abort(true); // don't let the user abort our actions half-way thru
		require AT_ROOT.'pages/'.$inline_do.'.php';
		//ignore_user_abort(false);
	}
	
	if(!$AT->ajax_request) { // we never display a page if making AJAX request
		require AT_ROOT.'pages/'.$AT->input->action.'.php';
	}
} AT_Main();



/************************************************************************/
/*       Shutdown stuff                                                 */
/************************************************************************/

function AT_Shutdown()
{
	unset($GLOBALS['AT']);
	exit;
} AT_Shutdown();


?>