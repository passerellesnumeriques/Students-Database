<?php 
function getLatestVersionURL() {
	$s = file_get_contents(dirname(__FILE__)."/conf/update_urls");
	$channel = file_get_contents(dirname(__FILE__)."/conf/channel");
	$s = str_replace("##CHANNEL##",$channel,$s);
	$lines = explode("\n",$s);
	foreach ($lines as $line) {
		if (substr($line,0,7) == "latest=")
			return trim(substr($line,7));
	}
	return null;
}
function getVersionsListURL() {
	$s = file_get_contents(dirname(__FILE__)."/conf/update_urls");
	$channel = file_get_contents(dirname(__FILE__)."/conf/channel");
	$s = str_replace("##CHANNEL##",$channel,$s);
	$lines = explode("\n",$s);
	foreach ($lines as $line) {
		if (substr($line,0,9) == "versions=")
			return trim(substr($line,9));
	}
	return null;
}
function getGenericUpdateURL() {
	$s = file_get_contents(dirname(__FILE__)."/conf/update_urls");
	$channel = file_get_contents(dirname(__FILE__)."/conf/channel");
	$s = str_replace("##CHANNEL##",$channel,$s);
	$lines = explode("\n",$s);
	foreach ($lines as $line) {
		if (substr($line,0,7) == "update=")
			return trim(substr($line,7));
	}
	return null;
}
function getUpdateURL($filename) {
	$s = file_get_contents(dirname(__FILE__)."/conf/update_urls");
	$channel = file_get_contents(dirname(__FILE__)."/conf/channel");
	$s = str_replace("##CHANNEL##",$channel,$s);
	$lines = explode("\n",$s);
	foreach ($lines as $line) {
		if (substr($line,0,7) == "update=")
			return str_replace("##FILE##", $filename, trim(substr($line,7)));
	}
	return null;
}
?>