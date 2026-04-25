<?php
if(!defined('AT_ROOT')) exit;

if($tfile['aid'] && !$AT->user->isMember()) {
	include AT_ROOT.'pages/includes/crfilter.php';
	if(!empty($blocked_aids) && in_array($tfile['aid'], $blocked_aids))
		$AT->output->error('Invalid tid supplied.', 'Invalid item', AT_Output::ERROR_NOTFOUND);
	
	// DNS complaints
	if(!empty($blocked_vids) && in_array($tfile['id'], $blocked_vids)) {
		$AT->output->error('Entry removed due to takedown request', 'Entry removed', AT_Output::ERROR_NOTFOUND);
	}
}

$AT->input->parse(array(
	'page' => AT_Input::TYPE_INT,
	'perpage' => AT_Input::TYPE_INT,
));
if($AT->input->page < 1) $AT->input->page = 1;
if($AT->input->perpage < 1) $AT->input->perpage = 10;
if($AT->input->perpage > 50) $AT->input->perpage = 10;

$title_html = htmlspecialchars_uni($tfile['name']);
$AT->output->title = $title_html;


$subaction = Toto::viewSubaction($tfile);
$self_url = unhtmlentities(Toto::viewUrl($tfile));

if(AT::testSeoUrl($self_url)) return;


// disable search indexing on certain things
if($tfile['deleted'] || $tfile['ulcomplete'] < 0)
	$AT->output->head_metas['robots'] = 'noindex';
// also don't index if it contains words we don't like
else {
	filtered_noindex($tfile['name']);
}


$allow_new_comments = !$AT->db::readonly && !($tfile['deleted'] && !$AT->user->isMod() && $tfile['dateline'] < TIME_NOW-86400*365) && (!$tfile['comments_locked'] || $AT->user->isMod());

if(!isset($tfile['comments_top'])) $tfile['comments_top'] = 0;
if(!isset($tfile['comments'])) $tfile['comments'] = 0;

// preload our comments here
if($tfile['comments_top'])
{
	$multipage = AT_Output::stdMultipageHandler($AT->input, $tfile['comments_top'], $AT->input->action, $subaction, array(), array(), 10, 50);

	$cq_fields = '`comments`.*, `users`.`username`, users.tagline, users.avatar';
	$cq_joins = array(array('left', 'users', 'uid'));
	
	if($AT->user->isMember()) {
		// also load comment voting info
		$cq_fields .= ', `comment_votes`.`vote`';
		$cq_joins[] = array('left', 'comment_votes', 'cid', 'cid` AND '.$AT->user->uid.'=`comment_votes`.`uid');
	}
	
	// first grab top level comments
	$AT->db->suppressNotices(true);
	$comments = $AT->db->selectGetAll('comments', 'cid', '`mid`='.$tfile['id'].' AND `replydepth`=0', $cq_fields, array('order' => '`comments`.`dateline` ASC', 'limit' => $AT->input->perpage, 'limit_start' => ($AT->input->page-1)*$AT->input->perpage, 'joins' => $cq_joins));
	$AT->db->suppressNotices(false);
	if(!empty($comments))
	{
		$replies = implode(',', array_keys($comments));
		// load replies
		$AT->db->suppressNotices(true);
		$AT->db->select('comments', '`mid`='.$tfile['id'].' AND `replydepth`<=10 AND `replytotop` IN ('.implode(',', array_keys($comments)).')', $cq_fields, array('order' => '`replydepth` ASC, `comments`.`dateline` ASC', 'joins' => $cq_joins));
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
}
else
{
	$comments = array();
	$multipage = '';
}


require_once AT_ROOT.'pages/includes/view_funcs.php';


// load our parsing engine
if(!isset($parser))
{
	require_once AT_ROOT.'includes/parser.php';
	$parser = new AT_Parser($AT->db, $AT->cache);
}
$parser_load = array();
foreach($comments as $cid => &$c) {
	$parser_load[] = 'toto_comment_'.$cid;
	if($c['uid'] && $c['tagline']) $parser_load[] = 'toto_user_tagline_'.$c['uid'];
}
$parser->load($parser_load);
unset($parser_load);


// load related info
$series_title = null;
if($tfile['aid']) {
	$series = $AT->db->selectGetArray('anidb.anime', '`anidb.anime`.id='.$tfile['aid'], 'eps,name', array('joins' => array(
		'LEFT JOIN anidb.animetitle ON animetitle.aid = '.$tfile['aid'].' AND animetitle.type=1'
	)));
	if(!$series) {
		// see if we can just get the title
		$series = $AT->db->selectGetArray('anidb.animetitle', 'aid='.$tfile['aid'], 'name');
	}
	if(isset($series))
		$series_title = $series['name'];
}
if($tfile['eid']) {
	$episode = $AT->db->selectGetArray('anidb.ep', 'id='.$tfile['eid'], 'name,epno,type');
	if(empty($episode))
		$tfile['eid'] = null;
}
	

if(isset($series_title)) {
	$AT->output->head_metas['description'] = $series_title;
	if(isset($episode) && $episode['epno']) {
		$AT->output->head_metas['description'] .= ' - '.format_episode($episode, 'num');
	}
	$series_link = AT::buildUrl('series', AT::seoPageSubaction($tfile['aid'], $series_title));
} /* else
	$AT->output->head_metas['description'] = $tfile['name']; */

$AT->output->head_extra_html = '<link rel="canonical" href="'.Toto::viewUrl($tfile, array('page' => $AT->input->page), true).'" /><script type="text/javascript" src="'.$AT->output->staticFileUrl('view.js').'"></script>'.AT_get_avatar_css($comments);
$AT->output->printHeader(0);


require AT_ROOT.'pages/includes/view_file_common.php';
$bc = buildViewBreadcrumb($tfile['aid'], $tfile['eid'], $series_title, $episode);
if($tfile['aid'] && $series_title && $tfile['eid'] && ($episode['epno'] || $episode['name']) && isset($series['eps'])) {
	$episode_title = format_episode($episode, 'long', $series['eps']);
	// grab episode link from breadcrumb - ugly, but well...
	end($bc);
	$episode_link = key($bc);
}
$AT->output->printTitleAndDesc($title_html, '', $bc);
unset($bc);

$auto_warn = ' <span title="This information has been automatically guessed and may be incorrect." class="tipnote">(!)</span>';
if($tfile['resolveapproved'])
	$resolve_warn = '';
else
	$resolve_warn = $auto_warn;
?>

<br />
<?php if($tfile['deleted'])
	echo '<div class="usernotice usernotice_alert">This file has been deleted.</div>';
elseif($tfile['ulcomplete'] == -1) {
	if($tfile['isdupe'] && ($dupetoto = $AT->db->selectGetArray('toto', 'isdupe=0 AND btih='.$AT->db->escape($tfile['btih']), 'id,tosho_id,nyaa_id,nyaa_subdom,anidex_id,nekobt_id,name'))) {
		echo '<div class="usernotice usernotice_alert">This torrent has been skipped as it appears to be a duplicate of ';
		echo '<a href="'.htmlspecialchars(Toto::viewUrl($dupetoto)).'">'.htmlspecialchars($dupetoto['name']).'</a>';
		echo '.</div>';
	} else
		echo '<div class="usernotice usernotice_alert">This torrent has not been fetched due to <a href="'.AT::buildUrl('about').'">skipping policy</a>.</div>';
} elseif($tfile['ulcomplete'] == -3)
	echo '<div class="usernotice usernotice_alert">Unknown error occurred when fetching this torrent.</div>';
?>
<table style="width: 100%;">
	<tr><th style="width: 9em;">Source Links</th><td>
	<?php if($tfile['tosho_id'])
		echo '<a href="https://www.tokyotosho.info/details.php?id='.$tfile['tosho_id'].'">TokyoTosho</a> | ';
	if($tfile['nyaa_id']) {
		echo nyaa_source_link($tfile), ($tfile['nyaa_id'] >= 923009 ? ' (<a href="'.CACHE_URL.'/nyaasi/view/'.$tfile['nyaa_id'].'">cached</a>)' : ''), ' | ';
	}
	if($tfile['anidex_id'])
		echo '<a href="https://anidex.info/?page=torrent&amp;id='.$tfile['anidex_id'].'">AniDex</a> | ';
	if($tfile['nekobt_id'])
		echo '<a href="https://nekobt.to/torrents/'.$tfile['nekobt_id'].'">nekoBT</a> | ';
	?>
	<a href="<?php echo htmlspecialchars(torrent_link_url($tfile)); ?>">Torrent Download</a>
	<?php if($tfile['magnet'])
		echo ' | <a href="', htmlspecialchars(magnet_link_url($tfile)), '">Magnet Link</a> ';
	?>
	(<?php echo $AT->friendlyFileSize($tfile['totalsize']); ?>)
	<?php if($tfile['stored_nzb'])
		echo ' | <a href="', htmlspecialchars(nzb_link_url($tfile)), '">NZB', ($tfile['ulcomplete'] == 2 ? ' (partial)' : ''), '</a> ';
	?>
	<?php
	if($tfile['website'])
		echo ' | <a href="', htmlspecialchars($tfile['website']), '">Website</a>';
	?>
	</td></tr>
	<tr><th>Date Submitted</th><td><?php echo $AT->user->fmtDate($tfile['dateline']); ?></td></tr>
	<?php if($tfile['aid'] && $series_title) { ?>
	<tr><th>Series<?php echo $resolve_warn; ?></th><td><?php
		echo '<a href="', $series_link, '">', htmlspecialchars($series_title), '</a>';
		if($tfile['eid']) {
			if(isset($episode_title) && $episode_title) {
				echo ' - <a href="', $episode_link, '">', $episode_title, '</a>';
			}
		}
	?></td></tr>
	<?php } ?>
	<?php if($tfile['comment']) { ?>
	<tr><th>Comment</th><td><?php echo strip_tags($tfile['comment']); /* TODO: run through parser */ ?></td></tr>
	<?php } ?>
	<?php if($tfile['srcurl'] && $tfile['srctitle']) { ?>
	<tr><th>Article<?php echo $auto_warn; ?></th><td><?php echo '<a href="', htmlspecialchars($tfile['srcurl']), '">', htmlspecialchars($tfile['srctitle']), '</a>'; ?></td></tr>
	<?php } ?>
	<?php
	$AT->db->suppressNotices(true);
	$tstats = $AT->db->selectGetAll('tracker_stats', 'tracker_id', 'tracker_stats.id='.$tfile['id'], 'tracker_stats.*, trackers.url', array('joins' => array(
		array('inner', 'trackers', 'tracker_id', 'id')
	)));
	$AT->db->suppressNotices(false);
	if(!empty($tstats)) {
		echo '<tr><th>Tracker(s)</th><td><table cellpadding="0" cellspacing="0" border="0">';
		$shown_trackers = 0; $skipped_trackers = 0;
		foreach($tstats as $ts) {
			$is_main_tracker = ($ts['tracker_id'] == $tfile['main_tracker_id']);
			if(!$is_main_tracker && $shown_trackers >= 8 && count($tstats) > 10) {
				++$skipped_trackers;
				continue;
			}
			echo '<tr><td style="padding-right: 1em;">';
			if($is_main_tracker)
				echo '<b>', htmlspecialchars($ts['url']), '</b>';
			else {
				++$shown_trackers;
				echo htmlspecialchars($ts['url']);
			}
			echo '</td>';
			if($ts['last_queried']) {
				if($ts['updated'] && (!$ts['error'] || $ts['updated'] > TIME_NOW-7200)) {
					echo '
						<td style="padding-left: 1em;" title="Seeders">S:&nbsp;</td><td align="right" title="Seeders">',$ts['complete'],'</td>
						<td style="padding-left: 1em;" title="Leechers">L:&nbsp;</td><td align="right" title="Leechers">',$ts['incomplete'],'</td>
						<td style="padding-left: 1em;" title="Completed">C:&nbsp;</td><td align="right" title="Completed">',$ts['downloaded'],'</td>
						<td style="padding-left: 1em;" title="Updated">U:&nbsp;</td><td align="right" title="Updated">',$AT->relative_time($ts['updated'], 'min'),'</td>
					';
				} else {
					echo '<td colspan="6" align="center">Scrape failed</td>
					<td style="padding-left: 1em;" title="Updated">U:&nbsp;</td><td title="Updated">', $AT->relative_time($ts['last_queried'], 'min'), '</td>';
				}
			} else {
				echo '<td colspan="8" align="center" title="Pending update">-</td>';
			}
			echo '</tr>';
		}
		if($skipped_trackers)
			echo '<tr><td style="font-style: italic; text-align: center;">'.$skipped_trackers.' more trackers</td><td colspan="8"></td></tr>';
		echo '</table></td></tr>';
	}
	?>
	<?php if($tfile['ulcomplete'] != -1 && $tfile['ulcomplete'] != -3 && ($tfile['torrentfiles'] != 1 || $tfile['ulcomplete'] < 1)) { ?>
	<tr><th>Files</th><td><?php
		switch($tfile['ulcomplete']) {
			case 0:
				echo '<em>This torrent is still being processed - not all files may be displayed</em>';
				break;
			case -2:
				echo '<em>This torrent appears to be broken - not all, if any, files may be displayed</em>';
				break;
			case 2:
				echo '<em>This torrent was partially fetched - not all files are displayed</em>';
				break;
		}
	?>

<?php

// output file list
$files = $AT->db->selectGetAll('files', 'id', '`toto_id`='.$tfile['id'], '`id`,`filename`,`filesize`,`type`,`links`,`crc32`,`sha256`', array(
	'joins' => array(array(
		'left', 'filelinks', 'id', 'fid'
	)),
	'order' => 'filename'
));
$alt = '';
?>


<div>
<?php
$hasAllHash = true; $hasAllSha256 = true;
foreach($files as &$file) {
	$rowclass = 'view_list_entry'.($alt?$alt='':$alt=' view_list_entry_alt');
	$size_tt = 'File size: '.$AT->user->fmtNum($file['filesize']).' byte'.($file['filesize']==1?'':'s');
	
	echo '<div class="', $rowclass, '">';
	echo '<div class="size" title="', $size_tt,'">', $AT->friendlyFileSize($file['filesize']), '</div>';
	echo '<div class="size_icon" title="', $size_tt,'"><span class="image16 icon_'.($file['type'] == 1 ? 'dir':'file').'size"></span></div>';
	echo '<div class="link"><a href="', AT::buildUrl('file', AT::seoPageSubaction($file['id'], $file['filename'])), '">';
	if($file['type'] == 1) {
		echo '<b>', htmlspecialchars($file['filename']), '</b>';
	} else {
		echo htmlspecialchars($file['filename']);
	}
	echo '</a></div>';
	
	echo '<div class="links">';
	echo formatFileLinks($file['links']);
	echo '<div class="clear"></div></div>';
	echo '</div>';
	
	$hasAllHash = $hasAllHash && isset($file['crc32']);
	$hasAllSha256 = $hasAllSha256 && isset($file['sha256']);
} ?>
</div>
</td></tr>
<?php
// check for attachments
if(!empty($files)) {
	// abuse the CRC32k as an indicator that a file hasn't been processed
	$attachStatus = $AT->db->selectGetArray('attachments', 'toto_id='.$tfile['id'], 'SUM(crc32k IS NULL) AS incomplete, COUNT(*) AS total', ['joins' => [['inner', 'files', 'fid', 'id']]]);
	if(!@$attachStatus['incomplete'] && @$attachStatus['total']) { ?>
<tr><th>Subtitles</th><td><a href="<?php echo STORAGE_URL_REDIR, 'torattachpk/', $tfile['id'], '/', name_base_for_url($tfile['name']), '_attachments.7z'; ?>">All Attachments</a></td></tr>
<?php }
if($hasAllHash) { ?>
<tr><th>Hash List</th><td><?php
$hashLists = ['sfv', 'md5', 'sha1'];
if($hasAllSha256) $hashLists[] = 'sha256';
echo implode(', ', array_map(function($hl) use(&$tfile) {
	return '<a href="'.AT::buildUrl('hashlist', $tfile['id'].'.'.$hl).'">'.strtoupper($hl).'</a>';
}, $hashLists));
?></td></tr><?php }
}
} ?>
</table>

<?php if($tfile['ulcomplete'] > 0 && $tfile['torrentfiles'] == 1 && $tfile['id']) {
	// TODO: it appears that it's possible to have $tfile['torrentfiles'] && $tfile['id'] == 0 be true - find out why 
	$file = $AT->db->selectGetArray('files', '`toto_id`='.$tfile['id']);
	if(!empty($file)) { // how can this be false?
		$size_tt = 'File size: '.$AT->user->fmtNum($file['filesize']).' byte'.($file['filesize']==1?'':'s');
?>
<table style="width: 100%; margin-top: 0.5em;">
<tr><th style="width: 9em;">File Name (Size)</th><td><a href="<?php echo AT::buildUrl('file', AT::seoPageSubaction($file['id'], $file['filename'])); ?>"><?php echo htmlspecialchars($file['filename']); ?></a> <span title="<?php echo $size_tt; ?>">(<?php echo $AT->friendlyFileSize($file['filesize']); ?>)</span></td></tr>


<?php
printAllFileLinksTable($file);
if($file['filethumbs'] || $file['filestore'] || ($file['vidframes'] || $file['vidframes'] === '0'))
	printScreenshots($file);
?>
<?php printExtractions($file); ?>

</table>
<?php }} ?>


<div class="clear"></div>

<?php if($allow_new_comments || $tfile['comments'] > 0) { ?>

<br />
<hr />
<br />

<div style="width: 100%;"><details <?=$AT->cookies->get(AT_Input::TYPE_BOOL, 'nocomments')?'':'open=""'?> ontoggle="document.cookie='ant[nocomments]='+(+!this.open)+ ';path=/';">
<?php
echo '<summary><strong><span id="view_comments_count">', $AT->user->fmtNum($tfile['comments']), '</span> comment(s):</strong></summary>';

echo '<div id="view_comments_real"></div>';

if($allow_new_comments) {
	echo '<div id="comment_reply_placeholder_0"><div id="comment_reply_form">'; // weird way to put it, but required for our form hack
	require_once AT_ROOT.'includes/output_form.php';
	$form = new AT_Output_Form($AT->input); // though we don't use the input here...
	$form->start(htmlspecialchars($self_url).'#newcomment', $AT->postKey, 'newcomment', 'return AJS.x.submitComment(this);', 'AJS.x.replyToComment(0,true);');
	echo '<input type="radio" name="replyto" value="-1" style="display:none;" />';
}

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
		echo AT_get_comment_html($parser, $comments, $c['cid'], ['newcid'=>$newcid, 'readonly' => !$allow_new_comments]);
	}
	
	echo '<div id="newcomments_placeholder_0"></div>';
	echo $multipage;
} else {
	// add placeholder to allow future comments to be added
	echo '<div id="newcomments_placeholder_0"></div>';
}

echo '</div>';

if($allow_new_comments) {
	echo '<div id="view_comments_replybox">';

	require_once AT_ROOT.'includes/output_table.php';
	$table = new AT_Output_Table_2ColFrm;
	$table->start((empty($comments)?'':'<noscript><div style="float: right;"><input type="radio" name="replyto" value="0" class="radio" checked="checked" title="Reply to this article, as oppossed to replying to another comment." /></div></noscript>').'Add new comment');
	$commentnote = '<details tabindex="-1"><summary>Show formatting tags</summary>
	<table style="margin-left: 1.5em"><tr>
		<td style="width: 12em">&lt;b&gt;<b>bold</b>&lt;/b&gt;</td>
		<td style="width: 12em">&lt;i&gt;<i>italic</i>&lt;/i&gt;</td>
		<td style="width: 12em">&lt;u&gt;<u>underline</u>&lt;/u&gt;</td>
		<td>&lt;s&gt;<s>strikethrough</s>&lt;/s&gt;</td>
	</tr><tr>
		<td>&lt;code&gt;<code>code</code>&lt;/code&gt;</td>
		<td>&lt;sub&gt;<sub>subscript</sub>&lt;/sub&gt;</td>
		<td>&lt;sup&gt;<sup>superscript</sup>&lt;/sup&gt;</td>
		<td>&lt;spoiler&gt;<span class="user_spoiler">spoiler</span>&lt;/spoiler&gt;</td>
	</tr><tr>
		<td>&lt;big&gt;<big>big</big>&lt;/big&gt;</td>
		<td>&lt;small&gt;<small>small</small>&lt;/small&gt;</td>
		<td>&lt;quote&gt;<span class="user_quote">quote</span>&lt;/quote&gt;</td>
		<td>&lt;a href=&quot;https://animetosho.org/&quot;&gt;<a href="https://animetosho.org/">link</a>&lt;/a&gt;</td>
	</tr></table></details>
	<big>Please be aware of the following before commenting:</big>
	<ul style="margin:0;padding-left:2em;">
		<li>Anime Tosho provides a mirror of torrents and is not the source. Please understand that <b>uploaders/submitters may not read comments here</b>, so you should check the <i>Source Links</i> section near the top of this page if you wish to contact them</li>
		<li><b>Expired links do NOT get reuploaded</b> as files are deleted after we process them</li>
		<li><b>Uploads are NOT instant</b>, so please wait for them to be processed. The entire uploading process is done by a bot which does NOT read any comments, so asking for links will have no effect on when they show up</li>
		<li>Critical comments are welcome, however note that statements such as &quot;X is crap&quot; or &quot;Y sucks&quot; are NOT criticisms. Please <b>provide reasoning with critical comments</b> to be informative, helpful and allow for debate.</li>
		<li>Personal attacks or insults are not welcome here. Such comments may be removed entirely and commenters may be temporarily banned.</li>
		<li>If you\'ve got no audio and you\'re using VLC, try a different player.</li>
	</ul>';
	if($AT->user->isMember()) {
		$table->spanRow('<strong>Comment Type:</strong> '.$form->selectS('message_type', $comment_types, 0));
		$table->spanRow($form->editorS('message', '', 4, 70, 'style="width: 100%;"', 'if(!t)return"You must enter a message to post.";if(t.length<2)return"Sorry, message must be at least 2 characters long.";if(t.length>5000)return"Comment is too long - please keep it under 5000 characters!";').'<small style="display: block;">'.$commentnote.'</small>');
	} elseif(@$_SERVER['HTTP_HOST'] == TOR_HOST) {
		$table->spanRow('Please <a href="'.AT::buildUrl('register').'">register an account</a> to post');
	} else {
		$table->row('Name:', $form->textboxS('displayname', $AT->cookies->get(AT_Input::TYPE_STR, 'displayname') ?: '', false, 25, 25, null, 'if(t.trim().length<3)return"Name too short";if(t.match(/[\u0080-\uffff]/) || !t.replace(/^[0-9]+$/,""))return"Invalid name.";'), '');
		$table->row('Comment Type:', $form->selectS('message_type', $comment_types, 0));
		$table->row('Message:', $form->editorS('message', '', 4, 70, 'style="width: 100%;"', 'if(!t)return"You must enter a message to post.";if(t.length<2)return"Sorry, message must be at least 2 characters long.";if(t.length>5000)return"Comment is too long - please keep it under 5000 characters!";'), $commentnote, true);
		$table->captchaRow($form, false, 'comment_captcha_row');
		?>
		<script type="text/javascript">
		<!--
			(showCaptcha = function() {
				if(!$id("newcomment_message").value.length) return;
				comment_captcha_row_show();
			})();
			$id("newcomment_message").onchange = showCaptcha;
			$id("newcomment_message").onkeypress = showCaptcha;
			$id("newcomment_message").onkeydown = showCaptcha;
			$id("newcomment_message").onkeyup = showCaptcha;
		//-->
		</script>
		<?php
	}
	$table->end();
	if($AT->user->isMember() || @$_SERVER['HTTP_HOST'] != TOR_HOST)
		$table->printStdFooter($form, 'comment', 'Post Comment', array(), true);

	echo '</div>';

	$hidden_fields = array('pagetime' => TIME_NOW);
	if($AT->input->got('page'))
		$hidden_fields['page'] = $AT->input->page;
	if($AT->input->got('perpage'))
		$hidden_fields['perpage'] = $AT->input->perpage;
	$form->hidden($hidden_fields);
	$form->end();

	echo '</div></div>';
}
?>
<script type="text/javascript">
<!--
	// hack to put comments outside of the form
	document.getElementById('view_comments_real').appendChild($id('view_comments'));
//-->
</script>
</details></div>

<?php
}

$AT->output->printFooter();
unset($parser);



?>