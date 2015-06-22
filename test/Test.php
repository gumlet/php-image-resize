<?php

include 'src/ImageResize.php';

use \Eventviva\ImageResize;

class ImageResizeTest extends PHPUnit_Framework_TestCase
{

    private $image_types = array(
        'gif',
        'jpeg',
        'png'
    );

    private $unsupported_image = 'Qk08AAAAAAAAADYAAAAoAAAAAQAAAAEAAAABABAAAAAAAAYAAAASCwAAEgsAAAAAAAAAAAAA/38AAAAA';
    private $image_string = 'R0lGODlhAQABAIAAAAQCBP///yH5BAEAAAEALAAAAAABAAEAAAICRAEAOw==';


    /**
     * Loading tests
     */

    public function testLoadGif()
    {
        $image = $this->createImage(1, 1, 'gif');
        $resize = new ImageResize($image);

        $this->assertEquals(IMAGETYPE_GIF, $resize->source_type);
        $this->assertInstanceOf('\Eventviva\ImageResize', $resize);
    }

    public function testLoadJpg()
    {
        $image = $this->createImage(1, 1, 'jpeg');
        $resize = new ImageResize($image);

        $this->assertEquals(IMAGETYPE_JPEG, $resize->source_type);
        $this->assertInstanceOf('\Eventviva\ImageResize', $resize);
    }

    public function testLoadPng()
    {
        $image = $this->createImage(1, 1, 'png');
        $resize = new ImageResize($image);

        $this->assertEquals(IMAGETYPE_PNG, $resize->source_type);
        $this->assertInstanceOf('\Eventviva\ImageResize', $resize);
    }

    public function testLoadString()
    {
        $resize = ImageResize::createFromString(base64_decode($this->image_string));

        $this->assertEquals(IMAGETYPE_GIF, $resize->source_type);
        $this->assertInstanceOf('\Eventviva\ImageResize', $resize);
    }


    /**
     * Bad load tests
     */

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Could not read file
     */
    public function testLoadNoFile()
    {
        new ImageResize(null);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Could not read file
     */
    public function testLoadUnsupportedFile()
    {
        new ImageResize(__FILE__);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Could not read file
     */
    public function testLoadUnsupportedFileString()
    {
        ImageResize::createFromString('');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Unsupported image type
     */
    public function testLoadUnsupportedImage()
    {
        $filename = $this->getTempFile();

        $image = fopen($filename, 'w');
        fwrite($image, base64_decode($this->unsupported_image));
        fclose($image);

        new ImageResize($filename);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Unsupported image type
     */
    public function testInvalidString()
    {
        ImageResize::createFromString(base64_decode($this->unsupported_image));
    }


    /**
     * Resize tests
     */

    public function testResizeToHeight()
    {
        $image = $this->createImage(200, 100, 'png');
        $resize = new ImageResize($image);

        $resize->resizeToHeight(50);

        $this->assertEquals(100, $resize->getDestWidth());
        $this->assertEquals(50, $resize->getDestHeight());
    }

    public function testResizeToWidth()
    {
        $image = $this->createImage(200, 100, 'png');
        $resize = new ImageResize($image);

        $resize->resizeToWidth(100);

        $this->assertEquals(100, $resize->getDestWidth());
        $this->assertEquals(50, $resize->getDestHeight());
    }

    public function testResizeToBestFit()
    {
        $image = $this->createImage(200, 500, 'png');
        $resize = new ImageResize($image);

        $resize->resizeToBestFit(100, 100);

        $this->assertEquals(40, $resize->getDestWidth());
        $this->assertEquals(100, $resize->getDestHeight());
    }

    public function testResizeToBestFitNoEnlarge()
    {
        $image = $this->createImage(200, 100, 'png');
        $resize = new ImageResize($image);

        $resize->resizeToBestFit(250, 250);

        $this->assertEquals(200, $resize->getDestWidth());
        $this->assertEquals(100, $resize->getDestHeight());
    }

    public function testScale()
    {
        $image = $this->createImage(200, 100, 'png');
        $resize = new ImageResize($image);

        $resize->scale(50);

        $this->assertEquals(100, $resize->getDestWidth());
        $this->assertEquals(50, $resize->getDestHeight());
    }

    public function testResize()
    {
        $image = $this->createImage(200, 100, 'png');
        $resize = new ImageResize($image);

        $resize->resize(50, 50);

        $this->assertEquals(50, $resize->getDestWidth());
        $this->assertEquals(50, $resize->getDestHeight());
    }

    public function testResizeLargerNotAllowed()
    {
        $image = $this->createImage(200, 100, 'png');
        $resize = new ImageResize($image);

        $resize->resize(400, 200);

        $this->assertEquals(200, $resize->getDestWidth());
        $this->assertEquals(100, $resize->getDestHeight());
    }

    /**
     * Crop tests
     */

    public function testCrop()
    {
        $image = $this->createImage(200, 100, 'png');
        $resize = new ImageResize($image);

        $resize->crop(50, 50);

        $this->assertEquals(50, $resize->getDestWidth());
        $this->assertEquals(50, $resize->getDestHeight());
    }

    public function testCropPosition()
    {
        $image = $this->createImage(200, 100, 'png');
        $resize = new ImageResize($image);

        $resize->crop(50, 50, false, $resize::CROPRIGHT);

        $reflection_class = new ReflectionClass('\Eventviva\ImageResize');
        $source_x = $reflection_class->getProperty('source_x');
        $source_x->setAccessible(true);

        $this->assertEquals(100, $source_x->getValue($resize));
    }

    public function testCropLargerNotAllowed()
    {
        $image = $this->createImage(200, 100, 'png');
        $resize = new ImageResize($image);

        $resize->crop(500, 500);

        $this->assertEquals(200, $resize->getDestWidth());
        $this->assertEquals(100, $resize->getDestHeight());
    }


    /**
     * Save tests
     */

    public function testSaveGif()
    {
        $image = $this->createImage(200, 100, 'gif');

        $resize = new ImageResize($image);

        $filename = $this->getTempFile();

        $resize->save($filename);

        $this->assertEquals(IMAGETYPE_GIF, exif_imagetype($filename));
    }

    public function testSaveJpg()
    {
        $image = $this->createImage(200, 100, 'jpeg');

        $resize = new ImageResize($image);

        $filename = $this->getTempFile();

        $resize->save($filename);

        $this->assertEquals(IMAGETYPE_JPEG, exif_imagetype($filename));
    }

    public function testSavePng()
    {
        $image = $this->createImage(200, 100, 'png');

        $resize = new ImageResize($image);

        $filename = $this->getTempFile();

        $resize->save($filename);

        $this->assertEquals(IMAGETYPE_PNG, exif_imagetype($filename));
    }

    public function testSaveChmod()
    {
        $image = $this->createImage(200, 100, 'png');

        $resize = new ImageResize($image);

        $filename = $this->getTempFile();

        $resize->save($filename, null, null, 0600);

        $this->assertEquals(600, substr(decoct(fileperms($filename)), 3));
    }


    /**
     * String test
     */

    public function testGetImageAsString()
    {
        $resize = ImageResize::createFromString(base64_decode($this->image_string));
        $image = $resize->getImageAsString();
        $this->assertEquals(43, strlen($image));
    }

    public function testToString()
    {
        $resize = ImageResize::createFromString(base64_decode($this->image_string));
        $image = (string)$resize;
        $this->assertEquals(43, strlen($image));
    }


    /**
     * Output tests
     */

    public function testOutputGif()
    {
        $image = $this->createImage(200, 100, 'gif');

        $resize = new ImageResize($image);

        ob_start();

        // supressing header errors
        @$resize->output();

        $image_contents = ob_get_clean();

        $info = finfo_open();

        $type = finfo_buffer($info, $image_contents, FILEINFO_MIME_TYPE);

        $this->assertEquals('image/gif', $type);
    }

    public function testOutputJpg()
    {
        $image = $this->createImage(200, 100, 'jpeg');

        $resize = new ImageResize($image);

        ob_start();

        // supressing header errors
        @$resize->output();

        $image_contents = ob_get_clean();

        $info = finfo_open();

        $type = finfo_buffer($info, $image_contents, FILEINFO_MIME_TYPE);

        $this->assertEquals('image/jpeg', $type);
    }

    public function testOutputPng()
    {
        $image = $this->createImage(200, 100, 'png');

        $resize = new ImageResize($image);

        ob_start();

        // supressing header errors
        @$resize->output();

        $image_contents = ob_get_clean();

        $info = finfo_open();

        $type = finfo_buffer($info, $image_contents, FILEINFO_MIME_TYPE);

        $this->assertEquals('image/png', $type);
    }


    /**
     * Helpers
     */

    private function createImage($width, $height, $type)
    {
        if (!in_array($type, $this->image_types)) {
            throw new \Exception('Unsupported image type');
        }

        $image = imagecreatetruecolor($width, $height);

        $filename = $this->getTempFile();

        $output_function = 'image' . $type;
        $output_function($image, $filename);

        return $filename;
    }

    private function getTempFile()
    {
        return tempnam(sys_get_temp_dir(), 'resize_test_image');
    }

}
