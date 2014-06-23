<?php

namespace Eventviva;

class ImageResize
{

    public $jpg_quality = 75;
    public $png_quality = 0;

    protected $source_image;
    protected $source_type;

    protected $source_w;
    protected $source_h;

    protected $dest_image;

    public function __construct($filename)
    {
        $this->load($filename);
    }

    public function load($filename)
    {
        $image_info = getimagesize($filename);

        if (!$image_info) {
            throw new \Exception('Could not read ' . $filename);
        }

        list(
            $this->source_w,
            $this->source_h,
            $this->source_type
        ) = $image_info;

        switch($this->source_type) {
            case IMAGETYPE_GIF:
                $this->source_image = imagecreatefromgif($filename);
            break;

            case IMAGETYPE_JPEG:
                $this->source_image = imagecreatefromjpeg($filename);
            break;

            case IMAGETYPE_PNG:
                $this->source_image = imagecreatefrompng($filename);
            break;

            default:
                throw new \Exception('Unsupported image type');
            break;
        }

        return $this;
    }

    public function save($filename, $image_type = null, $quality = null, $permissions = null)
    {
        $image_type = $image_type ?: $this->source_type;
        $quality = $quality !== null ? $quality : $this->getQuality($image_type);

        switch($image_type) {
            case IMAGETYPE_GIF:
                imagegif($this->dest_image, $filename);
            break;

            case IMAGETYPE_JPEG:
                imagejpeg($this->dest_image, $filename, $quality);
            break;

            case IMAGETYPE_PNG:
                imagepng($this->dest_image, $filename, $quality);
            break;
        }

        if ($permissions != null) {
            chmod($filename, $permissions);
        }

        return $this;
    }

    public function output($image_type = null, $quality = null)
    {
        $this->save(null, $image_type, $quality);
    }

    public function resizeToHeight($height)
    {
        $ratio = $height / $this->source_h;
        $width = $this->source_w * $ratio;

        $this->resize($width, $height);

        return $this;
    }

    public function resizeToWidth($width)
    {
        $ratio  = $width / $this->source_w;
        $height = $this->source_h * $ratio;

        $this->resize($width, $height);

        return $this;
    }

    public function scale($scale)
    {
        $width  = $this->source_w * $scale / 100;
        $height = $this->source_h * $scale / 100;

        $this->resize($width, $height);

        return $this;
    }

    public function resize($width, $height, $forcesize = false)
    {
        /* optional. if file is smaller, do not resize. */
        if ($forcesize === false) {
            if ($width > $this->source_w && $height > $this->source_h) {
                $width  = $this->source_w;
                $height = $this->source_h;
            }
        }

        $new_image = imagecreatetruecolor($width, $height);

        /* Check if this image is PNG or GIF, then set if Transparent */
        if (($this->source_type == IMAGETYPE_GIF) || ($this->source_type == IMAGETYPE_PNG)) {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
        }

        imagecopyresampled($new_image, $this->source_image, 0, 0, 0, 0, $width, $height, $this->source_w, $this->source_h);

        $this->dest_image = $new_image;

        return $this;
    }

    /* center crops image to desired width height */
    public function crop($width, $height)
    {
        $aspect_o = $this->source_w / $this->source_h;
        $aspect_f = $width / $height;

        if ($aspect_o >= $aspect_f) {
            $width_n  = $this->source_w / ($this->source_h / $height);
            $height_n = $height;
        } else {
            $width_n  = $width;
            $height_n = $this->source_h / ($this->source_w / $width);
        }

        $new_image = imagecreatetruecolor($width, $height);

        /* Check if this image is PNG or GIF, then set if Transparent */
        if (($this->source_type == IMAGETYPE_GIF) || ($this->source_type == IMAGETYPE_PNG)) {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
        }

        imagecopyresampled($new_image, $this->source_image, 0 - ($width_n - $width) * 0.5, 0 - ($height_n - $height) * 0.5, 0, 0, $width_n, $height_n, $this->source_w, $this->source_h);

        $this->dest_image = $new_image;

        return $this;
    }

    private function getQuality($image_type) {
        switch ($image_type) {
            case IMAGETYPE_JPEG:
                return $this->jpg_quality;
            break;

            case IMAGETYPE_PNG:
                return $this->png_quality;
            break;
        }
    }

}