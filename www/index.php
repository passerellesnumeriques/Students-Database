<?php
if (!file_exists(dirname(__FILE__)."/install_config.inc")) {
	include("install.inc");
	die();
}
include("install_config.inc");
#DEV
function component_auto_loader($classname) {
	require_once("component/".$classname."/".$classname.".inc");
}
#END

if (!isset($_SERVER["PATH_INFO"]) || strlen($_SERVER["PATH_INFO"]) == 0) $_SERVER["PATH_INFO"] = "/";
$path = substr($_SERVER["PATH_INFO"],1);

// security: do not allow .. in the path, to avoid trying to access to files which are protected
if (strpos($path, "..") !== FALSE) die("Access denied");

global $pn_app_version;
#DEV
$pn_app_version = file_get_contents(dirname(__FILE__)."/version");
#PROD
#$pn_app_version = "##VERSION##"; 
#END

if ($path == "maintenance/index.php") {
	include("maintenance/index.php");
	die();
}
if (file_exists("maintenance_in_progress")) {
	if (file_exists("maintenance/password")) {
		if (isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] == "maintenance" && sha1($_SERVER['PHP_AUTH_PW']) == file_get_contents("maintenance/password")) {
			// the administrator is testing the software
			if (file_exists("maintenance/update/www")) {
				// and the software is ready to be tested
				$path = realpath("maintenance");
				set_include_path($path."/update/www");
				chdir($path."/update/www");
				include($path."/update/www/index.php");
				die();
			}
		} else if (isset($_GET["request_to_test_update"])) {
			// the administrator wants to start testing
			header('WWW-Authenticate: Basic realm="Students Management Software Maintenance"');
			header('HTTP/1.0 401 Unauthorized');
			echo 'Access denied';
			die();
		}			
	}
	if ($path == "maintenance/maintenance.jpg") {
		header("Content-Type: image/jpeg");
		readfile("maintenance/maintenance.jpg");
		die();
	}
	if (strpos($path, "/service/")) {
		header("HTTP/1.0 403 Maintenance in progress");
		die();
	}
	include("maintenance/maintenance_page.php");
	die();
}


// check last time the user came, it was the same version, in order to refresh its cache if the version changed
if (!isset($_COOKIE["pnversion"]) || $_COOKIE["pnversion"] <> $pn_app_version) {
	if (strpos($path, "/page/") || $path == "") {
		setcookie("pnversion",$pn_app_version,time()+365*24*60*60,"/");
		session_set_cookie_params(24*60*60, "/dynamic/");
		session_start();
		session_destroy();
		echo "<script type='text/javascript'>window.top.location = '/reload';</script>";
		die();
	} else if (strpos($path, "/service/") && $path <> "/dynamic/application/service/loading") {
		header("pn_version_changed: yes", true, 403);
		die();
	} // else we let continue... to avoid blocking everything
}

if ($path == "reload") {
	header("Location: /");
	die();
}

date_default_timezone_set("GMT");

if ($path == "favicon.ico") { 
	header("Content-Type: image/ico");
	include("cache.inc"); 
	readfile("favicon.ico");
	die(); 
}

if ($path == "") {
	include("loading.inc");
	die();
}


set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));

$invalid = function($message) {
	header("HTTP/1.1 404 ".$message);
	die($message);
};

// get type of resource
$i = strpos($path, "/");
if ($i === FALSE) $invalid("Invalid request: no type of resource");
$type = substr($path, 0, $i);
$path = substr($path, $i+1);

// get the component name
$i = strpos($path, "/");
if ($i === FALSE) $invalid("Invalid request: no component name");
$component_name = substr($path, 0, $i);
$path = substr($path, $i+1);


switch ($type) {
case "static":
	$i = strrpos($path, ".");
	if ($i === FALSE) $invalid("Invalid resource type");
	$ext = substr($path, $i+1);
	include("cache.inc"); 
	switch ($ext) {
	case "gif": header("Content-Type: image/gif"); break;
	case "png": header("Content-Type: image/png"); break;
	case "jpg": case "jpeg": header("Content-Type: image/jpeg"); break;
	case "css": header("Content-Type: text/css"); break;
	case "js": header("Content-Type: text/javascript"); break;
	case "html": header("Content-Type: text/html;charset=UTF-8"); break;
	case "php": 
		if (!file_exists("component/".$component_name."/static/".$path)) $invalid("Static resource not found");
		include "component/".$component_name."/static/".$path;
		die();
	default:
		if (substr($component_name,0,4) <> "lib_")
			$invalid("Invalid static resource type");
	}
	if (!file_exists("component/".$component_name."/static/".$path)) $invalid("Static resource not found");
	if ($ext == "css") {
		require_once("css_cross_browser.inc");
		parse_css("component/".$component_name."/static/".$path);
	} else
		readfile("component/".$component_name."/static/".$path);
	die();
case "dynamic":
	// get the type of request
	$i = strpos($path, "/");
	if ($i === FALSE) $invalid("Invalid request: no dynamic type");
	$request_type = substr($path, 0, $i);
	$path = substr($path, $i+1);
#DEV	
	spl_autoload_register('component_auto_loader');
#END
	require_once("component/PNApplication.inc");
	session_set_cookie_params(24*60*60, "/dynamic/");
	session_start();
	require_once("SQLQuery.inc");
#DEV
	spl_autoload_unregister('component_auto_loader');
#END
	if (!isset($_SESSION["app"])) {
		PNApplication::$instance = new PNApplication();
		PNApplication::$instance->init();
		$_SESSION["app"] = &PNApplication::$instance;
		$_SESSION["version"] = $pn_app_version;
	} else {
		if (!isset($_SESSION["version"]) || $_SESSION["version"] <> $pn_app_version) {
			session_destroy();
			if ($request_type == "page") {
				echo "<script type='text/javascript'>window.top.location.href = '/';</script>";
			} else {
				header("Content-Type: application/json");
				echo "{errors:['The application has been updated to a new version.'],result:null}";
			}
			die();
		}
		PNApplication::$instance = &$_SESSION["app"];
		PNApplication::$instance->initRequest();
	}
#DEV
	$dev = new DevRequest();
	$dev->url = $_SERVER["PATH_INFO"];
	$dev->start_time = microtime(true);
	array_push(PNApplication::$instance->development->requests, $dev);
#END

	if (!isset(PNApplication::$instance->components[$component_name])) $invalid("Invalid request: unknown component ".$component_name);

	require_once("SQLQuery.inc"); // avoid to put it everywhere
	switch ($request_type) {
	case "page":
		header("Content-Type: text/html;charset=UTF-8");
		PNApplication::$instance->components[$component_name]->page($path);
		break;
	case "service":
		PNApplication::$instance->components[$component_name]->service($path);
		break;
	default: $invalid("Invalid request: unknown request type ".$request_type);
	}
#DEV
	$dev->end_time = microtime(true);
#END
	die();
default: $invalid("Invalid request: unknown resource type ".$type);
}
	
?>
