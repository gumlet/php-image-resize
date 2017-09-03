php-image-resize
================

PHP library to resize, scale and crop images.

[![Build Status](https://travis-ci.org/eventviva/php-image-resize.svg?branch=master)](https://travis-ci.org/eventviva/php-image-resize) [![Latest Stable Version](https://poser.pugx.org/eventviva/php-image-resize/v/stable)](https://packagist.org/packages/eventviva/php-image-resize) [![Monthly Downloads](https://poser.pugx.org/eventviva/php-image-resize/d/monthly)](https://packagist.org/packages/eventviva/php-image-resize)

Setup
-----

This package is available through Packagist with the vendor and package identifier the same as this repo.

If using [Composer](https://getcomposer.org/), in your `composer.json` file add:

```json
{
    "require": {
        "eventviva/php-image-resize": "1.6.*"
    }
}
```

Otherwise:

```php
include '/path/to/ImageResize.php';
```

Because this class uses namespacing, when instantiating the object, you need to either use the fully qualified namespace:

```php
$image = new \Eventviva\ImageResize();
```

Or alias it:

```php

use \Eventviva\ImageResize;

$image = new ImageResize();
```

> Note: This library uses GD class which do not support resizing animated gif files

Resize
------

To scale an image, in this case to half it's size (scaling is percentage based):

```php
$image = new ImageResize('image.jpg');
$image->scale(50);
$image->save('image2.jpg')
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
$image->crop(200, 200, ImageResize::CROPCENTER);
$image->save('image2.jpg');
```

In the case you have an image of 400px &times; 600px and you want to crop it to 200px &times; 200px the image will be resized down to 200px &times; 300px, then you can indicate how you want to handle those 100px exceeding passing the value of the crop mode you want to use.

For instance passing the crop mode `CROPTOP` will result as 100px taken off the bottom leaving you with 200px &times; 200px.


```php
$image = new ImageResize('image.jpg');
$image->crop(200, 200, ImageResize::CROPTOP);
$image->save('image2.jpg');
```

On the contrary passing the crop mode `CROPBOTTOM` will result as 100px taken off the top leaving you with 200px &times; 200px.

```php
$image = new ImageResize('image.jpg');
$image->crop(200, 200, ImageResize::CROPBOTTOM);
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

The properties `$quality_jpg` and `$quality_png` are available for you to configure:

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
$image->save('image2.jpg')
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
 
API Doc
-------

https://eventviva.github.io/php-image-resize/class-Eventviva.ImageResize.html
