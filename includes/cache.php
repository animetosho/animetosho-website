<?php

abstract class ATCore_Cache
{
	abstract public function get($name);
	abstract public function set($name, &$value, $ttl=0);
	abstract public function exists($name);
	abstract public function delete($name);
}

class ATCore_Cache_Xcache extends ATCore_Cache
{
	private $prefix='';
	public function __construct($prefix) {
		$this->prefix = $prefix;
	}
	
	public function get($name) {
		return xcache_get($this->prefix.$name);
	}
	public function set($name, &$value, $ttl=0) {
		return xcache_set($this->prefix.$name, $value, $ttl);
	}
	public function exists($name) {
		return xcache_isset($this->prefix.$name);
	}
	public function delete($name) {
		return xcache_unset($this->prefix.$name);
	}
}

class ATCore_Cache_Apc extends ATCore_Cache
{
	private $prefix='';
	public function __construct($prefix) {
		$this->prefix = $prefix;
	}
	
	public function get($name) {
		$v = apc_fetch($this->prefix.$name);
		if($v === false) return null;
		return $v;
	}
	public function set($name, &$value, $ttl=0) {
		return apc_store($this->prefix.$name, $value, $ttl);
	}
	public function exists($name) {
		return apc_exists($this->prefix.$name);
	}
	public function delete($name) {
		return apc_delete($this->prefix.$name);
	}
}
class ATCore_Cache_Apcu extends ATCore_Cache
{
	private $prefix='';
	public function __construct($prefix) {
		$this->prefix = $prefix;
	}
	
	public function get($name) {
		$v = apcu_fetch($this->prefix.$name);
		if($v === false) return null;
		return $v;
	}
	public function set($name, &$value, $ttl=0) {
		return apcu_store($this->prefix.$name, $value, $ttl);
	}
	public function exists($name) {
		return apcu_exists($this->prefix.$name);
	}
	public function delete($name) {
		return apcu_delete($this->prefix.$name);
	}
}

// a dummy file cache
class ATCore_Cache_Dummy extends ATCore_Cache
{
	public function get($name) {
		if(!$this->exists($name)) return null;
		return unserialize(file_get_contents(AT_ROOT.'dummycache/'.$name));
	}
	public function set($name, &$value, $ttl=0) {
		file_put_contents(AT_ROOT.'dummycache/'.$name, serialize($value));
	}
	public function exists($name) {
		return file_exists(AT_ROOT.'dummycache/'.$name);
	}
	public function delete($name) {
		if($this->exists($name))
			unlink(AT_ROOT.'dummycache/'.$name);
	}
}

// null cache for when things fail
class ATCore_Cache_Null extends ATCore_Cache
{
	public function get($name) {
		return null;
	}
	public function set($name, &$value, $ttl=0) {
	}
	public function exists($name) {
		return false;
	}
	public function delete($name) {
	}
}

?>