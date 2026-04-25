<?php
if(!defined('AT_ROOT')) die();

$AT->user->requireLogin();
if(!$AT->post_request)
	$AT->output->error('Authorization mismatch.', 'Authorization error', AT_Output::ERROR_FORBIDDEN);

require_once AT_ROOT.'pages/includes/view_funcs.php';

$AT->input->parse(array(
	'cid' => AT_Input::TYPE_INT,
	'gettext' => AT_Input::TYPE_BOOL,
	'displayname' => AT_Input::TYPE_STR,
	'message' => AT_Input::TYPE_STR,
	'message_type' => AT_Input::TYPE_INT
));

$comment = $AT->db->selectGetArray('comments', '`cid`='.$AT->input->cid, 'comments.*,users.username', array('joins' => array(array('left', 'users', 'uid'))));
if(!$comment || $comment['mid'] != $tfile['id'])
	$AT->output->error('The specified comment was not found.', 'Comment not found', AT_Output::ERROR_NOTFOUND);

// is user allowed to edit comment?
if(($comment['uid'] != $AT->user->uid || !empty($comment['edit_lock'])) && !$AT->user->isMod())
	$AT->output->error('You cannot edit this comment.', 'Permission denied', AT_Output::ERROR_FORBIDDEN);

// are we just grabbing the comment code?
if($AT->input->gettext && $AT->ajax_request) {
	// output editor form and exit
	require_once AT_ROOT.'includes/output_form.php';
	$form = new AT_Output_Form($AT->input); // though we don't use the input here...
	$form->start(Toto::viewUrl($tfile), $AT->postKey, 'editcomment', 'return AJS.x.submitEditComment(this,'.$comment['cid'].');', 'AJS.x.cancelEditComment();');
	if(!$comment['uid']) {
		echo '<strong>Name:</strong> ', $form->textboxS('displayname', $comment['name'], false, 40, 25);
		echo '<br />';
	}
	echo '<strong>Comment Type:</strong> ', $form->selectS('message_type', $comment_types, $comment['message_type']);
	echo '<br />';
	echo $form->editorS('message', $comment['message'], 4, 70, 'style="width: 100%;"', 'if(!t)return"You must enter a message to post.";if(t.length<2)return"Sorry, message must be at least 2 characters long.";if(t.length>5000)return"Comment is too long - please keep it under 5000 characters!";');
	
	echo '<div class="form_footer">';
	$form->endButtons('Save Edit'); // need to change reset label
	$form->hidden(array('do' => 'editcomment', 'cid' => $comment['cid']));
	echo '</div>';
	
	$form->end();
	
	echo '<script type="text/javascript"><!--
			document.getElementById("editcomment_reset").value = "Cancel";
		//-->
		</script>';
	return;
}

$msglen = strlen($AT->input->message);
if($msglen < 2 || $msglen > 5000)
	$AT->output->error('Comments must be between 2 and 5000 characters long.', 'Bad comment length', AT_Output::ERROR_BADREQ);

if(!isset($comment_types[$AT->input->message_type]))
	$AT->input->message_type = 0;

// update comment
$update = array(
	'message' => $AT->input->message,
	'message_type' => $AT->input->message_type,
	'edit_dateline' => TIME_NOW
);
if(!$comment['uid'] && $AT->input->displayname && !isset($AT->input->displayname[40]))
	$update['name'] = $AT->input->displayname;
// lock comments removed by mod from being added back
if($AT->user->isMod() && $comment['uid'] && stripos($AT->input->message, '[removed') !== false)
	$update['edit_lock'] = 1;
$AT->db->update('comments', $update, '`cid`='.$AT->input->cid);




if($AT->ajax_request)
{
	$comment = array_merge($comment, $update);
	// delete cached message from parser, and parse new message and output it
	echo '<modtitle>', AT_get_comment_title_html($comment), '</modtitle>';
	if(!isset($parser))
	{
		require_once AT_ROOT.'includes/parser.php';
		$parser = new AT_Parser($AT->db, $AT->cache);
	}
	$parser->deleteCached('toto_comment_'.$comment['cid']);
	echo $parser->parse('toto_comment_'.$comment['cid'], $AT->input->message);
}
else
{
	// essentially do nothing
}

?>