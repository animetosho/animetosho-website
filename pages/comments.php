<?php
if(!defined('AT_ROOT')) exit;

require_once AT_ROOT.'pages/includes/view_funcs.php';

$AT->input->parse(array(
	'page' => AT_Input::TYPE_INT,
	'perpage' => AT_Input::TYPE_INT,
	'feedback' => AT_Input::TYPE_BOOL,
	'modtalk' => AT_Input::TYPE_BOOL,
	'filter_types' => AT_Input::TYPE_INT | AT_Input::TYPE_ARRAY,
	
	'search' => AT_Input::TYPE_STR,
	'q' => AT_Input::TYPE_STR,
	'user' => AT_Input::TYPE_STR,
));
if($AT->input->page < 1) $AT->input->page = 1;
if($AT->input->perpage < 1) $AT->input->perpage = 50;
if($AT->input->perpage > 200) $AT->input->perpage = 50;

$AT->output->title = 'Latest Comments';

$comments_perpage = 10; // number of top-level comments displayed on a view page
$urlargs = array();
if(!empty($AT->input->filter_types))
	$urlargs['filter_types'] = $AT->input->filter_types;
if($AT->input->feedback)
	$urlargs['feedback'] = '1';
if(!$AT->user->isMod())
	$AT->input->modtalk = false;
if($AT->input->modtalk)
	$urlargs['modtalk'] = '1';

// if nothing is selected, select everything
if(!$AT->input->feedback && !$AT->input->modtalk && empty($AT->input->filter_types)) {
	$AT->input->feedback = true;
	if($AT->user->isMod()) $AT->input->modtalk = true;
	$AT->input->filter_types = array_keys($comment_types);
}
sort($AT->input->filter_types); // potentially helps with query caching

$msg_types = $AT->input->filter_types;
if($AT->input->feedback)
	$msg_types[] = -1;
if($AT->input->modtalk)
	$msg_types[] = -2;

if(empty($msg_types)) {
	// should never happen, but fallback in case we have logic fail
	$msg_types = array_keys($comment_types);
}

$parser_load = array();
$comments = array();
$search_sect = '';

if($AT->input->got('search') && $AT->user->isMod()) {
	$multipage = '';
	$qwhere = '1=1';
	
	//unset($urlargs['filter_types'], $urlargs['feedback'], $urlargs['modtalk']);
	
	$search_sect = '';
	if($AT->input->search == 'feedback' || ($AT->input->search == 'modtalk' && $AT->user->isMod()))
		$search_sect = $AT->input->search.'_';
	$table = $search_sect.'comments';
	
	if($AT->input->user) {
		$qwhere = 'users.username = '.$AT->db->escape($AT->input->user);
	}
	if($AT->input->q) {
		$kw = array_filter(explode(' ', $AT->input->q));
		$kw = array_map(function($t) use(&$AT) {
			return $AT->db->escape('%'.$t.'%');
		}, $kw);
		$qwhere .= ' AND '.$table.'.message LIKE '.implode(' AND '.$table.'.message LIKE ', $kw);
	}
	
	$qopts = array(
		'order' => $table.'.dateline DESC',
		'limit' => $AT->input->perpage,
		'limit_start' => ($AT->input->page-1)*$AT->input->perpage,
		'joins' => [
			array('left', 'users', 'uid'),
			'LEFT JOIN '.$AT->db->tableName($table).' c2 ON '.$table.'.replytotop = c2.cid'
		]
	);
	
	$AT->db->suppressNotices(true);
	if($AT->input->search == 'feedback' || $AT->input->search == 'modtalk') {
		$AT->db->select($table, $qwhere, $table.'.*, IF(c2.dateline IS NULL, '.$table.'.dateline, c2.dateline) AS top_dateline, users.username', $qopts);
	} else {
		$qopts['joins'][] = array('inner', 'toto', 'mid', 'id');
		$qopts['joins'][] = array('left', 'toto_meta', 'mid', 'id');
		$AT->db->select($table, $qwhere, $table.'.*, IF(c2.dateline IS NULL, '.$table.'.dateline, c2.dateline) AS top_dateline, toto.name AS page_name, toto.tosho_id, toto.nyaa_id, toto.nyaa_subdom, toto.anidex_id, toto.nekobt_id, toto.id, toto_meta.comments_top, users.username', $qopts);
	}
	while($comment = $AT->db->fetchArray()) {
		$comment['section'] = $search_sect;
		$comments[$search_sect.$comment['cid']] = $comment;
		$parser_load[] = 'toto_'.$search_sect.'comment_'.$comment['cid'];
	}
	$AT->db->suppressNotices(false);
} else {
	$qwhere = 'comments_all.message_type IN ('.implode(',', $msg_types).')';

	$AT->db->suppressNotices(true);
	$multipage = AT_Output::stdMultipageHandler($AT->input, $AT->db->selectGetField('comments_all', 'COUNT(*)', $qwhere), 'comments', '', $urlargs, array(), 50, 200, true);
	// pull out all IDs that we need
	$AT->db->select('comments_all', $qwhere, 'section,cid,top_dateline', array('order' => 'dateline DESC', 'limit' => $AT->input->perpage, 'limit_start' => ($AT->input->page-1)*$AT->input->perpage));
	$AT->db->suppressNotices(false);

	$fetch_comments = array(''=>array(), 'feedback'=>array(), 'modtalk'=>array());
	while($comment = $AT->db->fetchArray()) {
		$comments[$comment['section'].'_'.$comment['cid']] = $comment['top_dateline']; // forces correct ordering
		$fetch_comments[$comment['section']][] = $comment['cid'];
		$parser_load[] = 'toto_'.($comment['section'] ? $comment['section'].'_':'').'comment_'.$comment['cid'];
	}
	// actually fetch comment data
	$AT->db->suppressNotices(true);
	foreach($fetch_comments as $sect=>$cids) {
		if(empty($cids)) continue;
		if($sect) {
			$AT->db->select($sect.'_comments', 'cid IN ('.implode(',', $cids).')', $sect.'_comments.*, users.username', array('joins' => array(
				array('left', 'users', 'uid')
			)));
		} else {
			$AT->db->select('comments', 'cid IN ('.implode(',', $cids).')', 'comments.*, toto.name AS page_name, toto.tosho_id, toto.nyaa_id, toto.nyaa_subdom, toto.anidex_id, toto.nekobt_id, toto.id, toto_meta.comments_top, users.username', array('joins' => array(
				array('inner', 'toto', 'mid', 'id'),
				array('left', 'toto_meta', 'mid', 'id'),
				array('left', 'users', 'uid')
			)));
		}
		while($comment = $AT->db->fetchArray()) {
			$comment['section'] = $sect ? $sect.'_':'';
			$comment['top_dateline'] = $comments[$sect.'_'.$comment['cid']];
			$comments[$sect.'_'.$comment['cid']] = $comment;
		}
	}
	$AT->db->suppressNotices(false);
}


/*
// TODO: implement this filter in the above count
if(!$AT->user->isMember()) {
	include AT_ROOT.'pages/includes/crfilter.php';
	if(!empty($blocked_aids))
		$qwhere .= ' AND toto.aid NOT IN ('.implode(',', $blocked_aids).')';
}
*/

$AT->output->printHeader(0);
$AT->output->printTitleAndDesc('Latest Comments', '');

?>
<div class="home_list_filter" style="border-width: 1px; border-style: solid none;"><form action="<?php echo AT::buildUrl($AT->input->action) ?>" method="get">
<table style="border-spacing: 0;"><tr><td style="vertical-align: top; padding: 0;">Comment Types:</td><td style="padding: 0;"><?php
foreach($comment_types as $k => $v) {
	$chk = '';
	if(in_array($k, $AT->input->filter_types))
		$chk = ' checked="checked"';
	echo '<label><input type="checkbox" name="filter_types[]" value="'.$k.'"'.$chk.' />'.$v.'</label> ';
}
?>
<br />
<label><input type="checkbox" name="feedback" value="1" <?php if($AT->input->feedback) echo 'checked="checked"'; ?>/>Feedback</label>
<?php if($AT->user->isMod()) { ?><label><input type="checkbox" name="modtalk" value="1" <?php if($AT->input->modtalk) echo 'checked="checked"'; ?>/>ModTalk</label><?php } ?>
</td><td style="vertical-align: bottom; padding-left: 0.5em;"><input type="submit" value="Apply" /></td></tr></table>
</form></div>
<?php
if($AT->user->isAdmin()) {
?>
<div class="home_list_filter" style="border-width: 1px; border-style: none none solid none;"><form action="<?php echo AT::buildUrl($AT->input->action) ?>" method="get">
<table style="border-spacing: 0;">
<tr><td style="vertical-align: top;">Search:</td><td colspan="2"><input type="text" name="q" placeholder="keywords" value="<?=htmlspecialchars($AT->input->q)?>" size="30" class="text" /> <input type="text" name="user" placeholder="username" value="<?=htmlspecialchars($AT->input->user)?>" size="20" class="text" /></td>
</tr>
<tr>
<td style="vertical-align: top;">Comment Type:</td><td><select name="search" style="width: 10em;">
<option value="" <?=$search_sect==''?'selected="selected"':''?>>Comments</option>
<option value="feedback" <?=$search_sect=='feedback'?'selected="selected"':''?>>Feedback</option>
<?php if($AT->user->isMod()) { ?><option value="modtalk" <?=$search_sect=='modtalk'?'selected="selected"':''?>>Modtalk</option><?php } ?>
</select></td>
<td style="vertical-align: bottom; padding-left: 0.5em;"><input type="submit" value="Search" /></td>
</tr></table>
</form></div>
<?php
}

echo $multipage;

if(empty($comments)) {
	echo 'No comments found.';
	$AT->output->printFooter();
	return;
}

// output comments
if(!isset($parser)) {
	require_once AT_ROOT.'includes/parser.php';
	$parser = new AT_Parser($AT->db, $AT->cache);
}
$parser->load($parser_load);
unset($parser_load);

$comment_pagenum = array();
foreach($comments as &$comment) {
	if(!is_array($comment)) continue; // data fail
	$urlargs = array();
	
	$top_cid = $comment['replytotop'] ?: $comment['cid'];
	$pagenum_cache =& $comment_pagenum[$comment['section']][$top_cid];
	if(!isset($pagenum_cache))
		$pagenum_cache = $AT->cache->get($comment['section'].'commentpage_'.$comment['cid']);
	if(!isset($pagenum_cache)) {
		if(!$comment['section'] && $comment['comments_top'] <= $comments_perpage)
			// if there aren't enough comments on the page, page 1 is implied
			$pagenum_cache = 1;
		else {
			// check which page this comment is on
			$comment_pos = $AT->db->selectGetField($comment['section'].'comments', 'COUNT(*)', ($comment['section']?'':'`mid`='.$comment['mid'].' AND ').'`replydepth`=0 AND `dateline`<'.$comment['top_dateline']); // 0 based number
			$pagenum_cache = floor($comment_pos / $comments_perpage) + 1;
		}
		$AT->cache->set($comment['section'].'commentpage_'.$comment['cid'], $pagenum_cache, 600);
	}
	$urlargs = array('page' => $pagenum_cache);
	
	if($comment['section']) {
		$sect = substr($comment['section'], 0, -1);
		if($urlargs['page'] == 1) $urlargs['page'] = -1; // hack to force page 1 to show, since it's all reversed
		$url = AT::buildUrl($sect, '', $urlargs);
		$baseurl = AT::buildUrl($sect);
		$comment['message_type'] = 0;
		$cmtclass = 'comment2';
		$comment['page_name'] = '<em>'.ucfirst($sect).'</em>';
	} else {
		$ctmp = $comment;
		$ctmp['name'] = $ctmp['page_name'];
		$url = Toto::viewUrl($ctmp, $urlargs);
		$baseurl = Toto::viewUrl($ctmp);
		unset($ctmp);
		$cmtclass = 'comment';
		$comment['page_name'] = htmlspecialchars_uni($comment['page_name']);
	}
	if(!isset($comment['rating'])) $comment['rating'] = 0;
?>
<div class="<?php echo $cmtclass ?>">
<div class="comment_user"><a href="<?php echo $url ?>#comment<?php echo $comment['cid'] ?>">Comment</a> in <a href="<?=$baseurl?>"><?php echo $comment['page_name'] ?></a><br /><?php echo AT_get_comment_title_html($comment); ?></div>
<?php
if($comment['rating'] < -8)
	echo '<div class="comment_oblivioned">This comment has been hidden due to low rating.</div>';
else {
	$parser_key = 'toto_'.$comment['section'].'comment_'.$comment['cid'];
	if($comment['rating'] < -4)
		echo '<div class="comment_message_rating_low">'.$parser->parse($parser_key, $comment['message']).'</div>';
	else
		echo $parser->parse($parser_key, $comment['message']);
} ?>
</div>
<?php

} unset($comment);



echo $multipage;

$AT->output->printFooter();
unset($parser);

