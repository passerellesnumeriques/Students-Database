<?php 
function generate_component($name) {
	echo "   + Component ".$name."\n";
	global $generated_dir,$www_dir;
	mkdir($generated_dir."/component/".$name);
	$path = $www_dir."/component/".$name;
	
	$title2_counter = 1;
	$html = "<!DOCTYPE html>";
	$html .= "<html>";
	$html .= "<body>";
	
	$html .= "<h1>".$name."</h1>";
	
	if (file_exists($path."/doc/intro.html")) {
		$title = ($title2_counter++)." Introduction";
		$html .= "<h2>".$title."</h2>";
		$html .= file_get_contents($path."/doc/intro.html");
	}
	if (file_exists($path."/dependencies")) {
		$title = ($title2_counter++)." Dependencies";
		$html .= "<h2>".$title."</h2>";
		// TODO
	}
	if (file_exists($path."/datamodel.inc")) {
		$title = ($title2_counter++)." Data Model";
		$html .= "<h2>".$title."</h2>";
		// TODO
	}
	// TODO rights
	// TODO functionalities
	// TODO services
	// TODO pages
	if (file_exists($path."/static")) {
		$title = ($title2_counter++)." Static Resources";
		$html .= "<h2>".$title."</h2>";
		
		$image = array();
		$javascript = array();
		$unknown = array();
		
		$dir = opendir($path."/static");
		while (($filename = readdir($dir)) <> null) {
			$i = strrpos($filename, ".");
			if ($i === FALSE)
				array_push($unknown, $filename);
			else switch (substr($filename, $i+1)) {
				case "js": array_push($javascript, $filename); break;
				case "png": case "jpg": case "jpeg": case "gif": array_push($image, $filename); break;
				case "readme": break;
				default: array_push($unknown, $filename);
			}
		}
		closedir($dir);

		$title3_counter = 1;
		if (count($image) > 0) {
			mkdir($generated_dir."/component/".$name."/images");
			$title = ($title2_counter-1).".".($title3_counter++)." Images";
			$html .= "<h3>".$title."</h3>";
			$html .= "<ul>";
			foreach ($image as $filename) {
				copy_static($name, $filename, "component/".$name."/images/".$filename);
				$html .= "<li>".$filename." <img src='images/".$filename."'/></li>";
			}
			$html .= "</ul>";
		}
		if (count($javascript) > 0) {
			mkdir($generated_dir."/component/".$name."/javascript");
			$title = ($title2_counter-1).".".($title3_counter++)." Java Scripts";
			$html .= "<h3>".$title."</h3>";
			$html .= "<ul>";
			foreach ($javascript as $filename) {
				copy_static($name, $filename, "component/".$name."/javascript/".$filename);
				mkdir($generated_dir."/component/".$name."/javascript/".$filename."_doc");
				$cmd = dirname(__FILE__)."/tools/jsdoc/jsdoc.cmd --destination ".$generated_dir."/component/".$name."/javascript/".$filename."_doc ".$generated_dir."/component/".$name."/javascript/".$filename;
				if (file_exists($path."/static/".$filename.".readme"))
					$cmd .= " ".$path."/static/".$filename.".readme";
				else
					echo "WARNING: no readme file for JavaScript ".$path."/static/".$filename."\n";
				exec($cmd);
				$html .= "<li><a href='javascript/".$filename."_doc/index.html'>".$filename."</a>";
				if (file_exists($path."/static/".$filename.".readme"))
					$html .= ": ".file_get_contents($path."/static/".$filename.".readme");
				$html .= "</li>";
				unlink($generated_dir."/component/".$name."/javascript/".$filename); 
			}
			$html .= "</ul>";
		}
		if (count($unknown) > 0) {
			$title = ($title2_counter-1).".".($title3_counter++)." Unknown resource types";
			$html .= "<h3>".$title."</h3>";
			$html .= "<ul>";
			foreach ($unknown as $filename) {
				$html .= "<li>".$filename."</li>";
			}
			$html .= "</ul>";
		}
	}
	
	$html .= "</body>";
	$html .= "</html>";
	write_file("component/".$name."/index.html", $html);
}
?>