<?php 
class service_component_dependencies extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Generate the image of the required datamodel"; }
	public function inputDocumentation() { echo "<code>component</code>: component name, or <i>all</i> for the global data model"; }
	public function outputDocumentation() { echo "The image of the data model"; }
	
	public function getOutputFormat($input) { return "image/png"; }
	
	public function execute(&$component, $input) {
		$name = $_GET["component"];

		$uml = "@startuml\n";
		if ($name == "all") {
			foreach (PNApplication::$instance->components as $c) {
				$uml .= "class ".$c->name." {\n";
				$uml .= "}\n";
			}
			foreach (PNApplication::$instance->components as $c) {
				if (!file_exists("component/".$c->name."/dependencies")) continue;
				$f = fopen("component/".$c->name."/dependencies","r");
				while (($line = fgets($f,4096)) !== FALSE) {
					$line = trim($line);
					if (strlen($line) == 0) continue;
					$i = strpos($line,":");
					if ($i !== FALSE) {
						$dep = trim(substr($line,0,$i));
						$comment = trim(substr($line,$i+1));
					} else {
						$dep = $line;
						$comment = "";
					}
					$uml .= $c->name." --> ".$dep;
					if ($comment <> "") $uml .= " : ".$comment;
					$uml .= "\n"; 
				}
				fclose($f);
			}
		} else {
			$uml .= "class ".$name." {\n";
			$uml .= "}\n";
			if (file_exists("component/".$name."/dependencies")) {
				$f = fopen("component/".$name."/dependencies","r");
				while (($line = fgets($f,4096)) !== FALSE) {
					$line = trim($line);
					if (strlen($line) == 0) continue;
					$i = strpos($line,":");
					if ($i !== FALSE) {
						$dep = trim(substr($line,0,$i));
						$comment = trim(substr($line,$i+1));
					} else {
						$dep = $line;
						$comment = "";
					}
					$uml .= $name." --> ".$dep;
					if ($comment <> "") $uml .= " : ".$comment;
					$uml .= "\n"; 
				}
				fclose($f);
			}
		}
		$uml .= "hide members\n";
		$uml .= "hide circle\n";
		$uml .= "@enduml\n";
		$base_filename = tempnam(sys_get_temp_dir(), "pn");
		unlink($base_filename);
		$filename = $base_filename.".uml";
		$f = fopen($filename, "w");
		fwrite($f, $uml);
		fclose($f);
		set_time_limit(120);
		$tools_path = realpath("component/documentation/tools");
		session_write_close();
		exec("java.exe -jar \"$tools_path/plantuml.jar\" -graphvizdot \"$tools_path/graphviz_2.28/bin/dot.exe\" \"$filename\"");
		unlink($filename);
		$filename = $base_filename.".png";
		readfile($filename);
		unlink($filename);
	}
	
}
?>