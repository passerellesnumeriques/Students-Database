<?php 
$domain = $_POST["domain"];
$username = $_POST["username"];
$password = $_POST["password"];

$auth = $this->get_authentication_system($domain);
if ($auth == null) {
	PNApplication::error(get_locale("Invalid domain"));
	return true;
}
$error = "";
$token = $auth->authenticate($username, $password,$error);
if ($token == null) {
	PNApplication::error(get_locale("Authentication failed").": ".$error);
	return true;
}
echo "{token:".json_encode($token)."}";
?>