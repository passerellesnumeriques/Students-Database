<?php
$main = $_GET["main"];
$small = $_GET["small"];
$where = $_GET["where"];

if (substr($main, 0, 8) == "/static/") {
	$main = substr($main, 8);
	$i = strpos($main, "/");
	$main = "component/".substr($main, 0, $i)."/static/".substr($main,$i+1);
}
if (substr($small, 0, 8) == "/static/") {
	$small = substr($small, 8);
	$i = strpos($small, "/");
	$small = "component/".substr($small, 0, $i)."/static/".substr($small,$i+1);
}

$icon = imagecreatefromstring(file_get_contents($main));
imagealphablending($icon, true);
imagesavealpha($icon, true);
$sm = imagecreatefromstring(file_get_contents($small));
imagealphablending($sm, true);
imagesavealpha($sm, true);
$w = imagesx($sm);
$h = imagesy($sm);
$i = strpos($where,"_");
$xs = substr($where, 0, $i);
$ys = substr($where, $i+1);
switch ($xs) {
	case "right": $x = imagesx($icon)-$w; break;
	case "left": $x = 0; break;
	case "center": $x = imagesx($icon)/2-$w/2; break;
}
switch ($ys) {
	case "bottom": $y = imagesy($icon)-$h; break;
	case "top": $y = 0; break;
	case "center": $y = imagesy($icon)/2-$h/2; break;
}

imagecopy($icon, $sm, $x, $y, 0, 0, $w, $h);

header("Content-Type: image/png");
imagepng($icon);
?>