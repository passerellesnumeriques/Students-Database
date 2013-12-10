<?php 
class page_component extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->add_stylesheet("/static/documentation/CodeDoc.css");
		$this->add_stylesheet("/static/documentation/style.css");
		
		$name = $_GET["name"];
		echo "<div style='padding:10px;text-align:center;font-size:x-large'>$name</div>";
		if (file_exists("component/".$name."/datamodel.inc")) {
			echo "<h2>Data Model</h2>";
			echo "<center>";
			echo "<img src='/dynamic/documentation/service/datamodel?component=$name'/>";
			echo "</center>";
		}
		$files = array();
		$dir = opendir("component/".$name);
		while (($filename = readdir($dir)) !== FALSE) {
			if (is_dir("component/$name/$filename")) continue;
			$i = strrpos($filename, ".");
			if ($i === FALSE) continue;
			$ext = strtolower(substr($filename, $i+1));
			if ($ext == "php" || $ext == "inc") {
				if ($filename == "datamodel.inc") continue;
				array_push($files, "component/$name/$filename");
			}
		}
		closedir($dir);
		echo "<h2>PHP</h2>";
		require_once("PHPDoc.inc");
		PHPDoc::generate_doc($files);
	}
	
}
?>