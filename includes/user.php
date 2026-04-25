<?php
if(!defined('AT_ROOT')) die('Access Denied.');

class AT_User_base
{
	public $data = array();
	public $uid = 0;
	public $updates = array();
	public $sid = '';
	
	const ACCESS_GUEST = 0;
	const ACCESS_USER = 10;
	const ACCESS_USER_HIGH = 20;
	const ACCESS_MOD = 30;
	const ACCESS_ADMIN = 50;
	
	private static function getDefaults()
	{
		return array(
			'uid' => 0,
			'email' => '',
			'emailverified' => 0,
			//'numcomments' => 0,
			'accesslevel' => self::ACCESS_USER,
			'regdate' => 0,
			'lastvisit' => 0,
			'datefmt' => 'd/m/Y',
			'timefmt' => 'H:i', // H:i:s
			'timezone_offset' => 0,
			'timezone_dst' => 0,
			
			'disp_blacklist' => '',
		);
	}
	
	static function &createFromDBArray($array)
	{
		if(empty($array)) { $null = null; return $null; }
		$user = new AT_User;
		// separate UID from array (makes updates easier)
		$user->uid = $array['uid'];
		unset($array['uid']);
		$user->data = $array;
		return $user;
	}
	static function &createGuest($name='Guest')
	{
		$array = self::getDefaults();
		$array['username'] = $name;
		$array['accesslevel'] = self::ACCESS_GUEST;
		
		return self::createFromDBArray($array);
	}
	static function &createFromUid(&$db, $uid)
	{
		if(!$uid) { $ret = null; return $ret; }
		return self::createFromDBArray($db->selectGetArray('users', '`uid`='.$uid));
	}
	static function &createFromUsername(&$db, $uname)
	{
		if(!$uname) { $ret = null; return $ret; }
		return self::createFromDBArray($db->selectGetArray('users', '`username`='.$db->escape($uname)));
	}
	static function &createFromCookie(&$db, $cookiedata)
	{
		$null = null;
		if(!preg_match('~^([0-9]{1,9})_([0-9a-f]{32})$~i', $cookiedata, $user))
			return $null;
		$array = $db->selectGetArray('users', '`uid`='.$user[1].' AND `token`='.$db->escapeHexBin($user[2]));
		if(empty($array))
			return $null;
		return self::createFromDBArray($array);
	}
	
	static function &createNew(&$db, &$data)
	{
		$null = null;
		// double-check incomming $data
		assert('is_array($data)');
		assert('isset($data[\'username\'])');
		assert('AT_Users::findUsernameError($data[\'username\']) == ""');
		assert('isset($data[\'pwd\'])');
		assert('AT_Users::findPasswordError($data[\'username\'], $data[\'pwd\']) == ""');
		
		$hashed_pwd = self::hashPwd($data['pwd']);
		$new_token = pack('H*', self::generateNewToken());
		
		$new_user = array(
			//'username' => $data['username'],
			'pwd' => $hashed_pwd,
			'token' => $new_token,
			'regdate' => TIME_NOW,
			'lastvisit' => TIME_NOW,
			'accesslevel' => self::ACCESS_USER,
		);
		
		unset($data['pwd']);
		foreach($data as $dk => &$dv)
			$new_user[$dk] = $dv;
		
		// stick in defaults
		$defaults = self::getDefaults();
		unset($defaults['uid']);
		foreach($defaults as $dk => &$dv)
		{
			//if(isset($data[$dk]))
			//	$new_user[$dk] = $data[$dk];
			//else
			if(!isset($new_user[$dk]))
				$new_user[$dk] = $dv;
		}
		
		$db->insert('users', $new_user, false, true);
		$newuid = $db->insertId();
		
		if($newuid)
		{
			$new_user['uid'] = $newuid;
			return self::createFromDBArray($new_user); //!!! doesn't have everything!
		}
		else
			return $null;
	}
	
	// supply strings to $date and $time to override formats
	public function fmtDate($stamp, $date=true, $time=true, $friendly=true)
	{
		$user_time_offset = ($this->data['timezone_offset'] + $this->data['timezone_dst'])*3600;
		$stamp += $user_time_offset;
		$date_prefix = '';
		if($friendly && $date) {
			// determine timestamp for Yesterday 12:00:00 AM
			list($m, $d, $y) = explode('-', date('n-j-Y', TIME_NOW + $user_time_offset - 86400));
			$stamp_cutoff = mktime(0, 0, 0, $m, $d, $y);
			if($stamp >= $stamp_cutoff) {
				$date = false; // we'll use our own date generation
				if($stamp >= $stamp_cutoff+86400)
					$date_prefix = 'Today ';
				else
					$date_prefix = 'Yesterday ';
			}
		}
		$datefmt = $this->data['datefmt'];
		if(is_string($date)) $datefmt = $date;
		$timefmt = $this->data['timefmt'];
		if(is_string($time)) $timefmt = $time;
		if($date && $time)
			$fmt = $datefmt.' '.$timefmt;
		elseif($date)
			$fmt = &$datefmt;
		elseif($time)
			$fmt = &$timefmt;
		else
			return trim($date_prefix);
		return $date_prefix.date($fmt, $stamp);
	}
	
	public function fmtNum($num, $dec=0) {
		return number_format($num, $dec);
	}
	
	public function isPowerUser()
	{
		return ($this->data['accesslevel'] >= self::ACCESS_USER_HIGH);
	}
	public function isMod()
	{
		return ($this->data['accesslevel'] >= self::ACCESS_MOD);
	}
	public function isAdmin()
	{
		return ($this->data['accesslevel'] >= self::ACCESS_ADMIN);
	}
	public function isMember()
	{
		return (bool)($this->uid);
	}
	
	
	public function commitUpdates(&$db)
	{
		if(empty($this->updates))
			return;
		
		if(isset($this->updates['pwd'])) {
			$this->updates['pwd'] = self::hashPwd($this->updates['pwd']);
			// new password = new token
			$this->updates['token'] = pack('H*', self::generateNewToken());
		}
		
		$db->update('users', $this->updates, 'uid='.$this->uid);
		foreach($this->updates as $k => &$v)
			$this->data[$k] = $v;
		
		// clear tagline cache
		global $parser;
		if(!isset($parser)) {
			require_once AT_ROOT.'includes/parser.php';
			$parser = new AT_Parser($GLOBALS['AT']->db, $GLOBALS['AT']->cache);
		}
		$parser->deleteCached('toto_user_tagline_'.$this->uid);
		
		$this->updates = array();
	}
	
	
	public static function generateNewToken()
	{
		return md5(uniqid(mt_rand(), true));
	}
	
	// send token to user as a cookie
	public function sendToken($remember=false) {
		if($remember)
			$expire = 0; // expire in a year's time
		else
			$expire = null;
		$GLOBALS['AT']->output->setCookie('usertoken', $this->uid.'_'.bin2hex($this->data['token']), $expire);
	}
	
	public static function clearToken() {
		$GLOBALS['AT']->output->setCookie('usertoken', '');
	}
	
	// now unused (references now deleted AT::SESSION_TIMEOUT constant, so will fail anyway)
	public function createSession(&$db, &$cache, $sid, &$input) {
		return AT_Users::initSession($db, $cache, $sid, $this, $input);
	}
	
	public function deleteSession(&$cache, &$db) {
		AT_Users::deleteSession($cache, $db, $this->uid, $this->sid);
		$this->sid = '';
	}
	
	public function sendEmail($subject, $message, $verify_override=false) {
		if(!$this->data['email'] || (!$this->data['emailverified'] && !$verify_override) || !$this->uid)
			return false;
		return $GLOBALS['AT']->mail($this->data['email'], $subject, $this->data['username'].",<br />\n<br />\n".$message);
	}
	
	public function sendEmailVerification(&$db) {
		assert('$this->data[\'email\'] && $this->uid');
		// && !$this->data[\'emailverified\'] 
		// the above shouldn't be in assertion because updating the user affects the $updates array
		
		// first, check table to see number of requests sent to prevent abuse
		if($db->selectGetField('users_verify_email', 'COUNT(*)', '`uid`='.$this->uid.' AND `dateline`>'.(TIME_NOW-AT::EMAILVERIFY_MAXTIME)) > AT::EMAILVERIFY_MAX)
			$GLOBALS['AT']->output->error('Sorry, you have exceeded the maximum number of times you may send a email verification request.  Please try again at a later date.', 'Request Limit Exceeded', AT_Output::ERROR_FORBIDDEN);
		
		
		// generate verification hash
		$hash = md5(uniqid(mt_rand(), true));
		$db->insert('users_verify_email', array('uid' => $this->uid, 'email' => $this->data['email'], 'hash' => pack('H*', $hash), 'dateline' => TIME_NOW), true);
		
		// delete old verifications
		if(!mt_rand(0,9)) {
			$db->suppressNotices(true);
			$db->delete('users_verify_email', 'dateline<='.(TIME_NOW - AT::EMAILVERIFY_EXPIRE));
			$db->suppressNotices(false);
		}
		
		
		$activate_url = AT::buildUrl('activateemail', '', array('uid' => $this->uid, 'code' => $hash));
		return $this->sendEmail('Email Verification', 'This email has been sent to you as you have signed up at <em>'.SITE_NAME.'</em> with the username &quot;'.$this->data['username'].'&quot; and have nominated this email account to receive notifications.  If you were not intending to receive this email, you may safely ignore it.  Otherwise, you will need to verify this email address before any further notifications can be sent here - you can do so by following the link below:'."<br />\n<br />\n".'<a href="'.$activate_url.'">'.$activate_url.'</a>', true);
	}
	
	public function requireLogin()
	{
		if($this->isMember()) return;
		global $AT;
		$AT->output->error('You need to be logged in to view this page.', 'Not logged in', AT_Output::ERROR_FORBIDDEN);
	}
	
	// returns the 40 byte binary hashed password
	public static function hashPwd($pwd_in, $salt=false) {
		if(!$salt)
			$salt = pack('H*', sha1(uniqid(mt_rand(), true)));
		
		$pwd_in = md5($pwd_in);
		
		$i=10;
		while($i--)
			$pwd_in = sha1($pwd_in).md5($i);
		return pack('H*', sha1($pwd_in.md5($salt))) . $salt;
	}
	public function verifyPwd($pwd_in) { // verify supplied password for this user
		return ($this->data['pwd'] == self::hashPwd($pwd_in, substr($this->data['pwd'], 20)));
	}
}

if(!class_exists('AT_User')) {
	class AT_User extends AT_User_base {}
}

// general user functions
class AT_Users
{
	// returns false if username is valid, otherwise returns a string containing the error
	public static function findUsernameError($un) {
		// length must be 3-25 chars long
		if(!isset($un[2]))
			return 'Username is too short - it must be at least 3 characters long.';
		if(isset($un[25]))
			return 'Username is too long - it must be at most 25 characters long.';
		// verify chars
		if(!preg_match('~^[a-z0-9_\-()]+$~i', $un))
			return 'Username may only contain letters, numbers, and the underscore, hyphen and parenthesis characters.';
		if(is_numeric($un))
			return 'Sorry, usernames cannot be purely numeric.  Please include at least one character.';
			
		return false;
	}
	
	public static function findPasswordError($un, $pw) {
		// length must be 5-50 chars long
		if(!isset($pw[4]))
			return 'Password is too short - it must be at least 5 characters long.';
		if(isset($pw[50]))
			return 'Password is too long - it must be at most 50 characters long.';
		
		if(strtolower($un) == strtolower($pw))
			return 'Sorry, the password cannot be the same as your username (too easy to guess).  Please enter a stronger password.';
		
		return false;
	}
	
	public static function usernameExists(&$db, $username) {
		return ($db->selectGetField('users', 'uid', 'username='.$db->escape($username)));
	}
	
	// NOTE, this method won't check if the username exists
	public static function &validateUser($data) {
		$errors = array();
		if(isset($data['username']))
			$errors['username'] = self::findUsernameError($data['username']);
		if(isset($data['pwd'])) {
			if(isset($data['username']))
				$un = $data['username'];
			else
				$un = '';
			$errors['pwd'] = self::findPasswordError($un, $data['pwd']);
			if(isset($data['pwd2'])) {
				if($data['pwd'] != $data['pwd2'])
					$errors['pwd2'] = 'Entered passwords do not match.  Please re-enter both passwords and make sure they match.';
				else
					$errors['pwd2'] = '';
			}
		}
		if(isset($data['timezone']))
			$errors['timezone'] = ($data['timezone'] && !preg_match('~^-?1?[0-9](\.(5|75))?$~', $data['timezone']) ? 'Invalid timezone specified.' : '');
		if(isset($data['email']))
			$errors['email'] = ($data['email'] && !AT::isValidEmail($data['email']) ? 'Invalid email specified.' : '');
		if(isset($data['tagline']))
			$errors['tagline'] = (strlen($data['tagline']) > 500 ? 'Tagline too long':'');
		if(isset($data['avatar']) && strlen($data['avatar'])) {
			if(isset($data['avatar'][6144]))
				$errors['avatar'] = 'Avatar exceeds 6KB in size';
			else {
				// read dims
				$dims = @getimagesizefromstring($data['avatar']);
				if(empty($dims))
					$errors['avatar'] = 'Invalid image uploaded';
				elseif($dims[2] != IMAGETYPE_JPEG && $dims[2] != IMAGETYPE_PNG)
					$errors['avatar'] = 'Only JPEG/PNG images are allowed';
				elseif($dims[0] > 100 || $dims[1] > 100)
					$errors['avatar'] = 'Image dimensions exceed 100x100 dimension limit';
				elseif(!$dims[0] || !$dims[1])
					$errors['avatar'] = 'Image contains invalid dimensions';
				elseif($dims[2] == IMAGETYPE_PNG && strpos(substr($data['avatar'], 0, strpos($data['avatar'], 'IDAT')),'acTL')!==false)
					$errors['avatar'] = 'Animated PNGs are not allowed';
			}
		}
		
		return $errors;
	}
	
	// returns true if session is valid, false if a new one was created
	public static function initSession(&$db, &$cache, $sid, &$user, &$input) {
		// check if this session exists
		if(!ctype_xdigit($sid) || strlen($sid) != 32)
			$sid = '';
		$valid_session = false;
		$time_now = TIME_NOW;
		if($sid)
		{
			$key = 'session_'.$user->uid . '_' . $sid;
			$session = $cache->get($key);
			if(empty($session))
			{
				// query DB
				$session = $db->selectGetArray('sessions', 'sid="'.$sid.'" AND uid='.$user->uid.' AND time>'.(TIME_NOW - AT::SESSION_TIMEOUT), 'sid');
				if(!empty($session))
				{
					$valid_session = true;
					// stick it into the cache
					$cache->set($key, $time_now, AT::SESSION_TIMEOUT);
				}
			}
			else
				$valid_session = true;
		}
		
		$new_session = array(
			'time' => TIME_NOW,
			'action' => substr($input->get(AT_Input::TYPE_STR, 'action'), 0, 30),
			'subaction' => substr($input->get(AT_Input::TYPE_STR, 'subaction'), 0, 120),
			'uri' => substr($_SERVER['REQUEST_URI'], 0, 140),
		);
		
		if($valid_session)
		{
			// update existing
			$db->update('sessions', $new_session, 'sid="'.$sid.'" AND uid='.$user->uid);
			$user->sid = $sid;
			return true;
		}
		else
		{
			// create new
			$user->sid = $new_session['sid'] = md5(uniqid());
			$new_session['uid'] = $user->uid;
			$db->insert('sessions', $new_session, true);
			$key = 'session_'.$user->uid . '_' . $user->sid;
			$cache->set($key, $time_now, AT::SESSION_TIMEOUT);
			
			// clear out old sessions
			if(!mt_rand(0,9)) {
				$db->suppressNotices(true);
				$db->delete('sessions', 'time<='.(TIME_NOW - AT::SESSION_TIMEOUT));
				$db->suppressNotices(false);
			}
			return false;
		}
	}
	public static function deleteSession(&$cache, &$db, $uid, $sid) {
		if(!$sid || !ctype_xdigit($sid) || strlen($sid) != 32) return;
		$db->delete('sessions', '`uid`='.$uid.' AND `sid`="'.$sid.'"');
		$cache->delete('session_'.$uid.'_'.$sid);
	}
}

?>