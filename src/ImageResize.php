<?php

namespace Eventviva;

class ImageResize
{

    public $quality_jpg = 75;
    public $quality_png = 0;

    protected $source_image;
    protected $source_type;

    protected $original_w;
    protected $original_h;

    protected $dest_x = 0;
    protected $dest_y = 0;

    protected $source_x;
    protected $source_y;

    protected $dest_w;
    protected $dest_h;

    protected $source_w;
    protected $source_h;

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

        list (
            $this->original_w,
            $this->original_h,
            $this->source_type
        ) = $image_info;

        switch ($this->source_type) {
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

        $dest_image = imagecreatetruecolor($this->dest_w, $this->dest_h);

        switch ($image_type) {
            case IMAGETYPE_GIF:
                $background = imagecolorallocatealpha($dest_image, 255, 255, 255, 1);
                imagecolortransparent($dest_image, $background);
                imagefill($dest_image, 0, 0 , $background);
                imagesavealpha($dest_image, true);
            break;

            case IMAGETYPE_JPEG:
                $background = imagecolorallocate($dest_image, 255, 255, 255);
                imagefilledrectangle($dest_image, 0, 0, $this->dest_w, $this->dest_h, $background);
            break;

            case IMAGETYPE_PNG:
                imagealphablending($dest_image, false);
                imagesavealpha($dest_image, true);
            break;
        }

        imagecopyresampled(
            $dest_image,
            $this->source_image,
            $this->dest_x,
            $this->dest_y,
            $this->source_x,
            $this->source_y,
            $this->dest_w,
            $this->dest_h,
            $this->source_w,
            $this->source_h
        );

        switch ($image_type) {
            case IMAGETYPE_GIF:
                imagegif($dest_image, $filename);
            break;

            case IMAGETYPE_JPEG:
                if ($quality === null) {
                    $quality = $this->quality_jpg;
                }

                imagejpeg($dest_image, $filename, $quality);
            break;

            case IMAGETYPE_PNG:
                if ($quality === null) {
                    $quality = $this->quality_png;
                }

                imagepng($dest_image, $filename, $quality);
            break;
        }

        if ($permissions) {
            chmod($filename, $permissions);
        }

        return $this;
    }

    public function output($image_type = null, $quality = null)
    {
        $image_type = $image_type ?: $this->source_type;

        switch ($image_type) {
            case IMAGETYPE_GIF:
                $content_type = 'image/gif';
            break;

            case IMAGETYPE_JPEG:
                $content_type = 'image/jpg';
            break;

            case IMAGETYPE_PNG:
                $content_type = 'image/png';
            break;
        }

        header('Content-Type: ' . $content_type);

        $this->save(null, $image_type, $quality);
    }

    public function resizeToHeight($height)
    {
        $ratio = $height / $this->original_h;
        $width = $this->original_w * $ratio;

        $this->resize($width, $height);

        return $this;
    }

    public function resizeToWidth($width)
    {
        $ratio  = $width / $this->original_w;
        $height = $this->original_h * $ratio;

        $this->resize($width, $height);

        return $this;
    }

    public function scale($scale)
    {
        $width  = $this->original_w * $scale / 100;
        $height = $this->original_h * $scale / 100;

        $this->resize($width, $height, true);

        return $this;
    }

    public function resize($width, $height, $allow_enlarge = false)
    {
        if (!$allow_enlarge) {
            if ($width > $this->original_w || $height > $this->original_h) {
                $width  = $this->original_w;
                $height = $this->original_h;
            }
        }

        $this->source_x = 0;
        $this->source_y = 0;

        $this->dest_w = $width;
        $this->dest_h = $height;

        $this->source_w = $this->original_w;
        $this->source_h = $this->original_h;

        return $this;
    }

    public function crop($width, $height, $allow_enlarge = false)
    {
        if (!$allow_enlarge) {
            if ($width > $this->original_w || $height > $this->original_h) {
                $width  = $this->original_w;
                $height = $this->original_h;
            }
        }

        $this->resize($width, $height, $allow_enlarge);

        $ratio_source = $this->original_w / $this->original_h;
        $ratio_dest = $width / $height;

        if ($ratio_dest < $ratio_source) {
            $this->source_w *= $ratio_dest;
            $this->source_x = ($this->original_w - $this->source_w) / 2;
        } else {
            $this->source_h /= $ratio_dest;
            $this->source_y = ($this->original_h - $this->source_h) / 2;
        }

        return $this;
    }

}