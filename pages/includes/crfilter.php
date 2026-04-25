<?php

if(!defined('AT_ROOT')) exit;

$blocked_aids = array(
);

if($GLOBALS['AT']->db::readonly) {
	$blocked_vids = [
	];
} else {
	$blocked_vids = array(
	);
}

$noindex_keywords = array(
);
