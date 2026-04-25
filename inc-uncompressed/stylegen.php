<?php
if(PHP_SAPI != 'cli') die('Must be run via CLI');

$data = file_get_contents('style-gen.css');


function col_swp(&$cols) {
	foreach($cols as &$col) {
		if($col[0] == '#') {
			$col = '#'.substr($col, 1, 2) . substr($col, 5, 2) . substr($col, 3, 2);
		}
	}
}




$cols_yellow = array(

'[BODYBG]' => '#FFFFE8',
'[BODYFG]' => '#202000',

'[SIDEBG]' => '#FFFFC8',
'[SIDEIMG]' => 'sideimg_113.jpg',
'[BETANOTE]' => '#806000',
'[FEEDBACKLINK]' => '#FF8000',

'[TIPNOTE]' => '#FFFF00',
'[TIPNOTE_UL]' => '#FF8000',

'[INPUT_BORD]' => '#C0C0A0',
'[INPUT_HOV_BORD]' => '#A0C0A0',
'[INPUT_FOC_BORD]' => '#60D060',
'[REQUIRED_FIELD]' => '#208020',

'[COMMENT_BORD]' => '#C0C0A0',
'[COMMENT_BG]' => '#FFFFE0',
'[COMMENT2_BG]' => '#F0F0C0',
'[COMMENT_PH_BG]' => '#FFFFF0',
'[LINKBUTTON_BORD]' => '#8080FF',
'[LINKBUTTON_HOV_BG]' => '#FFFFFF',
'[LINKBUTTON_HOV_FG]' => '#000000',
//'[LINKBUTTON_OLD_FG]' => '#B080D0',

'[H2]' => '#C0A060',
'[LINK]' => '#3030FF',
'[LINK_HOV]' => '#0000A0',
'[LINK_FOC]' => '#A03030',
'[LINK_OLD]' => '#6030A0',

'[THEAD_BG]' => '#A0A050',
'[THEAD_FG]' => '#FFFFFF',

'[LISTITEM_BG]' => '#F8F8D0',
'[LISTITEM2_BG]' => '#F0F0C0',
'[LISTITEM_PROC_BG]' => '#E0E0C8',
'[LISTITEM2_PROC_BG]' => '#D0D0B8', // is this used?
'[LISTITEM_BAD_FG]' => '#FFA000',

'[DLLINK]' => '#4080E0',
'[DLLINK_HOV]' => '#2040A0',
'[DLLINK_OLD]' => '#8040E0',
'[DLLINK_MENU_BG]' => '#E0E0A0',
'[DLLINK_MENU_BORD]' => '#A0A060',
'[DLLINK_MENU_ARROW]' => '#808080',

);

$cols_blue = array(

'[BODYBG]' => '#E0F0FF',
'[BODYFG]' => '#000030',

'[SIDEBG]' => '#C0F0FF',
'[SIDEIMG]' => 'sideimg_114.jpg',
'[BETANOTE]' => '#002080',
'[FEEDBACKLINK]' => '#0080FF',

'[TIPNOTE]' => '#00FF30',
'[TIPNOTE_UL]' => '#00FFFF',

'[INPUT_BORD]' => '#A0B0C0',
'[INPUT_HOV_BORD]' => '#C0A0A0',
'[INPUT_FOC_BORD]' => '#D06060',
'[REQUIRED_FIELD]' => '#802020',

'[COMMENT_BORD]' => '#A0B0C0',
'[COMMENT_BG]' => '#E0FFFF',
'[COMMENT2_BG]' => '#C0F0F0',
'[COMMENT_PH_BG]' => '#F0FFFF',
'[LINKBUTTON_BORD]' => '#8080FF',
'[LINKBUTTON_HOV_BG]' => '#FFFFFF',
'[LINKBUTTON_HOV_FG]' => '#000000',
//'[LINKBUTTON_OLD_FG]' => '#B080D0',

'[H2]' => '#60A0C0',
'[LINK]' => '#3030FF',
'[LINK_HOV]' => '#0000A0',
'[LINK_FOC]' => '#A03030',
'[LINK_OLD]' => '#6030A0',

'[THEAD_BG]' => '#50A0A0',
'[THEAD_FG]' => '#FFFFFF',

'[LISTITEM_BG]' => '#D0F8FF',
'[LISTITEM2_BG]' => '#C0F0F8',
'[LISTITEM_PROC_BG]' => '#C8E0E0',
'[LISTITEM2_PROC_BG]' => '#D0D0B8', // is this used?
'[LISTITEM_BAD_FG]' => '#D08000',

'[DLLINK]' => '#4080E0',
'[DLLINK_HOV]' => '#2040A0',
'[DLLINK_OLD]' => '#8040E0',
'[DLLINK_MENU_BG]' => '#A0E0E0',
'[DLLINK_MENU_BORD]' => '#60A0A0',
'[DLLINK_MENU_ARROW]' => '#808080',

);

$cols_purple = array(

'[BODYBG]' => '#e0d0ff',
'[BODYFG]' => '#300030',

'[SIDEBG]' => '#d8c0ff',
'[SIDEIMG]' => 'sideimg_121.jpg',
'[BETANOTE]' => '#400080',
'[FEEDBACKLINK]' => '#6020ff',

'[TIPNOTE]' => '#b0e000',
'[TIPNOTE_UL]' => '#FFFF00',

'[INPUT_BORD]' => '#b0a0c0',
'[INPUT_HOV_BORD]' => '#A0C0A0',
'[INPUT_FOC_BORD]' => '#60D060',
'[REQUIRED_FIELD]' => '#802020',

'[COMMENT_BORD]' => '#b0a0c0',
'[COMMENT_BG]' => '#FFE0FF',
'[COMMENT2_BG]' => '#F0D0F0',
'[COMMENT_PH_BG]' => '#FFF0FF',
'[LINKBUTTON_BORD]' => '#8080FF',
'[LINKBUTTON_HOV_BG]' => '#FFFFFF',
'[LINKBUTTON_HOV_FG]' => '#000000',
//'[LINKBUTTON_OLD_FG]' => '#B080D0',

'[H2]' => '#A060C0',
'[LINK]' => '#2030FF',
'[LINK_HOV]' => '#0000A0',
'[LINK_FOC]' => '#A03030',
'[LINK_OLD]' => '#6030A0',

'[THEAD_BG]' => '#A050A0',
'[THEAD_FG]' => '#FFFFFF',

'[LISTITEM_BG]' => '#F0E0FF',
'[LISTITEM2_BG]' => '#E8D4FF',
'[LISTITEM_PROC_BG]' => '#D0C8D0',
'[LISTITEM2_PROC_BG]' => '#D0D0B8', // is this used?
'[LISTITEM_BAD_FG]' => '#D08000',

'[DLLINK]' => '#7060E0',
'[DLLINK_HOV]' => '#2040A0',
'[DLLINK_OLD]' => '#A030D0',
'[DLLINK_MENU_BG]' => '#E0C0E0',
'[DLLINK_MENU_BORD]' => '#A080A0',
'[DLLINK_MENU_ARROW]' => '#808080',

);

$cols_green = array(

'[BODYBG]' => '#D8FFC8',
'[BODYFG]' => '#003000',

'[SIDEBG]' => '#B0E8A0',
'[SIDEIMG]' => 'sideimg_122.jpg',
'[BETANOTE]' => '#007020',
'[FEEDBACKLINK]' => '#20B060',

'[TIPNOTE]' => '#FFFF30',
'[TIPNOTE_UL]' => '#FF8000',

'[INPUT_BORD]' => '#B0C0B0',
'[INPUT_HOV_BORD]' => '#E0A040',
'[INPUT_FOC_BORD]' => '#D06040',
'[REQUIRED_FIELD]' => '#805020',

'[COMMENT_BORD]' => '#80C090',
'[COMMENT_BG]' => '#E0FFEF',
'[COMMENT2_BG]' => '#C8F0C8',
'[COMMENT_PH_BG]' => '#F0FFF0',
'[LINKBUTTON_BORD]' => '#509050',
'[LINKBUTTON_HOV_BG]' => '#FFFFFF',
'[LINKBUTTON_HOV_FG]' => '#000000',
//'[LINKBUTTON_OLD_FG]' => '#B0D080',

'[H2]' => '#60C040',
/*'[LINK]' => '#208030',
'[LINK_HOV]' => '#20B050',
'[LINK_FOC]' => '#B03030',
'[LINK_OLD]' => '#A078A0',*/
'[LINK]' => '#3030FF',
'[LINK_HOV]' => '#0000A0',
'[LINK_FOC]' => '#A03030',
'[LINK_OLD]' => '#6030A0',


'[THEAD_BG]' => '#00A000',
'[THEAD_FG]' => '#FFFFFF',

'[LISTITEM_BG]' => '#B0FFA0',
'[LISTITEM2_BG]' => '#C8F8B8',
'[LISTITEM_PROC_BG]' => '#B8CCB8',
'[LISTITEM2_PROC_BG]' => '#D0B8D0', // is this used?
'[LISTITEM_BAD_FG]' => '#D08000',

/*'[DLLINK]' => '#40A840',
'[DLLINK_HOV]' => '#20C060',
'[DLLINK_OLD]' => '#907898',*/
'[DLLINK]' => '#4080E0',
'[DLLINK_HOV]' => '#2040A0',
'[DLLINK_OLD]' => '#8040E0',
'[DLLINK_MENU_BG]' => '#A8F0A8',
'[DLLINK_MENU_BORD]' => '#60A060',
'[DLLINK_MENU_ARROW]' => '#808080',

);

$cols_red = array(

'[BODYBG]' => '#ffc8c8',
'[BODYFG]' => '#300000',

'[SIDEBG]' => '#e8a0a0',
'[SIDEIMG]' => 'sideimg_123.jpg',
'[BETANOTE]' => '#6f0a27',
'[FEEDBACKLINK]' => '#B02060',

'[TIPNOTE]' => '#ffff68',
'[TIPNOTE_UL]' => '#FFA000',

'[INPUT_BORD]' => '#C0B0B0',
'[INPUT_HOV_BORD]' => '#407dd0',
'[INPUT_FOC_BORD]' => '#4a40d0',
'[REQUIRED_FIELD]' => '#a22525',

'[COMMENT_BORD]' => '#C08090',
'[COMMENT_BG]' => '#ffe0e7',
'[COMMENT2_BG]' => '#F0C8C8',
'[COMMENT_PH_BG]' => '#FFF0F0',
'[LINKBUTTON_BORD]' => '#905050',
  '[LINKBUTTON_HOV_BG]' => '#FFFFFF',
  '[LINKBUTTON_HOV_FG]' => '#000000',
//'[LINKBUTTON_OLD_FG]' => '#c880d0',

'[H2]' => '#d12c2c',
/*'[LINK]' => '#208030',
'[LINK_HOV]' => '#20B050',
'[LINK_FOC]' => '#B03030',
'[LINK_OLD]' => '#A078A0',*/
  '[LINK]' => '#3030FF',
  '[LINK_HOV]' => '#0000A0',
  '[LINK_FOC]' => '#A03030',
  '[LINK_OLD]' => '#6030A0',


'[THEAD_BG]' => '#af0101',
  '[THEAD_FG]' => '#FFFFFF',

'[LISTITEM_BG]' => '#e9ada7',
'[LISTITEM2_BG]' => '#fdbfab',
'[LISTITEM_PROC_BG]' => '#CCB8B8',
  '[LISTITEM2_PROC_BG]' => '#D0B8D0', // is this used?
'[LISTITEM_BAD_FG]' => '#d06c00',

/*'[DLLINK]' => '#40A840',
'[DLLINK_HOV]' => '#20C060',
'[DLLINK_OLD]' => '#907898',*/
  '[DLLINK]' => '#7080C0',
  '[DLLINK_HOV]' => '#2040A0',
  '[DLLINK_OLD]' => '#8040E0',
'[DLLINK_MENU_BG]' => '#F0A8A8',
'[DLLINK_MENU_BORD]' => '#A06060',
  '[DLLINK_MENU_ARROW]' => '#808080',

);

$cols_orange = array(

'[BODYBG]' => '#fff1bf',
'[BODYFG]' => '#461300',

'[SIDEBG]' => '#ffd576',
'[SIDEIMG]' => 'sideimg_124.jpg',
'[BETANOTE]' => '#b23009',
'[FEEDBACKLINK]' => '#f12e1a',

'[TIPNOTE]' => '#90ff0e',
'[TIPNOTE_UL]' => '#ddf700',

'[INPUT_BORD]' => '#feaf32',
'[INPUT_HOV_BORD]' => '#11b1fc',
'[INPUT_FOC_BORD]' => '#006cff',
'[REQUIRED_FIELD]' => '#ba5e09',

'[COMMENT_BORD]' => '#fc9929',
'[COMMENT_BG]' => '#ffe175',
'[COMMENT2_BG]' => '#ffd04d',
'[COMMENT_PH_BG]' => '#ffdb46',
'[LINKBUTTON_BORD]' => '#e96716',
  '[LINKBUTTON_HOV_BG]' => '#FFFFFF',
  '[LINKBUTTON_HOV_FG]' => '#000000',
//'[LINKBUTTON_OLD_FG]' => '#ffb524',

'[H2]' => '#ff5e1e',
/*'[LINK]' => '#208030',
'[LINK_HOV]' => '#20B050',
'[LINK_FOC]' => '#B03030',
'[LINK_OLD]' => '#A078A0',*/
  '[LINK]' => '#163cff',
  '[LINK_HOV]' => '#0000A0',
  '[LINK_FOC]' => '#00a01a',
  '[LINK_OLD]' => '#4d1faa',


'[THEAD_BG]' => '#d54403',
  '[THEAD_FG]' => '#FFFFFF',

'[LISTITEM_BG]' => '#ffdb79',
'[LISTITEM2_BG]' => '#ffe79d',
'[LISTITEM_PROC_BG]' => '#cabca2',
  '[LISTITEM2_PROC_BG]' => '#D0B8D0', // is this used?
'[LISTITEM_BAD_FG]' => '#f30000',

/*'[DLLINK]' => '#40A840',
'[DLLINK_HOV]' => '#20C060',
'[DLLINK_OLD]' => '#907898',*/
  '[DLLINK]' => '#7185d5',
  '[DLLINK_HOV]' => '#2040A0',
  '[DLLINK_OLD]' => '#8040E0',
'[DLLINK_MENU_BG]' => '#ffbf2e',
'[DLLINK_MENU_BORD]' => '#f2781a',
  '[DLLINK_MENU_ARROW]' => '#808080',

);


$cols_white = array(

'[BODYBG]' => '#f8f8f8',
'[BODYFG]' => '#303030',

'[SIDEBG]' => '#e0e0e0',
'[SIDEIMG]' => 'sideimg_131.jpg',
'[BETANOTE]' => '#a08009',
'[FEEDBACKLINK]' => '#0080ff',

'[TIPNOTE]' => '#ffff68',
'[TIPNOTE_UL]' => '#FFA000',

'[INPUT_BORD]' => '#a0a0a0',
'[INPUT_HOV_BORD]' => '#60A060',
'[INPUT_FOC_BORD]' => '#60D060',
'[REQUIRED_FIELD]' => '#208020',

'[COMMENT_BORD]' => '#808080',
'[COMMENT_BG]' => '#e8e8e8',
'[COMMENT2_BG]' => '#d8d8d8',
'[COMMENT_PH_BG]' => '#f8f8f8',
'[LINKBUTTON_BORD]' => '#505090',
  '[LINKBUTTON_HOV_BG]' => '#FFFFFF',
  '[LINKBUTTON_HOV_FG]' => '#000000',
//'[LINKBUTTON_OLD_FG]' => '#a0a0a0', // is this used?

'[H2]' => '#202020',
/*'[LINK]' => '#208030',
'[LINK_HOV]' => '#20B050',
'[LINK_FOC]' => '#B03030',
'[LINK_OLD]' => '#A078A0',*/
  '[LINK]' => '#1030c8',
  '[LINK_HOV]' => '#0000A0',
  '[LINK_FOC]' => '#2086F0',
  '[LINK_OLD]' => '#4d1faa',


'[THEAD_BG]' => '#606060',
  '[THEAD_FG]' => '#FFFFFF',

'[LISTITEM_BG]' => '#f0f0f0',
'[LISTITEM2_BG]' => '#e2e2e2',
'[LISTITEM_PROC_BG]' => '#b0b0b0',
  '[LISTITEM2_PROC_BG]' => '#D0B8D0', // is this used?
'[LISTITEM_BAD_FG]' => '#f30000',

/*'[DLLINK]' => '#40A840',
'[DLLINK_HOV]' => '#20C060',
'[DLLINK_OLD]' => '#907898',*/
  '[DLLINK]' => '#5070F0',
  '[DLLINK_HOV]' => '#2040A0',
  '[DLLINK_OLD]' => '#8040E0',
'[DLLINK_MENU_BG]' => '#d0d0d0',
'[DLLINK_MENU_BORD]' => '#808080',
  '[DLLINK_MENU_ARROW]' => '#808080',

);


$cols_pink = array(

'[BODYBG]' => '#ffe7fc',
'[BODYFG]' => '#230b23',

'[SIDEBG]' => '#ffc7f8',
'[SIDEIMG]' => 'sideimg_132.jpg',
'[BETANOTE]' => '#944c5b',
'[FEEDBACKLINK]' => '#ff0060',

'[TIPNOTE]' => '#eae154',
'[TIPNOTE_UL]' => '#91f04c',

'[INPUT_BORD]' => '#896eff',
'[INPUT_HOV_BORD]' => '#64abfe',
'[INPUT_FOC_BORD]' => '#6664e0',
'[REQUIRED_FIELD]' => '#16ca23',

'[COMMENT_BORD]' => '#fca2c4',
'[COMMENT_BG]' => '#ffe0f7',
'[COMMENT2_BG]' => '#ffd4f3',
'[COMMENT_PH_BG]' => '#fef6fb',
'[LINKBUTTON_BORD]' => '#f271c7',
  '[LINKBUTTON_HOV_BG]' => '#FFFFFF',
  '[LINKBUTTON_HOV_FG]' => '#000000',
//'[LINKBUTTON_OLD_FG]' => '#fc9eff',

'[H2]' => '#d54bc7',
/*'[LINK]' => '#208030',
'[LINK_HOV]' => '#20B050',
'[LINK_FOC]' => '#B03030',
'[LINK_OLD]' => '#A078A0',*/
  '[LINK]' => '#163cff',
  '[LINK_HOV]' => '#0000A0',
  '[LINK_FOC]' => '#00a01a',
  '[LINK_OLD]' => '#4d1faa',


'[THEAD_BG]' => '#e064b0',
  '[THEAD_FG]' => '#FFFFFF',

'[LISTITEM_BG]' => '#ffd0f0',
'[LISTITEM2_BG]' => '#ffddfb',
'[LISTITEM_PROC_BG]' => '#c7bec3',
  '[LISTITEM2_PROC_BG]' => '#D0B8D0', // is this used?
'[LISTITEM_BAD_FG]' => '#f30000',

/*'[DLLINK]' => '#40A840',
'[DLLINK_HOV]' => '#20C060',
'[DLLINK_OLD]' => '#907898',*/
  '[DLLINK]' => '#7185d5',
  '[DLLINK_HOV]' => '#2040A0',
  '[DLLINK_OLD]' => '#8040E0',
'[DLLINK_MENU_BG]' => '#ffd1f1',
'[DLLINK_MENU_BORD]' => '#f684cf',
  '[DLLINK_MENU_ARROW]' => '#808080',

);



$cols_neutral = array(

'[INPUT_BG]' => '#FFFFFF',
'[INPUT_HOV_BG]' => '#F8F8F8',
'[INPUT_FOC_BG]' => '#F0F0F0',
'[INPUT_FG]' => '#000000',

'[INPUT_ERROR_BORD]' => '#FF4040',
'[INPUT_ERROR_BG]' => '#FFD0D0',
'[INPUT_ERROR_FG]' => '#000000',
'[INPUT_ERROR_HOV_BORD]' => '#FF8080',
'[INPUT_ERROR_HOV_BG]' => '#FFF0F0',
'[INPUT_ERROR_FOC_BORD]' => '#FFA0A0',
'[INPUT_ERROR_FOC_BG]' => '#F0F0F0',
'[INPUT_ERROR_COMMENT]' => '#F04040',

'[MAIN_TITLE]' => '#000000',
'[COMMENT_OBLIVIONED]' => '#A00000',
'[ITEM_SKIPPED_FG]' => '#808080',
'[AJAX_SHADE_BG]' => '#FFFFE8',
'[AJAX_SHADE_FG]' => '#000000',
'[USERBAR_BG_PNG]' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQIHWP4nwYAAmcBZthYYgsAAAAASUVORK5CYII=', // old yellow one 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAF0lEQVR4XgXAgQwAAAACsAQDjOZqLawHBr0CZUVxOgwAAAAASUVORK5CYII='

'[USERNOTE_SUCCESS_BG]' => '#E0FFE0',
'[USERNOTE_SUCCESS_FG]' => '#208020',
'[USERNOTE_ALERT_BG]' => '#FFE0A0',
'[USERNOTE_ALERT_FG]' => '#805000',
'[USERNOTE_ERROR_BG]' => '#FFD0D0',
'[USERNOTE_ERROR_FG]' => '#A02020',
'[FORM_ERRORS_BG]' => '#FFE0E0',
'[FORM_ERRORS_FG]' => '#FF4040',
'[FORM_ERRORS_BORD]' => '#FF4040',

'[DLLINK_DEAD]' => '#A00000',
'[DLLINK_INACTIVE]' => '#909090',

'[GENERIC_BORDER]' => '#A0A0A0',

'[FOOTERLINE]' => '#606060',
'[HTMLBG]' => 'white',

'[SIDEIMGPROPS]' => 'left top no-repeat',
);




$cols_black = array(

'[BODYBG]' => '#070707',
'[BODYFG]' => '#cfcfcf',

'[SIDEBG]' => '#202020',
'[SIDEIMG]' => 'sideimg_133.jpg',
'[BETANOTE]' => '#13e313',
'[FEEDBACKLINK]' => '#ff7f00',

'[TIPNOTE]' => '#a3a300', // 000097
'[TIPNOTE_UL]' => '#FFA000', // 005fff

'[INPUT_BORD]' => '#5f5f5f',
'[INPUT_HOV_BORD]' => '#9f5f9f',
'[INPUT_FOC_BORD]' => '#9f2f9f',
'[REQUIRED_FIELD]' => '#dfd07f',

'[COMMENT_BORD]' => '#707070',
'[COMMENT_BG]' => '#171717',
'[COMMENT2_BG]' => '#272727',
'[COMMENT_PH_BG]' => '#070707',
'[LINKBUTTON_BORD]' => '#7684a4', // afaf6f
  '[LINKBUTTON_HOV_BG]' => '#000000',
  '[LINKBUTTON_HOV_FG]' => '#FFFFFF',
//'[LINKBUTTON_OLD_FG]' => '#5f5f5f', // is this used?

'[H2]' => '#dfdfdf',
/*'[LINK]' => '#208030',
'[LINK_HOV]' => '#20B050',
'[LINK_FOC]' => '#B03030',
'[LINK_OLD]' => '#A078A0',*/
  '[LINK]' => '#70c7ff',
  '[LINK_HOV]' => '#cdf0ff',
  '[LINK_FOC]' => '#629ef8',
  '[LINK_OLD]' => '#a579ff',


'[THEAD_BG]' => '#606060',
  '[THEAD_FG]' => '#000000',

'[LISTITEM_BG]' => '#0f0f0f',
'[LISTITEM2_BG]' => '#1d1d1d',
'[LISTITEM_PROC_BG]' => '#303030', 
  '[LISTITEM2_PROC_BG]' => '#D0B8D0', // is this used?
'[LISTITEM_BAD_FG]' => '#f30000', // 0cffff

/*'[DLLINK]' => '#40A840',
'[DLLINK_HOV]' => '#20C060',
'[DLLINK_OLD]' => '#907898',*/
  '[DLLINK]' => '#5088f0', // 7999ff
  '[DLLINK_HOV]' => '#82b1f2',
  '[DLLINK_OLD]' => '#a36af9',
'[DLLINK_MENU_BG]' => '#242424',
'[DLLINK_MENU_BORD]' => '#606060',
  '[DLLINK_MENU_ARROW]' => '#808080',

);


$cols_dbrown = array(

'[BODYBG]' => '#241c00',
'[BODYFG]' => '#d8d8d0',

'[SIDEBG]' => '#3a2600',
'[SIDEIMG]' => 'sideimg_134.jpg',
'[BETANOTE]' => '#e34913',
'[FEEDBACKLINK]' => '#c000ff',

'[TIPNOTE]' => '#0cd57d', // 000097
'[TIPNOTE_UL]' => '#48ffc9', // 005fff

'[INPUT_BORD]' => '#8c6f0a',
'[INPUT_HOV_BORD]' => '#aca203',
'[INPUT_FOC_BORD]' => '#d5e200',
'[REQUIRED_FIELD]' => '#de7fdf',

'[COMMENT_BORD]' => '#583c01',
'[COMMENT_BG]' => '#2c2400',
'[COMMENT2_BG]' => '#342a18',
'[COMMENT_PH_BG]' => '#140800',
'[LINKBUTTON_BORD]' => '#7692a4', // afaf6f
  '[LINKBUTTON_HOV_BG]' => '#000000',
  '[LINKBUTTON_HOV_FG]' => '#FFFFFF',
//'[LINKBUTTON_OLD_FG]' => '#5f5f5f', // is this used?

'[H2]' => '#e0d40e',
/*'[LINK]' => '#208030',
'[LINK_HOV]' => '#20B050',
'[LINK_FOC]' => '#B03030',
'[LINK_OLD]' => '#A078A0',*/
  '[LINK]' => '#70c7ff',
  '[LINK_HOV]' => '#cdf0ff',
  '[LINK_FOC]' => '#629ef8',
  '[LINK_OLD]' => '#a579ff',


'[THEAD_BG]' => '#b78a10',
  '[THEAD_FG]' => '#000000',

'[LISTITEM_BG]' => '#2c2400',
'[LISTITEM2_BG]' => '#342a18',
'[LISTITEM_PROC_BG]' => '#403c36', 
  '[LISTITEM2_PROC_BG]' => '#D0B8D0', // is this used?
'[LISTITEM_BAD_FG]' => '#f30000', // 0cffff

/*'[DLLINK]' => '#40A840',
'[DLLINK_HOV]' => '#20C060',
'[DLLINK_OLD]' => '#907898',*/
  '[DLLINK]' => '#5088f0', // 7999ff
  '[DLLINK_HOV]' => '#82b1f2',
  '[DLLINK_OLD]' => '#a36af9',
'[DLLINK_MENU_BG]' => '#291e00',
'[DLLINK_MENU_BORD]' => '#583c01',
  '[DLLINK_MENU_ARROW]' => '#808080',

);


$cols_dblue = array(

'[BODYBG]' => '#000024',
'[BODYFG]' => '#dfe4e5',

'[SIDEBG]' => '#00073a',
'[SIDEIMG]' => 'sideimg_141.jpg',
'[BETANOTE]' => '#1de313',
'[FEEDBACKLINK]' => '#fff600',

'[TIPNOTE]' => '#d50cc4', // 000097
'[TIPNOTE_UL]' => '#ff48e3', // 005fff

'[INPUT_BORD]' => '#0c7172',
'[INPUT_HOV_BORD]' => '#00847c',
'[INPUT_FOC_BORD]' => '#00e2ca',
'[REQUIRED_FIELD]' => '#df7f7f',

'[COMMENT_BORD]' => '#0d426f',
'[COMMENT_BG]' => '#060c42',
'[COMMENT2_BG]' => '#070535',
'[COMMENT_PH_BG]' => '#000214',
'[LINKBUTTON_BORD]' => '#7692a4', // afaf6f
  '[LINKBUTTON_HOV_BG]' => '#000000',
  '[LINKBUTTON_HOV_FG]' => '#FFFFFF',
//'[LINKBUTTON_OLD_FG]' => '#5f5f5f', // is this used?

'[H2]' => '#78c9e7',
/*'[LINK]' => '#208030',
'[LINK_HOV]' => '#20B050',
'[LINK_FOC]' => '#B03030',
'[LINK_OLD]' => '#A078A0',*/
  '[LINK]' => '#70c7ff',
  '[LINK_HOV]' => '#cdf0ff',
  '[LINK_FOC]' => '#629ef8',
  '[LINK_OLD]' => '#a579ff',


'[THEAD_BG]' => '#1985ea',
  '[THEAD_FG]' => '#000000',

'[LISTITEM_BG]' => '#060c42',
'[LISTITEM2_BG]' => '#090731',
'[LISTITEM_PROC_BG]' => '#363940', 
  '[LISTITEM2_PROC_BG]' => '#D0B8D0', // is this used?
'[LISTITEM_BAD_FG]' => '#f30000', // 0cffff

/*'[DLLINK]' => '#40A840',
'[DLLINK_HOV]' => '#20C060',
'[DLLINK_OLD]' => '#907898',*/
  '[DLLINK]' => '#5088f0', // 7999ff
  '[DLLINK_HOV]' => '#82b1f2',
  '[DLLINK_OLD]' => '#a36af9',
'[DLLINK_MENU_BG]' => '#020c3c',
'[DLLINK_MENU_BORD]' => '#0d426f',
  '[DLLINK_MENU_ARROW]' => '#808080',

);

$cols_dpurple = array(

'[BODYBG]' => '#200020',
'[BODYFG]' => '#e3dfe5',

'[SIDEBG]' => '#280038',
'[SIDEIMG]' => 'sideimg_142.jpg',
'[BETANOTE]' => '#1de313',
'[FEEDBACKLINK]' => '#fff600',

'[TIPNOTE]' => '#0cd57d', // 000097
'[TIPNOTE_UL]' => '#48ffc9', // 005fff

'[INPUT_BORD]' => '#6e7500',
'[INPUT_HOV_BORD]' => '#7f8400',
'[INPUT_FOC_BORD]' => '#c9cc02',
'[REQUIRED_FIELD]' => '#df7f7f',

'[COMMENT_BORD]' => '#621570',
'[COMMENT_BG]' => '#320742',
'[COMMENT2_BG]' => '#2d0837',
'[COMMENT_PH_BG]' => '#0d0014',
'[LINKBUTTON_BORD]' => '#b969ab', // afaf6f
  '[LINKBUTTON_HOV_BG]' => '#000000',
  '[LINKBUTTON_HOV_FG]' => '#FFFFFF',
//'[LINKBUTTON_OLD_FG]' => '#5f5f5f', // is this used?

'[H2]' => '#f7a0ff',
/*'[LINK]' => '#208030',
'[LINK_HOV]' => '#20B050',
'[LINK_FOC]' => '#B03030',
'[LINK_OLD]' => '#A078A0',*/
  '[LINK]' => '#70c7ff',
  '[LINK_HOV]' => '#cdf0ff',
  '[LINK_FOC]' => '#629ef8',
  '[LINK_OLD]' => '#a579ff',


'[THEAD_BG]' => '#c23fff',
  '[THEAD_FG]' => '#000000',

'[LISTITEM_BG]' => '#350746',
'[LISTITEM2_BG]' => '#280731',
'[LISTITEM_PROC_BG]' => '#3c3640', 
  '[LISTITEM2_PROC_BG]' => '#D0B8D0', // is this used?
'[LISTITEM_BAD_FG]' => '#f30000', // 0cffff

/*'[DLLINK]' => '#40A840',
'[DLLINK_HOV]' => '#20C060',
'[DLLINK_OLD]' => '#907898',*/
  '[DLLINK]' => '#5088f0', // 7999ff
  '[DLLINK_HOV]' => '#82b1f2',
  '[DLLINK_OLD]' => '#a36af9',
'[DLLINK_MENU_BG]' => '#2e023c',
'[DLLINK_MENU_BORD]' => '#5b0d6f',
  '[DLLINK_MENU_ARROW]' => '#808080',
);

$cols_dred = array(

'[BODYBG]' => '#180000',
'[BODYFG]' => '#e5e1df',

'[SIDEBG]' => '#310A05',
'[SIDEIMG]' => 'sideimg_143.jpg',
'[BETANOTE]' => '#1de313',
'[FEEDBACKLINK]' => '#fff600',

'[TIPNOTE]' => '#c4d50c', // 000097
'[TIPNOTE_UL]' => '#f4ff48', // 005fff

'[INPUT_BORD]' => '#696100',
'[INPUT_HOV_BORD]' => '#847f00',
'[INPUT_FOC_BORD]' => '#dae200',
'[REQUIRED_FIELD]' => '#d77fdf',

'[COMMENT_BORD]' => '#643020',
'[COMMENT_BG]' => '#330606',
'[COMMENT2_BG]' => '#270909',
'[COMMENT_PH_BG]' => '#140003',
'[LINKBUTTON_BORD]' => '#a47c76', // afaf6f
  '[LINKBUTTON_HOV_BG]' => '#000000',
  '[LINKBUTTON_HOV_FG]' => '#FFFFFF',
//'[LINKBUTTON_OLD_FG]' => '#5f5f5f', // is this used?

'[H2]' => '#FF826B',
/*'[LINK]' => '#208030',
'[LINK_HOV]' => '#20B050',
'[LINK_FOC]' => '#B03030',
'[LINK_OLD]' => '#A078A0',*/
  '[LINK]' => '#70c7ff',
  '[LINK_HOV]' => '#cdf0ff',
  '[LINK_FOC]' => '#629ef8',
  '[LINK_OLD]' => '#a579ff',


'[THEAD_BG]' => '#CC3F2C',
  '[THEAD_FG]' => '#000000',

'[LISTITEM_BG]' => '#250000',
'[LISTITEM2_BG]' => '#3A190F',
'[LISTITEM_PROC_BG]' => '#403b36', 
  '[LISTITEM2_PROC_BG]' => '#D0B8D0', // is this used?
'[LISTITEM_BAD_FG]' => '#f3bd00', // 0cffff

/*'[DLLINK]' => '#40A840',
'[DLLINK_HOV]' => '#20C060',
'[DLLINK_OLD]' => '#907898',*/
  '[DLLINK]' => '#5088f0', // 7999ff
  '[DLLINK_HOV]' => '#82b1f2',
  '[DLLINK_OLD]' => '#a36af9',
'[DLLINK_MENU_BG]' => '#310E05',
'[DLLINK_MENU_BORD]' => '#643020',
  '[DLLINK_MENU_ARROW]' => '#808080',

);

$cols_dteal = array(

'[BODYBG]' => '#001010',
'[BODYFG]' => '#dfe5e5',

'[SIDEBG]' => '#0a2629',
'[SIDEIMG]' => 'sideimg_144.jpg',
'[BETANOTE]' => '#e3b013',
'[FEEDBACKLINK]' => '#5aff00',

'[TIPNOTE]' => '#b54c06', // 000097
'[TIPNOTE_UL]' => '#dadc1c', // 005fff

'[INPUT_BORD]' => '#024e10',
'[INPUT_HOV_BORD]' => '#008406',
'[INPUT_FOC_BORD]' => '#5fbe0a',
'[REQUIRED_FIELD]' => '#df7f7f',

'[COMMENT_BORD]' => '#16464f',
'[COMMENT_BG]' => '#002a28',
'[COMMENT2_BG]' => '#07201e',
'[COMMENT_PH_BG]' => '#001010',
'[LINKBUTTON_BORD]' => '#6fa38d', // afaf6f
  '[LINKBUTTON_HOV_BG]' => '#000000',
  '[LINKBUTTON_HOV_FG]' => '#FFFFFF',
//'[LINKBUTTON_OLD_FG]' => '#5f5f5f', // is this used?

'[H2]' => '#b0fef1',
/*'[LINK]' => '#208030',
'[LINK_HOV]' => '#20B050',
'[LINK_FOC]' => '#B03030',
'[LINK_OLD]' => '#A078A0',*/
  '[LINK]' => '#70c7ff',
  '[LINK_HOV]' => '#cdf0ff',
  '[LINK_FOC]' => '#629ef8',
  '[LINK_OLD]' => '#a579ff',


'[THEAD_BG]' => '#0dc2cd',
  '[THEAD_FG]' => '#000000',

'[LISTITEM_BG]' => '#011b17',
'[LISTITEM2_BG]' => '#082a28',
'[LISTITEM_PROC_BG]' => '#3a403f', 
  '[LISTITEM2_PROC_BG]' => '#D0B8D0', // is this used?
'[LISTITEM_BAD_FG]' => '#f39500', // 0cffff

/*'[DLLINK]' => '#40A840',
'[DLLINK_HOV]' => '#20C060',
'[DLLINK_OLD]' => '#907898',*/
  '[DLLINK]' => '#5088f0', // 7999ff
  '[DLLINK_HOV]' => '#82b1f2',
  '[DLLINK_OLD]' => '#a36af9',
'[DLLINK_MENU_BG]' => '#042927',
'[DLLINK_MENU_BORD]' => '#1c5854',
  '[DLLINK_MENU_ARROW]' => '#808080',

);


$cols_dgrey = array(

'[BODYBG]' => '#333333',
'[BODYFG]' => '#e5e5e5',

'[SIDEBG]' => '#4d4d4d',
'[SIDEIMG]' => 'sideimg_151.jpg',
'[BETANOTE]' => '#13e313',
'[FEEDBACKLINK]' => '#fd47f4',

'[TIPNOTE]' => '#fff1d7', // 000097
'[TIPNOTE_UL]' => '#fff000', // 005fff

'[INPUT_BORD]' => '#999999',
'[INPUT_HOV_BORD]' => '#b3b23b',
'[INPUT_FOC_BORD]' => '#cbca43',
'[REQUIRED_FIELD]' => '#eede88',

'[COMMENT_BORD]' => '#969696',
'[COMMENT_BG]' => '#3b3b3b',
'[COMMENT2_BG]' => '#4a4a4a',
'[COMMENT_PH_BG]' => '#333333',
'[LINKBUTTON_BORD]' => '#7b89ab', // afaf6f
  '[LINKBUTTON_HOV_BG]' => '#000000',
  '[LINKBUTTON_HOV_FG]' => '#FFFFFF',
//'[LINKBUTTON_OLD_FG]' => '#5f5f5f', // is this used?

'[H2]' => '#f8f8f8',
/*'[LINK]' => '#208030',
'[LINK_HOV]' => '#20B050',
'[LINK_FOC]' => '#B03030',
'[LINK_OLD]' => '#A078A0',*/
  '[LINK]' => '#70c7ff',
  '[LINK_HOV]' => '#cdf0ff',
  '[LINK_FOC]' => '#629ef8',
  '[LINK_OLD]' => '#a579ff',


'[THEAD_BG]' => '#a6a6a6',
  '[THEAD_FG]' => '#000000',

'[LISTITEM_BG]' => '#3b3b3b',
'[LISTITEM2_BG]' => '#474747',
'[LISTITEM_PROC_BG]' => '#636363', 
  '[LISTITEM2_PROC_BG]' => '#D0B8D0', // is this used?
'[LISTITEM_BAD_FG]' => '#f30000', // 0cffff

/*'[DLLINK]' => '#40A840',
'[DLLINK_HOV]' => '#20C060',
'[DLLINK_OLD]' => '#907898',*/
  '[DLLINK]' => '#5088f0', // 7999ff
  '[DLLINK_HOV]' => '#82b1f2',
  '[DLLINK_OLD]' => '#a36af9',
'[DLLINK_MENU_BG]' => '#4d4d4d',
'[DLLINK_MENU_BORD]' => '#909090',
  '[DLLINK_MENU_ARROW]' => '#909090',

'[SIDEIMGPROPS]' => 'left top repeat-y',
);

$cols_dgreen = array(

'[BODYBG]' => '#001000',
'[BODYFG]' => '#dfe5df',

'[SIDEBG]' => '#092408',
'[SIDEIMG]' => 'sideimg_152.jpg',
'[BETANOTE]' => '#e3b013',
'[FEEDBACKLINK]' => '#fcff00',

'[TIPNOTE]' => '#f71c79', // 000097
'[TIPNOTE_UL]' => '#dadc1c', // 005fff

'[INPUT_BORD]' => '#4d4e02',
'[INPUT_HOV_BORD]' => '#847c00',
'[INPUT_FOC_BORD]' => '#beab0a',
'[REQUIRED_FIELD]' => '#df7f7f',

'[COMMENT_BORD]' => '#274f16',
'[COMMENT_BG]' => '#122d00',
'[COMMENT2_BG]' => '#062611',
'[COMMENT_PH_BG]' => '#001000',
'[LINKBUTTON_BORD]' => '#6fa379', // afaf6f
  '[LINKBUTTON_HOV_BG]' => '#000000',
  '[LINKBUTTON_HOV_FG]' => '#FFFFFF',
//'[LINKBUTTON_OLD_FG]' => '#5f5f5f', // is this used?

'[H2]' => '#cdffca',
/*'[LINK]' => '#208030',
'[LINK_HOV]' => '#20B050',
'[LINK_FOC]' => '#B03030',
'[LINK_OLD]' => '#A078A0',*/
  '[LINK]' => '#70c7ff',
  '[LINK_HOV]' => '#cdf0ff',
  '[LINK_FOC]' => '#629ef8',
  '[LINK_OLD]' => '#a579ff',


'[THEAD_BG]' => '#52d345',
  '[THEAD_FG]' => '#000000',

'[LISTITEM_BG]' => '#052301',
'[LISTITEM2_BG]' => '#063207',
'[LISTITEM_PROC_BG]' => '#303530', 
  '[LISTITEM2_PROC_BG]' => '#D0B8D0', // is this used?
'[LISTITEM_BAD_FG]' => '#f39500', // 0cffff

/*'[DLLINK]' => '#40A840',
'[DLLINK_HOV]' => '#20C060',
'[DLLINK_OLD]' => '#907898',*/
  '[DLLINK]' => '#5088f0', // 7999ff
  '[DLLINK_HOV]' => '#82b1f2',
  '[DLLINK_OLD]' => '#a36af9',
'[DLLINK_MENU_BG]' => '#062904',
'[DLLINK_MENU_BORD]' => '#2e581c',
  '[DLLINK_MENU_ARROW]' => '#808080',

);





$cols_inverted = array(

'[INPUT_BG]' => '#000000',
'[INPUT_HOV_BG]' => '#070707',
'[INPUT_FOC_BG]' => '#101010',
'[INPUT_FG]' => '#FFFFFF',

'[INPUT_ERROR_BORD]' => '#be0000',
'[INPUT_ERROR_BG]' => '#410000',
'[INPUT_ERROR_FG]' => '#FFFFFF',
'[INPUT_ERROR_HOV_BORD]' => '#920000',
'[INPUT_ERROR_HOV_BG]' => '#300000',
'[INPUT_ERROR_FOC_BORD]' => '#D00000',
'[INPUT_ERROR_FOC_BG]' => '#101010',
'[INPUT_ERROR_COMMENT]' => '#c10d0d',

'[MAIN_TITLE]' => '#FFFFFF',
'[COMMENT_OBLIVIONED]' => '#F08080',
'[ITEM_SKIPPED_FG]' => '#808080',
'[AJAX_SHADE_BG]' => '#000018',
'[AJAX_SHADE_FG]' => '#FFFFFF',
'[USERBAR_BG_PNG]' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQIHWNgSAMAAGkAZ9QfhWEAAAAASUVORK5CYII=',

'[USERNOTE_SUCCESS_BG]' => '#006000',
'[USERNOTE_SUCCESS_FG]' => '#C0F0C0',
'[USERNOTE_ALERT_BG]' => '#604000',
'[USERNOTE_ALERT_FG]' => '#F0D080',
'[USERNOTE_ERROR_BG]' => '#800000',
'[USERNOTE_ERROR_FG]' => '#F0C0C0',
'[FORM_ERRORS_BG]' => '#3d0000',
'[FORM_ERRORS_FG]' => '#bc0000',
'[FORM_ERRORS_BORD]' => '#bf0000',

'[DLLINK_DEAD]' => '#F08080',
'[DLLINK_INACTIVE]' => '#707070',

'[GENERIC_BORDER]' => '#606060',

'[FOOTERLINE]' => '#A0A0A0',
'[HTMLBG]' => 'black',

'[SIDEIMGPROPS]' => 'left top no-repeat',
);


//col_swp($cols);


file_put_contents('style_113.css', strtr($data, array_merge($cols_neutral, $cols_yellow)));
file_put_contents('style_114.css', strtr($data, array_merge($cols_neutral, $cols_blue)));
file_put_contents('style_121.css', strtr($data, array_merge($cols_neutral, $cols_purple)));
file_put_contents('style_122.css', strtr($data, array_merge($cols_neutral, $cols_green)));
file_put_contents('style_123.css', strtr($data, array_merge($cols_neutral, $cols_red)));
file_put_contents('style_124.css', strtr($data, array_merge($cols_neutral, $cols_orange)));
file_put_contents('style_131.css', strtr($data, array_merge($cols_neutral, $cols_white)));
file_put_contents('style_132.css', strtr($data, array_merge($cols_neutral, $cols_pink)));
file_put_contents('style_133.css', strtr($data, array_merge($cols_inverted, $cols_black)));
file_put_contents('style_134.css', strtr($data, array_merge($cols_inverted, $cols_dbrown)));
file_put_contents('style_141.css', strtr($data, array_merge($cols_inverted, $cols_dblue)));
file_put_contents('style_142.css', strtr($data, array_merge($cols_inverted, $cols_dpurple)));
file_put_contents('style_143.css', strtr($data, array_merge($cols_inverted, $cols_dred)));
file_put_contents('style_144.css', strtr($data, array_merge($cols_inverted, $cols_dteal)));
file_put_contents('style_151.css', strtr($data, array_merge($cols_inverted, $cols_dgrey)));
file_put_contents('style_152.css', strtr($data, array_merge($cols_inverted, $cols_dgreen)));
