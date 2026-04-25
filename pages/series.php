<?php
if(!defined('AT_ROOT')) exit;

$sa = strtolower($AT->input->get(AT_Input::TYPE_STR, 'subaction'));
if($sa == 'unsorted' || $sa == '0' || $sa == 'unsorted.0') {
	// make dummy series
	$series = array(
		'id' => 0,
		'name' => 'Unsorted Files'
	);
	
	$subaction = 'unsorted';
	$seriesExists = false;
	$enTitle = null;
} else {
	if($p=strpos($sa, '.'))
		$aid = intval(substr($sa, $p+1));
	elseif(is_numeric($sa))
		$aid = intval($sa);
	unset($sa);
	if(!isset($aid) || !$aid)
		$AT->output->error('Invalid aid supplied.', 'Invalid series', AT_Output::ERROR_BADREQ);
	$series = $AT->db->selectGetArray('anidb.anime', '`anidb.anime`.`id`='.$aid, '`anidb.anime`.*, animetitle.name, anidb_tvdb.tvdbid, anidb_tvdb.defaulttvdbseason, anidb_tvdb.imdbids', array('joins' => array(
		//array('inner', 'anidb.animetitle', 'id', 'aid')
		'INNER JOIN anidb.animetitle ON `anidb.anime`.id=animetitle.aid AND animetitle.type=1',
		['LEFT', 'anidb_tvdb', 'id', 'anidbid']
	)));
	$seriesExists = !empty($series);
	if(!$seriesExists) {
		// see if we happen to have a pending entry
		$series = $AT->db->selectGetArray('anidb.animetitle', 'aid='.$aid, 'aid AS id, name');
		if(empty($series))
			$AT->output->error('Invalid aid supplied.', 'Invalid series', AT_Output::ERROR_NOTFOUND);
	}
	unset($aid);
	
	
	$subaction = AT::seoPageSubaction($series['id'], $series['name']);
	$enTitle = get_anime_en_titles([$series['id']]);
	if(isset($enTitle[$series['id']]))
		$enTitle = $enTitle[$series['id']];
	if(empty($enTitle) || $enTitle == $series['name']) $enTitle = null;
}

filtered_noindex($series['name']);

$self_url = AT::buildUrl('series', $subaction, array(), false);

if(AT::testSeoUrl($self_url)) return;



$AT->output->title = htmlspecialchars($series['name']);
$AT->output->head_metas['description'] = $series['name'];

$page = $AT->input->get(AT_Input::TYPE_INT, 'page');
if($page < 1) $page = 1;

$gid = $AT->input->get(AT_Input::TYPE_INT, 'gid');
if($gid) {
	$groupName = $AT->db->selectGetField('anidb.group', 'name', 'id='.$gid);
	if(!isset($groupName))
		$AT->output->error('Invalid gid supplied.', 'Invalid group', AT_Output::ERROR_NOTFOUND);
}

$disp_qwhere = '/*!toto.*/aid='.$series['id'];
$disp_feedargs = ['aid' => $series['id']];
$disp_cat_type = $gid ? 'episode' : 'series_group';
require AT_ROOT.'pages/includes/listing.php';

if($gid) {
	$AT->output->title .= ' &mdash; '.htmlspecialchars($groupName);
	$AT->output->head_metas['description'] .= ' | '.$groupName;
	$AT->output->head_extra_html = '<link rel="canonical" href="'.AT::buildUrl('series', $series['id'], array('page' => $page, 'gid' => $gid)).'" />';
	
	$AT->output->printHeader(900);
	$AT->output->printTitleAndDesc(htmlspecialchars($groupName), '', [
		htmlspecialchars($self_url) => htmlspecialchars($series['name'])
	]);
} else {
	$AT->output->head_extra_html = '<link rel="canonical" href="'.AT::buildUrl('series', $series['id'], array('page' => $page)).'" />';
	$AT->output->printHeader(900);
	
	$AT->output->printTitleAndDesc($AT->output->title, $enTitle);
	
	
	if($seriesExists) { ?><table style="width: 100%;">
		<thead><tr><th colspan="2">Info</th></tr></thead>
		<tbody style="vertical-align: top;"><tr>
		<td>
			<?php if($series['picurl']) {
				echo '<a href="',STORAGE_URL_REDIR,'adbpics/main/', htmlspecialchars($series['picurl']), '"><img src="',STORAGE_URL,'adbpics/150/', htmlspecialchars($series['picurl']).'-thumb.jpg', '" srcset="',STORAGE_URL,'adbpics/main/', htmlspecialchars($series['picurl']), ' 2.5x" alt="[series image]" style="float: right; margin-left: 1em;" /></a>';
			} ?>
			<div style="margin-bottom: 1em;">
			<?php
			if($series['other']) {
				echo preg_replace(array(
					'~\[(/?(?:[bius]|[ou]l|li|code))\]~i',
					// TODO: images? (don't think they're ever used)
					'~\[url\=http\:(//anidb\.net/[^<>"]+?)\]~i',
					'~\[url\=(https?\://[^<>"]+?)\]~i',
					'~\[/url\]~i',
					'~\[spoiler\]~i',
					'~\[/spoiler\]~i'
				), array(
					'<$1>',
					'<a href="https:$1">',
					'<a href="$1">',
					'</a>',
					'<details><summary>Spoiler</summary>',
					'</details>'
				), nl2br(htmlspecialchars($series['other'])));
			} else echo '<i>No description available from AniDB</i>';
			?>
			</div>
			<div style="float: right; max-width: 30em;">
			<div style="text-align: right; font-size: smaller;"><?php
				echo htmlspecialchars($series['year']);
				$catmap = array(null, 'Unknown Type', 'TV Series', 'OVA', 'Movie', 'Other Type', 'Web Series', 'TV Special', 'Music Video');
				if(isset($catmap[$series['cid']]))
					echo ', ', $catmap[$series['cid']];
				if($series['eps'])
					echo ', ', $series['eps'], ' episode(s)';
			?></div>
			<?php
				echo '<a href="https://anidb.net/anime/', $series['id'], '">AniDB</a>';
				
				$resources = $AT->db->selectGetAll('anidb.animeresource', null, '`aid`='.$series['id'].' AND type IN(1,2,3,6)', 'id,type,data', ['order' => 'type ASC, IF(type IN(1,2,3), LPAD(CAST(REGEXP_REPLACE(data, "[^0-9].*$", "") AS unsigned), 12, "0"), data) ASC']);
				if($series['tvdbid'])
					$resources[] = ['type' => 'tvdb', 'data' => [$series['tvdbid'], $series['defaulttvdbseason']]];
				if($series['imdbids']) {
					foreach(explode(',', $series['imdbids']) as $imid)
						if($imid) $resources[] = ['type' => 'imdb', 'data' => $imid];
				}
				if(!empty($resources)) {
					
					$typemap = array(
						1 => 'ANN',                 // int
						2 => 'MAL',                 // int
						3 => 'AnimeNfo',            // int, followed by text
						4 => 'Official page (jp)',  // URL
						5 => 'Official page (en)',  // URL
						6 => 'Wiki (en)',           // text
						7 => 'Wiki (jp)',           // text
						8 => 'Schedule',            // int
						9 => 'Allcinema',           // int
						10 => 'Anison',             // int
						11 => '.lain',              // text
						12 => 'Generasia',
						13 => 'VGMdb',
						14 => 'VNDB',               // v{id}
						15 => 'Marumegane',         // int, followed by text
						16 => 'Animemorial',        // int
						17 => 'TV Animation Museum', // text
						18 => 'TV Tropes',
						19 => 'Wiki (ko)',          // text
						20 => 'Wiki (zh)',          // text
						// 21 unused
						22 => 'Facebook',           // text
						23 => 'Twitter',            // text
						// 24, 25 unused
						26 => 'YouTube',            // text
						// 27 unused
						28 => 'Crunchyroll',        // text
						// 29, 30 unused
						31 => 'Media Art Database', // int
						32 => 'Amazon Video',       // text
						33 => 'Baidu Baike',        // text
						34 => 'Official Stream',    // text
						35 => 'Official Blog',      // text
						// 36, 37 unused
						38 => 'bangumi',            // int
						39 => 'douban',             // int
						// 40 unused
						41 => 'Netflix',            // int
						42 => 'HiDive',             // text
						43 => 'IMDB',               // text ('tt'+id)
						44 => 'TMDB',               // text
						45 => 'Funimation',         // text
						46 => 'QQ',                 // text
						47 => 'BiliBili',           // text
						48 => 'Amazon Prime',       // text
						
						'tvdb' => 'TVDB',
						'imdb' => 'IMDB',
					);
					function resource_data_to_url($type, $data) {
						switch($type) {
							case  1: return 'https://www.animenewsnetwork.com/encyclopedia/anime.php?id='.$data;
							case  2: return 'https://myanimelist.net/anime/'.$data;
							case  3: return 'https://www.animenfo.com/animetitle,'.$data.',a.html';
							case  4: case 5: return $data;
							case  6: return 'https://en.wikipedia.org/wiki/'.$data;
							case  7: return 'https://ja.wikipedia.org/wiki/'.$data;
							case  8: return 'https://cal.syoboi.jp/tid/'.$data.'/time';
							case  9: return 'https://www.allcinema.net/cinema/'.$data;
							case 10: return 'http://anison.info/data/program/'.$data.'.html';
							case 11: return 'http://lain.gr.jp/'.$data;
							//case 12: case 13: // not used
							case 14: return 'http://vndb.org/'.$data;
							case 15: return 'http://www.anime.marumegane.com/'.$data.'.html';
							case 16: return 'http://www.animemorial.net/ja/'.$data.'-a';
							case 17: return 'http://home-aki.la.coocan.jp/anime-list/'.$data.'.htm';
							//case 18: // not used
							case 19: return 'https://ko.wikipedia.org/wiki/'.$data;
							case 20: return 'https://zh.wikipedia.org/wiki/'.$data;
							
							case 22: return 'https://www.facebook.com/'.$data;
							case 23: return 'https://twitter.com/'.$data;
							case 26: return 'https://www.youtube.com/'.$data;
							case 28: return 'http://www.crunchyroll.com/'.$data;
							case 31: return 'https://mediaarts-db.bunka.go.jp/an/anime_series/'.$data;
							case 32: return 'https://www.amazon.com/dp/'.$data;
							case 33: return 'https://baike.baidu.com/item/'.$data;
							case 34: case 35: return $data;
							case 38: return 'https://bgm.tv/subject/'.$data;
							case 39: return 'https://movie.douban.com/subject/'.$data;
							case 41: return 'https://www.netflix.com/title/'.$data;
							case 42: return 'https://www.hidive.com/'.$data;
							case 43: return 'https://www.imdb.com/title/'.$data;
							case 44: return 'https://www.themoviedb.org/'.$data;
							case 45: return 'https://www.funimation.com/shows/'.$data;
							case 46: return 'https://v.qq.com/detail/'.$data;
							case 47: return 'https://www.bilibili.com/'.$data;
							case 48: return 'https://www.primevideo.com/detail/'.$data;
							
							case 'tvdb':
								//if(isset($data[1])) // don't know what the season link is :/
								//	return '';
								return 'https://www.thetvdb.com/dereferrer/series/'.$data[0];
							case 'imdb':
								return 'http://www.imdb.com/title/tt'.$data;
						}
					}
					
					$resourceDisplay = $resCount = array();
					foreach($resources as $res) {
						$rd =& $resourceDisplay[$typemap[$res['type']]];
						$link = htmlspecialchars(resource_data_to_url($res['type'], $res['data']));
						if($rd) {
							++$resCount[$res['type']];
							$rd .= ' <a href="'.$link.'">('.$resCount[$res['type']].')</a>';
						} else {
							$rd = '<a href="'.$link.'">'.$typemap[$res['type']].'</a>';
							$resCount[$res['type']] = 1;
						}
					}
					ksort($resourceDisplay);
					echo ' | ', implode(' | ', $resourceDisplay);
				} else {
					if($series['animenfoid'])
						echo ' | <a href="https://www.animenfo.com/animetitle,'.$series['animenfoid'].','.$series['animenfostr'].',a.html">AnimeNfo</a>';
					if($series['annid'])
						echo ' | <a href="https://www.animenewsnetwork.com/encyclopedia/anime.php?id='.$series['annid'].'">ANN</a>';
				}
			?>
			</div>
			<?php
				$rel = $AT->db->selectGetAll('anidb.seq', 'id', '`anidb.seq`.`aid`='.$series['id'], '`anidb.seq`.id,nextaid,`anidb.seq`.type,name', ['order' => 'nextaid ASC', 'joins' => [
					'INNER JOIN anidb.animetitle ON `anidb.seq`.nextaid=animetitle.aid AND animetitle.type=1'
				]]);
				if(!empty($rel)) {
					echo '<div>';
					$sep = '';
					$relTypeMap = [1=>'Sequel', 2=>'Prequel', 11=>'Same setting', 12=>'Same setting', 21=>'Alternative setting', 22=>'Alternative setting', 31=>'Alternative version', 32=>'Alternative version', 41=>'Same characters', 42=>'Same characters', 51=>'Side story', 52=>'Parent story', 61=>'Summary', 62=>'Full story', 100=>'Other'];
					foreach($rel as $r) {
						echo $sep;
						if(isset($relTypeMap[$r['type']]))
							echo $relTypeMap[$r['type']];
						else
							echo 'Unknown relation';
						echo ': <a href="', AT::buildUrl('series', AT::seoPageSubaction($r['nextaid'], $r['name'])), '">', htmlspecialchars($r['name']), '</a>';
						$sep = '<br />';
					}
					echo '</div>';
				}
			?>
		<br clear="all" /></td>
		</tr></tbody>
	</table><?php }
	elseif($series['id']) {
		echo '<div style="font-style: italic;">This series <a href="https://anidb.net/anime/'.$series['id'].'">does not exist on AniDB</a></div>';
	}
}

// display stuffs
include AT_ROOT.'pages/includes/displist.php';

$GLOBALS['aid'] =& $series['id'];
$AT->output->printFooter();

?>