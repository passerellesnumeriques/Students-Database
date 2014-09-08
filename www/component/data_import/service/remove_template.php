<?php 
class service_remove_template extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$id = $input["id"];
		SQLQuery::startTransaction();
		$t = SQLQuery::create()->bypassSecurity()->select("DataImportTemplate")->whereValue("DataImportTemplate","id",$id)->executeSingleRow();
		if ($t == null) { PNApplication::error("This template does not exist anymore"); return; }
		$type = $t["type"];
		$type = $component->getTemplatePlugin($type);
		if ($type == null) { PNApplication::error("Invalid template plugin: ".$t["type"]); return; }
		if (!$type->canWrite()) { PNApplication::error("Access denied: you are not allowed to remove this template"); return; }
		SQLQuery::create()->bypassSecurity()->removeKey("DataImportTemplate", $id);
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>