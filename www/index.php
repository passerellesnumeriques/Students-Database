<?php
function component_auto_loader($classname) {
	require_once("component/".$classname."/".$classname.".inc");
}

// check last time the user came, it was the same version, in order to refresh its cache if the version changed
$version = include("version.inc");
if (!isset($_COOKIE["pnversion"]) || $_COOKIE["pnversion"] <> $version) {
	setcookie("pnversion",$version,time()+365*24*60*60,"/");
	header("Location: ?");
	session_start();
	session_destroy();
	die();
}

if (!isset($_SERVER["PATH_INFO"]) || strlen($_SERVER["PATH_INFO"]) == 0) $_SERVER["PATH_INFO"] = "/";
$path = substr($_SERVER["PATH_INFO"],1);

// security: do not allow .. in the path, to avoid trying to access to files which are protected
if (strpos($path, "..") !== FALSE) die("Access denied");

if ($path == "favicon.ico") { header("Content-Type: image/ico"); readfile("favicon.ico"); die(); }

if ($path == "") {
	spl_autoload_register('component_auto_loader');
	require_once("component/PNApplication.inc");
	session_start();
	require_once("SQLQuery.inc");
	spl_autoload_unregister('component_auto_loader');

	if (!isset($_SESSION["app"])) {
		PNApplication::$instance = new PNApplication();
		PNApplication::$instance->init();
		$_SESSION["app"] = &PNApplication::$instance;
	} else {
		PNApplication::$instance = &$_SESSION["app"];
		PNApplication::$instance->init_request();
	}
	if (PNApplication::$instance->current_domain == "Dev") {
		$dev = new DevRequest();
		$dev->url = $_SERVER["PATH_INFO"];
		array_push(PNApplication::$instance->development->requests, $dev);
	}
	include("loading.inc");
	die();
}

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));

function invalid($message) {
	header("HTTP/1.1 404 ".$message);
	die($message);
}

// get type of resource
$i = strpos($path, "/");
if ($i === FALSE) invalid("Invalid request: no type of resource");
$type = substr($path, 0, $i);
$path = substr($path, $i+1);

// get the component name
$i = strpos($path, "/");
if ($i === FALSE) invalid("Invalid request: no component name");
$component_name = substr($path, 0, $i);
$path = substr($path, $i+1);

switch ($type) {
case "static":
	$i = strrpos($path, ".");
	if ($i === FALSE) invalid("Invalid resource type");
	$ext = substr($path, $i+1);
	header('Cache-Control: public', true);
	header('Pragma: public', true);
	$date = date("D, d M Y H:i:s",time());
	header('Date: '.$date, true);
	$expires = time()+365*24*60*60;
	header('Expires: '.date("D, d M Y H:i:s",$expires).' GMT', true);
	switch ($ext) {
	case "gif": header("Content-Type: image/gif"); break;
	case "png": header("Content-Type: image/png"); break;
	case "jpg": case "jpeg": header("Content-Type: image/jpeg"); break;
	case "css": header("Content-Type: text/css"); break;
	case "js": header("Content-Type: text/javascript"); break;
	case "html": header("Content-Type: text/html;charset=UTF-8"); break;
	case "php": 
		if (!file_exists("component/".$component_name."/static/".$path)) invalid("Static resource not found");
		include "component/".$component_name."/static/".$path;
		die();
	default: invalid("Invalid static resource type");
	}
	if (!file_exists("component/".$component_name."/static/".$path)) invalid("Static resource not found");
	readfile("component/".$component_name."/static/".$path);
	die();
case "dynamic":
	// get the type of request
	$i = strpos($path, "/");
	if ($i === FALSE) invalid("Invalid request: no dynamic type");
	$request_type = substr($path, 0, $i);
	$path = substr($path, $i+1);

	spl_autoload_register('component_auto_loader');
	require_once("component/PNApplication.inc");
	session_set_cookie_params(24*60*60, "/dynamic/");
	session_start();
	require_once("SQLQuery.inc");
	spl_autoload_unregister('component_auto_loader');

	if (!isset($_SESSION["app"])) {
		PNApplication::$instance = new PNApplication();
		PNApplication::$instance->init();
		$_SESSION["app"] = &PNApplication::$instance;
	} else {
		PNApplication::$instance = &$_SESSION["app"];
		PNApplication::$instance->init_request();
	}
	if (PNApplication::$instance->current_domain == "Dev") {
		$dev = new DevRequest();
		$dev->url = $_SERVER["PATH_INFO"];
		$dev->start_time = microtime(true);
		array_push(PNApplication::$instance->development->requests, $dev);
	}

	if (!isset(PNApplication::$instance->components[$component_name])) invalid("Invalid request: unknown component ".$component_name);

	require_once("SQLQuery.inc"); // avoid to put it everywhere
	switch ($request_type) {
	case "page":
		header("Content-Type: text/html;charset=UTF-8");
		PNApplication::$instance->components[$component_name]->page($path);
		break;
	case "service":
		PNApplication::$instance->components[$component_name]->service($path);
		break;
	default: invalid("Invalid request: unknown request type ".$request_type);
	}
	if (PNApplication::$instance->current_domain == "Dev") {
		$dev->end_time = microtime(true);
	}
	die();
case "help":
	header('Cache-Control: public', true);
	header('Pragma: public', true);
	$date = date("D, d M Y H:i:s",time());
	header('Date: '.$date, true);
	$expires = time()+365*24*60*60;
	header('Expires: '.date("D, d M Y H:i:s",$expires).' GMT', true);
	header("Content-Type: text/html");
	require_once("component/Page.inc");
	pageHeaderStart();
	pageHeaderEnd();
	if (!file_exists("component/".$component_name."/page/".$path.".help.html")) invalid("Help not found for page '$path' on component '$component_name'");
	readfile("component/".$component_name."/page/".$path.".help.html");
	pageFooter();
	die();
default: invalid("Invalid request: unknown resource type ".$type);
}
?>
