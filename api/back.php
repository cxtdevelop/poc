<?php
$im = imagecreatefrompng("vps2.png");

header('Content-Type: image/png');

imagepng($im);
imagedestroy($im);
?>