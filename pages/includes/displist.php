<?php
if(!defined('AT_ROOT')) exit;

$has_next_page = false;
if(!empty($items) && count($items) > $perpage) {
	$has_next_page = true;
	array_pop($items);
}




$pagenav = '<div class="home_list_pagination">';
if($listing_order_col == 'idx_totalsize') {
	$prevPageText = 'Larger';
	$nextPageText = 'Smaller';
} else {
	$prevPageText = 'Older';
	$nextPageText = 'Newer';
}
if($listing_order_dir == 'ASC') {
	list($prevPageText, $nextPageText) = [$nextPageText, $prevPageText];
}

if($AT->input->page > 1 && !empty($items)) {
	$pageArgs = array('page' => $AT->input->page-1);
	if(!$AT->input->q && $limit_start > 1500) {
		// search for previous page's hint
		if(substr($listing_order_col, 0, 4) == 'idx_') {
			$ppDate = $AT->db->selectGetField('toto', substr($listing_order_col, 4), $unhinted_qwhere.' AND '.(reset($items)[substr($listing_order_col, 4)] * $listing_order_factor).$listing_order_op_next.$listing_order_col, array(
				'order' => $listing_order_col.' '.(($listing_order_dir == 'ASC') == ($listing_order_factor < 0)?'DESC':'ASC'),
				'limit_start' => $perpage
			));
			$pageArgs['_ts'] = $ppDate;
		}
	}
	$pagenav .= '<a href="' . AT::buildUrl($AT->input->action, $AT->input->subaction, array_merge($urlargs,$pageArgs)) . '">&lt; '.$prevPageText.' Entries</a>';
}
if($has_next_page) {
	$pageArgs = array('page' => $AT->input->page+1);
	if(!$AT->input->q && $limit_start >= 1500-$perpage) {
		if(substr($listing_order_col, 0, 4) == 'idx_') {
			$pageArgs['_ts'] = end($items)[substr($listing_order_col, 4)];
		}
	}
	$pagenav .= ($AT->input->page > 1 ? ' | ':'') . '<a href="' . AT::buildUrl($AT->input->action, $AT->input->subaction, array_merge($urlargs,$pageArgs)) . '">'.$nextPageText.' Entries &gt;</a>';
}
$pagenav .= '</div>';
echo $pagenav;

?>
<div class="home_list_filter">
<span class="dlxlink feeddd"><a href="<?=AT::buildUrl('feed','atom',$feed_args)?>" title="Atom Feed"><span class="image16 icon_feed"></span></a><ul class="linkmenu">
	<li><a href="<?=AT::buildUrl('feed','rss2',$feed_args)?>">RSS2 Feed</a></li>
	<li><a href="<?=AT::buildUrl('feed','rss2',array_merge(['only_tor'=>1], $feed_args))?>">RSS2 Torrent Feed</a></li>
	<li><a href="<?=AT::buildUrl('feed','atom',$feed_args)?>">Atom Feed</a></li>
</ul></span>
<form action="<?php echo AT::buildUrl($AT->input->action, $AT->input->subaction) ?>" method="get">
<input type="hidden" name="filter[0][t]" value="nyaa_class" /><select name="filter[0][v]">
		<option value=""<?php echo @$filter_nyaa_class_selected[''] ?>>Filter: (None)</option>
		<option value="remake"<?php echo @$filter_nyaa_class_selected['remake'] ?>>Filter: Hide Remakes</option>
		<option value="trusted"<?php echo @$filter_nyaa_class_selected['trusted'] ?>>Filter: Show Trusted/A+ only</option>
</select>&nbsp;
<?php if(!isset($listing_disable_sorting)) { ?>
<select name="order">
	<?php foreach(array(
		''       => 'Newest first',
		'date-a' => 'Oldest first',
		'size-a' => 'Smallest first',
		'size-d' => 'Largest first',
	) as $k=>$v) {
		echo '<option value="'.$k.'"'.@$listing_order_selected[$k].'>Sort: '.$v.'</option>';
	} ?>
</select>&nbsp;
<?php } ?>
<select name="disp">
	<?php foreach(array(
		'' => 'Downloads',
		'attachments' => 'Subtitles',
	) as $k=>$v) {
		echo '<option value="'.$k.'"'.@$disp_selected[$k].'>Show: '.$v.'</option>';
	} ?>
</select>&nbsp;
<?php if(!empty($disp_urlargs)) foreach($disp_urlargs as $k=>$v) {
	echo '<input type="hidden" name="'.htmlspecialchars($k).'" value="'.htmlspecialchars($v).'" />';
} ?>
<input type="submit" value="Apply" />
</form></div>
<div>
<?php

$prevsep = '';
$alt = '';

if(empty($items)) {
	echo '<div class="home_list_datesep">-</div><div>No items found!</div>';
} else foreach($items as &$item) {
	if($listing_order_col == 'idx_dateline') {
		// day separator functionality
		$day = $AT->user->fmtDate($item['dateline'], true, false, true);
		if($day != $prevsep) {
			echo '<div class="home_list_datesep">', htmlspecialchars_uni($day), '</div>';
			$prevsep = $day;
		}
	} else /* if($listing_order_col == 'totalsize') */ {
		// grouping?
		if(!$prevsep) {
			echo '<div class="home_list_datesep"></div>';
			$prevsep = true;
		}
	}
	//}
	
	$rowclass = 'home_list_entry'.($alt?$alt='':$alt=' home_list_entry_alt').' home_list_entry_compl_'.$item['ulcomplete'];
	$size_tt = 'Total file size: '.$AT->user->fmtNum($item['totalsize']).' byte'.($item['totalsize']==1?'':'s');
	$date_tt = 'Date/time submitted: '.$AT->user->fmtDate($item['dateline'], true, false, true).' '.$AT->user->fmtDate($item['dateline'], false, true);
?>
<div class="<?php echo $rowclass; ?>">
	<div class="date" title="<?php echo $date_tt; ?>"><?php echo $AT->user->fmtDate($item['dateline'], false, true); ?></div>
	<div class="date_icon" title="<?php echo $date_tt; ?>"><span class="image16 icon_clock"></span></div>
	<div class="size" title="<?php echo $size_tt; ?>"><?php echo $AT->friendlyFileSize($item['totalsize']); ?></div>
	<div class="size_icon" title="<?php echo $size_tt; ?>"><span class="image16 icon_<?php echo $item['torrentfiles'] > 1 ? 'dir':'file'; ?>size"></span></div>
	<?php $linktitle = str_replace("\n", '<wbr/>', /* &#8203; */
		htmlspecialchars_uni(
			wordwrap_fn2(str_replace("\n", '', $item['name']), 50, "\n")
		)
	);
	$catlink = '';
	if(isset($disp_cat_type)) {
		$catlinks = null;
		if($disp_cat_type == 'series' && $item['aid'] && isset($item['title'])) {
			$catlinks = [[
				'url' => AT::buildUrl('series', AT::seoPageSubaction($item['aid'], $item['title'])),
				'title' => $item['title']
			]];
		}
		if($disp_cat_type == 'series_group' && $item['aid'] && isset($item['title']) && !empty($item['groups'])) {
			$catlinks = [];
			if(count($item['groups']) > 3) {
				// use shortnames
				foreach($item['groups'] as $gid => $grp) {
					$catlinks[] = [
						'url' => AT::buildUrl('series', AT::seoPageSubaction($item['aid'], $item['title']), ['gid' => $gid]),
						'title' => $grp['shortname'],
						'tip' => $grp['name']
					];
				}
			} else {
				foreach($item['groups'] as $gid => $grp) {
					$catlinks[] = [
						'url' => AT::buildUrl('series', AT::seoPageSubaction($item['aid'], $item['title']), ['gid' => $gid]),
						'title' => $grp['name']
					];
				}
			}
		}
		if($disp_cat_type == 'episode' && $item['aid'] && $item['eid'] && isset($item['title'])) {
			$mock_ep = [
				'id' => $item['eid'],
				'type' => $item['eptype'],
				'epno' => $item['epno'],
				'name' => $item['eptitle'],
				'aid' => $item['aid'],
				'series_title' => $item['title'],
			];
			$catlinks = [[
				'url' => AT::buildUrl('episode', AT::seoPageSubaction($item['eid'], $item['title'].($item['epno'] ? ' '.format_episode($mock_ep, 'num') : ''))),
				'title' => format_episode($mock_ep, 'short'),
				'tip' => format_episode($mock_ep, 'long')
			]];
		}
		
		if(!empty($catlinks)) {
			$catlink = '<span class="serieslink">'.implode(', ', array_map(function($clink) {
				$ret = '<a href="'.$clink['url'].'"';
				if(isset($clink['tip']))
					$ret .= ' title="'.htmlspecialchars_uni($clink['tip']).'"';
				if(isset($clink['title'][31])) { // length threshold
					if(!isset($clink['tip']))
						$ret .= ' title="'.htmlspecialchars_uni($clink['title']).'"';
					$ret .= '>'.htmlspecialchars_uni(trim(substr($clink['title'], 0, 29))).'</a>...';
				} else
					$ret .= '>'.htmlspecialchars_uni($clink['title']).'</a>';
				return $ret;
			}, $catlinks)).'</span>';
		}
	}
	if(false && $item['ulcomplete'] == -1) { ?>
		<div class="link"><?php if($catlink) echo '<span class="links">', $catlink, '</span>'; ?><a href="<?php
			if($item['isdupe'])
				echo Toto::viewUrl($item);
			elseif($item['tosho_id'])
				echo 'https://www.tokyotosho.info/details.php?id=', $item['tosho_id'];
			elseif($item['nyaa_id'])
				echo 'https://'.($item['nyaa_subdom']?$item['nyaa_subdom'].'.':'').'nyaa.si/view/', $item['nyaa_id'];
			else
				echo htmlspecialchars($item['link']); ?>"><?php echo $linktitle;
		?></a></div>
	<?php } else {
		$linkurl = Toto::viewUrl($item);
	?>
		<div class="link"><a href="<?php echo $linkurl; ?>"><?php echo $linktitle; ?></a></div>
		<div class="links">
			<span class="links_right"><span class="misclinks">
				<span class="numcomments"><?php
					if($item['tosho_id']) {
						echo '<a href="https://www.tokyotosho.info/details.php?id='.$item['tosho_id'].'" title="TokyoTosho">TT</a>';
						if($item['nyaa_id'] || $item['anidex_id'] || $item['nekobt_id']) echo ' | ';
					}
					if($item['nyaa_id']) {
						echo nyaa_source_link($item);
						if($item['nekobt_id']) echo ' | ';
					}
					if($item['anidex_id']) {
						echo '<a href="https://anidex.info/?page=torrent&id='.$item['anidex_id'].'" title="AniDex">ADex</a>';
					}
					if($item['nekobt_id']) {
						echo '<a href="https://nekobt.to/torrents/'.$item['nekobt_id'].'" title="nekoBT">nBT</a>';
					}
				?></span>
				<?php if($item['website']) echo '<a href="', htmlspecialchars($item['website']), '" class="website">Website</a>'; ?>
			</span></span>
			<?php echo $catlink; ?>
			<?php if($AT->input->disp == 'attachments') { 
				if(@$item['has_all_attachments'])
					echo '<a href="', STORAGE_URL_REDIR, 'torattachpk/', $item['id'], '/', name_base_for_url($item['name']), '_attachments.7z" class="dllink">All Attachments</a>';
				if(!empty($item['subtitles'])) {
					$comma = @$item['has_all_attachments'] ? ' | ' : '';
					foreach($item['subtitles'] as $subtitle) {
						echo $comma;
						if($subtitle)
							print_subtitle_link($subtitle, $item['sig_filename']);
						else
							echo '[unavailable]';
						$comma = ', ';
					}
				} elseif(!@$item['has_all_attachments'])
					echo '&nbsp;';
			} else { ?>
				<a href="<?php echo htmlspecialchars(torrent_link_url($item)); ?>" class="dllink">Torrent</a><?php if($item['magnet']) echo '/<a href="', htmlspecialchars(magnet_link_url($item)), '">Magnet</a>';
				if(isset($item['complete'])) {
					// TODO: should really make this a CSS class instead
					echo ' <span title="Seeders: ', $item['complete'],' / Leechers: ', $item['incomplete'],'" style="color: #808080;">[', $item['complete'],'&#8593;/',$item['incomplete'], '&#8595;]</span>';
				}
				if($item['stored_nzb']) echo ', <a href="', htmlspecialchars(nzb_link_url($item)), '">NZB</a>';
				?>
				<?php if($item['sigfid']) { // TODO: also files which have been ZIP'd into a single package
					if($links = filelinks_decode($item['links'])) {
						// strip out links we don't care about
						foreach($links as $site => &$parts) {
							if(substr($site, 0, 1) == '!') // encrypted link
								unset($links[$site]);
							else {
								foreach($parts as $pn => $part) {
									if(@$part['st']) unset($parts[$pn]);
								}
								if(empty($parts)) unset($links[$site]);
							}
						} unset($parts);
						filelinks_to_tree($links);
						$morelinks = 0;
						foreach($links as $site => &$parts) {
							if(count($parts) > 1 || !isset($parts[0]))
								++$morelinks;
							else {
								$ln = $parts[0];
								if($dllinkurl = Toto::getFileLink($ln['url'])) {
									if(!empty($ln['children'])) {
										echo ' | <span class="dlxlink"><a href="', htmlspecialchars($dllinkurl), '" class="dllink">', htmlspecialchars($site), '</a><span class="downarrow">&#9660;</span><ul class="linkmenu">';
										foreach($ln['children'] as $csite => $cln) {
											if($dllinkurl = Toto::getFileLink($cln['url']))
												echo '<li><a href="', htmlspecialchars($dllinkurl), '" class="dllink">', htmlspecialchars($csite), '</a></li>';
										}
										echo '</ul></span>';
									}
									else {
										echo ' | <a href="', htmlspecialchars($dllinkurl), '" class="dllink">', htmlspecialchars($site), '</a>';
									}
								}
							}
							//if(is_string($ln))
							//	echo ' | <a href="', htmlspecialchars($ln), '" class="dllink">', htmlspecialchars($service), '</a>';
						}
						if($morelinks)
							echo ' | <a href="', $linkurl, '" style="font-style: italic;">'.$morelinks.' more...</a>';
					}
				}
				if($item['torrentfiles'] > 1 || !$item['sigfid'])
					echo ' <em>(', $AT->user->fmtNum($item['torrentfiles']), ' file',($item['torrentfiles']==1?'':'s'),')</em>';
				?>
			<?php } ?>
		</div>
	<?php } ?>
</div>

<?php
}

// pagination
?>
</div>
<?php
echo $pagenav;
