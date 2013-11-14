<?php require_once('ImageResizer.php');

$image = new ImageResize('img/picture.png');
/*$image->scale(50);
$image->save('img/picture2.png'); */

/*$image->resizeToHeight(500);
$image->save('img/picture3.png');
$image->resizeToHeight(200);
$image->save('img/picture4.png');*/

/*$image->resize(200,200,true);
$image->save('img/picture5.png');

$image->resizeToWidth(100);
$image->save('img/picture6.png');*/

$image->crop(512,512);
$image->save('img/picture_crop512x512.png');

?>