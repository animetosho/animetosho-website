<?php
if(!defined('AT_ROOT')) die();

if(!$AT->user->isMod())
	$AT->output->error('You cannot access this page.', 'Permission denied', AT_Output::ERROR_FORBIDDEN);

if(!$AT->post_request)
	$AT->output->error('Authorization mismatch.', 'Authorization error', AT_Output::ERROR_FORBIDDEN);

require_once AT_ROOT.'pages/includes/view_funcs.php';


$AT->input->parse(array(
	'cid' => AT_Input::TYPE_INT,
	'gettext' => AT_Input::TYPE_BOOL,
	//'displayname' => AT_Input::TYPE_STR,
	'message' => AT_Input::TYPE_STR
));

$comment = $AT->db->selectGetArray('modtalk_comments', '`cid`='.$AT->input->cid, 'modtalk_comments.*, users.username', array('joins' => array(array('left', 'users', 'uid'))));
if(!$comment)
	$AT->output->error('The specified comment was not found.', 'Comment not found', AT_Output::ERROR_NOTFOUND);

// is user allowed to edit comment?
if($comment['uid'] != $AT->user->uid && !$AT->user->isMod())
	$AT->output->error('You cannot edit this comment.', 'Permission denied', AT_Output::ERROR_FORBIDDEN);

// are we just grabbing the comment code?
if($AT->input->gettext && $AT->ajax_request) {
	// output editor form and exit
	require_once AT_ROOT.'includes/output_form.php';
	$form = new AT_Output_Form($AT->input); // though we don't use the input here...
	$form->start($AT->buildUrl('modtalk'), $AT->postKey, 'editcomment', 'return AJS.x.submitEditComment(this,'.$comment['cid'].');', 'AJS.x.cancelEditComment();');
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


// update comment
$update = array(
	'message' => $AT->input->message,
	'edit_dateline' => TIME_NOW
);
$AT->db->update('modtalk_comments', $update, '`cid`='.$AT->input->cid);




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
	$parser->deleteCached('toto_modtalk_comment_'.$comment['cid']);
	echo $parser->parse('toto_modtalk_comment_'.$comment['cid'], $AT->input->message);
}
else
{
	// essentially do nothing
}

?>