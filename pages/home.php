<?php
if(!defined('AT_ROOT')) exit;

//$listing_disable_sorting = true;
$disp_cat_type = 'series';
require AT_ROOT.'pages/includes/listing.php';

$AT->output->title = SITE_NAME;
//$AT->output->head_metas['description'] = '';
//$AT->output->head_metas['keywords'] = 'rapidshare,megaupload,despositfiles,hotfile,zshare,mediafire';
//$AT->output->head_extra_html .= '<link rel="canonical" href="'.AT::buildUrl('home', '', array('page' => $AT->input->page)).'" />';

$AT->output->printHeader(60);


/*
<div style="border: solid 1px #00d1e4; background: #ebfdff; padding: 0.5em; margin: 0.5em;">Welcome to <em>', SITE_NAME, '</em>.  This site is currently still in beta, so please bear/bare/rabbit with us whilst we sort out any problems which may, and probably will, arise.  Unlike Google, we don\'t have millions of dollars to throw away at any problems that crop up, but, if you do see any issues yourself, please <a href="'.AT::buildUrl('feedback').'">do tell us</a> so that we can improve your experience here! (no nekomimis will be given)</div>
*/

if(!$AT->input->got('page') && $AT->db::readonly) {
?><div style="text-align: center;">This is a mirror of <a href="https://animetosho.org/">Anime Tosho</a>.</div><?php
}

if(!$AT->input->got('page') && !$AT->db::readonly) {

?>
<div class="usernotice usernotice_alert">
<strong>Updates Frozen</strong> [2026-05-09]
<p>As per <a href="<?=AT::buildUrl('about','shutdown2')?>">previous notice</a>, all updates to Anime Tosho have now been permanently stopped. In other words, no new content will be added, and no data updates will be made.</p>

<p>Whilst this website will likely stay around &quot;frozen&quot; for a few months (at least until October), this marks the beginning of the end of the Anime Tosho project, and I expect that this is where most of you part ways.</p>

<p>I'd like to take the opportunity to shout out the incredible contributions many people in this general community have made, which has been a key source of my motivation here. A big thanks to uploaders, upstream websites, helpful commenters, developers of tools I've used and countless others who help make this community work and keep it running - Anime Tosho is built upon the work of many, and it wouldn't be possible without so many of you.</p>

<p>A few projects have started very recently looking to continue aspects of Anime Tosho: <a href="https://amenzb.moe/">ameNZB</a>, <a href="https://aninzb.moe/">aninzb</a> and <a href="https://animetosho.xyz/">Anime Tosho NEW</a> (if there's any more, <a href="<?=AT::buildUrl('feedback')?>">let us know!</a>). As these are relatively new, they may be rough around the edges for now, but might serve what you're looking for and hopefully eventually surpass Anime Tosho. Send your encouragement to the developers! I hope that they stand the test of time and achieve the goals the owners seek to accomplish.
<br/>You can also find a list of user suggested alternatives in the <a href="<?=AT::buildUrl('about','shutdown2')?>">previous notice</a>.</p>

<p>Regardless of however you continue your journey, I wish you all the best.</p>
</div>
<div class="usernotice usernotice_success">
Anime Tosho <a href="<?=AT::buildUrl('about','data')?>">data dump is now available</a>. If you want it, get it quick as it may not last long!
</div>
<div style="text-align: center; margin-top: -0.5em; margin-bottom: 2em; font-size: smaller">[<a href="<?=AT::buildUrl('about', 'news')?>">news archive</a>] 
[<a href="<?=AT::buildUrl('comments')?>">latest comments</a>]
</div>
<?php

} // if(!$AT->input->got('page') && !$AT->db::readonly)

// display stuffs
include AT_ROOT.'pages/includes/displist.php';

$AT->output->printFooter();

?>
