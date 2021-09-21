<?php
function hexrgb($hexstr) {
    $int = hexdec($hexstr);

    return array("red" => 0xFF & ($int >> 0x10), "green" => 0xFF & ($int >> 0x8), "blue" => 0xFF & $int);
}

$getColor = isset($_GET['color']) ? $_GET['color'] : 'ffffff';

$color = hexrgb('#' . $getColor);

header ("Content-type: image/png"); 
$img = ImageCreate (15, 15);      
$couleur_fond = ImageColorAllocate ($img, $color['red'], $color['green'], $color['blue']);
$ramka = imagerectangle($img, 0, 0, 14, 14, imagecolorallocate($img, 171, 171, 171));
ImagePng ($img);
