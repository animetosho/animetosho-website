<?php
/**
 * This is a stripped version of the Securimage v1.0.3.1 (http://www.phpcaptcha.org/) library.
 * We only retain the image generation portion of this library,
 * as AT has its own hash/key generation.  Also, modifications have been done to make this
 * library only work with PHP 5.
 * (a few other minor (hard-coded) modifications have been done)
 */

/**
 * Project:     Securimage: A PHP class for creating and managing form CAPTCHA images<br />
 * File:        securimage.php<br />
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or any later version.<br /><br />
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.<br /><br />
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA<br /><br />
 *
 * Any modifications to the library should be indicated clearly in the source code
 * to inform users that the changes are not a part of the original software.<br /><br />
 *
 * If you found this script useful, please take a quick moment to rate it.<br />
 * http://www.hotscripts.com/rate/49400.html  Thanks.
 *
 * @link http://www.phpcaptcha.org Securimage PHP CAPTCHA
 * @link http://www.phpcaptcha.org/latest.zip Download Latest Version
 * @link http://www.phpcaptcha.org/Securimage_Docs/ Online Documentation
 * @copyright 2007 Drew Phillips
 * @author drew010 <drew@drew-phillips.com>
 * @version 1.0.3.1 (March 24, 2008)
 * @package Securimage
 *
 */


/**
 * Securimage CAPTCHA Class.
 *
 * @package    Securimage
 * @subpackage classes
 *
 */
class Securimage {

  /**
   * The desired width of the CAPTCHA image.
   *
   * @var int
   */
  private $image_width = 220;

  /**
   * The desired width of the CAPTCHA image.
   *
   * @var int
   */
  private $image_height = 60;

  /**
   * Whether to use a GD font instead of a TTF font.<br />
   * TTF offers more support and options, but use this if your PHP doesn't support TTF.<br />
   *
   * @var boolean
   */
  private $use_gd_font = false;

  /**
   * The GD font to use.<br />
   * Internal gd fonts can be loaded by their number.<br />
   * Alternatively, a file path can be given and the font will be loaded from file.
   *
   * @var mixed
   */
  private $gd_font_file = '';

  /**
   * The approximate size of the font in pixels.<br />
   * This does not control the size of the font because that is determined by the GD font itself.<br />
   * This is used to aid the calculations of positioning used by this class.<br />
   *
   * @var int
   */
  private $gd_font_size = 20;

  // Note: These font options below do not apply if you set $use_gd_font to true with the exception of $text_color

  /**
   * The path to the TTF font file to load.
   *
   * @var string
   */
  private $ttf_file = '';

  /**
   * The font size.<br />
   * Depending on your version of GD, this should be specified as the pixel size (GD1) or point size (GD2)<br />
   *
   * @var int
   */
  private $font_size = 24;

  /**
   * The minimum angle in degrees, with 0 degrees being left-to-right reading text.<br />
   * Higher values represent a counter-clockwise rotation.<br />
   * For example, a value of 90 would result in bottom-to-top reading text.
   *
   * @var int
   */
  private $text_angle_minimum = -25;

  /**
   * The minimum angle in degrees, with 0 degrees being left-to-right reading text.<br />
   * Higher values represent a counter-clockwise rotation.<br />
   * For example, a value of 90 would result in bottom-to-top reading text.
   *
   * @var int
   */
  private $text_angle_maximum = 25;

  /**
   * The X-Position on the image where letter drawing will begin.<br />
   * This value is in pixels from the left side of the image.
   *
   * @var int
   */
  private $text_x_start = 8;

  /**
   * Letters can be spaced apart at random distances.<br />
   * This is the minimum distance between two letters.<br />
   * This should be <i>at least</i> as wide as a font character.<br />
   * Small values can cause letters to be drawn over eachother.<br />
   *
   * @var int
   */
  private $text_minimum_distance = 25;

  /**
   * Letters can be spaced apart at random distances.<br />
   * This is the maximum distance between two letters.<br />
   * This should be <i>at least</i> as wide as a font character.<br />
   * Small values can cause letters to be drawn over eachother.<br />
   *
   * @var int
   */
  private $text_maximum_distance = 36;

  /**
   * The background color for the image.<br />
   * This should be specified in HTML hex format.<br />
   * Make sure to include the preceding # sign!
   *
   * @var string
   */
  private $image_bg_color = '#e3daed';

  /**
   * The text color to use for drawing characters.<br />
   * This value is ignored if $use_multi_text is set to true.<br />
   * Make sure this contrasts well with the background color.<br />
   * Specify the color in HTML hex format with preceding # sign
   *
   * @see Securimage::$use_multi_text
   * @var string
   */
  private $text_color = '#ff0000';

  /**
   * Set to true to use multiple colors for each character.
   *
   * @see Securimage::$multi_text_color
   * @var boolean
   */
  private $use_multi_text = true;

  /**
   * String of HTML hex colors to use.<br />
   * Separate each possible color with commas.<br />
   * Be sure to precede each value with the # sign.
   *
   * @var string
   */
  private $multi_text_color = '#0a68dd,#f65c47,#8d32fd';

  /**
   * Set to true to make the characters appear transparent.
   *
   * @see Securimage::$text_transparency_percentage
   * @var boolean
   */
  private $use_transparent_text = true;

  /**
   * The percentage of transparency, 0 to 100.<br />
   * A value of 0 is completely opaque, 100 is completely transparent (invisble)
   *
   * @see Securimage::$use_transparent_text
   * @var int
   */
  private $text_transparency_percentage = 25;


  // Line options
  /**
   * Draw vertical and horizontal lines on the image.
   *
   * @see Securimage::$line_color
   * @see Securimage::$line_distance
   * @see Securimage::$line_thickness
   * @see Securimage::$draw_lines_over_text
   * @var boolean
   */
  private $draw_lines = true;

  /**
   * The color of the lines drawn on the image.<br />
   * Use HTML hex format with preceding # sign.
   *
   * @see Securimage::$draw_lines
   * @var string
   */
  private $line_color = '#80BFFF';

  /**
   * How far apart to space the lines from eachother in pixels.
   *
   * @see Securimage::$draw_lines
   * @var int
   */
  private $line_distance = 13;

  /**
   * How thick to draw the lines in pixels.<br />
   * 1-3 is ideal depending on distance
   *
   * @see Securimage::$draw_lines
   * @see Securimage::$line_distance
   * @var int
   */
  private $line_thickness = 3;

  /**
   * Set to true to draw angled lines on the image in addition to the horizontal and vertical lines.
   *
   * @see Securimage::$draw_lines
   * @var boolean
   */
  private $draw_angled_lines = true;

  /**
   * Draw the lines over the text.<br />
   * If fales lines will be drawn before putting the text on the image.<br />
   * This can make the image hard for humans to read depending on the line thickness and distance.
   *
   * @var boolean
   */
  private $draw_lines_over_text = false;

  /**
   * For added security, it is a good idea to draw arced lines over the letters to make it harder for bots to segment the letters.<br />
   * Two arced lines will be drawn over the text on each side of the image.<br />
   * This is currently expirimental and may be off in certain configurations.
   *
   * @var boolean
   */
  private $arc_linethrough = false;

  /**
   * The colors or color of the arced lines.<br />
   * Use HTML hex notation with preceding # sign, and separate each value with a comma.<br />
   * This should be similar to your font color for single color images.
   *
   * @var string
   */
  private $arc_line_colors = '#8080ff';

  /**
   * Full path to the WAV files to use to make the audio files, include trailing /.<br />
   * Name Files  [A-Z0-9].wav
   *
   * @since 1.0.1
   * @var string
   */
  private $audio_path = '';


  //END USER CONFIGURATION
  //There should be no need to edit below unless you really know what you are doing.

  /**
   * The gd image resource.
   *
   * @access private
   * @var resource
   */
  private $im;

  /**
   * The background image resource
   *
   * @access private
   * @var resource
   */
  private $bgimg;

  /**
   * The code generated by the script
   *
   * @access private
   * @var string
   */
  private $code;


  public function __construct($code) {
    $this->code = $code;
    $this->gd_font_file = AT_ROOT.'includes/3rdparty/securimage/gdfonts/bubblebath.gdf';
    $this->ttf_file = AT_ROOT.'includes/3rdparty/securimage/elephant.ttf';
    $this->audio_path = AT_ROOT.'includes/3rdparty/securimage/audio/';
  }
  /**
   * Generate and output the image
   *
   */
  public function doImage()
  {
    if($this->use_transparent_text == true || $this->bgimg != '') {
      $this->im = imagecreatetruecolor($this->image_width, $this->image_height);
      $bgcolor = imagecolorallocate($this->im, hexdec(substr($this->image_bg_color, 1, 2)), hexdec(substr($this->image_bg_color, 3, 2)), hexdec(substr($this->image_bg_color, 5, 2)));
      imagefilledrectangle($this->im, 0, 0, imagesx($this->im), imagesy($this->im), $bgcolor);
    } else { //no transparency
      $this->im = imagecreate($this->image_width, $this->image_height);
      $bgcolor = imagecolorallocate($this->im, hexdec(substr($this->image_bg_color, 1, 2)), hexdec(substr($this->image_bg_color, 3, 2)), hexdec(substr($this->image_bg_color, 5, 2)));
    }

    if($this->bgimg != '') { $this->setBackground(); }

    if (!$this->draw_lines_over_text && $this->draw_lines) $this->drawLines();

    $this->drawWord();

    if ($this->arc_linethrough == true) $this->arcLines();

    if ($this->draw_lines_over_text && $this->draw_lines) $this->drawLines();

	imagepng($this->im);
	imagedestroy($this->im);
  }

  /**
   * Set the background of the CAPTCHA image
   *
   * @access private
   *
   */
  private function setBackground()
  {
    $dat = @getimagesize($this->bgimg);
    if($dat == false) { return; }

    switch($dat[2]) {
      case 1:  $newim = @imagecreatefromgif($this->bgimg); break;
      case 2:  $newim = @imagecreatefromjpeg($this->bgimg); break;
      case 3:  $newim = @imagecreatefrompng($this->bgimg); break;
      case 15: $newim = @imagecreatefromwbmp($this->bgimg); break;
      case 16: $newim = @imagecreatefromxbm($this->bgimg); break;
      default: return;
    }

    if(!$newim) return;

    imagecopy($this->im, $newim, 0, 0, 0, 0, $this->image_width, $this->image_height);
  }

  /**
   * Draw arced lines over the text
   *
   * @access private
   *
   */
  private function arcLines()
  {
    $colors = explode(',', $this->arc_line_colors);
    imagesetthickness($this->im, 3);

    $color = $colors[rand(0, sizeof($colors) - 1)];
    $linecolor = imagecolorallocate($this->im, hexdec(substr($color, 1, 2)), hexdec(substr($color, 3, 2)), hexdec(substr($color, 5, 2)));

    $xpos   = $this->text_x_start + ($this->font_size * 2) + rand(-5, 5);
    $width  = $this->image_width / 2.66 + rand(3, 10);
    $height = $this->font_size * 2.14 - rand(3, 10);

    if ( rand(0,100) % 2 == 0 ) {
      $start = rand(0,66);
      $ypos  = $this->image_height / 2 - rand(5, 15);
      $xpos += rand(5, 15);
    } else {
      $start = rand(180, 246);
      $ypos  = $this->image_height / 2 + rand(5, 15);
    }

    $end = $start + rand(75, 110);

    imagearc($this->im, $xpos, $ypos, $width, $height, $start, $end, $linecolor);

    $color = $colors[rand(0, sizeof($colors) - 1)];
    $linecolor = imagecolorallocate($this->im, hexdec(substr($color, 1, 2)), hexdec(substr($color, 3, 2)), hexdec(substr($color, 5, 2)));

    if ( rand(1,75) % 2 == 0 ) {
      $start = rand(45, 111);
      $ypos  = $this->image_height / 2 - rand(5, 15);
      $xpos += rand(5, 15);
    } else {
      $start = rand(200, 250);
      $ypos  = $this->image_height / 2 + rand(5, 15);
    }

    $end = $start + rand(75, 100);

    imagearc($this->im, $this->image_width * .75, $ypos, $width, $height, $start, $end, $linecolor);
  }

  /**
   * Draw lines on the image
   *
   * @access private
   *
   */
  private function drawLines()
  {
    $linecolor = imagecolorallocate($this->im, hexdec(substr($this->line_color, 1, 2)), hexdec(substr($this->line_color, 3, 2)), hexdec(substr($this->line_color, 5, 2)));
    imagesetthickness($this->im, $this->line_thickness);

    //vertical lines
    for($x = 1; $x < $this->image_width; $x += $this->line_distance) {
      imageline($this->im, $x, 0, $x, $this->image_height, $linecolor);
    }

    //horizontal lines
    for($y = 11; $y < $this->image_height; $y += $this->line_distance) {
      imageline($this->im, 0, $y, $this->image_width, $y, $linecolor);
    }

    if ($this->draw_angled_lines == true) {
      for ($x = -($this->image_height); $x < $this->image_width; $x += $this->line_distance) {
        imageline($this->im, $x, 0, $x + $this->image_height, $this->image_height, $linecolor);
      }

      for ($x = $this->image_width + $this->image_height; $x > 0; $x -= $this->line_distance) {
        imageline($this->im, $x, 0, $x - $this->image_height, $this->image_height, $linecolor);
      }
    }
  }

  /**
   * Draw the CAPTCHA code over the image
   *
   * @access private
   *
   */
  private function drawWord()
  {
    if ($this->use_gd_font == true) {
      if (!is_int($this->gd_font_file)) { //is a file name
        $font = @imageloadfont($this->gd_font_file);
        if ($font == false) {
          trigger_error("Failed to load GD Font file {$this->gd_font_file} ", E_USER_WARNING);
          return;
        }
      } else { //gd font identifier
        $font = $this->gd_font_file;
      }

      $color = imagecolorallocate($this->im, hexdec(substr($this->text_color, 1, 2)), hexdec(substr($this->text_color, 3, 2)), hexdec(substr($this->text_color, 5, 2)));
      imagestring($this->im, $font, $this->text_x_start, ($this->image_height / 2) - ($this->gd_font_size / 2), $this->code, $color);

    } else { //ttf font
      if($this->use_transparent_text == true) {
        $alpha = intval($this->text_transparency_percentage / 100 * 127);
        $font_color = imagecolorallocatealpha($this->im, hexdec(substr($this->text_color, 1, 2)), hexdec(substr($this->text_color, 3, 2)), hexdec(substr($this->text_color, 5, 2)), $alpha);
      } else { //no transparency
        $font_color = imagecolorallocate($this->im, hexdec(substr($this->text_color, 1, 2)), hexdec(substr($this->text_color, 3, 2)), hexdec(substr($this->text_color, 5, 2)));
      }

      $x = $this->text_x_start;
      $strlen = strlen($this->code);
      $y_min = ($this->image_height / 2) + ($this->font_size / 2) - 2;
      $y_max = ($this->image_height / 2) + ($this->font_size / 2) + 2;
      $colors = explode(',', $this->multi_text_color);

      for($i = 0; $i < $strlen; ++$i) {
        $angle = rand($this->text_angle_minimum, $this->text_angle_maximum);
        $y = rand($y_min, $y_max);
        if ($this->use_multi_text == true) {
          $idx = rand(0, sizeof($colors) - 1);
          $r = substr($colors[$idx], 1, 2);
          $g = substr($colors[$idx], 3, 2);
          $b = substr($colors[$idx], 5, 2);
          if($this->use_transparent_text == true) {
            $font_color = imagecolorallocatealpha($this->im, hexdec($r), hexdec($g), hexdec($b), $alpha);
          } else {
            $font_color = imagecolorallocate($this->im, hexdec($r), hexdec($g), hexdec($b));
          }
        }
        @imagettftext($this->im, $this->font_size, $angle, $x, $y, $font_color, $this->ttf_file, $this->code[$i]);

        $x += rand($this->text_minimum_distance, $this->text_maximum_distance);
      } //for loop
    } //else ttf font
  } //function


  /**
   * Generate a wav file by concatenating individual files
   * @since 1.0.1
   * @access private
   * @param array $letters  Array of letters to build a file from
   * @return string  WAV file data
   */
   // modification: originally named "generateWAV", this now outputs the sound rather than return the WAV data
  public function doSound()
  {
    $first = true; // use first file to write the header...
    $data_len    = 0;
    $files       = array();

    $letters = str_split($this->code);
    foreach ($letters as $letter) {
      $filename = $this->audio_path . strtoupper($letter) . '.wav';

      $fp = fopen($filename, 'rb');

      $file = array();

      $data = fread($fp, filesize($filename)); // read file in

      $header = substr($data, 0, 36);
      $body   = substr($data, 44);


      $data = unpack('NChunkID/VChunkSize/NFormat/NSubChunk1ID/VSubChunk1Size/vAudioFormat/vNumChannels/VSampleRate/VByteRate/vBlockAlign/vBitsPerSample', $header);

      $file['sub_chunk1_id']   = $data['SubChunk1ID'];
      $file['bits_per_sample'] = $data['BitsPerSample'];
      $file['channels']        = $data['NumChannels'];
      $file['format']          = $data['AudioFormat'];
      $file['sample_rate']     = $data['SampleRate'];
      $file['size']            = $data['ChunkSize'] + 8;
      $file['data']            = $body;

      if ( ($p = strpos($file['data'], 'LIST')) !== false) {
        // If the LIST data is not at the end of the file, this will probably break your sound file
        $info         = substr($file['data'], $p + 4, 8);
        $data         = unpack('Vlength/Vjunk', $info);
        $file['data'] = substr($file['data'], 0, $p);
        $file['size'] = $file['size'] - (strlen($file['data']) - $p);
      }

      $files[] = $file;
      $data    = null;
      $header  = null;
      $body    = null;

      $data_len += strlen($file['data']);

      fclose($fp);
    }

    for($i = 0; $i < sizeof($files); ++$i) {
      if ($i == 0) { // output header
        echo pack('C4VC8', ord('R'), ord('I'), ord('F'), ord('F'), $data_len + 36, ord('W'), ord('A'), ord('V'), ord('E'), ord('f'), ord('m'), ord('t'), ord(' ')),

          pack('VvvVVvv',
                          16,
                          $files[$i]['format'],
                          $files[$i]['channels'],
                          $files[$i]['sample_rate'],
                          $files[$i]['sample_rate'] * (($files[$i]['bits_per_sample'] * $files[$i]['channels']) / 8),
                          ($files[$i]['bits_per_sample'] * $files[$i]['channels']) / 8,
                          $files[$i]['bits_per_sample'] ),

          pack('C4', ord('d'), ord('a'), ord('t'), ord('a')),
          pack('V', $data_len);
      }
      echo $files[$i]['data'];
    }
  }
} /* class Securimage */

?>