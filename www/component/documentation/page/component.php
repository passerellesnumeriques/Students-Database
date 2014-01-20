<?php 
class page_component extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->add_stylesheet("/static/documentation/CodeDoc.css");
		$this->add_stylesheet("/static/documentation/style.css");
		
		$name = $_GET["name"];
		echo "<div style='padding:10px;text-align:center;font-size:x-large'>$name</div>";

		if (file_exists("component/".$name."/dependencies")) {
			echo "<h2><a name='dependencies'>Dependencies</a></h2>";
			echo "<center>";
			echo "<img src='/dynamic/documentation/service/component_dependencies?component=$name'/>";
			echo "</center>";
		}
		
		if (file_exists("component/".$name."/datamodel.inc")) {
			echo "<h2><a name='datamodel'>Data Model</a></h2>";
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
				if ($filename == "init_data.inc") continue;
				array_push($files, "component/$name/$filename");
			}
		}
		closedir($dir);
		echo "<h2><a name='php'>PHP</a></h2>";
		require_once("PHPDoc.inc");
		PHPDoc::generate_doc($files);
		
		if (file_exists("component/$name/service")) {
			echo "<h2><a name='services'>Services</a></h2>";
			echo "<table class='codedoc_table'>";
			echo "<tr><th>Service</th><th colspan=2>Description / Input / Output</th></tr>";
			$this->browse_services("component/$name/service", "");
			echo "</table>";
		}
		
		if (file_exists("component/".$name."/static/")) {
			$files = array();
			$this->browse_js("component/".$name."/static/", "", $files);
			if (count($files) > 0) {
				echo "<h2><a name='javascript'>JavaScript</a></h2>";
				
				require_once("JSDoc.inc");
				foreach ($files as $file) {
					echo "<h3><a name='js_$file'>$file</a></h3>";
					$container_id = $this->generateID();
					echo "<div id='$container_id'></div>";
					$this->onload("window.parent.init_jsdoc(function(){var js = window.parent.filter_jsdoc(window.parent.jsdoc, 'component/$name/$file'); window.parent.build_jsdoc_namespace_content(document.getElementById('$container_id'),js,true,'');});");
					//JSDoc::generate_doc($this, "component/".$name."/static/".$file);
				}
			}
		}
	}
	
	private function browse_js($path, $rel, &$files) {
		$dir = opendir($path);
		while (($filename = readdir($dir)) <> FALSE) {
			if (is_dir("$path/$filename")) {
				if ($filename == "." || $filename == "..") continue;
				$this->browse_js("$path/$filename/", "$rel$filename/", $files);
				continue;
			}
			$i = strrpos($filename, ".");
			if ($i === FALSE) continue;
			$ext = strtolower(substr($filename, $i+1));
			if ($ext <> "js") continue;
			array_push($files, "$rel$filename");
		}
		closedir($dir);
	}
	
	private function browse_services($path, $rel) {
		$dir = opendir($path);
		while (($filename = readdir($dir)) <> null) {
			if (substr($filename, 0, 1) == ".") continue;
			if (is_dir($path."/".$filename)) {
				$this->browse_services($path."/".$filename, $rel.$filename."/");
				continue;
			}
			$i = strrpos($filename, ".");
			if ($i === FALSE) continue;
			$ext = substr($filename, $i+1);
			$ext = strtolower($ext);
			if ($ext <> "php") continue;
			$this->service_doc($path."/".$filename, $rel.substr($filename,0,$i));
		}
		closedir($dir);
	}
	
	private function service_doc($file, $service) {
		$service_classname = "service_".str_replace("/", "_", $service);
		require_once("component/Service.inc");
		require_once($file);
		$c = new $service_classname();
		echo "<tr>";
		echo "<td class='codedoc_method' valign=top rowspan=3>".$service."</td>";
		echo "<td class='codedoc_comment' colspan=2>";
		$c->documentation();
		echo "</td>";
		echo "</tr>";
		echo "<th valign=top>Input</th>";
		echo "<td class='codedoc_comment'>";
		$c->input_documentation();
		echo "</td>";
		echo "</tr>";
		echo "<th valign=top>Output</th>";
		echo "<td class='codedoc_comment'>";
		$c->output_documentation();
		echo "</td>";
		echo "</tr>";
	}
	
}
?>