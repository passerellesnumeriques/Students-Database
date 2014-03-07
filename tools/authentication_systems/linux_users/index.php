<?php 
if (!isset($_POST["service"])) {
	header("HTTP/1.1 404 Invalid request");
	die();
}
switch ($_POST["service"]) {
	case "authenticate": include("authenticate.inc"); break;
	default: header("HTTP/1.1 404 Unknown service"); die();
}
?>