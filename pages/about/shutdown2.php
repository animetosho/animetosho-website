<?php
if(!defined('AT_ROOT')) exit;

$AT->output->title = 'Anime Tosho Shutdown Update';
$AT->output->printHeader(86400);


$AT->output->printTitleAndDesc('Anime Tosho Shutdown Update');
?>

<p>This is an update to the <a href="<?=AT::buildUrl('about', 'shutdown')?>">previous shutdown announcement</a> with more details.</p>

<h3>Actions</h3>
<ul>
<li>Ingestion of new torrents will be halted around 2026-05-09 00:00:00 GMT</li>
<li>Existing torrents will be given several hours (up to around 24h) to complete processing - incomplete torrents will be marked as broken</li>
<li>Around the 10th of May (after all processing of existing torrents is done), further updates will cease and a database export of the updates server (MariaDB SQL script) will be created, as well as a torrent containing all screenshots, attachments, NZBs and torrent files. These will be made available with an announcement.
	<ul>
	<li>The updates server will seed this torrent until the host terminates the lease, which is expected to be around 2026-05-18</li>
	<li>The torrent will be a copy of the data as it is stored on the server, that is, all files are numbered and unidentifiable without looking up the accompanying database dump. All screenshots are in MKV format with accompanying WebP subtitles, attachments are all compressed .xz files, and NZBs are gzip'd.</li>
	<li>Data mirrored from AniDB will not be provided</li>
	</ul>
</li>
<li>This website will otherwise remain unaffected and continue operating without new torrents or updates, effectively operating as an archive</li>
<li>The feed/storage server is currently set to expire in October, and I'll be monitoring traffic to determine if it stays around for any longer</li>
<li>I'm planning to terminate this website in May 2027. Note that the animetosho.org domain expires mid 2029, which will fail to resolve after website termination</li>
<li>I'll post a notice when I decide on what to do regarding the website and feed/storage server</li>
</ul>

<h3>Code and Data</h3>
<p>As mentioned above, all processed data (minus AniDB) will be released after updates stop, likely on 2026-05-10.</p>
<p>The code to all AT operations is now <a href="https://github.com/animetosho/animetosho-setup#list-of-repositories">available across several repositories</a> (as long as it isn't taken down).
<br />For aspiring individuals, there's also a <a href="https://github.com/animetosho/animetosho-setup/blob/master/guide.md">setup guide</a> to help with setting up your own version of Anime Tosho. Despite this guide, I expect AT to be quite challenging to set up and manage, as much of it was designed only for my use.
<br />For those hoping to see this code release allow for a continuation of AT's operations, note that whilst technically possible, it's far from straightforward even for someone highly competent.
<br />(nevertheless, if anyone does try, I salute you, and best of luck!)</p>

<h3>Alternatives/Resources</h3>
<p>Possible alternatives to some aspects of AT, as well as helpful resources, have been posted in the <a href="<?=AT::buildUrl('feedback')?>">feedback section</a>. I haven't investigated any of these much, I just list them in case you find any useful:</p>
<ul>
<li><a href="https://scenenzbs.com/">SceneNZBs</a> (<a href="https://animetosho.org/feedback?page=483#comment22132">comment</a>) - they've reached out to let me know they'll ingest AT's NZB dump, and have already set up Nyaa/TokyoTosho/nekoBT torrent to Usenet mirroring</li>
<li><a href="https://amenzb.moe/">ameNZB</a> (<a href="https://animetosho.org/feedback?page=483#comment22136">comment</a>) - community-driven torrent to Usenet mirroring index</li>
<li><a href="https://tokyoinsider.com/">Tokyo Insider</a> (<a href="https://animetosho.org/feedback?page=484#comment22192">comment</a>) - provides anime DDLs (site down at time of writing)</li>
<li><a href="https://animetoki.com/">Anime Toki</a> (<a href="https://animetosho.org/feedback?page=484#comment22223">comment</a>) - anime DDL site, doesn't appear to be a mirror like AT</li>
<li><a href="https://kitsunekko.net/">kitsuneko.net</a> - anime English/Japanese subtitle repository</li>
<li><a href="https://everythingmoe.com/">EverythingMoe</a> (<a href="https://animetosho.org/feedback?page=480#comment22033">comment</a>) - anime website index</li>
</ul>
<p>If you know of any other alternatives or relevant resources, I'm happy to spotlight them</p>

<h3>Received Feedback</h3>
<p>The number of <a href="<?=AT::buildUrl('feedback')?>">appreciative comments I've received</a> over the past few months has been incredible. I never expected to get so many messages, and it's really heart-warming to know how much AT has helped others. Although I've avoided responding to most of these (to not sound like a broken record) know that I'm very grateful to every single one of them - thank you!</p>
