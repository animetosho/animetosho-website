<?php

class AT_Input extends \stdClass
{
	private $input_source;
	
	const TYPE_MIXED = 0;
	const TYPE_INT = 1;
	const TYPE_STR = 2;
	const TYPE_BOOL = 3;
	const TYPE_FLOAT = 4;
	const TYPE_ARRAY = 256;
	
	function __construct(&$source)
	{
		if(is_array($source))
		{
			if(count($source) > 100) die('Too much input received.');
			$this->input_source = $source;
		}
		else
			$this->input_source = array();
	}
	
	// parse input according to type
	public function get($type, $key, $refetch=false)
	{
		if($key == 'input_source') die('Tried to retrieve invalid input var.');
		
		$this_input = &$this->$key;
		
		if($refetch || !isset($this_input))
		{
			$array = ($type & self::TYPE_ARRAY);
			if($array)
			{
				$type -= self::TYPE_ARRAY;
				$this_input = array();
			}
			
			if(!isset($this->input_source[$key]))
			{
				if(!$array) {
					$tmp = '';
					$this_input = self::typeset($tmp, $type);
				}
			}
			else
			{
				if($array)
				{
					if(is_array($this->input_source[$key])) {
						foreach($this->input_source[$key] as $k => &$v)
							$this_input[$k] = self::typeset($v, $type);
					}
					else
						$this_input[] = self::typeset($this->input_source[$key], $type);
				}
				else
					$this_input = self::typeset($this->input_source[$key], $type);
			}
		}
		return $this_input;
	}
	public static function typeset(&$v, $type)
	{
		if($type == self::TYPE_MIXED) return $v;
		
		switch($type) {
			case self::TYPE_INT: return (int)$v;
			case self::TYPE_FLOAT: return (float)$v;
			case self::TYPE_STR: return @(string)$v;
			case self::TYPE_BOOL: return (bool)$v;
		}
	}
	public function parse($expected) { array_walk($expected, array($this, 'get')); }
	private static function strip_nulls($val) { return is_string($val) ? str_replace("\x0", '', $val) : $val; }
	
	public function got($key) { return isset($this->input_source[$key]); }
	
	
	
	public static function verifyCaptcha(&$cache, &$db, $hash, $key, $delete=true)
	{
		if(!ctype_xdigit($hash) || strlen($hash) != 32 || !ctype_alnum($key) || strlen($key) != 6)
			return false;
		
		$key_int = intval(base_convert(strtr(strtoupper($key), array('Z' => '0', 'Y' => '1')), 34, 10));
		// use the cache if we have it there
		$cachekey = 'captcha_'.$hash;
		if($cache->exists($cachekey)) {
			$realkey_int = $cache->get($cachekey);
			if($delete) {
				$cache->delete($cachekey);
				// remove in DB too!
				$db->delete('captcha', '`hash` = '.$db->escapeHexBin($hash));
			}
			return ($realkey_int == $key_int);
		}
		
		$date = $db->selectGetField('captcha', 'dateline', '`hash` = '.$db->escapeHexBin($hash).' AND `answerkey` = "'.$key_int.'"');
		if(empty($date))
			return false;
		
		// exists in the DB? delete it
		if($delete)
			$db->delete('captcha', '`hash` = '.$db->escapeHexBin($hash));
		if($date < TIME_NOW - AT::CAPTCHA_EXPIRE)
			return false;
		
		return true;
	}
	
	
	
	// parses multi-page input and makes it valid
	// this is ONLY to be used with $AT->input
	// NOTE: for the $order_bys argument, supply a list of valid order by options - if user specifies invalid one, the first in the list is assumed
	public function parseMultiPage($numitems, $order_bys=array(), $perpage_def=20, $perpage_max=200)
	{
		// prevent this function being called twice (makes new comments a little faster)
		static $parsed=false;
		if($parsed) return;
		$parsed = true;
		
		
		//if(!$numitems) return; // causes problems elsewhere, eg perpage not being defined
		$this->parse(array(
			'page' => self::TYPE_INT,
			'perpage' => self::TYPE_INT,
			'orderby' => self::TYPE_STR | self::TYPE_ARRAY,
			'orderasc' => self::TYPE_BOOL | self::TYPE_ARRAY
		));
		
		if(!empty($order_bys)) {
			$order_keys = array_flip($order_bys);
			
			// loop thru orderby/orderasc arrays
			foreach($this->orderby as $k => $v) {
				$v = $this->orderby[$k] = strtolower($this->orderby[$k]);
				if(!isset($order_keys[$v])) {
					unset($this->orderby[$k], $this->orderasc[$k]);
				}
				else {
					if(!isset($this->orderasc[$k]))
						$this->orderasc[$k] = 0;
				}
			}
			if(empty($this->orderby)) {
				$this->orderby = array(reset($order_bys));
				$this->orderasc = array(0);
			}
		}
		
		if($this->perpage < 1 || $this->perpage > $perpage_max) $this->perpage = $perpage_def;
		if($this->page < 1) $this->page = 1;
		$pages = ceil($numitems/$this->perpage);
		if($pages && $this->page > $pages) $this->page = $pages;
	}
	
}

?>