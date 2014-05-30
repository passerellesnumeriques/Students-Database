<?php 
if (!isset($_POST["username"]) || !isset($_POST["password"])) {
	header("HTTP/1.1 400 Bad authentication request");
	die();
}

function find_encrypted_password_for($username) {
	$f = fopen("/etc/shadow","r");
	if ($f == null) return null;
	$enc = null;
	while (!feof($f)) {
		$line = fgets($f,4096);
		$i = strpos($line,":");
		if ($i == false) continue;
		$user = substr($line,0,$i);
		if ($user != $username) continue;
		$j = strpos($line,":",$i+1);
		if ($j == false) continue;
		$enc = substr($line,$i+1,$j-($i+1));
		break;
	}
	fclose($f);
	return $enc;
}

$enc = find_encrypted_password_for($_POST["username"]);
if ($enc <> null && crypt($_POST["password"], $enc) === $enc) {
	session_start();
	$_SESSION["authenticated_user"] = $_POST["username"];
	header("Content-Type: text/xml");
	echo "<authentication result='ok' token='".session_id()."'></authentication>";
} else
	echo "<authentication result='fail'></authentication>";
?>