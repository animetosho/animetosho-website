<?php
if(!defined('AT_ROOT')) exit;

$AT->output->title = 'Anime Tosho Shutdown';
$AT->output->printHeader(86400);


$AT->output->printTitleAndDesc('Anime Tosho Shutdown');
?>

<p>This is advance notice that Anime Tosho will start shutting down <i>permanently</i>, beginning in May 2026.</p>

<p><b>2026-04-27: <a href="<?=AT::buildUrl('about', 'shutdown2')?>">shutdown update</a></b></p>

<h3>Summary</h3>
<ul>
<li>The updates server will be terminated in May 2026. Once this happens, no new torrents will be added or processed.</li>
<li>This website (including feed/API) will continue to be operational for at least several months after May, with the proviso that nothing new will appear. This will allow existing applications to &quot;work&quot; with old data.</li>
<li>Until updates stop, this site will continue operating normally</li>
</ul>

<h3>Why?</h3>
<p>After managing this project for roughly 15 years, I've decided that I want to move on to other things. This was not an easy decision to make and is something I've been considering over the past few years yet reluctant to do. The amount of time and effort I have poured into AT is basically a significant portion of my life now, so throwing it away is still an uncomfortable thought (especially considering the contributions others have made here). Nevertheless, it's something I always knew I had to do at some point.</p>

<p>Anime Tosho has been a lot more successful than I had originally imagined, and I'm proud of what it has achieved. I see that it delivers value to a sizable audience (myself included) and some downstream projects rely on it. Unfortunately I can't shut down this website without it affecting anyone else, so my apologies for disruptions my selfish decision will cause.</p>

<p>I don't intend to leave AT in a semi-unmaintained state (unlike *cough* Nyaa.si), despite the upkeep being relatively low. I also don't intend to hand over control of AT to someone else - I encourage anyone who wishes to manage a service like this to do so under their own name and terms. Thus I believe a shutdown to be the best course of action.</p>

<h3>The Plan</h3>
<p>I'm hoping to minimise the disruption this shutdown will cause with the following plan:</p>

<ul>
<li>For the next three months, this site will continue operating normally</li>
<li>In April, I'll add messaging to all pages on this site regarding this shutdown, and may provide an update to this plan.</li>
<li>The updates server's lease expires some time in May and I don't intend to renew it, hence updates will stop when the server is retired</li>
<li>After updates stop, I plan to make a final copy of the database (MariaDB SQL dump) publicly available. Data on the storage server (screenshots, torrents, NZBs and attachments) will likely be packaged up and made available, though the volume of data might complicate matters (the <a href="https://animetosho.org/about/logistics">logistics page</a> lists current volume under &quot;Storage files size&quot;). You can already <a href="https://storage.animetosho.org/dbexport/">download daily partial database exports here</a>.</li>
<li>This website and storage/feed server will remain active after the updates stop. I don't know how long I'll keep these going for yet, but it'll be at least a few months after May. This means that everything will still &quot;work&quot; for those few months, except that no new content will appear.</li>
<li>In the upcomming months (possibly after updates cease), I <i>hope</i> to release the code to more components of Anime Tosho (some components are <a href="https://github.com/animetosho">already available here</a>). This is probably more for anyone curious than for anyone interested in hosting their own variant of AT, as the code wasn't really designed to be handled by anyone but myself. However I do hope this inspires alternatives to AT show up.</li>
<li>The domain (animetosho.org) expires in 2029. I don't intend to keep the site running anywhere near that long, but it'll be years after shutdown before anyone can claim the address to use for whatever purpose. This also means that I won't be selling this website or domain (this project is a zero revenue venture till the end).</li>
<li>Whilst the website is still up, I'm happy to direct users to reasonable alternative services to AT should they appear and be suggested</li>
</ul>

<h3>Questions?</h3>
<p>Feel free to post any questions on the <a href="/feedback">feedback page</a>.</p>
