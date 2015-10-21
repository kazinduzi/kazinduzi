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
class Gd_Editor extends Editor {
    protected $image;
    protected $file;
    protected $type;

    /**
     * Constructor
     *
     * @param string $file
     * @throws \Exception
     */
    public function __construct($file=null) {
        if (!self::test()) {
            throw new \Exception(sprintf('GD library is not available or ...'));
        }
        $this->file = $file?:$file;
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
     * Load the image resource
     *
     * @return boolean
     * @throws Exception
     */
    public function load() {
        $size = @getimagesize($this->file);
		if (!$size) {
            throw new \Exception(sprintf('Could not read image size of the file #%s', $this->file));
        }
        $this->type = $size[2];
		if (!is_file($this->file) && !preg_match('|^https?:#|', $this->file))  {
            throw new \Exception(sprintf('File #%s doesn&#8217;t exist?', $this->file));
        }
		$this->image = @imagecreatefromstring(file_get_contents($this->file));
		if (!is_resource($this->image)) {
            throw new \Exception(sprintf('File #%s is not an image.', $this->file));
        }
		$this->updateSize($size[0], $size[1]);
		$this->mime_type = $size['mime'];
		return $this;
	}

    /**
     *
     * @param type $width
     * @param type $height
     * @return type
     */
    protected function updateSize($width = false, $height = false) {
		if (!$width) {
			$width = imagesx($this->image);
        }
		if (!$height) {
			$height = imagesy($this->image);
        }
		return parent::updateSize($width, $height);
	}

    /**
     *
     * @param type $max_w
     * @param type $max_h
     * @param type $crop
     * @return boolean|\Library\Image\WP_Error
     */
    public function resize($max_w, $max_h, $crop = false) {
		if ($this->size['width'] == $max_w && $this->size['height'] == $max_h) {
            return $this;
        }
		$resized = $this->_resize($max_w, $max_h, $crop);
		if (is_resource($resized)) {
			imagedestroy($this->image);
			$this->image = $resized;
			return $this;
		}
        throw new \Exception(sprintf('Image resize failed of the file #%s', $this->file));
	}

    /**
     *
     * @param array $sizes
     * @return type
     */
    public function multi_resize($sizes) {
		$metadata = array();
		$orig_size = $this->size;
		foreach ($sizes as $size => $size_data) {
			$image = $this->_resize($size_data['width'], $size_data['height'], $size_data['crop']);
            $resized = $this->_save($image);
            imagedestroy($image);
            if ($resized) {
                unset($resized['path']);
                $metadata[$size] = $resized;
            }
			$this->size = $orig_size;
		}
		return $metadata;
	}

    /**
     *
     * @param type $max_w
     * @param type $max_h
     * @param type $crop
     * @return type
     * @throws \Exception
     */
    protected function _resize($max_w, $max_h, $crop = false) {
		$dims = $this->image_resize_dimensions($this->size['width'], $this->size['height'], $max_w, $max_h, $crop);
		if (!$dims) {
			throw new \Exception(sprintf('Could not calculate resized image dimensions %s', $this->file));
		}
		list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $dims;
		$resized = $this->imagecreatetruecolor($dst_w, $dst_h);
        imagecopyresampled($resized, $this->image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
		if (is_resource($resized)) {
			$this->updateSize($dst_w, $dst_h);
			return $resized;
		}
		throw new \Exception(sprintf('Image resize failed #%s.', $this->file));
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
     * @return boolean
     * @throws \Exception
     */
    public function crop($src_x, $src_y, $src_w, $src_h, $dst_w = null, $dst_h = null, $src_abs = false) {
        if (!$dst_w) {
            $dst_w = $src_w;
        }
        if (!$dst_h) {
            $dst_h = $src_h;
        }
        $dst = $this->imagecreatetruecolor($dst_w, $dst_h);
        if ($src_abs) {
            $src_w -= $src_x;
            $src_h -= $src_y;
        }
        if (function_exists('imageantialias')) {
            imageantialias($dst, true);
        }
        imagecopyresampled($dst, $this->image, 0, 0, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
        if (is_resource($dst)) {
            imagedestroy($this->image);
            $this->image = $dst;
            $this->updateSize();
            return $this;
        }
        throw new \Exception(sprintf('Image crop failed #%s.', $this->file));
	}

    /**
     *
     * @param type $angle
     * @return boolean
     * @throws \Exceptions
     */
    public function rotate($angle, $background = null) {
        if (function_exists('imagerotate')) {
            # Convert the hexadecimal background value to a color index value.
            # Else Set the background color as transparent if $background is null.
            if (isset($background)) {
                $rgb = array();
                for ($i = 16; $i >= 0; $i -= 8) {
                    $rgb[] = (($background >> $i) & 0xFF);
                }
                $background = imagecolorallocatealpha($this->image, $rgb[0], $rgb[1], $rgb[2], 0);
            } else {
                # Get the current transparent color.
                $background = imagecolortransparent($this->image);
                # If no transparent colors, use white.
                if ($background == 0) {
                    $background = imagecolorallocatealpha($this->image, 255, 255, 255, 0);
                }
            }

            # Images are assigned a new color palette when rotating, removing any
            # transparency flags. For GIF images, keep a record of the transparent color.
            if ($this->mime_type == 'image/gif') {
                $transparent_index = imagecolortransparent($this->image);
                if ($transparent_index != 0) {
                    $transparent_gif_color = imagecolorsforindex($this->image, $transparent_index);
                }
            }

            # Perfom the rotation of an image
            $rotated = imagerotate($this->image, 360 - $angle, $background);

            # GIFs need to reassign the transparent color after performing the rotate.
            if (isset($transparent_gif_color)) {
                $background = imagecolorexactalpha($rotated, $transparent_gif_color['red'], $transparent_gif_color['green'], $transparent_gif_color['blue'], $transparent_gif_color['alpha']);
                imagecolortransparent($rotated, $background);
            }

            if (is_resource($rotated)) {
                imagedestroy($this->image);
                $this->image = $rotated;
                $this->updateSize();
                return $this;
            }
        }
        throw new \Exception(sprintf('Image rotate failed: File:%s', $this->file));
    }

    /**
     *
     * @param type $horz
     * @param type $vert
     * @return boolean
     * @throws \Exception
     */
    public function flip($direction) {
        $w = $this->size['width'];
        $h = $this->size['height'];
        $dst = $this->imagecreatetruecolor($w, $h);
        if (is_resource($dst)) {
            $sx = $direction=='vertical' ? ($w - 1) : 0;
            $sy = $direction=='horizontal' ? ($h - 1) : 0;
            $sw = $direction=='vertical' ? -$w : $w;
            $sh = $direction=='horizontal' ? -$h : $h;
            if (imagecopyresampled($dst, $this->image, 0, 0, $sx, $sy, $w, $h, $sw, $sh)) {
                imagedestroy($this->image);
                $this->image = $dst;
                return $this;
            }
        }
        throw new \Exception(sprintf('Image flip failed. File:%s', $this->file));
	}

    /**
     *
     * @param type $filename
     * @param type $mime_type
     * @return type
     */
    public function save($filename = null) {
        $saved = $this->_save($this->image, $filename);
        $this->file = $saved['path'];
        $this->mime_type = $saved['mime-type'];
            return $saved;
	}

    /**
     *
     * @param type $image
     * @param type $filename
     * @param type $compression
     * @return \Library\Image\Gd_Editor
     * @throws RuntimeException
     */
    protected function _save($image, $filename=null) {
        if (!$filename) {
            $ext = pathinfo($this->file, PATHINFO_EXTENSION);
            $filename = $this->generateFilename(null, null, $ext);
        }
        $compression = $this->quality;
        $mime_type = $this->default_mime_type;
        if (false === is_dir(dirname($filename))) {
            $oldmask = umask(0);
            mkdir(dirname($filename), 0777, true);
            umask($oldmask);
        }
        if (!is_writeable(dirname($filename))) {
            chmod(dirname($filename), 0777);
            if (!is_writeable(dirname($filename))) {
                throw new \RuntimeException(sprintf('File is not writeable, and could not correct permissions: %s', $filename));
            }
        }
        if ($this->getType() == IMAGETYPE_JPEG) {
            $mime_type = 'image/jpeg';
            imagejpeg($image, $filename, $compression);
        } elseif ($this->getType() == IMAGETYPE_GIF) {
            $mime_type = 'image/gif';
            imagegif( $image, $filename);
        } elseif ($this->getType() == IMAGETYPE_PNG) {
            $mime_type = 'image/png';
            if (function_exists('imageistruecolor') && !imageistruecolor($image)) {
                imagetruecolortopalette($image, false, imagecolorstotal($image));
            }
            imagepng($image, $filename);
        }

        # Set correct file permissions
        $stat = stat(dirname($filename));
        $perms = $stat['mode'] & 0000666; #same permissions as parent folder, strip off the executable bits
        @chmod($filename, $perms);

        return array(
                'path' => $filename,
                'file' => basename($filename),
                'width' => $this->size['width'],
                'height' => $this->size['height'],
                'mime-type'=> $mime_type,
            );
        return $this;
   }

    /**
     *
     * @param type $mime_type
     * @return type
     */
    public function stream($mime_type = null) {
        if (is_null($mime_type)) {
            $mime_type = $this->default_mime_type;
        }
        switch ($mime_type) {
            case 'image/png':
                header('Content-Type: image/png');
                return imagepng($this->image);
            case 'image/gif':
                header('Content-Type: image/gif');
                return imagegif($this->image);
            default:
                header('Content-Type: image/jpeg');
                return imagejpeg($this->image, null, $this->quality);
        }
    }

    /**
     *
     */
    public function __destruct() {
        # we don't need the original in memory anymore
        if ($this->image) {
            imagedestroy($this->image);
        }
    }

    /**
     *
     * @return type
     */
    private function getType() {
        return $this->type;
    }

    /**
     *
     * @param type $width
     * @param type $height
     * @return type
     */
    private function imagecreatetruecolor($width, $height) {
        $img = imagecreatetruecolor($width, $height);
        if (is_resource($img) && function_exists('imagealphablending') && function_exists('imagesavealpha')) {
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }
        return $img;
    }

    /**
     *
     * @param type $orig_w
     * @param type $orig_h
     * @param type $dest_w
     * @param type $dest_h
     * @param type $crop
     * @return boolean
     */
    private function image_resize_dimensions($orig_w, $orig_h, $dest_w, $dest_h, $crop = false) {
        if ($orig_w <= 0 || $orig_h <= 0) {
            return false;
        }
        # At least one of dest_w or dest_h must be specific
        if ($dest_w <= 0 && $dest_h <= 0) {
            return false;
        }

        if ($crop) {
            # crop the largest possible portion of the original image that we can size to $dest_w x $dest_h
            $aspect_ratio = $orig_w / $orig_h;
            $new_w = min($dest_w, $orig_w);
            $new_h = min($dest_h, $orig_h);
            if (!$new_w) {
                $new_w = (int)($new_h * $aspect_ratio);
            }
            if (!$new_h) {
                $new_h = (int)($new_w / $aspect_ratio);
            }
            $size_ratio = max($new_w / $orig_w, $new_h / $orig_h);
            $crop_w = round($new_w / $size_ratio);
            $crop_h = round($new_h / $size_ratio);
            $s_x = floor(($orig_w - $crop_w) / 2);
            $s_y = floor(($orig_h - $crop_h) / 2);
        } else {
            # don't crop, just resize using $dest_w x $dest_h as a maximum bounding box
            $crop_w = $orig_w;
            $crop_h = $orig_h;
            $s_x = 0;
            $s_y = 0;
            list($new_w, $new_h) = $this->constrain_dimensions($orig_w, $orig_h, $dest_w, $dest_h);
        }

        # if the resulting image would be the same size or larger we don't want to resize it
        if ($new_w >= $orig_w && $new_h >= $orig_h) {
            $new_w = $orig_w;
            $new_h = $orig_h;
            /*
            $orig_w = $new_w;
            $orig_h = $new_h;
            */
        }

        # the return array matches the parameters to imagecopyresampled()
        # int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h
        return array(0, 0, (int)$s_x, (int)$s_y, (int)$new_w, (int)$new_h, (int)$crop_w, (int)$crop_h);

    }

    /**
     *
     * @param type $current_width
     * @param type $current_height
     * @param type $max_width
     * @param type $max_height
     * @return type
     */
    private function constrain_dimensions($current_width, $current_height, $max_width=0, $max_height=0) {
        if (!$max_width and !$max_height) {
            return array($current_width, $current_height);
        }

        $width_ratio = $height_ratio = 1.0;
        $did_width = $did_height = false;

        if ($max_width > 0 && $current_width > 0 && $current_width > $max_width) {
            $width_ratio = $max_width / $current_width;
            $did_width = true;
        }

        if ($max_height > 0 && $current_height > 0 && $current_height > $max_height) {
            $height_ratio = $max_height / $current_height;
            $did_height = true;
        }

        # Calculate the larger/smaller ratios
        $smaller_ratio = min($width_ratio, $height_ratio);
        $larger_ratio  = max($width_ratio, $height_ratio);

        if ((int)($current_width * $larger_ratio) > $max_width || (int)($current_height * $larger_ratio) > $max_height) {
            # The larger ratio is too big. It would result in an overflow.
            $ratio = $smaller_ratio;
        } else {
            # The larger ratio fits, and is likely to be a more "snug" fit.
            $ratio = $larger_ratio;
        }

        $w = (int)($current_width  * $ratio);
        $h = (int)($current_height * $ratio);

        # Sometimes, due to rounding, we'll end up with a result like this: 465x700 in a 177x177 box is 117x176... a pixel short
        # We also have issues with recursive calls resulting in an ever-changing result. Constraining to the result of a constraint should yield the original result.
        # Thus we look for dimensions that are one pixel shy of the max value and bump them up
        if ($did_width && $w == $max_width - 1) {
            $w = $max_width; # Round it up
        }
        if ($did_height && $h == $max_height - 1) {
            $h = $max_height; # Round it up
        }

        return array( $w, $h );
    }


    /**
     *
     * @param array $args
     * @return boolean
     */
    public static function test($args = array()) {
        if (!extension_loaded('gd') || !function_exists('gd_info')) {
            return false;
        }
        # On some setups GD library does not provide imagerotate()
        if (isset($args['methods']) && in_array('rotate', $args['methods']) && !function_exists('imagerotate')) {
            return false;
        }
        return true;
    }

    /**
     *
     * @param type $mime_type
     * @return boolean
     */
    public static function supports_mime_type($mime_type) {
        $image_types = imagetypes();
        switch($mime_type) {
            case 'image/jpeg':
                return ($image_types & IMG_JPG) != 0;
            case 'image/png':
                return ($image_types & IMG_PNG) != 0;
            case 'image/gif':
                return ($image_types & IMG_GIF) != 0;
        }
        return false;
    }
}