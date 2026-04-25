<?php if(!defined('AT_ROOT')) die(); ?>
		</div></div>
		</div>
		
		<div id="mtopbar_c"><div id="mtopbar" style="right: 0; width: auto;">
			<a id="anchor_top"></a>
			<span id="topbar_title" style="font-size: 1.5em; padding-top: 0; margin-right: 2em; vertical-align: top;"><?php echo '<a href="', AT::buildUrl('home'), '">', SITE_NAME, '</a>'; ?></span>
			<div id="topbar_search">
				<form action="<?php echo AT::buildUrl('search'); ?>" method="get">
					<div>
						<input type="text" name="q" class="text" value="<?php echo ($AT->input->action == 'search' ? htmlspecialchars($AT->input->q) : ''); ?>" placeholder="Search" /><input type="submit" value="" class="submit icon_search" />
					</div>
				</form>
			</div>
			
			<span style="float: right; margin-right: 1em;">
				<script type="text/javascript">
				<!--
					function themeChange(opt) {
						document.getElementById('stylesheet').href = opt.value;
						document.cookie = "ant[theme]=" + opt.getAttribute('rel') + "; path=/";
					}
					document.write('<select onchange="themeChange(this.options[this.selectedIndex])" style="font-size: smaller; margin-right: 2em; min-width: 2em;" autocomplete="off">');
					<?php
					foreach(array(
						113 => array('', '#FFFFE8', '#202020', 'style_113'),
						114 => array('', '#E0F0FF', '#202020', 'style_114'),
						121 => array('', '#E0D0FF', '#202020', 'style_121'),
						122 => array('', '#D8FFC8', '#202020', 'style_122'),
						123 => array('', '#ffc8c8', '#202020', 'style_123'),
						124 => array('', '#fff1bf', '#202020', 'style_124'),
						131 => array('', '#f8f8f8', '#202020', 'style_131'),
						132 => array('', '#ffe7fc', '#202020', 'style_132'),
						133 => array('', '#070707', '#e0e0e0', 'style_133'),
						134 => array('', '#241c00', '#e0e0e0', 'style_134'),
						141 => array('', '#000024', '#e0e0e0', 'style_141'),
						142 => array('', '#200020', '#e0e0e0', 'style_142'),
						143 => array('', '#180000', '#e0e0e0', 'style_143'),
						144 => array('', '#001010', '#e0e0e0', 'style_144'),
						151 => array('', '#333333', '#e0e0e0', 'style_151'),
						152 => array('', '#001000', '#e0e0e0', 'style_152'),
					) as $themeId => $themeDet) {
						echo 'document.write(\'<option value="'.$this->staticFileUrl($themeDet[3].'.css').'" style="background-color: '.$themeDet[1].'; color: '.$themeDet[1].';" rel="'.$themeId.'"'.(str_replace('_','',$this->theme) == $themeId ? ' selected="selected"' :'').'>Style</option>\');';
					}
					?>
					document.write('</select>');
				//-->
				</script>
				Current Time: <?php echo $AT->user->fmtDate(TIME_NOW, true, true, false); ?>
			</span>
			
			
		</div></div>
		<div class="clear"></div>

<?php echo $this->foot_extra_html; ?>

	</body>
</html>
