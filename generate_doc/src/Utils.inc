<?php 
function php_str($str) {
	return str_replace("\\", "\\\\", str_replace("\"", "\\\"", str_replace("$", "\\\$", $str)));
}
?>