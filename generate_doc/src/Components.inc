<?php 
class Components {
	
	public static function list_components() {
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
	
	public static function get_dependencies($component_name) {
		global $www_dir;
		$deps = array();
		if (file_exists($www_dir."/component/".$component_name."/dependencies")) {
			$f = fopen($www_dir."/component/".$component_name."/dependencies","r");
			while (($line = fgets($f, 4096)) !== false) {
				$line = trim($line);
				if (strlen($line) == 0) continue;
				$i = strpos($line,":");
				if ($i === FALSE) $i = strlen($line);
				$deps[substr($line,0,$i)] = substr($line,$i+1);
			}
			fclose($f);
		}
		return $deps;
	}
	
	public static function order_components_by_dependency($list) {
		$l = array();
		foreach ($list as $name)
			Components::order_components_by_dependency_($name, $l);
		return $l;
	}
	private static function order_components_by_dependency_($name, &$list) {
		if (in_array($name, $list)) return;
		$deps = Components::get_dependencies($name);
		foreach ($deps as $dep_name=>$dep_doc)
			Components::order_components_by_dependency_($dep_name, $list);
		array_push($list, $name);
	}
	
}
?>