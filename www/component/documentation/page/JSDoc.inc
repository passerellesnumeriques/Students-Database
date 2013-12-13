<?php 
class JSDoc {
	
	public static function generate_doc(&$page, $file) {
		$page->require_javascript("jsdoc.js");
		$filename = tempnam(sys_get_temp_dir(), "pn");
		unlink($filename);
		set_time_limit(120);
		$www_path = str_replace("\\","/",realpath(dirname($_SERVER["SCRIPT_FILENAME"])));
		$tools_path = str_replace("\\","/",realpath("component/documentation/tools"));
		$file = str_replace("\\","/",$file);
		session_write_close();
		exec("java.exe -cp \"$tools_path/rhino/bin;$tools_path/javascript_doc/bin\" org.pn.jsdoc.JavaScriptDocGenerator \"$www_path\" \"$filename\" \"$file\"");
		$jsdoc = file_get_contents($filename);
		unlink($filename);
		
		$fct_name = $page->generate_id();
		$container_id = $page->generate_id();
		echo "<div id='$container_id'></div>";
		echo "<script type='text/javascript'>\r\n";
		echo "function $fct_name() {\r\n";
		echo $jsdoc;
		echo "build_jsdoc('$container_id',jsdoc);\r\n";
		echo "}\r\n";
		echo "$fct_name();";
		echo "</script>\r\n";
	}
	
}
?>