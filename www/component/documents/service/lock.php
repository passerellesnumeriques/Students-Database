<?php 
class service_lock extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) {
		if (isset($_GET["format"]))
			switch ($_GET["format"]) {
				case "raw": return "text/plain;charset=UTF-8";
			}
		return parent::getOutputFormat($input);
	}
	
	public function execute(&$component, $input) {
		$id = $_GET["id"];
		$format = @$_GET["format"];

		$error = null;
		$locked = null;
		
		$attached = SQLQuery::create()->bypassSecurity()->select("AttachedDocument")->whereValue("AttachedDocument","document",$id)->executeSingleRow();
		if ($attached <> null) {
			$pi = $component->getAttachedDocumentsPlugin($attached["table"],$attached["type"]);
			if ($pi == null)
				$error = "Invalid document";
			else if (!$pi->canWrite($attached["key"],$attached["sub_model"]))
				$error = "Access denied";
			else {
				require_once("component/data_model/DataBaseLock.inc");
				DataBaseLock::lockRow("Document", $id, $locked, true);
			}
		} else {
			$error = "Invalid document";
		}
		if ($format == "raw") {
			if ($error <> null) echo $error;
			else if ($locked <> null) echo $locked;
			else echo "OK";
		} else {
			if ($error <> null) PNApplication::error($error);
			else if ($locked <> null) echo "{locked:".json_encode(($locked))."}";
			else echo "true";
		}
	}
	
}
?>