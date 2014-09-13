<?php 
header('Cache-Control: public,max-age=31536000', true);
header('Pragma: public', true);
$date = date("D, d M Y H:i:s",time());
header('Date: '.$date, true);
$expires = time()+365*24*60*60;
header('Expires: '.date("D, d M Y H:i:s",$expires).' GMT', true);
header('Vary: Cookie,User-Agent', true);
?>