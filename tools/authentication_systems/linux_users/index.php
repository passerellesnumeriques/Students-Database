<?php 
if (!isset($_POST["service"])) {
	header("HTTP/1.1 404 Invalid request");
	die();
}
if ($_POST["service"] == "authenticate") {
	include("authenticate.inc");
	die();
}
if (isset($_POST["token"])) session_id($_POST["token"]);
session_start();
if (!isset($_SESSION["authenticated_user"])) {
	header("HTTP/1.1 403 You must be authenticated to use this service");
	die();
}
switch ($_POST["service"]) {
	case "get_users": include("get_users.inc"); break;
	default: header("HTTP/1.1 404 Unknown service"); die();
}
?>