<?php
if(!defined('AT_ROOT')) exit;

$AT->output->title = 'Logistics';
$AT->output->printHeader(86400);


$AT->output->printTitleAndDesc('Logistics of Anime Tosho');
?>

<p>This page provides a high level overview of the specifications, stats and costs with running Anime Tosho, for the curious minded. Details are accurate at time of writing in February 2026.</p>

<h3>Servers and Setup</h3>

<p>Anime Tosho runs on three servers: a processing/updates server, primary webserver and secondary webserver.</p>

<h4>Updates Server</h4>
<p>This server performs all the behind-the-scenes processing and content generation, including torrent downloads, uploads/mirroring content, screenshot extraction etc.</p>

<h5>Stats</h5>
<ul>
<li>Average bandwidth usage in Jan 2026: 239GB in + 1,805GB out per day</li>
<li>Average torrent seed (upload/download) ratio: ~1.3 (target ratio: 1.25)</li>
<li>Database size (on disk): 12.0GB (9.5GB shared + 2.5GB private)</li>
</ul>

<h5>Setup/Specs</h5>
<ul>
<li>Host: <a href="https://oneprovider.com/en/configure/dediconf/44">OneProvider</a> (Online.net)</li>
<li>Type: dedicated/&quot;bare metal&quot; server</li>
<li>CPU: Xeon L3426 (4 cores / 8 threads) [<a href="https://browser.geekbench.com/v5/cpu/14720874">GB5</a>]</li>
<li>RAM: 16GB</li>
<li>Disk: 2x 2TB HDD (in mixed RAID modes across partitions)</li>
<li>Bandwidth: 300Mbps unmetered</li>
<li>OS: Debian amd64</li>
<li>Cost: &euro;196/year</li>
</ul>

<p>Many standard off-the-shelf tools are used during processing, however these are coordinated with completely custom scripts. Examples include:</p>

<ul>
<li>Torrenting: Transmission</li>
<li>Scraping: custom script; CloudScraper used when necessary</li>
<li>Uploading to DDL hosts: custom script</li>
<li>Uploading to Usenet: ParPar, Nyuu</li>
<li>Series/episode tagging: custom script; AniDB used as source</li>
<li>Media file information: mediainfo, ffprobe, mkvmerge-identify</li>
<li>Screenshot handling: ffmpeg, VapourSynth</li>
<li>Subtitle extraction: mkvextract</li>
<li>Compression/archiving: 7zip, xz, cwebp</li>
<li>...and a bunch of other tools I forgot to mention</li>
</ul>

<h4>Primary Webserver</h4>
<p>This server serves the main website (animetosho.org). Updates are received from the updates server via database replication.</p>

<h5>Stats</h5>
<ul>
<li>Average bandwidth usage in Jan 2026: 2.05GB in + 20.55GB out per day</li>
<li>Typical page requests: ~1,020k/day</li>
<li>Website database size (on disk): 207MB private + 7.0GB shared (updates)</li>
</ul>

<h5>Setup/Specs</h5>
<ul>
<li>Host: <a href="https://my.frantech.ca/cart.php?a=add&pid=1423">BuyVM</a></li>
<li>Type: KVM virtual server</li>
<li>CPU: 1 vCore (Ryzen 7950X3D)</li>
<li>RAM: 1GB</li>
<li>Disk: 20GB SSD</li>
<li>Bandwidth: 1Gbps shared</li>
<li>OS: Debian amd64</li>
<li>Cost: US$42/year</li>
</ul>

<p>This server runs a nginx, MariaDB, PHP and Manticoresearch setup to handle the website.</p>

<h4>Secondary Webserver</h4>
<p>This server serves everything the primary webserver doesn't, such as screenshots and RSS feeds. Updates are received from the updates server via database replication and <i>lsync</i> file synchronisation.</p>

<h5>Stats</h5>
<ul>
<li>Average bandwidth usage in Jan 2026: 31.82GB in + 349.5GB out per day</li>
<li>Typical requests: ~12.2m/day (feed.animetosho.org) + ~375k/day (storage.animetosho.org)</li>
<li>Storage files size: 848GB (659GB screenshots [9.3M files] + 146GB attachments [2.8M files] + 24GB NZBs [458K files] + 19GB torrents [502K files])</li>
</ul>

<h5>Setup/Specs</h5>
<ul>
<li>Host: <a href="https://greencloudvps.com/billing/store/storage-kvm-sale/nl10gbpsstorage-2">GreenCloudVPS</a></li>
<li>Type: KVM virtual server</li>
<li>CPU: 4 vCores (EPYC 7643)</li>
<li>RAM: 6GB</li>
<li>Disk: 2TB RAID10 HDD</li>
<li>Bandwidth: 10TB/month @ 10Gbps</li>
<li>OS: Debian amd64</li>
<li>Cost: US$80/year</li>
</ul>

<p>As this server hosts a copy of the main website, it runs a similar setup, but also has some additional scripts for serving screenshots and attachments.</p>


<h3>Costs</h3>
<p>Server costs are listed above, which make up the bulk of the costs of running Anime Tosho. Currently, the only other recurring cost is the domain name registration (roughly US$13/year), which also includes DNS hosting, and Usenet accounts for uploading (cost varies/unpredictable). There have been more costs in the past, such as 1Fichier premium accounts and a server to specifically handle that.</p>
<table align="center">
<thead><th>Item</th><th>Annual Cost</th></thead>
<tbody>
	<tr><td>Updates Server</td><td align="right">&euro;196</td></tr>
	<tr><td>Primary Webserver</td><td align="right">$42</td></tr>
	<tr><td>Secondary Webserver</td><td align="right">$80</td></tr>
	<tr><td>Domain</td><td align="right">$15</td></tr>
	<tr style="font-weight: bold;"><td>Total</td><td align="right">$137 + &euro;196</td></tr>
</tbody>
</table>
<p>All up, costs total around $369.6/year or <b>$30.8/month</b> at time of writing, ignoring fees and forex expenses.</p>
<p>Note that Anime Tosho has no revenue stream, so all costs are paid out-of-pocket by the site admin.</p>
