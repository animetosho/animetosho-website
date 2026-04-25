<?php
if(!defined('AT_ROOT')) exit;

if(!$AT->user->isMod())
	$AT->output->error('You cannot access this page.', 'Permission denied', AT_Output::ERROR_FORBIDDEN);

$AT->input->parse(array(
	'page' => AT_Input::TYPE_INT,
	'perpage' => AT_Input::TYPE_INT,
));
if($AT->input->perpage < 1) $AT->input->perpage = 10;
if($AT->input->perpage > 50) $AT->input->perpage = 10;
if($AT->input->page < 1) {
	// determine last page
	$num_comments = $AT->db->selectGetField('modtalk_comments', 'COUNT(cid)', '`replydepth`=0');
	$pages = ceil($num_comments / $AT->input->perpage);
	$AT->input->page = max(1,$pages);
}

$AT->output->title = 'ModTalk Page';

require_once AT_ROOT.'pages/includes/view_funcs.php';

// preload our comments here
$cq_fields = '`modtalk_comments`.*, `users`.`username`, users.tagline, users.avatar';
$cq_joins = array(array('left', 'users', 'uid'));
// first grab top level comments
$AT->db->suppressNotices(true);
$comments = $AT->db->selectGetAll('modtalk_comments', 'cid', '`replydepth`=0', $cq_fields, array('order' => '`modtalk_comments`.`dateline` ASC', 'limit' => $AT->input->perpage, 'limit_start' => ($AT->input->page-1)*$AT->input->perpage, 'joins' => $cq_joins));
$AT->db->suppressNotices(false);
if(!empty($comments))
{
	$replies = implode(',', array_keys($comments));
	// load replies
	$AT->db->suppressNotices(true);
	$AT->db->select('modtalk_comments', '`replydepth`<=10 AND `replytotop` IN ('.implode(',', array_keys($comments)).')', $cq_fields, array('order' => '`replydepth` ASC, `modtalk_comments`.`dateline` ASC', 'joins' => $cq_joins));
	$AT->db->suppressNotices(false);
	while($comment = $AT->db->fetchArray())
	{
		if(!isset($comments[$comment['replyto']]['replies']))
			$comments[$comment['replyto']]['replies'] = array($comment['cid']);
		else
			$comments[$comment['replyto']]['replies'][] = $comment['cid'];
		$comments[$comment['cid']] = $comment;
	}
}

$multipage = AT_Output::stdMultipageHandler($AT->input, $AT->db->selectGetField('modtalk_comments', 'COUNT(*)', '`replydepth`=0'), 'modtalk', '', array(), array(), 10, 50, true);



// load our parsing engine
if(!isset($parser))
{
	require_once AT_ROOT.'includes/parser.php';
	$parser = new AT_Parser($AT->db, $AT->cache);
}
$parser_load = array();
foreach($comments as $cid => &$c) {
	$parser_load[] = 'toto_modtalk_comment_'.$cid;
	if($c['uid'] && $c['tagline']) $parser_load[] = 'toto_user_tagline_'.$c['uid'];
}
$parser->load($parser_load);
unset($parser_load);


$AT->output->head_extra_html = '<script type="text/javascript" src="'.$AT->output->staticFileUrl('view.js').'"></script>'.AT_get_avatar_css($comments);
$AT->output->printHeader(0);


$AT->output->printTitleAndDesc('ModTalk Page', 'Copy of the Feedback page, not viewable by the general public.<br /><a href="'.AT::buildUrl('admin', 'updates1').'/">Updates server admin interface</a>');

?>

<br />

<div style="width: 100%;">
<?php
echo '<strong><span id="view_comments_count">', $AT->user->fmtNum($AT->db->selectGetField('modtalk_comments', 'COUNT(*)')), '</span> comment(s) posted:</strong> [<a href="', AT::buildUrl('comments'), '?modtalk=1">view latest</a>]';
echo '<div id="view_comments_real"></div>';

echo '<div id="comment_reply_placeholder_0"><div id="comment_reply_form">'; // weird way to put it, but required for our form hack
require_once AT_ROOT.'includes/output_form.php';
$form = new AT_Output_Form($AT->input); // though we don't use the input here...
$form->start(AT::buildUrl('modtalk').'#newcomment', $AT->postKey, 'newcomment', 'return AJS.x.submitComment(this);', 'AJS.x.replyToComment(0,true);');


echo '<div id="view_comments">';
// comments
if(!empty($comments))
{
	echo $multipage;
	
	if(isset($new_comment))
		$newcid = $new_comment['cid'];
	else
		$newcid = 0;
	foreach($comments as &$c)
	{
		if($c['replydepth']) continue;
		echo AT_get_comment_html($parser, $comments, $c['cid'], ['newcid' => $newcid, 'parser_prefix' => 'toto_modtalk_comment_']);
	}
	
	echo '<div id="newcomments_placeholder_0"></div>';
	echo $multipage;
} else {
	// add placeholder to allow future comments to be added
	echo '<div id="newcomments_placeholder_0"></div>';
}

echo '</div>';

echo '<div id="view_comments_replybox">';

require_once AT_ROOT.'includes/output_table.php';
$table = new AT_Output_Table_2ColFrm;
$table->start((empty($comments)?'':'<noscript><div style="float: right;"><input type="radio" name="replyto" value="0" class="radio" checked="checked" title="Reply to this article, as oppossed to replying to another comment." /></div></noscript>').'Add new comment');
$table->spanRow($form->editorS('message', '', 6, 70, 'style="width: 100%;"', 'if(!t)return"You must enter a message to post.";if(t.length<2)return"Sorry, message must be at least 2 characters long.";if(t.length>30000)return"Comment is too long - please keep it under 30000 characters!";'));
$table->end();
$table->printStdFooter($form, 'comment', 'Post Comment', array(), true);

echo '</div>';

$hidden_fields = array('pagetime' => TIME_NOW);
if($AT->input->got('page'))
	$hidden_fields['page'] = $AT->input->page;
if($AT->input->got('perpage'))
	$hidden_fields['perpage'] = $AT->input->perpage;
$form->hidden($hidden_fields);
$form->end();
?>
</div></div>
<script type="text/javascript">
<!--
	// hack to put comments outside of the form
	document.getElementById('view_comments_real').appendChild($id('view_comments'));
//-->
</script>
</div>

<?php
$AT->output->printFooter();
unset($parser);



?>