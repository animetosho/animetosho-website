<?php
if(!defined('AT_ROOT')) exit;


/*
 * ~~~~~~~~ NOTE! textbox, textarea, checkbox, list etc RETURN, not echo  ~~~~~~~~
 */

class AT_Output_Form
{
	public $errors = array();
	private $input = null;
	
	public $id='';
	private $id_prefix='';
	private $jsfuncs = array();
	
	function __construct(&$input)
	{
		$this->input = &$input;
	}
	
	public function start($action, $postkey='', $id='', $validatejs='', $resetjs='') {
		$this->id = $id;
		if($id) {
			$js_stuff = ' id="'.$id.'" onreset="'.$resetjs.'return (AJS.resetFormErrors.bind(AJS))(this,false);" onsubmit="return AJS.validateForm(this)';
			if($validatejs)
				$js_stuff .= ' &amp;&amp; (function(){'.htmlspecialchars(strtr($validatejs, array("\n" => '', "\r" => '', "\t" => ''))).' return true;}.bind(this))()';
			
			$js_stuff .= ';"';
			
			$this->id_prefix = $id.'_';
		}
		else
			$js_stuff='';
		echo '<form action="', $action, '" method="', ($postkey ? 'post" enctype="multipart/form-data' : 'get') ,'"', $js_stuff, '>';
		if($postkey)
			echo '<div style="display: none;"><input type="hidden" name="postkey" value="', $postkey, '" /></div>';
		if(!empty($this->errors[0]))
		{
			// display errors here
			if(is_array($this->errors[0]))
				$errors = '<strong>The following errors occurred:</strong><ul><li>'.implode('</li><li>', $this->errors[0]).'</li></ul>';
			else
				$errors = '<strong>'.$this->errors[0].'</strong>';
			echo '<div class="form_errors">', $errors, '</div>';
			//unset the error so subsequent forms aren't affected
			unset($this->errors[0]);
		}
	}
	
	public function end() {
		echo '</form>';
		// echo out javascript stuff
		if($this->id) {
			$v = $this->id.'_Validator';
			?>
			<script type="text/javascript">
			<!--
				window.<?php echo $v; ?> = {
					<?php
					$d='';
					foreach($this->jsfuncs as $elem => &$js) {
						echo $d, $elem, '_validate: function() {
							var t=this.value;
							', $js, '
							return false;
						}';
						if(!$d) $d = ', ';
					}
					?>
				};
				
				//$(document).ready(function() {
					AJS.formValidatorAttach("<?php echo $this->id; ?>");
					var e=$id("<?php echo $this->id; ?>").elements;
					for(i=0; i<e.length; i++) {
						if(ep = $id(e[i].id+"_error"))
							AJS.frmDefMsgs[e[i].id] = ep.innerHTML;
					}
				//});
			//-->
			</script>
			<?php
			
		}
		$this->id = '';
		$this->jsfuncs = array();
		
	}
	
	
	public function textboxS($nameid, $defvalue='', $pwdfield=false, $maxlen=null, $size=null, $xhtml=null, $js='')
	{
		if($js) $this->jsfuncs[$nameid] = $js;
		$xargs = '';
		if(isset($maxlen)) $xargs .= ' maxlength="'.$maxlen.'"';
		if(isset($size)) $xargs .= ' size="'.$size.'"';
		
		if($this->input->got($nameid))
			$defvalue = $this->input->get(AT_Input::TYPE_STR, $nameid);
		
		if(isset($xhtml)) $xargs .= ' '.$xhtml;
		$haserror = (isset($this->errors[$nameid]) && $this->errors[$nameid]);
		return '<input type="'.($pwdfield?'password':'text').'" class="text'.($haserror?'_error':'').'" name="'.$nameid.'" id="'.$this->id_prefix.$nameid.'" value="'.htmlspecialchars_uni($defvalue).'"'.$xargs.' />'.$this->getErrorSpan($nameid);
	}
	
	public function fileS($nameid, $size=null, $xhtml=null)
	{
		// enforce that this function is only called _once_
		assert('call_user_func(create_function("", \'global $_c;if(isset($_c))return !$_c;return ($_c=true);\'))');
		
		$xargs = '';
		if(isset($size)) $xargs .= ' size="'.$size.'"';
		
		if(isset($xhtml)) $xargs .= ' '.$xhtml;
		$haserror = (isset($this->errors[$nameid]) && $this->errors[$nameid]);
		return '<input type="file" class="file'.($haserror?'_error':'').'" name="'.$nameid.'" id="'.$this->id_prefix.$nameid.'"'.$xargs.' />'.$this->getErrorSpan($nameid);
	}
	
	public function textareaS($nameid, $defvalue='', $rows=null, $cols=null, $xhtml=null, $js='')
	{
		if($js) $this->jsfuncs[$nameid] = $js;
		$xargs = '';
		if(isset($cols)) $xargs .= ' cols="'.$cols.'"';
		if(isset($rows)) $xargs .= ' rows="'.$rows.'"';
		
		if($this->input->got($nameid))
			$defvalue = $this->input->get(AT_Input::TYPE_STR, $nameid);
		
		if(isset($xhtml)) $xargs .= ' '.$xhtml;
		$haserror = (isset($this->errors[$nameid]) && $this->errors[$nameid]);
		return '<textarea class="text'.($haserror?'_error':'').'" name="'.$nameid.'" id="'.$this->id_prefix.$nameid.'" '.$xargs.'>'.htmlspecialchars_uni($defvalue).'</textarea>'.$this->getErrorSpan($nameid);
	}
	
	public function editorS($nameid, $defvalue='', $rows=null, $cols=null, $xhtml=null, $js='')
	{
		// currently just map to the textarea
		return $this->textareaS($nameid, $defvalue, $rows, $cols, $xhtml, $js);
	}
	
	public function checkboxS($nameid, $defchk=false, $label='', $xhtml=null)
	{
		$xargs = '';
		if($this->input->got($nameid))
			$defchk = $this->input->get(AT_Input::TYPE_BOOL, $nameid);
		if($defchk) $xargs .= ' checked="checked"';
		if(isset($xhtml)) $xargs .= ' '.$xhtml;
		return '<label><input type="checkbox" class="checkbox" name="'.$nameid.'" id="'.$this->id_prefix.$nameid.'" value="1"'.$xargs.' />'.$label.'</label>'.$this->getErrorSpan($nameid);
	}
	
	public function selectS($nameid, $options=array(), $defvalue=null, $xhtml=null)
	{
		if($this->input->got($nameid))
			$defvalue = $this->input->get(AT_Input::TYPE_STR, $nameid);
		
		if(isset($xhtml)) $xhtml .= ' '.$xhtml;
		$haserror = (isset($this->errors[$nameid]) && $this->errors[$nameid]);
		
		$ret = '<select name="'.$nameid.'" id="'.$this->id_prefix.$nameid.'" class="select'.($haserror?'_error':'').'"'.$xhtml.'>';
		foreach($options as $k => &$v) {
			if($k == $defvalue)
				$sel = ' selected="selected"';
			else
				$sel = '';
			$ret .= "\r\n\t".'<option value="'.$k.'"'.$sel.'>'.htmlspecialchars_uni($v).'</option>';
		}
		return $ret."\r\n".'</select>'.$this->getErrorSpan($nameid);
	}
	
	public static function hidden($fields) {
		echo '<div style="display: none;">';
		foreach($fields as $n => &$v)
			echo '<input type="hidden" name="', $n,'" value="', htmlspecialchars_uni($v), '" />';
		echo '</div>';
	}
	public function submit($text, $name='') {
		if($name) {
			$x = ' name="'.$name.'"';
			$sx = '_'.$name;
		}
		else
			$sx = $x = '';
		echo '<input type="submit" id="', $this->id_prefix, 'submit', $sx, '" class="submit" value="', htmlspecialchars_uni($text), '"', $x, ' />';
	}
	public function reset($text='Reset') {
		if($text)
			$x = ' value="'.$text.'"';
		else
			$x = '';
		echo '<input type="reset" id="', $this->id_prefix, 'reset" class="button"', $x, ' />';
	}
	public function endButtons($text) {
		$this->submit($text);
		echo ' ';
		$this->reset();
	}
	
	private function getErrorText($nameid)
	{
		if(isset($this->errors[$nameid]) && $this->errors[$nameid])
			return $this->errors[$nameid];
		else
			return '';
	}
	
	private function getErrorSpan($nameid) {
		$errortext = $this->getErrorText($nameid);
		return '<span class="errortext" id="'.$this->id_prefix.$nameid.'_error" style="display: '.($errortext?'block':'none').';">'.$errortext.'</span>';
	}
	
	
	public function timezoneListS($nameid='timezone', $select_offset=0)
	{
		if($this->input->got($nameid))
			$select_offset = $this->input->get(AT_Input::TYPE_FLOAT, $nameid);
		$return = '<select name="'.$nameid.'" id="'.$this->id_prefix.$nameid.'">';
		
		$offsets = array(-12, -11, -10, -9, -8, -7, -6, -5, -4.5, -4, -3.5, -3, -2, -1, 0, 1, 2, 3, 3.5, 4, 4.5, 5, 5.5, 5.75, 6, 6.5, 7, 8, 9, 9.5, 10, 10.5, 11, 12);
		if(!in_array($select_offset, $offsets))
			$select_offset = 0;
		foreach($offsets as &$o)
		{
			$gmt_offset = '';
			if($o)
			{
				if($o > 0) $gmt_offset = '+';
				$gmt_offset .= intval($o).':'.str_pad(abs(intval(($o - intval($o)) * 60)), 2, '0', STR_PAD_LEFT).' Hours ';
			}
			$return .= '	<option value="'.$o.'"'.($select_offset == $o ? ' selected="selected"' : '').'>GMT '.$gmt_offset.'('.gmdate('h:i A', TIME_NOW+$o*3600).')</option>';
		}
		$return .= '</select>'.$this->getErrorSpan($nameid);
		// need to add in Javascript auto detection
		
		
		return $return;
	}

}
?>