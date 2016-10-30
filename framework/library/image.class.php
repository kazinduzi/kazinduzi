<?php

defined('KAZINDUZI_PATH') || exit('No direct script access allowed');
/**
 * Kazinduzi Framework (http://framework.kazinduzi.com/).
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 *
 * @link      http://kazinduzi.com
 *
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 */
class Image
{
    private $image;
    private $type;

   /**
    * constructor for the Image processing with GD Library.
    *
    * @param string $filename
    */
   public function __construct($filename)
   {
       if (!$this->isGD()) {
           throw new Exception('GD Library is not loaded');
       }
       $this->load($filename);
   }

   /**
    * Class destructor.
    */
   public function __destruct()
   {
       if (is_resource($this->image)) {
           imagedestroy($this->image);
       }
   }

   /**
    * check if GD Library is loaded, and support typical image types like (JPG, PNG, GIF, WBMP).
    *
    * @return bool
    */
   public function isGD()
   {
       if (extension_loaded('gd') && imagetypes() & IMG_PNG && imagetypes() & IMG_GIF && imagetypes() & IMG_JPG && imagetypes() & IMG_WBMP) {
           return true;
       } else {
           return false;
       }
   }

   /**
    * Load the image from the provided image.
    *
    * @param string $filename
    */
   private function load($filename)
   {
       list($width, $height, $type, $attr) = getimagesize($filename);
       $this->type = $type;
       if ($type == IMAGETYPE_JPEG) {
           $this->image = imagecreatefromjpeg($filename);
       } elseif ($type == IMAGETYPE_GIF) {
           $this->image = imagecreatefromgif($filename);
       } elseif ($type == IMAGETYPE_PNG) {
           $this->image = imagecreatefrompng($filename);
       }
   }

   /**
    * get the width of the image.
    *
    * @return int
    */
   public function getWidth()
   {
       return imagesx($this->image);
   }

   /**
    * get the Height of the image.
    *
    * @return int
    */
   public function getHeight()
   {
       return imagesy($this->image);
   }

   /**
    * get the type of the image.
    *
    * @return string
    */
   public function getType()
   {
       return $this->type;
   }

   /**
    * Resizes the image to the given Height.
    *
    * @param int $height
    *
    * @return Image
    */
   public function resizeToHeight($height)
   {
       $ratio = $height / $this->getHeight();
       $width = $this->getWidth() * $ratio;
       $this->resize($width, $height);

       return $this;
   }

   /**
    * Resize the image to the given width.
    *
    * @param int $width
    *
    * @return Image
    */
   public function resizeToWidth($width)
   {
       $ratio = $width / $this->getWidth();
       $height = $this->getheight() * $ratio;
       $this->resize($width, $height);

       return $this;
   }

   /**
    * Scales the image with the provided scale.
    *
    * @param int $scale
    *
    * @return Image
    */
   public function scale($scale)
   {
       $width = $this->getWidth() * $scale / 100;
       $height = $this->getheight() * $scale / 100;
       $this->resize($width, $height);

       return $this;
   }

   /**
    * resize the image to the given  sizes.
    *
    * @param int $width
    * @param int $height
    */
   public function resize($width, $height)
   {
       $new_image = imagecreatetruecolor($width, $height);
      // apply the transparancy for PNG image
      if (IMAGETYPE_PNG == $this->getType()) {
          imagealphablending($this->image, false);
          imagefill($this->image, 0, 0, imagecolorallocatealpha($this->image, 0, 0, 0, 127));
          imagesavealpha($this->image, true);
      }
      // apply the transparancy for GIF image
      elseif (IMAGETYPE_GIF == $this->getType()) {
          imagecolortransparent($this->image, imagecolorallocate($this->image, 0, 0, 0));
          imagetruecolortopalette($this->image, true, 256);
      }
       imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
       $this->image = $new_image;

       return $this;
   }

   /**
    * @return string
    */
   public function getImageAsString()
   {
       $data = null;
       ob_start();
       $this->output();
       $data = ob_get_contents();
       ob_end_clean();

       return $data;
   }

   /**
    * save the image.
    *
    * @param string $filename
    * @param int $compression
    * @param int $permissions
    */
   public function save($filename, $compression = 75, $permissions = null)
   {
       // Make sure the directory is writeable
      if (!is_writable(dirname($filename))) {
          @chmod(dirname($filename), 0777);
            // Throw an exception if not writeable
            if (!is_writable(dirname($filename))) {
                throw new RuntimeException('File is not writeable, and could not correct permissions: '.$filename);
            }
      }
       if ($this->getType() == IMAGETYPE_JPEG) {
           imagejpeg($this->image, $filename, $compression);
       } elseif ($this->getType() == IMAGETYPE_GIF) {
           imagegif($this->image, $filename);
       } elseif ($this->getType() == IMAGETYPE_PNG) {
           imagepng($this->image, $filename);
       }

       if ($permissions != null) {
           chmod($filename, $permissions);
       }

       return $this;
   }

   /**
    * output the image to the buffer.
    *
    * @param int $type
    */
   public function output($raw = false)
   {
       if (headers_sent()) {
           throw new RuntimeException('Cannot show image, headers have already been sent');
       }

       if ($this->getType() == IMAGETYPE_JPEG) {
           ($raw) ? header('Content-type: image/jpeg') : null;
           imagejpeg($this->image);
       } elseif ($this->getType() == IMAGETYPE_GIF) {
           ($raw) ? header('Content-type: image/gif') : null;
           imagegif($this->image);
       } elseif ($this->getType() == IMAGETYPE_PNG) {
           ($raw) ? header('Content-type: image/png') : null;
           imagepng($this->image);
       }
   }
}

/*
 * Example of usage
 * $Image = new Image($filename);
 * $Image->resizeToWidth($width);
 * $Image->save();
 */
