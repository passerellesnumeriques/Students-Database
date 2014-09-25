<?php 
class service_get_js extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}

	public function execute(&$component, $input) {
		$file = @$input["path"];
		$filename = tempnam(sys_get_temp_dir(), "pn");
		unlink($filename);
		set_time_limit(120);
		$www_path = str_replace("\\","/",realpath(dirname($_SERVER["SCRIPT_FILENAME"])));
		$tools_path = str_replace("\\","/",realpath("component/documentation/tools"));
		$cmd = "java.exe -cp \"$tools_path/rhino/bin;$tools_path/javascript_doc/bin\" org.pn.jsdoc.JavaScriptDocGenerator \"$www_path\" \"$filename\"";
		if ($file <> null) { 
			$file = str_replace("\\","/",$file);
			$cmd .= " \"$file\"";
		}
		$cmd .= " 2>&1";
		$out = array();
		$ret = 0;
		exec($cmd, $out, $ret);
		if (!file_exists($filename)) {
			PNApplication::error("JavaScriptDocGenerator command failed (".$ret."): ".$cmd."\r\n".json_encode($out));
			return;
		}
		$jsdoc = file_get_contents($filename);
		unlink($filename);
		if ($jsdoc == null) {
			PNApplication::error("Cannot read the JavaScript doc generated");
		}
		$js = json_encode(utf8_encode($jsdoc));
		if ($js == null) {
			PNApplication::error("Error encoding JavaScript doc into JSON structure");
		}
		echo "{js:".$js.",out:[";
		$first = true;
		foreach ($out as $line) {
			if ($first) $first = false; else echo ",";
			echo json_encode(trim($line));
		}
		echo "]}";
	}
	
}
?>