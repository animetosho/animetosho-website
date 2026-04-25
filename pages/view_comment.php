<?php
if(!defined('AT_ROOT')) exit;

if(!$AT->post_request)
	$AT->output->error('Authorization mismatch.', 'Authorization error', AT_Output::ERROR_FORBIDDEN);

if($tfile['deleted'] && !$AT->user->isMod() && $tfile['dateline'] < TIME_NOW-86400*365 && (!$tfile['comments_locked'] || $AT->user->isMod()))
	$AT->output->error('Commenting has been disabled for this entry.', 'Commenting not allowed', AT_Output::ERROR_FORBIDDEN);

//$AT->user->requireLogin();
require_once AT_ROOT.'pages/includes/view_funcs.php';

$AT->input->parse(array(
	'message' => AT_Input::TYPE_STR,
	'message_type' => AT_Input::TYPE_INT,
	'replyto' => AT_Input::TYPE_INT,
	
	'displayname' => AT_Input::TYPE_STR,
	
	'captcha' => AT_Input::TYPE_STR,
	'captcha_hash' => AT_Input::TYPE_STR
));

$msg =& $AT->input->message;
$msglen = strlen($msg);
if($msglen < 2 || $msglen > 5000)
	$AT->output->error('Comments must be between 2 and 5000 characters long.', 'Bad comment length', AT_Output::ERROR_BADREQ);

// note that this feature was only added after comment ID #9590 was posted
if(!isset($comment_types[$AT->input->message_type]))
	$AT->input->message_type = 0;



if($AT->input->replyto) {
	if($AT->input->replyto < 0)
		$AT->output->error('Invalid comment parent.', 'Invalid comment', AT_Output::ERROR_FORBIDDEN);
	
	$cr = $AT->db->selectGetArray('comments', '`cid`='.$AT->input->replyto, '`replydepth`,`replytotop`,`parentlist`,`repliesdirect`');
	if(empty($cr))
		$AT->input->replyto = 0;
	elseif($cr['replydepth'] > 10)
		$AT->output->error('Sorry, the maximum comment depth is 10 comments - please reply to a different comment.', 'Comment Depth Limit Reached', AT_Output::ERROR_FORBIDDEN);
	elseif($cr['repliesdirect'] > 50) // not thread safe, but doesn't matter too much if we get slightly over 50
		$AT->output->error('Replies to this comment have exceeded the maximum allowed.  Please reply to a different comment.', 'Comment Reply Limit Reached', AT_Output::ERROR_FORBIDDEN);
}

if($AT->user->isMember())
	$AT->input->displayname = $AT->user->data['username'];
else {
	$AT->input->displayname = trim($AT->input->displayname);
	if($nameErr = validate_display_name($AT->input->displayname))
		$AT->output->error($nameErr, 'Invalid name', AT_Output::ERROR_BADREQ);
}


// check for dupes
if($AT->db->selectGetField('comments', 'cid', '`uid`='.$AT->user->uid.' AND `name`='.$AT->db->escape($AT->input->displayname).' AND `message`='.$AT->db->escape($AT->input->message).' AND `replyto`='.$AT->input->replyto.' AND `dateline`>'.(TIME_NOW-300)))
	$AT->output->error('You have already posted this comment.', 'Duplicate submission', AT_Output::ERROR_BADREQ);


// verify captcha for guests
if(!$AT->user->isMember()) {
	if(!AT_Input::verifyCaptcha($AT->cache, $AT->db, $AT->input->captcha_hash, $AT->input->captcha))
		$AT->output->error('Image verification code incorrect - please go back, refresh the page, and try again.  (TIP: <a href="'.AT::buildUrl('register').'">register an account</a> to save you entering these annoying image verifications ;))', 'Invalid code', AT_Output::ERROR_FORBIDDEN);
} else {
	// flood check
	if($AT->db->selectGetField('comments', 'COUNT(*)', '`uid`='.$AT->user->uid.' AND `dateline`>'.(TIME_NOW-1800)) > 10)
		$AT->output->error('You are posting too many comments.', 'Comment flood', AT_Output::ERROR_BADREQ);
}

require_once AT_ROOT.'pages/includes/comment_filter.php';


$new_comment = array(
	'message' => $AT->input->message,
	'message_type' => $AT->input->message_type,
	'replyto' => $AT->input->replyto,
	
	'uid' => $AT->user->uid,
	'name' => $AT->input->displayname,
	'mid' => $tfile['id'],
	
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
$AT->db->insert('comments', $new_comment);
$new_comment['cid'] = $AT->db->insertId();
log_activity($new_comment['cid']);

if(!isset($tfile['comments'])) {
	// probably doesn't exist
	$AT->db->insert('toto_meta', array('id' => $tfile['id']), false, true);
	$tfile['comments'] = $tfile['comments_top'] = 0;
}

$media_update = array('comments' => 'comments+1');
// change the internal counter too
++$tfile['comments'];
if(!$AT->input->replyto) {
	$media_update['comments_top'] = 'comments_top+1';
	++$tfile['comments_top'];
}
$AT->db->update('toto_meta', $media_update, '`id`='.$tfile['id'], true);
if($AT->user->uid)
	$AT->db->update('users', array('numcomments' => 'numcomments+1'), '`uid`='.$AT->user->uid, true);

if($AT->input->replyto) {
	$parent_comment_update = array('repliesdirect' => 'repliesdirect+1');
	if($cr['parentlist']) {
		// if this is more than 1 level deep (ie, the comment above has a parentlist)...
		$parents = unpack('L*', $new_comment['parentlist']);
		$AT->db->suppressNotices(true);
		$AT->db->update('comments', array('numreplies' => 'numreplies+1'), '`cid` IN ('.implode(',', $parents).')', true);
		$AT->db->suppressNotices(false);
	} else {
		$parent_comment_update['numreplies'] = 'numreplies+1';
	}
	$AT->db->update('comments', $parent_comment_update, '`cid`='.$AT->input->replyto, true);
}

//++$AT->user->data['numcomments'];


// do we redirect to a new page?
if(!$AT->input->replyto) {
	$AT->input->parseMultiPage($tfile['comments_top'], array(), 10, 50);
	$new_comment_pos = $AT->db->selectGetField('comments', 'COUNT(*)', '`mid`='.$new_comment['mid'].' AND `replydepth`=0 AND `dateline`<'.TIME_NOW); // 0 based number
	$new_comment_page = floor($new_comment_pos / $AT->input->perpage) + 1;
	if($AT->input->page != $new_comment_page) {
		$url_args = array('page' => $new_comment_page);
		if($AT->input->got('perpage'))
			$url_args['perpage'] = $AT->input->perpage;
		// order bys etc not valid here...
		
		$redirect_url = unhtmlentities(Toto::viewUrl($tfile, $url_args)).'#comment'.$new_comment['cid'];
	}
}

if(!$AT->user->isMember() && strtolower($AT->input->displayname) != 'anonymous')
	$AT->output->setCookie('displayname', $AT->input->displayname);

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
		echo AT_get_comment_html($parser, $comments, $new_comment['cid'], [], $new_comment['replydepth'], $AT->input->replyto);
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