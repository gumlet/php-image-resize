<?php

namespace Eventviva;

class ImageResize {

    protected $image;
    protected $image_type;

    public function __construct($filename) {
        $this->load($filename);
    }

    public function load($filename) {

        $image_info = getimagesize($filename);
        $this->image_type = $image_info[2];
        if ($this->image_type == IMAGETYPE_JPEG) {

            $this->image = imagecreatefromjpeg($filename);
        } elseif ($this->image_type == IMAGETYPE_GIF) {

            $this->image = imagecreatefromgif($filename);
        } elseif ($this->image_type == IMAGETYPE_PNG) {

            $this->image = imagecreatefrompng($filename);
        }
        return $this;
    }

    public function save($filename, $image_type = IMAGETYPE_PNG, $compression = 75, $permissions = null) {

        if ($image_type == IMAGETYPE_JPEG) {
            imagejpeg($this->image, $filename, $compression);
        } elseif ($image_type == IMAGETYPE_GIF) {

            imagegif($this->image, $filename);
        } elseif ($image_type == IMAGETYPE_PNG) {

            imagealphablending($this->image, false);
            imagesavealpha($this->image, true);
            imagepng($this->image, $filename);
        }
        if ($permissions != null) {

            chmod($filename, $permissions);
        }
        return $this;
    }

    public function output($image_type = IMAGETYPE_JPEG) {

        if ($image_type == IMAGETYPE_JPEG) {
            imagejpeg($this->image);
        } elseif ($image_type == IMAGETYPE_GIF) {

            imagegif($this->image);
        } elseif ($image_type == IMAGETYPE_PNG) {

            imagepng($this->image);
        }
    }

    public function getWidth() {

        return imagesx($this->image);
    }

    public function getHeight() {

        return imagesy($this->image);
    }

    public function resizeToHeight($height) {

        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width, $height);
        return $this;
    }

    public function resizeToWidth($width) {
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;
        $this->resize($width, $height);
        return $this;
    }

    public function scale($scale) {
        $width = $this->getWidth() * $scale / 100;
        $height = $this->getheight() * $scale / 100;
        $this->resize($width, $height);
        return $this;
    }

    public function resize($width, $height, $forcesize = false) {
        /* optional. if file is smaller, do not resize. */
        if ($forcesize === false) {
            if ($width > $this->getWidth() && $height > $this->getHeight()) {
                $width = $this->getWidth();
                $height = $this->getHeight();
            }
        }

        $new_image = imagecreatetruecolor($width, $height);
        /* Check if this image is PNG or GIF, then set if Transparent */
        if (($this->image_type == IMAGETYPE_GIF) || ($this->image_type == IMAGETYPE_PNG)) {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
        }
        imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());

        $this->image = $new_image;
        return $this;
    }
    
    /* center crops image to desired width height */
    public function crop($width,$height){
    	$aspect_o = $this->getWidth()/$this->getHeight();
    	$aspect_f = $width/$height;
    
    	if($aspect_o>=$aspect_f){
    		$width_n=$this->getWidth() / ($this->getHeight()/$height);
    		$height_n=$height;
    	}else{
    		$width_n=$width;
    		$height_n=$this->getHeight() / ($this->getWidth()/$width);
    	}
    
        $new_image = imagecreatetruecolor($width, $height);
        /* Check if this image is PNG or GIF, then set if Transparent */
        if (($this->image_type == IMAGETYPE_GIF) || ($this->image_type == IMAGETYPE_PNG)) {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
        }
        imagecopyresampled($new_image, $this->image, 0 - ($width_n - $width)*0.5, 0 - ($height_n - $height)*0.5, 0, 0, $width_n, $height_n, $this->getWidth(), $this->getHeight());
        
        $this->image = $new_image;
        return $this;
    }
 
}
