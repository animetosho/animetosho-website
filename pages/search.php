<?php
if(!defined('AT_ROOT')) exit;

// seems to be an actor using old User-Agent values doing a lot of non-anime searches
if(!$AT->user->isMember() && (@$_SERVER['HTTP_USER_AGENT'] == 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.75 Safari/537.36' || @$_SERVER['HTTP_USER_AGENT'] == 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36')) {
	$AT->output->error('Too many invalid search requests have been submitted from this source. This block may be bypassed if the search is submitted from a registered account.', 'Request blocked', AT_Output::ERROR_FORBIDDEN);
}

$AT->input->parse(array(
	'subaction' => AT_Input::TYPE_STR,
	'q' => AT_Input::TYPE_STR,
	'page' => AT_Input::TYPE_INT,
	'aid' => AT_Input::TYPE_INT
));
// need to use 'q' variable because of how forms work...
if(!$AT->input->q && $AT->input->subaction)
	$AT->input->q = $AT->input->subaction;
if(!$AT->input->q) {
	$AT->output->error('No search term supplied.', 'Invalid search', AT_Output::ERROR_BADREQ);
}

if(preg_match('~^[a-fA-F0-9]{40}$~', $AT->input->q)) {
	// see if this is a valid BTIH
	$tfile = $AT->db->selectGetArray('toto', '`btih`=X\''.$AT->input->q.'\' AND `toto`.`isdupe`=0');
	if(!empty($tfile)) {
		// redirect
		$turl = unhtmlentities(Toto::viewUrl($tfile));
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: '.$turl);
		return;
	}
}

$bc = array();
if($AT->input->aid) {
	$series = $AT->db->selectGetArray('anidb.animetitle', 'type=1 AND `aid`='.$AT->input->aid, 'aid,name');
	if(empty($series))
		$AT->output->error('Invalid aid supplied.', 'Invalid series', AT_Output::ERROR_NOTFOUND);
	$bc[AT::buildUrl('series', AT::seoPageSubaction($series['aid'], $series['name']))] = htmlspecialchars($series['name']);
}


$AT->output->title = 'Search Results: '.htmlspecialchars($AT->input->q);
if(!empty($series['name']))
	$AT->output->title .= ' &mdash; '.htmlspecialchars($series['name']);
$AT->output->head_metas['description'] = '';
$AT->output->head_metas['keywords'] = '';
$AT->output->head_extra_html = '<link rel="canonical" href="'.AT::buildUrl('search', $AT->input->q, array('page' => $AT->input->page)).'" />';

$disp_cat_type = 'series';
require AT_ROOT.'pages/includes/listing.php';

$AT->output->printHeader(300);
$AT->output->printTitleAndDesc($AT->output->title, '', $bc);

?>
<div style="margin: 0.5em 0.5em;"><form action="<?php echo AT::buildUrl('search'); ?>" method="get">
<input type="text" name="q" class="text" value="<?=htmlspecialchars($AT->input->q ?: 'Search')?>" onfocus="if(this.value=='Search')this.value='';" style="width: 20em; margin-right: 0.5em;" /><input type="submit" value="Search" class="submit" />
<div><label class="check_label" style="margin-left: 0.5em; white-space: normal;"><input type="checkbox" name="qx" value="1"<?=(@$AT->input->qx ? ' checked="checked"':'')?> /> Search using <a href="http://sphinxsearch.com/docs/archives/2.2.10/extended-syntax.html" target="_blank" rel="noopener">Sphinx search expression</a> instead of <a href="<?=AT::buildUrl('about')?>">regular search syntax</a> (special operators: <code>&quot;search phrase&quot; partial* -excluded</code>)</label></div>
<div><label style="margin-left: 0.5em;">Search within series ID #<input type="text" name="aids" class="text" value="<?=htmlspecialchars((@$disp_urlargs['aids'] ?: @$disp_urlargs['aid']) ?: '')?>" style="width: 5em;" /></label> (anime IDs can be obtained from <a href="https://anidb.net/" target="_blank" rel="noopener">AniDB</a>)</div>
</form></div>
<?php
include AT_ROOT.'pages/includes/displist.php';
$GLOBALS['aid'] =& $AT->input->aid;
$AT->output->printFooter();

?>