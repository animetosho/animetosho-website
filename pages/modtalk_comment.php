<?php
if(!defined('AT_ROOT')) exit;

if($AT->input->action != 'contact' && !$AT->user->isMod())
	$AT->output->error('You cannot access this page.', 'Permission denied', AT_Output::ERROR_FORBIDDEN);
$isContact = ($AT->input->action == 'contact');

if(!$AT->post_request)
	$AT->output->error('Authorization mismatch.', 'Authorization error', AT_Output::ERROR_FORBIDDEN);

//$AT->user->requireLogin();

$AT->input->parse(array(
	'message' => AT_Input::TYPE_STR,
	'replyto' => AT_Input::TYPE_INT,
	
	'displayname' => AT_Input::TYPE_STR,
	
	'captcha' => AT_Input::TYPE_STR,
	'captcha_hash' => AT_Input::TYPE_STR
));

if($isContact) $AT->input->replyto = 0;

$msglen = strlen($AT->input->message);
if($msglen < 2 || $msglen > 30000)
	$AT->output->error('Comments must be between 2 and 30000 characters long.', 'Bad comment length', AT_Output::ERROR_BADREQ);

if($AT->input->replyto) {
	$cr = $AT->db->selectGetArray('modtalk_comments', '`cid`='.$AT->input->replyto, '`replydepth`,`replytotop`,`parentlist`,`repliesdirect`');
	if(empty($cr))
		$AT->input->replyto = 0;
	elseif($cr['replydepth'] > 10)
		$AT->output->error('Sorry, the maximum comment depth is 10 comments - please reply to a different comment.', 'Comment Depth Limit Reached', AT_Output::ERROR_FORBIDDEN);
	elseif($cr['repliesdirect'] > 50) // not thread safe, but doesn't matter too much if we get slightly over 50
		$AT->output->error('Replies to this comment have exceeded the maximum allowed.  Please reply to a different comment.', 'Comment Reply Limit Reached', AT_Output::ERROR_FORBIDDEN);
}

if($AT->user->isMember())
	$AT->input->displayname = $AT->user->data['username'];
elseif(!$AT->input->displayname)
	$AT->input->displayname = 'Anonymous';
elseif(isset($AT->input->displayname[40]))
	$AT->output->error('Name is too long.', 'Invalid name', AT_Output::ERROR_BADREQ);



// check for dupes
if($AT->db->selectGetField('modtalk_comments', 'cid', '`uid`='.$AT->user->uid.' AND `name`='.$AT->db->escape($AT->input->displayname).' AND `message`='.$AT->db->escape($AT->input->message).' AND `replyto`='.$AT->input->replyto.' AND `dateline`>'.(TIME_NOW-300)))
	$AT->output->error('You have already posted this comment.', 'Duplicate submission', AT_Output::ERROR_BADREQ);


// verify captcha for guests
if(!$AT->user->isMember()) {
	if(!AT_Input::verifyCaptcha($AT->cache, $AT->db, $AT->input->captcha_hash, $AT->input->captcha))
		$AT->output->error('Image verification code incorrect - please go back, refresh the page, and try again. (TIP: <a href="'.AT::buildUrl('register').'">register an account</a> to save you entering these annoying image verifications ;))', 'Invalid code', AT_Output::ERROR_FORBIDDEN);
}

$new_comment = array(
	'message' => $AT->input->message,
	'replyto' => $AT->input->replyto,
	
	'uid' => $AT->user->uid,
	'name' => $AT->input->displayname,
	
	'dateline' => TIME_NOW,
	
	// add in defaults, since we may need these for AJAX replying
	'replydepth' => 0,
	'numreplies' => 0,
	'repliesdirect' => 0,
	'replytotop' => 0,
	'edit_dateline' => 0
	
);
if($AT->input->replyto) {
	$new_comment['replydepth'] = $cr['replydepth'] +1;
	$new_comment['replytotop'] = ($cr['replydepth'] ==0 ? $AT->input->replyto : $cr['replytotop']);
	$new_comment['parentlist'] = $cr['parentlist'].pack('L', $AT->input->replyto);
}
if($isContact) {
	$new_comment['message'] = "<b>Contact Message:</b>\n".$new_comment['message'];
}
$AT->db->insert('modtalk_comments', $new_comment);
$new_comment['cid'] = $AT->db->insertId();
log_activity($new_comment['cid']);

if($AT->input->replyto) {
	$parent_comment_update = array('repliesdirect' => 'repliesdirect+1');
	if($cr['parentlist']) {
		// if this is more than 1 level deep (ie, the comment above has a parentlist)...
		$parents = unpack('L*', $new_comment['parentlist']);
		$AT->db->suppressNotices(true);
		$AT->db->update('modtalk_comments', array('numreplies' => 'numreplies+1'), '`cid` IN ('.implode(',', $parents).')', true);
		$AT->db->suppressNotices(false);
	} else {
		$parent_comment_update['numreplies'] = 'numreplies+1';
	}
	$AT->db->update('modtalk_comments', $parent_comment_update, '`cid`='.$AT->input->replyto, true);
}


if($isContact) {
	$AT->output->redirect($AT->buildUrl('feedback'), 'Thank you, your message has been sent.');
	AT_Shutdown();
}


// do we redirect to a new page?
if(!$AT->input->replyto) {
	$numcomments = $AT->db->selectGetField('modtalk_comments', 'COUNT(*)', '`replydepth`=0');
	$AT->input->parseMultiPage($numcomments, array(), 10, 50);
	$new_comment_pos = $AT->db->selectGetField('modtalk_comments', 'COUNT(*)', '`replydepth`=0 AND `dateline`<'.TIME_NOW); // 0 based number
	$new_comment_page = floor($new_comment_pos / $AT->input->perpage) + 1;
	
	// hack to default to last page
	$curpage = $AT->input->page;
	if(!$AT->input->got('page'))
		$curpage = floor(($numcomments-1) / $AT->input->perpage) +1;
	if($curpage != $new_comment_page) {
		$url_args = array('page' => $new_comment_page);
		if($AT->input->got('perpage'))
			$url_args['perpage'] = $AT->input->perpage;
		// order bys etc not valid here...
		
		$redirect_url = $AT->buildUrl('modtalk', '', $url_args, false).'#comment'.$new_comment['cid'];
	}
}


if($AT->ajax_request) {
	if(isset($redirect_url)) {
		// redirect
		echo '<redirect>', $redirect_url, '</redirect>';
	}
	else {
		// output comment
		// do we search for newer comments?
		
		if(!isset($parser))
		{
			require_once AT_ROOT.'includes/parser.php';
			$parser = new AT_Parser($AT->db, $AT->cache);
		}
		require_once AT_ROOT.'pages/includes/view_funcs.php';
		// add in username here (usually pulled via query)
		$new_comment['username'] = $AT->user->data['username'];
		$new_comment['avatar'] = @$AT->user->data['avatar'];
		$new_comment['tagline'] = @$AT->user->data['tagline'];
		$comments = array($new_comment['cid'] => $new_comment);
		if(!empty($cr) && $AT->input->replyto)
			$comments[$AT->input->replyto] = $cr;
		echo AT_get_comment_html($parser, $comments, $new_comment['cid'], ['parser_prefix'=>'toto_modtalk_comment_'], $new_comment['replydepth'], $AT->input->replyto);
	}
}
else {
	if(isset($redirect_url)) {
		$AT->output->redirect($redirect_url, 'Thank you, your comment has been added, and you will be redirected to it.');
		AT_Shutdown();
	}
	else {
		$AT->input->message = $AT->input->captcha = $AT->input->captcha_hash = '';
		$AT->input->replyto = 0;
	}
}

?>