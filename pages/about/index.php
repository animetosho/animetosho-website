<?php
if(!defined('AT_ROOT')) exit;

$AT->output->title = 'About';
$AT->output->printHeader(86400);


$AT->output->printTitleAndDesc('About / FAQs');

?>

<p>Anime Tosho is a free, automated service which mirrors most torrents posted on <a href="https://tokyotosho.info/?cat=1">TokyoTosho's anime category</a>, <a href="https://nyaa.si/?c=1_2">Nyaa.si's English translated anime category</a> and <a href="http://nekobt.to/">nekoBT's English tagged releases</a> onto various file hosting (otherwise known as 'DDL') services, as well as Usenet. The script running the service performs a few other tasks, such as taking screenshots of videos, extracting embedded subtitles and attempting to find a source article/blog post and providing crude classification of information.</p>


<p style="font-size: smaller; font-style: italic; text-align: right;">Last Updated: Jan 16th, 2021</p>
<h3 style="margin-top: -1.5em;">FAQs</h3>
<h4>General</h4>
<dl>
	<dt>What special search operators are supported?</dt>
	<dd>Search is based on partially matching words. If more than one word is specified, results need to match against all words. Note that short words are ignored. Search behaviour can be modified with some special operators:
	<ul>
		<li>Partial word matching can be done by placing a star at the beginning or end of a word, for example <em>poke*</em> Make sure words are at least 4 characters in length.</li>
		<li>Words can be excluded by prefixing them with a dash, example: <em>horriblesubs -480p</em> (results must contain <em>horriblesubs</em> but not <em>480p</em>)</li>
		<li>Phrases can be surrounded by double quotes, which forces words to appear together, for example <em>&quot;one piece&quot;</em></li>
	</ul>
	</dd>
	<dt>What hosts does Anime Tosho upload to?</dt>
	<dd>This varies from time to time... So just see some recent files for an idea of what hosts are currently being uploaded to.</dd>
	<dt>How long do uploads take to appear?</dt>
	<dd>Anime Tosho tries to mirror the files as quickly as possible, however, sometimes things will take longer. Typically, most single files get mirrored in minutes to hours; batches or larger entries could take days, depending on server load. Note that how fast the torrent can be downloaded may also be a big contributing factor, as well as the number/size of files recently posted (since a lot of files being posted can increase the size of the processing queue). At times, hosts, or the updates server itself, have problems which can cause delays.</dd>
	<dt>Is there a feed available?</dt>
	<dd><a href="<?php echo AT::buildUrl('feed', 'rss2'); ?>">RSS2</a> (<a href="<?php echo AT::buildUrl('feed', 'rss2', ['only_tor'=>1]); ?>">torrents only</a>), <a href="<?=AT::buildUrl('feed', 'api')?>">Newznab/Torznab</a> and <a href="<?php echo AT::buildUrl('feed', 'atom'); ?>">ATOM</a>. Listing pages should have an RSS icon in the top-right, which can be used to get the feed URL for that particular listing.
	<br/>If you are trying to add any of these to an application which insists on an API key, please just put <i>0</i> as the key (or anything else, as Anime Tosho doesn't require an API key, but it's helpful if everyone uses the same key for server caching).</dd>
	<dt>Is there an API available?</dt>
	<dd>The feed (see above) can serve as an API. You can also replace the <code>rss2</code> or <code>atom</code> in the feed URL with <code>json</code> to get a JSON formatted version. The JSON API also supports querying torrent/file info if given a supplied torrent ID (<a href="https://feed.animetosho.org/json?show=torrent&id=431894">example</a>).
	<br />Alternatively, daily database exports are also <a href="<?=STORAGE_URL?>dbexport/">available for download here</a>.</dd>
	<!-- <dt>Are there any other sites similar to Anime Tosho?</dt>
	<dd>There's heaps of sites that provide DDL services for anime, so go and do a search if you wish! I find most of these other sites tend to only upload files from a small number of groups (possibly to get their posting up as fast as possible) and almost never upload releases such as those sourced from a BD, which was why I decided to start up Anime Tosho. On the flip side, they may categorise files more nicely and have a larger community.
	<br />Many fansubbers will also provide a DDL at their blog, so please support them and check it out.
	<br />Anime Tosho does do a few other things, such as provide screenshots, mediainfo dumps, subtitle extraction and multiple file hashes, which may be useful to some but I haven't seen many other sites do.</dd> -->
	<dt>Is there a donate link?</dt>
	<dd>Donations aren't accepted. Consider donating to fansub groups, file hosts or purchase relevant products to keep the anime scene alive.</dd>
	<dt>Is there any way I can help out?</dt>
	<dd>For Anime Tosho's core function of mirroring and processing torrents, it is 100% automated, which means low maintenace. However, for those willing to volunteer themselves, there are always a number of ways one can help out, some ideas:
	<ul>
		<li>providing helpful/useful comments, or responding to questions</li>
		<li>providing <a href="<?php echo AT::buildUrl('feedback'); ?>">feedback/ideas/suggestions</a>, including spotting and reporting mistakes</li>
		<li>if you want to contribute code, contributions are welcome to <a href="https://github.com/animetosho">AT's open source tools</a> (and these aren't specific to Anime Tosho's needs, so you'll be helping others too!)</li>
		<li>if there's something you want to see/have (e.g. new website design?), and you can help, please do <a href="<?php echo AT::buildUrl('feedback'); ?>">leave a note</a>!</li>
	</ul></dd>
	<dt>Are there any ads/tracking?</dt>
	<dd>There are no ads or tracking (beyond typical web-server logging) employed at Anime Tosho. There should be no requests made to any non-AT controlled server on pages here. This is a zero revenue service, so your details aren't being sold to anyone else. Web server logs are retained for 3-4 days.<br />Note that this may not be the case for external sites that are linked to, which Anime Tosho ultimately has no control over.<br />Also note that if you register an account, post a comment, or similar, these actions are retained in the database to enable such actions to persist and function. Logging in or changing an option that is remembered (such as site style) will set a cookie to allow your selection to be retained.</dd>
	<dt>Will Anime Tosho's bot script be made publicly available?</dt>
	<dd>I have no plans of doing this at the moment, and besides, the script was not designed to be used by others. (it's very configuration specific, and requires someone who really knows the script to manage it) However, these custom tools <a href="https://github.com/animetosho">are available at Github</a> and can be used freely in any way. Daily database exports are also <a href="<?=STORAGE_URL?>dbexport/">available for download here</a>.</dd>
	<dt>How do I upload torrents to Anime Tosho?</dt>
	<dd>Anime Tosho do not accept torrent uploads as this is a mirror service. Please submit torrents to sources <a href="https://tokyotosho.info/?cat=1">TokyoTosho</a> or <a href="https://nyaa.si/?c=1_2">Nyaa</a> in the correct category, and Anime Tosho will add them automatically.</dd>
</dl>
<h4>Issues</h4>
<dl>
	<!-- <dt>[This file] has been posted in the anime section of TokyoTosho/Nyaa, but doesn't appear on Anime Tosho!</dt>
	<dd><a href="/about/filtseries">Here is a list of series' that are skipped.</a></dd> -->
	<dt>I can't access Anime Tosho without using a proxy/Tor</dt>
	<dd>Connectivity issues can arise from a number of causes and may require diagnosis to find the issue (and maybe fix it, though it may not be likely). If you don't wish to diagnose the cause, some suggestions you can try:
		<ul>
			<li>Use a proxy/VPN or access via <a href="https://www.torproject.org/">Tor</a></li>
		</ul>
		Otherwise, things to check:
		<ul>
			<li>If accessing animetosho.org via the browser fails, what error does it display? If it's something along the lines of 'connection timed out', chances are that connectivity to Anime Tosho isn't working</li>
			<li>Can you ping animetosho.org? To test this out, open a command terminal (in Windows, it's Start -&gt; Run -&gt; cmd) and enter <code>ping animetosho.org</code> - if the result is something along the lines of 'Request timed out', then connectivity isn't working, but if you are getting replies, then connectivity is fine</li>
			<li>If connectivity isn't working, try performing a traceroute from the command terminal. In Windows, the command is <code>tracert animetosho.org</code> , for other OSes, it's <code>traceroute animetosho.org</code> . The output of this command should show the route that packets are taking to reach Anime Tosho and you should see that responses stop coming back beyond a certain point: <ul>
				<li>if this point is within the first few lines, there's a connectivity issue close to you - check that your router or network isn't dropping traffic</li>
				<li>if this point is later, for example, beyond the first 5 numbered lines, connectivity is broken somewhere else down the path. If this is the case, please post the output of this command <a href="<?php echo AT::buildUrl('feedback'); ?>">here</a> - note that you should mask/delete the first few numbered lines to preserve your privacy</li>
			</ul></li>
		</ul>
		If the above doesn't help, try <a href="<?php echo AT::buildUrl('feedback'); ?>">posting here</a> with all the details (error messages, output of the above commands, etc).
	</dd>
	<dt>[This file] has been posted to TokyoTosho/Nyaa but is greyed out here</dt>
	<dd>A grey background means that the file is being fetched. If the text is grey, it means that the file has been skipped.</dd>
	<dt>What files does Anime Tosho skip?</dt>
	<dd>Due to bandwidth limitations of the server, Anime Tosho skips the following:
		<ul>
			<li>Most torrents over 16GB in size</li>
			<li>Some specific exceptions, such as duplicates/reposts and excessively large versions of a file (e.g. BD remuxes)</li>
		</ul>
		However if there's something you want, you can elect to post a comment in the skipped torrent to see if anyone is willing to help.
	</dd>
	<dt>Why doesn't Anime Tosho fetch raws, remuxes and otherwise large files?</dt>
	<dd>As mentioned in the above answer, available resources are the primary concern. Large content is particularly taxing on resources, expensive to cater for, and generally unpopular, so the decision has been made to drop these high-cost, low-reward releases.</dd>
	<dt>[some file] hasn't been uploaded to [some host]!</dt>
	<dd>Please be aware that uploading is not instant and takes time to process. This is especially an issue during busy periods when lots of files get submitted, because the queue for uploading can grow rather large.<br />Another thing to note are some restrictions on some hosts - usually this is a size restriction if uploading to it would require splitting into many parts.<br />Other than that, it's possible that the automated script has broken, the host is having issues or some other weird anomaly has occurred.</dd>
	<dt>Why is [some host] no longer being uploaded to?</dt>
	<dd>Usually the explicit reason will be mentioned at the top of the <a href="<?php echo AT::buildUrl(''); ?>">home page</a>, but otherwise, it's often because the host has changed policies which makes it difficult or impossible for an automated script to upload to.</dd>
	<dt>I have found a dead link, will it be reuploaded?</dt>
	<dd>Unfortunately no, as continually reuploading old files would ultimately require an increasing amount of bandwidth and storage. Also, once the script has processed the file, it is deleted off disk to make room for other files. You'll have to try to download the files from the host before they are removed, or find another source.</dd>
	<dt>Why so many dead links? (for a recent file)</dt>
	<dd>It seems that many multi file hosters available nowadays are quite unreliable, so this may cause files to simply not be uploaded to various hosts. Also, files are always uploaded anonymously, so often will die quicker due to inactivity. Finally, they may simply be removed by the host (either through direct reporting of the link, or hash match with another report).</dd>
	<dt>[some file]'s specified series/episode/group is incorrect!</dt>
	<dd>The anime title, episode number and group name mentioned by Anime Tosho (not the filename) are actually automatically guessed by a script, and almost never manually set by humans. Due to the limitations of the heuristics used in the script, it does occasionally mis-classify items, sometimes with great comical value. Currently there is no manual review process in place, however, I find that the script is accurate most of the time.</dd>
	<dt>How trustworthy are files posted here?</dt>
	<dd>All files posted here are sourced from other torrent indexers. As with any system which accepts arbitrary user submissions, mistakes, mis-labellings or even malicious content may be posted. Although this is usually rare, it is recommended that users check comments and/or screenshots for abnormalities before downloading, and being vigilant before opening files. Checking the <i>Source Links</i> can also be helpful.
	<br /><b>Malicious content</b>: please be cautious if any unexpected executables (such as files ending in <i>.exe</i>) or scripts that are included in a torrent, as it may be a sign of a malicious file. These are usually removed from listing fairly soon, but you may come across them nonetheless, so vigilance is recommended. Please leave us a comment if you find any malicious content.</dd>
</dl>
<h4>Common Technical Issues</h4>
<dl>
	<dt>How do I extract .7z or .xz files?</dt>
	<dd>I strongly recommend using <a href="http://7-zip.org/">7-Zip</a> as it handles everything. If the file is broken into multiple parts (has .001, .002 etc on the end), get 7-Zip to extract from the .001 file and it will automatically look for the rest - NOTE: you may need to select the .001 file in 7-Zip and use the File menu &gt; &quot;Open Inside *&quot; option, as 7-Zip's auto-detection sometimes misidentifies files. Non-Windows users can try the <a href="http://p7zip.sourceforge.net/">p7zip</a> command line tool, and the command <code>7zr x <em>some_file.7z.001</em></code>.
	<br />Possible software suggestions:
	<ul>
		<li><a href="http://7-zip.org/">7-Zip</a> (Windows) or <a href="http://p7zip.sourceforge.net/">p7zip</a> (non-Windows) &gt;= 9.04</li>
		<li><a href="https://peazip.github.io/">PeaZip</a></li>
		<li><a href="https://www.rarlab.com/download.htm">WinRAR</a> &gt;= 5.00</li>
		<li><a href="https://www.winzip.com/en/download/winzip/">WinZip</a> &gt;= 18</li>
		<li><a href="http://tukaani.org/xz/">XZ Utils</a> (.xz only) - included in many recent Linux distros</li>
	</ul>
	</dd>
	<dt>How do I join multi-part (.001, .002 etc) files?</dt>
	<dd>Just use <a href="http://7-zip.org/">7-Zip</a> and extract the .001 file. Alternatively, the freeware <i>HJ-Split</i> will do the trick, or if you know the command line, <code>copy /b <em>some_file.ext</em>.0?? <em>some_file.ext</em></code> will work. Non-Windows users should be able to use the command <code>cat <em>some_file.ext</em>.0?? &gt; <em>some_file.ext</em></code>.</dd>
	<dt>What's the API key for adding Anime Tosho's &quot;-nab API&quot; to applications such as Sonarr?</dt>
	<dd>No API key is required. If the application requires one, please put <i>0</i> as the key (although anything will work, everyone using the same key helps with server caching).</dd>
</dl>
<h4>Improvements/suggestions</h4>
<dl>
	<dt>Why isn't MEGA (mega.nz) used for uploads?</dt>
	<dd>At the time of writing, MEGA accounts have a relatively small storage limit, whilst around 200GB of files are uploaded <i>per day</i>. Simple mathematics would deduce that this is simply an impractical endeavour.</dd>
	<dt>Will Anime Tosho support uploading to [some file host]?</dt>
	<dd>You can suggest it on <a href="<?php echo AT::buildUrl('feedback'); ?>">the feedback page</a>, although, being realistic, the usual answer will be no. The main constraint here is the amount of server bandwidth required to upload to each additional host, however, if there's overwhelming support for a particular host, it will be considered.
	<br />Some considerations to make when considering a host:
	<ul>
		<li>Are accounts required for uploading? Anonymous uploading is preferred here.</li>
		<li>If an account is required, how much space is given?</li>
		<li>Are there any other upload limits?</li>
		<li>How long do files last?</li>
		<li>Does the host modify uploaded files in any way?</li>
		<li>What quirks does the host have?</li>
		<li>Is the host friendly to downloaders?</li>
		<li>How long has the host been around? How likely are they to disappear soon?</li>
	</ul></dd>
	<dt>Will Anime Tosho ever be mirroring non-anime files (e.g. manga)?</dt>
	<dd>Highly unlikely, and there are no plans to do so. Anime Tosho's current focus is on English translated anime only.</dd>
	<dt>Can you upload [some arbitrary file]?</dt>
	<dd>Anime Tosho only mirrors new files posted to TokyoTosho/Nyaa's anime category and does not accept arbitrary mirror requests. I suggest trying to ask someone to help you on a filesharing forum, or perhaps use a torrent to DDL mirroring service (or get your own seedbox).</dd>
</dl>


<h3>Credits</h3>
<p>A vague/rough list - sorry if I left anyone out:</p>
<ul>
	<li>Source websites (see top of page), from where lots of files here are sourced</li>
	<li><a href="https://anidb.net/">AniDB</a>, where various information here is sourced as well</li>
	<li>The anonymous people who bother to seed torrents</li>
	<li>Operators of the free hosting services, for providing free file hosting</li>
	<li>Developers of supporting libraries used here, <a href="http://jquery.com/">jQuery</a>, <a href="http://www.webtoolkit.info/javascript-md5.html">Webtoolkit Javascript MD5</a> and <a href="http://www.phpcaptcha.org/">Securimage</a></li>
	<li>Developers of applications used: <ul>
		<li><a href="http://mediainfo.sourceforge.net/">mediainfo</a></li>
		<li><a href="https://www.bunkus.org/videotools/mkvtoolnix/">mkvtoolnix</a></li>
		<li><a href="https://gpac.wp.mines-telecom.fr/mp4box/">mp4box</a></li>
		<li><a href="http://mplayerhq.hu/">mplayer</a></li>
		<li><a href="http://ffmpeg.org/">ffmpeg</a></li>
		<li><a href="http://p7zip.sourceforge.net/">p7zip</a></li>
		<li><a href="http://tukaani.org/xz/">XZ utils</a></li>
		<li><a href="http://advancemame.sourceforge.net/comp-readme.html">AdvanceCOMP</a></li>
		<li><a href="https://github.com/mozilla/mozjpeg/">mozjpeg</a></li>
		<li><a href="https://github.com/axkibe/lsyncd/">lsyncd</a></li>
		<li><a href="http://nginx.org/">nginx</a></li>
		<li><a href="https://mariadb.com/">MariaDB</a></li>
		<li><a href="http://php.net/">PHP</a></li>
		<li><a href="http://nodejs.org/">NodeJS</a></li>
		<li><a href="https://transmissionbt.com/">Transmission</a></li>
		<li><a href="https://aria2.github.io/">aria2</a></li>
	</ul> And any other tools I forgot to include.</li>
	<li>All the various fansubbers or those who release stuff for everyone else to enjoy, as well as the studios etc for actually making the material</li>
	<li>People who have provided helpful feedback and suggestions to me (and I do appreciate the various &quot;thanks&quot; comments, although I don't reply to them all the time (it gets repetitive) - just be aware that I do appreciate them!)</li>
	<li>And of course all the visitors to this site for making my efforts have meaning</li>
	<li>Oh, and other people involved in the community for helping make it... a community!</li>
</ul>

