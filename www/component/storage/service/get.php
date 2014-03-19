<?php 
class service_get extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	public function get_output_format($input) {
		$id = $_GET["id"];
		$file = SQLQuery::create()->bypassSecurity()->select("Storage")->whereValue("Storage", "id", $id)->executeSingleRow();
		if ($file <> null && $file["mime"] <> null) return $file["mime"];
		return "application/octet-stream";
	}
	
	public function execute(&$component, $input) {
		$id = $_GET["id"];
		$file = SQLQuery::create()->bypassSecurity()->select("Storage")->whereValue("Storage", "id", $id)->executeSingleRow();
		if ($file == null) {
			PNApplication::error("Invalid storage id");
			return;
		}
		if (!$component->canReadFile($file)) {
			PNApplication::error("Access Denied.");
			return;
		}
		if (!isset($_GET["revision"]) || $_GET["revision"] <> $file["revision"]) {
			header("Location: ?id=".$id."&revision=".$file["revision"]);
			return;
		}
		header('Cache-Control: public', true);
		header('Pragma: public', true);
		$date = date("D, d M Y H:i:s",time());
		header('Date: '.$date, true);
		$expires = time()+60*24*60*60;
		header('Expires: '.date("D, d M Y H:i:s",$expires).' GMT', true);
		header('Vary: Cookie');
		$path = $component->get_data_path($id);
		header("Content-Length: ".filesize($path));
		readfile($path);
	}
	
}
?>