<?php 
class service_get extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	private $id = null;
	private $file = null;
	public function getOutputFormat($input) {
		$id = $_GET["id"];
		if ($this->file == null || $this->id <> $id) {
			$this->id = $id;
			$this->file = SQLQuery::create()->bypassSecurity()->select("Storage")->whereValue("Storage", "id", $id)->executeSingleRow();
		}
		if ($this->file <> null && $this->file["mime"] <> null) return $this->file["mime"];
		return "application/octet-stream";
	}
	
	public function execute(&$component, $input) {
		$id = $_GET["id"];
		if (isset($_POST["download"])) $_GET["filename"] = $_POST["download"];
		if ($this->file == null || $this->id <> $id) {
			$this->id = $id;
			$this->file = SQLQuery::create()->bypassSecurity()->select("Storage")->whereValue("Storage", "id", $id)->executeSingleRow();
		}
		if ($this->file == null) {
			header("HTTP/1.0 400 Invalid storage id");
			return;
		}
		if (!$component->canReadFile($this->file)) {
			header("HTTP/1.0 400 Access denied to this file");
			return;
		}
		if (!isset($_GET["revision"]) || $_GET["revision"] <> $this->file["revision"]) {
			header("Location: ?id=".$id."&revision=".$this->file["revision"].(isset($_GET["filename"]) ? "&filename=".urlencode($_GET["filename"]) : ""));
			return;
		}
		$path = $component->get_data_path($id);
		include("cache.inc");
		cacheHeadersFromFile($path);
		if (isset($_GET["filename"]))
			header("Content-Disposition: attachment; filename=\"".str_replace("\"","\\\"",$_GET["filename"])."\"");
		header("Content-Length: ".filesize($path));
		readfile($path);
	}
	
}
?>