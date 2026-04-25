<?php

if(!defined('AT_ROOT')) exit;


define('FEED_HOSTNAME', 'feed.animetosho.org');
define('CACHE_URL', 'https://cache.animetosho.org');

define('TOR_HOST', 'rozvtgjhfwfrf4xbbhqevgwia6ut3twhcv3kxspqnklekojyoskcfryd.onion');
if(preg_match('~^[a-z0-9]+\.'.preg_quote('animetosho.org').'$~', @$_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != FEED_HOSTNAME)
	define('HOME_URL', 'https://'.$_SERVER['HTTP_HOST']);
elseif(TOR_HOST && @$_SERVER['HTTP_HOST'] == TOR_HOST)
	define('HOME_URL', 'http://'.TOR_HOST);
else
	define('HOME_URL', 'https://animetosho.org');

if(TOR_HOST && @$_SERVER['HTTP_HOST'] == TOR_HOST) {
	define('STORAGE_URL', 'http://storage.2dhhlhbqpcgkros3mddwq7tuhhiaubjydejoy6npa3rn7snsofq3qjyd.onion/');
	define('STORAGE_URL_REDIR', STORAGE_URL);
}
else {
	define('STORAGE_URL', 'https://storage.animetosho.org/');
	if(@$_SERVER['HTTP_HOST'] == FEED_HOSTNAME)
		define('STORAGE_URL_REDIR', STORAGE_URL);
}


define('INC_URL', HOME_URL.'/inc');
define('INC_ROOT', AT_ROOT.'inc/');

if(@$_SERVER['HTTP_HOST'] == 'mirror.animetosho.org')
	define('SITE_NAME', 'AT Mirror');
else
	define('SITE_NAME', 'Anime Tosho');

define('AT_URL_QSTR', 0); // AT_URL_QSTR should now only ever be "0" (javascript conflict)
//define('USE_MINIMAL_FOOTER', 1); // mirror.animetosho.org's reduced footer

require_once AT_ROOT.'includes/db.php';
class toto_db extends ATCore_DB_MySQLi { // `extends DB_readonly` for mirror
	const repl_db = 'toto_repl';
	public function tableName($table) {
		switch($table) {
			case 'toto':
			case 'files':
			case 'filelinks':
			case 'attachment_files':
			case 'attachments':
			case 'trackers':
			case 'tracker_stats':
			case 'anidb_tvdb':
			case 'fileinfo':
				return '`'.self::repl_db.'`.`'.$this->table_prefix.$table.'`';
		}
		return parent::tableName($table);
	}
}

$config = array(
	'debugmode' => 1,
	
	'enc_key' => md5('xxxxxxxx'), // make it something unique
	'cookiepath' => '/',
	'cookiedomain' => '',
	'cookiename' => 'ant', // set to blank to totally disable cookies
	
	'db' => function() {
		return new toto_db(array(
			'dbhost' => 'localhost',
			'dbuser' => 'toto_web',
			'dbpassword' => 'xxxxxxxx',
			'dbname' => 'anito',
			
			'dbport' => 3306,
			'dbprefix' => 'toto_',
			
			'dbdebug' => 1,
		));
	},
	'cache_prefix' => 'anito_',
	'cache_type' => 'Apcu', // Dummy or Null or Xcache or Apc/u
	
	'mail_from' => 'mailer@animetosho.org',
	'mail_replyto' => 'no-reply@animetosho.org',
	'mail_msgidsuf' => '@animetosho.org',
	//'mail_contact' => '',
	
);



?>