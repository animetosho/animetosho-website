<?php
if(!defined('AT_ROOT')) exit;

//$listing_disable_sorting = true;
$disp_cat_type = 'series';
require AT_ROOT.'pages/includes/listing.php';

$AT->output->title = SITE_NAME;
//$AT->output->head_metas['description'] = '';
//$AT->output->head_metas['keywords'] = 'rapidshare,megaupload,despositfiles,hotfile,zshare,mediafire';
//$AT->output->head_extra_html .= '<link rel="canonical" href="'.AT::buildUrl('home', '', array('page' => $AT->input->page)).'" />';

$AT->output->printHeader(60);


/*
<div style="border: solid 1px #00d1e4; background: #ebfdff; padding: 0.5em; margin: 0.5em;">Welcome to <em>', SITE_NAME, '</em>.  This site is currently still in beta, so please bear/bare/rabbit with us whilst we sort out any problems which may, and probably will, arise.  Unlike Google, we don\'t have millions of dollars to throw away at any problems that crop up, but, if you do see any issues yourself, please <a href="'.AT::buildUrl('feedback').'">do tell us</a> so that we can improve your experience here! (no nekomimis will be given)</div>
*/

if(!$AT->input->got('page') && $AT->db::readonly) {
?><div style="text-align: center;">This is a mirror of <a href="https://animetosho.org/">Anime Tosho</a>.</div><?php
}

if(!$AT->input->got('page') && !$AT->db::readonly) {

?>
<table class="home_updates"><tr>
<td style="width: 60%;"><div class="box">
<strong>Latest Updates</strong> [<a href="<?=AT::buildUrl('about', 'news')?>">archive</a>]
<?php
foreach(array(
	'8th Feb 2026' => '<s>The storage/feed/API server is quickly approaching its bandwidth quota (which resets in a week). I may disable some features (screenshots/attachments) and run the feed/API in a degraded state (some requests will fail) to conserve bandwidth until the quota resets.</s> Quota reset, regular operation restored.',
	'5th Feb 2026' => 'Anime Tosho will begin <a href="'.AT::buildUrl('about', 'shutdown').'">shutting down permanently in May 2026</a>.',
) as $date => $msg) {
	echo '<div', (TIME_NOW - strtotime($date) < 86400*3 ? ' style="font-size: 1.2em;"':''), '><strong>', $date, '</strong>: ', $msg, '</div>';
}
?>
</div></td>
<?php

// latest comments
$parser_load = $comments = $comments_top = array();

$comments_perpage = 10;
$AT->db->suppressNotices(true);
$AT->db->select('comments', '', 'comments.cid,comments.name AS comment_name,comments.mid,comments.dateline,comments.message,comments.replyto,comments.replytotop,toto.name,toto.tosho_id,toto.nyaa_id,toto.nyaa_subdom,toto.anidex_id,toto.nekobt_id,toto_meta.comments_top,toto.id AS toto_id', array('order' => 'dateline DESC', 'limit' => 5, 'joins' => array(
	array('inner', 'toto', 'mid', 'id'),
	array('left', 'toto_meta', 'mid', 'id')
)));
$AT->db->suppressNotices(false);
while($comment = $AT->db->fetchArray()) {
	$parser_load[] = 'toto_comment_'.$comment['cid'];
	$comments[] = $comment;
	if($comment['comments_top'] > $comments_perpage && $comment['replyto']) {
		// this comment may be paged, and isn't a topmost parent = we need to fetch the dateline of the parent
		$comments_top[$comment['replytotop']] = $comment['cid'];
	}
} unset($comment);
if(!empty($comments)) {
	// grab any top level comments for paging calculations
	$comments_top_datelines = array();
	if(!empty($comments_top)) {
		$AT->db->select('comments', 'cid IN ('.implode(',', array_keys($comments_top)).')', 'cid,dateline');
		while($comment = $AT->db->fetchArray()) {
			$comments_top_datelines[$comments_top[$comment['cid']]] = $comment['dateline'];
		} unset($comment);
	}
	// output comments
	echo '<td style="width: 40%;"><div class="box"><strong>Latest Comments</strong> [<a href="'.AT::buildUrl('comments').'">view all</a>]';
	if(!isset($parser)) {
		require_once AT_ROOT.'includes/parser.php';
		$parser = new AT_Parser($AT->db, $AT->cache);
	}
	$parser->load($parser_load);
	unset($parser_load);
	
	foreach($comments as &$comment) {
		$cmt_urlargs = array();
		
		if($comment['comments_top'] > $comments_perpage) {
			$comment_dateline = $comment['dateline'];
			if($comment['replyto'] && isset($comments_top_datelines[$comment['cid']]))
				$comment_dateline = $comments_top_datelines[$comment['cid']];
			// check which page this comment is on
			// TODO: maybe consider the possibility of caching this, although it's probably not that important
			$comment_pos = $AT->db->selectGetField('comments', 'COUNT(*)', '`mid`='.$comment['mid'].' AND `replydepth`=0 AND `dateline`<'.$comment_dateline); // 0 based number
			$comment_page = floor($comment_pos / $comments_perpage) + 1;
			$cmt_urlargs = array('page' => $comment_page);
		} // otherwise, page 1 is implied
		
		$url = Toto::viewUrl($comment, $cmt_urlargs);
		$msg = unhtmlentities(preg_replace('~\s+~', ' ', strip_tags($parser->parse('toto_comment_'.$comment['cid'], $comment['message']))));
		if(isset($msg[40])) $msg = mb_substr($msg, 0, 40) . '...';
		$title = $comment['name'];
		if(isset($title[25]))
			$title = '<span title="'.htmlspecialchars($title).'">' . htmlspecialchars(mb_substr($title, 0, 25)) . '...</span>';
		else
			$title = htmlspecialchars($title);
		echo '<div><a href="',$url,'#comment',$comment['cid'],'" title="',htmlspecialchars($msg),'">', $AT->user->fmtDate($comment['dateline']), ': posted by ', htmlspecialchars($comment['comment_name']), ' in ', $title, '</a></div>';
	} unset($comment);
	echo '</div></td>';
}
?></tr></table><?php

} // if(!$AT->input->got('page') && !$AT->db::readonly)

// display stuffs
include AT_ROOT.'pages/includes/displist.php';

$AT->output->printFooter();

?>
