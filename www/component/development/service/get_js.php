<?php 
class service_get_js extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}

	public function execute(&$component, $input) {
		$file = realpath($input["path"]);
		$filename = tempnam(sys_get_temp_dir(), "pn");
		unlink($filename);
		set_time_limit(120);
		$www_path = realpath(dirname($_SERVER["SCRIPT_FILENAME"]));
		$tools_path = realpath("component/documentation/tools");
		session_write_close();
		exec("java.exe -cp \"$tools_path/rhino/bin;$tools_path/javascript_doc/bin\" org.pn.jsdoc.JavaScriptDocGenerator \"$www_path\" \"$filename\" \"$file\"");
		$jsdoc = file_get_contents($filename);
		unlink($filename);
		echo json_encode($jsdoc);
	}
	
}
?>