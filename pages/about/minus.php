<?php
if(!defined('AT_ROOT')) exit;

$AT->output->title = 'About Minus';
$AT->output->printHeader(86400);


$AT->output->printTitleAndDesc('Extracting from Minus');

?>

<p>Minus is a convenient service for uploading images to. Which means it should be perfect for us to put anything but images to. Because of this, getting files may be a bit tricky.</p>


<p style="font-size: smaller; font-style: italic; text-align: right;">Last Updated: Nov 28, 2012</p>
<h3 style="margin-top: -1.5em;">How to extract files downloaded from Minus</h3>
<ol>
	<li>The supplied link will open up the Minus folder containing all parts of the file. After opening it, download <em>all</em> parts in this folder.</li>
	<li>To make things easier, you may opt to add a <em>.7z</em> file extension to every file just downloaded. Doing so will allow applications to recognise these as 7-Zip files more readily, although you may be able to skip this step.</li>
	<li>If on Windows, open up the 7-Zip File Manager (if you do not have 7-Zip installed, <a href="http://7-zip.org/">download it from here</a>). For OSX/Linux, use your preferred 7-Zip extraction software, or the command line <a href="http://p7zip.sourceforge.net/">p7zip utility</a>.</li>
	<li>Extract all downloaded parts. If you're using 7-Zip File Manager, navigate to where you downloaded all the parts, select the downloaded parts and click <em>Extract</em> from the toolbar (tip: if the <em>Extract to</em> path ends with <code>*\</code> you may wish to delete these two characters).</li>
	<li>If there was only one part, you should now have your file. If there was multiple parts, then you need to join them using your favourite utility.</li>
</ol>

