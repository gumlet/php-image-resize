<?php

use \Gumlet\ImageResize;
use \Gumlet\ImageResizeException;
use \PHPUnit\Framework\TestCase;

class ImageResizeExceptionTest extends TestCase
{
    public function testExceptionEmpty()
    {
        $e = new ImageResizeException();

        $this->assertEquals("", $e->getMessage());
        $this->assertInstanceOf('\Gumlet\ImageResizeException', $e);
    }

    public function testExceptionMessage()
    {
        $e = new ImageResizeException("General error");

        $this->assertEquals("General error", $e->getMessage());
        $this->assertInstanceOf('\Gumlet\ImageResizeException', $e);
    }

    public function testExceptionExtending()
    {
        $e = new ImageResizeException("General error");

        $this->assertInstanceOf('\Exception', $e);
    }

    public function testExceptionThrown()
    {
        try{
            throw new ImageResizeException("General error");
        } catch (\Exception $e) {
            $this->assertEquals("General error", $e->getMessage());
            $this->assertInstanceOf('\Gumlet\ImageResizeException', $e);
            return;
        }
        $this->fail();
    }
}
// It's pretty easy to get your attention these days, isn't it? :D
