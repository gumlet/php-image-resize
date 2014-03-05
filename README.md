php-image-resize
================

PHP class to re-size and scale images

### USAGE:
If you want to scale image:

```php
$image = new ImageResize('picture.jpg');
$image->scale(50);
$image->save('picture2.jpg')
```
    
If you want to resize image:
```php
$image = new ImageResize('picture.jpg');
$image->resizeToHeight(500);
$image->save('picture2.jpg');
$image->resizeToWidth(200);
$image->save('picture3.jpg');
```

If you want to scale image: 
```php
$image = new ImageResize('picture.jpg');
$image->scale(256,512);
$image->save('picture4.jpg');
```
	
If you want to crop image: 
```php
$image = new ImageResize('picture.jpg');
$image->crop(512,512);
$image->save('picture5.jpg');
```
