<?php
if(!defined('AT_ROOT')) exit;

$GLOBALS['comment_types'] = $comment_types = array(
	// NOTE: if changing this list, update the "icon" list further down!
	0 => '(none)',
	1 => 'Contains additional download link(s)',
	2 => 'Question',
);

function AT_get_comment_title_html($c) {
	global $AT; // only used for $AT->user
	if($c['uid'])
		$un = htmlspecialchars($c['username']);
	else {
		if($c['name'] != 'Anonymous')
			$un = '<em>Anonymous: &quot;'.htmlspecialchars($c['name']).'&quot;</em>';
		else
			$un = '<em>'.htmlspecialchars($c['name']).'</em>';
	}
	
	$msgtype = '';
	if(@$c['message_type']) {
		$msgtype = '<span class="comment_type" title="'.$GLOBALS['comment_types'][$c['message_type']].'">';
		switch($c['message_type']) {
			case 1: $msgtype .= 'L'; break;
			case 2: $msgtype .= 'Q'; break;
		}
		$msgtype .= '</span> ';
	}
	
	$posttime = $AT->user->fmtDate($c['dateline']);
	if($c['edit_dateline'])
		$posttime = '<span title="Last modified on '.$AT->user->fmtDate($c['edit_dateline']).'">'.$posttime.' *</span>';
	
	return $msgtype.$posttime.' &mdash; <strong>'.$un.'</strong>';
}

function AT_get_avatar_css($comments) {
	$avadone = array();
	$avatar_code = '';
	$user =& $GLOBALS['AT']->user;
	
	// hack to always put the current user's avatar in
	foreach(array_merge($comments, array(array(
		'uid' => $user->uid,
		'avatar' => @$user->data['avatar']
	))) as $c) {
		if($c['uid'] && $c['avatar'] && !isset($avadone[$c['uid']])) {
			$avadone[$c['uid']] = 1;
			$dims = @getimagesizefromstring($c['avatar']);
			if($dims && $dims[0] && $dims[1]) {
				// we give a max display size of 50x50; scale down if over
				if($dims[0] > 50 || $dims[1] > 50) {
					$r = $dims[0] / $dims[1];
					if($r > 1) { // width too large
						$dims[0] = 50;
						$dims[1] = round(50/$r);
					} else { // height too large
						$dims[0] = round(50*$r);
						$dims[1] = 50;
					}
				}
				$avatar_code .= '.user_ava_'.$c['uid'].'{background-image:url("data:'.$dims['mime'].';base64,'.base64_encode($c['avatar']).'");width:'.$dims[0].'px;height:'.$dims[1].'px;background-size:'.$dims[0].'px '.$dims[1].'px}';
			}
		}
	}
	if($avatar_code) $avatar_code = '<style type="text/css">'.$avatar_code.'</style>';
	return $avatar_code;
}

function AT_get_comment_html(&$parser, &$comments, $cid, $opts=[], $recur_level=0, $parent_cid=0)
{
	isset($opts['cid']) or $opts['cid'] = 0;
	isset($opts['parser_prefix']) or $opts['parser_prefix'] = 'toto_comment_';
	isset($opts['readonly']) or $opts['readonly'] = false;
	
	//global $comments;
	global $AT; // only used for $AT->user
	$c = &$comments[$cid];
	
	// set hard max recursion depth of 10
	if($recur_level > 10) die('Internal Error');
	
	$subhtml = $subhtml_hidden = '';
	if($recur_level < 10) {
		if(!empty($c['replies']))
			foreach($c['replies'] as &$scid)
				$subhtml .= AT_get_comment_html($parser, $comments, $scid, $opts, $recur_level+1, $c['cid']);
	}
	elseif($c['numreplies']) {
		$subhtml_hidden = '<a class="comments_hidden" href="'.AT::buildUrl('viewcomments').'">There are '.$AT->user->fmtNum($c['numreplies']).' replie(s) to this comment (click here to show).</a>';
	}
	
	if($cid==@$opts['newcid'])
		$anchor = '<a id="newcomment"></a>';
	else
		$anchor = '';
	
	if(($c['uid'] && $c['uid'] == $AT->user->uid && empty($c['edit_lock'])) || $AT->user->isMod())
		$edit = '<a href="javascript:void(AJS.x.editComment('.$cid.'));">Edit</a> ';
	else
		$edit = '';
	
	$reply_parent_placeholder = '';
	if(!$opts['readonly'] && $c['replydepth'] < 10 && $c['repliesdirect'] < 50) {
		$reply_script = '<a href="javascript:void(AJS.x.replyToComment('.$cid.'));">Reply</a>';
		$reply_noscript = '<noscript>
				<div><label title="Reply to this comment (enter comment in textbox below)"><input type="radio" name="replyto" value="'.$cid.'" class="radio" />Reply</label></div>
			</noscript>';
		
		$subhtml = '<div id="comment_reply_placeholder_'.$cid.'" class="newcomment_placeholder"></div>
			'.$subhtml.'
			<div id="newcomments_placeholder_'.$cid.'"></div>';
		
	} elseif(!$opts['readonly'] && $parent_cid && $comments[$parent_cid]['repliesdirect'] < 50) {
		$reply_script = '<a href="javascript:void(AJS.x.replyToComment('.$cid.',false,'.$parent_cid.'));">Reply</a>';
		$reply_noscript = '<noscript>
				<div><label title="Reply to the parent of this comment (enter comment in textbox below)"><input type="radio" name="replyto" value="'.$parent_cid.'" class="radio" />Reply</label></div>
			</noscript>';
		
		$reply_parent_placeholder = '<div id="comment_reply_placeholder_'.$cid.'" class="newcomment_placeholder"></div>';
		
	} else {
		$reply_script = $reply_noscript = '';
	}
	
	if($reply_script || $edit)
		$script = '<script type="text/javascript">
			<!--
				document.getElementById(\'comment_controls_'.$cid.'\').innerHTML = \''.$edit.$reply_script.'\';
			//-->
			</script>';
	else
		$script = '';
	
	$rating_code = '';
	$comment_rating_class = '';
	$comment_oblivioned = false;
	if(isset($c['rating'])) {
		/*if($c['rating'] < -1)
			$comment_rating_class = ' comment_message_rating_low';
		else*/if($c['rating'] < -4)
			$comment_rating_class = ' comment_message_rating_low'; //vlow
		if($c['rating'] < -8)
			$comment_oblivioned = !$AT->user->isMod();
		if($AT->user->isMember() && !$comment_oblivioned && $c['uid'] != $AT->user->uid) {
			$vu_extra = $vd_extra = '';
			if(@$c['vote']) {
				if($c['vote'] > 0)
					$vu_extra = ' class="voted"';
				elseif($c['vote'] < 0)
					$vd_extra = ' class="voted"';
			}
			$vote_url = function($vote) use($AT, $cid) {
				return AT::buildUrl($AT->input->action, $AT->input->subaction, array('postkey' => $AT->makePostKey($AT->user->uid), 'cid' => $cid, 'do' => 'commentvote', 'vote' => $vote, 'page' => $AT->input->page)).'#comment'.$cid;
			};
			$rating_code = '<span class="comment_voting"><a title="Rate Up" href="'.$vote_url(@$c['vote'] > 0 ? 0:1).'"'.$vu_extra.' onclick="return AJS.x.voteUpComment('.$cid.');" id="comment_voteup_'.$cid.'"><span id="comment_rating_'.$cid.'" vote_state="'.@$c['vote'].'">'.($AT->user->isMod() ? $c['rating']:'').'</span> &#8911;</a><a title="Rate Down" href="'.$vote_url(@$c['vote'] < 0 ? 0:-1).'"'.$vd_extra.' onclick="return AJS.x.voteDownComment('.$cid.');" id="comment_votedown_'.$cid.'">&#8910;</a></span>';
		}
	}
	
	$tagline = '';
	if(@$c['tagline']) {
		$tagline = '<span class="comment_user_tagline">'.$parser->parse('toto_user_tagline_'.$c['uid'], $c['tagline'], array('oneline' => true)).'</span>';
	}
	$avatar = $avahide = '';
	if(@$c['avatar']) {
		$avatar = '<div class="comment_user_avatar user_ava_'.$c['uid'].'" id="comment_ava_'.$cid.'"></div>';
		$avahide = '$id(\'comment_ava_'.$cid.'\').style.display=';
	}
	
	return $anchor.'<a id="comment'.$cid.'"></a><div class="comment'.($recur_level%2 ? '2':'').'">'.$avatar.'<div class="comment_user"><span onclick="(e=$id(\'comment_body_'.$cid.'\').style).display='.$avahide.'(e.display?\'\':\'none\');" id="comment_mod_'.$cid.'">'.AT_get_comment_title_html($c).'</span>'.$tagline.$rating_code.'</div>
	<div id="comment_body_'.$cid.'">'.($comment_oblivioned ? '<div class="comment_oblivioned">This comment has been hidden due to low rating.</div>' : '
		<div id="comment_message_'.$cid.'" class="comment_message'.$comment_rating_class.'">'.$parser->parse($opts['parser_prefix'].$cid, $c['message']).'</div>
		<div>
			<div class="comment_buttons" id="comment_controls_'.$cid.'">
				'.$reply_noscript.'
			</div>
			'.$script.'
			'.$subhtml_hidden.'
		</div>
		<div style="clear: both;"></div>
		').'
		'.$subhtml.'
	</div>
	</div>'.$reply_parent_placeholder;
}

?>