<?php
function component_auto_loader($classname){
	require_once("component/".$classname."/".$classname.".inc");
}

// Check last time the user came, it was the same version, in order to change its cache in case the version changed

$version=include("version.inc");
if (!isset($_COOKIE["pnversion"])||$_COOKIE["pnversion"]<>$version){
	setcookie("pnversion",$version,time()+24*60*60*365,"/");
	header("Location :?");
	die();
}

if (!isset($_SERVER["PATH_INFO"]) || strlen($_SERVER["PATH_INFO"]) == 0) $_SERVER["PATH_INFO"] = "/";


$path = substr($_SERVER["PATH_INFO"],1);

// Security : do not allow .. in the path, to avoid trying to access to protected files
if (strpos($path, "..") !== FALSE) die("Access denied");

if ($path == "favicon.ico") { header("Content-Type: image/ico"); readfile("favicon.ico"); die(); }

if ($path==""){
	include("loading.inc");
	die();
}

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));

function invalid($message) {
header("HTTP/1.1 404 ".$message);
die($message);
}

//Get type of resource
$i=strpos($path,"/");
if($i=== FALSE) invalid("Invalid request : no type of resource");
$type=substr($path,0,$i);
$path=substr($path,$i+1);

//Get the component name
$i=strpos($path,"/");
if ($i===FALSE) invalid("Invalid request : no type of component");
$component_name=substr($path,0,$i);
$path=substr($path,$i+1);

switch ($type) {
case "static":
	$i = strpos($path, ".");
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
	case "html": header("Content-Type: text/html"); break;
	//No difference between common or component as it was the case in the previous version
	case "php": include "component/".$component_name."/static/".$path; die();
	default: invalid("Invalid static resource type");
	}
	readfile("component/".$component_name."/static/".$path);
	die();
	
case "dynamic":
	//Get the type of the request
	$i = strpos($path, "/");
	if ($i === FALSE) invalid("Invalid request: no dynamic type");
	$request_type = substr($path, 0, $i);
	$path = substr($path, $i+1);
	
	spl_autoload_register('component_auto_loader');
	require_once('component/PNApplication.inc');
	session_set_cookie_params(24*60*60, "/dynamic/");
	session_start();
	require_once("DataBase.inc");
	spl_autoload_unregister('component_auto_loader');
	
	global $app;
	if(!isset($_SESSION['$app'])){
		$app = new PNApplication();
		$app->current_domain = file_get_contents("conf/local_domain");
		$app->init();
		$_SESSION["app"]=&$app;
	}
	else {
		$app=&$_SESSION["app"];
	}
	PNApplication::$instance=&$app;
	DataBase::$conn->select_db("students_".$app->current_domain);
	
	if (!isset($app->components[$component_name])) invalid("Invalid request: unknown component ".$component_name);
	
	switch($request_type){
	case "page":
		header("Content-Type: text/html;charset=UTF-8");
		$app->components[$component_name]->page($path);
		break;
	case "service":
		$app->components[$component_name]->service($path);
		break;
	default: invalid("Invalid request: unknown request type ".$request_type);
	}
	die();
	
	//nothing about "locale"
?>