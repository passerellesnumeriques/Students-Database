<?php 
class service_remove extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$id = $input["id"];
		
		$attached = SQLQuery::create()->bypassSecurity()->select("AttachedDocument")->whereValue("AttachedDocument","document",$id)->executeSingleRow();
		if ($attached <> null) {
			$pi = $component->getAttachedDocumentsPlugin($attached["table"],$attached["type"]);
			if ($pi == null) {
				PNApplication::error("Invalid document");
				return;
			}
			if (!$pi->canAddAndRemove($attached["key"],$attached["sub_model"])) {
				PNApplication::error("Access denied");
				return;
			}
			require_once("component/data_model/DataBaseLock.inc");
			$lock = DataBaseLock::isLocked("Document", $id, null);
			if ($lock <> null) {
				if (is_integer($lock)) $who = "You";
				else {
					$i = strpos($lock, "\\");
					$who = PNApplication::$instance->user_management->getUserFullName(substr($lock,0,$i),substr($lock,$i+1));
				}
				PNApplication::error("The file is being edited by $who. You cannot remove it right now.");
				return;
			}
			SQLQuery::create()->bypassSecurity()->removeKey("Document", $id);
			if (!PNApplication::hasErrors())
				echo "true";
		} else
			PNApplication::error("Invalid document");
	}	
	
}
?>