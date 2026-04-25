<?php
if(!defined('AT_ROOT')) exit;


$sa = strtolower($AT->input->get(AT_Input::TYPE_STR, 'subaction'));
if($p=strpos($sa, '.'))
	$eid = substr($sa, $p+1);
elseif(is_numeric($sa))
	$eid = $sa;
elseif($sa == 'unsorted')
	$eid = 0;
else
	$AT->output->error('Invalid eid supplied.', 'Invalid episode', AT_Output::ERROR_BADREQ);
$navlinks = [];
if($eid == 'unsorted' || $eid == '0') {
	// grab aid
	$aid = (int)substr($sa, 0, $p);
	if($aid)
		$series = $AT->db->selectGetArray('anidb.animetitle', 'type=1 AND `aid`='.$aid, 'aid,name');
	if(empty($series)) {
		//$AT->output->error('Invalid eid supplied.', 'Invalid episode', AT_Output::ERROR_NOTFOUND);
		$episode = array(
			'id' => 0,
			'type' => 1, // normal
			'epno' => '',
			'name' => '',
			'aid' => 0,
			'series_title' => 'Unsorted Files',
		);
		$subaction = 'unsorted';
	} else {
		// make dummy series
		$episode = array(
			'id' => 0,
			'type' => 1, // normal
			'epno' => '',
			'name' => 'Unsorted Files',
			'aid' => $series['aid'],
			'series_title' => $series['name'],
		);
		$subaction = $series['aid'].'.unsorted';
	}
} else {
	unset($sa);
	$eid = (int)$eid;
	if(!$eid)
		$AT->output->error('Invalid eid supplied.', 'Invalid episode', AT_Output::ERROR_BADREQ);
	$episode = $AT->db->selectGetArray('anidb.ep', '`anidb.ep`.`id`='.$eid, '`anidb.ep`.id,epno,`anidb.ep`.type,`anidb.ep`.name,`anidb.ep`.aid,`anidb.ep`.aired,animetitle.name AS series_title', array('joins' => array(
		'LEFT JOIN anidb.animetitle ON `anidb.ep`.aid = anidb.animetitle.aid AND anidb.animetitle.type=1',
	)));
	if(empty($episode))
		$AT->output->error('Invalid eid supplied.', 'Invalid episode', AT_Output::ERROR_NOTFOUND);
	unset($eid);
	
	
	$satitle = $episode['series_title'];
	if($episode['epno']) {
		$episode['ep'] = format_episode($episode, 'num');
		$satitle .= ' '.$episode['ep'];
		
		// search for related episodes
		// note: 'name' excluded to not show episode title
		$releps = $AT->db->selectGetAll('anidb.ep', 'id', 'aid = '.$episode['aid'].' AND type = '.$episode['type'].' AND epno IN ('.($episode['epno']-1).','.($episode['epno']+1).')', 'id,epno,aired,type', ['order' => 'aired DESC, epno DESC, id DESC']);
		// TODO: if missing next/prev, and is regular episode type, search for sequels/prequels?
		// - also search for next/prev aired, to cover specials
		foreach($releps as $relep) {
			$relep['aid'] = $episode['aid'];
			$retitle = $episode['series_title'];
			$relep['series_title'] = $retitle;
			
			$linktext = htmlspecialchars(format_episode($relep, 'long'));
			if($relep['aired'] && $episode['aired'])
				$re_is_newer = $relep['aired'] > $episode['aired'];
			elseif($relep['epno'])
				$re_is_newer = (int)$relep['epno'] > (int)$episode['epno'];
			else
				$re_is_newer = $relep['id'] > $episode['id'];
			if($re_is_newer)
				$linktext = '&lt; '.$linktext;
			else
				$linktext .= ' &gt;';
			if($relep['epno']) {
				$retitle .= ' '.format_episode($relep, 'num');
			}
			$navlinks[] = '<a href="'.AT::buildUrl('episode', AT::seoPageSubaction($relep['id'], $retitle)).'">'.$linktext.'</a>';
		}
	}
	$subaction = AT::seoPageSubaction($episode['id'], $satitle);
}

$self_url = AT::buildUrl('episode', $subaction, array(), false);

if(AT::testSeoUrl($self_url)) return;


$eptitle = format_episode($episode, 'long') ?: null;

filtered_noindex($episode['series_title']);


$bc = array();
if($episode['aid'])
	$bc[AT::buildUrl('series', AT::seoPageSubaction($episode['aid'], $episode['series_title']))] = htmlspecialchars($episode['series_title']);

$AT->output->title = $episode['series_title'];
if($episode['epno'])
	$AT->output->title .= ' - '.$episode['ep'];
elseif($episode['name'])
	$AT->output->title .= ' - '.$episode['name'];
$AT->output->title = htmlspecialchars($AT->output->title);

if(isset($eptitle)) {
	$pagetitle = $eptitle;
}
else {
	$pagetitle = $episode['series_title'];
	if($episode['id']) $bc = array(); // prevent double breadcrumb item
}
$AT->output->head_metas['description'] = $episode['series_title'].(isset($eptitle) ? ' - '.$eptitle : '');


$page = $AT->input->get(AT_Input::TYPE_INT, 'page');
if($page < 1) $page = 1;
$AT->output->head_extra_html = '<link rel="canonical" href="'.AT::buildUrl('episode', $episode['id'], array('page' => $page)).'" />';


// display stuffs
if(!$episode['id'] && $episode['aid']) {
	$disp_qwhere = 'aid='.$episode['aid'].' AND eid='.$episode['id'];
	$disp_feedargs = ['aid' => $episode['aid'], 'eid' => $episode['id']];
} else {
	$disp_qwhere = 'eid='.$episode['id'];
	$disp_feedargs = ['eid' => $episode['id']];
}
if(!$episode['id'] && !$episode['aid'])
	$disp_cat_type = 'series';


$disp_urlargs['eids'] = $episode['id'];
require AT_ROOT.'pages/includes/listing.php';

$AT->output->printHeader(900);
$AT->output->printTitleAndDesc(htmlspecialchars($pagetitle), '', $bc);
if(!empty($navlinks)) {
	echo '<div class="home_list_pagination">', implode(' | ', $navlinks), '</div>';
}
include AT_ROOT.'pages/includes/displist.php';

$GLOBALS['aid'] =& $episode['aid'];
$AT->output->printFooter();

