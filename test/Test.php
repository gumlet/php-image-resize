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

    public function testLoadGif() {
        $image = $this->createImage(1, 1, 'gif');
        $resize = new ImageResize($image);

        $this->assertEquals(IMAGETYPE_GIF, $resize->source_type);
    }

    public function testLoadJpg() {
        $image = $this->createImage(1, 1, 'jpeg');
        $resize = new ImageResize($image);

        $this->assertEquals(IMAGETYPE_JPEG, $resize->source_type);
    }

    public function testLoadPng() {
        $image = $this->createImage(1, 1, 'png');
        $resize = new ImageResize($image);

        $this->assertEquals(IMAGETYPE_PNG, $resize->source_type);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage getimagesize(): Filename cannot be empty
     */
    public function testLoadNoFile() {
        new ImageResize(null);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Could not read
     */
    public function testLoadUnsupportedFile() {
        new ImageResize(__FILE__);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Unsupported image type
     */
    public function testLoadUnsupportedImage() {
        $filename = $this->getTempFile();

        $image = fopen($filename, 'w');
        fwrite($image, base64_decode($this->unsupported_image));
        fclose($image);

        new ImageResize($filename);
    }

    public function testResizeToHeight() {
        $image = $this->createImage(200, 100, 'png');
        $resize = new ImageResize($image);

        $resize->resizeToHeight(50);

        $this->assertEquals(100, $resize->getDestWidth());
        $this->assertEquals(50, $resize->getDestHeight());
    }

    public function testResizeToWidth() {
        $image = $this->createImage(200, 100, 'png');
        $resize = new ImageResize($image);

        $resize->resizeToWidth(100);

        $this->assertEquals(100, $resize->getDestWidth());
        $this->assertEquals(50, $resize->getDestHeight());
    }

    public function testScale() {
        $image = $this->createImage(200, 100, 'png');
        $resize = new ImageResize($image);

        $resize->scale(50);

        $this->assertEquals(100, $resize->getDestWidth());
        $this->assertEquals(50, $resize->getDestHeight());
    }

    public function testResize() {
        $image = $this->createImage(200, 100, 'png');
        $resize = new ImageResize($image);

        $resize->resize(50, 50);

        $this->assertEquals(50, $resize->getDestWidth());
        $this->assertEquals(50, $resize->getDestHeight());
    }

    public function testResizeLargerNotAllowed() {
        $image = $this->createImage(200, 100, 'png');
        $resize = new ImageResize($image);

        $resize->resize(400, 200);

        $this->assertEquals(200, $resize->getDestWidth());
        $this->assertEquals(100, $resize->getDestHeight());
    }

    public function testCrop() {
        $image = $this->createImage(200, 100, 'png');
        $resize = new ImageResize($image);

        $resize->crop(50, 50);

        $this->assertEquals(50, $resize->getDestWidth());
        $this->assertEquals(50, $resize->getDestHeight());
    }

    public function testCropLargerNotAllowed() {
        $image = $this->createImage(200, 100, 'png');
        $resize = new ImageResize($image);

        $resize->crop(500, 500);

        $this->assertEquals(200, $resize->getDestWidth());
        $this->assertEquals(100, $resize->getDestHeight());
    }

    public function testSaveGif() {
        $image = $this->createImage(200, 100, 'gif');

        $resize = new ImageResize($image);

        $filename = $this->getTempFile();

        $resize->save($filename);

        $this->assertEquals(IMAGETYPE_GIF, exif_imagetype($filename));
    }

    public function testSaveJpg() {
        $image = $this->createImage(200, 100, 'jpeg');

        $resize = new ImageResize($image);

        $filename = $this->getTempFile();

        $resize->save($filename);

        $this->assertEquals(IMAGETYPE_JPEG, exif_imagetype($filename));
    }

    public function testSavePng() {
        $image = $this->createImage(200, 100, 'png');

        $resize = new ImageResize($image);

        $filename = $this->getTempFile();

        $resize->save($filename);

        $this->assertEquals(IMAGETYPE_PNG, exif_imagetype($filename));
    }

    public function testSaveChmod() {
        $image = $this->createImage(200, 100, 'png');

        $resize = new ImageResize($image);

        $filename = $this->getTempFile();

        $resize->save($filename, null, null, 0600);

        $this->assertEquals(600, substr(decoct(fileperms($filename)), 3));
    }

    public function testOutputGif() {
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

    public function testOutputJpg() {
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

    public function testOutputPng() {
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

    private function createImage($width, $height, $type) {
        if (!in_array($type, $this->image_types)) {
            throw new \Exception('Unsupported image type');
        }

        $image = imagecreatetruecolor($width, $height);

        $filename = $this->getTempFile();

        $output_function = 'image' . $type;
        $output_function($image, $filename);

        return $filename;
    }

    private function getTempFile() {
        return tempnam(sys_get_temp_dir(), 'resize_test_image');
    }

}
