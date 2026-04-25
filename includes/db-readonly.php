<?php
defined('AT_ROOT') or die;

require AT_ROOT.'includes/db.php';

class DB_readonly extends ATCore_DB_MySQLi {
	public const readonly = true;
	public function insert($table, $row, $replace = false, $ignore = false) {
		return true;
	}
	public function insertMulti($table, $rows, $replace = false, $ignore = false) {
		return true;
	}
	public function delete($table, $where='', $limit=0, $order='') {
		return 0;
	}
	public function update($table, $data, $where='', $expr = false, $limit=0) {
		return 0;
	}
}
