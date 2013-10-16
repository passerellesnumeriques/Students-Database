<?php


function generate($page) {
	ob_start();
	include $page.".php";
	$content = ob_get_contents();
	ob_end_clean();
	write_file($page.".html", $content);
}
function copy_file($filename) {
	global $generated_dir;
	copy($filename, $generated_dir."/".$filename);
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