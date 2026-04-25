<?php
if(!defined('AT_ROOT')) exit;

function view_link2html($title, $link, $force_enc = false) {
	$title = htmlspecialchars_uni($title);
	if(isset($link['st']) && ($link['st'] == 3 || $link['st'] == 1 || $link['st'] == -1))
		return '<span class="'.($link['st']==3?'deadlink':'inactivelink').'">'.$title.'</span>';
	
	$cpref = $csuf = '';
	if(!empty($link['children'])) {
		$cpref = '<span class="dlxlink">';
		$csuf = '<span class="downarrow">&#9660;</span><ul class="linkmenu">';
		foreach($link['children'] as $ctitle => &$clink)
			$csuf .= '<li>'.view_link2html($ctitle, $clink, $force_enc).'</li>';
		$csuf .= '</ul></span>';
	}
	
	if(@$link['st'] == 2)
		return $cpref.'<a href="'.htmlspecialchars($link['url']).'" class="deadlink">'.$title.'</a>'.$csuf;
	else {
		if($linkurl = Toto::getFileLink($link['url'], $force_enc))
			return $cpref.'<a href="'.htmlspecialchars($linkurl).'">'.$title.'</a>'.$csuf;
	}
}

function formatFileLinks($filelinks, $force_enc=false) {
	if(empty($filelinks)) return '';
	if(!is_array($filelinks)) {
		if(!($filelinks = filelinks_decode($filelinks))) return '';
	}
	
	filelinks_to_tree($filelinks);
	
	$ret = '';
	$multipart_links = '';
	$delim = $delim2 = '';
	foreach($filelinks as $site => $filelink) {
		if(substr($site, 0, 1) == '!') continue; // skip encrypted links
		// compact format for audio extracts
		if(isset($filelink['url'])) $filelink = [$filelink];
		
		$multipart_init = false;
		$multipart = count($filelink) > 1;
		foreach($filelink as $part => &$partlink) {
			isset($partlink['children']) or $partlink['children'] = array();
			if($multipart) {
				if(!$multipart_init) {
					$multipart_init = true;
					$multipart_links .= $delim2 . htmlspecialchars_uni($site) . ': ';
					$delim2 = '<br />';
					$delim3 = '';
				}
				$multipart_links .= $delim3.view_link2html('Part'.($part+1), $partlink, $force_enc);
				$delim3 = ', ';
			} else {
				
				$ret .= $delim.view_link2html($site, $partlink, $force_enc);
				$delim = ' | ';
			}
		}
	}
	if($multipart_links && $delim) $ret .= '<br />';
	return $ret.$multipart_links;
}

// only uses id, (if encrypted) stuff for viewUrl, and added keys of $file
function printAllFileLinksTable($file) {
	if(!isset($file['links'])) {
		$file['links'] = $GLOBALS['AT']->db->selectGetField('filelinks', 'links', '`fid`='.$file['id']);
	}
	$links = filelinks_decode($file['links']);
	if(empty($links)) return;
	
	$ddl_servers = ['ddl.animetosho.org'];
	
	// pull out encrypted links
	$enclink_threshold = TIME_NOW; //TIME_NOW - ($GLOBALS['AT']->user->uid == 1 ? 0 : 86400*21);
	$enclinks = array();
	//if($GLOBALS['AT']->user->uid)
	if(0) // all Direct links are probably dead, so always hide
		foreach($links as $site => $parts) {
			if(substr($site, 0, 1) == '!') {
				$firstpart = reset($parts);
				if($firstpart['added'] <= $enclink_threshold) {
					if($site == '!1Fichier') {
						$enclinks['Direct'] = array_map(function($part) use(&$ddl_servers) {
							if(preg_match('~^https?\://1fichier\.com/\?([a-zA-Z0-9]{10,20})$~', @$part['url'], $m)) {
								$part['url'] = 'https://'.$ddl_servers[array_rand($ddl_servers)].'/pf/'.cryptDirectFichierLink($m[1]);
							} elseif(!isset($part['url']) || $part['url']) {
								// unexpected URL format = hide it
								$part['url'] = '';
							}
							return $part;
						}, $parts);
					} elseif($GLOBALS['AT']->user->isMember()) {
						$enclinks[substr($site, 1)] = $parts;
					}
				}
				unset($links[$site]);
			}
		}
	
	if(!empty($links)) {
		echo '<tr><th>Download</th><td>';
		echo formatFileLinks($links);
		echo '</td></tr>';
	}
	if(!empty($enclinks)) {
		echo '<tr><th>Encrypted Download</th><td>';
		echo formatFileLinks($enclinks, true);
		echo '<br /><small>Password: <input type="text" value="'.htmlspecialchars(Toto::viewUrl($file, array(), true)).'" readonly="1" onfocus="this.select()" onclick="this.select()" ondblclick="this.select()" style="font-size: smaller; width: 20em; background: transparent;" class="text" /></small></td></tr>';
	}
}

function cryptDirectFichierLink($urlFrag) {
	$ip = inet_pton(REMOTE_IP);
	
	// !!! data not signed
	$data = hex2bin('1f35').pack('v', floor(time()/86400)).$urlFrag;
	$iv = openssl_random_pseudo_bytes(4); // .str_repeat("\0", 12)
	$key = hex2bin('3c0e528071f0c79e').str_pad(substr($ip, 0, strlen($ip)/2), 8, "\0");
	
	$data = $iv.openssl_encrypt($data, 'aes-128-ofb', $key, true, str_pad($iv, 16, "\0"));
	return bin2hex($data);
}


function getFileSubtitles($fid) {
	static $cache = [];
	if(isset($cache[$fid]))
		return $cache[$fid];
	
	$attachments = $GLOBALS['AT']->db->selectGetField('attachments', 'attachments', 'fid='.$fid);
	if($attachments) {
		require_once AT_ROOT.'pages/includes/finfo-compress.php';
		$attachments = FileInfoCompressor::decompress_unpack('attach', $attachments);
		$attachments = $attachments[1] ?? null;
	}
	return $cache[$fid] = $attachments;
}

function getFileInfo($fid, $types='7z,content,mediainfo') {
	static $cached_id, $cached_data;
	if($cached_id == $fid.$types)
		return $cached_data;
	
	require_once AT_ROOT.'pages/includes/finfo-compress.php';
	$info = $GLOBALS['AT']->db->selectGetArray('fileinfo', 'fid='.$fid.' AND type IN("'.str_replace(',', '","', $types).'")');
	
	$cached_id = $fid.$types;
	if(empty($info))
		return $cached_data = ['', null];
	$sz = FileInfoCompressor::decompressed_size($info['info']);
	if($sz && $sz > 4194304) // refuse to decompress if larger than 4MB
		return $cached_data = ['', null];
	return $cached_data = [
		$info['type'],
		FileInfoCompressor::decompress($info['type'], $info['info'])
	];
}

function urlArgBuild($a) {
	if(empty($a)) return '';
	$delim = '?';
	$ret = '';
	foreach($a as $k => $v) {
		$ret .= $delim.rawurlencode($k).'='.rawurlencode($v);
		$delim = '&';
	}
	return $ret;
}

// note this function requires some <style> stuff in the header!
function printScreenshots($file) {
	$finfo = getFileInfo($file['id']);
	echo '<tr><th>', ($finfo[0] == 'mediainfo' ? 'Screenshots':'Sample Images'), '</th><td>';
	if($file['vidframes'] || $file['vidframes'] === '0') {
		// determine which subtitle to render
		$subtitle = null;
		$sattach = getFileSubtitles($file['id']);
		if(!empty($sattach)) {
			// track selection: pick default/forced if there's only one, else pick first English track, else pick first track (SRT tracks always excluded)
			$track_def = $track_eng = [];
			foreach($sattach as $subtrack) {
				if(empty($subtrack) || !isset($subtrack['tracknum']) || @$subtrack['codec'] == 'SRT') continue;
				if(@$subtrack['default'] || @$subtrack['forced'])
					$track_def[] = $subtrack['tracknum'];
				if(@$subtrack['lang'] == 'eng')
					$track_eng[] = $subtrack['tracknum'];
				if(!isset($subtitle))
					$subtitle = $subtrack['tracknum'];
			}
			if(count($track_def) == 1)
				$subtitle = reset($track_def);
			elseif(!empty($track_eng))
				$subtitle = reset($track_eng);
		}
		
		// x264 build hack
		$mi = $finfo[1];
		$x264_build = '';
		if(preg_match('~'."\n".'Chroma subsampling\s+\: 4\:4\:4.*?'."\n".'Writing library\s+\: x264 core (\d+) r~si', $mi, $m)) {
			// extract x264 build
			$x264_build = '&x264='.$m[1];
		}
		
		$ss = array();
		foreach(explode(',', $file['vidframes']) as $sstime) {
			$urlpath = 'sframes/'.storage_id2hash($file['id']).'_'.$sstime;
			$urlbase = STORAGE_URL.$urlpath;
			$ss[(int)($sstime/1000)] = array(
				STORAGE_URL_REDIR.$urlpath.'.png'.($subtitle ? '?s='.$subtitle.$x264_build : ($x264_build ? '?'.substr($x264_build, 1) : '')),
				// some bots order params alphabetically, so order 'h' before 'w' to be consistent to help caching
				$urlbase.'.jpg?h=96&w=128'.$x264_build,
				$urlbase.'.jpg?h=144&w=192'.$x264_build.' 1.5x, '.$urlbase.'.jpg?h=192&w=256'.$x264_build.' 2x',
			);
		}
	} else {
		$ss = unserialize($file['filethumbs']);
		$sstore = null;
		if($file['filestore'] !== '')
			$sstore = unserialize($file['filestore']);
		if(empty($ss) && empty($sstore)) {
			echo '</td></tr>';
			return;
		}
		
		if(empty($ss)) $ss = array();
		// merge $sstore into $ss
		if(!empty($sstore['original'])) foreach($sstore['original'] as $sstime => $img) {
			if(!preg_match('~^([a-f0-9]{3})/([a-f0-9]{3})/([a-f0-9]{2})_o([a-f0-9]{16}\.[a-z0-9]{2,4})$~', $img, $m)) continue;
			$img = STORAGE_URL_REDIR.'sshots/'.$m[1].$m[2].$m[3].'/o'.$m[4];
			if(!isset($ss[$sstime]))
				$ss[$sstime] = array($img);
			else
				$ss[$sstime][0] = $img;
		}
		if(!empty($sstore['thumb'])) foreach($sstore['thumb'] as $sstime => $img) {
			if(!preg_match('~^([a-f0-9]{3})/([a-f0-9]{3})/([a-f0-9]{2})_t([a-f0-9]{16}\.jpg)$~', $img, $m)) continue;
			$img = STORAGE_URL.'sshots/'.$m[1].$m[2].$m[3].'/t'.$m[4];
			if(!isset($ss[$sstime]))
				$ss[$sstime] = array(false, $img);
			else
				$ss[$sstime][1] = $img;
		}
		ksort($ss);
	}
	
	// overflow: hidden; white-space: nowrap; display: inline-block; 
	$sshtml = [];
	$ic = 0;
	foreach($ss as $sstime => $imgs) {
		++$ic;
		if($finfo[0] == 'mediainfo')
			$sstitle = ' title="Screenshot at '.secs2time($sstime).'"';
		else
			$sstitle = ' title="'.htmlspecialchars($sstime).'"';
		
		$html = '';
		if($imgs[0])
			$html .= '<a href="'.htmlspecialchars($imgs[0]).'" class="screenthumb"'.$sstitle.'>';
		
		if(isset($imgs[1])) { // thumbnail
			$srcset = '';
			if(isset($imgs[2])) $srcset = ' srcset="'.htmlspecialchars($imgs[2]).'"';
			$html .= '<img src="'.htmlspecialchars($imgs[1]).'" alt="preview"'.$sstitle.$srcset.' class="screenthumb"';
			if($ic >= 3) $html .= ' loading="lazy"';
			$html .= '/>';
		}
		else // no thumb - display directly
			$html .= '[thumbnail unavailable]';
		
		if($imgs[0])
			$html .= '</a>';
		$sshtml[] = $html;
	}
	echo '<div style="position: relative;">';
	echo implode('', array_slice($sshtml, 0, 2));
	if(isset($sshtml[2])) {
		// screenshot hider
		echo '<input type="checkbox" id="thumbnail_hider_check" role="button" />';
		echo '<label id="thumbnail_holder_disp" for="thumbnail_hider_check" title="Show more screenshots">more &raquo;</label>';
		echo '<span id="thumbnail_hider">';
		$hidden_thumbs = implode('', array_slice($sshtml, 2));
		echo '<noscript>',$hidden_thumbs,'</noscript>';
		echo '</span>';
		echo '<script type="text/javascript"><!--
			function showThumbnails(){
				$("#thumbnail_hider").html($("#thumbnail_hider_check").is(":checked") ? '.json_encode($hidden_thumbs).' : "");
			}
			$("#thumbnail_hider_check").change(showThumbnails);
			addEventListener("pageshow", showThumbnails);
		//--></script>';
	}
	echo '</div>';
	echo '</td></tr>';
}
function secs2time($s) {
	return str_pad((int)($s/3600), 2, '0', STR_PAD_LEFT)
	  .':'.str_pad((int)($s/60) %60, 2, '0', STR_PAD_LEFT)
	  .':'.str_pad($s %60, 2, '0', STR_PAD_LEFT);
}

function printExtractions($file) {
	if(!empty($file['audioextract']))
		$audio = filelinks_decode($file['audioextract'], 'audioextract');
	// check for storage attachments
	$sattach = getFileSubtitles($file['id']);
	
	if(empty($audio) && empty($sattach)) return;
	
	echo '<tr><th>Extractions</th><td>';
	if(!empty($audio)) {
		echo 'Audio: ', formatFileLinks($audio);
		if(!empty($sattach)) echo '<br/>';
	}
	if(!empty($sattach)) {
		echo 'Subtitles: <a href="', STORAGE_URL_REDIR, 'attachpk/', $file['id'], '/', name_base_for_url($file['filename']), '_attachments.7z">All Attachments</a>';
		
		$comma = ' | ';
		foreach($sattach as $attach) {
			echo $comma;
			if($attach)
				print_subtitle_link($attach, $file['filename']);
			else
				echo '[unavailable]';
			$comma = ', ';
		}
	} /* if($file['filesubs'] !== '' || $file['attachpack']) {
		echo '<tr><th>Subtitles</th><td>';
		printSubtracks($file['filesubs'], $file['attachpack']);
		echo '</td></tr>';
	} */
	echo '</td></tr>';
}

/* function printSubtracks($subs, $attachpack=null) {
	$tracks = null;
	if($subs) $tracks = unserialize($subs);
	if($attachpack) {
		echo '<a href="',htmlspecialchars($attachpack),'">All Attachments</a>';
		if(!empty($tracks))
			echo ' | ';
	}
	if(empty($tracks)) return;
	$comma = '';
	foreach($tracks as $trackid => $track) {
		// we're guaranteed to have a codec set
		$text = '#'.$trackid.': ';
		if(@$track['name']) {
			$text .= htmlspecialchars($track['name']).' [';
			if(@$track['lang'])
				$text .= htmlspecialchars($track['lang']).', ';
			$text .= htmlspecialchars($track['codec']).']';
		} else {
			if(@$track['lang'])
				$text .= htmlspecialchars($track['lang']).' ['.htmlspecialchars($track['codec']).']';
			else
				$text .= htmlspecialchars($track['codec']);
		}
		if(@$track['url'])
			$text = '<a href="'.htmlspecialchars($track['url']).'">'.$text.'</a>';
		
		echo $comma, $text;
		$comma = ', ';
	}
} */

function buildViewBreadcrumb($aid, $eid, $series_title=null, &$episode=null) {
	$bc = array();
	global $AT;
	if($aid && !isset($series_title)) {
		$series_title = $AT->db->selectGetField('anidb.animetitle', 'name', 'aid='.$aid.' AND type=1');
	}
	
	if($aid && $series_title) {
		$series_link = AT::buildUrl('series', AT::seoPageSubaction($aid, $series_title));
		$bc[$series_link] = htmlspecialchars($series_title);
		if($eid && !isset($episode)) {
			$episode = $AT->db->selectGetArray('anidb.ep', 'id='.$eid, 'name,epno,type');
		}
		if($eid && !empty($episode) && ($episode['epno'] || $episode['name'])) {
			$episode_title = format_episode($episode, 'short');
			$episode_link = AT::buildUrl('episode', AT::seoPageSubaction($eid, $series_title.($episode['epno'] ? ' '.format_episode($episode, 'num') : '')));
			$bc[$episode_link] = htmlspecialchars($episode_title);
		}
		elseif(!$eid) {
			$episode_link = AT::buildUrl('episode', $aid.'.unsorted');
			$bc[$episode_link] = ($episode_title = 'Unsorted Files');
		} else
			$episode_title = $episode_link = ''; // won't be shown anyway
	} else {
		$bc[AT::buildUrl('series', 'unsorted')] = 'Unsorted Files';
	}
	
	return $bc;
}
