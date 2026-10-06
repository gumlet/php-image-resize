<?php

namespace Gumlet;

use Exception;

/**
 * PHP class to resize and scale images
 */
class ImageResize
{
    public const CROPTOP = 1;
    public const CROPCENTRE = 2;
    public const CROPCENTER = 2;
    public const CROPBOTTOM = 3;
    public const CROPLEFT = 4;
    public const CROPRIGHT = 5;
    public const CROPTOPCENTER = 6;
    public const IMG_FLIP_HORIZONTAL = 0;
    public const IMG_FLIP_VERTICAL = 1;
    public const IMG_FLIP_BOTH = 2;

    public int $quality_jpg = 85;
    public int $quality_webp = 85;
    public int $quality_avif = 60;
    public int $quality_png = 6;
    public bool $quality_truecolor = true;
    public bool $gamma_correct = false;

    public int $interlace = 1;

    /**
     * Maximum number of source pixels (width × height) allowed when loading an image.
     * Prevents image-bomb attacks where a small compressed file (e.g. a 56 KB PNG)
     * decompresses into a canvas large enough to exhaust PHP memory.
     *
     * Default: 15,000,000 (~3873×3873 px). A 4000×4000 image requires ~61 MB of
     * raw canvas memory, which exhausts a typical 128 M memory_limit.
     * Set to 0 to disable the limit (only do this for fully trusted input).
     */
    public int $max_source_pixels = 15_000_000;

    public int $source_type;

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

    /**
     * Create instance from a string
     *
     * @throws ImageResizeException
     */
    public static function createFromString(string $image_data): self
    {
        if ($image_data === '') {
            throw new ImageResizeException('image_data must not be empty');
        }
        $resize = new self('data://application/octet-stream;base64,' . base64_encode($image_data));

        return $resize;
    }

    /**
     * Add filter function for use right before save image to file.
     */
    public function addFilter(callable $filter): static
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Apply filters.
     *
     * @param $image resource an image resource identifier
     * @param $filterType filter type and default value is IMG_FILTER_NEGATE
     */
    protected function applyFilter($image, $filterType = IMG_FILTER_NEGATE)
    {
        foreach ($this->filters as $function) {
            $function($image, $filterType);
        }
    }

    /**
     * Loads image source and its properties to the instantiated object
     *
     * @throws ImageResizeException
     */
    public function __construct(string $filename)
    {
        if ($filename === '' || (substr($filename, 0, 5) !== 'data:' && !is_file($filename))) {
            throw new ImageResizeException('File does not exist');
        }

        if (!$image_info = getimagesize($filename, $this->source_info)) {
            $image_info = getimagesize($filename);
        }

        if (!$image_info) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $filename);
            if (strstr($mime, 'image') !== false) {
                throw new ImageResizeException('Unsupported image type');
            }

            throw new ImageResizeException('Unsupported file type');
        }

        // Guard against image-bomb attacks: reject images whose uncompressed canvas
        // would exceed the configured pixel limit before calling imagecreatefromXXX().
        if ($this->max_source_pixels > 0 && ($image_info[0] * $image_info[1]) > $this->max_source_pixels) {
            throw new ImageResizeException(
                sprintf(
                    'Source image is too large (%dx%d = %d pixels). Maximum allowed: %d pixels.',
                    $image_info[0],
                    $image_info[1],
                    $image_info[0] * $image_info[1],
                    $this->max_source_pixels
                )
            );
        }

        $this->original_w = $image_info[0];
        $this->original_h = $image_info[1];
        $this->source_type = $image_info[2];

        switch ($this->source_type) {
            case IMAGETYPE_GIF:
                $this->source_image = imagecreatefromgif($filename);
                break;

            case IMAGETYPE_JPEG:
                $jpeg = $this->imageCreateJpegfromExif($filename);
                if ($jpeg === false) {
                    throw new ImageResizeException('Could not load image');
                }
                $this->source_image = $jpeg;

                // set new width and height for image, maybe it has changed
                $this->original_w = imagesx($this->source_image);
                $this->original_h = imagesy($this->source_image);

                break;

            case IMAGETYPE_PNG:
                $this->source_image = imagecreatefrompng($filename);
                break;

            case IMAGETYPE_WEBP:
                $this->source_image = imagecreatefromwebp($filename);
                break;

            case IMAGETYPE_AVIF:
                $this->source_image = imagecreatefromavif($filename);
                $this->original_w = imagesx($this->source_image);
                $this->original_h = imagesy($this->source_image);
                break;

            case IMAGETYPE_BMP:
                $this->source_image = imagecreatefrombmp($filename);
                break;

            default:
                throw new ImageResizeException('Unsupported image type');
        }

        if (!$this->source_image) {
            throw new ImageResizeException('Could not load image');
        }

        $this->resize($this->getSourceWidth(), $this->getSourceHeight());
    }

    // http://stackoverflow.com/a/28819866
    public function imageCreateJpegfromExif(string $filename): \GdImage|false
    {
        $img = imagecreatefromjpeg($filename);
        if ($img === false) {
            return false;
        }

        if (!function_exists('exif_read_data') || !isset($this->source_info['APP1']) || strpos($this->source_info['APP1'], 'Exif') !== 0) {
            return $img;
        }

        try {
            $exif = @exif_read_data($filename);
        } catch (Exception $e) {
            $exif = null;
        }

        if (!$exif || !isset($exif['Orientation'])) {
            return $img;
        }

        $orientation = $exif['Orientation'];

        if ($orientation === 6 || $orientation === 5) {
            $img = imagerotate($img, 270, 0);
        } elseif ($orientation === 3 || $orientation === 4) {
            $img = imagerotate($img, 180, 0);
        } elseif ($orientation === 8 || $orientation === 7) {
            $img = imagerotate($img, 90, 0);
        }

        if ($orientation === 5 || $orientation === 4 || $orientation === 7) {
            imageflip($img, IMG_FLIP_HORIZONTAL);
        }

        return $img;
    }

    /**
     * Saves new image
     *
     * @param array{0: int, 1: int}|false $exact_size
     */
    public function save(
        string|null $filename,
        ?int $image_type = null,
        int|null $quality = null,
        ?int $permissions = null,
        array|false $exact_size = false
    ): static {
        $image_type = $image_type ?: $this->source_type;
        $quality = is_numeric($quality) ? (int) abs($quality) : null;

        switch ($image_type) {
            case IMAGETYPE_GIF:
                if (!empty($exact_size) && is_array($exact_size)) {
                    $dest_image = imagecreatetruecolor($exact_size[0], $exact_size[1]);
                } else {
                    $dest_image = imagecreatetruecolor((int) $this->getDestWidth(), (int) $this->getDestHeight());
                }

                $background = imagecolorallocatealpha($dest_image, 255, 255, 255, 1);
                imagecolortransparent($dest_image, $background);
                imagefill($dest_image, 0, 0, $background);
                imagesavealpha($dest_image, true);
                break;

            case IMAGETYPE_JPEG:
                if (!empty($exact_size) && is_array($exact_size)) {
                    $dest_image = imagecreatetruecolor($exact_size[0], $exact_size[1]);
                    $background = imagecolorallocate($dest_image, 255, 255, 255);
                    imagefilledrectangle($dest_image, 0, 0, $exact_size[0], $exact_size[1], $background);
                } else {
                    $dest_image = imagecreatetruecolor((int) $this->getDestWidth(), (int) $this->getDestHeight());
                    $background = imagecolorallocate($dest_image, 255, 255, 255);
                    imagefilledrectangle($dest_image, 0, 0, (int) $this->getDestWidth(), (int) $this->getDestHeight(), $background);
                }
                break;

            case IMAGETYPE_WEBP:
                if (!empty($exact_size) && is_array($exact_size)) {
                    $dest_image = imagecreatetruecolor($exact_size[0], $exact_size[1]);
                    $background = imagecolorallocate($dest_image, 255, 255, 255);
                    imagefilledrectangle($dest_image, 0, 0, $exact_size[0], $exact_size[1], $background);
                } else {
                    $dest_image = imagecreatetruecolor((int) $this->getDestWidth(), (int) $this->getDestHeight());
                    $background = imagecolorallocate($dest_image, 255, 255, 255);
                    imagefilledrectangle($dest_image, 0, 0, (int) $this->getDestWidth(), (int) $this->getDestHeight(), $background);
                }

                imagealphablending($dest_image, false);
                imagesavealpha($dest_image, true);

                break;

            case IMAGETYPE_AVIF:
                if (!empty($exact_size) && is_array($exact_size)) {
                    $dest_image = imagecreatetruecolor($exact_size[0], $exact_size[1]);
                    $background = imagecolorallocate($dest_image, 255, 255, 255);
                    imagefilledrectangle($dest_image, 0, 0, $exact_size[0], $exact_size[1], $background);
                } else {
                    $dest_image = imagecreatetruecolor((int) $this->getDestWidth(), (int) $this->getDestHeight());
                    $background = imagecolorallocate($dest_image, 255, 255, 255);
                    imagefilledrectangle($dest_image, 0, 0, (int) $this->getDestWidth(), (int) $this->getDestHeight(), $background);
                }

                imagealphablending($dest_image, false);
                imagesavealpha($dest_image, true);

                break;

            case IMAGETYPE_PNG:
                if (!$this->quality_truecolor || !imageistruecolor($this->source_image)) {
                    if (!empty($exact_size) && is_array($exact_size)) {
                        $dest_image = imagecreate($exact_size[0], $exact_size[1]);
                    } else {
                        $dest_image = imagecreate((int) $this->getDestWidth(), (int) $this->getDestHeight());
                    }
                } else {
                    if (!empty($exact_size) && is_array($exact_size)) {
                        $dest_image = imagecreatetruecolor($exact_size[0], $exact_size[1]);
                    } else {
                        $dest_image = imagecreatetruecolor((int) $this->getDestWidth(), (int) $this->getDestHeight());
                    }
                }

                imagealphablending($dest_image, false);
                imagesavealpha($dest_image, true);

                $background = imagecolorallocatealpha($dest_image, 255, 255, 255, 127);
                imagecolortransparent($dest_image, $background);
                imagefill($dest_image, 0, 0, $background);
                break;

            case IMAGETYPE_BMP:
                if (!empty($exact_size) && is_array($exact_size)) {
                    $dest_image = imagecreatetruecolor($exact_size[0], $exact_size[1]);
                    $background = imagecolorallocate($dest_image, 255, 255, 255);
                    imagefilledrectangle($dest_image, 0, 0, $exact_size[0], $exact_size[1], $background);
                } else {
                    $dest_image = imagecreatetruecolor((int) $this->getDestWidth(), (int) $this->getDestHeight());
                    $background = imagecolorallocate($dest_image, 255, 255, 255);
                    imagefilledrectangle($dest_image, 0, 0, (int) $this->getDestWidth(), (int) $this->getDestHeight(), $background);
                }
                break;

            default:
                throw new ImageResizeException('Unsupported image type');
        }

        imageinterlace($dest_image, $this->interlace);

        if ($this->gamma_correct) {
            imagegammacorrect($this->source_image, 2.2, 1.0);
        }

        if (!empty($exact_size) && is_array($exact_size)) {
            if ($this->getSourceHeight() < $this->getSourceWidth()) {
                $this->dest_x = 0;
                $this->dest_y = ($exact_size[1] - $this->getDestHeight()) / 2;
            }
            if ($this->getSourceHeight() > $this->getSourceWidth()) {
                $this->dest_x = ($exact_size[0] - $this->getDestWidth()) / 2;
                $this->dest_y = 0;
            }
        }

        imagecopyresampled(
            $dest_image,
            $this->source_image,
            $this->dest_x,
            $this->dest_y,
            (int) $this->source_x,
            (int) $this->source_y,
            (int) $this->getDestWidth(),
            (int) $this->getDestHeight(),
            (int) $this->source_w,
            (int) $this->source_h
        );

        if ($this->gamma_correct) {
            imagegammacorrect($dest_image, 1.0, 2.2);
        }


        $this->applyFilter($dest_image);

        switch ($image_type) {
            case IMAGETYPE_GIF:
                imagegif($dest_image, $filename);
                break;

            case IMAGETYPE_JPEG:
                if ($quality === null || $quality > 100) {
                    $quality = $this->quality_jpg;
                }

                imagejpeg($dest_image, $filename, $quality);
                break;

            case IMAGETYPE_WEBP:
                if ($quality === null) {
                    $quality = $this->quality_webp;
                }

                imagewebp($dest_image, $filename, $quality);
                break;

            case IMAGETYPE_AVIF:
                if ($quality === null) {
                    $quality = $this->quality_avif;
                }

                imageavif($dest_image, $filename, $quality);
                break;

            case IMAGETYPE_PNG:
                if ($quality === null || $quality > 9) {
                    $quality = $this->quality_png;
                }

                imagepng($dest_image, $filename, $quality);
                break;

            case IMAGETYPE_BMP:
                imagebmp($dest_image, $filename);
                break;

            default:
                throw new ImageResizeException('Unsupported image type');
        }

        if ($permissions && is_string($filename)) {
            chmod($filename, $permissions);
        }

        return $this;
    }

    public function getImageAsString(?int $image_type = null, int|string|null $quality = null): string
    {
        $string_temp = tempnam(sys_get_temp_dir(), '');

        $this->save($string_temp, $image_type, $quality);

        $string = file_get_contents($string_temp);

        unlink($string_temp);

        return $string;
    }

    public function __toString(): string
    {
        return $this->getImageAsString();
    }

    public function output(?int $image_type = null, int|string|null $quality = null): void
    {
        $image_type = $image_type ?: $this->source_type;

        header('Content-Type: ' . image_type_to_mime_type($image_type));

        $this->save(null, $image_type, $quality);
    }

    public function resizeToShortSide(int $max_short, bool $allow_enlarge = false): static
    {
        if ($this->getSourceHeight() < $this->getSourceWidth()) {
            $ratio = $max_short / $this->getSourceHeight();
            $long = (int) round($this->getSourceWidth() * $ratio);

            $this->resize($long, $max_short, $allow_enlarge);
        } else {
            $ratio = $max_short / $this->getSourceWidth();
            $long = (int) round($this->getSourceHeight() * $ratio);

            $this->resize($max_short, $long, $allow_enlarge);
        }

        return $this;
    }

    public function resizeToLongSide(int $max_long, bool $allow_enlarge = false): static
    {
        if ($this->getSourceHeight() > $this->getSourceWidth()) {
            $ratio = $max_long / $this->getSourceHeight();
            $short = (int) round($this->getSourceWidth() * $ratio);

            $this->resize($short, $max_long, $allow_enlarge);
        } else {
            $ratio = $max_long / $this->getSourceWidth();
            $short = (int) round($this->getSourceHeight() * $ratio);

            $this->resize($max_long, $short, $allow_enlarge);
        }

        return $this;
    }

    public function resizeToHeight(int $height, bool $allow_enlarge = false): static
    {
        $ratio = $height / $this->getSourceHeight();
        $width = (int) round($this->getSourceWidth() * $ratio);

        $this->resize($width, $height, $allow_enlarge);

        return $this;
    }

    public function resizeToWidth(int $width, bool $allow_enlarge = false): static
    {
        $ratio  = $width / $this->getSourceWidth();
        $height = (int) round($this->getSourceHeight() * $ratio);

        $this->resize($width, $height, $allow_enlarge);

        return $this;
    }

    public function resizeToBestFit(int $max_width, int $max_height, bool $allow_enlarge = false): static
    {
        if ($this->getSourceWidth() <= $max_width && $this->getSourceHeight() <= $max_height && $allow_enlarge === false) {
            return $this;
        }

        $ratio  = $this->getSourceHeight() / $this->getSourceWidth();
        $width = $max_width;
        $height = (int) round($width * $ratio);

        if ($height > $max_height) {
            $height = $max_height;
            $width = (int) round($height / $ratio);
        }

        return $this->resize($width, $height, $allow_enlarge);
    }

    public function scale(int|float $scale): static
    {
        $width  = (int) round($this->getSourceWidth() * $scale / 100);
        $height = (int) round($this->getSourceHeight() * $scale / 100);

        $this->resize($width, $height, true);

        return $this;
    }

    public function resize(int $width, int $height, bool $allow_enlarge = false): static
    {
        if (!$allow_enlarge) {
            // if the user hasn't explicitly allowed enlarging,
            // but either of the dimensions are larger then the original,
            // then just use original dimensions - this logic may need rethinking

            if ($width > $this->getSourceWidth() || $height > $this->getSourceHeight()) {
                $width  = $this->getSourceWidth();
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

    public function crop(int $width, int $height, bool $allow_enlarge = false, int $position = self::CROPCENTER): static
    {
        if (!$allow_enlarge) {
            // this logic is slightly different to resize(),
            // it will only reset dimensions to the original
            // if that particular dimenstion is larger

            if ($width > $this->getSourceWidth()) {
                $width  = $this->getSourceWidth();
            }

            if ($height > $this->getSourceHeight()) {
                $height = $this->getSourceHeight();
            }
        }

        $ratio_source = $this->getSourceWidth() / $this->getSourceHeight();
        $ratio_dest = $width / $height;

        if ($ratio_dest < $ratio_source) {
            $this->resizeToHeight($height, $allow_enlarge);

            $excess_width = (int) round(($this->getDestWidth() - $width) * $this->getSourceWidth() / $this->getDestWidth());

            $this->source_w = $this->getSourceWidth() - $excess_width;
            $this->source_x = $this->getCropPosition($excess_width, $position);

            $this->dest_w = $width;
        } else {
            $this->resizeToWidth($width, $allow_enlarge);

            $excess_height = (int) round(($this->getDestHeight() - $height) * $this->getSourceHeight() / $this->getDestHeight());

            $this->source_h = $this->getSourceHeight() - $excess_height;
            $this->source_y = $this->getCropPosition($excess_height, $position);

            $this->dest_h = $height;
        }

        return $this;
    }

    public function freecrop(int $width, int $height, int|false $x = false, int|false $y = false): static
    {
        if ($x === false || $y === false) {
            return $this->crop($width, $height);
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

    public function getSourceWidth(): int
    {
        return $this->original_w;
    }

    public function getSourceHeight(): int
    {
        return $this->original_h;
    }

    public function getDestWidth(): int|float
    {
        return $this->dest_w;
    }

    public function getDestHeight(): int|float
    {
        return $this->dest_h;
    }

    /**
     * Gets crop position (X or Y) according to the given position
     *
     * @param integer $expectedSize
     * @param integer $position
     * @return integer
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
        return (int) round($size);
    }

    public function gamma(bool $enable = false): static
    {
        $this->gamma_correct = $enable;

        return $this;
    }
}
