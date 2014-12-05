<?php
if (!file_exists(dirname(__FILE__)."/install_config.inc")) {
	include("install.inc");
	die();
}
include("install_config.inc");
#DEV
function component_auto_loader($classname) {
	if ($classname == "DevRequest")
		require_once("component/development/development.inc");
	else
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
#END
#PROD
#$pn_app_version = "##VERSION##"; 
#END

#DEV
if (substr($path,0,7) == "deploy/") {
	if ($path == "deploy/") $path = "deploy/index.php";
	set_include_path(realpath("../deploy"));
	chdir("../deploy");
	include("../deploy/".substr($path,7));
	die();
}
if (@$_COOKIE["test_deploy"] == "true") {
	if (file_exists("../test_deploy")) {
		set_include_path(realpath("../test_deploy"));
		chdir("../test_deploy");
		if (substr($path,0,12) == "test_deploy/")
			$_SERVER["PATH_INFO"] = substr($path,11);
		$_SERVER["DOCUMENT_ROOT"] = realpath("../test_deploy");
		$_SERVER["CONTEXT_DOCUMENT_ROOT"] = realpath("../test_deploy");
		$_SERVER["SCRIPT_FILENAME"] = realpath("../test_deploy/index.php");
		include("../test_deploy/index.php");
		die();
	} else
		setcookie("test_deploy","",time()+365*24*60*60,"/");
}
#END

if ($path == "maintenance/maintenance.jpg") {
	header("Content-Type: image/jpeg");
	readfile("maintenance/maintenance.jpg");
	die();
}
if ($path == "maintenance/index.php" || $path == "maintenance/" || $path == "maintenance") {
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
		header("pn_version_changed: ".$pn_app_version, true, 403);
		die();
	} else if (strpos($path, "/static/")) {
	} else {
		// else we let continue... to avoid blocking everything
	}
}

if ($path == "reload") {
	header("Location: /");
	die();
}

date_default_timezone_set("GMT");

if ($path == "favicon.ico") { 
	header("Content-Type: image/ico");
	include("cache.inc"); 
	cacheHeadersFromFile("favicon.ico");
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
if ($i === FALSE) $invalid("Invalid request: no type of resource ($path)");
$type = substr($path, 0, $i);
$path = substr($path, $i+1);

// get the component name
$i = strpos($path, "/");
if ($i === FALSE) $invalid("Invalid request: no component name ($path)");
$component_name = substr($path, 0, $i);
$path = substr($path, $i+1);


switch ($type) {
case "static":
	$i = strrpos($path, ".");
	if ($i === FALSE) $invalid("Invalid resource type ($path)");
	$ext = substr($path, $i+1);
	include("cache.inc");
	cacheHeadersFromFile("component/".$component_name."/static/".$path);
	switch ($ext) {
	case "gif": header("Content-Type: image/gif"); break;
	case "png": header("Content-Type: image/png"); break;
	case "jpg": case "jpeg": header("Content-Type: image/jpeg"); break;
	case "css": header("Content-Type: text/css"); break;
	case "js": header("Content-Type: text/javascript"); break;
	case "html": header("Content-Type: text/html;charset=UTF-8"); break;
	case "woff": header("Content-Type: application/font-woff"); break;
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
	if ($i === FALSE) $invalid("Invalid request: no dynamic type ($path)");
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
	$_SESSION["alive_timestamp"] = time(); // make it alive, to avoid being automatically closed
	if (isset($_SESSION["remote"])) {
		if ($_SESSION["remote"] <> $_SERVER["REMOTE_ADDR"]) {
			// try to use a session, with a different IP => reject and force open a new session
			session_write_close();
			setcookie(session_name(), "", time()-10000, "/dynamic/");
			setcookie(session_name(), "", time()-10000, "/");
			header("Location: /");
			die("Access denied: you changed address");
		}
	} else {
		$_SESSION["remote"] = $_SERVER["REMOTE_ADDR"];
	}
	if (isset($_SESSION["instance_uid"])) {
		if ($_SESSION["instance_uid"] <> file_get_contents("conf/instance.uid")) {
			// use a session from a different installation => reject and force open a new session
			session_write_close();
			setcookie(session_name(), "", time()-10000, "/dynamic/");
			setcookie(session_name(), "", time()-10000, "/");
			header("Location: /");
			die("Access denied: you come from a different installation");
		}
	} else {
		$_SESSION["instance_uid"] = file_get_contents("conf/instance.uid");
	}
	if (!isset($_SESSION["app"])) {
		PNApplication::$instance = new PNApplication();
		PNApplication::$instance->init();
		$_SESSION["app"] = &PNApplication::$instance;
		$_SESSION["version"] = $pn_app_version;
		if (isset($_SERVER["HTTP_USER_AGENT"]))
			$_SESSION["user_agent"] = $_SERVER["HTTP_USER_AGENT"]; 
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
	@session_start();
	if (isset($_SESSION["app"]))
		$_SESSION["app"]->development->requests = PNApplication::$instance->development->requests;
	$dev->end_time = microtime(true);
	session_write_close();
#END
	die();
default: $invalid("Invalid request: unknown resource type ".$type);
}
	
?>
