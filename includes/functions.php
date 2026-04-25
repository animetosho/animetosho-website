<?php
if(!defined('AT_ROOT')) die('Access Denied.');


if(!function_exists('htmlspecialchars_uni'))
{
	function htmlspecialchars_uni($str)
	{
		return strtr(
			preg_replace('/&(?!#[0-9]+;)/', '&amp;', $str),
			array('<' => '&lt;', '>' => '&gt;', '"' => '&quot;')
		);
	}
}

if(!function_exists('unhtmlentities')) {
	function unhtmlentities($s) { return html_entity_decode($s, ENT_COMPAT, 'UTF-8'); }
}

/** MB_* functions **/
if(!function_exists('mb_strlen')) {
	function mb_strlen($str) { return strlen($str); }
}
if(!function_exists('mb_strpos')) {
	function mb_strpos($haystack, $needle, $offset=0) { return strpos($haystack, $needle, $offset); }
}
if(!function_exists('mb_strrpos')) {
	function mb_strrpos($haystack, $needle) { return strrpos($haystack, $needle); }
}
if(!function_exists('mb_strtolower')) {
	function mb_strtolower($str) { return strtolower($str); }
}
if(!function_exists('mb_strtoupper')) {
	function mb_strtoupper($str) { return strtoupper($str); }
}
if(!function_exists('mb_substr')) {
	function mb_substr($str, $start, $length=null) {
		if(isset($length))
			return substr($str, $start, $length);
		else
			return substr($str, $start);
	}
}
if(!function_exists('mb_language')) { // dummy function
	function mb_language($newlang='') { return false; }
}
if(!function_exists('mb_send_mail')) {
	function mb_send_mail($to, $subject, $message, $additional_headers=null, $additional_parameters=null) { return mail($to, $subject, $message, $additional_headers, $additional_parameters); }
}


?>