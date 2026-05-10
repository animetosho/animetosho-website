<?php
if(!defined('AT_ROOT')) exit;

switch($sa = $AT->input->subaction) {
	case 'minus':
	case 'anonfiles':
	case 'filtseries':
	case 'endofbeta':
	case 'news':
	case 'logistics':
	case 'shutdown':
	case 'shutdown2':
	case 'data':
		break;
	default:
		$sa = 'index';
}

include AT_ROOT.'pages/about/'.$sa.'.php';

$AT->output->printFooter();


?>