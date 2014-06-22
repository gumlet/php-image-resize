<?php

namespace Eventviva;

class ImageResize
{

    public $jpg_quality = 75;

    protected $image;
    protected $image_type;

    public function __construct($filename)
    {
        $this->load($filename);
    }

    public function load($filename)
    {
        $image_info = getimagesize($filename);
        $this->image_type = $image_info[2];

        switch($this->image_type) {
            case IMAGETYPE_JPEG:
                $this->image = imagecreatefromjpeg($filename);
            break;

            case IMAGETYPE_GIF:
                $this->image = imagecreatefromgif($filename);
            break;

            case IMAGETYPE_PNG:
                $this->image = imagecreatefrompng($filename);
            break;
        }

        return $this;
    }

    public function save($filename, $image_type = null, $jpg_quality = null, $permissions = null)
    {
        $image_type = $image_type ?: $this->image_type;
        $jpg_quality = $jpg_quality ?: $this->jpg_quality;

        switch($image_type) {
            case IMAGETYPE_JPEG:
                imagejpeg($this->image, $filename, $jpg_quality);
            break;

            case IMAGETYPE_GIF:
                imagegif($this->image, $filename);
            break;

            case IMAGETYPE_PNG:
                imagepng($this->image, $filename);
            break;
        }

        if ($permissions != null) {
            chmod($filename, $permissions);
        }

        return $this;
    }

    public function output($image_type = null, $jpg_quality = null)
    {
        $this->save(null, $image_type, $jpg_quality);
    }

    public function getWidth()
    {
        return imagesx($this->image);
    }

    public function getHeight()
    {
        return imagesy($this->image);
    }

    public function resizeToHeight($height)
    {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;

        $this->resize($width, $height);

        return $this;
    }

    public function resizeToWidth($width)
    {
        $ratio  = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;

        $this->resize($width, $height);

        return $this;
    }

    public function scale($scale)
    {
        $width  = $this->getWidth() * $scale / 100;
        $height = $this->getheight() * $scale / 100;

        $this->resize($width, $height);

        return $this;
    }

    public function resize($width, $height, $forcesize = false)
    {
        /* optional. if file is smaller, do not resize. */
        if ($forcesize === false) {
            if ($width > $this->getWidth() && $height > $this->getHeight()) {
                $width  = $this->getWidth();
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
    public function crop($width, $height)
    {
        $aspect_o = $this->getWidth() / $this->getHeight();
        $aspect_f = $width / $height;

        if ($aspect_o >= $aspect_f) {
            $width_n  = $this->getWidth() / ($this->getHeight() / $height);
            $height_n = $height;
        } else {
            $width_n  = $width;
            $height_n = $this->getHeight() / ($this->getWidth() / $width);
        }

        $new_image = imagecreatetruecolor($width, $height);

        /* Check if this image is PNG or GIF, then set if Transparent */
        if (($this->image_type == IMAGETYPE_GIF) || ($this->image_type == IMAGETYPE_PNG)) {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
        }

        imagecopyresampled($new_image, $this->image, 0 - ($width_n - $width) * 0.5, 0 - ($height_n - $height) * 0.5, 0, 0, $width_n, $height_n, $this->getWidth(), $this->getHeight());

        $this->image = $new_image;

        return $this;
    }

}