<?php
if(!defined('AT_ROOT')) exit;
// returns $items and may return $items_count; not sure if returns other variables

$AT->input->parse(array(
	'page' => AT_Input::TYPE_INT,
	'perpage' => AT_Input::TYPE_INT,
	'filter' => AT_Input::TYPE_MIXED | AT_Input::TYPE_ARRAY,
	'_ts' => AT_Input::TYPE_INT,
	'q' => AT_Input::TYPE_STR,
	'order' => AT_Input::TYPE_STR,
	'disp' => AT_Input::TYPE_STR,
	'aids' => AT_Input::TYPE_STR,
	'eids' => AT_Input::TYPE_STR,
	'gids' => AT_Input::TYPE_STR,
	'aid' => AT_Input::TYPE_INT,
	'eid' => AT_Input::TYPE_INT,
	'gid' => AT_Input::TYPE_INT,
));
if(!$AT->input->page || $AT->input->page < 1)
	$AT->input->page = 1;


$qwhere = '1=1';
$tblPref = 'toto.';

$urlargs = array();

foreach(['a','e','g'] as $idt) {
	$varName = $idt.'ids';
	$$varName = str_replace($idt, '', $AT->input->$varName);
	if($$varName) {
		$$varName = array_unique(array_map('intval', explode(',', $$varName)));
		$$varName = array_filter($$varName); // remove 0s
		if(count($$varName) > 20) $$varName = array_slice($$varName, 0, 20);
	}
	if(empty($$varName)) $$varName = null;
}

$has_aeid = isset($disp_feedargs['aid']) || isset($disp_feedargs['eid']) || $aids || $eids || $AT->input->aid || $AT->input->eid;


// search stuff
$keywordstr = $AT->input->q;
if($keywordstr) {
	if($AT->input->get(AT_Input::TYPE_BOOL, 'qx')) {
		$keywords = addslashes($keywordstr);
		$disp_urlargs['qx'] = '1';
	} else {
		$keywords = array();
		$notkeywords = array();
		$nonword = '\\x00-\\x26\\x28-\\x2f\\x3a-\\x40\\x5b-\\x60\\x7b-\\x7f';
		$isword = '[^\\x00-\\x26\\x28-\\x2f\\x3a-\\x40\\x5b-\\x60\\x7b-\\x7f]';
		//$nonword = '[^\'0-9A-Za-z\\x80-\\xff]';
		// extract special terms
		// quoted terms
		$keywordstr = preg_replace_callback('~(?<=['.$nonword.']|^)(-?)"([^"]+)"(?=['.$nonword.']|$)~', function($s) use (&$keywords) {
			$keywords[] = $s[1].'"'.addslashes($s[2]).'"';
			return '';
		}, $keywordstr);
		// not/star; commented out line allows patterns like '-*word', but Sphinx seems to ignore that
		//$keywordstr = preg_replace_callback('~(?<=['.$nonword.']|^)(-\\*'.$isword.'+\\*?|[-*]'.$isword.'+\\*?|'.$isword.'+\\*?)(?=['.$nonword.']|$)~', function($s) use (&$keywords) {
		// Sphinx doesn't seem to consider stars on both side of the term, eg *word*
		$keywordstr = preg_replace_callback('~(?<=['.$nonword.']|^)([-*]'.$isword.'+|'.$isword.'+\\*|\\*'.$isword.'+\\*)(?=['.$nonword.']|$)~', function($s) use (&$keywords, &$notkeywords) {
			if($s[1][0] == '-') // need to separate not keywords because Sphinx won't allow searches with only not keywords
				$notkeywords[] = addslashes($s[1]);
			else
				$keywords[] = addslashes($s[1]);
			return '';
		}, $keywordstr);
		
		$kwmap = [
			'the' => 'the',
			'and' => 'and',
			'not' => 'not',
			'is' => 'is',
			'it' => 'it',
			'in' => 'in',
			'an' => 'an',
			'as' => 'as',
			'at' => 'at',
			'by' => 'by',
			'do' => 'do',
			'go' => 'go',
			'if' => 'if',
			'me' => 'me',
			'of' => 'of',
			'on' => 'on',
			'or' => 'or',
			'so' => 'so',
			'us' => 'us',
			'we' => 'we',
			'to' => 'to',
			'no' => 'no',
			'ni' => 'ni',
			'na' => 'na',
			'ga' => 'ga',
			'wa' => 'wa',
			'wo' => '(o|wo)',
			'o' => '(o|wo)',
			'e' => '(e|he)',
			'he' => '(e|he)',
			
			'ii' => '(ii|2)',
		];
		$keywords = array_merge($keywords, array_map(function($e) use($kwmap) {
			$e = addslashes($e);
			$le = strtolower($e);
			if(isset($kwmap[$le]))
				return $kwmap[$le];
			if($e == '480' || $e == '540' || $e == '576' || $e == '720' || $e == '1080' || $e == '2160')
				return '('.$e.'|*'.$e.'|'.$e.'p)';
			if(ctype_digit($e))
				return '('.$e.'|*e'.$e.'|s'.$e.'*)';
			if(strpos($e, "'") !== false) // apostrophes are treated as word separators - try to put them together (words like "it's" will be problematic to expand, so avoid that)
				return '"'.$e.'"';
			if(preg_match('~^[sS]0*(\d+)$~', $e, $m))
				return '('.$e.'|*'.$e.'*|'.$m[1].')';
			if(isset($e[1]) && !ctype_digit($e) && strpos($e, '*') === false)
				return '("'.$e.'"|"*'.$e.'*")';
			return '"'.$e.'"';
		}, preg_split('~['.$nonword.']+~', $keywordstr, -1, PREG_SPLIT_NO_EMPTY)));
		
		if(empty($keywords) && (!$has_aeid || empty($notkeywords)))
			$AT->output->error('No search terms found in query.', 'Invalid search', AT_Output::ERROR_BADREQ);
		$keywords = implode(' ', array_merge($keywords, $notkeywords));
	}
	
	$qwhere = 'MATCH(\''.$keywords.'\')'; // mysqli_real_escape_string($sphinx, $keywords)
	$tblPref = '';
	$disp_urlargs['q'] = $AT->input->q;
}


if(!$AT->user->isMember()) {
	include AT_ROOT.'pages/includes/crfilter.php';
	if(!empty($blocked_aids))
		$qwhere .= ' AND /*!toto.*/aid NOT IN ('.implode(',', $blocked_aids).')';
}
if(isset($disp_qwhere))
	$qwhere .= ' AND ('.$disp_qwhere.')';
if($AT->user->data['disp_blacklist'] && !$AT->input->q) { // TODO: map to search keywords if searching
	$bl_terms = array();
	foreach(explode("\n", $AT->user->data['disp_blacklist']) as $bl_term) {
		//$bl_terms[] = $tblPref.'name LIKE "%'.strtr($AT->db->escape($bl_term), array('%' => '\\%', '_' => '\\_')).'%"';
		$bl_terms[] = $tblPref.'name LIKE '.$AT->db->escape('%'.strtr($bl_term, array('%' => '|%', '_' => '|_', '|' => '||')).'%').' ESCAPE "|"';
	}
	if(!empty($bl_terms)) {
		$qwhere .= ' AND NOT ('.implode(' OR ', $bl_terms).')';
	}
}

if($eids) {
	$qwhere .= ' AND eid IN('.implode(',', $eids).')';
	$disp_urlargs['eids'] = implode(',', $eids);
}
elseif($aids) {
	$qwhere .= ' AND /*!toto.*/aid IN('.implode(',', $aids).')';
	$disp_urlargs['aids'] = implode(',', $aids);
} else {
	if($eid = $AT->input->eid) {
		$qwhere .= ' AND /*!toto.*/eid='.$eid;
		$disp_urlargs['eid'] = $eid;
	}
	if($aid = $AT->input->aid) {
		$qwhere .= ' AND /*!toto.*/aid='.$aid;
		$disp_urlargs['aid'] = $aid;
	}
}

if(!$gids && $AT->input->gid)
	$gids = [$gid = $AT->input->gid];
if($gids && $has_aeid && !$AT->input->q) {
	$qwhere .= ' AND ('.implode(' OR ', array_map(function($gid) {
		return 'CONCAT(",", gids, ",") LIKE "%,'.$gid.',%"';
	}, $gids)).')';
	if(!empty($gid))
		$disp_urlargs['gid'] = $gid;
	else
		$disp_urlargs['gids'] = implode(',', $gids);
}

if(!empty($disp_urlargs)) $urlargs = $disp_urlargs;

// apply filters
$filter_urlargs = array();
$filter_nyaa_class_selected = array('' => ' selected="selected"'); // temp for now
foreach($AT->input->filter as $filter) {
	if(!is_array($filter)) continue;
	$tmp = ''; $size_op = null;
	$val = strtolower(trim($filter['v'] ?? ''));
	switch($filter_type = strtolower(trim($filter['t'] ?? ''))) {
		case 'nyaa_class':
			switch($val) {
				case 'remake':
					$qwhere .= ' AND nyaa_class != 1';
				break;
				case 'trusted':
				case 'aplus':
					$qwhere .= ' AND nyaa_class >= 3';
				break;
				/* case 'aplus':
					$qwhere .= ' AND nyaa_class = 4';
				break; */
				default: $val = '';
			}
			if($val) {
				$filter_urlargs[] = array('t' => $filter_type, 'v' => $val);
				$filter_nyaa_class_selected = array($val => ' selected="selected"');
			}
		break;
		case 'size_min':
			$size_op = '>=';
		case 'size_max':
			isset($size_op) or $size_op = '<=';
			// parse the size string
			if(preg_match('~^(\d+\.?|\d*\.\d+)([kmg]b?|(?:[kmg]|kilo|mega|giga)?bytes?)?$~', str_replace(array(',',' ','-'),'',$v), $m)) {
				$size = (float)$m[1];
				switch(@$m[2][0]) {
					case 'g': $size *= 1024;
					case 'm': $size *= 1024;
					case 'k': $size *= 1024;
				}
				
				$qwhere .= ' AND /*\'"sphinxstrip"\'*/idx_totalsize'.$size_op.$size;
			}
			$filter_urlargs[] = array('t' => $filter_type, 'v' => $size);
		break;
		case 'title_excl':
			$tmp = 'NOT ';
			// fallthrough
		case 'title_incl':
			if($val || $val === '0')
				$qwhere .= ' AND '.$tmp.$tblPref.'name LIKE '.$AT->db->escape('%'.strtr($val, array('%' => '|%', '_' => '|_')).'%').' ESCAPE "|"';
			$filter_urlargs[] = array('t' => $filter_type, 'v' => $val);
		break;
		case 'state':
			switch($val) {
				case 'skipped':
					$qwhere .= ' AND ulcomplete >= 0';
				break;
				case 'incomplete':
					$qwhere .= ' AND ulcomplete = 1'; // doesn't actually only show completed (may still be uploading)
				break;
			}
			$filter_urlargs[] = array('t' => $filter_type, 'v' => $val);
		break;
		/*case 'source':
			switch($val) {
				case 'nyaa':
					$qwhere .= ' AND nyaa_id != 0';
				break;
				case 'tokyotosho':
					$qwhere .= ' AND tosho_id != 0';
				break;
				case 'anidex':
					$qwhere .= ' AND anidex_id != 0';
				case 'nekobt':
					$qwhere .= ' AND nekobt_id != 0';
				break;
			}
			$filter_urlargs[] = array('t' => $filter_type, 'v' => $val);
		break;*/
		/*case 'cat':
			$val = (int)$val;
			$qwhere .= ' AND cat = '.$val;
			$filter_urlargs[] = array('t' => $filter_type, 'v' => $val);
			$filter_cat_selected = array($val => ' selected="selected"');
		break;
		case 'nyaa_cat':
			if(!preg_match('~^(\d)+(?:_(\d+))$~', $val, $m)) break;
			if($m[2])
				$qwhere .= ' AND nyaa_cat = "'.$val.'"';
			else
				$qwhere .= ' AND nyaa_cat LIKE "'.$val.'%"';
			$filter_urlargs[] = array('t' => $filter_type, 'v' => $val);
			$filter_nyaa_cat_selected = array($val => ' selected="selected"');
		break;*/
		//case 'files_min':
		// anime type??
		// recently aired, still showing, current season etc
		// links alive
		// skipped or not
	}
}
foreach($filter_urlargs as $k => $v) {
	$urlargs['filter['.$k.'][t]'] = $v['t'];
	$urlargs['filter['.$k.'][v]'] = $v['v'];
} unset($filter_urlargs);

$perpage = $AT->input->perpage;
if($perpage < 1) $perpage = 75;
if($perpage > 200) $perpage = 200;
if(isset($disp_offset))
	$limit_start = $disp_offset;
else
	$limit_start = ($AT->input->page-1) * $perpage;
if($limit_start > 7500 && !$AT->input->_ts) { // && !$AT->input->q // <- should this be checked too?
	$AT->output->error('Sorry, browsing this far into history has been disabled. Please use search or other filters to view older items.', 'History Limit Reached', AT_Output::ERROR_FORBIDDEN);
}


$listing_order_col = 'idx_dateline';
$listing_order_dir = 'ASC';
$listing_order_factor = -1;
if(isset($listing_disable_sorting)) $AT->input->order = '';
switch($AT->input->order) {
	case 'size-a':
		$listing_order_col = 'idx_totalsize';
		$listing_order_dir = 'ASC';
		$qwhere .= ' /*!AND idx_totalsize IS NOT NULL*/';
		$listing_order_factor = 1;
		break;
	case 'size-d':
		$listing_order_col = 'idx_totalsize';
		$listing_order_dir = 'DESC';
		$qwhere .= ' /*!AND idx_totalsize IS NOT NULL*/';
		$listing_order_factor = 1;
		break;
	case 'date-a':
		$listing_order_col = 'idx_dateline';
		$listing_order_dir = 'DESC';
		$qwhere .= ' /*!AND idx_dateline IS NOT NULL*/';
		break;
	//case 'date-d':
	default:
		$AT->input->order = '';
		$qwhere .= ' /*!AND idx_dateline IS NOT NULL*/';
		break;
}
$listing_order_op_next = $listing_order_dir == 'ASC' ? '>':'<';
if($AT->input->order)
	$urlargs['order'] = $AT->input->order;
$listing_order_selected = array($AT->input->order => ' selected="selected"');

if($AT->input->disp != 'attachments') $AT->input->disp = '';
if($AT->input->disp)
	$urlargs['disp'] = $AT->input->disp;
$disp_selected = [$AT->input->disp => ' selected="selected"'];

$qopt = array(
	'order' => $listing_order_col.' '.$listing_order_dir,
	'limit' => $perpage+1, // to see if there's a next page
	'limit_start' => $limit_start,
);



if($AT->input->q) {
	
	$sphinx = @mysqli_connect('localhost', '', '', '', 9306, '/var/run/manticore/searchd.sock');
	if(!$sphinx) $AT->output->error('Failed to connect to search server.', 'Internal error', AT_Output::ERROR_SERVER);
	mysqli_set_charset($sphinx, 'utf8');
	$listing_order = $listing_order_col.' '.$listing_order_dir;
	if(substr($listing_order_col, 0, 4) == 'idx_') {
		$listing_order = substr($listing_order_col, 4).' '.(($listing_order_dir == 'DESC') == ($listing_order_factor < 0) ?'ASC':'DESC');
	}
	try {
		$result = @mysqli_query($sphinx,
		'SELECT id FROM idx_main_combined
		WHERE '.str_replace('/*\'"sphinxstrip"\'*/idx_', '', $qwhere).'
		ORDER BY '.$listing_order.'
		LIMIT '.$qopt['limit_start'].','.$qopt['limit'].'
		OPTION max_matches=7500'.($AT->input->qx ? ', expand_keywords=0':'').($has_aeid ? ', not_terms_only_allowed=1':'')
		);
		$ids = '';
		if($result) {
			while($id = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$ids .= ($ids?',':'').$id['id'];
			}
		}
	} catch(mysqli_sql_exception $e) {
		$AT->output->error('Invalid search terms supplied. <!-- '.$e->getMessage().' -->', 'Invalid search', AT_Output::ERROR_BADREQ);
	}
	if($AT->input->subaction == 'api') {
		if($result = mysqli_query($sphinx, 'SHOW META LIKE \'total_found\'')) {
			$items_count = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$items_count = $items_count['Value'];
		}
	}
	mysqli_close($sphinx);
	
	if(!$ids) {
		$items = [];
		$qwhere = '1=0';
	} else {
		unset($qopt['limit'], $qopt['limit_start']);
		$qwhere = 'toto.id IN ('.$ids.')';
	}
} elseif($limit_start >= 1500) {
	// optimisation for large pagenums (for some reason, MySQL is more efficient with a simple select than with joins - maybe it's because it can't figure out that joined stuff can be skipped with large offsets)
	$unhinted_qwhere = $qwhere;
	if(substr($listing_order_col, 0, 4) == 'idx_') {
		if($AT->input->_ts) {
			$qwhere .= ' AND '.$listing_order_col.$listing_order_op_next.($AT->input->_ts*$listing_order_factor);
		} else {
			$startItem = $AT->db->selectGetArray('toto', $qwhere, 'id,'.substr($listing_order_col, 4).' AS val', $qopt);
			if(empty($startItem))
				$qwhere = '1=0';
			else
				$qwhere .= ' AND '.$listing_order_col.$listing_order_op_next.'='.($startItem['val']*$listing_order_factor);
		}
	}
	unset($qopt['limit_start']);
}


if($qwhere != '1=0') {
	$AT->db->suppressNotices(true); // shut up stupid warning
	$qsel = 'toto.*';
	if($AT->input->disp == 'attachments') {
		$qopt['joins'] = array(
			array('left', 'files', 'sigfid', 'id'),
			array('left', 'attachments', 'sigfid', 'fid')
		);
		$qsel = 'toto.*,files.filename AS sig_filename,attachments';
	} elseif($AT->input->subaction == 'api' || $AT->input->subaction == 'json') {
		// API doesn't need links
		$qsel = 'toto.*';
	} else {
		$qopt['joins'] = array(
			array('left', 'filelinks', 'sigfid', 'fid'),
		);
		$qsel = 'toto.*,links';
	}


	if(!isset($disp_cat_type)) {
		if($AT->input->subaction == 'api') {
			$qopt['joins'][] = ['left', 'anidb_tvdb', 'aid', 'anidbid'];
			$qopt['joins'][] = ['left', 'anidb.anime', 'aid', 'id'];
			$qopt['joins'][] = ['left', 'anidb.ep', 'eid', 'id'];
			$qsel .= ', anidb_tvdb.tvdbid, anidb_tvdb.defaulttvdbseason, anidb_tvdb.imdbids, `anidb.anime`.airdate AS aniaired, `anidb.ep`.aired AS epaired, epno, `anidb.anime`.picurl AS anipicurl';
		}
	} else {
		if($disp_cat_type == 'series' || $disp_cat_type == 'series_group' || $disp_cat_type == 'episode') {
			$qopt['joins'][] = 'LEFT JOIN anidb.animetitle ON toto.aid=animetitle.aid AND animetitle.type=1';
			//$qopt['joins'][] = array('left', 'anidb.animetitle', 'aid');
			$qsel .= ',animetitle.name AS title';
		}
		if($disp_cat_type == 'episode') {
			$qopt['joins'][] = 'LEFT JOIN anidb.ep ON toto.eid=ep.id';
			$qsel .= ',ep.epno,ep.type AS eptype,ep.name AS eptitle';
		}
	}
	$items = $AT->db->selectGetAll('toto', 'id', $qwhere, $qsel, $qopt);

	if(!empty($items)) {
		if($AT->input->disp == 'attachments') {
			require_once AT_ROOT.'pages/includes/finfo-compress.php';
			$complete_ids = [];
			foreach($items as &$item) {
				if(isset($item['attachments'])) {
					$item['subtitles'] = FileInfoCompressor::decompress_unpack('attach', $item['attachments']);
					$item['subtitles'] = $item['subtitles'][1] ?? null; // 1=ATTACHMENT_SUBTITLE
					unset($item['attachments']);
				}
				if($item['ulcomplete'] > 0)
					$complete_ids[] = $item['id'];
			} unset($item);
			
			// determine presence of "All Attachments" link
			if(!empty($complete_ids)) {
				$allattachs = $AT->db->selectGetAll('attachments', 'toto_id', 'toto_id IN ('.implode(',', $complete_ids).')', 'toto_id, SUM(crc32k IS NULL) AS incomplete, COUNT(*) AS total', [
					'joins' => [
						['inner', 'files', 'fid', 'id']
					],
					'group' => 'toto_id'
				]);
				foreach($allattachs as $id => $allattach) {
					if(!$allattach['incomplete'] && $allattach['total'])
						$items[$id]['has_all_attachments'] = true;
				}
			}
		} elseif($AT->input->subaction != 'rss2' && $AT->input->subaction != 'atom') {
			// grab tracker stats
			// TODO: consider excluding dead trackers?
			$updateCutoff = floor(TIME_NOW/1800)*1800 - 3600; // quantize time to 30min blocks to make query cache efficient
			$tracker_filter = ' AND updated != 0 AND (error = 0 OR updated > '.$updateCutoff.')';
			$tr_missing = [];
			$tr_same_tracker_id = reset($items)['main_tracker_id'];
			foreach($items as $item) {
				$tr_missing[$item['id']] = 1;
				if(isset($tr_same_tracker_id) && $tr_same_tracker_id != $item['main_tracker_id'])
					$tr_same_tracker_id = null;
			}
			
			if(isset($tr_same_tracker_id)) {
				// frequent case shortcut: all entries have the same main tracker, so make a simpler query
				$tracker_fetch = 'id IN('.implode(',', array_keys($items)).') AND tracker_id='.$tr_same_tracker_id;
			} else {
				$tracker_fetch = '('.implode(' OR ', array_map(function($item) {
					return '(id='.$item['id'].' AND tracker_id='.$item['main_tracker_id'].')';
				}, $items)).')';
			}
			$tstats = $AT->db->selectGetAll(
				'tracker_stats', '',
				$tracker_fetch.$tracker_filter,
				'id, complete, incomplete, downloaded, updated'
			);
			foreach($tstats as $ts) {
				$items[$ts['id']]['complete'] = $ts['complete'];
				$items[$ts['id']]['downloaded'] = $ts['downloaded']; // exposed in JSON API
				$items[$ts['id']]['scraped'] = $ts['updated']; // exposed in JSON API
				$items[$ts['id']]['incomplete'] = $ts['incomplete'];
				
				if(isset($ts['incomplete']))
					unset($tr_missing[$ts['id']]);
			}
			if(!empty($tr_missing)) {
				$tstats = $AT->db->selectGetAll(
					'tracker_stats', '',
					'id IN ('.implode(',', array_keys($tr_missing)).')'.$tracker_filter,
					'id, MAX(complete) AS complete, MAX(incomplete) AS incomplete, MAX(downloaded) AS downloaded',
					array('group' => 'id')
				);
				foreach($tstats as $ts) {
					$items[$ts['id']]['complete'] = $ts['complete'];
					$items[$ts['id']]['downloaded'] = $ts['downloaded'];
					$items[$ts['id']]['incomplete'] = $ts['incomplete'];
				}
			}
		}
		
		if($disp_cat_type ?? '' == 'series_group') {
			$fetch_gids = [];
			foreach($items as $item) {
				if(!$item['gids']) continue;
				$item_gids = explode(',', $item['gids']);
				foreach($item_gids as $item_gid)
					$fetch_gids[$item_gid] = $item_gid;
			}
			if(!empty($fetch_gids)) {
				$group_names = $AT->db->selectGetAll('anidb.group', 'id', 'id IN ('.implode(',', $fetch_gids).')', 'id,name,shortname');
				foreach($items as &$item) {
					if(!$item['gids']) continue;
					$item_gids = explode(',', $item['gids']);
					$item['groups'] = [];
					foreach($item_gids as $item_gid)
						if(isset($group_names[$item_gid]))
							$item['groups'][$item_gid] = $group_names[$item_gid];
					if(empty($item['groups'])) unset($item['groups']);
				} unset($item);
			}
		}
	}
	
	$AT->db->suppressNotices(false);
}

// feed URL stuff
$feed_args = array_merge($urlargs, @$disp_feedargs?:[]);
$AT->output->head_extra_html .= '<link rel="alternate" type="application/atom+xml" title="Releases (Atom 1.0)" href="'.AT::buildUrl('feed', 'atom', $feed_args).'" />
<link rel="alternate" type="application/rss+xml" title="Releases (RSS 2.0)" href="'.AT::buildUrl('feed', 'rss2', $feed_args).'" />';

