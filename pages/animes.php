<?php
if(!defined('AT_ROOT')) exit;

$AT->output->title = 'Series Updates';
//$AT->output->head_metas['description'] = '';
//$AT->output->head_metas['keywords'] = '';

$AT->output->printHeader(900);
$AT->output->printTitleAndDesc('Series Updates', 'The latest releases grouped by series.<br /><a href="'.AT::buildUrl('episodes').'">Group by episode</a>');






$AT->input->parse(array(
	'page' => AT_Input::TYPE_INT,
	'perpage' => AT_Input::TYPE_INT,
	'filter_old' => AT_Input::TYPE_BOOL,
));
if(!$AT->input->page || $AT->input->page < 1)
	$AT->input->page = 1;
$urlargs = array();

//$numitems = $AT->db->selectGetField('anidb.anime', 'COUNT(*)');
$numitems = 4000; // effectively stops massive offsets

$perpage = $AT->input->perpage;
if($perpage < 1) $perpage = 40;
if($perpage > 100) $perpage = 100;
$limit_start = ($AT->input->page-1) * $perpage;
if($limit_start > $numitems) {
	$limit_start = 0;
	$AT->input->page = 1;
}

$where = '1=1';
$min_date = 0;
if($AT->input->filter_old) {
	// we reduce to day precision to help with query caching
	$min_date = ((int)(TIME_NOW/86400) - 105)*86400;
	$where .= ' AND dateline > '.$min_date;
	$urlargs['filter_old'] = 1;
}

if(!$AT->user->isMember()) {
	include AT_ROOT.'pages/includes/crfilter.php';
	if(!empty($blocked_aids))
		$where .= ' AND anime_latest.aid NOT IN ('.implode(',', $blocked_aids).')';
}

$AT->db->suppressNotices(true);
$animes = $AT->db->selectGetAll('anime_latest', 'aid', $where, 'anime_latest.aid,dateline,anidb.animetitle.name,anidb.anime.picurl,anidb.anime.dateflags,anidb.anime.airdate,anidb.anime.enddate,anidb.cat.name AS type,anidb.anime.eps', array(
	'order' => 'dateline DESC',
	'limit' => $perpage,
	'limit_start' => $limit_start,
	'joins' => array(
		//array('left', 'anidb.anime', 'aid', 'id')
		'LEFT JOIN anidb.anime ON anime_latest.aid = anidb.anime.id',
		'LEFT JOIN anidb.animetitle ON anime_latest.aid = anidb.animetitle.aid AND anidb.animetitle.type=1',
		'LEFT JOIN anidb.cat ON CONCAT("cat:", anidb.anime.cid) = anidb.cat.id',
	)
));
$AT->db->suppressNotices(false);

// the new window functions in MariaDB 10.2 / MySQL 8.0 seem nice, but a nested select screams slowwww... we'll just UNION a whole bunch of stuff instead
$AT->db->suppressNotices(true);
if($AT->db->query(
	implode(' UNION ALL ', array_map(function($aid) use($min_date) {
		$where = '';
		if($min_date)
			$where = ' AND idx_dateline < -'.$min_date;
		return '(SELECT id, aid, name, dateline, totalsize, tosho_id, nyaa_id, nyaa_subdom, anidex_id, nekobt_id, ulcomplete FROM toto_repl.toto_toto WHERE aid='.$aid.' AND idx_dateline IS NOT NULL'.$where.' ORDER BY idx_dateline ASC LIMIT '.($aid == 0 ? 8 : 6).')';
	}, array_keys($animes)))
)) {
	while($torrent = $AT->db->fetchArray()) {
		$ani =& $animes[$torrent['aid']];
		if(!isset($ani['torrents'])) $ani['torrents'] = [];
		$ani['torrents'][] = $torrent;
	} unset($ani);
	$AT->db->freeResult();
}
$AT->db->suppressNotices(false);
$enTitles = get_anime_en_titles(array_keys($animes));

$pagenav = '<div class="home_list_pagination">';
if($AT->input->page > 1)
	$pagenav .= '<a href="' . AT::buildUrl($AT->input->action, $AT->input->subaction, array_merge($urlargs, array('page' => $AT->input->page-1))) . '">&lt; Newer Entries</a>';
if($numitems > $limit_start+$perpage)
	$pagenav .= ($AT->input->page > 1 ? ' | ':'') . '<a href="' . AT::buildUrl($AT->input->action, $AT->input->subaction, array_merge($urlargs, array('page' => $AT->input->page+1))) . '">Older Entries &gt;</a>';
$pagenav .= '</div>';
echo $pagenav;

?>
<div class="home_list_filter" style="border-width: 1px; border-style: solid none;"><form action="<?php echo AT::buildUrl($AT->input->action, $AT->input->subaction) ?>" method="get">
<label>Exclude old files: <input type="checkbox" name="filter_old" value="1" <?php if($AT->input->filter_old) echo 'checked="checked"'; ?> /></label>
<input type="submit" value="Apply" />
</form></div>
<?php

$alt = '';
$prevsep = '';
foreach($animes as $aid => &$ani) {
	// day separator functionality
	$day = $AT->user->fmtDate($ani['dateline'], true, false, true);
	if($day != $prevsep) {
		echo '<div class="home_list_datesep">', htmlspecialchars_uni($day), '</div>';
		$prevsep = $day;
	}
	
	if($aid == 0) {
		$ani['name'] = 'Unsorted Files';
		$url = AT::buildUrl('series', 'unsorted');
	} else {
		$url = AT::buildUrl('series', AT::seoPageSubaction($aid, $ani['name']));
	}
	echo '<div class="home_list_entry'.($alt?$alt='':$alt=' home_list_entry_alt').'" style="padding-left: 70px;">
		<div style="float: left; width: 65px; min-height: 2em; margin-right: 0.5em; margin-left: -65px;">';
	if($ani['picurl']) {
		echo '<a href="', $url, '"><img src="',STORAGE_URL,'adbpics/65x100/', htmlspecialchars($ani['picurl']), '-thumb.jpg" srcset="',STORAGE_URL,'adbpics/150/', htmlspecialchars($ani['picurl']), '-thumb.jpg 2.308x" alt="[image]" /></a>';
	}
	echo '</div>
		<a href="', $url, '"><strong';
	if(isset($enTitles[$aid]) && $enTitles[$aid] != $ani['name'])
		echo ' title="', htmlspecialchars($enTitles[$aid]), '"';
	echo '>', htmlspecialchars($ani['name']), '</strong></a>';
	
	if(!($ani['dateflags'] & 35) && $aid) { // if start d/m/y is known
		$enddate = PHP_INT_MAX;
		if($ani['dateflags'] & 16) // if 'finished'
			$enddate = 0;
		elseif(!($ani['dateflags'] & 76) && $ani['enddate']) // if end date is known
			$enddate = $ani['enddate'];
		
		if($enddate < TIME_NOW) { // if finished
			switch(strtolower($ani['type'])) {
				case 'tv series':
				case 'other':
				case 'web':
				case 'unknown':
					if($ani['eps'] == 1)
						echo ' (single episode)';
					else
						echo ' (finished)';
					break;
				case 'ova':
					if($ani['eps'] == 1)
						echo ' (OVA)';
					else
						echo ' (finished)';
					break;
				case 'movie':
					echo ' (movie)';
					break;
				case 'music video':
					echo ' (music video)';
					break;
				case 'tv special':
					echo ' (special)';
					break;
			}
			
		} elseif($ani['airdate'] < TIME_NOW) {
			//echo ' (ongoing)';
		}
	}
	
	echo '<div style="font-size: smaller; margin-left: 1.5em;">';
	echo implode('<br />', array_map(function($torrent) use(&$AT) {
		// TODO: include size/date?
		return '<a href="'.Toto::viewUrl($torrent).'" title="Posted: '.$AT->user->fmtDate($torrent['dateline']).' | Size: '.$AT->friendlyFileSize($torrent['totalsize']).'" class="tor_list_entry_compl_'.$torrent['ulcomplete'].'">'.
			str_replace("\n", '<wbr/>', /* &#8203; */
				htmlspecialchars_uni(
					wordwrap_fn2(str_replace("\n", '', $torrent['name']), 50, "\n")
				)
			).
			'</a>';
	}, $ani['torrents']));
	echo '</div>';
	
	echo '<div class="clear"></div></div>';
}

echo $pagenav;

$AT->output->printFooter();

?>