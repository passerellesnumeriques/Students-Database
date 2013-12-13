<?php 
class service_get_js extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}

	public function execute(&$component, $input) {
		$file = $input["path"];
		$filename = tempnam(sys_get_temp_dir(), "pn");
		unlink($filename);
		set_time_limit(120);
		$www_path = str_replace("\\","/",realpath(dirname($_SERVER["SCRIPT_FILENAME"])));
		$tools_path = str_replace("\\","/",realpath("component/documentation/tools"));
		$file = str_replace("\\","/",$file);
		session_write_close();
		$out = array();
		$ret = 0;
		exec("java.exe -cp \"$tools_path/rhino/bin;$tools_path/javascript_doc/bin\" org.pn.jsdoc.JavaScriptDocGenerator \"$www_path\" \"$filename\" \"$file\"", $out, $ret);
		if (!file_exists($filename)) {
			PNApplication::error("JavaScriptDocGenerator command failed (".$ret."): "."java.exe -cp \"$tools_path/rhino/bin;$tools_path/javascript_doc/bin\" org.pn.jsdoc.JavaScriptDocGenerator \"$www_path\" \"$filename\" \"$file\""."\r\n".json_encode($out));
			return;
		}
		$jsdoc = file_get_contents($filename);
		unlink($filename);
		echo json_encode($jsdoc);
	}
	
}
?>