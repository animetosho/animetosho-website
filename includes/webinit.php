<?php
if(!defined('AT_ROOT')) die('Access Denied.');


// remove these - they're uneeded :P
unset($HTTP_SERVER_VARS, $HTTP_GET_VARS, $HTTP_POST_VARS, $HTTP_COOKIE_VARS, $HTTP_POST_FILES, $HTTP_ENV_VARS, $HTTP_SESSION_VARS);

if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
{
	function stripslashes_recursive(&$a) {
		return $a = is_array($a) ? array_map('stripslashes_recursive', $a) : stripslashes($a);
	}
	stripslashes_recursive($_REQUEST);
	stripslashes_recursive($_POST);
	stripslashes_recursive($_GET);
	stripslashes_recursive($_COOKIE);
	@ini_set('magic_quotes_gpc', 0);
}



// only allow POST/GET/HEAD requests
if(!isset($_SERVER['REQUEST_METHOD']) || ($_SERVER['REQUEST_METHOD'] != 'POST' && $_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'HEAD'))
	die('Invalid Request.');

// should be optimized in the future, but not critical
if($_SERVER['REQUEST_METHOD'] == 'HEAD')
{
	if(!function_exists('empty_string')) {
		function empty_string() { return ''; }
	}
	ob_start('empty_string');
}


if(isset($_SERVER['REMOTE_ADDR']))
	$ip =& $_SERVER['REMOTE_ADDR'];

if(!$ip || $ip == '127.0.0.1') {
	// check for local proxy
	if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		if(preg_match_all('~[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}~', $_SERVER['HTTP_X_FORWARDED_FOR'], $addresses)) {
			foreach($addresses[0] as $addr) {
				if($addr != '127.0.0.1' && substr($addr, 0, 8) != '192.168.' && substr($addr, 0, 7) != '172.16.') {
					$ip = $addr;
					break;
				}
			}
		}
	}
	if(!$ip)
		$ip = '0.0.0.0';
}

define('REMOTE_IP', $ip);
unset($ip);

?>