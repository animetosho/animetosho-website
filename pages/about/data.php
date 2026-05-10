<?php
if(!defined('AT_ROOT')) exit;

$AT->output->title = 'Anime Tosho Data Dump';
$AT->output->printHeader(86400);


$AT->output->printTitleAndDesc('Anime Tosho Data Dump');
?>

<p>The following is a torrent containing data and assets produced over the years of AT operation. Feel free to share or use this data in any way you like.
<br/>Note that I am only able to seed this torrent for a few days, and I won't be retaining this data.</p>

<p><a href="https://animetosho.org/at-data.torrent" style="font-weight: bold">Download Torrent</a> (total size: 1.01TB)</p>

<p>The rest of this page provides some info on how to interpret this data.</p>

<h3>Database dumps</h3>
<p>The <b>database-dump.7z</b> file in the torrent holds MariaDB SQL dumps, corresponding to the databases of Anime Tosho's updates. You'll need to import the schemas before the data (if you've <a href="https://github.com/animetosho/animetosho-setup/blob/master/guide.md">set up AT using the guide</a>, the schemas should already be imported).</p>
<p>Note: <i>timebase.txt</i> holds the last time (Unix timestamp) the torrent ingestion was run. If you're running <a href="https://github.com/animetosho/animetosho-updater">animetosho-updater</a>, place it in the same directory as <i>cron.php</i>.</p>

<p>A description of the tables <a href="https://github.com/animetosho/animetosho-updater#summary-by-db-table">can be found here</a>, and a description of some fields can be <a href="https://storage.animetosho.org/dbexport/">found here</a>.</p>

<h3>Storage Data</h3>
<p>The files have been packaged into a series of 7z files to ease distribution. Unless you're just archiving data, you'll likely want to extract these. To do this in a way that's <a href="https://github.com/animetosho/animetosho-setup/blob/master/guide.md#data-importation">compatible with AT's code</a>, the 7z archives need to be unpacked into the same folder that they're located. On Windows with 7-Zip installed, go into each folder and select the .7z files, right-click and <i>Extract Here</i>, otherwise the following Bash command (run in the directory contains the torrents' contents) can achieve this.</p>

<p>Extract and remove archives:
<br/><code>find . -mindepth 2 -type f -name '*.7z' -execdir sh -c '7z x "$1" &amp;&amp; unlink "$1"' _ '{}' \;</code>
<br/>Extract archives, retaining .7z files:
<br/><code>find . -mindepth 2 -type f -name '*.7z' -execdir 7z x '{}' \;</code>
</p>

<p>Note that all files, excluding those in <i>torrent/</i>, are named using a hex encoded ID and you'll need to cross reference them with the database dumps above to identify them. To find it in the database table, convert the full hex name (see below) to decimal and look up the corresponding ID.</p>

<p>Assuming all .7z files are unpacked, the files are arranged in the following folders:</p>
<ul>
<li><b>attachments</b>: extracted subtitles, attachments and chapters/tags, keyed by attachment file ID (DB ref: <code>toto_repl.toto_attachment_files.id</code>). All files are XZ compressed</li>
<li><b>nzb</b>: NZBs produced when uploading to Usenet, keyed by Anime Tosho torrent ID (DB ref: <code>toto_repl.toto_toto.id</code>). All files are GZip compressed</li>
<li><b>sframes</b>: extracted video I-Frames and pre-rendered subtitles, keyed by Anime Tosho file ID (DB ref: <code>toto_repl.toto_files.id</code>). These files are used for <a href="https://github.com/animetosho/frame-server/blob/master/README.md#rationale">displaying screenshots</a>. The I-Frames are stored as MKVs with the timestamp (DB ref: <code>toto_repl.toto_files.vidframes</code>) tacked onto the filename, while the pre-rendered subtitles are in WebP format with the track ID (DB ref: <code>toto_repl.toto_attachments.attachments</code>) + timestamp tacked onto the filename.
<br/>Note: this additional info tacked ont the filename is separated with underscores and isn't hex encoded</li>
<li><b>sshots</b>: legacy screenshot thumbnails, keyed by Anime Tosho file ID (DB ref: <code>toto_repl.toto_files.id</code>). Files are stored as ZIP files containing the JPEG thumbnails (DB ref: <code>toto_repl.toto_files.filethumbs</code>).</li>
<li><b>torrents</b>: fetched .torrent files, keyed by BTIH (DB ref: <code>toto_repl.toto_toto.btih</code>).</li>
<li><b>anidex_archive</b>: .torrent file archive from AniDex, keyed by AniDex ID (DB ref: <code>arcscrape.anidex_torrents.id</code>)</li>
<li><b>nekobt_archive</b>: .torrent file archive from nekoBT, keyed by nekoBT ID (DB ref: <code>arcscrape.nekobt_torrents.id</code>)</li>
<li><b>nyaasi_archive</b>: .torrent file archive from Nyaa.si, keyed by Nyaa ID (DB ref: <code>arcscrape.nyaasi_torrents.id</code>)</li>
<li><b>nyaasis_archive</b>: .torrent file archive from sukebei.nyaa.si, keyed by Sukebei ID (DB ref: <code>arcscrape.nyaasis_torrents.id</code>)</li>
</ul>
<p>Note that files are split into subfolders (to avoid having too many files in a folder) and the full name should be interpreted as a concatenation of the file's parent folders. For example, a file ideally named &quot;01234567.xz&quot; may be stored as &quot;01234/567.xz&quot; (and refers to ID=19088743 in the database). </p>

<h3>Note on Bad Data</h3>
<p>There's a small number of known instances of issues that I never got around to cleaning up. Examples off the top of my head:</p>
<ul>
<li>Data in compressed database fields used to be packed using a custom LZMA2 scheme and the compression code had a bug which would corrupt the data in rare cases</li>
<li>Some I-frame extractions (sframes folder) failed, but ffmpeg still outputted an MKV with 0 frames. These will obviously fail to render as screenshots</li>
<li>There may be other issues that I don't know of or can't remember. A lot has changed over the years, and there'll be imperfections (especially since I test in prod)</li>
</ul>

<h3>Source Code</h3>
<p>You can find a <a href="https://github.com/animetosho/animetosho-setup#list-of-repositories">list of code repositories which runs AT here</a>.</p>
