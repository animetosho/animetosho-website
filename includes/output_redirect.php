<?php
if(!defined('AT_ROOT')) die();

if(!$text) $text = 'You will be redirected...';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="refresh" content="3;URL=<?php echo htmlspecialchars($url); ?>" />
<meta name="robots" content="noindex" />
<title>Redirecting...</title>
<link rel="stylesheet" type="text/css" href="<?php echo self::staticFileUrl('subpage.css'); ?>" />
</head>
<body>

<div class="redirbox">
	<h2>Redirecting...</h2>
	<div class="message"><?php echo $text; ?></div>
	<a href="<?php echo htmlspecialchars($url); ?>" class="link">&raquo; Click here if your browser doesn't automatically redirect you...</a>
</div>

</body>
</html>