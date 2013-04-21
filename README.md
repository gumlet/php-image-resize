php-image-resize
================

PHP class to re-size and scale images

### USAGE:
If you want to scale image:


    $image = new ImageResize('picture.jpg');
    $image->scale(50);
    $image->save('picture2.jpg')
    
If you want to resize image:

    $image = new ImageResize('picture.jpg');
    $image->resizeToHeight(500);
    $image->save('picture2.jpg');
    $image->resizeToHeight(200);
    $image->save('picture3.jpg');
