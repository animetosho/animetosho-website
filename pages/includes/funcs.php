<?php
if(!defined('AT_ROOT')) exit;

if(!defined('STORAGE_URL_REDIR'))
	define('STORAGE_URL_REDIR', AT::buildUrl('storage').'/');

// copy of id2hash() from cron/includes/finfo.php
function storage_id2hash($id) {
	return str_pad(base_convert($id, 10, 16), 8, '0', STR_PAD_LEFT);
}

function name_base_for_url($title, $full=false) {
	if($full)
		$title = strtr($title, array('/'=>''));
	else
		$title = basename($title);
	$title = mb_substr(preg_replace('~\.[a-zA-Z0-9]{1,10}$~', '', $title), 0, 200);
	return rawurlencode($title);
}
function torrent_link_url($item) {
	if($item['stored_torrent'] && $item['btih']) {
		$base = STORAGE_URL_REDIR;
		if(empty($_SERVER['HTTPS'])) // workaround for clients which don't support HTTPS
			$base = str_replace('https://', 'http://', $base);
		return $base.'torrent/'.bin2hex($item['btih']).'/'.name_base_for_url($item['torrentname'] ?: $item['name'], 1).'.torrent';
	}
	return $item['link'];
}
function magnet_link_url($item) {
	return $item['magnet'].'&dn='.rawurlencode($item['name']);
}
function nzb_link_url($item, $gz='.gz') {
	if(!$item['stored_nzb']) return false;
	$base = STORAGE_URL_REDIR;
	if(empty($_SERVER['HTTPS'])) // workaround for clients which don't support HTTPS
		$base = str_replace('https://', 'http://', $base);
	return $base.'nzbs/'.storage_id2hash($item['id']).'/'.name_base_for_url($item['torrentname'], 1).'.nzb'.$gz;
}

function filelinks_decode($fl, $ctype='filelinks') {
	if(!$fl) return null;
	static $loaded = false;
	if(!$loaded) {
		require_once AT_ROOT.'pages/includes/finfo-compress.php';
		$loaded = true;
	}
	$ret = FileInfoCompressor::decompress_unpack($ctype, $fl);
	// filter out invalid entries
	if($ret) {
		foreach($ret as $site => &$parts)
			foreach($parts as $pn => $v)
				if(!$v) unset($parts[$pn]);
	}
	return $ret;
}
// merge children into parents
function filelinks_to_tree(&$fl) {
	foreach($fl as $site => &$filelink)
		if(strpos($site, '|')) {
			list($psite, $ssite) = explode('|', $site, 2);
			if(true || $psite != 'ExoShare' || $GLOBALS['AT']->user->isMember()) {
				if(isset($fl[$psite])) {
					// note that we implicitly assume that child sites have same number of parts as their parents
					foreach($filelink as $part => &$partlink) {
						$plinks =& $fl[$psite][$part]['children'];
						isset($plinks) or $plinks = array();
						$plinks[$ssite] = $partlink;
					}
				}
			}
			unset($fl[$site]);
		}
}

function print_subtitle_link($subtitle, $filename) {
	echo '<a href="', STORAGE_URL_REDIR, 'attach/', storage_id2hash($subtitle['_afid']), '/', name_base_for_url($filename), '_track';
	
	// generate filename
	if(isset($subtitle['tracknum']))
		echo $subtitle['tracknum'];
	if(@$subtitle['lang'])
		echo '.', htmlspecialchars($subtitle['lang']);
	if($subtitle['codec'] == 'VOB') echo empty($subtitle['vobidx']) ? '.sub' : '.idx';
	elseif($subtitle['codec'] == 'PGS') echo '.sup';
	else echo '.', htmlspecialchars(strtolower($subtitle['codec']));
	echo '.xz">';
	
	// we're guaranteed to have a codec set
	if(@$subtitle['name']) {
		echo htmlspecialchars($subtitle['name']), ' [';
		if(@$subtitle['lang'])
			echo htmlspecialchars($subtitle['lang']), ', ';
		echo htmlspecialchars($subtitle['codec']), ']';
	} else {
		if(@$subtitle['lang'])
			echo htmlspecialchars($subtitle['lang']), ' ';
		echo '[', htmlspecialchars($subtitle['codec']), ']';
	}
	
	echo '</a>';
}

function format_episode($ep, $format='num', $num_eps=null) {
	$eptypes = array(null, 'Episode', 'Special','Credit','Trailer','Parody','Other');
	$eptypes_short = array(null, '', 'S','C','T','P','O');
	
	$eptype = $ep['type'] ?: 1;
	switch($format) {
		case 'num': // number only, eg 'S1' or '10'
			if($ep['epno'])
				return $eptypes_short[$eptype].$ep['epno'];
			return '';
		case 'short': // eg '2, A Great Second Episode'
			$epline = '';
			if($ep['epno']) {
				$epline = $eptypes_short[$eptype].$ep['epno'];
			}
			if($ep['name']) {
				if($ep['name'] != 'Episode '.$eptypes_short[$eptype].$ep['epno'])
					$epline .= ($ep['epno']?', ':'').$ep['name'];
				if(($ep['type'] == 2 && $ep['name'] == 'OVA '.$ep['epno']) || ($ep['epno'] == 1 && $num_eps == 1))
					// for single episode movies/OVAs
					$epline = $ep['name'];
			}
			return $epline;
		case 'long': // eg 'Episode 2 (of 10): A Great Second Episode'
			$epline = '';
			if($ep['epno']) {
				$epline = $eptypes[$eptype].' '.$ep['epno'];
				if($eptype == 1 && $num_eps) {
					$epline .= ' (of '.$num_eps.')';
				}
			}
			if(!empty($ep['name'])) {
				if($ep['name'] != 'Episode '.$eptypes_short[$eptype].$ep['epno'])
					$epline .= ($ep['epno']?': ':'').$ep['name'];
				if(($ep['type'] == 2 && $ep['name'] == 'OVA '.$ep['epno']) || ($ep['epno'] == 1 && $num_eps == 1))
					// for single episode movies/OVAs
					$epline = $ep['name'];
			}
			return $epline;
	}
}

// like PHP's wordwrap, but this favours cutting strings after underscores
/*
function wordwrap_fn($in, $len=50, $delim=' ') {
	// since this is aimed at filenames, ignore newlines and non-space delimiters
	$ina = explode(' ', $in);
	foreach($ina as &$s) {
		if(mb_strlen($s) > $len) {
			// find underscore split points
			if(strpos($s, '_')) {
				$su = explode('_', $s);
				$s = '';
				$cnt = 0;
				for($k=0, $kc=count($su); $k<$kc; ++$k) {
					$sus = $su[$k];
					// append underscore to everything but last elem
					if($k+1 < $kc) $sus .= '_';
					$sus_len = mb_strlen($sus);
					if($sus_len + $cnt > $len) {
						$s .= ($cnt ? $delim : '') . wordwrap($sus, $len, $delim, true);
						$cnt = $sus_len % $len;
					}
					else {
						// not over limit - join
						$s .= $sus;
						$cnt += $sus_len;
					}
				}
			} else
				$s = wordwrap($s, $len, $delim, true);
		}
	}
	return implode(' ', $ina);
}
*/
// simpler version of above - inserts delim after every underscore
// only designed for utf-8 charset
function mb_wordwrap($string, $len, $delim) {
	// this preg_split call will only work with utf-8 charsets (and similar)
	$stre = preg_split('~(\s+)~', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
	$isdelim = true;
	foreach($stre as &$s) {
		$isdelim = !$isdelim;
		if($isdelim) continue;
		
		if(mb_strlen($s) > $len) {
			$sj = $s;
			$s = $delimj = '';
			do {
				$s .= $delimj . mb_substr($sj, 0, $len);
				$sj = mb_substr($sj, $len);
				$delimj = $delim;
			} while(mb_strlen($sj) > $len);
			if($sj !== '')
				$s .= $delim . $sj;
		}
	}
	return implode('', $stre);
}


function wordwrap_fn2($in, $len=50, $delim=' ') {
	// since this is aimed at filenames, ignore newlines and non-space delimiters
	$ina = explode(' ', $in);
	foreach($ina as &$s) {
		if(mb_strlen($s) > $len) {
			// find underscore split points
			if(strpos($s, '_')) {
				$su = explode('_', $s);
				foreach($su as &$sus) {
					if(mb_strlen($sus)+1 > $len)
						$sus = mb_wordwrap($sus, $len, $delim, true);
				}
				$s = implode('_'.$delim, $su);
			} else
				$s = mb_wordwrap($s, $len, $delim, true);
		}
	}
	return implode(' ', $ina);
}

function filtered_noindex($anititle) {
	if(!isset($GLOBALS['noindex_keywords'])) {
		include AT_ROOT.'pages/includes/crfilter.php';
		foreach(['blocked_aids','blocked_vids','noindex_keywords'] as $k) {
			$GLOBALS[$k] = $$k;
		}
	}
	global $noindex_keywords, $AT;
	if(!empty($noindex_keywords)) {
		$name_i = strtolower($anititle);
		foreach($noindex_keywords as $block_kw) {
			if(strpos($name_i, $block_kw) !== false || strpos($name_i, str_replace(' ', '', $block_kw)) !== false) {
				$AT->output->head_metas['robots'] = 'noindex';
				break;
			}
		}
	}
}

function nyaa_source_link($item) {
	$ret = '<a href="https://'.($item['nyaa_subdom'] ? $item['nyaa_subdom'].'.':'').'nyaa.si/view/'.$item['nyaa_id'].'"';
	switch($item['nyaa_class']) {
		case -1: case 0:
			if($item['nyaa_cat']) $ret .= ' title="Nyaa: Hidden"><span style="color: #969696;">&#9679;</span'; // orig: C0C0C0
			break;
		case 1: $ret .= ' title="Nyaa: Remake"><span style="color: #c18558;">&#9679;</span'; break; // orig: F0B080
		case 2: $ret .= ' title="Nyaa: Normal"><span style="color: #FBFBFB;">&#9679;</span'; break;
		case 3: $ret .= ' title="Nyaa: Trusted"><span style="color: #6cac7e;">&#9679;</span'; break; // orig: 98D9A8
		case 4: $ret .= ' title="Nyaa: A+"><span style="color: #2b86c3;">&#9679;</span'; break; // orig: 60B0F0
	}
	return $ret.'>Nyaa</a>';
}

function get_anime_en_titles($aids) {
	// resolve English anime titles - prefer official title, but fallback to a synonym if there's only one
	global $AT;
	// NOTE: 2 = synonym/alias, 4 = official title
	$AT->db->suppressNotices(true);
	$titles = $AT->db->selectGetAll('anidb.animetitle', '', 'type IN(2,4) AND langid=4 AND aid IN('.implode(',', $aids).')', 'aid,type,name');
	$AT->db->suppressNotices(false);
	
	$ret = []; $alt = [];
	foreach($titles as $title) {
		$aid = $title['aid'];
		if($title['type'] == 4)
			$ret[$aid] = $title['name'];
		else {
			if(!isset($alt[$aid])) $alt[$aid] = [];
			$alt[$aid][] = $title['name'];
		}
	}
	// add in missing titles
	foreach($alt as $aid => $titles) {
		if(!isset($ret[$aid]) && count($titles) == 1)
			$ret[$aid] = reset($titles);
	}
	return $ret;
}

function validate_display_name($name) {
	// length must be 3-25 chars long
	if(!isset($name[2]))
		return 'Name is too short - it must be at least 3 characters long.';
	if(isset($name[25]))
		return 'Name is too long - it must be at most 25 characters long.';
	// verify chars
	if(preg_match("#[\x80-\xff]#", $name))
		return 'Name may only contain ASCII (basic) characters.';
	if(is_numeric($name))
		return 'Sorry, name cannot be purely numeric.  Please include at least one character.';
	
	return false;
}

function log_activity($id) {
	global $AT;
	$AT->db->insert('activity_log', [
		'action' => $AT->input->action,
		'id' => $id,
		'ip' => REMOTE_IP,
		'uagent' => @$_SERVER['HTTP_USER_AGENT'] ?: null,
		'dateline' => TIME_NOW,
	]);
	
	// randomly prune entries
	if(!mt_rand(0, 10))
		$AT->db->delete('activity_log', 'dateline<'.(TIME_NOW-86400*3));
}
