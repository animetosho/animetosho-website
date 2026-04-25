<?php
if(!defined('AT_ROOT')) exit;

$AT->output->title = 'Episode Updates';
//$AT->output->head_metas['description'] = '';

$AT->output->printHeader(900);
$AT->output->printTitleAndDesc('Episode Updates', 'The latest releases grouped by episode.<br /><a href="'.AT::buildUrl('animes').'">Group by series</a>');


$AT->input->parse(array(
	'aids' => AT_Input::TYPE_STR,
	'page' => AT_Input::TYPE_INT,
	'perpage' => AT_Input::TYPE_INT,
	'filter_old' => AT_Input::TYPE_BOOL,
	'order' => AT_Input::TYPE_STR,
));
if(!$AT->input->page || $AT->input->page < 1)
	$AT->input->page = 1;
$urlargs = array();

//$numitems = $AT->db->selectGetField('adb_episodes', 'COUNT(*)');
$numitems = 2500; // effectively stops massive offsets

$perpage = $AT->input->perpage;
if($perpage < 1) $perpage = 50;
if($perpage > 100) $perpage = 100;
$limit_start = ($AT->input->page-1) * $perpage;
if($limit_start > $numitems) {
	$limit_start = 0;
	$AT->input->page = 1;
}

$order_updated = ($AT->input->order == 'updated');
if($order_updated) $urlargs['order'] = 'updated';

$aidSource = $order_updated ? 'aniep_latest' : 'anidb.ep';

$where = $order_updated ? '1=1' : 'eid!=0';
$min_date = 0;
if($AT->input->filter_old) {
	// we reduce to day precision to help with query caching
	$date = 'IF(anidb.ep.aired=0,anidb.ep.date,anidb.ep.aired)';
	if($order_updated)
		$date = 'IF(eid=0,dateline,'.$date.')';
	$min_date = ((int)(TIME_NOW/86400) - 105)*86400;
	$where .= ' AND '.$date.' > '.$min_date;
	$urlargs['filter_old'] = 1;
}

$aids = str_replace('a', '', $AT->input->aids);
if($aids) {
	$aids = array_unique(array_map('intval', explode(',', $aids)));
	if(count($aids) > 20) $aids = array_slice($aids, 0, 20);
	$aid_list = implode(',', $aids);
	$where .= ' AND '.$aidSource.'.aid IN('.$aid_list.')';
	$urlargs['aids'] = $aid_list;
}


if(!$AT->user->isMember()) {
	include AT_ROOT.'pages/includes/crfilter.php';
	if(!empty($blocked_aids))
		$where .= ' AND '.$aidSource.'.aid NOT IN ('.implode(',', $blocked_aids).')';
}

$AT->db->suppressNotices(true);
$joinType = $order_updated ? 'LEFT' : 'INNER';
$qopts = array(
	'order' => 'dateline DESC',
	'limit' => $perpage,
	'limit_start' => $limit_start,
	'joins' => array(
		//array($joinType, 'anidb.ep', 'eid', 'id'),
		$joinType.' JOIN anidb.ep ON eid = anidb.ep.id',
		//array('left', 'anidb.anime', 'aid', 'id')
		$joinType.' JOIN anidb.anime ON '.$aidSource.'.aid = anidb.anime.id',
		'LEFT JOIN anidb.animetitle ON '.$aidSource.'.aid = anidb.animetitle.aid AND anidb.animetitle.type=1',
	)
);
$qcols = ',dateline,anidb.ep.type,anidb.ep.epno,anidb.ep.name,anidb.ep.aired,anidb.anime.eps,anidb.anime.dateflags,anidb.animetitle.name AS atitle,anidb.anime.picurl';
if($order_updated) {
	$AT->db->select('aniep_latest', $where, 'aniep_latest.aid,aniep_latest.eid'.$qcols, $qopts);
	$eps = [];
	while($res = $AT->db->fetchArray()) {
		$eps[$res['aid'].'_'.$res['eid']] = $res;
	}
	$AT->db->freeResult();
} else
	$eps = $AT->db->selectGetAll('ep_latest', 'eid', $where, 'ep_latest.eid,anidb.ep.aid'.$qcols, $qopts);

// fetch associated torrents
if($AT->db->query(
	implode(' UNION ALL ', array_map(function($ep) use($min_date, $order_updated) {
		$where = '';
		if($min_date)
			$where = ' AND idx_dateline < -'.$min_date;
		return '(SELECT id, aid, eid, name, dateline, totalsize, tosho_id, nyaa_id, nyaa_subdom, anidex_id, nekobt_id, ulcomplete FROM toto_repl.toto_toto WHERE eid='.$ep['eid'].($order_updated ? ' AND aid='.$ep['aid'] : '').' AND idx_dateline IS NOT NULL'.$where.' ORDER BY idx_dateline ASC LIMIT '.($ep['aid'] == 0 ? 7 : ($ep['eid'] ? 3 : 5)).')';
	}, $eps))
)) {
	while($torrent = $AT->db->fetchArray()) {
		$key = $torrent['eid'];
		if($order_updated) $key = $torrent['aid'].'_'.$key;
		$ep =& $eps[$key];
		if(!isset($ep['torrents'])) $ep['torrents'] = [];
		$ep['torrents'][] = $torrent;
	} unset($ep);
	$AT->db->freeResult();
}
$AT->db->suppressNotices(false);
$enTitles = get_anime_en_titles(array_map(function($ep) { return $ep['aid']; }, $eps));


$pagenav = '<div class="home_list_pagination">';
if($AT->input->page > 1)
	$pagenav .= '<a href="' . AT::buildUrl($AT->input->action, $AT->input->subaction, array_merge($urlargs, array('page' => $AT->input->page-1))) . '">&lt; Newer Entries</a>';
if($numitems > $limit_start+$perpage)
	$pagenav .= ($AT->input->page > 1 ? ' | ':'') . '<a href="' . AT::buildUrl($AT->input->action, $AT->input->subaction, array_merge($urlargs, array('page' => $AT->input->page+1))) . '">Older Entries &gt;</a>';
$pagenav .= '</div>';
echo $pagenav;

?>
<div class="home_list_filter" style="border-width: 1px; border-style: solid none;"><form action="<?php echo AT::buildUrl($AT->input->action, $AT->input->subaction) ?>" method="get">
<?php foreach($urlargs as $uaKey => $uaVal) {
	if(in_array($uaKey, ['order', 'filter_old'])) continue;
	echo '<input type="hidden" name="', htmlspecialchars($uaKey), '" value="', htmlspecialchars($uaVal), '" />';
} ?>
<select name="order" style="margin-right: 1em"><?php
$order_selected = ['added' => '', 'updated' => ''];
$order_selected[$order_updated ? 'updated':'added'] = ' selected="selected"';
foreach(array(
	'added'   => 'First Added',
	'updated' => 'Last Updated'
) as $k=>$v) {
	echo '<option value="'.$k.'"'.$order_selected[$k].'>Sort: '.$v.'</option>';
} ?></select>
<label>Exclude old episodes: <input type="checkbox" name="filter_old" value="1" <?php if($AT->input->filter_old) echo 'checked="checked"'; ?> /></label>
<input type="submit" value="Apply" />
</form></div>
<?php

// find first toto item which doesn't have an eid and slot it in

$alt = '';
$prevsep = '';
foreach($eps as $key => &$ep) {
	// day separator functionality
	$day = $AT->user->fmtDate($ep['dateline'], true, false, true);
	if($day != $prevsep) {
		echo '<div class="home_list_datesep">', htmlspecialchars_uni($day), '</div>';
		$prevsep = $day;
	}
	
	if($ep['aid']) {
		$title = $ep['atitle'];
		if($ep['eid']) {
			$satitle = $ep['atitle'];
			if($ep['epno']) $satitle .= ' '.format_episode($ep, 'num');
			$url = AT::buildUrl('episode', AT::seoPageSubaction($ep['eid'], $satitle));
		} else {
			$url = AT::buildUrl('episode', $ep['aid'].'.unsorted');
		}
	} else {
		$title = 'Unsorted Files';
		$url = AT::buildUrl('series', 'unsorted');
	}
	echo '<div class="home_list_entry'.($alt?$alt='':$alt=' home_list_entry_alt').'" style="padding-left: 70px">
		<div style="float: left; width: 65px; min-height: 2em; margin-right: 0.5em; margin-left: -65px">';
	if($ep['picurl']) {
		echo '<a href="', AT::buildUrl('series', AT::seoPageSubaction($ep['aid'], $ep['atitle'])), '"><img src="',STORAGE_URL,'adbpics/65x100/', htmlspecialchars($ep['picurl']), '-thumb.jpg" srcset="',STORAGE_URL,'adbpics/150/', htmlspecialchars($ep['picurl']), '-thumb.jpg 2.308x" alt="[image]" /></a>';
	}
	echo '</div>
		<a href="', $url, '"><strong';
	if(isset($enTitles[$ep['aid']]) && $enTitles[$ep['aid']] != $title)
		echo ' title="', htmlspecialchars($enTitles[$ep['aid']]), '"';
	echo '>', htmlspecialchars($title), '</strong>';
	if($ep['epno'] || $ep['name']) {
		echo '<br />', htmlspecialchars(format_episode($ep, 'long', $ep['eps']));
	}
	else if(!$ep['eid'] && $ep['aid'])
		echo ' &mdash; Unsorted Files';
	echo '</a>';
	if($ep['aired'])
		echo '<br /><small>Air/release date: ', $AT->user->fmtDate($ep['aired'], true, false, false), '</small>';
	
	if(!empty($ep['torrents'])) {
		echo '<br /><div style="font-size: smaller; margin-left: 1.5em; margin-top: 0.25em">';
		echo implode('<br />', array_map(function($torrent) use(&$AT) {
			// TODO: include size/date?
			return '<a href="'.Toto::viewUrl($torrent).'" title="Posted: '.$AT->user->fmtDate($torrent['dateline']).' | Size: '.$AT->friendlyFileSize($torrent['totalsize']).'" class="tor_list_entry_compl_'.$torrent['ulcomplete'].'">'.
				str_replace("\n", '<wbr/>', /* &#8203; */
					htmlspecialchars_uni(
						wordwrap_fn2(str_replace("\n", '', $torrent['name']), 50, "\n")
					)
				).
				'</a>';
		}, $ep['torrents']));
		echo '</div>';
	}
	echo '<div class="clear"></div></div>';
}

echo $pagenav;

$AT->output->printFooter();

?>