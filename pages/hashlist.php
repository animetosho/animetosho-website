<?php
if(!defined('AT_ROOT')) exit;


$sa = $AT->input->get(AT_Input::TYPE_STR, 'subaction');
list($id, $type) = explode('.', $sa);
$id = (int)$id;

if(!$id || !in_array($type, ['sfv', 'md5', 'sha1', 'sha256']))
	$AT->output->error('Invalid id or type supplied.', 'Invalid request', AT_Output::ERROR_NOTFOUND);


$hashType = $type == 'sfv' ? 'crc32' : $type;

// get files associated with torrent
$files = $AT->db->selectGetAll('files', 'id', '`toto_id`='.$id.' AND '.$hashType.' IS NOT NULL', 'id,`filename`,'.$hashType.' AS hash', array('order' => 'filename'));
if(empty($files))
	$AT->output->error('No files found.', 'Not found', AT_Output::ERROR_NOTFOUND);

header('Content-Type: text/x-'.$type.'; charset=utf-8');
$tname = $AT->db->selectGetField('toto', 'name', 'id='.$id);
header('Content-Disposition: attachment; filename*=UTF-8\'\''.rawurlencode($tname).'.'.$type);

foreach($files as &$file) {
	$hash = bin2hex($file['hash']);
	if($type == 'sfv')
		echo $file['filename'], '  ', $hash, "\r\n";
	else
		echo $hash, '  ', $file['filename'], "\r\n";
}

?>