<?php
echo "Generate technical documention\n";
echo " + Analyzing application\n";
// directories
global $www_dir, $generated_dir;
$www_dir = dirname(__FILE__)."/../www";
$generated_dir = dirname(__FILE__)."/../generated_doc";

$to_execute = array();

function list_components() {
	global $www_dir;
	$list = array();
	$dir = opendir($www_dir."/component");
	if ($dir == null) return $list;
	while (($filename = readdir($dir)) <> null) {
		if (substr($filename, 0, 1) == ".") continue;
		if (is_dir($www_dir."/component/".$filename)) array_push($list, $filename);
	}
	closedir($dir);
	return $list;
}
function order_components_($name, &$list) {
	if (in_array($name, $list)) return;
	global $www_dir;
	if (file_exists($www_dir."/component/".$name."/dependencies")) {
		$f = fopen($www_dir."/component/".$name."/dependencies","r");
		while (($line = fgets($f, 4096)) !== false) {
			$line = trim($line);
			if (strlen($line) == 0) continue;
			$i = strpos($line,":");
			if ($i === FALSE) $i = strlen($line);
			$dep = substr($line,0,$i);
			order_components_($dep, $list);
		}
		fclose($f);
	}
	array_push($list, $name);
}
function order_components($list) {
	$l = array();
	foreach ($list as $name)
		order_components_($name, $l);
	return $l;
}
global $components;
$components = list_components();
$components = order_components($components);

function execute($cmd) {
	global $to_execute;
	array_push($to_execute, $cmd);
}
function execute_commands() {
	global $generated_dir, $to_execute;
	echo "Execute external commands\n";
	mkdir($generated_dir."/batch");
	$main_batch = "@echo off\r\n";
	$sub_batch_index = 0;
	foreach ($to_execute as $cmd) {
		$name = "batch".($sub_batch_index++);
		if ($sub_batch_index == 5) {
			$main_batch .= "ping -n 1 -w 2000 1.2.3.4 > NUL 2>&1\r\n";
		} else if ($sub_batch_index < 10) {
			$main_batch .= "ping -n 1 -w 500 1.2.3.4 > NUL 2>&1\r\n";
		} else if (($sub_batch_index%3) == 0) {
			$main_batch .= "ping -n 1 -w 1000 1.2.3.4 > NUL 2>&1\r\n";
		} else {
			$main_batch .= "ping -n 1 -w 100 1.2.3.4 > NUL 2>&1\r\n";
		}
		$main_batch .= "START /B CMD /C CALL ".$name.".bat\r\n";
		$f = fopen($generated_dir."/batch/".$name.".bat","w");
		if (is_array($cmd)) {
			foreach ($cmd as $c)
				fwrite($f, $c."\r\n");
		} else {
			fwrite($f, $cmd."\r\n");
		}
		fclose($f);
	}
	$f = fopen($generated_dir."/batch/main.bat","w");
	fwrite($f, $main_batch);
	fclose($f);
	system("CD ".$generated_dir."/batch && main.bat");
	echo "Cleaning temporary files\n";
	remove_dir($generated_dir."/batch");
}
function write_file($filename, $content) {
	global $generated_dir;
	$path = $generated_dir."/".$filename;
	$f = fopen($path, "w");
	fwrite($f, $content);
	fclose($f);
}
function generate($page) {
	ob_start();
	include $page.".php";
	$content = ob_get_contents();
	ob_end_clean();
	write_file($page.".html", $content);
}
function generate_uml($uml, $filename) {
	write_file($filename.".uml", "@startuml\n".$uml."@enduml\n");
	$dir = dirname(__FILE__)."/tools";
	execute("java.exe -jar ".$dir."/plantuml.jar -graphvizdot ".$dir."/graphviz_2.28/bin/dot.exe"." ".dirname(__FILE__)."/../generated_doc/".$filename.".uml");
}
function copy_file($filename) {
	global $generated_dir;
	copy($filename, $generated_dir."/".$filename);
}
function mkdir_rec($dir) {
	if (is_dir($dir)) return;
	if (!is_dir(dirname($dir))) mkdir_rec(dirname($dir));
	mkdir($dir);
}
function copy_static($component,$static_name,$target_path) {
	global $generated_dir, $www_dir;
	mkdir_rec(dirname($generated_dir."/".$target_path));
	copy($www_dir."/component/".$component."/static/".$static_name, $generated_dir."/".$target_path);
}
function recurse_copy($src,$dst) {
	$dir = opendir($src);
	@mkdir($dst);
	while(false !== ( $file = readdir($dir)) ) {
		if (( $file != '.' ) && ( $file != '..' )) {
			if ( is_dir($src . '/' . $file) ) {
				recurse_copy($src . '/' . $file,$dst . '/' . $file);
			}
			else {
				copy($src . '/' . $file,$dst . '/' . $file);
			}
		}
	}
	closedir($dir);
}
function remove_dir($path) {
	$dir = @opendir($path);
	if ($dir == null) return;
	while(false !== ( $file = readdir($dir)) ) {
		if (( $file != '.' ) && ( $file != '..' )) {
			if ( is_dir($path . '/' . $file) ) {
				remove_dir($path . '/' . $file);
			}
			else {
				unlink($path . '/' . $file);
			}
		}
	}
	closedir($dir);
	rmdir($path);
}
function copy_dir($src, $dst) {
	$dir = opendir($src);
	while (($filename = readdir($dir)) <> null) {
		if ($filename == "." || $filename == "..") continue;
		if (is_dir($src."/".$filename)) {
			mkdir($dst."/".$filename);
			copy_dir($src."/".$filename, $dst."/".$filename);
		} else
			copy($src."/".$filename, $dst."/".$filename);
	}
	closedir($dir);
}
function copy_dir_flat($src, $dst) {
	$dir = opendir($src);
	while (($filename = readdir($dir)) <> null) {
		if ($filename == "." || $filename == "..") continue;
		if (is_dir($src."/".$filename)) continue;
		copy($src."/".$filename, $dst."/".$filename);
	}
	closedir($dir);
}

mkdir($generated_dir."/tmp");
echo " + Prepare PHP documentation\n";
foreach ($components as $name) {
	echo "  + Component ".$name."\n";
	mkdir_rec($generated_dir."/component/".$name."/php");
	mkdir_rec($generated_dir."/tmp/component/".$name."/php");
	$path = $www_dir."/component/".$name;
	copy_dir_flat($path, $generated_dir."/tmp/component/".$name."/php");
	execute(getenv("PHP_PATH")."/php.exe -c ".$generated_dir."/php.ini ".dirname(__FILE__)."/tools/apigen/apigen.php --source ".$generated_dir."/tmp/component/".$name."/php"." --destination ".$generated_dir."/component/".$name."/php --extensions inc,php");
}
execute_commands();

// main structure
echo " + Generate files\n";
copy_file("index.html");
copy_file("style.css");
copy_file("tree_expand.png");
copy_file("tree_collapse.png");
copy_file("list_circle.gif");
copy_file("component.png");
copy_file("dependencies.png");
copy_file("static.png");
copy_file("javascript.png");
copy_file("image.png");
copy_file("text.png");
copy_file("datamodel.png");
copy_file("php.gif");
copy_file("service.gif");
copy_static("javascript","utils.js","utils.js");

$nav = array();

// general
$nav_general = array();
mkdir($generated_dir."/general");
// general doc
copy_dir($www_dir."/doc", $generated_dir."/general");
$f = fopen($www_dir."/doc/index","r");
while (($line = fgets($f,4096)) <> null) {
	$line = trim($line);
	$i = strpos($line, ":");
	if ($i === FALSE) { echo "ERROR: invalid index file for general documentation"; continue; }
	$title = trim(substr($line,0,$i));
	$link = trim(substr($line,$i+1));
	array_push($nav_general, array($title,"general/".$link));
}
fclose($f);
// php
mkdir($generated_dir."/tmp/general_php");
mkdir($generated_dir."/tmp/general_php/component");
copy_dir_flat($www_dir,$generated_dir."/tmp/general_php");
copy_dir_flat($www_dir."/component",$generated_dir."/tmp/general_php/component");
mkdir($generated_dir."/general/php");
execute(getenv("PHP_PATH")."/php.exe -c ".$generated_dir."/php.ini ".dirname(__FILE__)."/tools/apigen/apigen.php --source ".$generated_dir."/tmp/general_php"." --destination ".$generated_dir."/general/php"." --extensions inc,php");
array_push($nav_general, array("PHP", "general/php/index.html"));

copy_file("general/data_model.html");
array_push($nav_general, array("Data Model", "general/data_model.html"));

array_push($nav, array("General", null, $nav_general));

// components
//mkdir($generated_dir."/component");
// generate index
write_file("component/index.html", "<img src='dependencies.png'/>");
$uml = "";
$uml .= "hide members\n";
$uml .= "hide circle\n";
foreach ($components as $c) {
	$uml .= "class ".$c."\n";
	if (file_exists($www_dir."/component/".$c."/dependencies")) {
		$f = fopen($www_dir."/component/".$c."/dependencies","r");
		while (($line = fgets($f, 4096)) !== false) {
			$line = trim($line);
			if (strlen($line) == 0) continue;
			$i = strpos($line,":");
			if ($i === FALSE) $i = strlen($line);
			$uml .= substr($line,0,$i)." <-- ".$c;
			$doc = trim(substr($line,$i+1));
			if (strlen($doc) > 0)
				$uml .= " : ".$doc;
			$uml .= "\n";
		}
		fclose($f);
	}
}
generate_uml($uml, "component/dependencies");
// generate each component
include "generate_component.php";
$nav_components = array();
$datamodel_uml = "";
foreach ($components as $c)
	generate_component($c, $nav_components, $datamodel_uml);

array_push($nav, array("Components", "component/index.html", $nav_components));
$datamodel_uml .= "hide methods\n";
generate_uml($datamodel_uml, "general/data_model");

// navigation and home
generate("navigation");
generate("home");

execute_commands();

echo "Cleaning temporary files\n";
remove_dir($generated_dir."/tmp");

echo "Generation done.\n";
/*

ob_start();

function start_generate() {
	ob_clean();
}
function end_generate($filename) {
	$content = ob_get_clean();
	write_file($filename, $content);
}
function write_file($filename, $content) {
	$path = dirname(__FILE__)."/../generated_doc/".$filename;
	$f = fopen($path, "w");
	fwrite($f, $content);
	fclose($f);
}

function generate_uml($uml, $filename) {
	write_file($filename.".uml", $uml);
	$dir = dirname(__FILE__)."/tools";
	exec("java.exe -jar ".$dir."/plantuml.jar -graphvizdot ".$dir."/graphviz_2.28/bin/dot.exe"." ".dirname(__FILE__)."/../generated_doc/".$filename.".uml");
}

start_generate();include "generate_index.php";end_generate("index.html");

$uml = "@startuml\n";
$uml .= "Object1 <|-- Object2\n";
$uml .= "@enduml\n";
generate_uml($uml, "test");
*/
?>