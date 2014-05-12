<?php 
class service_static extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) {
		$path = $_GET["path"];
		$i = strrpos($path, ".");
		$ext = strtolower(substr($path, $i+1));
		switch ($ext) {
			case "png": return "image/png";
			case "gif": return "image/gif";
			case "jpg": case "jpeg": return "image/jpeg";
			default: return "text/plain";
		}
	}
	
	public function execute(&$component, $input) {
		$component = $_GET["component"];
		if (strpos($component, "..") !== false) die("Access denied");
		$path = $_GET["path"];
		if (strpos($path, "..") !== false) die("Access denied");
		readfile("component/$component/doc/$path");
	}
	
}
?>