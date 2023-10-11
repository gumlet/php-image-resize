php-image-resize
================

PHP library to resize, scale and crop images.

![Build Status](https://github.com/gumlet/php-image-resize/workflows/PHP%20CI/badge.svg) [![Latest Stable Version](https://poser.pugx.org/gumlet/php-image-resize/v/stable)](https://packagist.org/packages/gumlet/php-image-resize) [![Monthly Downloads](https://poser.pugx.org/gumlet/php-image-resize/d/monthly)](https://packagist.org/packages/gumlet/php-image-resize) [![Coverage Status](https://coveralls.io/repos/github/gumlet/php-image-resize/badge.svg?branch=master)](https://coveralls.io/github/gumlet/php-image-resize?branch=master)

Cloud Solution
---------------

If you don't want to crop, resize and store images on your server, <a href="https://www.gumlet.com" target="_blank">Gumlet.com</a> is a **free** service which can process images in real-time and serve worldwide through CDN.

------------------

Setup
-----

This package is available through Packagist with the vendor and package identifier the same as this repo.

If using [Composer](https://getcomposer.org/), in your `composer.json` file add:

```json
{
    "require": {
        "gumlet/php-image-resize": "2.0.*"
    }
}
```

If you are still using PHP 5.3, please install version ```1.7.0``` and if you are using PHP 5.4, please install version ```1.8.0``` of this library.

WebP support is added with PHP `5.6.0` and current version of library supports that. If you are facing issues, please use `1.9.2` version of this library.

For PHP versions >= 7.2, `2.0.1` or above version of this library should be used.

Otherwise:

```php
include '/path/to/ImageResize.php';
```

Because this class uses namespacing, when instantiating the object, you need to either use the fully qualified namespace:

```php
$image = new \Gumlet\ImageResize();
```

Or alias it:

```php

use \Gumlet\ImageResize;

$image = new ImageResize();
```

> Note: This library uses GD class which do not support resizing animated gif files

------------------

Resize
------

To scale an image, in this case to half it's size (scaling is percentage based):

```php
$image = new ImageResize('image.jpg');
$image->scale(50);
$image->save('image2.jpg');
```

To resize an image according to one dimension (keeping aspect ratio):

```php
$image = new ImageResize('image.jpg');
$image->resizeToHeight(500);
$image->save('image2.jpg');

$image = new ImageResize('image.jpg');
$image->resizeToWidth(300);
$image->save('image2.jpg');
```

To resize an image according to a given measure regardingless its orientation (keeping aspect ratio):

```php
$image = new ImageResize('image.jpg');
$image->resizeToLongSide(500);
$image->save('image2.jpg');

$image = new ImageResize('image.jpg');
$image->resizeToShortSide(300);
$image->save('image2.jpg');
```

To resize an image to best fit a given set of dimensions (keeping aspet ratio):
```php
$image = new ImageResize('image.jpg');
$image->resizeToBestFit(500, 300);
$image->save('image2.jpg');
```

All resize functions have ```$allow_enlarge``` option which is set to false by default.
You can enable by passing ```true``` to any resize function:
```php
$image = new ImageResize('image.jpg');
$image->resize(500, 300, $allow_enlarge = True);
$image->save('image2.jpg');
```

If you are happy to handle aspect ratios yourself, you can resize directly:

```php
$image = new ImageResize('image.jpg');
$image->resize(800, 600);
$image->save('image2.jpg');
```

This will cause your image to skew if you do not use the same width/height ratio as the source image.

Crop
----

To to crop an image:

```php
$image = new ImageResize('image.jpg');
$image->crop(200, 200);
$image->save('image2.jpg');
```

This will scale the image to as close as it can to the passed dimensions, and then crop and center the rest.

In the case of the example above, an image of 400px &times; 600px will be resized down to 200px &times; 300px, and then 50px will be taken off the top and bottom, leaving you with 200px &times; 200px.

Crop modes:

Few crop mode options are available in order for you to choose how you want to handle the eventual exceeding width or height after resizing down your image.
The default crop mode used is the `CROPCENTER`.
As a result those pieces of code are equivalent:

```php
$image = new ImageResize('image.jpg');
$image->crop(200, 200);
$image->save('image2.jpg');
```

```php
$image = new ImageResize('image.jpg');
$image->crop(200, 200, true, ImageResize::CROPCENTER);
$image->save('image2.jpg');
```

In the case you have an image of 400px &times; 600px and you want to crop it to 200px &times; 200px the image will be resized down to 200px &times; 300px, then you can indicate how you want to handle those 100px exceeding passing the value of the crop mode you want to use.

For instance passing the crop mode `CROPTOP` will result as 100px taken off the bottom leaving you with 200px &times; 200px.


```php
$image = new ImageResize('image.jpg');
$image->crop(200, 200, true, ImageResize::CROPTOP);
$image->save('image2.jpg');
```

On the contrary passing the crop mode `CROPBOTTOM` will result as 100px taken off the top leaving you with 200px &times; 200px.

```php
$image = new ImageResize('image.jpg');
$image->crop(200, 200, true, ImageResize::CROPBOTTOM);
$image->save('image2.jpg');
```

Freecrop:

There is also a way to define custom crop position.
You can define $x and $y in ```freecrop``` method:

```php
$image = new ImageResize('image.jpg');
$image->freecrop(200, 200, $x =  20, $y = 20);
$image->save('image2.jpg');
```

Loading and saving images from string
-------------------------------------

To load an image from a string:

```php
$image = ImageResize::createFromString(base64_decode('R0lGODlhAQABAIAAAAQCBP///yH5BAEAAAEALAAAAAABAAEAAAICRAEAOw=='));
$image->scale(50);
$image->save('image.jpg');
```

You can also return the result as a string:

```php
$image = ImageResize::createFromString(base64_decode('R0lGODlhAQABAIAAAAQCBP///yH5BAEAAAEALAAAAAABAAEAAAICRAEAOw=='));
$image->scale(50);
echo $image->getImageAsString();
```

Magic `__toString()` is also supported:

```php
$image = ImageResize::createFromString(base64_decode('R0lGODlhAQABAIAAAAQCBP///yH5BAEAAAEALAAAAAABAAEAAAICRAEAOw=='));
$image->resize(10, 10);
echo (string)$image;
```

Displaying
----------

As seen above, you can call `$image->save('image.jpg');`

To render the image directly into the browser, you can call `$image->output()`;

Image Types
-----------

When saving to disk or outputting into the browser, the script assumes the same output type as input.

If you would like to save/output in a different image type, you need to pass a (supported) PHP [`IMAGETYPE_`* constant](http://www.php.net/manual/en/image.constants.php):

- `IMAGETYPE_GIF`
- `IMAGETYPE_JPEG`
- `IMAGETYPE_PNG`

This allows you to save in a different type to the source:

```php
$image = new ImageResize('image.jpg');
$image->resize(800, 600);
$image->save('image.png', IMAGETYPE_PNG);
```

Quality
-------

The properties `$quality_jpg`, `$quality_webp` and `$quality_png` are available for you to configure:

```php
$image = new ImageResize('image.jpg');
$image->quality_jpg = 100;
$image->resize(800, 600);
$image->save('image2.jpg');
```

By default they are set to 85 and 6 respectively. See the manual entries for [`imagejpeg()`](http://www.php.net/manual/en/function.imagejpeg.php) and [`imagepng()`](http://www.php.net/manual/en/function.imagepng.php) for more info.

You can also pass the quality directly to the `save()`, `output()` and `getImageAsString()` methods:

```php
$image = new ImageResize('image.jpg');
$image->crop(200, 200);
$image->save('image2.jpg', null, 100);

$image = new ImageResize('image.jpg');
$image->resizeToWidth(300);
$image->output(IMAGETYPE_PNG, 4);

$image = new ImageResize('image.jpg');
$image->scale(50);
$result = $image->getImageAsString(IMAGETYPE_PNG, 4);
```

We're passing `null` for the image type in the example above to skip over it and provide the quality. In this case, the image type is assumed to be the same as the input.

Interlacing
-----------

By default, [image interlacing](http://php.net/manual/en/function.imageinterlace.php) is turned on. It can be disabled by setting `$interlace` to `0`:

```php
$image = new ImageResize('image.jpg');
$image->scale(50);
$image->interlace = 0;
$image->save('image2.jpg');
```

Chaining
--------

When performing operations, the original image is retained, so that you can chain operations without excessive destruction.

This is useful for creating multiple sizes:

```php
$image = new ImageResize('image.jpg');
$image
    ->scale(50)
    ->save('image2.jpg')

    ->resizeToWidth(300)
    ->save('image3.jpg')

    ->crop(100, 100)
    ->save('image4.jpg')
;
```

Exceptions
--------

ImageResize throws ImageResizeException for it's own for errors. You can catch that or catch the general \Exception which it's extending.

It is not to be expected, but should anything go horribly wrong mid way then notice or warning Errors could be shown from the PHP GD and Image Functions (http://php.net/manual/en/ref.image.php)

```php
try{
    $image = new ImageResize(null);
    echo "This line will not be printed";
} catch (ImageResizeException $e) {
    echo "Something went wrong" . $e->getMessage();
}
```

Filters
--------

You can apply special effects for new image like blur or add banner.

```php
$image = new ImageResize('image.jpg');

// Add blure
$image->addFilter(function ($imageDesc) {
    imagefilter($imageDesc, IMG_FILTER_GAUSSIAN_BLUR);
});

// Add banner on bottom left corner
$image18Plus = 'banner.png'
$image->addFilter(function ($imageDesc) use ($image18Plus) {
    $logo = imagecreatefrompng($image18Plus);
    $logo_width = imagesx($logo);
    $logo_height = imagesy($logo);
    $image_width = imagesx($imageDesc);
    $image_height = imagesy($imageDesc);
    $image_x = $image_width - $logo_width - 10;
    $image_y = $image_height - $logo_height - 10;
    imagecopy($imageDesc, $logo, $image_x, $image_y, 0, 0, $logo_width, $logo_height);
});

```

Flip
--------

Flips an image using a given mode and this method is only for PHP version 5.4.

```php
$flip = new ImageResize('image.png');
$image = imagecreatetruecolor(200, 100);

$image->addFilter(function ($image) {
    imageflip($image, IMG_FLIP_HORIZONTAL);
});

```

Both functions will be used in the order in which they were added.

Gamma color correction
--------

You can enable the gamma color correction which is disabled by default.

```php
$image = new ImageResize('image.png');
$image->gamma(true);
```

API Doc
-------

https://gumlet.github.io/php-image-resize/index.html

------------------

Maintainer
----------

This library is maintained by <a href="https://www.gumlet.com" target="_blank">Gumlet.com</a>

[<img src="https://assets.gumlet.com/public/img/logo.png" width="300px">](https://www.gumlet.com)
