<?php
if(!defined('AT_ROOT')) exit;

if($AT->input->subaction == 'nabapi' || $AT->input->subaction == 'rssx') // support rewrite
	$AT->input->subaction = 'api';

$AT->input->disp = ''; // TODO: allow this to be changed
$AT->input->parse(array(
	'only_nzb' => AT_Input::TYPE_BOOL,
	'only_tor' => AT_Input::TYPE_BOOL,
));
$ulcomplete_map = [0=>'processing', 1=>'complete', 2=>'complete_partial', -1=>'skipped', -2=>'download_failed', -3=>'error'];

$emptyList = false;
if($AT->input->subaction == 'api') {
	// API support
	switch($AT->input->get(AT_Input::TYPE_STR, 't')) {
		case 'caps': // also consider mapping it directly in nginx
			header('Expires: '.gmdate('D, d M Y H:i:s', TIME_NOW+86400*7).' GMT');
			header('Cache-Control: public');
			header('Content-Type: text/xml; charset=utf-8');
			header('X-Accel-Redirect: /nabcaps.xml');
			return;
		case '': case 'search':
			$AT->input->page = 1;
			$AT->input->perpage = 75;
			if($limit = $AT->input->get(AT_Input::TYPE_INT, 'limit'))
				$AT->input->perpage = $limit;
			if($offset = $AT->input->get(AT_Input::TYPE_INT, 'offset'))
				$disp_offset = $offset;
			if($AT->input->got('order'))
				$sortCol = strtolower(preg_replace('~-.*$~', '', $AT->input->get(AT_Input::TYPE_STR, 'order')));
			else
				$sortCol = 'date';
			if($sort = $AT->input->get(AT_Input::TYPE_STR, 'sort')) {
				if(preg_match('~^([a-z]+)_(asc|desc)$~', strtolower($sort), $m)) {
					if($m[1] == 'size')
						$sortCol = 'size';
					elseif($m[1] == 'posted')
						$sortCol = 'date';
					// unsupported: name, files
					// 'cat' is pointless, dunno what 'stats' refers to
					$AT->input->order = $sortCol.'-'.$m[2][0];
				} else
					$AT->input->order = 'date-d';
			}
			
			if($sortCol == 'size' && (($minsize = $AT->input->get(AT_Input::TYPE_INT, 'minsize')) || ($maxsize = $AT->input->get(AT_Input::TYPE_INT, 'maxsize')))) {
				if($minsize) $disp_qwhere = (@$disp_qwhere ? ' AND ':'').'/*\'"sphinxstrip"\'*/idx_totalsize>='.$minsize;
				if($maxsize) $disp_qwhere = (@$disp_qwhere ? ' AND ':'').'/*\'"sphinxstrip"\'*/idx_totalsize<='.$maxsize;
			}
			if($sortCol == 'date' && ($maxage = $AT->input->get(AT_Input::TYPE_INT, 'maxage'))) {
				$disp_qwhere = (@$disp_qwhere ? ' AND ':'').'/*\'"sphinxstrip"\'*/idx_dateline>='.(TIME_NOW-$maxage*86400);
			}
			// TODO: support cat parameter
			if($cat = $AT->input->get(AT_Input::TYPE_STR, 'cat')) {
				$catArray = explode(',', $cat);
				if(!in_array('5070', $catArray) && !in_array('2020', $catArray))
					$emptyList = true;
			}
			
			break; // continue on below
		case 'details': // TODO: parse id, and just filter the feed based on that
		case 'get': // TODO:
		
		default: // getinfo, register, tvsearch, movie, music, book, cartadd, cartdel, comments, commentadd, user, 
			$AT->output->error('Function Not Available.', 'Function Not Available', '203 Function Not Available');
	}
}

function bin2hex_coalesce($arr, $k) {
	return isset($arr[$k]) ? bin2hex($arr[$k]) : null;
}

if($AT->input->subaction == 'json') {
	header('Content-Type: application/json; charset=utf-8');
	// cache-control, expires, etc headers?
	
	
	
	function fmt_filelinks($links) {
		$retlinks = [];
		foreach($links as $site => $parts) {
			if(substr($site, 0, 1) == '!' /*encrypted link*/ || strpos($site, '|')) continue;
			if(isset($parts['url'])) $parts = [$parts]; // compact format for audioextract
			if(count($parts) > 1) {
				$linkparts = [];
				foreach($parts as $part => $ln) {
					if(@$ln['st']) continue; // skip bad links
					if(!($linkurl = Toto::getFileLink($ln['url']))) continue;
						$linkparts[$part] = $linkurl;
				}
				$retlinks[$site] = $linkparts;
			} else {
				$ln = reset($parts);
				if(!@$ln['st'] && ($linkurl = Toto::getFileLink($ln['url'])))
					$retlinks[$site] = $linkurl;
			}
			//$is_multipart = (count($parts) > 1);
		}
		return $retlinks;
	}
	function fmt_file($file, $attachment_files) {
		$ret = [
			'id' => (int)$file['id'],
			'is_archive' => (bool)($file['type'] == 1),
			'filename' => $file['filename'],
			'size' => (double)$file['filesize'],
			'processed' => (bool)$file['crc32k'],
			
			'crc32' => bin2hex_coalesce($file, 'crc32'),
			'md5' => bin2hex_coalesce($file, 'md5'),
			'sha1' => bin2hex_coalesce($file, 'sha1'),
			'sha256' => bin2hex_coalesce($file, 'sha256'),
			'ed2k' => bin2hex_coalesce($file, 'ed2k'),
			'bt2' => bin2hex_coalesce($file, 'bt2'),
		];
		if($ret['processed']) {
			$ret['vidframe_timestamps'] = $file['vidframes'] ? array_map('intval', explode(',', $file['vidframes'])) : null;
			if(isset($file['attachments'])) {
				$ret['attachments'] = [];
				foreach([ATTACHMENT_OTHER, ATTACHMENT_SUBTITLE] as $attachType) {
					if(!isset($file['attachments'][$attachType])) continue;
					$attachTypeStr = attachment_type_str($attachType);
					foreach($file['attachments'][$attachType] as $attach) {
						if(!$attach) continue;
						$attachInfo = unpack_attachment_info($attachType, $attach);
						unset($attachInfo['_afid']);
						$sz = $attachment_files[$attach['_afid']]['filesize'] ?? null;
						$ret['attachments'][] = [
							'id' => $attach['_afid'],
							'type' => $attachTypeStr,
							'info' => $attachInfo,
							'size' => isset($sz) ? (int)$sz : null
						];
					}
				}
				foreach([ATTACHMENT_CHAPTERS, ATTACHMENT_TAGS] as $attachType) {
					if(!isset($file['attachments'][$attachType])) continue;
					$afid = $file['attachments'][$attachType];
					$sz = $attachment_files[$afid]['filesize'] ?? null;
					$ret['attachments'][] = [
						'id' => $afid,
						'type' => attachment_type_str($attachType),
						'size' => isset($sz) ? (int)$sz : null
					];
				}
			}
		}
		if(!empty($file['links']) && ($links = filelinks_decode($file['links']))) {
			$retlinks = fmt_filelinks($links);
			if(!empty($retlinks))
				$ret['links'] = $retlinks;
		}
		if(!empty($file['audioextract']) && ($audiolinks = filelinks_decode($file['audioextract'], 'audioextract'))) {
			$retlinks = fmt_filelinks($audiolinks);
			if(!empty($retlinks))
				$ret['links_audio'] = $retlinks;
		}
		if(!empty($file['fileinfo'])) {
			$ret['info'] = [];
			foreach($file['fileinfo'] as $info) {
				$sz = FileInfoCompressor::decompressed_size($info['info']);
				if(!$sz || $sz < 4194304) {
					if($info['type'] == 'ffprobe' || $info['type'] == 'mediainfoj')
						$unpacked_data = FileInfoCompressor::decompress_unpack($info['type'], $info['info']);
					else
						$unpacked_data = FileInfoCompressor::decompress($info['type'], $info['info']);
					$ret['info'][$info['type']] = $unpacked_data;
				}
			}
		}
		return $ret;
	}
	function proc_file_unpack_collect_attach(&$file, &$attachfile_ids) {
		if(!isset($file['attachments'])) return;
		$file['attachments'] = FileInfoCompressor::decompress_unpack('attach', $file['attachments']);
		if(!$file['attachments']) return;
		foreach([ATTACHMENT_OTHER, ATTACHMENT_SUBTITLE] as $attachType) {
			if(!isset($file['attachments'][$attachType])) continue;
			foreach($file['attachments'][$attachType] as $attach) {
				if($attach)
					$attachfile_ids[$attach['_afid']] = 1;
			}
		}
		foreach([ATTACHMENT_CHAPTERS, ATTACHMENT_TAGS] as $attachType)
			if(isset($file['attachments'][$attachType]))
				$attachfile_ids[$file['attachments'][$attachType]] = 1;
	}
	function proc_file_get_attachfiles($attachfile_ids) {
		if(empty($attachfile_ids)) return [];
		global $AT;
		return $AT->db->selectGetAll('attachment_files', 'id', 'id IN ('.implode(',', array_keys($attachfile_ids)).')', 'id,filesize');
	}
	
	switch(strtolower($AT->input->get(AT_Input::TYPE_STR, 'show'))) {
		case 'torrent':
			// fetch torrent
			$qwhere = null;
			if($id = $AT->input->get(AT_Input::TYPE_INT, 'id'))
				$qwhere = 'id='.$id;
			elseif($btih = $AT->input->get(AT_Input::TYPE_STR, 'btih')) {
				if(!preg_match('~^[a-fA-F0-9]{40}$~', $btih)) {
					header('HTTP/1.1 404 Not Found');
					echo '{"error":"Requested torrent not found"}';
					return;
				}
				$qwhere = 'btih=X\''.$btih.'\' AND isdupe=0';
			}
			else {
				$nyaa_id = $AT->input->get(AT_Input::TYPE_INT, 'nyaa_id');
				$sukebei_id = $AT->input->get(AT_Input::TYPE_INT, 'sukebei_id');
				$tosho_id = $AT->input->get(AT_Input::TYPE_INT, 'tosho_id');
				$anidex_id = $AT->input->get(AT_Input::TYPE_INT, 'anidex_id');
				$nekobt_id = $AT->input->get(AT_Input::TYPE_INT, 'nekobt_id');
				
				if(!!$nyaa_id + !!$sukebei_id + !!$tosho_id + !!$anidex_id + !!$nekobt_id != 1) {
					header('HTTP/1.1 404 Not Found');
					echo '{"error":"Requested torrent not found"}';
					return;
				}
				
				if($nyaa_id)
					$qwhere = 'nyaa_id='.$nyaa_id.' AND nyaa_subdom=""';
				if($sukebei_id)
					$qwhere = 'nyaa_id='.$sukebei_id.' AND nyaa_subdom="sukebei"';
				if($tosho_id)
					$qwhere = 'tosho_id='.$tosho_id;
				if($anidex_id)
					$qwhere = 'anidex_id='.$anidex_id;
				if($nekobt_id)
					$qwhere = 'nekobt_id='.$nekobt_id;
			}
			// fetch files
			if(isset($qwhere))
				$torrent = $AT->db->selectGetArray('toto', $qwhere);
			if(empty($torrent)) {
				header('HTTP/1.1 404 Not Found');
				echo '{"error":"Requested torrent not found"}';
				return;
			}
			
			require_once AT_ROOT.'pages/includes/finfo-compress.php';
			require_once AT_ROOT.'pages/includes/attach-info.php';
			$files = $AT->db->selectGetAll('files', 'id', '`toto_id`='.$torrent['id'], 'files.*, filelinks.links, attachments.attachments', array(
				//'order' => 'filename',
				'joins' => [['left', 'filelinks', 'id', 'fid'], ['left', 'attachments', 'id', 'fid']]
			));
			// TODO: consider moving attachments/audioextract to file info only
			$attachment_files = [];
			if(!empty($files)) {
				$attachfile_ids = [];
				foreach($files as &$file)
					proc_file_unpack_collect_attach($file, $attachfile_ids);
				unset($file);
				$attachment_files = proc_file_get_attachfiles($attachfile_ids);
			}
			
			echo json_encode([
				//'id' => $torrent['id'],
				'title' => $torrent['name'],
				'timestamp' => (int)$torrent['dateline'],
				'status' => @$ulcomplete_map[$torrent['ulcomplete']] ?: 'unknown',
				//'added_timestamp' => $torrent['updated'],
				'tosho_id' => (int)$torrent['tosho_id'] ?: null,
				//'tosho_cat' => (int)$torrent['cat'],
				'nyaa_id' => (int)$torrent['nyaa_id'] ?: null,
				'nyaa_subdom' => $torrent['nyaa_subdom'] ?: null,
				'anidex_id' => (int)$torrent['anidex_id'] ?: null,
				'nekobt_id' => (int)$torrent['nekobt_id'] ?: null,
				
				'comment' => $torrent['comment'],
				'is_dupe' => (bool)$torrent['isdupe'],
				'deleted' => (bool)$torrent['deleted'],
				
				'torrent_url' => torrent_link_url($torrent),
				'torrent_name' => $torrent['torrentname'],
				'info_hash' => bin2hex_coalesce($torrent, 'btih'),
				'info_hash_v2' => bin2hex_coalesce($torrent, 'btih_sha256'),
				'magnet_uri' => magnet_link_url($torrent),
				
				'nzb_url' => nzb_link_url($torrent, '') ?: null,
				
				'anidb_aid' => (int)$torrent['aid'] ?: null,
				'anidb_eid' => (int)$torrent['eid'] ?: null,
				'anidb_fid' => (int)$torrent['fid'] ?: null,
				
				'article_url' => $torrent['srcurl'] ?: null,
				'article_title' => $torrent['srcurl'] ? $torrent['srctitle'] : null,
				
				'website_url' => $torrent['website'] ?: null,
				
				'total_size' => (double)$torrent['totalsize'],
				'num_files' => (int)$torrent['torrentfiles'],
				'primary_file_id' => (int)$torrent['sigfid'] ?: null,
				'files' => $torrent['ulcomplete'] < 1 ? null : array_map(fn($x) => fmt_file($x, $attachment_files), array_values($files))
			], JSON_UNESCAPED_SLASHES);
			
		return;
		case 'file':
			$file = null;
			if($id = $AT->input->get(AT_Input::TYPE_INT, 'id'))
				$file = $AT->db->selectGetArray('files', 'id='.$id, 'files.*, filelinks.links, attachments.attachments', [
					'joins' => [['left', 'filelinks', 'id', 'fid'], ['left', 'attachments', 'id', 'fid']]
				]);
			if(empty($file)) {
				header('HTTP/1.1 404 Not Found');
				echo '{"error":"Requested file not found"}';
				return;
			}
			
			
			require_once AT_ROOT.'pages/includes/finfo-compress.php';
			require_once AT_ROOT.'pages/includes/attach-info.php';
			$attachfile_ids = [];
			proc_file_unpack_collect_attach($file, $attachfile_ids);
			$attachment_files = proc_file_get_attachfiles($attachfile_ids);
			
			$file['fileinfo'] = $AT->db->selectGetAll('fileinfo', 'type', 'fid='.$id, 'type,info');
			$ret = fmt_file($file, $attachment_files);
			$ret['torrent_id'] = (int)$file['toto_id'];
			echo json_encode($ret, JSON_UNESCAPED_SLASHES);
		return;
		// animes, episodes
	}
}

if($AT->input->subaction == 'rss2' || $AT->input->subaction == 'api')
	header('Content-Type: application/rss+xml; charset=utf-8');
elseif($AT->input->subaction == 'atom')
	header('Content-Type: application/atom+xml; charset=utf-8');
elseif($AT->input->subaction == 'json') {
	//header('Content-Type: application/json; charset=utf-8'); // sent above
}
else $AT->output->error('Unsupported feed type.', 'Invalid feed type', AT_Output::ERROR_NOTFOUND);


$home_url = AT::buildUrl('home');

// TODO: comment feeds?

if($emptyList)
	$items = [];
else {
	//$disp_qwhere = 'ulcomplete=1';
	require AT_ROOT.'pages/includes/listing.php';
	if(count($items) > $perpage) {
		array_pop($items);
	}
}

$lastbuild = TIME_NOW;
if(!empty($items)) {
	$lastbuild = reset($items);
	$lastbuild = $lastbuild['added_date'];
}

// generate feed items
$feeditems = array();
foreach($items as &$item) {
	$viewlink = Toto::viewUrl($item);
	$updatetime = $item['added_date'];
	$sources = array();
	if($item['tosho_id'])
		$sources[] = '<a href="https://www.tokyotosho.info/details.php?id='.$item['tosho_id'].'">TokyoTosho</a>';
	if($item['nyaa_id'])
		$sources[] = nyaa_source_link($item);
	if($item['anidex_id'])
		$sources[] = '<a href="https://anidex.info/?page=torrent&amp;id='.$item['anidex_id'].'">AniDex</a>';
	if($item['nekobt_id'])
		$sources[] = '<a href="https://nekobt.to/torrents/'.$item['nekobt_id'].'">nekoBT</a>';
	$content_size = '<strong>Total Size</strong>: '.$AT->friendlyFileSize($item['totalsize']);
	$content = '<strong>Source(s)</strong>: '.implode(' | ', $sources).'<br/>'.$content_size.'<br/><strong>Download Links</strong>: ';
	$content .= '<a href="'.htmlspecialchars(torrent_link_url($item)).'">Torrent</a>';
	if($item['magnet']) $content .= '/<a href="'.htmlspecialchars(magnet_link_url($item)).'">Magnet</a>';
	if($item['stored_nzb']) $content .= ', <a href="'.htmlspecialchars(nzb_link_url($item)).'">NZB</a>';
	if(isset($item['links']) && ($links = filelinks_decode($item['links']))) {
		$multipart_links = '';
		foreach($links as $site => $parts) {
			// explicit substr is a workaround for bug, e.g. in fid=652091
			if(substr($site, 0, 1) == '!' /*encrypted link*/ || strpos($site, '|')) continue;
			$is_multipart = (count($parts) > 1);
			$multipart_comma = '<br/>'.htmlspecialchars($site).': ';
			foreach($parts as $part => $ln) {
				if(@$ln['st']) continue; // skip bad links
				if(!($linkurl = Toto::getFileLink($ln['url']))) continue;
				if($is_multipart) {
					$multipart_links .= $multipart_comma;
					$multipart_links .= '<a href="'.htmlspecialchars($linkurl).'">Part'.($part+1).'</a>';
					$multipart_comma = ', ';
				} else {
					$content .= ' | <a href="'.htmlspecialchars($linkurl).'">'.htmlspecialchars($site).'</a>';
				}
				//if($ln['resolvedate'] > $updatetime) $updatetime = $ln['resolvedate'];
			}
		}
		$content .= $multipart_links;
	} else {
		$content .= ' | <a href="'.$viewlink.'">'.$item['torrentfiles'].' file(s)</a>';
	}
	
	if($item['tosho_id']) {
		$source = 'https://www.tokyotosho.info/details.php?id='.$item['tosho_id'];
		$source_name = 'TokyoTosho';
	} elseif($item['nyaa_id']) {
		$source = 'https://'.($item['nyaa_subdom']?$item['nyaa_subdom'].'.':'').'nyaa.si/view/'.$item['nyaa_id'];
		$source_name = 'Nyaa';
	} elseif($item['anidex_id']) {
		$source = 'https://anidex.info/?page=torrent&amp;id='.$item['anidex_id'];
		$source_name = 'AniDex';
	} elseif($item['nekobt_id']) {
		$source = 'https://nekobt.to/torrents/'.$item['nekobt_id'];
		$source_name = 'nekoBT';
	} else {
		$source = $item['link'];
		$source_name = 'Source';
	}
	
	$feeditems[] = array(
		'title' => $item['name'],
		'link' => $viewlink,
		'permalink' => AT::buildUrl('view', 'a'.$item['id']), //Toto::viewUrl($item, array(), true),
		'source' => $source,
		'source_name' => $source_name,
		'published' => (int)$item['dateline'],
		'updated' => (int)$updatetime,
		'content' => ($AT->input->only_nzb || $AT->input->only_tor ? $content_size : $content),
		'tosho_id' => (int)$item['tosho_id'],
		'nyaa_id' => (int)$item['nyaa_id'],
		'anidex_id' => (int)$item['anidex_id'],
		'nekobt_id' => (int)$item['nekobt_id'],
		'nyaa_subdom' => $item['nyaa_subdom'],
		'download' => torrent_link_url($item),
		'nzb_download' => nzb_link_url($item, ''),
		'size' => (double)$item['totalsize'],
		'info_hash' => bin2hex_coalesce($item, 'btih'),
		'torrentfiles' => (int)$item['torrentfiles'],
		'cat' => $item['cat'],
		'magnet' => magnet_link_url($item),
		'seeders' => isset($item['complete']) ? (int)$item['complete'] : null,
		'leechers' => isset($item['incomplete']) ? (int)$item['incomplete'] : null,
		'tor_downloads' => isset($item['downloaded']) ? (int)$item['downloaded'] : null,
		'tor_scraped' => isset($item['scraped']) ? (int)$item['scraped'] : null,
		
		// copy for req='json'
		'id' => (int)$item['id'],
		'aid' => (int)$item['aid'],
		'eid' => (int)$item['eid'],
		'fid' => (int)$item['fid'],
		'srcurl' => $item['srcurl'],
		'srctitle' => $item['srctitle'],
		'website' => $item['website'],
		'torrentname' => $item['torrentname'],
		'info_hash_v2' => bin2hex_coalesce($item, 'btih_sha256'),
		'status' => @$ulcomplete_map[$item['ulcomplete']] ?: 'unknown',
		
		// these are only selected for req='api'
		'tvdbid' => isset($item['tvdbid']) ? [$item['tvdbid'], $item['defaulttvdbseason']] : null,
		'imdbid' => isset($item['imdbids']) && !strpos($item['imdbids'], ',') ? $item['imdbids'] : null,
		'aniaired' => isset($item['aniaired']) && $item['aniaired'] ? $item['aniaired'] : null,
		'epaired' => isset($item['epaired']) && $item['epaired'] ? $item['epaired'] : null,
		// TODO: AniDB -> TVDB episode mapping
		'epno' => isset($item['epno']) ? $item['epno'] : null,
		'anipicurl' => $item['anipicurl'] ?? null,
	);
}


header('Expires: '.gmdate('D, d M Y H:i:s', max($lastbuild >= TIME_NOW ? 0 : $lastbuild+300, TIME_NOW+120)).' GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastbuild).' GMT');
header('Cache-Control: public');


if($AT->input->subaction == 'json') {
	echo json_encode(array_map(function($item) {
		return [
			'id' => $item['id'],
			'title' => $item['title'],
			'link' => $item['link'],
			'timestamp' => $item['published'],
			'status' => $item['status'],
			//'added_timestamp' => $item['updated'],
			'tosho_id' => $item['tosho_id'] ?: null,
			'nyaa_id' => $item['nyaa_id'] ?: null,
			'nyaa_subdom' => $item['nyaa_subdom'] ?: null,
			'anidex_id' => $item['anidex_id'] ?: null,
			'nekobt_id' => $item['nekobt_id'] ?: null,
			
			'torrent_url' => $item['download'],
			'torrent_name' => $item['torrentname'],
			'info_hash' => $item['info_hash'],
			'info_hash_v2' => $item['info_hash_v2'] ?: null,
			'magnet_uri' => $item['magnet'],
			'seeders' => $item['seeders'],
			'leechers' => $item['leechers'],
			'torrent_downloaded_count' => $item['tor_downloads'],
			'tracker_updated' => $item['tor_scraped'],
			
			'nzb_url' => $item['nzb_download'] ?: null,
			
			'total_size' => $item['size'],
			'num_files' => $item['torrentfiles'],
			
			//'tvdb_id' => $item['tvdbid'],
			//'imdb_id' => $item['imdbid'],
			'anidb_aid' => $item['aid'] ?: null,
			'anidb_eid' => $item['eid'] ?: null,
			'anidb_fid' => $item['fid'] ?: null,
			
			'article_url' => $item['srcurl'] ?: null,
			'article_title' => $item['srcurl'] ? $item['srctitle'] : null,
			
			'website_url' => $item['website'] ?: null,
		];
	}, $feeditems), JSON_UNESCAPED_SLASHES);
} else {

	// minify XML output
	ob_start(function($data) {
		return strtr($data, array(
			"\t" => '',
			"\r" => '',
			"\n" => '',
		));
	});

	echo '<?xml version="1.0" encoding="utf-8"?>';
?>

<?php if($AT->input->subaction == 'rss2' || $AT->input->subaction == 'api') { ?>

<?php if($AT->input->subaction == 'rss2') { /* xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" */ ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<?php } else { ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:newznab="http://www.newznab.com/DTD/2010/feeds/attributes/" xmlns:torznab="http://torznab.com/schemas/2015/feed">
<?php } ?>
	<channel>
		<atom:link href="<?php echo AT::buildUrl('feed', $AT->input->subaction); ?>" rel="self" type="application/rss+xml"/>
		<title><?php echo htmlspecialchars(SITE_NAME); ?></title>
		<link><?php echo $home_url; ?></link>
		<description>Latest releases feed</description>
		
		<language>en-gb</language>
		<ttl>30</ttl>
		<lastBuildDate><?php echo gmdate('D, d M Y H:i:s O', $lastbuild); ?></lastBuildDate>
		
		<?php if($AT->input->subaction == 'api') {
			echo '<newznab:response offset="'.$offset.'"'.(isset($items_count) ? ' total="'.$items_count.'"' : '').'/>';
			echo '<newznab:apilimits apiCurrent="0" apimax="1" apioldesttime="'.gmdate('D, d M Y H:i:s O', TIME_NOW-86400+10).'" apiNextAvailable="'.gmdate('D, d M Y H:i:s O', TIME_NOW+10).'"/>';
		} ?>
		
	<?php foreach($feeditems as &$item) { ?>
		<item>
			<title><?php echo htmlspecialchars($item['title']); ?></title>
			<description><![CDATA[<?php echo $item['content']; ?>]]></description>
			<link><?php echo $item['link']; ?></link>
			<comments><?php echo $item['link']; ?></comments>
			<?php if(!$AT->input->only_nzb)
				echo '<enclosure url="'.(($AT->input->subaction == 'rss2') ? preg_replace('~^https\://~', 'http://', htmlspecialchars($item['download'])) : htmlspecialchars($item['download'])).'" type="application/x-bittorrent" length="0"/>';
			?>
			<?php if($item['nzb_download'] && !$AT->input->only_tor)
				echo '<enclosure url="', ($AT->input->subaction == 'rss2') ? preg_replace('~^https\://~', 'http://', htmlspecialchars($item['nzb_download'])) : htmlspecialchars($item['nzb_download']), '" type="application/x-nzb" length="0"/>', "\r\n";
			?>
			<source url="<?php echo $item['source']; ?>"><?php echo $item['source_name']; ?></source>
			<pubDate><?php echo gmdate('D, d M Y H:i:s O', $item['updated']); ?></pubDate>
			<guid isPermaLink="true"><?php echo $item['permalink']; ?></guid>
<?php if($AT->input->subaction == 'api') { ?>
			<category>Anime</category>
			
			<newznab:attr name="category" value="5070"/>
			<newznab:attr name="category" value="2020"/>
			<torznab:attr name="category" value="5070"/>
			<torznab:attr name="category" value="2020"/>
			<newznab:attr name="files" value="<?=$item['torrentfiles']?>"/>
			<torznab:attr name="files" value="<?=$item['torrentfiles']?>"/>
			<newznab:attr name="size" value="<?=$item['size']?>"/>
			<torznab:attr name="size" value="<?=$item['size']?>"/>
			
			<?php if(isset($item['tvdbid'])) { ?>
			<torznab:attr name="tvdbid" value="<?=$item['tvdbid'][0]?>"/>
			<?php if(isset($item['tvdbid'][1])) { ?>
			<newznab:attr name="season" value="<?=$item['tvdbid'][1]?>"/>
			<?php }} ?>
			<?php if(isset($item['imdbid'])) { ?>
			<newznab:attr name="imdb" value="<?=$item['imdbid']?>"/>
			<torznab:attr name="imdb" value="<?=$item['imdbid']?>"/>
			<?php } ?>
			<?php if(isset($item['aniaired'])) { ?>
			<newznab:attr name="year" value="<?=gmdate('Y', $item['aniaired'])?>"/>
			<?php } ?>
			<?php if(isset($item['epaired'])) { ?>
			<newznab:attr name="tvairdate" value="<?=gmdate('r', $item['epaired'])?>"/>
			<?php } ?>
			<?php if(isset($item['epno'])) { ?>
			<newznab:attr name="episode" value="<?=$item['epno']?>"/>
			<?php } ?>
			<?php if(isset($item['anipicurl'])) { ?>
			<newznab:attr name="coverurl" value="<?=STORAGE_URL.'adbpics/main/'.htmlspecialchars($item['anipicurl'])?>"/>
			<torznab:attr name="bannerurl" value="<?=STORAGE_URL.'adbpics/main/'.htmlspecialchars($item['anipicurl'])?>"/>
			<?php } ?>

			
			<torznab:attr name="infohash" value="<?=$item['info_hash']?>"/>
			<torznab:attr name="magneturl" value="<?=htmlspecialchars($item['magnet'])?>"/>
			<?php if(isset($item['seeders'])) { ?>
			<torznab:attr name="seeders" value="<?=$item['seeders']?>"/>
			<torznab:attr name="leechers" value="<?=$item['leechers']?>"/>
			<torznab:attr name="peers" value="<?=$item['seeders']+$item['leechers']?>"/>
			<?php } ?>
<?php } ?>
		</item>
	<?php } ?>
		
	</channel>
</rss>

<?php } elseif($AT->input->subaction == 'atom') { ?>

<feed xmlns="http://www.w3.org/2005/Atom">
	<title><?php echo htmlspecialchars(SITE_NAME); ?></title>
	<subtitle>Latest releases feed</subtitle>
	<link href="<?php echo AT::buildUrl('feed', 'atom'); ?>" rel="self" type="application/atom+xml"/>
	<link href="<?php echo AT::buildUrl('feed', 'rss2'); ?>" rel="alternate" type="application/rss+xml"/>
	<link href="<?php echo $home_url; ?>" rel="alternate" type="text/html"/>
	<id><?php echo $home_url; ?></id>
	<updated><?php echo gmdate('Y-m-d\TH:i:s\Z', $lastbuild); ?></updated>
	
	
<?php foreach($feeditems as &$item) { ?>
	<entry>
		<title><?php echo htmlspecialchars($item['title']); ?></title>
		<content type="html"><![CDATA[<?php echo $item['content']; ?>]]></content>
		<link href="<?php echo $item['link']; ?>" rel="alternate" type="text/html"/>
		<?php if(!$AT->input->only_nzb)
			echo '<link href="'.htmlspecialchars($item['download']).'" rel="enclosure" type="application/x-bittorrent"/>';
		?>
		<?php if($item['nzb_download'] && !$AT->input->only_tor)
			echo '<link href="', htmlspecialchars($item['nzb_download']), '" rel="enclosure" type="application/x-nzb"/>', "\r\n";
		?>
		<link href="<?php echo $item['source']; ?>" rel="via" type="text/html"/>
		<updated><?php echo gmdate('Y-m-d\TH:i:s\Z', $item['updated']); ?></updated>
		<published><?php echo gmdate('Y-m-d\TH:i:s\Z', $item['published']); ?></published>
		<id><?php echo $item['permalink']; ?></id>
		<author><name>?</name></author>
	</entry>
<?php } ?>
	
	
</feed>


<?php }
}

