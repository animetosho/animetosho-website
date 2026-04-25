<?php
if(!defined('AT_ROOT')) exit;

if(!$AT->validPostKey($AT->input->postkey, $AT->user->uid))
	$AT->output->error('Authorization mismatch.', 'Authorization error', AT_Output::ERROR_FORBIDDEN);

$AT->user->requireLogin();
require_once AT_ROOT.'pages/includes/view_funcs.php';

$AT->input->parse(array(
	'cid' => AT_Input::TYPE_INT,
	'vote' => AT_Input::TYPE_INT,
));

if($AT->input->vote < -1 || $AT->input->vote > 1)
	$AT->output->error('Supplied rating is not valid.', 'Invalid rating', AT_Output::ERROR_FORBIDDEN);

// get existing vote
$cr = $AT->db->selectGetArray('comments', '`comments`.`cid`='.$AT->input->cid, '`vote`,`rating`,`comments`.`uid`', array('joins' => array(
	array('left', 'comment_votes', 'cid', 'cid` AND '.$AT->user->uid.'=`comment_votes`.`uid')
)));
if(empty($cr))
	$AT->output->error('Cannot rate a comment that does not exist.', 'Invalid comment', AT_Output::ERROR_FORBIDDEN);

if($AT->user->uid == $cr['uid'])
	$AT->output->error('Sorry, you cannot rate your own comments.', 'Cannot rate own comment', AT_Output::ERROR_FORBIDDEN);

$cr['vote'] = (int)$cr['vote'];
if($cr['vote'] == $AT->input->vote)
	$AT->output->error('Rating has already been submitted.', 'Duplicate rating', AT_Output::ERROR_FORBIDDEN);

$vote = array(
	'cid' => $AT->input->cid,
	'uid' => $AT->user->uid,
	'dateline' => TIME_NOW,
	'vote' => $AT->input->vote,
	'idx_mid' => $tfile['id']
);
$AT->db->insert('comment_votes', $vote, true);

// update comment rating
$diff = $AT->input->vote - $cr['vote'];
$cr['rating'] += $diff;
$AT->db->update('comments', array('rating' => 'rating+'.$diff), 'cid='.$AT->input->cid, true, 1);


if($AT->ajax_request) {
	echo 'ok';
}
?>