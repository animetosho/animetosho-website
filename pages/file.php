<?php
if(!defined('AT_ROOT')) exit;



$sa = $AT->input->get(AT_Input::TYPE_STR, 'subaction');
if($p=strpos($sa, '.'))
	$fid = intval(substr($sa, $p+1));
elseif(is_numeric($sa))
	$fid = intval($sa);
unset($sa);
if(!isset($fid) || !$fid)
	$AT->output->error('Invalid fid supplied.', 'Invalid file', AT_Output::ERROR_BADREQ);
$file = $AT->db->selectGetArray('files', '`files`.`id`='.$fid, 'files.*, toto.aid, toto.eid, toto.name, toto.deleted, toto.tosho_id, toto.nyaa_id,toto.nyaa_subdom,toto.anidex_id,toto.nekobt_id, links, anidb.animetitle.name AS animename', array('joins' => array(
	array('left', 'toto', 'toto_id', 'id'),
	array('left', 'filelinks', 'id', 'fid'),
	'LEFT JOIN anidb.animetitle ON anidb.animetitle.aid=toto.aid AND anidb.animetitle.type=1'
)));
if(empty($file))
	$AT->output->error('Invalid fid supplied.', 'Invalid file', AT_Output::ERROR_NOTFOUND);
unset($fid);

if($file['aid'] && !$AT->user->isMember()) {
	include AT_ROOT.'pages/includes/crfilter.php';
	if(!empty($blocked_aids) && in_array($file['aid'], $blocked_aids))
		$AT->output->error('Invalid fid supplied.', 'Invalid file', AT_Output::ERROR_NOTFOUND);
}

if($file['deleted'])
	$AT->output->head_metas['robots'] = 'noindex';
else {
	filtered_noindex($file['name']);
	filtered_noindex($file['animename']);
}


$title_html = htmlspecialchars($file['filename']);
$AT->output->title = $title_html;

$subaction = AT::seoPageSubaction($file['id'], $file['filename']);
$self_url = AT::buildUrl('file', $subaction, array(), false);

if(AT::testSeoUrl($self_url)) return;


$AT->output->head_metas['description'] = $file['filename'];
$AT->output->head_extra_html = '<link rel="canonical" href="'.AT::buildUrl('file', $file['id']).'" />';
$AT->output->printHeader(60);

require AT_ROOT.'pages/includes/view_file_common.php';
$bc = buildViewBreadcrumb($file['aid'], $file['eid'], $file['animename']);
$bc[Toto::viewUrl($file)] = htmlspecialchars($file['name']);
$AT->output->printTitleAndDesc($title_html, '', $bc);

// output file info
?>

<br />
<?php if($file['deleted'])
	echo '<div class="usernotice usernotice_alert">This file has been deleted.</div>';
?>
<table style="width: 100%;" id="infotable">
	<tr><th style="width: 9em;">File Size</th><td><?php echo $AT->friendlyFileSize($file['filesize']), ' (', $AT->user->fmtNum($file['filesize']), ' byte', ($file['filesize']==1?'':'s'), ')'; ?></td></tr>
	<?php
	printAllFileLinksTable($file);
	?>
	<?php
	$hashes = '';
	$hashfunc = function($hash) use(&$file, &$hashes) {
		if(isset($file[$hash])) $hashes .= ($hashes?', ':'') . '<strong>'.strtoupper($hash).'</strong>: '.strtoupper(bin2hex($file[$hash]));
	};
	$hashfunc('crc32');
	$hashfunc('md5');
	$hashfunc('sha1');
	$hashfunc('sha256');
	//$hashfunc('tth');
	if(isset($file['ed2k'])) {
		$ed2k = bin2hex($file['ed2k']);
		$hashes .= ($hashes?', ':'') . '<strong>ED2K</strong>: <a href="ed2k://|file|'.rawurlencode($file['filename']).'|'.$file['filesize'].'|'.$ed2k.'|/">'.strtoupper($ed2k).'</a>';
	}
	if($hashes) echo '<tr><th>Hashes</th><td>', $hashes, '</td></tr>';
	?>
	<?php if($file['filethumbs'] || $file['filestore'] || ($file['vidframes'] || $file['vidframes'] === '0'))
		printScreenshots($file);
	?>
	<?php printExtractions($file); ?>
	<?php
	$finfo1 = getFileInfo($file['id']);
	if($finfo1[1]) { ?>
	<tr><th>Additional Info <script type="text/javascript">
		if(navigator.clipboard)
			document.write('<a title="Copy text" href="#" onclick="navigator.clipboard.writeText(document.getElementById(\'file_addinfo\').innerText);return false">&#x1f4cb;</a>');
	</script></th><td><div style="font-family: consolas, courier new, monospace; word-wrap: break-word;" id="file_addinfo"><?php
		echo nl2br(strtr(htmlspecialchars($finfo1[1]), array('  '=>'&nbsp;&nbsp;', "\t" => str_repeat('&nbsp;', 8))));
	?></div></td></tr>
	<?php } ?>
	<?php if($AT->input->get(AT_Input::TYPE_BOOL, 'more_info')) {
		$finfo2 = getFileInfo($file['id'], 'mkvinfo,7z-slt,mp4boxinfo');
		if($finfo2[1]) {
		?>
	<tr><th>Additional Info (2) <script type="text/javascript">
		if(navigator.clipboard)
			document.write('<a title="Copy text" href="#" onclick="navigator.clipboard.writeText(document.getElementById(\'file_addinfo2\').innerText);return false">&#x1f4cb;</a>');
	</script></th><td><div style="font-family: consolas, courier new, monospace; word-wrap: break-word;" id="file_addinfo2"><?php
		echo nl2br(strtr(htmlspecialchars($finfo2[1]), array('  '=>'&nbsp;&nbsp;', "\t" => str_repeat('&nbsp;', 8))));
	?></div></td></tr>
	<?php }} ?>
</table>

<?php
$files = $AT->db->selectGetAll('files', 'id', '`toto_id`='.$file['toto_id'], '`id`,`filename`,`filesize`', array('order' => 'filename'));
if(count($files) > 1) {
	?>
	<br />
	<table style="width: 100%;">
	<thead><tr><th>Other Files in this Torrent</th></tr></thead>
	<tbody><tr><td style="padding-left: 1em;">
	<?php
	foreach($files as $other_file) {
		if($other_file['id'] == $file['id'])
			echo '<div><strong>', htmlspecialchars($file['filename']), '</strong></div>';
		else
			echo '<div><a href="', AT::buildUrl('file', AT::seoPageSubaction($other_file['id'], $other_file['filename'])), '">', htmlspecialchars($other_file['filename']), '</a></div>';
	}
	?>
	</td></tr></tbody>
	</table>
	<?php
}
unset($files);

$AT->output->printFooter();


?>