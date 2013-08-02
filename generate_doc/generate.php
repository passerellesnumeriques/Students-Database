<?php
echo "Generate technical documention\n";
echo " + Analyzing application\n";
// directories
global $www_dir, $generated_dir;
$www_dir = dirname(__FILE__)."/../www";
$generated_dir = dirname(__FILE__)."/../generated_doc";

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
global $components;
$components = list_components();

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
	write_file($filename.".uml", $uml);
	$dir = dirname(__FILE__)."/tools";
	exec("java.exe -jar ".$dir."/plantuml.jar -graphvizdot ".$dir."/graphviz_2.28/bin/dot.exe"." ".dirname(__FILE__)."/../generated_doc/".$filename.".uml");
}
function copy_file($filename) {
	global $generated_dir;
	copy($filename, $generated_dir."/".$filename);
}
function copy_static($component,$static_name,$target_path) {
	global $generated_dir, $www_dir;
	copy($www_dir."/component/".$component."/static/".$static_name, $generated_dir."/".$target_path);
}

echo " + Generate files\n";
copy_file("index.html");
copy_file("style.css");
copy_static("javascript","utils.js","utils.js");
generate("header");
generate("navigation");
generate("home");
mkdir($generated_dir."/component");
include "generate_component.php";
foreach ($components as $c)
	generate_component($c);

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