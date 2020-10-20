<?php

namespace Gumlet;

use Exception;

/**
 * PHP class to resize and scale images
 */
class ImageResize implements ImageResizeInterface
{
    /**
     * @var int JPEG output quality
     */
    public $quality_jpg = 85;
    
    /**
     * @var int WEBP output quality
     */
    public $quality_webp = 85;
    
    /**
     * @var int PNG output quality
     */
    public $quality_png = 6;
    
    /**
     * @var bool
     */
    public $quality_truecolor = true;
    
    /**
     * @var bool
     */
    public $gamma_correction = false;
    
    /**
     * @var int
     */
    public $interlace = 1;
    
    /**
     * @var mixed
     */
    public $source_type;
    
    protected $source_image;
    
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
    
    protected $source_info;
    
    protected $filters = [];
    
    public static function createFromString($image_data)
    {
        if (empty( $image_data ) || $image_data === null) {
            throw new ImageResizeException( __CLASS__ . ' ERROR: image_data must not be empty.' );
        }
        return new self( 'data://application/octet-stream;base64,'.base64_encode( $image_data ) );
    }
    
    public function __construct($filename)
    {
        if (!defined('IMAGETYPE_WEBP')) {
            define('IMAGETYPE_WEBP', 18);
        }
        
        if ($filename === null || empty($filename))
            throw new ImageResizeException(__CLASS__ . " ERROR: No filename given");
        
        if (substr( $filename, 0, 5 ) !== 'data:' && !is_file( $filename )) {
            throw new ImageResizeException( __CLASS__ . " ERROR: Not a file or valid datastream" );
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (false === $finfo)
            throw new ImageResizeException(__CLASS__ . " ERROR: Can't retrieve file info.");
        
        $checkWEBP = false;
        
        if (strstr( finfo_file( $finfo, $filename ), 'image' ) === false) {
            if (version_compare( PHP_VERSION, '7.0.0', '<=' ) && strstr( file_get_contents( $filename, false, null, 0, 50 ), 'WEBPVP8' ) !== false) {
                $checkWEBP = true;
                $this->source_type = IMAGETYPE_WEBP;
            } else {
                throw new ImageResizeException( __CLASS__ . ' ERROR: Unsupported file type (WEBP)' );
            }
        } elseif (strstr( finfo_file( $finfo, $filename ), 'image/webp' ) !== false) {
            $checkWEBP = true;
            $this->source_type = IMAGETYPE_WEBP;
        }
        
        if (!$image_info = getimagesize( $filename, $this->source_info )) {
            $image_info = getimagesize( $filename );
        }
        
        if (!$checkWEBP) {
            if (!$image_info) {
                throw new ImageResizeException( __CLASS__ . ' ERROR: Could not read file' );
            }
            
            $this->original_w = $image_info[ 0 ];
            $this->original_h = $image_info[ 1 ];
            $this->source_type = $image_info[ 2 ];
        }
        
        switch ($this->source_type) {
            case IMAGETYPE_GIF: {
                $this->source_image = imagecreatefromgif( $filename );
                break;
            }
            case IMAGETYPE_JPEG: {
                $this->source_image = $this->imageCreateJpegfromExif( $filename );
                break;
            }
            case IMAGETYPE_PNG: {
                $this->source_image = imagecreatefrompng( $filename );
                break;
            }
            case IMAGETYPE_WEBP: {
                $this->source_image = imagecreatefromwebp( $filename );
                break;
            }
            default: {
                throw new ImageResizeException( __CLASS__ . ' ERROR: Unsupported image type' );
            }
        }
       
        if (!$this->source_image) {
            throw new ImageResizeException( __CLASS__ . ' ERROR: Could not load image' );
        }
        
        // set new width and height for image, maybe it has changed
        $this->original_w = imagesx( $this->source_image );
        $this->original_h = imagesy( $this->source_image );
    
        return $this->resize( $this->getSourceWidth(), $this->getSourceHeight() );
    }
    
    public function addFilter(callable $filter)
    {
        $this->filters[] = $filter;
        return $this;
    }
    
    public function imageCreateJpegfromExif($filename)
    {
        $img = imagecreatefromjpeg( $filename );
        if (false === $img)
            throw new ImageResizeException(__CLASS__ . ' ERROR: Loading jpeg failed');
        
        if (!function_exists( 'exif_read_data' ) || !isset( $this->source_info[ 'APP1' ] ) || strpos( $this->source_info[ 'APP1' ], 'Exif' ) !== 0) {
            return $img;
        }
        
        try {
            $exif = @exif_read_data( $filename );
        } catch (Exception $e) {
            $exif = null;
        }
        
        if (!$exif || !isset( $exif[ 'Orientation' ] )) {
            return $img;
        }
        
        $orientation = $exif[ 'Orientation' ];
        
        if ($orientation === 6 || $orientation === 5) {
            $img = imagerotate( $img, 270, null );
        } elseif ($orientation === 3 || $orientation === 4) {
            $img = imagerotate( $img, 180, null );
        } elseif ($orientation === 8 || $orientation === 7) {
            $img = imagerotate( $img, 90, null );
        }
        
        if (false === $img)
            throw new ImageResizeException(__CLASS__ . " ERROR: Rotating image failed");
        
        if ($orientation === 5 || $orientation === 4 || $orientation === 7) {
            if (function_exists( 'imageflip' )) {
                if (false === imageflip( $img, IMG_FLIP_HORIZONTAL ))
                    throw new ImageResizeException(__CLASS__ . ' ERROR: Flipping image failed.');
                
            } else {
                $this->imageFlip( $img, IMG_FLIP_HORIZONTAL );
            }
        }
        
        return $img;
    }
    
    public function save($filename, $image_type = null, $quality = null, $permissions = null, $exact_size = false)
    {
        $image_type = $image_type ?: $this->source_type;
        $quality = is_numeric( $quality ) ? (int)abs( $quality ) : null;
        $source_image = $this->source_image;
    
        if (!empty($exact_size) && is_array($exact_size)) {
            $_width = $exact_size[0];
            $_height = $exact_size[1];
        } else {
            $_width = $this->getDestWidth();
            $_height = $this->getDestHeight();
        }
        
        // Prepare image pattern for conversion
        
        switch ($image_type) {
            case IMAGETYPE_GIF: {
                $dest_image = imagecreatetruecolor( $_width, $_height );
                if (false === $dest_image)
                    throw new ImageResizeException(__CLASS__ . ' Error creating image/gif resource');
    
                $transparent_color = imagecolorallocatealpha( $dest_image, 255, 255, 255, 1 );
                if (false === $transparent_color)
                    throw new ImageResizeException(__CLASS__ . ' Error creating background alpha color.');
    
                $transparent_color = imagecolortransparent( $dest_image, $transparent_color );
                if (false === $transparent_color)
                    throw new ImageResizeException(__CLASS__ . ' Error defining color as transparent.');
                
                if (false === imagefill( $dest_image, 0, 0, $transparent_color ))
                    throw new ImageResizeException(__CLASS__ . ' Error: filling image with background alpha color failed.');
    
                //@todo: ???  https://stackoverflow.com/a/11920133/5127037
                if (false === imagesavealpha( $dest_image, true ))
                    throw new ImageResizeException(__CLASS__ . ' Error: setting the flag to save full alpha channel information.');
                
                break;
            }
    
            case IMAGETYPE_JPEG: {
                $dest_image = imagecreatetruecolor( $_width, $_height );
                if (false === $dest_image)
                    throw new ImageResizeException(__CLASS__ . ' Error creating image/jpeg resource');
                
                $transparent_color = imagecolorallocate( $dest_image, 255, 255, 255 );
                if (false === $transparent_color)
                    throw new ImageResizeException(__CLASS__ . ' Error creating background alpha color');
                
                if (false === imagefilledrectangle( $dest_image, 0, 0, $_width, $_height, $transparent_color ))
                    throw new ImageResizeException(__CLASS__ . ' Error: filling image with background color failed');
                
                break;
            }
    
            case IMAGETYPE_WEBP: {
                if (version_compare( PHP_VERSION, '5.5.0', '<' ))
                    throw new ImageResizeException( __CLASS__ . ' For WebP support PHP >= 5.5.0 is required' );
                
                $dest_image = imagecreatetruecolor( $_width, $_height );
                if (false === $dest_image)
                    throw new ImageResizeException(__CLASS__ . ' Error creating image/webp resource');
                
                $transparent_color = imagecolorallocate( $dest_image, 255, 255, 255 );
                if (false === $transparent_color)
                    throw new ImageResizeException(__CLASS__ . ' Error creating background alpha color');
    
                if (false === imagefilledrectangle( $dest_image, 0, 0, $_width, $_height, $transparent_color ))
                    throw new ImageResizeException(__CLASS__ . ' Error: filling image with background color failed');
    
                if (false === imagealphablending( $dest_image, false ))
                    throw new ImageResizeException(__CLASS__ . ' Error setting blending mode for webp image');
                
                if (false === imagesavealpha( $dest_image, true ))
                    throw new ImageResizeException(__CLASS__ . ' Error setting SAVE ALPHA flag');
    
                break;
            }
            
            case IMAGETYPE_PNG: {
                if (!$this->quality_truecolor && !imageistruecolor( $source_image )) {
                    $dest_image = imagecreate( $_width, $_height );
                } else {
                    $dest_image = imagecreatetruecolor( $_width, $_height );
                }
                if (false === $dest_image)
                    throw new ImageResizeException(__CLASS__ . ' Error creating image/png resource');
    
                if (false === imagealphablending( $dest_image, false ))
                    throw new ImageResizeException(__CLASS__ . ' Error setting blending mode for webp image');
    
                if (false === imagesavealpha( $dest_image, true ))
                    throw new ImageResizeException(__CLASS__ . ' Error setting SAVE ALPHA flag');
    
                $transparent_color = imagecolorallocatealpha( $dest_image, 255, 255, 255, 127 );
                if (false === $transparent_color)
                    throw new ImageResizeException(__CLASS__ . ' Error creating background alpha color');
    
                $transparent_color = imagecolortransparent( $dest_image, $transparent_color );
                if (false === $transparent_color)
                    throw new ImageResizeException(__CLASS__ . ' Error defining color as transparent');
    
                if (false === imagefill( $dest_image, 0, 0, $transparent_color ))
                    throw new ImageResizeException(__CLASS__ . ' Error: filling image with background alpha color failed');
                
                break;
            }
        }
        
        if (false === imageinterlace( $dest_image, $this->interlace ))
            throw new ImageResizeException(__CLASS__ . ' Error setting interlace flag');
        
        if (!empty( $exact_size ) && is_array( $exact_size )) {
            if ($this->getSourceHeight() < $this->getSourceWidth()) {
                $this->dest_x = 0;
                $this->dest_y = ($exact_size[ 1 ] - $this->getDestHeight()) / 2;
            }
            if ($this->getSourceHeight() > $this->getSourceWidth()) {
                $this->dest_x = ($exact_size[ 0 ] - $this->getDestWidth()) / 2;
                $this->dest_y = 0;
            }
        }
    
        if ($this->gamma_correction) {
            if (false === imagegammacorrect( $source_image, 2.2, 1.0 ))
                throw new ImageResizeException(__CLASS__ . ' Error image gamma correction (2.2 -> 1.0)');
        }
        
        if (false === imagecopyresampled(
            $dest_image,
            $source_image,
            $this->dest_x,
            $this->dest_y,
            $this->source_x,
            $this->source_y,
            $this->getDestWidth(),
            $this->getDestHeight(),
            $this->source_w,
            $this->source_h
        )) throw new ImageResizeException(__CLASS__ . 'ERROR: Resample image failed');
        
        if ($this->gamma_correction) {
            if (false === imagegammacorrect( $dest_image, 1.0, 2.2 ))
                throw new ImageResizeException(__CLASS__ . ' ERROR: Correction image gamma failed (1.0 -> 2.2)');
            if (false === imagegammacorrect( $source_image, 1.0, 2.2 ))
                throw new ImageResizeException(__CLASS__ . ' ERROR: Correction source image gamma failed (1.0 -> 2.2)');
        }
        
        $this->applyFilter( $dest_image );
        
        // Save image
        
        switch ($image_type) {
            case IMAGETYPE_GIF: {
                if (false === imagegif( $dest_image, $filename ))
                    throw new ImageResizeException(__CLASS__ . ' ERROR: storing GIF image failed');
                
                break;
            }
            case IMAGETYPE_JPEG: {
                if ($quality === null || $quality > 100) {
                    $quality = $this->quality_jpg;
                }
    
                if (false === imagejpeg( $dest_image, $filename, $quality ))
                    throw new ImageResizeException(__CLASS__ . ' ERROR: storing JPEG image failed');
                
                break;
            }
            case IMAGETYPE_WEBP: {
                if (version_compare( PHP_VERSION, '5.5.0', '<' )) {
                    throw new ImageResizeException( __CLASS__ . ' ERROR: PHP > 5.5.0 required for storing images to WebP format' );
                }
                if ($quality === null) {
                    $quality = $this->quality_webp;
                }
                
                if (false === imagewebp( $dest_image, $filename, $quality ))
                    throw new ImageResizeException(__CLASS__ . ' ERROR: storing WEBP image failed');
                break;
            }
            case IMAGETYPE_PNG: {
                if ($quality === null || $quality > 9) {
                    $quality = $this->quality_png;
                }
    
                if (false === imagepng( $dest_image, $filename, $quality ))
                    throw new ImageResizeException(__CLASS__ . ' ERROR: storing PNG image failed');
                
                break;
            }
        }
        
        if ($permissions) {
            if (false === chmod( $filename, $permissions ))
                throw new ImageResizeException(__CLASS__ . ' ERROR: setting destination file permissions failed');
        }
        
        if (false === imagedestroy( $dest_image ))
            throw new ImageResizeException(__CLASS__ . ' ERROR: cleaning temporary image failed.');
        
        return $this;
    }
    
    public function getImageAsString($image_type = null, $quality = null)
    {
        $temporary_filename = tempnam( sys_get_temp_dir(), '' );
        if (false === $temporary_filename)
            throw new ImageResizeException(__CLASS__ . 'ERROR: generating temporary filename failed');
        
        $this->save( $temporary_filename, $image_type, $quality );
        
        $data = file_get_contents( $temporary_filename );
        if (false === $data)
            throw new ImageResizeException(__CLASS__ . ' ERROR: loading temporary file failed');
        
        if (false === unlink( $temporary_filename ))
            throw new ImageResizeException(__CLASS__ . ' ERROR: unlinking temporary file failed');
        
        return $data;
    }
    
    public function __toString()
    {
        return $this->getImageAsString();
    }
    
    public function output($image_type = null, $quality = null)
    {
        $image_type = $image_type ?: $this->source_type;
        
        header( 'Content-Type: ' . image_type_to_mime_type( $image_type ) );
        
        $this->save( null, $image_type, $quality );
    }
    
    public function resizeToShortSide($max_short, $allow_enlarge = false)
    {
        if ($this->getSourceHeight() < $this->getSourceWidth()) {
            $ratio = $max_short / $this->getSourceHeight();
            $long = $this->getSourceWidth() * $ratio;

            $this->resize($long, $max_short, $allow_enlarge);
        } else {
            $ratio = $max_short / $this->getSourceWidth();
            $long = $this->getSourceHeight() * $ratio;

            $this->resize($max_short, $long, $allow_enlarge);
        }

        return $this;
    }
    
    public function resizeToLongSide($max_long, $allow_enlarge = false)
    {
        if ($this->getSourceHeight() > $this->getSourceWidth()) {
            $ratio = $max_long / $this->getSourceHeight();
            $short = $this->getSourceWidth() * $ratio;
            
            $this->resize( $short, $max_long, $allow_enlarge );
        } else {
            $ratio = $max_long / $this->getSourceWidth();
            $short = $this->getSourceHeight() * $ratio;
            
            $this->resize( $max_long, $short, $allow_enlarge );
        }
        
        return $this;
    }
    
    public function resizeToHeight($height, $allow_enlarge = false)
    {
        $ratio = $height / $this->getSourceHeight();
        $width = $this->getSourceWidth() * $ratio;
        
        $this->resize( $width, $height, $allow_enlarge );
        
        return $this;
    }
    
    public function resizeToWidth($width, $allow_enlarge = false)
    {
        $ratio = $width / $this->getSourceWidth();
        $height = $this->getSourceHeight() * $ratio;
        
        $this->resize( $width, $height, $allow_enlarge );
        
        return $this;
    }
    
    public function resizeToBestFit($max_width, $max_height, $allow_enlarge = false)
    {
        if ($this->getSourceWidth() <= $max_width && $this->getSourceHeight() <= $max_height && $allow_enlarge === false) {
            return $this;
        }
        
        $ratio = $this->getSourceHeight() / $this->getSourceWidth();
        $width = $max_width;
        $height = $width * $ratio;
        
        if ($height > $max_height) {
            $height = $max_height;
            $width = (int)round( $height / $ratio );
        }
        
        return $this->resize( $width, $height, $allow_enlarge );
    }
    
    public function scale($scale)
    {
        if ($scale === 100)
            return $this;
        
        $width = $this->getSourceWidth() * $scale / 100;
        $height = $this->getSourceHeight() * $scale / 100;
        
        $this->resize( $width, $height, true );
        
        return $this;
    }
    
    public function resize($width, $height, $allow_enlarge = false)
    {
        if (!$allow_enlarge) {
            // if the user hasn't explicitly allowed enlarging,
            // but either of the dimensions are larger then the original,
            // then just use original dimensions - this logic may need rethinking
            
            if ($width > $this->getSourceWidth() || $height > $this->getSourceHeight()) {
                $width = $this->getSourceWidth();
                $height = $this->getSourceHeight();
            }
        }
        
        $this->source_x = 0;
        $this->source_y = 0;
        
        $this->dest_w = $width;
        $this->dest_h = $height;
        
        $this->source_w = $this->getSourceWidth();
        $this->source_h = $this->getSourceHeight();
        
        return $this;
    }
    
    public function crop($width, $height, $allow_enlarge = false, $position = self::CROPCENTER)
    {
        if (!$allow_enlarge) {
            // this logic is slightly different to resize(),
            // it will only reset dimensions to the original
            // if that particular dimenstion is larger
            
            if ($width > $this->getSourceWidth()) {
                $width = $this->getSourceWidth();
            }
            
            if ($height > $this->getSourceHeight()) {
                $height = $this->getSourceHeight();
            }
        }
        
        $ratio_source = $this->getSourceWidth() / $this->getSourceHeight();
        $ratio_dest = $width / $height;
        
        if ($ratio_dest < $ratio_source) {
            $this->resizeToHeight( $height, $allow_enlarge );
            
            $excess_width = ($this->getDestWidth() - $width) / $this->getDestWidth() * $this->getSourceWidth();
            
            $this->source_w = $this->getSourceWidth() - $excess_width;
            $this->source_x = $this->getCropPosition( $excess_width, $position );
            
            $this->dest_w = $width;
        } else {
            $this->resizeToWidth( $width, $allow_enlarge );
            
            $excess_height = ($this->getDestHeight() - $height) / $this->getDestHeight() * $this->getSourceHeight();
            
            $this->source_h = $this->getSourceHeight() - $excess_height;
            $this->source_y = $this->getCropPosition( $excess_height, $position );
            
            $this->dest_h = $height;
        }
        
        return $this;
    }
    
    public function freecrop($width, $height, $x = false, $y = false)
    {
        if ($x === false || $y === false) {
            return $this->crop( $width, $height );
        }
        $this->source_x = $x;
        $this->source_y = $y;
        if ($width > $this->getSourceWidth() - $x) {
            $this->source_w = $this->getSourceWidth() - $x;
        } else {
            $this->source_w = $width;
        }
        
        if ($height > $this->getSourceHeight() - $y) {
            $this->source_h = $this->getSourceHeight() - $y;
        } else {
            $this->source_h = $height;
        }
        
        $this->dest_w = $width;
        $this->dest_h = $height;
        
        return $this;
    }
    
    public function getSourceWidth()
    {
        return $this->original_w;
    }
    
    public function getSourceHeight()
    {
        return $this->original_h;
    }
    
    public function getDestWidth()
    {
        return $this->dest_w;
    }
    
    public function getDestHeight()
    {
        return $this->dest_h;
    }
    
    /**
     * Apply filters.
     *
     * @param $image - resource an image resource identifier
     * @param $filterType - filter type and default value is IMG_FILTER_NEGATE
     */
    protected function applyFilter($image, $filterType = IMG_FILTER_NEGATE)
    {
        foreach ($this->filters as $function) {
            $function( $image, $filterType );
        }
    }
    
    /**
     * Gets crop position (X or Y) according to the given position
     *
     * @param integer $expectedSize
     * @param integer $position
     * @return float|integer
     */
    protected function getCropPosition($expectedSize, $position = self::CROPCENTER)
    {
        $size = 0;
        switch ($position) {
            case self::CROPBOTTOM:
            case self::CROPRIGHT:
                $size = $expectedSize;
                break;
            case self::CROPCENTER:
            case self::CROPCENTRE:
                $size = $expectedSize / 2;
                break;
            case self::CROPTOPCENTER:
                $size = $expectedSize / 4;
                break;
        }
        return $size;
    }
    
    public function imageFlip($image, $mode)
    {
        switch ($mode) {
            case self::IMG_FLIP_HORIZONTAL:
            {
                $max_x = imagesx( $image ) - 1;
                $half_x = $max_x / 2;
                $sy = imagesy( $image );
                $temp_image = imageistruecolor( $image ) ? imagecreatetruecolor( 1, $sy ) : imagecreate( 1, $sy );
                for ($x = 0; $x < $half_x; ++$x) {
                    imagecopy( $temp_image, $image, 0, 0, $x, 0, 1, $sy );
                    imagecopy( $image, $image, $x, 0, $max_x - $x, 0, 1, $sy );
                    imagecopy( $image, $temp_image, $max_x - $x, 0, 0, 0, 1, $sy );
                }
                break;
            }
            case self::IMG_FLIP_VERTICAL:
            {
                $sx = imagesx( $image );
                $max_y = imagesy( $image ) - 1;
                $half_y = $max_y / 2;
                $temp_image = imageistruecolor( $image ) ? imagecreatetruecolor( $sx, 1 ) : imagecreate( $sx, 1 );
                for ($y = 0; $y < $half_y; ++$y) {
                    imagecopy( $temp_image, $image, 0, 0, 0, $y, $sx, 1 );
                    imagecopy( $image, $image, 0, $y, 0, $max_y - $y, $sx, 1 );
                    imagecopy( $image, $temp_image, 0, $max_y - $y, 0, 0, $sx, 1 );
                }
                break;
            }
            case self::IMG_FLIP_BOTH:
            {
                $sx = imagesx( $image );
                $sy = imagesy( $image );
                $temp_image = imagerotate( $image, 180, 0 );
                imagecopy( $image, $temp_image, 0, 0, 0, 0, $sx, $sy );
                break;
            }
            default:
                return null;
        }
        imagedestroy( $temp_image );
    }
    
    public function gamma($enable = true)
    {
        $this->gamma_correction = $enable;
        return $this;
    }
    
    public function setQualityJPEG($quality)
    {
        $this->quality_jpg = $quality ?: $this->quality_jpg;
        return $this;
    }
    
    public function setQualityPNG($quality)
    {
        $this->quality_png = $quality ?: $this->quality_png;
        return $this;
    }
    
    public function setQualityWebp($quality)
    {
        $this->quality_webp = $quality ?: $this->quality_webp;
        return $this;
    }
    
}
