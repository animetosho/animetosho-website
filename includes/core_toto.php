<?php
if(!defined('AT_ROOT')) exit;

require AT_ROOT.'includes/core.php';

class Toto extends AT {
	private static function totoId($toto) {
		if($toto['tosho_id']) return $toto['tosho_id'];
		if($toto['nyaa_id']) return (@$toto['nyaa_subdom']?'s':'n').$toto['nyaa_id'];
		if($toto['anidex_id']) return 'd'.$toto['anidex_id'];
		if($toto['nekobt_id']) return 'k'.$toto['nekobt_id'];
		if(isset($toto['toto_id']))
			return 'a'.$toto['toto_id'];
		return 'a'.$toto['id'];
	}
	public static function viewUrl($toto, $args=array(), $perma=false) {
		$id = self::totoId($toto);
		if(!$perma)
			$id = self::seoPageSubaction($id, $toto['name']);
		return self::buildUrl('view', $id, $args);
	}
	public static function viewSubaction($toto) {
		$id = self::seoPageSubaction(self::totoId($toto), $toto['name']);
		return $id;
	}
	
	public static function getFileLink($url, $force_enc=false) {
		return $url;
	}
}

$AT = new Toto;
?>