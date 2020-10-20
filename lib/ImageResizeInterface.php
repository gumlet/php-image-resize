<?php

namespace Gumlet;

interface ImageResizeInterface
{
    const CROPTOP = 1;
    const CROPCENTRE = 2;
    const CROPCENTER = 2;
    const CROPBOTTOM = 3;
    const CROPLEFT = 4;
    const CROPRIGHT = 5;
    const CROPTOPCENTER = 6;
    const IMG_FLIP_HORIZONTAL = 0;
    const IMG_FLIP_VERTICAL = 1;
    const IMG_FLIP_BOTH = 2;
    
    /**
     * Loads image source and its properties to the instanciated object
     *
     * @param string $filename
     * @return ImageResize
     * @throws ImageResizeException
     */
    public function __construct($filename);
    
    /**
     * Create instance from a string
     *
     * @param string $image_data
     * @return ImageResize
     * @throws ImageResizeException
     */
    public static function createFromString($image_data);
    
    /**
     * Add filter function for use right before save image to file.
     *
     * @param callable $filter
     * @return $this
     */
    public function addFilter(callable $filter);
    
    /**
     * Creating
     *
     * http://stackoverflow.com/a/28819866
     *
     * @param $filename
     * @return false|resource
     * @throws ImageResizeException
     */
    public function imageCreateJpegfromExif($filename);
    
    /**
     * Saves new image
     *
     * @param string $filename
     * @param integer $image_type
     * @param integer $quality
     * @param integer $permissions
     * @param boolean $exact_size
     * @return static
     * @throws ImageResizeException
     */
    public function save($filename, $image_type = null, $quality = null, $permissions = null, $exact_size = false);
    
    
    /**
     * Convert the image to string
     *
     * @param int $image_type
     * @param int $quality
     * @return string
     * @throws ImageResizeException
     */
    public function getImageAsString($image_type = null, $quality = null);
    
    /**
     * Convert the image to string with the current settings
     *
     * @return string
     * @throws ImageResizeException
     */
    public function __toString();
    
    /**
     * Outputs image to browser
     *
     * @param string $image_type
     * @param integer $quality
     * @throws ImageResizeException
     */
    public function output($image_type = null, $quality = null);
    
    /**
     * [FLUENT] Resizes image according to the given short side (short side proportional)
     *
     * @param integer $max_short
     * @param boolean $allow_enlarge
     * @return static
     */
    public function resizeToShortSide($max_short, $allow_enlarge = false);
    
    /**
     * [FLUENT] Resizes image according to the given long side (short side proportional)
     *
     * @param integer $max_long
     * @param boolean $allow_enlarge
     * @return static
     */
    public function resizeToLongSide($max_long, $allow_enlarge = false);
    
    /**
     * [FLUENT] Resizes image according to the given height (width proportional)
     *
     * @param integer $height
     * @param boolean $allow_enlarge
     * @return static
     */
    public function resizeToHeight($height, $allow_enlarge = false);
    
    /**
     * [FLUENT] Resizes image according to the given width (height proportional)
     *
     * @param integer $width
     * @param boolean $allow_enlarge
     * @return static
     */
    public function resizeToWidth($width, $allow_enlarge = false);
    
    /**
     * [FLUENT] Resizes image to best fit inside the given dimensions
     *
     * @param integer $max_width
     * @param integer $max_height
     * @param boolean $allow_enlarge
     * @return static
     */
    public function resizeToBestFit($max_width, $max_height, $allow_enlarge = false);
    
    /**
     * [FLUENT] Resizes image according to given scale (proportionally)
     *
     * @param integer|float $scale
     * @return static
     */
    public function scale($scale);
    
    /**
     * [FLUENT] Resizes image according to the given width and height
     *
     * @param integer $width
     * @param integer $height
     * @param boolean $allow_enlarge
     * @return static
     */
    public function resize($width, $height, $allow_enlarge = false);
    
    /**
     * [FLUENT] Crops image according to the given width, height and crop position
     *
     * @param integer $width
     * @param integer $height
     * @param boolean $allow_enlarge
     * @param integer $position
     * @return static
     */
    public function crop($width, $height, $allow_enlarge = false, $position = self::CROPCENTER);
    
    /**
     * [FLUENT] Crops image according to the given width, height, x and y
     *
     * @param integer $width
     * @param integer $height
     * @param mixed $x
     * @param mixed $y
     * @return static
     */
    public function freecrop($width, $height, $x = false, $y = false);
    
    /**
     * Gets source width
     *
     * @return integer
     */
    public function getSourceWidth();
    
    /**
     * Gets source height
     *
     * @return integer
     */
    public function getSourceHeight();
    
    /**
     * Gets width of the destination image
     *
     * @return integer
     */
    public function getDestWidth();
    
    /**
     * Gets height of the destination image
     * @return integer
     */
    public function getDestHeight();
    
    /**
     * Enable or not the gamma color correction on the image, enabled by default
     *
     * @param bool $enable
     * @return static
     */
    public function gamma($enable = true);
    
    /**
     * Flips an image using a given mode if PHP version is lower than 5.5
     *
     * @param resource $image
     * @param integer $mode
     */
    public function imageFlip($image, $mode);
    
    /**
     * Set JPEG Quality
     *
     * @param $quality
     * @return static
     */
    public function setQualityJPEG($quality);
    
    /**
     * @param $quality
     * @return static
     */
    public function setQualityPNG($quality);
    
    /**
     * @param $quality
     * @return static
     */
    public function setQualityWebp($quality);
}
