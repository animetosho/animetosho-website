<?php
define('AT_ROOT', dirname(dirname(__FILE__)).'/');
require AT_ROOT.'includes/webinit.php';
require AT_ROOT.'includes/core_toto.php';
$AT->loadInput();

$hash = $AT->input->get(AT_Input::TYPE_STR, 'h');
if(!ctype_xdigit($hash) || strlen($hash) != 32) exit;


$AT->loadCache();

// special actions
$action = $AT->input->get(AT_Input::TYPE_STR, 'action');
if(!$AT->post_request) $action = '';

if($action == 'refresh') {
	// delete current hash
	$AT->cache->delete('captcha_'.$hash);
	$AT->loadDb();
	$AT->db->delete('captcha', 'hash='.$AT->db->escapeHexBin($hash));
	
	sendNoCacheHeaders();
	header('Content-Type: text/plain');
	// get new hash
	$captcha = AT::generateCaptcha($AT->cache, $AT->db);
	$salt = md5(uniqid(mt_rand(), true));
	echo $captcha[0], AT::md5x($captcha[1], 50, $salt), $salt;
	
	if(!$AT->input->get(AT_Input::TYPE_BOOL, 'usedata'))
		AMD_Shutdown();
	
	// browser supports data: URI, so send the new captcha in one request
	$key = $captcha[1];
	
	function captchaObFlush($data) {
		return 'image/png;base64,'.base64_encode($data);
	}
	ob_start('captchaObFlush');
}
else {
	$key_int = $AT->cache->get('captcha_'.$hash);
	if(!$key_int) {
		// see if it's in the DB
		$AT->loadDb();
		$key_int = $AT->db->selectGetField('captcha', 'answerkey', '`hash` = '.$AT->db->escapeHexBin($hash).' AND `dateline` >= '.(TIME_NOW - AT::CAPTCHA_EXPIRE));
		if(!$key_int) {
			// output bad hash or image expired
			header('HTTP/1.1 404 Not Found');
			sendNoCacheHeaders();
			header('Content-Type: text/plain');
			echo 'Bad hash or hash expired';
		}
		else
			$AT->cache->set('captcha_'.$hash, $key_int, AT::CAPTCHA_EXPIRE);
	}
	if($key_int)
		$key = strtr(str_pad(strtoupper(base_convert($key_int, 10, 34)), 6, '0', STR_PAD_LEFT), array('0' => 'Z', '1' => 'Y'));
}

if(isset($key) && $key) {
	if($action != 'refresh')
		sendNoCacheHeaders();
	
	
	if($action == 'formverify') {
		header('Content-Type: text/plain');
		if($key == strtoupper($AT->input->get(AT_Input::TYPE_STR, 'key'))) {
			$AT->cache->delete('captcha_'.$hash);
			if(!is_object($AT->db))
				$AT->loadDb();
			$AT->db->delete('captcha', 'hash='.$AT->db->escapeHexBin($hash));
			echo 'valid';
		}
		else
			echo 'invalid';
		AMD_Shutdown();
	}
	
	require AT_ROOT.'includes/3rdparty/securimage/securimage.php';
	$img = new Securimage($key);
	/*
	if($AT->input->get(AT_Input::TYPE_BOOL, 'audio')) {
		header('Content-Type: audio/x-wav');
		//header('Content-Disposition: attachment; name="captcha.wav"');
		header('Content-Disposition: filename="captcha.wav"');
		$img->doSound();
	}
	else { */
		if($action != 'refresh')
			header('Content-Type: image/png');
		$img->doImage();
	//}
	unset($img);
}

// shut down
AT_Shutdown();


function sendNoCacheHeaders() {
	header('Expires: Sat, 1 Jan 2000 01:00:00 GMT');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: no-cache, must-revalidate');
	header('Pragma: no-cache');
	header('X-Accel-Expires: 0');
}
?>