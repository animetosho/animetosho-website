<?php if(!defined('AT_ROOT')) die(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="robots" content="noindex" />
<title>Error</title>
<link rel="stylesheet" type="text/css" href="<?php echo self::staticFileUrl('subpage.css'); ?>" />
</head>
<body>

<div class="errorbox">
	<h2><?php echo $title; ?></h2>
	<div class="message">
	<?php echo $message, '
	</div>';
	
	// only show back button if referred by us
	$referer = @$_SERVER['HTTP_REFERER'];
	if($referer && substr($referer, 0, strlen(HOME_URL)) == HOME_URL) {
	?>
	<script language="javascript" type="text/javascript">
	<!--
		document.write('<a href="javascript:history.go(-1);void(0);" class="link">&laquo;&nbsp; Back</a>');
	//-->
	</script>
	<?php } ?>
</div>

</body>
</html>