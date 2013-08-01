<?php 
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
?>