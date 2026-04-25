<?php
if(!defined('AT_ROOT')) exit;

if(!$AT->user->isMod())
	$AT->output->error('You cannot access this page.', 'Permission denied', AT_Output::ERROR_FORBIDDEN);

// proxy to backend
$url = $AT->input->get(AT_Input::TYPE_STR, 'subaction');
if($p = strpos($_SERVER['REQUEST_URI'], '?'))
	$url .= substr($_SERVER['REQUEST_URI'], $p);
@list($host,) = explode('/', $url, 2);

if(!ctype_alnum($host)) die('Invalid server');

header('X-Accel-Redirect: /__proxy_updates/'.@$_SERVER['REQUEST_METHOD'].'/'.$host.'/'.$url);
