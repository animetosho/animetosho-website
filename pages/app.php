<?php
if(!defined('AT_ROOT')) exit;

switch($sa = $AT->input->subaction) {
	case '':
	case 'node-yencode':
	case 'nyuu':
	case 'parpar':
		header('Location: https://github.com/animetosho/'.$sa);
		break;
	default:
		$AT->output->error('Invalid page.', 'Page not found', AT_Output::ERROR_BADREQ);
}

?>