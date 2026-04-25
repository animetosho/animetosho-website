<?php if(!defined('AT_ROOT')) die(); ?>
		</div></div>
		</div>
		
		<div id="topbar_c"></div>
		<div id="topbar_c2"><div id="topbar">
			<a id="anchor_top"></a>
			<div id="topbar_title"><?php echo '<a href="', AT::buildUrl('home'), '">', SITE_NAME, '</a>'; ?></div>
			<div id="topbar_betanote" title="Expect shit to happen; PS: we're not Google">beta</div>
			<div id="topbar_tagline">Anime DDL+NZB mirror</div>
			<div id="topbar_search">
				<form action="<?php echo AT::buildUrl('search'); ?>" method="get">
					<div>
						<?php if(@$AT->input->qx) echo '<input type="hidden" name="qx" value="1"/>'; ?>
						<input type="text" name="q" class="text" value="<?php echo ($AT->input->action == 'search' ? htmlspecialchars($AT->input->q) : ''); ?>" placeholder="Search" /><input type="submit" value="" class="submit icon_search" />
					</div>
					<?php if(in_array($AT->input->action, array('series','episode','search')) && @$GLOBALS['aid']) { ?>
						<div id="topbar_search_series"><label class="check_label" title="Only search within this series"><input type="checkbox" name="aid" value="<?php echo $GLOBALS['aid']; if($AT->input->action=='search') echo '" checked="checked'; ?>" /> ...in this series only</label></div>
					<?php } ?>
				</form>
			</div>
			<div id="topbar_nav_links">
				<a href="<?php echo AT::buildUrl('home'); ?>">Torrents</a><br />
				<a href="<?php echo AT::buildUrl('animes'); ?>">Series</a> | <a href="<?php echo AT::buildUrl('episodes'); ?>">Episodes</a>
			</div>
			
			<div id="userbar">
				<?php $this->printUserBar($AT->user); ?>
			</div>
			
			<div id="topbar_time">
				Current Time: <?php echo $AT->user->fmtDate(TIME_NOW, true, true, false); ?>
				<br />
				<script type="text/javascript">
				<!--
					function themeChange(opt) {
						document.getElementById('stylesheet').href = opt.value;
						var d = new Date();
						var m = Math.floor((d.getMonth() +2) / 3)*3 +1;
						if(m>11)
							d.setFullYear(d.getFullYear()+1, m-11, 1);
						else
							d.setMonth(m,1);
						d.setHours(0,0,0);
						//document.cookie = document.cookie.replace(/&?ant\[theme\]=([^&]+)/g, "") + "&ant[theme]=" + opt.rel;
						document.cookie = "ant[theme]=" + opt.getAttribute('rel') + "; expires=" + d.toGMTString() + "; path=/";
					}
					document.write('Style: <select onchange="themeChange(this.options[this.selectedIndex])" style="font-size: smaller; max-width: 99%" autocomplete="off">');
					<?php
					foreach(array(
						//113 => array('2011q3 - Kyoko', '#FFFFE8', '#202020', 'style_113'),
						//114 => array('2011q4 - Ika Musume', '#E0F0FF', '#202020', 'style_114'),
						//121 => array('2012q1 - Henrietta', '#E0D0FF', '#202020', 'style_121'),
						//122 => array('2012q2 - Yuka', '#D8FFC8', '#202020', 'style_122'),
						//123 => array('2012q3 - Klein', '#ffc8c8', '#202020', 'style_123'),
						//124 => array('2012q4 - Rin', '#fff1bf', '#202020', 'style_124'),
						//131 => array('2013q1 - Masuzu', '#f8f8f8', '#202020', 'style_131'),
						//132 => array('2013q2 - Naru', '#ffe7fc', '#202020', 'style_132'),
						//133 => array('2013q3 - Elsie', '#070707', '#e0e0e0', 'style_133'),
						//134 => array('2013q4 - Ouka', '#241c00', '#e0e0e0', 'style_134'),
						//141 => array('2014q1 - Rikka', '#000024', '#e0e0e0', 'style_141'),
						//142 => array('2014q2 - Rize', '#200020', '#e0e0e0', 'style_142'),
						//143 => array('2014q3 - Seijuurou', '#180000', '#e0e0e0', 'style_143'),
						//144 => array('2014q4 - Noel', '#001010', '#e0e0e0', 'style_144'),
						//151 => array('2015q1 - Julie', '#333333', '#e0e0e0', 'style_151'),
						//152 => array('2015q2 - Sakura', '#001000', '#e0e0e0', 'style_152'),
						//153 => array('2015q3 - Umaru', '#FFFFE8', '#202020', 'style_153'),
						//154 => array('2015q4 - Yuki', '#E0F0FF', '#202020', 'style_154'),
						//161 => array('2016q1 - Hotaru', '#E0D0FF', '#202020', 'style_161'),
						//162 => array('2016q2 - Ikoma', '#D8FFC8', '#202020', 'style_162'),
						//163 => array('2016q3 - Ruby', '#ffc8c8', '#202020', 'style_163'),
						//164 => array('2016q4 - Kohana', '#fff1bf', '#202020', 'style_164'),
						//171 => array('2017q1 - Shii', '#f8f8f8', '#202020', 'style_171'),
						//172 => array('2017q2 - Yoshino', '#ffe7fc', '#202020', 'style_172'),
						//173 => array('2017q3 - Hotaru', '#070707', '#e0e0e0', 'style_173'),
						//174 => array('2017q4 - Cardia', '#241c00', '#e0e0e0', 'style_174'),
						//181 => array('2018q1 - Rin', '#000024', '#e0e0e0', 'style_181'),
						//182 => array('2018q2 - Ruki', '#200020', '#e0e0e0', 'style_182'),
						//183 => array('2018q3 - Yuzu &amp; Sumire', '#180000', '#e0e0e0', 'style_183'),
						//184 => array('2018q4 - Merc', '#001010', '#e0e0e0', 'style_184'),
						//191 => array('2019q1 - Gray', '#333333', '#e0e0e0', 'style_191'),
						//192 => array('2019q2 - Nona', '#001000', '#e0e0e0', 'style_192'),
						//193 => array('2019q3 - Hibiki', '#FFFFE8', '#202020', 'style_193'),
						//194 => array('2019q4 - Miki', '#E0F0FF', '#202020', 'style_194'),
						//201 => array('2020q1 - Cinnamon', '#E0D0FF', '#202020', 'style_201'),
						//202 => array('2020q2 - Akari', '#D8FFC8', '#202020', 'style_202'),
						//203 => array('2020q3 - Tiara', '#ffc8c8', '#202020', 'style_203'),
						//204 => array('2020q4 - Rena', '#fff1bf', '#202020', 'style_204'),
						//211 => array('2021q1 - Unnamed/Kumoko', '#f8f8f8', '#202020', 'style_211'),
						//212 => array('2021q2 - Gloomy', '#ffe7fc', '#202020', 'style_212'),
						//213 => array('2021q3 - Rio', '#070707', '#e0e0e0', 'style_213'),
						//214 => array('2021q4 - Suzune', '#241c00', '#e0e0e0', 'style_214'),
						//221 => array('2022q1 - Mai', '#000024', '#e0e0e0', 'style_221'),
						222 => array('2022q2 - Yuu', '#200020', '#e0e0e0', 'style_222'),
						223 => array('2022q3 - Chiyo', '#180000', '#e0e0e0', 'style_223'),
						224 => array('2022q4 - Rachel', '#001010', '#e0e0e0', 'style_224'),
						231 => array('2023q1 - Himuro', '#333333', '#e0e0e0', 'style_231'),
						232 => array('2023q2 - Shiragiku', '#001000', '#e0e0e0', 'style_232'),
						233 => array('2023q3 - Charlotte', '#FFFFE8', '#202020', 'style_233'),
						234 => array('2023q4 - Sunraku', '#E0F0FF', '#202020', 'style_234'),
						241 => array('2024q1 - Utena', '#E0D0FF', '#202020', 'style_241'),
						242 => array('2024q2 - Gobmi', '#D8FFC8', '#202020', 'style_242'),
						243 => array('2024q3 - Minamo', '#ffc8c8', '#202020', 'style_243'),
						244 => array('2024q4 - Maki', '#fff1bf', '#202020', 'style_244'),
						251 => array('2025q1 - Tarou', '#f8f8f8', '#202020', 'style_251'),
						252 => array('2025q2 - Ouka', '#ffe7fc', '#202020', 'style_252'),
						253 => array('2025q3 - Gen', '#070707', '#e0e0e0', 'style_253'),
						254 => array('2025q4 - Youko', '#241c00', '#e0e0e0', 'style_254'),
						 '' => array('2026q1 - Aki', '#000024', '#e0e0e0', 'style'),
					) as $themeId => $themeDet) {
						echo 'document.write(\'<option value="'.$this->staticFileUrl($themeDet[3].'.css').'" style="background-color: '.$themeDet[1].'; color: '.$themeDet[2].';" rel="'.$themeId.'"'.(str_replace('_','',$this->theme) == $themeId ? ' selected="selected"' :'').'>'.$themeDet[0].'</option>\');';
						//$themeSet = ($this->theme == $themeId || ($themeId == 999 && !$this->theme));
						//echo 'document.write(\'<option value="'.$this->staticFileUrl($themeDet[3].'.css').'" style="background-color: '.$themeDet[1].'; color: '.$themeDet[2].';" rel="'.$themeId.'"'.($themeSet ? ' selected="selected"' :'').'>'.$themeDet[0].'</option>\');';
					}
					?>
					document.write('</select>');
				//-->
				</script>
				<br /><br />
				<a href="<?php echo AT::buildUrl('feedback'); ?>" class="feedbacklink"><?php echo array_rand(array_flip(array(
					'Give us your feedback!',
					'Hate this website?!',
					'Gimme moar catgirls!',
					'/wants redesign',
					'[insert poll here]',
					'~nya~',
					'I don\'t even...',
					'What\'s this?',
				))); ?></a>
				<?php if($AT->user->isMod()) echo '<br /><a href="'.AT::buildUrl('modtalk').'">ModTalk</a>'; ?>
				<br /><a href="<?php echo AT::buildUrl('about'); ?>">About/FAQs</a>
				<br/><br/><a href="https://discord.gg/wrdSsv7APv">Discord</a>
			</div>

			
		</div></div>
		<div class="clear"></div>

<?php echo $this->foot_extra_html; ?>
	</body>
</html>
<?php echo "<!-- Page generated in ".round(microtime(true)-TIME_NOW_MICRO, 5)." seconds -->"; ?>
