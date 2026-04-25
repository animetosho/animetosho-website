<?php if(!defined('AT_ROOT')) die(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
	<head>
		<title><?php echo $this->title, ' | ', SITE_NAME; ?></title>
		<!--<base href="<?php echo HOME_URL; ?>/" />-->
<?php
// $this->head_metas['robots'] = 'noindex'; // for mirror site
foreach($this->head_metas as $type => &$content) { ?>
		<meta name="<?php echo $type; ?>" content="<?php echo strtr(htmlspecialchars_uni($content), array("\n" => '', "\r" => '')); ?>" />
<?php } ?>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="viewport" content="width=600, initial-scale=1" />
		<link rel="shortcut icon" href="<?php echo $this->staticFileUrl('favicon.ico'); ?>" />
		<link rel="stylesheet" type="text/css" href="<?php echo $this->staticFileUrl('style-base.css'); ?>" />
		<link rel="stylesheet" type="text/css" href="<?php echo $this->staticFileUrl('style'.$this->theme.'.css'); ?>" id="stylesheet" />
		<link rel="search" type="application/opensearchdescription+xml" href="/opensearchdescription.xml" title="<?php echo SITE_NAME; ?> Search" />
		<script type="text/javascript">
		<!--
			var rootUrl = "<?php echo HOME_URL.'/'; ?>", incUrl = "<?php echo INC_URL.'/'; ?>";
			var postkey = "<?php echo $GLOBALS['AT']->postKey; ?>";
		//-->
		</script>
		<script type="text/javascript" src="<?php echo INC_URL; ?>/jquery.js?ver=1.2.6"></script>
		<script type="text/javascript" src="<?php echo $this->staticFileUrl('common.js'); ?>"></script>
		<?php echo $this->head_extra_html; ?>
	</head>
	<body><?php if(!$simple_header) { ?>
		<div id="content_c"><div id="<?php if(defined('USE_MINIMAL_FOOTER')) echo 'm'; ?>content_c2"><div id="content">
			<?php
				foreach($this->notices as &$notice) {
					echo '			<div class="usernotice usernotice_', $notice[1], '">', $notice[0], '</div>', "\r\n";
				}
			?>
<?php }