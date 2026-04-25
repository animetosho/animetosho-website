<?php
if(!defined('AT_ROOT')) die('Access Denied.');

define('AT_PARSECACHE_MEM_EXPIRE', 1800); // keep small preparsed messages in mem for 1 day
define('AT_PARSECACHE_MEM_THRESHOLD', 2048); // keep messages <=1KB in mem

// NOTE: MUST unset this instance after use! (flushes changes to the DB)
class AT_Parser {
	private $datacache = array();
	private $datacache_updates = array();
	private $db = null;
	private $cache = null;
	
	
	public function __construct(&$db, &$cache) {
		$this->db = &$db;
		$this->cache = &$cache;
	}
	
	public function parse($key, &$msg, $opts=array()) {
		if(!$msg) return '';
		if(!isset($this->datacache[$key])) {
			$this->datacache[$key] = $this->parseMessage($msg, $opts);
			$data = &$this->datacache[$key];
			
			// if lower than threshold, add to mem cache
			if(!isset($data[AT_PARSECACHE_MEM_THRESHOLD])) {
				$this->cache->set('parsecache_'.$key, $data, AT_PARSECACHE_MEM_EXPIRE);
			}
			// add to the DB too
			if(!@$opts['nodbcache']) {
				$this->datacache_updates[] = array('title' => $key, 'data' => $data, 'dateline' => TIME_NOW);
			}
		}
		return $this->datacache[$key];
	}
	
	public function load($items) {
		foreach($items as $k => &$item) {
			$data = $this->cache->get('parsecache_'.$item);
			if($data) {
				unset($items[$k]);
				$this->datacache[$item] = $data;
			}
		}
		// not cached - we'll try the DB
		if(!empty($items)) {
			// since PHP doesn't like a SELECT...WHERE <key> IN () query...
			$this->db->suppressNotices(true);
			$this->db->select('parsecache', 'title IN ("'.implode('","', $items).'")');
			$this->db->suppressNotices(false);
			while($item = $this->db->fetchArray()) {
				$this->datacache[$item['title']] = $item['data'];
				// load it into the memcache as well
				if(!isset($item['data'][AT_PARSECACHE_MEM_THRESHOLD]))
					$this->cache->set('parsecache_'.$item['title'], $item['data'], AT_PARSECACHE_MEM_EXPIRE);
			}
			$this->db->freeResult();
		}
	}
	
	public function loadParse($key, &$msg) {
		$this->load(array($key));
		return $this->parse($key, $msg);
	}
	
	public function deleteCached($key) {
		$this->cache->delete('parsecache_'.$key);
		$this->db->delete('parsecache', '`title`='.$this->db->escape($key));
		unset($this->datacache[$key]);
	}
	
	public function __destruct() {
		if(!empty($this->datacache_updates)) {
			// flush updates to the DB
			$this->db->insertMulti('parsecache', $this->datacache_updates, true);
		}
	}
	
	
	// options: noautourl, oneline
	public static function parseMessage($msg, $options=array()) {
		
		// firstly fix \r's, then htmlspecialchar
		$msg = htmlspecialchars_uni(str_replace("\r", "\n", str_replace("\r\n", "\n", $msg)));
		
		
		// shouldn't need to do this, but parse out "$" and other vulnerable chars just in case
		$msg = strtr($msg, array('$' => '&#36;', '\\' => '&#92;'));
		
		// auto-url
		if(!isset($options['noautourl'])) {
			$msg = preg_replace('~(?<=\s|^)((?:https?|ftp)\://(([a-z0-9\-.]+\.[a-z]{2,8}|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})(/[^<>]*?)?)|magnet:\?[a-z]+=[^<>]+?)(?=\s|$|&lt;)~i', '&lt;url&gt;$1&lt;/url&gt;', $msg);
		}
		
		// do tag replacing
		self::doTagReplace($msg, @$options['oneline']);
		
		
		// if the user is trying to be smart... (eg "&amp;raquo;" => "&raquo;")
		$msg = preg_replace('~&amp;(#?[0-9a-z]{1,8});~i', '&$1;', $msg);
		
		
		// fix double spaces, tabs and newlines
		$msg = strtr($msg, array("\n" => "<br />\r\n", '  ' => '&nbsp;&nbsp;', "\t" => "<pre class=\"user_tab\">\t</pre>"));
		$msg = '<div class="user_message_c">'.$msg.'</div>';
		
		return $msg;
	}
	
	
	private static function doTagReplace(&$msg, $oneline=false) {
		// should we use instance level private vars instead of static ones?
		static $finds;
		if(!isset($finds)) {
			$link_preg_part = '=((?:&quot;|\')?)([^\n]+?)\\2(\s*)&gt;(.+?)&lt;/';
			$color_preg_part = '=((?:&quot;|\')?)(#?[a-z0-9 \-]{1,20})\\2(\s*)&gt;(.+?)&lt;/';
			$finds = array(
				'~&lt;([bius]|strong|em|cite|dfn|var|kbd|code|samp|strike|sub|sup|big|small|bold|italic|underline|super|superscript|subscript|spoiler)(\s*)&gt;(.+?)&lt;/\\1&gt;~si', //simple tag match
				// url
				'~&lt;(url)&gt;([a-z\-]{1,15}\://.+?|magnet:\?.+?)&lt;/url&gt;~i',
				'~&lt;(url)'.$link_preg_part.'url&gt;~si',
				'~&lt;(a) href'.$link_preg_part.'a&gt;~si',
				
				'~&lt;(colou?r)'.$color_preg_part.'\\1&gt;~si',
				'~&lt;(size)=((?:&quot;|\')?)(big|small)\\2&gt;(.+?)&lt;/size&gt;~si',
				'~&lt;(font)=((?:&quot;|\')?)([0-9a-z \-_]+)\\2&gt;(.+?)&lt;/font&gt;~si',
				
				'~&lt;(fon)t\s+(.+?)&gt;(.+?)&lt;/font&gt;~si',
				'~&lt;(span)\s+style=((?:&quot;|\')?)(.+?)\\2\s*&gt;(.+?)&lt;/span&gt;~si',
				
				'~&lt;(quote|blockquote)&gt;(.+?)&lt;/\\1&gt;'."\n".'?~is'
			);
		}
		// need to add a lot more tags
		// url, email, quote?, code?, color, font, align?, list?, fieldset?, table?, smallcaps?, indent?, 
		// fix h1...h6?, span/style/div?, acronyms?
		
		$msg = preg_replace_callback($finds, function($m) use($oneline) {
			switch(trim(strtolower($m[1]))) {
				case 'url': case 'a':
					if(isset($m[3]))
						return self::parseLink($m[3], $m[5]);
					return self::parseLink($m[2]);
				case 'color': case 'colour':
					return '<span style="color: '.$m[3].';">'.$m[5].'</span>';
				case 'size':
					return '<'.$m[3].'>'.$m[4].'</'.$m[2].'>';
				case 'font':
					return '<span style="font-family: '.$m[3].';">'.$m[4].'</span>';
				case 'fon': // dodgy hack :)
					return self::parseFont($m[2], $m[3], $oneline);
				case 'span':
					return self::parseSpan($m[3], $m[4]);
				case 'quote': case 'blockquote':
					if($oneline) return $m[2];
					return '<div class="user_quote">'.$m[2].'</div>';
				case 'spoiler':
					return '<span class="user_spoiler">'.$m[3].'</span>';
				case 'big':
					if($oneline) return $m[3];
					// fallthru
				default:
					return self::simpleTag($m[1], $m[2], $m[3]);
			}
		}, $msg);
	}
	
	private static function simpleTag($tag, $ex, $content) {
		static $simple_tag_map = array(
			'b' => 'b',
			'i' => 'i',
			'u' => 'u',
			//'o' => 'o',
			's' => 's',
			
			'strong' => 'b',
			'em' => 'i',
			'cite' => 'i',
			'dfn' => 'i',
			'var' => 'i',
			'code' => 'code',
			'samp' => 'code',
			'kbd' => 'code',
			'strike' => 's',
			
			'bold' => 'b',
			'italic' => 'i',
			'underline' => 'u',
			//'overline' => 'o',
			'sup' => 'sup',
			'sub' => 'sub',
			'super' => 'sup',
			'superscript' => 'sup',
			'subscript' => 'sub',
			
			//'sc' => 'span style="font-variant: small-caps;"'
			'big' => 'big',
			'small' => 'small',
		);
		
		$tag = $simple_tag_map[strtolower($tag)];
		self::doTagReplace($content);
		return '<'.$tag.'>'.$content.'</'.$tag.'>';
	}
	
	private static function parseLink($url, $content='') {
		$url = unhtmlentities($url);
		$url_decode = urldecode($url);
		
		if($p = strpos($url_decode, ':'))
		{
			$uri = strtolower(substr($url_decode, 0, $p));
			if(ctype_alnum($uri)) {
				// prevent $url from starting with "javascript:"
				switch($uri) {
					case 'javascript':
						$url = 'noscript:'.substr($url, 11);
						break;
					case 'mailto':
						// mail icon?
						break;
					case 'http': case 'https': case 'ftp':
						break;
					default:
						// other URI - mark appropriately?
				}
			}
			else
				unset($uri);
		}
		if(!isset($uri))
		{
			// if no URI is supplied, we'll assume it's http
			$url = 'http://'.$url;
		}
		
		if($content) {
			self::doTagReplace($content);
		} else {
			if(!mb_check_encoding($url_decode))
				$url_decode = $url;
			if(mb_strlen($url_decode) > 50) // too long
				$content = self::html_uni(mb_substr($url_decode, 0, 38).'...'.mb_substr($url_decode, -10));
			else
				$content = self::html_uni($url_decode);
		}
		
		return '<a href="'.self::html($url).'" onclick="return AJS.linkTargetBlank(this);">'.$content.'</a>';
	}
	
	private static function parseFont($attrib, $content='', $oneline=false) {
		$attrib = '  '.trim($attrib).'  '; // double-spaces to ensure that substr() later on doesn't have issues
		$match = array();
		$css = '';
		// match face property
		if(preg_match('~\sface=(&quot;|\')([0-9a-z \-_]+)\\1\s~i', $attrib, $match, PREG_OFFSET_CAPTURE)) {
			// remove this from $attrib
			$attrib = '  '.trim(substr($attrib, 0, $match[0][1]).substr($attrib, $match[0][1]+strlen($match[0][0]))).'  ';
			$css .= 'font-family: '.trim($match[2][0]).';';
		}
		// match color property
		if(preg_match('~\scolou?r=(&quot;|\')#?([0-9a-z \-]{1,20})\\1\s~i', $attrib, $match, PREG_OFFSET_CAPTURE)) {
			// remove this from $attrib
			$attrib = '  '.trim(substr($attrib, 0, $match[0][1]).substr($attrib, $match[0][1]+strlen($match[0][0]))).'  ';
			$css .= 'color: '.trim($match[2][0]).';';
		}
		// match size property
		if(preg_match('~\ssize=(&quot;|\')(big|bigger|\+1|small|smaller|\-1)\\1\s~i', $attrib, $match, PREG_OFFSET_CAPTURE)) {
			// remove this from $attrib
			$attrib = '  '.trim(substr($attrib, 0, $match[0][1]).substr($attrib, $match[0][1]+strlen($match[0][0]))).'  ';
			$size = strtolower($match[2][0]);
			if($size == 'big' || $size == 'bigger' || $size == '+1') {
				$size = 'big';
				if($oneline) $size = null;
			} else
				$size = 'small';
		}
		
		self::doTagReplace($content);
		if($css) $content = '<span style="'.$css.'">'.$content.'</span>';
		if(isset($size)) $content = '<'.$size.'>'.$content.'</'.$size.'>';
		return $content;
	}
	
	private static function parseStyle($css) {
		$css = array_map('trim', explode(';', $css));
		// we'll accept font-family, color, background-color, font-weight (normal|bold|bolder|lighter|100-900), text-decoration (none|underline|overline|line-through), font-style (normal|italic|oblique), font-variant (normal|small-caps), vertical-align (baseline|sub|super)
		$entries = array();
		foreach($css as &$elem) {
			if(!($p = strpos($elem, ':')))
				continue;
			$name = strtolower(trim(substr($elem, 0, $p)));
			$val = trim(substr($elem, $p+1));
			$valid = false;
			switch($name) {
				case 'font-family':
					$valid = (preg_match('~^[a-zA-Z0-9 \\-_,]+$~', $val));
					break;
				case 'color':
				case 'background-color':
					$valid = (preg_match('~^#?[a-zA-Z0-9 \-]{1,20}$~', $val));
					// fix for rgb() macro (note, we only do this here :P)
					if(!$valid && preg_match('~^rgb\(([0-9]{1,3}),([0-9]{1,3}),([0-9]{1,3})\)$~i', $val, $m)) {
						unset($m[0]);
						$val = '#';
						foreach($m as $n) {
							if($n > 255) $n = 255;
							$n2 = dechex($n);
							if($n < 16) $n2 = '0'.$n;
							$val .= $n2;
						}
						assert('strlen($val==7)');
					}
					break;
				case 'font-weight':
					$val = strtolower($val);
					$valid = (in_array($val, array('normal','bold','bolder','lighter','100','200','300','400','500','600','700','800','900')));
					break;
				case 'text-decoration':
					$val = strtolower($val);
					$valid = (in_array($val, array('none','underline','overline','line-through')));
					break;
				case 'font-style':
					$val = strtolower($val);
					$valid = (in_array($val, array('normal','italic','oblique')));
					break;
				case 'font-variant':
					$val = strtolower($val);
					if($val=='smallcaps') $val='small-caps';
					$valid = (in_array($val, array('normal','small-caps')));
					break;
				case 'vertical-align':
					$val = strtolower($val);
					if($val=='sup') $val='super';
					$valid = (in_array($val, array('baseline','sub','super')));
					break;
					
			}
			if(!$valid) continue;
			$entries[$name] = $val; // we use an array instead to prevent dupe entries
		}
		
		// now build return string
		if(empty($entries)) return '';
		$ret = $d = '';
		foreach($entries as $n => &$v) {
			$ret .= $d.$n.': '.$v.';';
			if(!$d) $d = ' ';
		}
		return $ret;
	}
	private static function parseSpan($css='', $content='') {
		self::doTagReplace($content);
		return '<span style="'.self::parseStyle($css).'">'.$content.'</span>';
	}
	
	// hacky way to not get confused with double-parsing entities
	// because PHP's preg_replace, using arrays, basically is a preg_replace in a loop, not a continued function... :|
	private static function html($str) {
		return strtr(
			$str,
			array('<' => '&#60;', '>' => '&#62;', '"' => '&#34;')
		);
	}
	private static function html_uni($str) {
		return strtr(
			preg_replace('/&(?!#[0-9]+;)/', '&amp;', $str),
			array('<' => '&#60;', '>' => '&#62;', '"' => '&#34;')
		);
	}

}
