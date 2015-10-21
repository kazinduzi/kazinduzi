<?php namespace framework\library\Image;
/**
 * Kazinduzi Framework (http://framework.kazinduzi.com/)
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 * @link      http://kazinduzi.com
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 * @package   Kazinduzi
 */
class Imagick_Editor extends Editor {
    protected $image;
    protected $file;
    protected $type;
    protected $imagick = null;

    /**
     *
     */
    public function __construct($filename=null) {
        $this->file = $filename?:$filename;
        if (!self::test()) {
            throw new \Exception(sprintf('Imagick library is not available or ...'));
        }
        if (null == $this->imagick) {
            $this->imagick = new \Imagick();
        }
        $this->imagick->readimage($this->file);
    }

    /**
     * Set the file to be edited
     *
     * @param string $file
     * @return \framework\library\Image\Gd_Editor
     */
    public function setFile($file) {
        $this->file = $file;
        $this->load();
    }

    /**
     *
     * @return \framework\library\Image\Imagick_Editor
     */
    public function load() {
        $this->imagick->readImage($filename);
        return $this;
    }

    /**
     *
     * @param type $width
     * @param type $height
     * @param type $crop
     * @return \framework\library\Image\Imagick_Editor
     */
    public function resize($width, $height, $crop = false) {
        $this->imagick->scaleImage($width, $height, $keepar);
        return $this;
    }

    public function multi_resize($sizes) {

    }

    /**
     *
     * @param type $src_x
     * @param type $src_y
     * @param type $src_w
     * @param type $src_h
     * @param type $dst_w
     * @param type $dst_h
     * @param type $src_abs
     * @return \framework\library\Image\Imagick_Editor
     */
    public function crop($src_x, $src_y, $src_w, $src_h, $dst_w = null, $dst_h = null, $src_abs = false) {
        if ( $src_abs ) {
			$src_w -= $src_x;
			$src_h -= $src_y;
		}
        try {
            $this->imagick->cropImage( $src_w, $src_h, $src_x, $src_y );
			$this->imagick->setImagePage( $src_w, $src_h, 0, 0);
            if ($dst_w || $dst_h) {
				// If destination width/height isn't specified, use same as
				// width/height from source.
				if (! $dst_w) {
					$dst_w = $src_w;
                }
				if (! $dst_h) {
					$dst_h = $src_h;
                }
				$this->image->scaleImage( $dst_w, $dst_h );
			}
            return $this;
        } catch (\Exception $e) {
			print_r($e);
		}
    }

    /**
     *
     * @param integer $angle
     * @return \framework\library\Image\Imagick_Editor
     */
    public function rotate($angle) {
        $this->imagick->rotateimage(new ImagickPixel('none'), 360-$angle);
        return $this;
    }

    /**
     *
     * @param type $direction
     * @return boolean|\framework\library\Image\Imagick_Editor
     */
    public function flip($direction) {
        switch ($direction) {
            case 'vertical':
                $this->imagick->flipimage();
                break;
            case 'horizontal':
                $this->imagick->flopimage();
                break;
            case 'both':
                $this->imagick->flipimage();
                $this->imagick->flopimage();
                break;
            default: 
                return false;
        }
        return $this;
    }

    public function stream($mime_type = null) {

    }

    public function save($filename = null, $mime_type = null) {
        if (!$filename) {
            $ext = pathinfo($this->file, PATHINFO_EXTENSION);
			$filename = $this->generateFilename(null, null, $ext);
        }
        if (!is_writeable(dirname($filename))) {
            @chmod(dirname($filename), 0777);
            if (!is_writeable(dirname($filename))) {
                throw new \RuntimeException(sprintf('File is not writeable, and could not correct permissions: %s', $filename));
            }
        }
        try {
			$orig_format = $this->imagick->getImageFormat();
			$this->imagick->setImageFormat(strtoupper($ext));
            $this->imagick->writeimage($filename);
			# Reset original Format
			$this->imagick->setImageFormat($orig_format);
            # Set correct file permissions
            $stat = stat(dirname($filename));
            $perms = $stat['mode'] & 0000666; #same permissions as parent folder, strip off the executable bits
            @chmod($filename, $perms);
            return array(
                'path' => $filename,
                'file' => basename($filename),
                'width' => $this->size['width'],
                'height' => $this->size['height'],
                'mime-type' => $this->imagick->getimagemimetype(),
            );
		}
		catch (\Exception $e) {
			print_r($e);
		}

    }

    /**
     * Destructor
     */
    public function __destruct() {
		if ( $this->imagick ) {
			// we don't need the original in memory anymore
			$this->imagick->clear();
			$this->imagick->destroy();
		}
	}

    /**
	 * Creates a new color usable by Imagick.
	 *
	 * @param  string   $hex    The hex code of the color
	 * @param  integer  $alpha  The alpha of the color, 0 (trans) to 100 (opaque)
	 * @return string   rgba representation of the hex and alpha values.
	 */
	protected function create_color($hex, $alpha) {
		$rgb = $this->create_hex_color($hex);
        $red = $rgb['red'];
        $blue = $rgb['blue'];
        $green = $rgb['green'];
		return new \ImagickPixel('rgba('.$red.', '.$green.', '.$blue.', '.round($alpha / 100, 2).')');
	}

    /**
     *
     * @param type $args
     * @return boolean
     */
    public static function test($args = array()) {
		// First, test Imagick's extension and classes.
		if (!extension_loaded('imagick') || !class_exists('Imagick') || !class_exists('ImagickPixel')) {
            return false;
        }
		if (version_compare(phpversion('imagick'), '2.2.0', '<')) {
            return false;
        }
		$required_methods = array(
			'clear',
			'destroy',
			'valid',
			'getimage',
			'writeimage',
			'getimageblob',
			'getimagegeometry',
			'getimageformat',
			'setimageformat',
			'setimagecompression',
			'setimagecompressionquality',
			'setimagepage',
			'scaleimage',
			'cropimage',
			'rotateimage',
			'flipimage',
			'flopimage',
		);
		// Now, test for deep requirements within Imagick.
		if (! defined('imagick::COMPRESSION_JPEG')) {
            return false;
        }
		if (array_diff($required_methods, get_class_methods('Imagick'))) {
            return false;
        }
		return true;
	}
}