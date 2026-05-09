<?php
if(!defined('AT_ROOT')) exit;

class AT_Output
{
	public $title='';
	public $head_metas=array();
	public $head_extra_html='';
	public $foot_extra_html=''; // just before </body>
	public $theme='';
	
	private $config = array();
	private $notices = array();
	
	function __construct($cookiename, $cookiedomain, $cookiepath) {
		$this->config['cookiename'] = $cookiename;
		$this->config['cookiedomain'] = $cookiedomain;
		$this->config['cookiepath'] = $cookiepath;
	}
	
	public function setCookie($name, $value='', $expire=0) {
		if(!$this->config['cookiename']) return; // this allows disabling cookies altogether from the config
		if($expire === 0)
			$expire = TIME_NOW + AT::DEFAULT_COOKIE_EXPIRE; // expire in a year by default
		setcookie($this->config['cookiename'].'['.$name.']', $value, $expire, $this->config['cookiepath'], $this->config['cookiedomain']);
	}
	
	const NOTICE_SUCCESS = 'success';
	const NOTICE_ALERT = 'alert';
	const NOTICE_ERROR = 'error';
	public function addNotice($msg, $type=self::NOTICE_SUCCESS) {
		$this->notices[] = array($msg, $type);
	}
	
	
	/**
	 * Outputs the header
	 * REQUIRES: $this->title, $this->head_metas, $this->head_extra_html, user stuff
	 */
	public function printHeader($expire=7200, $lastmod=0, $simple_header=false)
	{
		header('Content-Type: text/html; charset=UTF-8');
		
		// send cache headers
		header('Expires: '.gmdate('D, d M Y H:i:s', TIME_NOW+$expire).' GMT');
		if(!$lastmod) {
			if($expire)
				// make up something random - it should work in most cases though
				$lastmod = floor(TIME_NOW / $expire) * $expire;
			else
				$lastmod = TIME_NOW;
		}
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastmod).' GMT');
		if(!$expire)
		{
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
			header('X-Accel-Expires: 0');
		} else {
			//header('Cache-Control: public');
			header('Cache-Control: private');
		}
		
		include AT_ROOT.'includes/output_header.php';
	}
	
	public function printFooter($simple=false) {
		if($simple) echo '</body></html>';
		else {
			global $AT;
			include AT_ROOT.'includes/output_footer'.(defined('USE_MINIMAL_FOOTER')?'_minimal':'').'.php';
		}
	}
	
	public function printTitleAndDesc($title, $desc='', $breadcrumbs=array())
	{
		echo '
			<div id="title_desc">';
		echo '
				<div id="nav_bc"><a href="', AT::buildUrl('home'), '">'.SITE_NAME.' Home</a>';
		foreach($breadcrumbs as $link => &$text)
			echo ' &raquo; <a href="', $link, '">', $text, '</a>';
		echo ' &raquo; </div>';
		echo '
				<h2 id="title">', $title, '</h2>';
		if($desc)
			echo '
				<p class="description" id="description">', $desc, '</p>';
		echo '
			</div>
		';
	}
	
	public function printUserBar(&$user)
	{
		if($user->uid)
			echo '<!--[if lt IE 8]>Welcome<![endif]--><![if gte IE 8]><span class="image16 icon_user" title="You are logged in"></span><![endif]> '.$user->data['username'].'
			<div id="userbar_extlinks"><a href="'.AT::buildUrl('usercp').'" rel="nofollow">Account Settings</a><br />
			<a href="'.AT::buildUrl('logout', '', array('postkey' => $GLOBALS['AT']->postKey)).'" rel="nofollow">Logout</a></div>';
		else {
			?>
				<form action="" method="post" id="userbar_loginform">
				<div><label>
					<span class="label">User:</span><input type="text" class="text" name="login_username" value="" />
				</label></div>
				<div><label>
					<span class="label">Pass:</span><input type="password" class="text" name="login_password" value="" />
				</label></div>
				<div>
					<input type="submit" class="submit" value="Login" style="float: right;" />
					<label class="check_label">Remember <input type="checkbox" class="checkbox" name="login_remember" value="1" /></label>
					<input type="hidden" name="postkey" value="<?php echo $GLOBALS['AT']->postKey; ?>" />
					<input type="hidden" name="do" value="login" />
				</div>
				</form>
				<div id="userbar_extlinks">
					<a href="<?php echo AT::buildUrl('login'); ?>" rel="nofollow">Forgot Password</a><br />
					<a href="<?php echo AT::buildUrl('register'); ?>" rel="nofollow">Register an Account</a>
				</div>
			<?php
		}
	}
	
	
	public static function staticFileUrl($file, $args='', $html=true) {
		$url = INC_URL.'/'.$file.'?';
		if($args) $args .= '&';
		$args .= 'cache='.md5(filemtime(INC_ROOT.$file));
		$url .= $args;
		
		// htmlspecialchars shouldn't actually have an effect, but meh
		if($html) $url = htmlspecialchars($url);
		return $url;
	}
	
	public function setTheme($theme) {
		if(file_exists(INC_ROOT.'style_'.$theme.'.css'))
			$this->theme = '_'.$theme;
	}
	
	const ERROR_BADREQ = 400;
	const ERROR_FORBIDDEN = 403;
	const ERROR_NOTFOUND = 404;
	const ERROR_SERVER = 500;
	const ERROR_UNAVAIL = 503;
	
	public static function error($message, $title='', $type = self::ERROR_SERVER)
	{
		if(is_string($type))
			header('HTTP/1.1 '.$type);
		else switch($type)
		{
			case self::ERROR_BADREQ: header('HTTP/1.1 400 Bad Request'); break;
			case self::ERROR_FORBIDDEN: header('HTTP/1.1 403 Forbidden'); break;
			case self::ERROR_NOTFOUND: header('HTTP/1.1 404 Not Found'); break;
			case self::ERROR_SERVER: header('HTTP/1.1 500 Internal Server Error'); break;
			case self::ERROR_UNAVAIL: header('HTTP/1.1 503 Service Unavailable'); break;
		}
		
		// always send no-cache headers for error page
		header('Expires: Sat, 1 Jan 2000 01:00:00 GMT');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		
		if(isset($GLOBALS['AT']->ajax_request) && $GLOBALS['AT']->ajax_request)
		{
			// send xml or plaintext error?
			// stick to plaintext
			echo '<error>'.strip_tags($message).'</error>';
		}
		else
		{
			header('Content-Type: text/html; charset=UTF-8');
			
			if(!$title) $title = 'An error has occurred';
			include AT_ROOT.'includes/output_error.php';
		}
		AT_Shutdown();
	}
	
	public static function redirect($url, $text=null)
	{
		// always send no-cache headers for redirect page
		header('Expires: Sat, 1 Jan 2000 01:00:00 GMT');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		
		if($text===null) {
			header('Location: '.$url);
			AT_Shutdown();
		} else {
			header('Content-Type: text/html; charset=UTF-8');
			include AT_ROOT.'includes/output_redirect.php';
		}
	}
	
	
	
	
	// Verifies page number inputs, and generates pagination links
	public static function handleMultiPage($num, &$perpage, &$page, $url,$force_first=false)
	{
		$page = intval($page);
		if($page < 1) $page = 1;
		$perpage = intval($perpage);
		if($perpage < 1) $perpage = 1;
		$pages = ceil($num/$perpage);
		if($page > $pages) $page = $pages;
		
		if($pages < 2) return '';
		
		// we'll have a 10 page cutoff
		if($pages > 10)
		{
			// at least display two numbers beside each point
			if($page < 6) { // at the lower end
				$pages_array = array_merge(range(1, 7), array($pages-1, $pages));
			}
			elseif($page > $pages-6) { // at the upper end
				$pages_array = array_merge(array(1, 2), range($pages-6, $pages));
			}
			else { // somewhere in the middle
				$pages_array = array_merge(array(1, 2), range($page-2, $page+2), array($pages-1, $pages));
			}
		}
		else
		{
			// just a simple display of the pages
			$pages_array = range(1, $pages);
		}
		
		$multipage = '';
		$prev_page = reset($pages_array)-1; // just a trick...
		foreach($pages_array as &$i)
		{
			if($i-1 != $prev_page) // there was a gap somewhere...
				$multipage .= ' ... ';
			$prev_page = $i;
			
			if($page == $i)
				$multipage .= '<span class="multipage_current">'.$i.'</span>';
			else {
				if($i == 1 && !$force_first) {
					// TODO: bad ugly hack...
					$pageurl = preg_replace(array(
						'~[?&]page=__PAGE__$~',
						'~(?<=[?&])page=__PAGE__&~'
					), '', $url);
				}
				else
					$pageurl = str_replace('__PAGE__', $i, $url);
				$multipage .= '<a href="'.$pageurl.'" title="Go to page '.$i.'"><span class="multipage_page">'.$i.'</span></a>';
			}
		}
		
		return '<div class="pagination">Page: '.$multipage.'</div>';
	}
	
	// handles most multi-page pages directly from input source
	public static function stdMultipageHandler(&$input, $numitems, $url_action, $url_subaction='', $url_args=array(), $order_bys=array(), $perpage_def=20, $perpage_max=200, $force_first=false)
	{
		if(!$numitems) return false;
		$input->parseMultiPage($numitems, $order_bys, $perpage_def, $perpage_max);
		
		
		$url_args['page'] = '__PAGE__';
		if($input->got('perpage'))
			$url_args['perpage'] = $input->perpage;
		if(!empty($order_bys) && $input->got('orderby')) {
			foreach($input->orderby as $k => &$v) {
				$url_args['orderby['.$k.']'] = $v;
				$url_args['orderasc['.$k.']'] = $input->orderasc[$k];
			}
		}
		
		return self::handleMultiPage($numitems, $input->perpage, $input->page, AT::buildUrl($url_action, $url_subaction, $url_args), $force_first);
	}
	
	
}
?>