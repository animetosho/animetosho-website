<?php
if(!defined('AT_ROOT')) exit;


error_reporting(E_ALL | E_STRICT);

// fix annoying PHP date issues in strict mode...
date_default_timezone_set('UTC');

define('TIME_NOW_MICRO', microtime(true));
define('TIME_NOW', time());




/************* GENERAL INITIALISATION ***********/
require_once AT_ROOT.'includes/functions.php';

if(function_exists('mb_internal_encoding'))
	@mb_internal_encoding('UTF-8');


class AT
{
	//************* SETTINGS ***********
	const DEFAULT_COOKIE_EXPIRE = 31536000; //365*86400
	const CAPTCHA_EXPIRE = 600;
	const FILESESS_EXPIRE = 1814400; // 21 days
	const HTTPPOST_EXPIRE = 5;
	const HTTPPOST_MAX = 100; // a fairly generous limit - max 100 POST requests in 5 seconds
	const EMAILVERIFY_EXPIRE = 259200; // 86400*3
	const EMAILVERIFY_MAX = 10;
	const EMAILVERIFY_MAXTIME = 1800; // user can send max of 10 email verify requests per 30 mins
	const PWDRESET_EXPIRE = 86400;
	const INVITE_EXPIRE = 1209600; // 2 weeks
	const PWDRESET_MAX = 10;
	const PWDRESET_MAXTIME = 1800; // user can send max of 10 password resets per 30 mins
	
	const TMPUPLOAD_EXPIRE = 86400;
	
	const UPLOAD_MAXSIZE = 1024; //size in MB
	
	
	public $db = null;
	public $cache = null;
	
	// input related
	public $input = null;
	public $cookies = null;
	public $post_request = false;
	
	public $postKey = '';
	
	private $config;
	
	public $output;
	public $ajax_request;
	public $user;
	
	public static function md5x($in, $rounds=1, $salt='') {
		while($rounds--)
			$in=md5($in.$salt);
		return $in;
	}
	
	/** convert a time in seconds to a friendly duration format, eg 65 (seconds) -> 00:01:05 **/
	public static function friendlyDuration($duration)
	{
		return str_pad(floor($duration / 3600), 2, '0', STR_PAD_LEFT) .':'. str_pad(floor($duration/60) % 60, 2, '0', STR_PAD_LEFT) .':'. str_pad($duration % 60, 2, '0', STR_PAD_LEFT);
	}
	
	/** convert a file size in bytes to a friendly format, eg 51813 -> 50.60KB **/
	public function friendlyFileSize($size)
	{
		/* our threshold will be at 1000x the unit
		 *  eg: 100 bytes, 999 bytes, 0.977KB (1000 bytes)
		 *
		 * we'll handle up to petabytes (assuming PHP itself doesn't stuff itself up; floats shouldn't though)
		 */
		$units = array(
			0 => 'bytes',
			1 => 'KB',
			2 => 'MB',
			3 => 'GB',
			4 => 'TB',
			5 => 'PB'
		);
		for($i = count($units)-1; $i > 0; --$i)
			if($size > pow(2, ($i-1)*10)*999)
			{
				$num = $size / pow(2, $i*10);
				// determine how much to round
				if($num >= 100)		$round = 1;
				elseif($num >= 10)	$round = 2;
				else				$round = 3;
				
				return $this->user->fmtNum(round($num, $round), $round).' '.$units[$i];
			}
		
		// still here?  must be bytes then
		return $this->user->fmtNum($size).' '.$units[0];
	}
	
	public function relative_time($ts, $min_nem='sec') {
		$d = TIME_NOW - $ts;
		$neg = $d < 0 ? '-':'';
		$d = abs($d);
		
		$ret = array();
		foreach(array('d' => 86400, 'hr' => 3600, 'min' => 60, 'sec' => 1) as $nem => $int) {
			if($v = floor($d/$int)) {
				$ret[] = $neg.$v.$nem;
				$d %= $int;
			}
			if($nem == $min_nem) break;
		}
		if(empty($ret)) return '0'.$min_nem.' ago';
		return implode(', ', $ret).' ago';
	}
	
	public static function buildUrl($action='', $subaction='', $ex_args=array(), $htmlspecialchar=true) {
		if($action == 'feed') {
			$url = 'https://'.FEED_HOSTNAME.'/'.rawurlencode($subaction);
			if(!empty($ex_args))
				$url .= '?'.http_build_query($ex_args);
			if($htmlspecialchar)
				$url = htmlspecialchars($url);
			return $url;
		}
		$url = '/';
		if($action=='home') $action = ''; // direct all links to home to the same URL
		if(AT_URL_QSTR) {
			$url .= 'index.php';
			if($action !== '') {
				$tmp_args['action'] = $action;
				if($subaction !== '')
					$tmp_args['subaction'] = $subaction;
				$ex_args = $tmp_args + $ex_args;
			}
		}
		else {
			if($action !== '') {
				$url .= rawurlencode($action);
				if($subaction !== '') {
					$sa = rawurlencode($subaction);
					// for some reason, mod_rewrite chokes on %2f
					$sa = strtr($sa, array('%2f' => '/', '%2F' => '/'));
					$url .= '/'.$sa;
				}
			}
		}
		if(!empty($ex_args)) {
			$a = '';
			foreach($ex_args as $k => $v) {
				// paging hack
				if($k == 'page') {
					if($v == 1) continue;
					if($v == -1) // force page 1 to display (unnecessary for multipage though)
						$v = 1;
				}
				if(is_array($v)) {
					foreach($v as $vv)
						$a .= '&'. rawurlencode($k) .'[]='. rawurlencode($vv);
				}
				else
					$a .= '&'. rawurlencode($k) .'='. rawurlencode($v);
			}
			if($a) $a[0] = '?';
			$url .= $a;
		}
		if($htmlspecialchar)
			$url = htmlspecialchars($url);
		return HOME_URL.$url;
	}
	
	// grabs the subaction for a thing - only used for view.php in stdMultipageHandler
	public static function seoPageSubaction($id, $title) {
		//return self::urlTitle($title).'.'.$this->encodeId($id);
		$title = self::urlTitle($title);
		if($title !== '')
			return $title.'.'.$id;
		return $id;
	}
	
	private static function urlTitle($title)
	{
		if(!defined('AT_SEO_MAX_URL_KEYWORDS'))
			define('AT_SEO_MAX_URL_KEYWORDS', 8);
		if(!defined('AT_SEO_MAX_URL_LEN'))
			define('AT_SEO_MAX_URL_LEN', 60);
		$title = preg_replace('~&([0-9a-zA-Z]{2,8}?);~', '', $title); // remove entites
		//$title = unhtmlentities($title);
		$title = str_replace(array('\'', '!', '"', '#', '$', '%', '*', '?', '`'), '', $title);
		$title = str_replace(array(' ', '&', '(', ')', '+', ',', '.', '/', ':', ';', '<', '=', '>', '@', '[', '\\', ']', '^', '{', '|', '}', '~'), '-', $title);
		// remove any other undesirable chars + double spacing
		$title = preg_replace(array('~[^0-9a-zA-Z\-_]~', '~-{2,}~'), array('', '-'), $title);
		if($title === '') return '';
		// remove spaces at beginning or end
		if($title[0] == '-') $title = substr($title, 1);
		if(substr($title, -1) == '-') $title = substr($title, 0, -1);
		$title = self::stripCommonWords($title, '-');
		if($title === '') return '';
		if(AT_SEO_MAX_URL_KEYWORDS)
			if(substr_count($title, '-')-1 > AT_SEO_MAX_URL_KEYWORDS)
			{
				$kw = array_unique(explode('-', $title));
				//if(count($kw) > AT_SEO_MAX_URL_KEYWORDS)
				$kw = array_slice($kw, 0, AT_SEO_MAX_URL_KEYWORDS);
				$title = implode('-', $kw);
			}
		if(AT_SEO_MAX_URL_LEN && strlen($title) > AT_SEO_MAX_URL_LEN)
		{
			$kw = array_unique(explode('-', $title));
			$newtitle = $delim = '';
			foreach($kw as &$w) {
				if(strlen($newtitle) + strlen($w) + strlen($delim) <= AT_SEO_MAX_URL_LEN)
					$newtitle .= $delim.$w;
				else
					break;
				if(!$delim) $delim = '-';
			}
			if($newtitle)
				$title = $newtitle;
			else
				$title = substr($title, 0, AT_SEO_MAX_URL_LEN);
		}
		$title = strtolower($title);
		return $title;
	}
	
	public static function testSeoUrl($self_url) {
		if(isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI']) {
			$host = substr(HOME_URL, 0, strpos(HOME_URL, '/', strlen(HOME_URL) > 8 ? 9:0));
			if(!$host) $host = HOME_URL;
			$request = $host.urldecode($_SERVER['REQUEST_URI']);
			$request_args = '';
			if($request != $self_url) {
				// check if passing args
				$self_url_len = strlen($self_url);
				
				$request_args = substr($request, $self_url_len);
				$request = substr($request, 0, $self_url_len);
				// enforce request_args arguments
				$request_args = strstr($request_args, '?');
				unset($self_url_len);
			}
			if($request != $self_url)
			{
				header('HTTP/1.1 301 Moved Permanently');
				header('Location: '.$self_url.$request_args);
				return true;
			}
		}
	}
	
	private static function stripCommonWords($str, $delim = ' ')
	{
		$splitstr = explode($delim, $str);
		$common_words = array(
			'the' => 1,		'of' => 1,		'to' => 1,		'and' => 1,		'this' => 1,
			'in' => 1,		'is' => 1,		'it' => 1,		'you' => 1,		'that' => 1,
			'was' => 1,		'for' => 1,		'on' => 1,		'are' => 1,		'with' => 1,
			'as' => 1,		'i' => 1,		'be' => 1,		'at' => 1,		'have' => 1,
			'a' => 1,		'or' => 1,		'had' => 1,		'by' => 1,		'but' => 1,
			'what' => 1,	'some' => 1,	'we' => 1,		'can' => 1,		'were' => 1,
			'there' => 1,	'how' => 1,		'an' => 1,		'do' => 1,		'if' => 1,
			'would' => 1,	'so' => 1,
		);
		foreach($splitstr as $i => &$word)
			if($word && isset($common_words[strtolower($word)]))
				unset($splitstr[$i]);
		
		return implode($delim, $splitstr);
	}
	
	public static function generateCaptcha(&$cache, &$db)
	{
		$i=0;
		$key = AT::randomStr(6);
		$key_int = intval(base_convert(strtr($key, array('Z' => '0', 'Y' => '1')), 34, 10));
		do {
			$hash = md5(uniqid(mt_rand(), true));
			if(++$i > 3) { // if we've already tried 3 times, something must be wrong with the DB
				global $AT;
				$AT->output->error('Unable to generate CAPTCHA!', 'System error', AT_Output::ERROR_SERVER);
			}
		} while(!$db->insert('captcha', array('dateline' => TIME_NOW, 'hash' => pack('H*', $hash), 'answerkey' => $key_int)));
		
		// clear old expired captchas
		if(!mt_rand(0,9)) {
			$db->suppressNotices(true);
			$db->delete('captcha', 'dateline<='.(TIME_NOW - AT::CAPTCHA_EXPIRE));
			$db->suppressNotices(false);
		}
		
		// store this into our cache as well
		$cache->set('captcha_'.$hash, $key_int, AT::CAPTCHA_EXPIRE);
		
		return array($hash, $key);
	}
	
	
	// postkey functions don't belong in the core...
	public function makePostKey($userid = '', $timeoffset = 0) {
		// note that some IPs always change, so lock to first to octets only
		//if(!$userid) $userid = substr(REMOTE_IP, 0, strpos(REMOTE_IP, '.', strpos(REMOTE_IP, '.')+1));
		if(!$userid) $userid = 'guest';
		return md5( sha1(substr($this->config['enc_key'], 0, 10)) .'/'. $userid .'/'. (floor(TIME_NOW / 43200)-$timeoffset) );
	}
	
	public function validPostKey($key, $userid = '')
	{
		if(strlen($key) != 32) return false;
		if($key == $this->makePostKey($userid, 0)) return true;
		if($key == $this->makePostKey($userid, 1)) return true; // allow keys from past 12 hour period
		return false;
	}
	
	
	
	public static function randomStr($len)
	{
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ23456789'; // no "0" or "1"
		$ret = '';
		while($len--)
			$ret .= $chars[mt_rand(0, 33)];
		return $ret;
	}
	
	
	
	public function mail($to, $subject, $message, $from='', $message_plain='') {
		return true; // disable mailing since our server doesn't allow outgoing SMTP
		
		static $mb_lang_call = false;
		if(!$mb_lang_call) {
			$mb_lang_call = true;
			mb_language('uni');
		}
		if(!$from) $from = $this->config['mail_from'];
		$replyto = $this->config['mail_replyto'];
		if(!$replyto) $replyto = $from;
		$boundary = 'Boundary-'.md5(mt_rand());
		$headers = array(
			'From: '.$from,
			'Reply-To: '.$replyto,
			'Return-Path: '.$replyto,
			'Message-ID: '.md5(uniqid(TIME_NOW)).'@'.$this->config['mail_msgidsuf'],
			//'Content-Transfer-Encoding: 8bit',
			'X-Priority: 3',
			'X-MSMail-Priority: Normal',
			'X-Mailer: '.SITE_NAME,
			'MIME-Version: 1.0',
			'Content-Type: multipart/alternative; boundary="'.$boundary.'"'
		);
		if(!$message_plain) $message_plain = unhtmlentities(strip_tags(str_replace('<br />', "\r\n", $message)));
		$usercp_url = AT::buildUrl('usercp');
		$footer_note = 'You are receiving this message because you have chosen to subscribe this email address at '.SITE_NAME.'.  If you have not subscribed and do not wish to subscribe, you can safely ignore this email.  If you are subscribed and do not wish to receive further emails from us, you may remove your email address from your account by visiting your ';
		$message = "--$boundary\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit\n\n ".SITE_NAME." Mailer \n---------------\n$message_plain\n_______________\n{$footer_note}UserCP ($usercp_url)\n\n--$boundary\nContent-Type: text/html; charset=utf-8\nContent-Transfer-Encoding: 8bit\n\n<em>".SITE_NAME." Mailer</em><hr />$message<hr />$footer_note<a href=\"$usercp_url\">UserCP</a>\n\n--$boundary--";
		//mb_send_mail base64 encodes everything...
		return mail($to, $subject, $message, implode("\n", $headers));
	}
	
	public static function isValidEmail($email) {
		return preg_match('~^[0-9a-zA-Z.\-_]{1,160}@[0-9a-zA-Z.\-_]{1,160}\.[a-zA-Z]{2,10}$~', $email);
	}
	
	public static function arrayContainsTrue(&$a)
	{
		foreach($a as &$v)
			if($v)
				return true;
		return false;
	}
	
	function __construct()
	{
		require AT_ROOT.'includes/config.php';
		$this->config = $config;
	}
	
	public function loadInput()
	{
		require_once AT_ROOT.'includes/inputhandler.php';
		
		$request = $_GET + $_POST;
		$this->input = new AT_Input($request);
		$this->post_request = (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST');
		
		if(!isset($_COOKIE[$this->config['cookiename']]) || !is_array($_COOKIE[$this->config['cookiename']])) // if no cookies array, fake one
			$_COOKIE[$this->config['cookiename']] = array();
		$this->cookies = new AT_Input($_COOKIE[$this->config['cookiename']]);
		// free up some memory
		unset($_GET, $_POST, $_COOKIE, $_REQUEST);
	}
	public function loadDb()
	{
		$this->db = $this->config['db']();
	}
	public function loadCache()
	{
		require_once AT_ROOT.'includes/cache.php';
		$cacheclass = 'ATCore_Cache_'.$this->config['cache_type'];
		$this->cache = new $cacheclass($this->config['cache_prefix']);
	}
	public function loadOutput()
	{
		require_once AT_ROOT.'includes/output.php';
		$this->output = new AT_Output($this->config['cookiename'], $this->config['cookiedomain'], $this->config['cookiepath']);
	}
	
	
	function __destruct()
	{
		unset($this->input, $this->cookies, $this->db, $this->cache, $this->output);
	}
}

if(!function_exists('AT_Shutdown')) {
	function AT_Shutdown() {
		unset($GLOBALS['AT']);
		exit;
	}
}
?>
