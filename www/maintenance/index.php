<?php
if (!file_exists("maintenance/password")) {
	header('HTTP/1.0 403 Access denied');
	die("The application is not is maintenance mode.");
}

if (!isset($_SERVER['PHP_AUTH_USER'])) {
	header('WWW-Authenticate: Basic realm="Students Management Software Maintenance"');
	header('HTTP/1.0 401 Unauthorized');
	echo 'Access denied';
	die();
}
if ($_SERVER['PHP_AUTH_USER'] <> "maintenance" || sha1($_SERVER['PHP_AUTH_PW']) <> file_get_contents("maintenance/password")) {
		header('WWW-Authenticate: Basic realm="Students Management Software Maintenance"');
		header('HTTP/1.0 401 Unauthorized');
		echo 'Access denied';
		die();
}
?>
<html>
<head>
	<title>Maintenance</title>
</head>
<body>
<?php 
if (file_exists("maintenance_time") && @$_GET["step"] <> "back_to_normal") {
	$timing = intval(file_get_contents("maintenance_time"));
	$remaining = $timing-time();
	if ($remaining > -5) {
		echo "The application will enter maintenance mode in ".($remaining < 10 ? "few seconds" : $remaining." seconds");
		echo "<br/><button onclick=\"location.href='/maintenance?step=back_to_normal';\">Stop it!</button>";
		echo "<script type='text/javascript'>setTimeout(function(){location.reload()},".($remaining > 30 ? "5000" : $remaining > 10 ? "2000" : "800").");</script>";
	} else {
		$f = fopen("maintenance_in_progress", "w");
		fclose($f);
		unlink("maintenance_time");
		echo "<script type='text/javascript'>location.href='/maintenance?step=destroy_sessions';</script>";
	}
} else {
	if (!isset($_GET["step"])) $_GET["step"] = "destroy_sessions";
	include("step_".$_GET["step"].".inc");
}
?>
</body>
</html>