<?php
if(!defined('AT_ROOT')) exit;

function isTorExitNode($ip){
	$ip = array_reverse(explode('.',$ip));
	if(count($ip) != 4) return false;
	return (@gethostbyname(implode('.', $ip).'.dnsel.torproject.org')=='127.0.0.2');
}

if(!$AT->user->isMember()) {
	
	if($_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.0' && $AT->input->displayname != 'Anonymous') {
		$AT->output->error('Your message has been flagged by the spam filter. If this is incorrect, please <a href="'.AT::buildUrl('register').'">register an account</a> if you wish to post this comment.', 'Spam detected', AT_Output::ERROR_FORBIDDEN);
	}
	
	if(@$_SERVER['HTTP_HOST'] == TOR_HOST || REMOTE_IP == '127.0.0.1' || REMOTE_IP == '::1')
		$AT->output->error('Posting from this location anonymously is not allowed.', 'Post banned', AT_Output::ERROR_FORBIDDEN);
	
	// check for Tor
	/*
	if(isTorExitNode(REMOTE_IP)) {
		$AT->output->error('Sorry, posting anonymously from Tor is not allowed. If you need to comment, you will need to disable Tor or register an account.', 'Tor banned', AT_Output::ERROR_FORBIDDEN);
	}
	*/
}

$floodCheckBaseWhere = '`ip`='.$AT->db->escape(REMOTE_IP).' AND `action`='.$AT->db->escape($AT->input->action).' AND `dateline`>'.(TIME_NOW-3600);

if($AT->input->action == 'feedback') {
	if(!$AT->user->isMember()) {
		if($AT->db->selectGetField('activity_log', 'COUNT(*)', $floodCheckBaseWhere) > 5)
			$AT->output->error('You are posting too many comments.', 'Comment flood', AT_Output::ERROR_BADREQ);
	}

	if($AT->user->uid && $AT->user->data['regdate'] > (TIME_NOW-86400)) {
		$thresh = 8;
		if($AT->input->action == 'feedback') {
			if($AT->db->selectGetField('feedback_comments', 'COUNT(*)', '`uid`='.$AT->user->uid.' AND `dateline`>'.(TIME_NOW-300)) > $thresh)
				$AT->output->error('You are posting too many comments.', 'Comment flood', AT_Output::ERROR_BADREQ);
		}
	}
}

if($AT->input->action == 'view') {
	if(!$AT->user->isMember()) {
		// flood check
		// TODO: if enough comments posted by this IP, check for post similarity
		
		if($AT->db->selectGetField('activity_log', 'COUNT(*)', $floodCheckBaseWhere) > 10)
			$AT->output->error('You are posting too many comments.', 'Comment flood', AT_Output::ERROR_BADREQ);
	}
}
