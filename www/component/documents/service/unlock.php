<?php 
class service_unlock extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$id = $_GET["id"];

		$attached = SQLQuery::create()->bypassSecurity()->select("AttachedDocument")->whereValue("AttachedDocument","document",$id)->executeSingleRow();
		if ($attached <> null) {
			$pi = $component->getAttachedDocumentsPlugin($attached["table"],$attached["type"]);
			if ($pi == null) {
				PNApplication::error("Invalid document");
				return;
			}
			if (!$pi->canWrite($attached["key"],$attached["sub_model"])) {
				PNApplication::error("Access denied");
				return;
			}
			require_once("component/data_model/DataBaseLock.inc");
			$lock = DataBaseLock::isLocked("Document", $id, null);
			if ($lock <> null && is_integer($lock))
				DataBaseLock::unlock($lock);
		} else {
			PNApplication::error("Invalid document");
			return;
		}
		echo "true";
	}
	
}
?>