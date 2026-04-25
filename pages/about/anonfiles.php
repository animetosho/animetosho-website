<?php
if(!defined('AT_ROOT')) exit;

$AT->output->title = 'About AnonFiles';
$AT->output->printHeader(86400);


$AT->output->printTitleAndDesc('Dealing with AnonFiles Corruption');

?>

<p>AnonFiles is a fairly popular service around here. Unfortunately, downloading from AF sometimes gives corrupt files. <span style="font-size: smaller; font-style: italic;"><a href="https://animetosho.org/view/doki-date-live-05-1280x720-hi10p-aac-b37cc0f5-mkv.650692#comment14584">Why??</a></span></p>
<p>Because all files uploaded to AF are wrapped in a 7-Zip archive, you will be notified of a corrupt download when you try to extract it. This page will document possible solutions to this problem when you experience it.</p>

<h3>Re-download the file or part</h3>
<p>From quick tests, it appears that AnonFiles does random bit flips on data at times. This means that re-downloading could give you a file without any bit flips, or perhaps bit flips in different positions (i.e. a file corrupted differently) likely resulting in increased stress levels.</p>

<h3>Repair the file/part with a torrent</h3>
<ol>
	<li>Extract all part(s) of the file, ignoring any CRC errors</li>
	<li>If necessary, join all these parts together into a complete (but with errors) file</li>
	<li>Download the torrent file, and open this in your torrent client</li>
	<li>Start the torrent then stop it a short time after (this is just to get the torrent client to write some incomplete files to disk)</li>
	<li>Try finding the incomplete download for this torrent on your disk, if it doesn't exist, you may need to get your client to download a bit of the torrent before it shows up</li>
	<li>Close your torrent client, including any instance of it in the system tray</li>
	<li>Replace the incomplete file with the complete file you extracted in steps 1 and 2 above (depending on your torrent client, you may need to rename the complete file first)</li>
	<li>Start up your torrent client</li>
	<li>Do a force hash check on the torrent. After it's done, the torrent should appear to be almost complete</li>
	<li>Just torrent what's left of the file. You should now have a complete file without any errors</li>
	<li>If you're a man, seed the file back to the community for a while.</li>
</ol>

<h3>Use a good part from another host</h3>
<p>This is only relevant if the file is broken into 2 or more parts, and another host has been uploaded to with the same number of parts, and AT doesn't send files to this other host wrapped in a 7-Zip archive.</p>
<p>For example, if a file is uploaded to AnonFiles in 2 parts, and is uploaded to X in 2 parts, but part 2 of the file from AnonFiles gives a CRC error on extraction. You could download part 2 from X, delete the part 2 you got from AnonFiles, extract part 1 of the file you got from AnonFiles, and join this with part 2 from X.</p>
<p>See, that was easy, wasn't it?</p>

<h3>Ignore the corrupt file/part</h3>
<p>If all else fails, you can just extract the file/part, ignoring the CRC error. If necessary, then join all the parts together and leave it at that. Depending on where corruption occurred, you may in fact have a playable file, perhaps with an odd glitch here or there (such as any lack of censoring).</p>

<h3>Download from another source</h3>
<p>Durp</p>
