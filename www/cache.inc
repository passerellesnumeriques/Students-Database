<?php 
function cacheHeaders($date, $etag) {
	if ($date == null || $date == false)
		$date = time();
	header('Cache-Control: public,max-age=31536000', true);
	header('Pragma: public', true);
	$d = date("D, d M Y H:i:s",$date);
	header('Last-Modified: '.$d, true);
	header('Etag: '.md5($etag));
	$expires = time()+365*24*60*60;
	header('Expires: '.date("D, d M Y H:i:s",$expires).' GMT', true);
	header('Vary: Cookie,User-Agent', true);
}
function cacheHeadersFromFile($filename) {
	cacheHeaders(filemtime($filename), $filename." ".filemtime($filename)." ".filesize($filename));
}
?>